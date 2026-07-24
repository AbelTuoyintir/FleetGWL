<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Region;
use App\Models\District;
use App\Models\Station;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{

    public function vehiclesNeedingPage()
    {
        return view('maintenance.vehicles-needing');
    }

    /**
     * Search vehicles by registration number.
     *
     * Used by AJAX in resources/views/vehicle-maintenance/edit.blade.php
     */
    public function searchVehicle(Request $request)
    {
        $registration = trim((string) $request->query('registration', ''));

        if ($registration === '') {
            return response()->json([]);
        }

        $vehicles = Vehicle::query()
            ->where('status', 'active')
            ->where(function ($q) use ($registration) {
                $q->where('registration_number', 'like', '%' . $registration . '%');
            })
            ->limit(10)
            ->get();

        return response()->json(
            $vehicles->map(function (Vehicle $vehicle) {
                return [
                    'id' => $vehicle->id,
                    'registration_number' => $vehicle->registration_number,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color,
'mileage' => $vehicle->mileage,
                    'status' => $vehicle->status,
                ];
            })
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Bolt: Eager load driver.user to fix N+1 query issue in the maintenance list
        $maintenanceRecords = Maintenance::where('status', '!=', 'deleted')
            ->with(['vehicle', 'driver.user'])
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

    if (auth()->user() && auth()->user()->isDriver()) {
        $driver = auth()->user()->driver;

        if ($driver && $driver->vehicle) {
            $vehicles = $vehicles->where('id', $driver->vehicle->id);
            $vehicleId = $driver->vehicle->id;
        }
    }

    $selectedVehicle = $vehicleId
        ? $vehicles->firstWhere('id', $vehicleId)
        : $vehicles->first();

    $vehicle = $selectedVehicle;

    if (!$vehicle) {
        abort(404, 'Vehicle not found for maintenance job order.');
    }

    $drivers = Driver::where('status', 'active')
        ->with('user')
        ->get();

    $maintenance = new Maintenance();

    $maintenance->vehicle_id = $vehicle->id;
    $maintenance->mileage_at_service = $vehicle->latest_mileage;
    $maintenance->status = 'scheduled';

    // IMPORTANT
    $checklistItems = collect();

    return view('vehicle-maintenance.edit', compact(
        'vehicles',
        'vehicle',
        'drivers',
        'vehicleId',
        'selectedVehicle',
        'maintenance',
        'checklistItems'
    ));
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

        return redirect()->route('maintenance.index')
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
        try {
            $maintenance = Maintenance::with(['vehicle', 'driver.user'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $maintenance
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Record not found'], 404);
        }
    }

       // Add to your MaintenanceController

    public function statistics(Request $request)
    {
        try {
            $query = Maintenance::where('status', '!=', 'deleted');
            
            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }
            
            $pending = (clone $query)->where('status', 'waiting')->count();
            $inProgress = (clone $query)->where('status', 'in_progress')->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            
            return response()->json([
                'success' => true,
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

        return redirect()->route('maintenance.index')
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

    /**
     * Company configuration data for print notices.
     */
    protected function getCompanyConfig()
    {
        return [
            'name' => 'Ghana Water Company Limited',
            'short_name' => 'Ghana Water Ltd.',
            'abbreviation' => 'GWCL',
            'head_office' => [
                'address' => 'Registration Office: 28th February Road Near Independence Square',
                'tel' => '233-6102-666781-7 / 233-634-390',
                'fax' => '233-6302-663552',
                'telephone' => 'DIRWAT',
                'website' => 'www.gwcl.com.gh',
                'email' => 'info@gwcl.com.gh',
            ],
            'default_region' => [
                'address' => 'Post Office Box 163, Tema – Ghana',
                'sub_address' => 'West Africa',
                'tel' => '233 (0) 303 202 832/3',
                'fax' => '233 (0) 303 214',
                'email' => 'tema.region@ghanaawwater.info',
                'website' => 'www.ghanaawater.info',
            ],
            'bankers' => ['Social Security Bank', 'Ghana Commercial Bank'],
            'board_of_directors' => 'Hoa. Alexander Kwamena Afenyi-Markin (Chairman), Dr. Clifford Briaumah (Managing Director), Mr. Joseph Obeng-Odo, Mr. Michael Ayensu, Naba Sirji Beving, Hon. Kwame Ampofo Tivunam, Clement Achekun, Kaba, Dr. Forster Kum-Atama Sarpong, Madam Maria Aha Lwolue-Johnson, Mr. Alexander K.B. Bumey, Mrs. Serena Kwakye Mintah',
            'default_signatory' => 'ING. MAC-DOE HANYABUI',
            'default_signatory_title' => '(AG. REGIONAL CHIEF MANAGER)',
            'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRqka9HtHi5QpNxxBcGIcKb831huIiHmR-xx6e5NnE2X0T1uyXfp880DUg&s=10',
        ];
    }

    /**
     * Get affected zones from a vehicle's location hierarchy.
     */
    protected function getAffectedZonesForVehicle(Vehicle $vehicle): array
    {
        $zones = [];

        // Add vehicle's region
        if ($vehicle->region) {
            $zones[] = $vehicle->region->name . ' Region';
        }

        // Add vehicle's district
        if ($vehicle->district) {
            $zones[] = $vehicle->district->name . ' District';
        }

        // Add vehicle's station
        if ($vehicle->station) {
            $zones[] = $vehicle->station->name . ' Station';
        }

        return $zones;
    }

    /**
     * Get affected zones from filter parameters (region/district/station).
     */
    protected function getAffectedZonesFromFilters(?int $regionId, ?int $districtId, ?int $stationId): array
    {
        $zones = [];

        if ($regionId) {
            $region = Region::find($regionId);
            if ($region) {
                $zones[] = $region->name . ' Region';
            }
        }

        if ($districtId) {
            $district = District::with('region')->find($districtId);
            if ($district) {
                $prefix = $district->region ? $district->region->name . ' - ' : '';
                $zones[] = $prefix . $district->name . ' District';
            }
        }

        if ($stationId) {
            $station = Station::with(['district.region'])->find($stationId);
            if ($station) {
                $prefix = '';
                if ($station->district) {
                    $prefix = $station->district->name . ' - ';
                } elseif ($station->region) {
                    $prefix = $station->region->name . ' - ';
                }
                $zones[] = $prefix . $station->name;
            }
        }

        return $zones;
    }

    /**
     * Print a single maintenance record as an official notice.
     */
    public function printNotice(Maintenance $maintenance)
    {
        $maintenance->load(['vehicle.region', 'vehicle.district', 'vehicle.station', 'driver.user']);

        $vehicle = $maintenance->vehicle;
        $driver = $maintenance->driver;
        $company = $this->getCompanyConfig();

        // For a single vehicle, get zones from its hierarchy
        if ($vehicle) {
            $affectedZones = $this->getAffectedZonesForVehicle($vehicle);
        } else {
            $affectedZones = [];
        }

        // Determine signatory from maintenance record or use default
        $signatoryName = $company['default_signatory'];
        $signatoryTitle = $company['default_signatory_title'];
        $regionName = $vehicle && $vehicle->region ? strtoupper($vehicle->region->name) . ' REGION' : 'TEMA REGION';

        return view('print.maintenance-print', compact(
            'maintenance',
            'vehicle',
            'driver',
            'company',
            'affectedZones',
            'signatoryName',
            'signatoryTitle',
            'regionName'
        ));
    }

    /**
     * Print batch maintenance records filtered by region/district/station.
     */
    public function printBatchNotice(Request $request)
    {
        $regionId = $request->integer('region_id', 0) ?: null;
        $districtId = $request->integer('district_id', 0) ?: null;
        $stationId = $request->integer('station_id', 0) ?: null;
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Maintenance::with(['vehicle.region', 'vehicle.district', 'vehicle.station', 'driver.user'])
            ->where('status', '!=', 'deleted');

        // Apply location filters via vehicle relationship
        if ($regionId) {
            $query->whereHas('vehicle', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }
        if ($districtId) {
            $query->whereHas('vehicle', function ($q) use ($districtId) {
                $q->where('district_id', $districtId);
            });
        }
        if ($stationId) {
            $query->whereHas('vehicle', function ($q) use ($stationId) {
                $q->where('station_id', $stationId);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }
        if ($dateFrom) {
            $query->whereDate('maintenance_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('maintenance_date', '<=', $dateTo);
        }

        $maintenanceRecords = $query->latest()->get();
        $company = $this->getCompanyConfig();

        // Get affected zones from filters
        $affectedZones = $this->getAffectedZonesFromFilters($regionId, $districtId, $stationId);

        // Determine region name from the first vehicle or filter
        if ($regionId) {
            $region = Region::find($regionId);
            $regionName = $region ? strtoupper($region->name) . ' REGION' : 'REGION';
        } elseif ($maintenanceRecords->isNotEmpty() && $maintenanceRecords->first()->vehicle && $maintenanceRecords->first()->vehicle->region) {
            $regionName = strtoupper($maintenanceRecords->first()->vehicle->region->name) . ' REGION';
        } else {
            $regionName = 'TEMA REGION';
        }

        $signatoryName = $company['default_signatory'];
        $signatoryTitle = $company['default_signatory_title'];
        $isBatch = true;

        return view('print.maintenance-print', compact(
            'maintenanceRecords',
            'company',
            'affectedZones',
            'signatoryName',
            'signatoryTitle',
            'regionName',
            'isBatch'
        ));
    }
}
