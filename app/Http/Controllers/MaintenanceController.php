<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maintenanceRecords = Maintenance::where('status', '!=', 'deleted')
            ->with(['vehicle', 'driver'])
            ->latest()
            ->paginate(15);

        return view('vehicle-maintenance.index', compact('maintenanceRecords'));
    }

    /**
     * Show the form for creating a new resource.
     */
public function create($vehicleId = null)
{
    $vehicles = Vehicle::query()
        ->where('status', 'active')
        ->select('vehicles.*')
        ->selectSub(function ($query) {
            $query->from('mileage_logs')
                ->select('end_mileage')
                ->whereColumn('mileage_logs.vehicle_id', 'vehicles.id')
                ->orderByDesc('id')
                ->limit(1);
        }, 'latest_mileage')
        ->get();

    // If a driver is creating this, they should only see their assigned vehicle
    if (auth()->user() && auth()->user()->isDriver()) {
        $driver = auth()->user()->driver;
        if ($driver && $driver->vehicle) {
            $vehicles = $vehicles->where('id', $driver->vehicle->id);
            $vehicleId = $driver->vehicle->id;
        }
    }

    $selectedVehicle = $vehicleId ? $vehicles->firstWhere('id', $vehicleId) : null;
    $drivers = \App\Models\Driver::where('status', 'active')->with('user')->get();
    
    return view('vehicle-maintenance.create', compact('vehicles', 'drivers', 'vehicleId', 'selectedVehicle'));
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
             $validated = $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'maintenance_type' => 'required|string|max:255',
                'checklist' => 'nullable|array',
                'other_maintenance_type' => 'nullable|string|max:255',
                'maintenance_date' => 'required|date',
                'mileage_at_service' => 'nullable|integer|min:0',
                'status' => 'required|in:scheduled,completed,cancelled,waiting,dispatched',
        ]);

        if ($validated['maintenance_type'] === 'other' && !empty($validated['other_maintenance_type'])) {
            $validated['maintenance_type'] = $validated['other_maintenance_type'];
        }

        // Handle role-based status overrides
        $user = auth()->user();
        if ($user && $user->isDriver()) {
            $validated['status'] = 'waiting';
            
            // Notify Admins
            $admins = \App\Models\User::where('role', 'admin')->get();
            $vehicle = \App\Models\Vehicle::find($validated['vehicle_id']);
            
            foreach ($admins as $admin) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'user_id' => $admin->id,
                    'type' => 'maintenance_request',
                    'message' => 'Driver ' . $user->name . ' has requested maintenance for vehicle ' . ($vehicle ? $vehicle->registration_number : 'unknown') . '.',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } elseif ($user && $user->isAdmin()) {
            // Admin default is dispatched unless they specifically choose something else
            if (empty($request->status) || $request->status === 'waiting') {
                 $validated['status'] = 'dispatched';
            }
        }

         // Set the 'date' column to match maintenance_date
        $validated['date'] = $validated['maintenance_date'];
        $validated['created_by'] = auth()->id();

        Maintenance::create($validated);

        return redirect()->route('vehicle-maintenance.index')
            ->with('success', 'Maintenance record created successfully.');

        }catch(\Exception $e){
            return back()->withErrors(['error' => 'An error occurred while saving the maintenance record: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $maintenance = Maintenance::with(['vehicle', 'driver'])->findOrFail($id);

        return view('admin.vehicles.maintenance-details', compact('maintenance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
public function edit(Maintenance $maintenance)
{
    $vehicles = Vehicle::all();
    $drivers = \App\Models\Driver::where('status', '!=', 'deleted')->with('user')->get();
    return view('vehicle-maintenance.edit', compact('maintenance', 'vehicles', 'drivers'));
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'maintenance_type' => 'required|string|max:255',
            'checklist' => 'nullable|array',
            'other_maintenance_type' => 'nullable|string|max:255',
            'maintenance_date' => 'required|date',
            'mileage_at_service' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'service_provider' => 'nullable|string|max:255',
            'next_service_due' => 'nullable|date',
            'next_expected_mileage' => 'nullable|integer|min:0',
            'status' => 'required|in:scheduled,completed,cancelled,waiting,dispatched',
        ]);

        // Sync the 'date' column with 'maintenance_date'
        $validated['date'] = $validated['maintenance_date'];
        $validated['modified_by'] = auth()->id();

        $maintenance->update($validated);

        return redirect()->route('vehicle-maintenance.index')
            ->with('success', 'Maintenance record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Maintenance $maintenance)
    {
        $maintenance->update([
            'status' => 'deleted',
            'deleted_by' => auth()->id()
        ]);

        return redirect()->route('vehicle-maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }

    public function getMaintenanceData($id)
    {
        try {
            \Log::info('Fetching maintenance data for ID: ' . $id);
            $maintenance = Maintenance::with(['vehicle', 'driver'])
                ->findOrFail($id);

            \Log::info('Maintenance data fetched successfully', ['data' => $maintenance]);

            return response()->json([
                'success' => true,
                'data' => $maintenance
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching maintenance data', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load maintenance data.'
            ], 500);
        }
    }

    /**
     * Get vehicles that need maintenance (for dashboard widget).
     */
    public function getVehiclesNeedingMaintenance()
    {
        try {
            $alerts = \App\Models\MaintenanceAlert::with(['vehicle.assignedDriver'])
                ->where('status', 'pending')
                ->get()
                ->map(function ($alert) {
                    $vehicle = $alert->vehicle;
                    $driver = $vehicle->assignedDriver ?? null;
                    
                    // Interval threshold (default 5000km if not specified)
                    $interval = 5000;
                    $mileageSince = $alert->mileage_since_maintenance;
                    
                    // Calculate progress (max 100%)
                    $progress = $interval > 0 ? min(100, round(($mileageSince / $interval) * 100)) : 0;
                    $excess = max(0, $mileageSince - $interval);
                    
                    return [
                        'id' => $vehicle->id,
                        'registration_number' => $vehicle->registration_number,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'driver_name' => $driver ? $driver->name : 'Unassigned',
                        'current_mileage' => (int) $alert->current_mileage,
                        'mileage_since_maintenance' => (int) $mileageSince,
                        'excess_mileage' => (int) $excess,
                        'progress_percentage' => (int) $progress,
                    ];
                });

            return response()->json([
                'success' => true,
                'count' => $alerts->count(),
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching vehicles needing maintenance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load maintenance alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acknowledge a maintenance alert.
     */
    public function acknowledgeAlert($vehicleId)
    {
        try {
            \App\Models\MaintenanceAlert::where('vehicle_id', $vehicleId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'acknowledged',
                    'acknowledged_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Alert acknowledged successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error acknowledging alert: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge alert.'
            ], 500);
        }
    }
}
