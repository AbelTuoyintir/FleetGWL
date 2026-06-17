<?php

namespace App\Http\Controllers;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\VehicleMaintenance;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\FuelStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\SqlDate;


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
        // SQLite doesn't support YEAR()/MONTH(), so we use strftime for driver-agnostic grouping.
        $maintenanceTrend = VehicleMaintenance::select(
            DB::raw("cast(strftime('%Y', maintenance_date) as integer) as year"),
            DB::raw("cast(strftime('%m', maintenance_date) as integer) as month"),
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

        // Update validation to include 'both' option
        $validated = $request->validate([
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string|in:servicing,specific,both',
            'mileage_at_service' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'selected_services' => 'nullable|array',
            'selected_services.*' => 'string|max:255',
            'other_maintenance_type' => 'nullable|string|max:255',
            'include_service' => 'nullable|boolean',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        try {
            DB::beginTransaction();

            // Prepare checklist based on selected services
            $checklist = [];
            
            // If both is selected or specific is selected with services
            if ($validated['maintenance_type'] === 'both' || ($validated['maintenance_type'] === 'specific' && !empty($validated['selected_services']))) {
                $checklist = $validated['selected_services'] ?? [];
                
                // Add service package to checklist if 'both' is selected
                if ($validated['maintenance_type'] === 'both') {
                    array_unshift($checklist, 'Standard Service Package (Oil Change, Filter Replacement, Basic Inspection)');
                }
            }
            
            // Add other maintenance type if provided
            if (!empty($validated['other_maintenance_type'])) {
                $checklist[] = $validated['other_maintenance_type'];
            }
            
            // Determine final maintenance type for database
            $dbMaintenanceType = $validated['maintenance_type'];
            if ($dbMaintenanceType === 'servicing') {
                $dbMaintenanceType = 'general_service';
            } elseif ($dbMaintenanceType === 'specific') {
                $dbMaintenanceType = 'specific';
            } elseif ($dbMaintenanceType === 'both') {
                $dbMaintenanceType = 'both';
            }
            
            // Prepare description with additional details
            $fullDescription = $validated['description'] ?? '';
            
            // Add service details if 'both' is selected
            if ($validated['maintenance_type'] === 'both') {
                $serviceDetails = "\n\n--- Service Requested ---\n- Oil Change\n- Oil Filter Replacement\n- Air Filter Check/Replacement\n- General Vehicle Inspection";
                $fullDescription .= $serviceDetails;
            }
            
            // Add checklist items to description if any
            if (!empty($checklist)) {
                $fullDescription .= "\n\n--- Specific Repairs Requested ---\n- " . implode("\n- ", $checklist);
            }
            
            // Create maintenance record
            $maintenance = Maintenance::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'maintenance_date' => $validated['maintenance_date'],
                'date' => $validated['maintenance_date'],
                'maintenance_type' => $dbMaintenanceType,
                'mileage_at_service' => $validated['mileage_at_service'],
                'description' => $fullDescription,
                'parts_replaced' => json_encode($checklist), // Store selected services as JSON
                'checklist' => json_encode($checklist),
                'other_maintenance_type' => $validated['other_maintenance_type'] ?? null,
                'priority' => $validated['priority'] ?? 'medium',
                'status' => 'waiting',
                'cost' => 0.00,
            ]);
            
            // Update vehicle's last maintenance request info
            $vehicle->update([
                'last_maintenance_request_date' => now(),
                'last_maintenance_request_mileage' => $validated['mileage_at_service'],
            ]);
            
            // Prepare notification message based on type
            $typeMessage = '';
            switch ($validated['maintenance_type']) {
                case 'servicing':
                    $typeMessage = 'regular service';
                    break;
                case 'specific':
                    $typeMessage = 'specific repairs (' . count($checklist) . ' item(s))';
                    break;
                case 'both':
                    $typeMessage = 'regular service plus ' . count($checklist) . ' additional repair(s)';
                    break;
            }
            
            // Notify Admins
            $admins = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($admins as $admin) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $admin->id,
                    'type' => 'maintenance_request',
                    'title' => 'New Maintenance Request',
                    'message' => 'Driver ' . auth()->user()->name . ' has requested ' . $typeMessage . ' for ' . $vehicle->registration_number . ' (Mileage: ' . number_format($validated['mileage_at_service']) . ' km)',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Also send email notification (optional)
            // Mail::to($admins->pluck('email')->toArray())->send(new MaintenanceRequestNotification($maintenance, $driver));
            
            DB::commit();
            
            // Determine success message
            $successMessage = '';
            switch ($validated['maintenance_type']) {
                case 'servicing':
                    $successMessage = 'Service request submitted successfully! Our team will contact you shortly.';
                    break;
                case 'specific':
                    $successMessage = 'Repair request submitted successfully! Our team will review and get back to you.';
                    break;
                case 'both':
                    $successMessage = 'Service and repair request submitted successfully! Our team will handle both requests.';
                    break;
            }
            
            return redirect()->route('driver.fuel-mileage.maintenance.index')
                ->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Maintenance request failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id
            ]);
            
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
        $vehicle = $driver->vehicle;

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

        // Render an existing view (this repo only has mileage-create for driver mileage screens).
        // Provide a minimal set of variables so the view can render without undefined variables.
        $currentWeek = Carbon::now()->weekOfYear;
        $weekStart = Carbon::now()->startOfWeek();
        $weekLabel = "Week " . $currentWeek . " (" . $weekStart->format('M d') . ")";

        $existingLog = MileageLog::where('vehicle_id', $vehicle->id)
            ->where('driver_id', $driver->id)
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->first();

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
            DB::raw(SqlDate::year('maintenance_date') . ' as year'),
            DB::raw(SqlDate::month('maintenance_date') . ' as month'),
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
                Maintenance::create([
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
}
