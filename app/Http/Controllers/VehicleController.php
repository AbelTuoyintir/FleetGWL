<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\District;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Maintenance;
use App\Models\VehicleMaintenance;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MaintenanceChecklistItem;
use App\Models\FuelLog;
use App\Models\MileageLog;
use Carbon\Carbon;

class VehicleController extends Controller
{
    //

    public function index()
    {
        return view('admin.vehicles.vehicles');
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'registration_number' => 'required|string|max:20|unique:vehicles,registration_number',
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'color' => 'nullable|string|max:50',
                'chassis_number' => 'required|string|max:255|unique:vehicles,chassis_number',
                'engine_number' => 'nullable|string|max:255|unique:vehicles,engine_number',
                'mileage' => 'nullable|integer|min:0',
                'registration_date' => 'nullable|date',
                'insurance_expiry_date' => 'nullable|date',
                'next_inspection_date' => 'nullable|date',
                'vehicle_type' => 'required|string|max:100',
                'status' => 'required|in:active,inactive,maintenance,disposed,deleted',
                'notes' => 'nullable|string',
                'district_id' => 'nullable|exists:districts,id',
                'region_id' => 'nullable|exists:regions,id',
                'station_id' => 'nullable|exists:stations,id',
                'photo' => 'nullable|image|mimes:jpg,jpeg,gif,png|max:10024',
                'purchase_price' => 'nullable|numeric|min:0',
                'purchase_date' => 'nullable|date',
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('vehicle-photos', 'public');
                $validated['photo'] = $photoPath;
            }

            $validated['created_by'] = auth()->id();
            $vehicle = Vehicle::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle added successfully.',
                    'data' => $vehicle->fresh(['region', 'district', 'station', 'assignedDriver']),
                ], 201);
            }

            return redirect()->back()->with('success', 'Vehicle added successfully.');
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in VehicleController@store: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong while adding the vehicle.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $drivers = Driver::where('status', 'active')->get();
        $regions = Region::all();
        $districts = District::all();
        $stations = \App\Models\Station::where('status', 'active')->get();

        return view('admin.vehicles.edit', compact('vehicle', 'drivers', 'regions', 'districts', 'stations'));
    }

    public function show($id)
    {
        $vehicle = Vehicle::where('status', '!=', 'deleted')
                        ->with(['region', 'maintenances', 'documents'])
                        ->with('driver')
                        ->findOrFail($id);

        // Calculate statistics
        $maintenanceCount = $vehicle->maintenances->count();
        $maintenanceThisMonth = $vehicle->maintenances()
            ->whereMonth('created_at', now()->month)
            ->count();

        $averageWeeklyMileage = $this->calculateAverageWeeklyMileage($vehicle);
        $averageMonthlyMileage = $averageWeeklyMileage * 4;

        // Get recent activity
        $recentActivity = $this->getRecentActivity($vehicle);

        // Get maintenance log (paginated)
        $maintenanceLog = $vehicle->maintenances()
            ->orderBy('date', 'desc')
            ->paginate(10);

        // Get documents
        $documents = $vehicle->documents;

        // Sample mileage breakdown (replace with actual data from your system)
        $mileageBreakdown = $this->getMileageBreakdown($vehicle);

        // Get all active drivers for the assignment modal
        $drivers = Driver::where('status', 'active')->get();

        // Get checklist items for the maintenance modal
        $checklistItems = MaintenanceChecklistItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->get();

        return view('admin.vehicles.show', compact(
            'vehicle',
            'maintenanceCount',
            'maintenanceThisMonth',
            'averageWeeklyMileage',
            'averageMonthlyMileage',
            'recentActivity',
            'maintenanceLog',
            'documents',
            'mileageBreakdown',
            'drivers',
            'checklistItems'
        ));
    }

 /**
 * Calculate average weekly mileage for a specific period
 */
private function calculateAverageWeeklyMileage($vehicle, $weeks = 12)
{
    $startDate = now()->subWeeks($weeks);
    
    // Get mileage logs within date range
    $mileageLogs = MileageLog::where('vehicle_id', $vehicle->id)
        ->where('created_at', '>=', $startDate)
        ->orderBy('created_at', 'asc')
        ->get();
    
    if ($mileageLogs->count() >= 2) {
        $firstLog = $mileageLogs->first();
        $lastLog = $mileageLogs->last();
        
        $totalDistance = $lastLog->end_mileage - $firstLog->start_mileage;
        $weeksCount = $firstLog->created_at->diffInWeeks($lastLog->created_at);
        
        if ($weeksCount > 0) {
            return round($totalDistance / $weeksCount);
        }
    }
    
    // Fallback to fuel log method
    $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
        ->where('date', '>=', $startDate)
        ->where('distance_traveled', '>', 0)
        ->orderBy('date', 'asc')
        ->get();
    
    if ($fuelLogs->count() >= 2) {
        $firstLog = $fuelLogs->first();
        $lastLog = $fuelLogs->last();
            
            $totalDistance = $lastLog->odometer - $firstLog->odometer;
            $weeksCount = $firstLog->date->diffInWeeks($lastLog->date);
            
            if ($weeksCount > 0) {
                return round($totalDistance / $weeksCount);
            }
        }
        
        return 500; // Default fallback
    }

    private function getRecentActivity($vehicle)
    {
        $activities = [];

        // Add maintenance activities
        foreach ($vehicle->maintenances()->latest()->take(5)->get() as $maintenance) {
            $activities[] = [
                'icon' => 'fa-wrench',
                'title' => $maintenance->service,
                'date' => $maintenance->date->format('M j, Y'),
                'type' => 'Maintenance',
                'status_class' => 'bg-yellow-100 text-yellow-700'
            ];
        }

        // Add status changes or other activities
        $activities[] = [
            'icon' => 'fa-info-circle',
            'title' => 'Status updated to ' . $vehicle->status,
            'date' => $vehicle->updated_at->format('M j, Y'),
            'type' => 'Update',
            'status_class' => 'bg-blue-100 text-blue-700'
        ];

        return $activities;
    }

    private function getMileageBreakdown($vehicle)
    {
        $periods = [
            [
                'label' => 'Last Week',
                'start' => Carbon::now()->subWeek()->startOfWeek(),
                'end' => Carbon::now()->subWeek()->endOfWeek(),
            ],
            [
                'label' => 'This Month',
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now(),
            ],
            [
                'label' => 'Last Month',
                'start' => Carbon::now()->subMonth()->startOfMonth(),
                'end' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            [
                'label' => 'Last 3 Months',
                'start' => Carbon::now()->subMonths(3)->startOfMonth(),
                'end' => Carbon::now(),
            ],
        ];

        $breakdown = [];

        foreach ($periods as $period) {
            // Get mileage logs for the period
            $distance = MileageLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->get()
                ->sum(function($log) {
                    return max(0, ($log->end_mileage ?? 0) - ($log->start_mileage ?? 0));
                });

            // Get fuel logs for the period
            $fuelData = FuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date', [$period['start'], $period['end']])
                ->select(
                    DB::raw('SUM(fuel_quantity) as total_fuel'),
                    DB::raw('SUM(fuel_cost) as total_cost')
                )
                ->first();

            $breakdown[] = [
                'period' => $period['label'],
                'mileage' => $vehicle->mileage, // Using current vehicle mileage as base
                'distance' => $distance,
                'fuel_used' => (float) ($fuelData->total_fuel ?? 0),
                'cost' => (float) ($fuelData->total_cost ?? 0),
            ];
        }

        return $breakdown;
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validatedData = $request->validate([
            'registration_number' => 'required|string|max:50|unique:vehicles,registration_number,' . $vehicle->id,
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:50',
            'chassis_number' => 'required|string|max:100|unique:vehicles,chassis_number,' . $vehicle->id,
            'engine_number' => 'nullable|string|max:100|unique:vehicles,engine_number,' . $vehicle->id,
            'mileage' => 'nullable|integer|min:0',
            'registration_date' => 'nullable|date',
            'insurance_expiry_date' => 'nullable|date',
            'next_inspection_date' => 'nullable|date',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'vehicle_type' => 'required|string|max:100',
            'status' => 'required|in:active,inactive,maintenance,disposed,deleted',
            'notes' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'owner_contact' => 'nullable|string|max:255',
'district_id' => 'nullable|exists:districts,id',
            'region_id' => 'nullable|exists:regions,id',
            'station_id' => 'nullable|exists:stations,id',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ]);

        try {
            // Handle photo upload if exists
            if ($request->hasFile('photo')) {
                if ($vehicle->photo && Storage::disk('public')->exists($vehicle->photo)) {
                    Storage::disk('public')->delete($vehicle->photo);
                }
                $validatedData['photo'] = $request->file('photo')->store('vehicle-photos', 'public');
            }

            $validatedData['modified_by'] = auth()->id();

            $vehicle->update($validatedData);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle updated successfully!',
                    'data' => $vehicle->fresh(['region', 'district', 'station', 'assignedDriver'])
                ], 200);
            }

            return redirect()->route('vehicles.index')
                ->with('success', 'Vehicle updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Vehicle update failed: ' . $e->getMessage(), [
                'vehicle_id' => $vehicle->id,
                'user_id' => auth()->id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update vehicle. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update vehicle. Please try again.');
        }
    }

    public function destroy(Vehicle $vehicle)
    {
        try {
            $vehicle->softDelete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle deleted successfully.',
                ]);
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Vehicle delete failed: ' . $e->getMessage(), ['vehicle_id' => $vehicle->id]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete vehicle.',
                ], 500);
            }

            return redirect()->route('vehicles.index')->with('error', 'Failed to delete vehicle.');
        }
    }

    /**
     * Dispatch vehicle for maintenance — creates a Maintenance record and sets vehicle status.
     */
    public function dispatchForMaintenance(Request $request, Vehicle $vehicle)
    {
        try {
            $validated = $request->validate([
                'maintenance_notes' => 'nullable|string|max:1000',
            ]);

            $maintenance = Maintenance::create([
                'vehicle_id' => $vehicle->id,
                'maintenance_type' => 'General Service',
                'maintenance_date' => now()->toDateString(),
                'date' => now()->toDateString(),
                'description' => $validated['maintenance_notes'] ?? '',
                'status' => 'dispatched',
                'created_by' => auth()->id(),
            ]);

            $vehicle->update(['status' => 'maintenance']);

            return redirect()->back()->with('success', 'Vehicle dispatched for maintenance. Record #' . $maintenance->id . ' created.');
        } catch (\Exception $e) {
            Log::error('Error dispatching vehicle for maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to dispatch vehicle for maintenance.');
        }
    }

    /**
     * Release vehicle from maintenance — marks latest maintenance as completed and sets vehicle active.
     */
    public function releaseFromMaintenance(Vehicle $vehicle)
    {
        try {
            $latestMaintenance = $vehicle->maintenances()
                ->whereIn('status', ['dispatched', 'scheduled'])
                ->latest()
                ->first();

            if ($latestMaintenance) {
                $latestMaintenance->update([
                    'status' => 'completed',
                    'modified_by' => auth()->id(),
                ]);
            }

            $vehicle->update(['status' => 'active']);

            return redirect()->back()->with('success', 'Vehicle released from maintenance successfully.');
        } catch (\Exception $e) {
            Log::error('Error releasing vehicle from maintenance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to release vehicle from maintenance.');
        }
    }

        /**
     * Get vehicles data for API/JSON responses
     */
    public function getVehiclesData(Request $request)
    {
        try {
            $query = Vehicle::where('vehicles.status', '!=', 'deleted')
                ->with(['region', 'district', 'station', 'assignedDriver']);
            
            // Apply filters
            if ($request->filled('status')) {
                $query->where('vehicles.status', $request->status);
            }
            
            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }
            
            if ($request->filled('vehicle_type')) {
                $query->where('vehicle_type', $request->vehicle_type);
            }
            
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('chassis_number', 'like', "%{$search}%");
                });
            }
            
            $vehicles = $query->orderBy('created_at', 'desc')->paginate(15);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $vehicles->items(),
                    'pagination' => [
                        'current_page' => $vehicles->currentPage(),
                        'last_page' => $vehicles->lastPage(),
                        'per_page' => $vehicles->perPage(),
                        'total' => $vehicles->total()
                    ]
                ]);
            }
            
            return view('admin.vehicles.vehicles', compact('vehicles'));
            
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch vehicles'], 500);
        }
    }

    /**
     * Get vehicle status statistics for dashboard
     */
    public function getVehicleStatistics()
    {
        try {
            $maintenanceTable = Maintenance::resolveTableName();
            $hasVehicleMaintenanceTable = Schema::hasTable($maintenanceTable);
            $maintenanceDueColumn = $hasVehicleMaintenanceTable
                ? (Schema::hasColumn($maintenanceTable, 'next_service_due')
                    ? 'next_service_due'
                    : (Schema::hasColumn($maintenanceTable, 'maintenance_date') ? 'maintenance_date' : null))
                : null;

            $stats = [
                'total' => Vehicle::where('status', '!=', 'deleted')->count(),
                'active' => Vehicle::where('status', 'active')->count(),
                'inactive' => Vehicle::where('status', 'inactive')->count(),
                'maintenance' => Vehicle::where('status', 'maintenance')->count(),
                'disposed' => Vehicle::where('status', 'disposed')->count(),
                'unassigned' => Vehicle::whereNull('assigned_driver_id')->where('status', '!=', 'deleted')->count(),
                'assigned' => Vehicle::whereNotNull('assigned_driver_id')->where('status', '!=', 'deleted')->count(),
                'expiring_insurance' => Vehicle::where('insurance_expiry_date', '<=', now()->addDays(30))
                    ->where('insurance_expiry_date', '>=', now())
                    ->where('status', '!=', 'deleted')
                    ->count(),
                'expired_insurance' => Vehicle::where('insurance_expiry_date', '<', now())
                    ->where('status', '!=', 'deleted')
                    ->count(),
                'high_mileage' => Vehicle::where('mileage', '>=', 100000)
                    ->where('status', '!=', 'deleted')
                    ->count(),
            ];
            
            // Vehicle type distribution
            $stats['by_type'] = Vehicle::where('status', '!=', 'deleted')
                ->select('vehicle_type', DB::raw('count(*) as count'))
                ->groupBy('vehicle_type')
                ->get();
            
            // Maintenance due this month
            $stats['maintenance_due'] = 0;
            if ($hasVehicleMaintenanceTable && $maintenanceDueColumn) {
                $stats['maintenance_due'] = Vehicle::whereHas('maintenances', function($q) use ($maintenanceDueColumn) {
                    $q->whereIn('status', ['pending', 'scheduled', 'waiting', 'dispatched'])
                    ->where($maintenanceDueColumn, '<=', now()->addDays(30));
                })->count();
            }
            
            return response()->json(['success' => true, 'data' => $stats]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching vehicle statistics: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch statistics'], 500);
        }
    }

    /**
     * Get form data for vehicle registration (regions, districts, etc.)
     */
    public function getFormData()
    {
        try {
            $data = [
                'regions' => Region::where('status', '!=', 'deleted')->get(),
                'districts' => District::where('status', '!=', 'deleted')->get(),
                'stations' => \App\Models\Station::where('status', '!=', 'deleted')->get(),
                'drivers' => Driver::where('status', 'active')->get(),
                'vehicle_types' => ['Saloon', 'SUV', 'Truck', 'Bus', 'Van', 'Motorcycle', 'Pickup', 'Others'],
                'statuses' => ['active', 'inactive', 'maintenance', 'disposed'],
            ];
            
            return response()->json(['success' => true, 'data' => $data]);
            
        } catch (\Throwable $e) {
            Log::error('Error fetching form data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch form data'], 500);
        }
    }

    /**
     * Export vehicles to CSV/Excel
     */
    public function exportVehicles(Request $request)
    {
        try {
            $vehicles = Vehicle::where('status', '!=', 'deleted')
                ->with(['region', 'district', 'station', 'assignedDriver'])
                ->get();
            
            $filename = 'vehicles_export_' . date('Y-m-d_His') . '.csv';
            $handle = fopen('php://temp', 'w+');
            
            // Add headers
            fputcsv($handle, [
                'Registration Number', 'Make', 'Model', 'Year', 'Color', 
                'Chassis Number', 'Engine Number', 'Mileage', 'Vehicle Type', 
                'Status', 'Region', 'District', 'Station', 
                'Assigned Driver', 'Insurance Expiry', 'Purchase Price', 'Purchase Date'
            ]);
            
            // Add data rows
            foreach ($vehicles as $vehicle) {
                fputcsv($handle, [
                    $vehicle->registration_number,
                    $vehicle->make,
                    $vehicle->model,
                    $vehicle->year,
                    $vehicle->color,
                    $vehicle->chassis_number,
                    $vehicle->engine_number,
                    $vehicle->mileage,
                    $vehicle->vehicle_type,
                    $vehicle->status,
                    $vehicle->region->name ?? 'N/A',
                    $vehicle->district->name ?? 'N/A',
                    $vehicle->station->name ?? 'N/A',
                    $vehicle->assignedDriver->name ?? 'Unassigned',
                    $vehicle->insurance_expiry_date,
                    $vehicle->purchase_price,
                    $vehicle->purchase_date,
                ]);
            }
            
            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);
            
            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('Error exporting vehicles: ' . $e->getMessage());
            return back()->with('error', 'Failed to export vehicles');
        }
    }

    public function showImportForm()
    {
        return redirect()->route('vehicles.index', ['showImport' => 1]);
    }

    public function downloadTemplate()
    {
        $filename = 'vehicle_import_template.csv';
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, [
            'registration_number',
            'make',
            'model',
            'year',
            'vehicle_type',
            'chassis_number',
            'engine_number',
            'status',
            'color',
            'mileage',
            'registration_date',
            'insurance_expiry_date',
            'next_inspection_date',
            'fuel_consumption',
            'purchase_price',
            'purchase_date',
            'notes',
            'owner_name',
'owner_contact',
            'driver_email',
            'region_name',
            'district_name',
        ]);


        fputcsv($handle, [
            'GR-1234-24',
            'Toyota',
            'Hilux',
            2022,
            'Pickup',
            'CHS1234567890',
            'ENG1234567890',
            'active',
            'White',
            45210,
            '2024-01-10',
            '2026-01-09',
            '2026-07-10',
            10.8,
            185000,
            '2024-01-05',
            'Assigned for field operations',
            'GWCL',
            '0300000000',
            'driver@example.com',
            'Accra East',
            'Tema',
            'Transport',
        ]);

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function importVehicles(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'update_existing' => 'nullable|boolean',
        ]);

        $updateExisting = (bool) $request->boolean('update_existing');
        $result = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'failed_rows' => [],
        ];

        try {
            $rows = $this->extractRowsFromImportFile($request->file('file'));
            $result['total_rows'] = count($rows);

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $normalized = $this->normalizeImportRow($row);

                if ($this->isEmptyImportRow($normalized)) {
                    continue;
                }

                $validation = $this->validateImportRow($normalized, $rowNumber);
                if ($validation !== true) {
                    $result['failed']++;
                    $result['errors'][] = $validation;
                    $result['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'error' => $validation,
                        'data' => $normalized,
                    ];
                    continue;
                }

                $existingVehicle = Vehicle::where('registration_number', $normalized['registration_number'])
                    ->orWhere('chassis_number', $normalized['chassis_number'])
                    ->first();

                if ($existingVehicle && !$updateExisting) {
                    $result['skipped']++;
                    $duplicateError = "Row {$rowNumber}: Duplicate vehicle found (registration/chassis).";
                    $result['errors'][] = $duplicateError;
                    $result['failed_rows'][] = [
                        'row_number' => $rowNumber,
                        'error' => $duplicateError,
                        'data' => $normalized,
                    ];
                    continue;
                }

                $payload = $this->mapImportPayload($normalized);

                if ($existingVehicle && $updateExisting) {
                    $payload['modified_by'] = auth()->id();
                    $existingVehicle->update($payload);
                    $result['updated']++;
                } else {
                    $payload['created_by'] = auth()->id();
                    Vehicle::create($payload);
                    $result['created']++;
                }
            }

            DB::commit();
            session(['vehicles_import_last_result' => $result]);
            session(['vehicles_import_failed_rows' => $result['failed_rows']]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vehicle import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed due to a critical error. No records were saved.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getImportStatus()
    {
        return response()->json([
            'success' => true,
            'data' => session('vehicles_import_last_result', null),
        ]);
    }

    public function downloadFailedImportRows()
    {
        $failedRows = session('vehicles_import_failed_rows', []);

        if (empty($failedRows)) {
            return back()->with('error', 'No failed rows available for download.');
        }

        $filename = 'vehicle_import_failed_rows_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, ['row_number', 'error', 'registration_number', 'make', 'model', 'year', 'vehicle_type', 'chassis_number', 'driver_email', 'region_name', 'district_name']);

        foreach ($failedRows as $entry) {
            $data = $entry['data'] ?? [];
            fputcsv($handle, [
                $entry['row_number'] ?? '',
                $entry['error'] ?? '',
                $data['registration_number'] ?? '',
                $data['make'] ?? '',
                $data['model'] ?? '',
                $data['year'] ?? '',
                $data['vehicle_type'] ?? '',
                $data['chassis_number'] ?? '',
                $data['driver_email'] ?? '',
                $data['region_name'] ?? '',
                $data['district_name'] ?? '',
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function extractRowsFromImportFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {
            $sheets = Excel::toArray([], $file);
            if (empty($sheets) || empty($sheets[0])) {
                return [];
            }

            $rawRows = $sheets[0];
            $headers = $this->normalizeImportHeaders(array_shift($rawRows) ?? []);

            return $this->combineRowsWithHeaders($headers, $rawRows);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read import file.');
        }

        $headers = null;
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeImportHeaders($data);
                continue;
            }
            $rows[] = array_combine($headers, array_pad($data, count($headers), null));
        }
        fclose($handle);

        return $rows;
    }

    private function normalizeImportHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = strtolower(trim((string) $header));
            $header = preg_replace('/[^a-z0-9]+/', '_', $header);
            return trim((string) $header, '_');
        }, $headers);
    }

    private function combineRowsWithHeaders(array $headers, array $rows): array
    {
        $combined = [];
        foreach ($rows as $row) {
            $normalizedRow = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row);
            $combined[] = array_combine($headers, array_pad($normalizedRow, count($headers), null));
        }

        return $combined;
    }

    private function normalizeImportRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }
        return $normalized;
    }

    private function isEmptyImportRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }

    private function validateImportRow(array $row, int $rowNumber): true|string
    {
        $validator = Validator::make($row, [
            'registration_number' => 'required|string|max:20',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'vehicle_type' => 'required|string|max:100',
            'chassis_number' => 'required|string|max:255',
            'engine_number' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance,disposed,deleted',
            'mileage' => 'nullable|integer|min:0',
            'registration_date' => 'nullable|date',
            'insurance_expiry_date' => 'nullable|date',
            'next_inspection_date' => 'nullable|date',
            'fuel_consumption' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'driver_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return 'Row ' . $rowNumber . ': ' . implode(' ', $validator->errors()->all());
        }

        return true;
    }

    private function mapImportPayload(array $row): array
    {
        $regionId = null;
        $districtId = null;
        $driverId = null;

        if (!empty($row['region_name'])) {
            $regionId = Region::whereRaw('LOWER(name) = ?', [strtolower($row['region_name'])])->value('id');
        }
        if (!empty($row['district_name'])) {
            $districtId = District::whereRaw('LOWER(name) = ?', [strtolower($row['district_name'])])->value('id');
        }

        if (!empty($row['driver_email'])) {
            $driverId = Driver::whereHas('user', function ($query) use ($row) {
                $query->whereRaw('LOWER(email) = ?', [strtolower($row['driver_email'])]);
            })->value('id');
        }

        return [
            'registration_number' => $row['registration_number'],
            'make' => $row['make'],
            'model' => $row['model'],
            'year' => (int) $row['year'],
            'vehicle_type' => $row['vehicle_type'],
            'chassis_number' => $row['chassis_number'],
            'engine_number' => $row['engine_number'] ?: null,
            'status' => $row['status'] ?: 'active',
            'color' => $row['color'] ?: null,
            'mileage' => $row['mileage'] !== '' ? $row['mileage'] : null,
            'registration_date' => $row['registration_date'] ?: null,
            'insurance_expiry_date' => $row['insurance_expiry_date'] ?: null,
            'next_inspection_date' => $row['next_inspection_date'] ?: null,
            'fuel_consumption' => $row['fuel_consumption'] !== '' ? $row['fuel_consumption'] : null,
            'purchase_price' => $row['purchase_price'] !== '' ? $row['purchase_price'] : null,
            'purchase_date' => $row['purchase_date'] ?: null,
            'notes' => $row['notes'] ?: null,
            'owner_name' => $row['owner_name'] ?: null,
            'owner_contact' => $row['owner_contact'] ?: null,
            'region_id' => $regionId,
            'district_id' => $districtId,
            'assigned_driver_id' => $driverId,
            // location: station is currently not mapped from import template
            'station_id' => null,
        ];
    }

    /**
     * Search vehicles by registration number (with multiple results)
     **/
    public function searchByRegistrationNumber(Request $request)
    {
        try {
            $plate = $request->query('plate');
            
            if (!$plate || strlen(trim($plate)) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter at least 2 characters',
                    'vehicles' => []
                ]);
            }
            
            $vehicles = Vehicle::where('registration_number', 'LIKE', "%{$plate}%")
                ->where('status', '!=', 'deleted')
                ->limit(5)
                ->get(['id', 'registration_number', 'make', 'model', 'year', 'color']);
            
            if ($vehicles->count() > 0) {
                return response()->json([
                    'success' => true,
                    'vehicles' => $vehicles,
                    'count' => $vehicles->count()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No vehicles found',
                    'vehicles' => []
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Vehicle search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching for vehicle',
                'vehicles' => []
            ], 500);
        }
    }

    /**
     * Get vehicle details for AJAX
     */
    public function getVehicleDetails($id)
    {
        try {
            $vehicle = Vehicle::with(['assignedDriver:id,name', 'region:id,name'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $vehicle->id,
                    'registration_number' => $vehicle->registration_number,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color,
                    'current_odometer' => $vehicle->mileage ?? $vehicle->current_odometer ?? 0,
                    'fuel_type' => $vehicle->fuel_type ?? 'petrol',
                    'driver_id' => $vehicle->assigned_driver_id,
                    'driver_name' => $vehicle->assignedDriver ? $vehicle->assignedDriver->name : null,
                    'status' => $vehicle->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }
    }
}
