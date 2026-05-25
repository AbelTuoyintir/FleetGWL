<?php

namespace App\Http\Controllers;
use App\Models\FuelLog;
use App\Models\VehicleMaintenance;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\FuelStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class FuelMileageController extends Controller
{

 /**
     * Dashboard for fuel and mileage
     */
    public function dashboard()
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return view('driver.no-vehicle');
        }

        // Get recent maintenance records
        $recentMaintenances = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'deleted')
            ->with(['vehicle', 'driver'])
            ->latest()
            ->take(5)
            ->get();

        // Get recent mileage logs
        $recentMileageLogs = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'deleted')
            ->with('vehicle')
            ->latest()
            ->take(5)
            ->get();

        // Get statistics
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $stats = [
            'monthly_maintenance_cost' => VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('driver_id', $driver->id)
                ->whereYear('maintenance_date', $currentYear)
                ->whereMonth('maintenance_date', $currentMonth)
                ->where('status', 'completed')
                ->sum('cost'),
            'pending_requests_count' => VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('driver_id', $driver->id)
                ->where('status', 'waiting')
                ->count(),
            'monthly_distance' => MileageLog::where('vehicle_id', $vehicle->id)
                ->where('driver_id', $driver->id)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->sum('distance_covered'),
            'total_maintenance_expenditure' => VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('driver_id', $driver->id)
                ->sum('cost'),
            'service_alert' => $vehicle->needs_service ?? false,
        ];

        // Get maintenance trend (last 6 months)
        $maintenanceTrend = VehicleMaintenance::select(
            DB::raw('YEAR(maintenance_date) as year'),
            DB::raw('MONTH(maintenance_date) as month'),
            DB::raw('SUM(cost) as total_cost'),
            DB::raw('COUNT(*) as request_count')
        )
        ->where('vehicle_id', $vehicle->id)
        ->where('driver_id', $driver->id)
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->take(6)
        ->get();

        return view('driver.fuel-mileage.dashboard', compact(
            'driver',
            'vehicle',
            'recentMaintenances',
            'recentMileageLogs',
            'stats',
            'maintenanceTrend'
        ));
    }

    /**
     * Maintenance Records (formerly fuelLogs)
     */
    public function maintenanceLogs(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();

        $query = VehicleMaintenance::where('driver_id', $driver->id)
            ->where('status', '!=', 'deleted')
            ->with(['vehicle', 'driver'])
            ->latest('maintenance_date');

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('maintenance_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Filter by maintenance type
        if ($request->has('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        $maintenances = $query->paginate(15);

        // Statistics
        $summary = [
            'total_cost' => $maintenances->sum('cost'),
            'total_records' => $maintenances->total(),
            'avg_cost' => $maintenances->avg('cost'),
            'last_dispatch' => $maintenances->where('status', 'dispatched')->first()?->maintenance_date,
        ];

        return view('driver.fuel-mileage.maintenance-index', compact('maintenances', 'summary'));
    }

    /**
     * Show maintenance request form
     */
    public function createMaintenanceRequest()
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $selectedVehicle = $driver->vehicle;

        if (!$selectedVehicle) {
            return redirect()->route('driver.fuel-mileage.dashboard')
                ->with('error', 'No vehicle assigned.');
        }

        $vehicles = Vehicle::where('status', 'active')->with(['region', 'district'])->get();

        return view('driver.fuel-mileage.maintenance-create', compact('selectedVehicle', 'vehicles', 'driver'));
    }

    /**
     * Store maintenance request
     */
    public function storeMaintenanceRequest(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return back()->with('error', 'No vehicle assigned.');
        }

        $validated = $request->validate([
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string|in:servicing,specific,breakdown',
            'mileage_at_service' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'checklist' => 'nullable|array',
            'other_maintenance_type' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $maintenance = VehicleMaintenance::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'maintenance_date' => $validated['maintenance_date'],
                'date' => $validated['maintenance_date'], // Sync both
                'maintenance_type' => $validated['maintenance_type'],
                'mileage_at_service' => $validated['mileage_at_service'],
                'description' => $validated['description'] ?? null,
                'checklist' => $validated['checklist'] ?? [],
                'other_maintenance_type' => $validated['other_maintenance_type'] ?? null,
                'status' => 'waiting', // Driver requests always start as waiting
                'cost' => 0.00, // Cost is updated by admin/technician later
            ]);

            // Notify Admins
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $admin->id,
                    'type' => 'maintenance_request',
                    'message' => 'Driver ' . auth()->user()->name . ' has requested maintenance for ' . $vehicle->registration_number,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('driver.fuel-mileage.maintenance.index')
                ->with('success', 'Maintenance request submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show maintenance log details
     */
    public function showMaintenanceLog($id)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();

        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['vehicle', 'driver'])
            ->firstOrFail();

        return view('driver.fuel-mileage.maintenance-show', compact('maintenance'));
    }

    /**
     * Mileage Log Index
     */
    public function mileageLogs(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();

        $query = MileageLog::where('driver_id', $driver->id)
            ->where('status', '!=', 'deleted')
            ->with(['vehicle', 'driver'])
            ->latest();

        // Filter by week
        if ($request->has('week')) {
            $query->where('week_label', 'like', "%{$request->week}%");
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('week_start_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $logs = $query->paginate(15);

        // Weekly statistics
        $weeklyStats = [];
        foreach ($logs as $log) {
            $weeklyStats[] = [
                'week' => $log->week_label,
                'distance' => $log->distance_covered,
                'alert' => $log->service_alert,
            ];
        }

        return view('driver.fuel-mileage.mileage-index', compact('logs', 'weeklyStats'));
    }

    /**
     * Show create mileage log form
     */
    public function createMileageLog()
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return redirect()->route('driver.fuel-mileage.dashboard')
                ->with('error', 'No vehicle assigned.');
        }

        // Determine current week
        $currentWeek = Carbon::now()->weekOfYear;
        $weekStart = Carbon::now()->startOfWeek();
        $weekLabel = "Week " . $currentWeek . " (" . $weekStart->format('M d') . ")";

        // Check if log already exists for this week
        $existingLog = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->first();

        // Get last week's end mileage
        $lastWeekLog = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->latest()
            ->first();

        return view('driver.fuel-mileage.mileage-create', compact(
            'vehicle',
            'weekLabel',
            'weekStart',
            'existingLog',
            'lastWeekLog'
        ));
    }

    /**
     * Store mileage log
     */
    public function storeMileageLog(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return back()->with('error', 'No vehicle assigned.');
        }

        $request->validate([
            'week_start_date' => 'required|date',
            'week_label' => 'required|string|max:100',
            'start_mileage' => 'required|integer|min:0',
            'end_mileage' => 'required|integer|gt:start_mileage',
        ]);

        // Check for duplicate week
        $existingLog = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('week_start_date', $request->week_start_date)
            ->first();

        if ($existingLog) {
            return back()->with('error', 'A mileage log already exists for this week.')
                ->withInput();
        }

        try {
            $mileageLog = MileageLog::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'week_label' => $request->week_label,
                'week_start_date' => $request->week_start_date,
                'start_mileage' => $request->start_mileage,
                'end_mileage' => $request->end_mileage,
                'service_alert' => false, // Will be calculated in model boot
            ]);

            // Update vehicle's current mileage
            if ($request->end_mileage > $vehicle->current_mileage) {
                $vehicle->update(['current_mileage' => $request->end_mileage]);
            }

            return redirect()->route('driver.fuel-mileage.mileage-logs.index')
                ->with('success', 'Mileage log recorded successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to record mileage log: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show mileage log details
     */
    public function showMileageLog($id)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();

        $log = MileageLog::where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['vehicle', 'driver'])
            ->firstOrFail();

        // Get previous and next logs for navigation
        $previousLog = MileageLog::where('vehicle_id', $log->vehicle_id)
            ->where('driver_id', $driver->id)
            ->where('id', '<', $log->id)
            ->latest('id')
            ->first();

        $nextLog = MileageLog::where('vehicle_id', $log->vehicle_id)
            ->where('driver_id', $driver->id)
            ->where('id', '>', $log->id)
            ->oldest('id')
            ->first();

        // Get fuel logs for this period
        $fuelLogs = FuelLog::where('vehicle_id', $log->vehicle_id)
            ->where('driver_id', $driver->id)
            ->where('odometer', '>=', $log->start_mileage)
            ->where('odometer', '<=', $log->end_mileage)
            ->get();

        return view('driver.fuel-mileage.mileage-show', compact(
            'log',
            'previousLog',
            'nextLog',
            'fuelLogs'
        ));
    }

    /**
     * Generate reports (Maintenance Focused)
     */
    public function reports()
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return redirect()->route('driver.fuel-mileage.dashboard')
                ->with('error', 'No vehicle assigned.');
        }

        // Monthly maintenance expenditure report
        $monthlyMaintenance = VehicleMaintenance::select(
            DB::raw('YEAR(maintenance_date) as year'),
            DB::raw('MONTH(maintenance_date) as month'),
            DB::raw('SUM(cost) as total_cost'),
            DB::raw('COUNT(*) as request_count')
        )
        ->where('vehicle_id', $vehicle->id)
        ->where('driver_id', $driver->id)
        ->where('status', 'completed')
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->take(12)
        ->get();

        // Weekly mileage report
        $weeklyMileage = MileageLog::select(
            'week_label',
            'week_start_date',
            'distance_covered',
            'service_alert'
        )
        ->where('vehicle_id', $vehicle->id)
        ->where('driver_id', $driver->id)
        ->orderBy('week_start_date', 'desc')
        ->take(8)
        ->get();

        // Maintenance type breakdown
        $typeBreakdown = VehicleMaintenance::select(
            'maintenance_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(cost) as total_cost')
        )
        ->where('vehicle_id', $vehicle->id)
        ->where('driver_id', $driver->id)
        ->groupBy('maintenance_type')
        ->get();

        return view('driver.fuel-mileage.reports', compact(
            'vehicle',
            'monthlyMaintenance',
            'weeklyMileage',
            'typeBreakdown'
        ));
    }

    /**
     * Quick log form (combined maintenance and mileage)
     */
    public function quickLog()
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return redirect()->route('driver.fuel-mileage.dashboard')
                ->with('error', 'No vehicle assigned.');
        }

        // Get last readings
        $lastMaintenance = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->latest()
            ->first();

        $lastMileageLog = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->latest()
            ->first();

        return view('driver.fuel-mileage.quick-log', compact(
            'vehicle',
            'lastMaintenance',
            'lastMileageLog'
        ));
    }

    /**
     * Store quick log
     */
    public function storeQuickLog(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $vehicle = $driver->vehicle;

        if (!$vehicle) {
            return back()->with('error', 'No vehicle assigned.');
        }

        $request->validate([
            'log_type' => 'required|in:maintenance,mileage,both',
            'date' => 'required|date',
            'odometer' => 'required|numeric|min:0',
            'maintenance_type' => 'nullable|required_if:log_type,maintenance,both|in:servicing,specific,breakdown',
            'description' => 'nullable|required_if:log_type,maintenance,both|string',
            'week_label' => 'nullable|required_if:log_type,mileage,both|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $messages = [];

            // Handle Maintenance Request
            if (in_array($request->log_type, ['maintenance', 'both'])) {
                VehicleMaintenance::create([
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $driver->id,
                    'maintenance_date' => $request->date,
                    'date' => $request->date,
                    'maintenance_type' => $request->maintenance_type,
                    'mileage_at_service' => $request->odometer,
                    'description' => $request->description,
                    'status' => 'waiting',
                    'cost' => 0.00,
                ]);

                $messages[] = 'Maintenance request submitted';
            }

            // Handle mileage logging
            if (in_array($request->log_type, ['mileage', 'both'])) {
                $weekStart = Carbon::parse($request->date)->startOfWeek();

                // Check for existing log
                $existingLog = MileageLog::where('vehicle_id', $vehicle->id)
                    ->where('driver_id', $driver->id)
                    ->where('week_start_date', $weekStart->format('Y-m-d'))
                    ->first();

                if (!$existingLog) {
                    $lastWeekLog = MileageLog::where('vehicle_id', $vehicle->id)
                        ->where('driver_id', $driver->id)
                        ->latest()
                        ->first();

                    $startMileage = $lastWeekLog ? $lastWeekLog->end_mileage : $vehicle->current_mileage;

                    MileageLog::create([
                        'vehicle_id' => $vehicle->id,
                        'driver_id' => $driver->id,
                        'week_label' => $request->week_label,
                        'week_start_date' => $weekStart,
                        'start_mileage' => $startMileage,
                        'end_mileage' => $request->odometer,
                    ]);

                    $messages[] = 'Mileage log recorded';
                }
            }

            // Update vehicle mileage
            if ($request->odometer > $vehicle->current_mileage) {
                $vehicle->update(['current_mileage' => $request->odometer]);
            }

            DB::commit();

            $message = implode(' and ', $messages) . ' successfully!';
            return redirect()->route('driver.fuel-mileage.dashboard')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to record log: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete maintenance record (soft delete / status update)
     */
    public function destroyMaintenanceLog($id)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();

        $maintenance = VehicleMaintenance::where('id', $id)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        if ($maintenance->status == 'completed' || $maintenance->status == 'dispatched') {
            return back()->with('error', 'Cannot delete a record that has already been dispatched or completed.');
        }

        $maintenance->update(['status' => 'deleted']);

        return redirect()->route('driver.fuel-mileage.maintenance.index')
            ->with('success', 'Maintenance request deleted successfully.');
    }

    /**
     * Delete a mileage log
     */
    public function destroyMileageLog($id)
    {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $mileageLog = MileageLog::findOrFail($id);

        // Verify the mileage log belongs to this driver
        if ($mileageLog->driver_id !== $driver->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        try {
            $mileageLog->delete();
            return back()->with('success', 'Mileage log deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete mileage log: ' . $e->getMessage());
        }
    }
}
