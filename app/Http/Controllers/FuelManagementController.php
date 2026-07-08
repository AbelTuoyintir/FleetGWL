<?php

namespace App\Http\Controllers;

use App\Models\FuelLog;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\User;
use App\Models\FuelStation;
use App\Support\SqlDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\SqlDate;

class FuelManagementController extends Controller
{
    /**
     * Get active vehicles by registration/number plate.
     * Used for auto-filling the vehicle when user types the plate.
     */
    public function vehicleByPlate(Request $request)
    {
        $plate = trim((string) $request->query('plate', ''));
        if ($plate === '') {
            return response()->json(['success' => false, 'message' => 'plate is required'], 422);
        }

        // Normalize: remove all non-alphanumeric characters for robust matching
        $plateNormalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));

        $vehicle = Vehicle::whereIn('status', ['active', 'maintenance'])
            ->where(function($query) use ($plateNormalized) {
                // Remove spaces and hyphens in DB for comparison (SQLite friendly)
                $query->whereRaw("UPPER(REPLACE(REPLACE(registration_number, ' ', ''), '-', '')) = ?", [$plateNormalized]);
            })
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $lastLog = FuelLog::where('vehicle_id', $vehicle->id)
            ->where('status', '!=', 'deleted')
            ->latest('date')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vehicle->id,
                'registration_number' => $vehicle->registration_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'make_model' => trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')),
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'current_odometer' => $vehicle->current_odometer ?? $vehicle->mileage,
                'driver_id' => $vehicle->assigned_driver_id,
                'fuel_type' => $lastLog ? $lastLog->fuel_type : null,
                'display' => trim(($vehicle->registration_number ?? '') . ' - ' . trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? ''))),
            ],
        ]);
    }

    /**
     * Display a listing of fuel logs.
     */
public function index(Request $request)
{
    $query = FuelLog::with(['vehicle', 'driver', 'loggedBy'])
                    ->where('status', '!=', 'deleted')
                    ->orderBy('date', 'desc')
                    ->orderBy('created_at', 'desc');

    // Apply filters
    if ($request->filled('vehicle_id')) {
        $query->where('vehicle_id', $request->vehicle_id);
    }

    if ($request->filled('date_from')) {
        $query->where('date', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->where('date', '<=', $request->date_to);
    }

    if ($request->filled('fuel_type')) {
        $query->where('fuel_type', $request->fuel_type);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('driver_id')) {
        $query->where('driver_id', $request->driver_id);
    }

    $fuelLogs = $query->paginate(25);
    $vehicles = Vehicle::where('status', 'active')->get();

    // OPTION 1: Get all users as drivers (simplest solution)
    // $drivers = User::all();

    // OPTION 2: If you have a 'role' column in users table
        $drivers = Driver::with('user')->where('status', 'active')->get();
 
        // OPTION 3: If you have a separate drivers table
    // $drivers = Driver::all(); // If you have a Driver model

    $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
    $statuses = ['recorded', 'verified', 'rejected'];

    return view('admin.fuel-management', compact('fuelLogs', 'vehicles', 'drivers', 'fuelTypes', 'statuses'));
}

    /**
     * Show the form for creating a new fuel log.
     */
    public function create()
    {
        $vehicles = Vehicle::where('status', 'active')->get();
        $drivers = Driver::where('status', 'active')->get(); // Get from Driver model
        $fuelStations = FuelStation::where('is_active', true)->get();
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
        $paymentMethods = ['cash', 'credit_card', 'debit_card', 'company_account', 'fuel_card', 'mobile_payment'];

        return view('fuel-management.create', compact('vehicles', 'drivers', 'fuelStations', 'fuelTypes', 'paymentMethods'));
    }

    /**
     * Store a newly created fuel log.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'date' => 'required|date',
            'odometer' => 'required|numeric|min:0',
            'fuel_quantity' => 'required|numeric|min:0.1',
            'fuel_cost' => 'required|numeric|min:0',
            'fuel_price_per_unit' => 'required|numeric|min:0',
            'fuel_type' => 'required|string|in:petrol,diesel,electric,hybrid,cng,lpg',
            'fuel_station' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'driver_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'is_full_tank' => 'boolean',
            'is_maintenance_fuel' => 'boolean',
            'payment_method' => 'nullable|string|max:50',
        ]);

        // Get previous odometer reading
        $previousLog = FuelLog::where('vehicle_id', $validated['vehicle_id'])
                             ->where('date', '<', $validated['date'])
                             ->orderBy('date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->first();

        $validated['previous_odometer'] = $previousLog ? $previousLog->odometer : 0;
        $validated['distance_traveled'] = $validated['odometer'] - $validated['previous_odometer'];

        // Calculate fuel efficiency
        if ($validated['distance_traveled'] > 0 && $validated['fuel_quantity'] > 0) {
            $validated['fuel_efficiency'] = $validated['distance_traveled'] / $validated['fuel_quantity'];
            $validated['cost_per_distance'] = $validated['fuel_cost'] / $validated['distance_traveled'];
        }

        $validated['logged_by'] = auth()->id();
        $validated['status'] = 'recorded';

        $fuelLog = FuelLog::create($validated);

        // Update vehicle's current odometer
        $vehicle = Vehicle::find($validated['vehicle_id']);
        if ($vehicle->current_odometer < $validated['odometer']) {
            $vehicle->update(['current_odometer' => $validated['odometer']]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fuel log created successfully.',
                'data' => $fuelLog->fresh(['vehicle', 'driver', 'loggedBy']),
            ]);
        }

        return redirect()->route('fuel-management.index')
                        ->with('success', 'Fuel log created successfully.');
    }

    /**
     * Display the specified fuel log.
     */
    public function show(FuelLog $fuelLog)
    {
        $fuelLog->load(['vehicle', 'driver', 'loggedBy']);

        // Get related logs for statistics
        $previousLogs = FuelLog::where('vehicle_id', $fuelLog->vehicle_id)
                              ->where('date', '<', $fuelLog->date)
                              ->orderBy('date', 'desc')
                              ->limit(5)
                              ->get();

        $nextLogs = FuelLog::where('vehicle_id', $fuelLog->vehicle_id)
                          ->where('date', '>', $fuelLog->date)
                          ->orderBy('date', 'asc')
                          ->limit(5)
                          ->get();

        return view('fuel-management.show', compact('fuelLog', 'previousLogs', 'nextLogs'));
    }

    /**
     * Show the form for editing the specified fuel log.
     */
   public function edit(FuelLog $fuelLog)
    {
        $vehicles = Vehicle::where('status', 'active')->get();
        $drivers = User::where('status', 'active')->get();
        $fuelStations = FuelStation::where('is_active', true)->get();
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
        $paymentMethods = ['cash', 'credit_card', 'debit_card', 'company_account', 'fuel_card', 'mobile_payment'];

        return view('fuel-management.edit', compact('fuelLog', 'vehicles', 'drivers', 'fuelStations', 'fuelTypes', 'paymentMethods'));
    }

    /**
     * Update the specified fuel log.
     */
    public function update(Request $request, FuelLog $fuelLog)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'date' => 'required|date',
            'odometer' => 'required|numeric|min:0',
            'fuel_quantity' => 'required|numeric|min:0.1',
            'fuel_cost' => 'required|numeric|min:0',
            'fuel_price_per_unit' => 'required|numeric|min:0',
            'fuel_type' => 'required|string|in:petrol,diesel,electric,hybrid,cng,lpg',
            'fuel_station' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:100',
            'driver_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'is_full_tank' => 'boolean',
            'is_maintenance_fuel' => 'boolean',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:recorded,verified,rejected',
        ]);

        // Recalculate distances
        $previousLog = FuelLog::where('vehicle_id', $validated['vehicle_id'])
                             ->where('id', '!=', $fuelLog->id)
                             ->where('date', '<', $validated['date'])
                             ->orderBy('date', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->first();

        $validated['previous_odometer'] = $previousLog ? $previousLog->odometer : 0;
        $validated['distance_traveled'] = $validated['odometer'] - $validated['previous_odometer'];

        // Recalculate efficiency
        if ($validated['distance_traveled'] > 0 && $validated['fuel_quantity'] > 0) {
            $validated['fuel_efficiency'] = $validated['distance_traveled'] / $validated['fuel_quantity'];
            $validated['cost_per_distance'] = $validated['fuel_cost'] / $validated['distance_traveled'];
        } else {
            $validated['fuel_efficiency'] = null;
            $validated['cost_per_distance'] = null;
        }

        $fuelLog->update($validated);

        // Update subsequent logs if odometer changed
        if ($request->has('recalculate_subsequent') && $request->recalculate_subsequent) {
            $this->recalculateSubsequentLogs($fuelLog);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fuel log updated successfully.',
                'data' => $fuelLog->fresh(['vehicle', 'driver', 'loggedBy']),
            ]);
        }

        return redirect()->route('fuel-management.index')
                        ->with('success', 'Fuel log updated successfully.');
    }

    /**
     * Remove the specified fuel log.
     */
    public function destroy(FuelLog $fuelLog)
    {
        $fuelLog->update(['status' => 'deleted']);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fuel log deleted successfully.',
            ]);
        }

        return redirect()->route('fuel-management.index')
                        ->with('success', 'Fuel log deleted successfully.');
    }

    public function quickStats(Request $request)
    {
        // Bolt: Optimize by using database aggregation instead of in-memory collection methods.
        // This avoids fetching all records and hydrating models, significantly reducing memory and CPU usage.
        $query = FuelLog::where('status', '!=', 'deleted');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $stats = $query->selectRaw('
            SUM(fuel_quantity) as total_fuel,
            SUM(fuel_cost) as total_cost,
            SUM(distance_traveled) as total_distance,
            AVG(CASE WHEN fuel_efficiency > 0 THEN fuel_efficiency ELSE NULL END) as avg_efficiency
        ')->first();

        $totalCost = (float) ($stats->total_cost ?? 0);
        $totalDistance = (float) ($stats->total_distance ?? 0);

        return response()->json([
            'success' => true,
            'total_fuel' => (float) ($stats->total_fuel ?? 0),
            'total_cost' => $totalCost,
            'total_distance' => $totalDistance,
            'avg_efficiency' => (float) ($stats->avg_efficiency ?? 0),
            'avg_cost_per_km' => $totalDistance > 0 ? $totalCost / $totalDistance : 0,
        ]);
    }

    public function analyticsData(Request $request)
    {
        // Bolt: Optimize by using database-level aggregation instead of in-memory processing.
        // This avoids fetching thousands of records and hydrating models, significantly reducing memory and CPU usage.

        $baseQuery = FuelLog::where('fuel_logs.status', '!=', 'deleted');

        if ($request->filled('vehicle_id')) {
            $baseQuery->where('fuel_logs.vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('date_from')) {
            $baseQuery->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->where('date', '<=', $request->date_to);
        }

        // 1. Monthly Trends and Summary
        $yearSql = SqlDate::year('date');
        $monthSql = SqlDate::month('date');

        $monthlyStats = (clone $baseQuery)
            ->selectRaw("
                {$yearSql} as year,
                {$monthSql} as month,
                SUM(fuel_quantity) as fuel,
                SUM(fuel_cost) as cost,
                SUM(distance_traveled) as distance,
                AVG(CASE WHEN fuel_efficiency > 0 THEN fuel_efficiency ELSE NULL END) as efficiency
            ")
            ->groupBy(DB::raw($yearSql), DB::raw($monthSql))
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                $date = \Carbon\Carbon::createFromDate($item->year, $item->month, 1);
                $item->month_label = $date->format('M Y');
                return $item;
            });

        // 2. Fuel Types Distribution
        $fuelTypeStats = (clone $baseQuery)
            ->select('fuel_type')
            ->selectRaw('SUM(fuel_quantity) as total_fuel')
            ->groupBy('fuel_type')
            ->get();

        // 3. Efficiency by Vehicle
        $efficiencyStats = (clone $baseQuery)
            ->leftJoin('vehicles', 'fuel_logs.vehicle_id', '=', 'vehicles.id')
            ->select('vehicles.registration_number', 'vehicles.make')
            ->selectRaw('AVG(CASE WHEN fuel_efficiency > 0 THEN fuel_efficiency ELSE NULL END) as avg_efficiency')
            ->groupBy('fuel_logs.vehicle_id', 'vehicles.registration_number', 'vehicles.make')
            ->get();

        $data = [
            'months' => $monthlyStats->pluck('month_label')->values(),
            'fuel_data' => $monthlyStats->pluck('fuel')->map(fn($v) => (float)$v)->values(),
            'cost_data' => $monthlyStats->pluck('cost')->map(fn($v) => (float)$v)->values(),
            'fuel_types' => [
                'labels' => $fuelTypeStats->pluck('fuel_type')->values(),
                'values' => $fuelTypeStats->pluck('total_fuel')->map(fn($v) => (float)$v)->values(),
            ],
            'efficiency' => [
                'vehicles' => $efficiencyStats->map(function($v) {
                    return $v->registration_number ? ($v->registration_number . ' - ' . $v->make) : 'Unknown';
                })->values(),
                'values' => $efficiencyStats->pluck('avg_efficiency')->map(fn($v) => (float)$v)->values(),
            ],
            'monthly_summary' => $monthlyStats->map(function ($item) {
                $distance = (float) $item->distance;
                $cost = (float) $item->cost;
                return [
                    'month' => $item->month_label,
                    'fuel' => (float) $item->fuel,
                    'cost' => $cost,
                    'distance' => $distance,
                    'efficiency' => (float) ($item->efficiency ?? 0),
                    'cost_per_km' => $distance > 0 ? $cost / $distance : 0,
                ];
            })->values(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get fuel efficiency report.
     */
    public function efficiencyReport(Request $request)
    {
        $query = FuelLog::where('distance_traveled', '>', 0)
                       ->where('fuel_quantity', '>', 0);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $logs = $query->with('vehicle')->get();

        // Group by vehicle and calculate efficiency
        $efficiencyData = $logs->groupBy('vehicle_id')->map(function($vehicleLogs) {
            $totalDistance = $vehicleLogs->sum('distance_traveled');
            $totalFuel = $vehicleLogs->sum('fuel_quantity');

            return [
                'vehicle' => $vehicleLogs->first()->vehicle->name ?? 'Unknown',
                'total_distance' => $totalDistance,
                'total_fuel' => $totalFuel,
                'avg_efficiency' => $totalFuel > 0 ? $totalDistance / $totalFuel : 0,
                'logs_count' => $vehicleLogs->count(),
                'best_efficiency' => $vehicleLogs->max('fuel_efficiency'),
                'worst_efficiency' => $vehicleLogs->min('fuel_efficiency'),
            ];
        });

        $vehicles = Vehicle::where('status', 'active')->get();

        return view('fuel-management.efficiency-report', compact('efficiencyData', 'vehicles'));
    }

    /**
     * Bulk import fuel logs.
     */
    public function import()
    {
        return view('fuel-management.import');
    }

    /**
     * Process bulk import.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xls,xlsx',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        // Process import file
        // You can implement Excel/CSV parsing here

        return redirect()->route('fuel-management.index')
                        ->with('success', 'Fuel logs imported successfully.');
    }

    /**
     * Export fuel logs.
     */
    // Add at the top of the controller

    /**
     * Show export page.
     */
    public function exportPage()
    {
        $vehicles = Vehicle::where('status', 'active')->get();
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];

        return view('fuel-management.export', compact('vehicles', 'fuelTypes'));
    }

    /**
     * Export fuel logs.
     */
    public function export(Request $request)
    {
        $query = FuelLog::with(['vehicle', 'driver', 'loggedBy']);

        // Apply filters
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        $logs = $query->orderBy('date', 'desc')->get();

        if ($logs->isEmpty()) {
            return redirect()->route('fuel-management.index')
                            ->with('error', 'No fuel logs found matching your criteria.');
        }

        $format = $request->input('format', 'csv');

        if ($format === 'excel') {
            return $this->exportExcel($logs, $request);
        } elseif ($format === 'pdf') {
            return $this->exportPDF($logs, $request);
        } else {
            return $this->exportCSV($logs, $request);
        }
    }

    /**
     * Export to CSV.
     */
    private function exportCSV($logs, $request)
    {
        $filename = 'fuel-logs-' . date('Y-m-d-H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($logs, $request) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Add headers if requested
            if ($request->input('include_headers', true)) {
                $columns = $request->input('columns', [
                    'date', 'vehicle', 'odometer', 'fuel_quantity', 'fuel_cost',
                    'fuel_price_per_unit', 'fuel_type', 'fuel_station', 'driver',
                    'fuel_efficiency', 'status'
                ]);

                $headers = [];
                foreach ($columns as $column) {
                    $headers[] = $this->getColumnLabel($column);
                }

                fputcsv($file, $headers);
            }

            // Add data rows
            foreach ($logs as $log) {
                $row = [];
                $columns = $request->input('columns', [
                    'date', 'vehicle', 'odometer', 'fuel_quantity', 'fuel_cost',
                    'fuel_price_per_unit', 'fuel_type', 'fuel_station', 'driver',
                    'fuel_efficiency', 'status'
                ]);

                foreach ($columns as $column) {
                    $row[] = $this->getColumnValue($log, $column);
                }

                fputcsv($file, $row);
            }

            // Add summary if requested
            if ($request->input('include_summary', false)) {
                fputcsv($file, []);
                fputcsv($file, ['Summary Statistics']);
                fputcsv($file, ['Total Fuel:', $logs->sum('fuel_quantity') . ' L']);
                fputcsv($file, ['Total Cost:', '$' . number_format($logs->sum('fuel_cost'), 2)]);
                fputcsv($file, ['Total Distance:', $logs->sum('distance_traveled') . ' km']);
                fputcsv($file, ['Average Efficiency:', number_format($logs->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency'), 2) . ' km/L']);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get column label.
     */
    private function getColumnLabel($column)
    {
        $labels = [
            'date' => 'Date',
            'vehicle' => 'Vehicle',
            'odometer' => 'Odometer (km)',
            'previous_odometer' => 'Previous Odometer (km)',
            'distance_traveled' => 'Distance Traveled (km)',
            'fuel_quantity' => 'Fuel Quantity (L)',
            'fuel_cost' => 'Fuel Cost ($)',
            'fuel_price_per_unit' => 'Price per Unit ($/L)',
            'fuel_type' => 'Fuel Type',
            'fuel_station' => 'Fuel Station',
            'location' => 'Location',
            'receipt_number' => 'Receipt Number',
            'driver' => 'Driver',
            'logged_by' => 'Logged By',
            'fuel_efficiency' => 'Fuel Efficiency (km/L)',
            'cost_per_distance' => 'Cost per km ($/km)',
            'payment_method' => 'Payment Method',
            'status' => 'Status',
            'is_full_tank' => 'Full Tank',
            'is_maintenance_fuel' => 'Maintenance Fuel',
            'notes' => 'Notes',
            'created_at' => 'Created Date'
        ];

        return $labels[$column] ?? ucfirst(str_replace('_', ' ', $column));
    }

    /**
     * Get column value.
     */
    private function getColumnValue($log, $column)
    {
        switch ($column) {
            case 'date':
                return $log->date->format('Y-m-d');
            case 'vehicle':
                return $log->vehicle->name ?? 'N/A';
            case 'odometer':
                return $log->odometer;
            case 'previous_odometer':
                return $log->previous_odometer ?? 0;
            case 'distance_traveled':
                return $log->distance_traveled ?? 0;
            case 'fuel_quantity':
                return $log->fuel_quantity;
            case 'fuel_cost':
                return $log->fuel_cost;
            case 'fuel_price_per_unit':
                return $log->fuel_price_per_unit;
            case 'fuel_type':
                return $log->fuel_type;
            case 'fuel_station':
                return $log->fuel_station ?? '';
            case 'location':
                return $log->location ?? '';
            case 'receipt_number':
                return $log->receipt_number ?? '';
            case 'driver':
                return $log->driver->name ?? 'N/A';
            case 'logged_by':
                return $log->loggedBy->name ?? 'System';
            case 'fuel_efficiency':
                return $log->fuel_efficiency ?? 0;
            case 'cost_per_distance':
                return $log->cost_per_distance ?? 0;
            case 'payment_method':
                return $log->payment_method ?? '';
            case 'status':
                return $log->status;
            case 'is_full_tank':
                return $log->is_full_tank ? 'Yes' : 'No';
            case 'is_maintenance_fuel':
                return $log->is_maintenance_fuel ? 'Yes' : 'No';
            case 'notes':
                return $log->notes ?? '';
            case 'created_at':
                return $log->created_at->format('Y-m-d H:i:s');
            default:
                return '';
        }
    }

    /**
     * Export to Excel (requires maatwebsite/excel package).
     */
    private function exportExcel($logs, $request)
    {
        // If you have maatwebsite/excel package installed
        // return Excel::download(new FuelLogsExport($logs, $request), 'fuel-logs.xlsx');

        // Fallback to CSV if Excel package not installed
        return $this->exportCSV($logs, $request);
    }

    /**
     * Export to PDF (requires barryvdh/laravel-dompdf package).
     */
    private function exportPDF($logs, $request)
    {
        // If you have dompdf package installed
        // $pdf = PDF::loadView('fuel-management.pdf', compact('logs'));
        // return $pdf->download('fuel-logs.pdf');

        // Fallback to CSV if PDF package not installed
        return redirect()->route('fuel-management.export-page')
                        ->with('error', 'PDF export requires dompdf package. Exporting as CSV instead.');
    }

    /**
     * Get fuel consumption forecast.
     */
    public function forecast(Request $request)
    {
        $vehicleId = $request->vehicle_id;
        $period = $request->period ?? 30; // days

        if (!$vehicleId) {
            return response()->json(['error' => 'Vehicle ID required'], 400);
        }

        // Get historical data
        $historicalData = FuelLog::where('vehicle_id', $vehicleId)
                                ->where('date', '>=', now()->subDays(90))
                                ->orderBy('date')
                                ->get();

        if ($historicalData->count() < 5) {
            return response()->json(['error' => 'Insufficient historical data'], 400);
        }

        // Calculate average daily consumption
        $totalFuel = $historicalData->sum('fuel_quantity');
        $totalDays = $historicalData->count();
        $avgDailyFuel = $totalFuel / $totalDays;

        // Generate forecast
        $forecast = [];
        $currentDate = now();
        $estimatedOdometer = $historicalData->last()->odometer;

        for ($i = 1; $i <= $period; $i++) {
            $date = $currentDate->copy()->addDays($i);
            $estimatedOdometer += ($avgDailyFuel * 10); // Assuming 10 km per liter average

            $forecast[] = [
                'date' => $date->format('Y-m-d'),
                'estimated_fuel' => $avgDailyFuel,
                'estimated_cost' => $avgDailyFuel * 100, // Assuming 100 per unit
                'estimated_odometer' => $estimatedOdometer,
            ];
        }

        return response()->json([
            'success' => true,
            'vehicle_id' => $vehicleId,
            'period' => $period,
            'forecast' => $forecast,
        ]);
    }

    

    /**
     * Get fuel statistics and analytics with vehicle filtering
     */
    public function analytics(Request $request)
    {
        $query = FuelLog::with(['vehicle', 'driver', 'loggedBy'])
            ->where('status', '!=', 'deleted');

        // Apply filters
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        $logs = $query->get();
        
        // Get selected vehicle info for display
        $selectedVehicle = null;
        if ($request->filled('vehicle_id')) {
            $selectedVehicle = Vehicle::find($request->vehicle_id);
        }

        // Calculate statistics
        $stats = [
            'total_fuel' => $logs->sum('fuel_quantity'),
            'total_cost' => $logs->sum('fuel_cost'),
            'total_distance' => $logs->sum('distance_traveled'),
            'avg_efficiency' => $logs->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency'),
            'avg_cost_per_km' => $logs->where('cost_per_distance', '>', 0)->avg('cost_per_distance'),
            
            // Vehicle-specific stats (if vehicle selected)
            'vehicle_info' => $selectedVehicle ? [
                'name' => $selectedVehicle->registration_number . ' - ' . $selectedVehicle->make . ' ' . $selectedVehicle->model,
                'total_logs' => $logs->count(),
                'first_record' => $logs->min('date'),
                'last_record' => $logs->max('date'),
                'current_mileage' => $selectedVehicle->current_odometer ?? $logs->max('odometer') ?? 0,
            ] : null,
            
            // Fuel by type
            'fuel_by_type' => $logs->groupBy('fuel_type')->map(function($group) {
                return [
                    'quantity' => $group->sum('fuel_quantity'),
                    'cost' => $group->sum('fuel_cost'),
                    'percentage' => 0 // Will calculate later
                ];
            }),
            
            // Cost by vehicle (for comparison)
            'cost_by_vehicle' => $logs->groupBy('vehicle_id')->map(function($group) {
                $vehicle = $group->first()->vehicle;
                return [
                    'vehicle_id' => $group->first()->vehicle_id,
                    'vehicle_name' => $vehicle->registration_number . ' - ' . $vehicle->make . ' ' . $vehicle->model,
                    'total_cost' => $group->sum('fuel_cost'),
                    'total_fuel' => $group->sum('fuel_quantity'),
                    'total_distance' => $group->sum('distance_traveled'),
                    'avg_efficiency' => $group->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency'),
                    'logs_count' => $group->count()
                ];
            })->sortByDesc('total_cost'),
            
            // Monthly trends
            'monthly_trends' => $logs->groupBy(function($log) {
                return $log->date->format('Y-m');
            })->sortKeys()->map(function($group) {
                return [
                    'fuel' => (float)$group->sum('fuel_quantity'),
                    'cost' => (float)$group->sum('fuel_cost'),
                    'distance' => (float)$group->sum('distance_traveled'),
                    'avg_efficiency' => $group->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency')
                ];
            }),
            
            // Weekly trends (last 12 weeks)
            'weekly_trends' => $logs->groupBy(function($log) {
                return $log->date->format('Y-W');
            })->sortKeys()->take(12)->map(function($group) {
                return [
                    'fuel' => (float)$group->sum('fuel_quantity'),
                    'cost' => (float)$group->sum('fuel_cost'),
                    'distance' => (float)$group->sum('distance_traveled')
                ];
            }),
            
            // Efficiency trend over time
            'efficiency_trend' => $logs->sortBy('date')->groupBy(function($log) {
                return $log->date->format('Y-m');
            })->map(function($group) {
                return $group->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency');
            })->filter(),
            
            // Top 5 most efficient vehicles
            'top_efficient_vehicles' => FuelLog::whereIn('vehicle_id', $logs->pluck('vehicle_id')->unique())
                ->where('fuel_efficiency', '>', 0)
                ->select('vehicle_id', DB::raw('AVG(fuel_efficiency) as avg_efficiency'), DB::raw('COUNT(*) as logs_count'))
                ->groupBy('vehicle_id')
                ->with('vehicle')
                ->orderBy('avg_efficiency', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'vehicle_name' => $item->vehicle->registration_number . ' - ' . $item->vehicle->make,
                        'avg_efficiency' => round($item->avg_efficiency, 1),
                        'logs_count' => $item->logs_count
                    ];
                }),
            
            // Bottom 5 least efficient vehicles
            'bottom_efficient_vehicles' => FuelLog::whereIn('vehicle_id', $logs->pluck('vehicle_id')->unique())
                ->where('fuel_efficiency', '>', 0)
                ->select('vehicle_id', DB::raw('AVG(fuel_efficiency) as avg_efficiency'), DB::raw('COUNT(*) as logs_count'))
                ->groupBy('vehicle_id')
                ->with('vehicle')
                ->orderBy('avg_efficiency', 'asc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'vehicle_name' => $item->vehicle->registration_number . ' - ' . $item->vehicle->make,
                        'avg_efficiency' => round($item->avg_efficiency, 1),
                        'logs_count' => $item->logs_count
                    ];
                }),
            
            // Cost comparison (current period vs previous period)
            'cost_comparison' => $this->calculateCostComparison($logs, $request),
            
            // Chart data
            'chart_trends' => [
                'labels' => $logs->groupBy(fn($log) => $log->date->format('M Y'))->sortKeys()->keys(),
                'fuel' => $logs->groupBy(fn($log) => $log->date->format('M Y'))->sortKeys()->map(fn($group) => (float)$group->sum('fuel_quantity'))->values(),
                'cost' => $logs->groupBy(fn($log) => $log->date->format('M Y'))->sortKeys()->map(fn($group) => (float)$group->sum('fuel_cost'))->values(),
                'distance' => $logs->groupBy(fn($log) => $log->date->format('M Y'))->sortKeys()->map(fn($group) => (float)$group->sum('distance_traveled'))->values(),
            ],
            'chart_efficiency' => $logs->groupBy('vehicle_id')->map(function($group) {
                $vehicle = $group->first()->vehicle;
                return [
                    'name' => $vehicle ? ($vehicle->registration_number . ' - ' . $vehicle->make) : 'Unknown',
                    'efficiency' => (float)($group->where('fuel_efficiency', '>', 0)->avg('fuel_efficiency') ?? 0)
                ];
            })->sortByDesc('efficiency')->values(),
        ];
        
        // Calculate percentages for fuel by type
        $totalFuel = $stats['total_fuel'];
        if ($totalFuel > 0) {
            foreach ($stats['fuel_by_type'] as $type => &$data) {
                $data['percentage'] = round(($data['quantity'] / $totalFuel) * 100, 1);
            }
        }
        
        // Get list of vehicles for filter dropdown
        $vehicles = Vehicle::where('status', 'active')->orderBy('registration_number')->get();
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
        
        // For AJAX requests, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'selected_vehicle' => $selectedVehicle,
                'vehicles' => $vehicles
            ]);
        }
        
        // For normal view
        return view('fuel-management.analytics', compact('stats', 'vehicles', 'selectedVehicle', 'fuelTypes'));
    }

    /**
     * Calculate cost comparison between current and previous period
     */
    private function calculateCostComparison($logs, $request)
    {
        $currentPeriodStart = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->subDays(30);
        $currentPeriodEnd = $request->filled('date_to') ? Carbon::parse($request->date_to) : now();
        
        $periodLength = $currentPeriodStart->diffInDays($currentPeriodEnd);
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($periodLength);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();
        
        $currentTotalCost = $logs->sum('fuel_cost');
        $currentTotalFuel = $logs->sum('fuel_quantity');
        
        $previousLogs = FuelLog::whereBetween('date', [$previousPeriodStart, $previousPeriodEnd]);
        
        if ($request->filled('vehicle_id')) {
            $previousLogs->where('vehicle_id', $request->vehicle_id);
        }
        
        $previousLogs = $previousLogs->get();
        $previousTotalCost = $previousLogs->sum('fuel_cost');
        $previousTotalFuel = $previousLogs->sum('fuel_quantity');
        
        $costChange = $previousTotalCost > 0 ? (($currentTotalCost - $previousTotalCost) / $previousTotalCost) * 100 : 0;
        $fuelChange = $previousTotalFuel > 0 ? (($currentTotalFuel - $previousTotalFuel) / $previousTotalFuel) * 100 : 0;
        
        return [
            'current_period' => [
                'start' => $currentPeriodStart->format('Y-m-d'),
                'end' => $currentPeriodEnd->format('Y-m-d'),
                'cost' => $currentTotalCost,
                'fuel' => $currentTotalFuel
            ],
            'previous_period' => [
                'start' => $previousPeriodStart->format('Y-m-d'),
                'end' => $previousPeriodEnd->format('Y-m-d'),
                'cost' => $previousTotalCost,
                'fuel' => $previousTotalFuel
            ],
            'cost_change_percent' => round($costChange, 1),
            'fuel_change_percent' => round($fuelChange, 1),
            'trend' => $costChange <= 0 ? 'down' : 'up'
        ];
    }

    public function editData(FuelLog $fuelManagement)
    {
        return response()->json([
            'success' => true,
            'data' => $fuelManagement,
        ]);
    }

        /**
     * Display fuel cost analysis page
     */
    public function costAnalysis(Request $request)
    {
        $vehicles = Vehicle::where('status', 'active')->orderBy('registration_number')->get();
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'];
        
        // Get default date range (last 30 days)
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        return view('fuel.cost-analysis', compact('vehicles', 'fuelTypes', 'dateFrom', 'dateTo'));
    }

    /**
     * Get cost analysis data for AJAX requests
     */
    public function getCostAnalysisData(Request $request)
    {
        try {
            $query = FuelLog::where('status', '!=', 'deleted')
                ->with(['vehicle', 'driver']);
            
            // Apply filters
            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }
            
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
            
            if ($request->filled('fuel_type')) {
                $query->where('fuel_type', $request->fuel_type);
            }
            
            $logs = $query->get();
            
            // Calculate totals
            $totalCost = $logs->sum('fuel_cost');
            $totalFuel = $logs->sum('fuel_quantity');
            $totalDistance = $logs->sum('distance_traveled');
            $avgFuelPrice = $totalFuel > 0 ? $totalCost / $totalFuel : 0;
            $avgCostPerKm = $totalDistance > 0 ? $totalCost / $totalDistance : 0;
            
            // Cost by vehicle
            $costByVehicle = $logs->groupBy('vehicle_id')->map(function($group) {
                $vehicle = $group->first()->vehicle;
                $totalCost = $group->sum('fuel_cost');
                $totalFuel = $group->sum('fuel_quantity');
                $totalDistance = $group->sum('distance_traveled');
                
                return [
                    'vehicle_name' => $vehicle ? ($vehicle->registration_number . ' - ' . $vehicle->make . ' ' . $vehicle->model) : 'Unknown',
                    'total_cost' => $totalCost,
                    'total_fuel' => $totalFuel,
                    'total_distance' => $totalDistance,
                    'avg_fuel_price' => $totalFuel > 0 ? $totalCost / $totalFuel : 0,
                    'cost_per_km' => $totalDistance > 0 ? $totalCost / $totalDistance : 0,
                    'logs_count' => $group->count(),
                    'percentage' => 0 // Will calculate later
                ];
            })->sortByDesc('total_cost');
            
            // Calculate percentages
            $totalAllCost = $costByVehicle->sum('total_cost');
            foreach ($costByVehicle as &$vehicle) {
                $vehicle['percentage'] = $totalAllCost > 0 ? round(($vehicle['total_cost'] / $totalAllCost) * 100, 1) : 0;
            }
            
            // Cost by month
            $costByMonth = $logs->groupBy(function($log) {
                return $log->date->format('Y-m');
            })->sortKeys()->map(function($group) {
                return [
                    'month' => $group->first()->date->format('F Y'),
                    'month_key' => $group->first()->date->format('M Y'),
                    'total_cost' => $group->sum('fuel_cost'),
                    'total_fuel' => $group->sum('fuel_quantity'),
                    'total_distance' => $group->sum('distance_traveled'),
                    'avg_fuel_price' => $group->sum('fuel_quantity') > 0 ? $group->sum('fuel_cost') / $group->sum('fuel_quantity') : 0,
                ];
            });
            
            // Cost by fuel type
            $costByFuelType = $logs->groupBy('fuel_type')->map(function($group) {
                $totalCost = $group->sum('fuel_cost');
                $totalFuel = $group->sum('fuel_quantity');
                return [
                    'total_cost' => $totalCost,
                    'total_fuel' => $totalFuel,
                    'avg_price' => $totalFuel > 0 ? $totalCost / $totalFuel : 0,
                    'color' => $this->getFuelTypeColor($group->first()->fuel_type)
                ];
            });
            
            // Daily cost trend (last 30 days)
            $dailyCost = $logs->groupBy(function($log) {
                return $log->date->format('Y-m-d');
            })->sortKeys()->take(30)->map(function($group) {
                return [
                    'date' => $group->first()->date->format('M d'),
                    'cost' => $group->sum('fuel_cost'),
                    'fuel' => $group->sum('fuel_quantity')
                ];
            });
            
            // Weekly comparison
            $weeklyComparison = $this->calculateWeeklyComparison($logs);
            
            // Cost efficiency metrics
            $costEfficiency = [
                'most_efficient' => $costByVehicle->filter(function($item) {
                    return $item['cost_per_km'] > 0;
                })->sortBy('cost_per_km')->take(5)->values(),
                'least_efficient' => $costByVehicle->filter(function($item) {
                    return $item['cost_per_km'] > 0;
                })->sortByDesc('cost_per_km')->take(5)->values(),
            ];
            
            // Summary statistics
            $summary = [
                'total_cost' => $totalCost,
                'total_fuel' => $totalFuel,
                'total_distance' => $totalDistance,
                'avg_fuel_price' => $avgFuelPrice,
                'avg_cost_per_km' => $avgCostPerKm,
                'total_logs' => $logs->count(),
                'unique_vehicles' => $logs->unique('vehicle_id')->count(),
                'period_days' => $this->getPeriodDays($request),
            ];
            
            // Calculate trends
            $trends = $this->calculateTrends($logs, $request);
            
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'cost_by_vehicle' => $costByVehicle->values(),
                'cost_by_month' => $costByMonth->values(),
                'cost_by_fuel_type' => $costByFuelType,
                'daily_cost' => $dailyCost,
                'weekly_comparison' => $weeklyComparison,
                'cost_efficiency' => $costEfficiency,
                'trends' => $trends,
                'filters_applied' => [
                    'vehicle_id' => $request->vehicle_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'fuel_type' => $request->fuel_type
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Cost analysis error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cost analysis data'
            ], 500);
        }
    }

    /**
     * Get detailed cost breakdown for a specific vehicle
     */
    public function getCostBreakdown(Request $request)
    {
        try {
            $vehicleId = $request->vehicle_id;
            
            if (!$vehicleId) {
                return response()->json(['success' => false, 'message' => 'Vehicle ID required'], 400);
            }
            
            $query = FuelLog::where('vehicle_id', $vehicleId)
                ->where('status', '!=', 'deleted')
                ->with(['vehicle', 'driver']);
            
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
            
            $logs = $query->orderBy('date', 'desc')->get();
            
            // Calculate cost per month for this vehicle
            $monthlyBreakdown = $logs->groupBy(function($log) {
                return $log->date->format('Y-m');
            })->map(function($group) {
                return [
                    'month' => $group->first()->date->format('F Y'),
                    'total_cost' => $group->sum('fuel_cost'),
                    'total_fuel' => $group->sum('fuel_quantity'),
                    'total_distance' => $group->sum('distance_traveled'),
                    'avg_price' => $group->sum('fuel_quantity') > 0 ? $group->sum('fuel_cost') / $group->sum('fuel_quantity') : 0,
                    'cost_per_km' => $group->sum('distance_traveled') > 0 ? $group->sum('fuel_cost') / $group->sum('distance_traveled') : 0
                ];
            });
            
            // Recent expensive transactions
            $expensiveTransactions = $logs->sortByDesc('fuel_cost')->take(10);
            
            // Statistics
            $totalCost = $logs->sum('fuel_cost');
            $totalFuel = $logs->sum('fuel_quantity');
            $totalDistance = $logs->sum('distance_traveled');
            
            return response()->json([
                'success' => true,
                'vehicle' => [
                    'id' => $vehicleId,
                    'name' => $logs->first()?->vehicle?->registration_number ?? 'Unknown',
                    'make' => $logs->first()?->vehicle?->make ?? 'N/A',
                    'model' => $logs->first()?->vehicle?->model ?? 'N/A'
                ],
                'summary' => [
                    'total_cost' => $totalCost,
                    'total_fuel' => $totalFuel,
                    'total_distance' => $totalDistance,
                    'avg_fuel_price' => $totalFuel > 0 ? $totalCost / $totalFuel : 0,
                    'cost_per_km' => $totalDistance > 0 ? $totalCost / $totalDistance : 0,
                    'total_logs' => $logs->count()
                ],
                'monthly_breakdown' => $monthlyBreakdown,
                'expensive_transactions' => $expensiveTransactions->map(function($log) {
                    return [
                        'date' => $log->date->format('Y-m-d'),
                        'fuel_quantity' => $log->fuel_quantity,
                        'fuel_cost' => $log->fuel_cost,
                        'fuel_type' => $log->fuel_type,
                        'fuel_station' => $log->fuel_station,
                        'odometer' => $log->odometer,
                        'driver' => $log->driver?->name ?? 'Unknown'
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Cost breakdown error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load breakdown'], 500);
        }
    }

    /**
     * Helper: Calculate weekly comparison
     */
    private function calculateWeeklyComparison($logs)
    {
        $currentWeek = $logs->filter(function($log) {
            return $log->date->between(now()->startOfWeek(), now()->endOfWeek());
        });
        
        $previousWeek = $logs->filter(function($log) {
            return $log->date->between(now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek());
        });
        
        $currentCost = $currentWeek->sum('fuel_cost');
        $previousCost = $previousWeek->sum('fuel_cost');
        
        $costChange = $previousCost > 0 ? (($currentCost - $previousCost) / $previousCost) * 100 : 0;
        
        return [
            'current_week_cost' => $currentCost,
            'previous_week_cost' => $previousCost,
            'cost_change_percent' => round($costChange, 1),
            'trend' => $costChange <= 0 ? 'down' : 'up'
        ];
    }

    /**
     * Helper: Calculate trends (current vs previous period)
     */
    private function calculateTrends($logs, $request)
    {
        // Get current period from request
        $currentStart = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->subDays(30);
        $currentEnd = $request->filled('date_to') ? Carbon::parse($request->date_to) : now();
        
        $periodLength = $currentStart->diffInDays($currentEnd);
        $previousStart = $currentStart->copy()->subDays($periodLength);
        $previousEnd = $currentStart->copy()->subDay();
        
        $currentCost = $logs->sum('fuel_cost');
        $currentFuel = $logs->sum('fuel_quantity');
        
        $previousLogs = FuelLog::whereBetween('date', [$previousStart, $previousEnd]);
        
        if ($request->filled('vehicle_id')) {
            $previousLogs->where('vehicle_id', $request->vehicle_id);
        }
        
        $previousLogs = $previousLogs->get();
        $previousCost = $previousLogs->sum('fuel_cost');
        $previousFuel = $previousLogs->sum('fuel_quantity');
        
        return [
            'cost_change' => $previousCost > 0 ? round((($currentCost - $previousCost) / $previousCost) * 100, 1) : 0,
            'fuel_change' => $previousFuel > 0 ? round((($currentFuel - $previousFuel) / $previousFuel) * 100, 1) : 0,
            'previous_cost' => $previousCost,
            'previous_fuel' => $previousFuel,
            'previous_period_days' => $periodLength
        ];
    }

    /**
     * Helper: Get period days
     */
    private function getPeriodDays($request)
    {
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $start = Carbon::parse($request->date_from);
            $end = Carbon::parse($request->date_to);
            return $start->diffInDays($end) + 1;
        }
        return 30;
    }

    /**
     * Helper: Get fuel type color for charts
     */
    private function getFuelTypeColor($type)
    {
        $colors = [
            'petrol' => '#3b82f6',
            'diesel' => '#f59e0b',
            'electric' => '#10b981',
            'hybrid' => '#8b5cf6',
            'cng' => '#06b6d4',
            'lpg' => '#ef4444'
        ];
        return $colors[$type] ?? '#6b7280';
    }
}
