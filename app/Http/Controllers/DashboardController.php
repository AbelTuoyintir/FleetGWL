<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Asset;
use App\Models\Region;
use App\Models\Maintenance;
use App\Models\Driver;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the fleet management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Initialize all variables with default values matching the view's expected structure
        $criticalAlerts = (object) [
            'overdue_maintenance' => 0,
            'expired_insurances' => 0,
            'expired_registrations' => 0,
            'vehicles_needing_attention' => 0,
        ];

        $totalVehicles = 0;
        $vehiclesByRegion = collect();
        $activeDrivers = 0;
        $unassignedVehicles = 0;
        $vehicleUtilization = (object) ['total' => 0, 'assigned' => 0, 'utilization_rate' => 0];
        $maintenanceCosts = (object) ['monthly_cost' => 0, 'ytd_cost' => 0];
        $maintenanceDueVehicles = 0;
        $upcomingVehicleMaintenance = collect();
        $maintenanceTrend = collect();
        $predictiveMaintenance = collect();
        $driverAvailability = collect();
        $activeRegions = 0;
        $maintenanceDueAssets = 0;
        $totalOffices = 0;
        $totalAssets = 0;
        $recentVehicles = collect();
        $vehicleStatus = [];
        $fuelEfficiency = collect();
        $monthlySummary = (object) ['vehicles_added' => 0, 'maintenance_completed' => 0, 'drivers_added' => 0];
        $mapData = collect();
        $expiringDocuments = collect();

        try {
            // Cache all dashboard data for 5 minutes by default
            $dashboardData = Cache::remember('dashboard_complete_data', 300, function() {
                return $this->gatherDashboardData();
            });

            if (! $this->isDashboardDataValid($dashboardData)) {
                Cache::forget('dashboard_complete_data');
                $dashboardData = $this->gatherDashboardData();
                Cache::put('dashboard_complete_data', $dashboardData, 300);
            }

            // Extract data from cached result
            extract($dashboardData);
            
        } catch (\Exception $e) {
            Log::error('Dashboard data gathering failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Attempt to get partial data if cache failed
            $vars = get_defined_vars();
            $this->gatherPartialDashboardData($vars);
            extract($vars);
        }

        // Return view with all variables
        return view('admin.dashboard', compact(
            'criticalAlerts',
            'totalVehicles',
            'vehiclesByRegion',
            'activeDrivers',
            'unassignedVehicles',
            'vehicleUtilization',
            'maintenanceCosts',
            'maintenanceDueVehicles',
            'upcomingVehicleMaintenance',
            'maintenanceTrend',
            'predictiveMaintenance',
            'driverAvailability',
            'totalAssets',
            'activeRegions',
            'maintenanceDueAssets',
            'totalOffices',
            'recentVehicles',
            'vehicleStatus',
            'fuelEfficiency',
            'monthlySummary',
            'mapData',
            'expiringDocuments'
        ));
    }

    /**
     * Validate cached dashboard payload to avoid incomplete-object failures in Blade.
     *
     * @param mixed $dashboardData
     * @return bool
     */
    private function isDashboardDataValid($dashboardData)
    {
        if (! is_array($dashboardData)) {
            return false;
        }

        if (! array_key_exists('criticalAlerts', $dashboardData)) {
            return false;
        }

        if ($dashboardData['criticalAlerts'] instanceof \__PHP_Incomplete_Class) {
            return false;
        }

        // Check collections for strings or incomplete classes (common cache issues)
        $collectionsToCheck = ['vehiclesByRegion', 'recentVehicles', 'upcomingVehicleMaintenance', 'expiringDocuments'];
        
        foreach ($collectionsToCheck as $key) {
            if (array_key_exists($key, $dashboardData)) {
                $data = $dashboardData[$key];

                if (is_string($data)) {
                    return false;
                }

                if (is_iterable($data)) {
                    foreach ($data as $item) {
                        if (is_string($item) || $item instanceof \__PHP_Incomplete_Class) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Gather all dashboard data in one method for better cache management.
     *
     * @return array
     */
    private function gatherDashboardData()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $thirtyDaysFromNow = $now->copy()->addDays(30);
        $sixtyDaysFromNow = $now->copy()->addDays(60);
        $maintenanceTable = Maintenance::resolveTableName();
        $hasVehicleMaintenanceTable = Schema::hasTable($maintenanceTable);
        $maintenanceDueColumn = $hasVehicleMaintenanceTable
            ? (Schema::hasColumn($maintenanceTable, 'next_service_due')
                ? 'next_service_due'
                : (Schema::hasColumn($maintenanceTable, 'maintenance_date') ? 'maintenance_date' : null))
            : null;
        $maintenanceCompletedAtColumn = $hasVehicleMaintenanceTable
            ? (Schema::hasColumn($maintenanceTable, 'completed_at')
                ? 'completed_at'
                : (Schema::hasColumn($maintenanceTable, 'updated_at') ? 'updated_at' : null))
            : null;

        // 1. Critical Alerts
        $criticalAlerts = (object) [
            'overdue_maintenance' => $hasVehicleMaintenanceTable
                ? Vehicle::where('status', '!=', 'deleted')
                    ->whereHas('maintenances', function($q) use ($now, $maintenanceDueColumn) {
                        $q->where('status', '!=', 'deleted')
                          ->whereIn('status', ['pending', 'scheduled', 'waiting', 'dispatched'])
                          ->when($maintenanceDueColumn, function ($query) use ($maintenanceDueColumn, $now) {
                              $query->where($maintenanceDueColumn, '<', $now);
                          });
                    })->count()
                : 0,
            'expired_insurances' => Document::where('status', '!=', 'deleted')
                ->where('document_type', 'insurance')
                ->where('expiry_date', '<', $now)
                ->count(),
            'expired_registrations' => Document::where('status', '!=', 'deleted')
                ->where('document_type', 'registration')
                ->where('expiry_date', '<', $now)
                ->count(),
            'vehicles_needing_attention' => Vehicle::where('status', '!=', 'deleted')
                ->whereIn('status', ['needs_repair', 'in_shop'])
                ->count(),
        ];

        // 2. Vehicle Statistics
        $totalVehicles = Vehicle::where('status', '!=', 'deleted')->count();
        
        $vehiclesByRegion = Vehicle::where('vehicles.status', '!=', 'deleted')
            ->select('regions.name as region_name', DB::raw('count(*) as count'))
            ->join('regions', 'vehicles.region_id', '=', 'regions.id')
            ->groupBy('regions.name', 'regions.id')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'region_name' => $item->region_name,
                    'count' => (int) $item->count,
                ];
            });
        
        $activeDrivers = Driver::where('status', '!=', 'deleted')
            ->whereHas('vehicle', function($q) {
                $q->where('status', '!=', 'deleted');
            })
            ->count();
        
        $unassignedVehicles = Vehicle::where('status', '!=', 'deleted')
            ->whereNull('assigned_driver_id')
            ->count();

        // 3. Performance Metrics
        $utilizationData = DB::table('vehicles')
            ->where('status', '!=', 'deleted')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN assigned_driver_id IS NOT NULL THEN 1 ELSE 0 END) as assigned,
                ROUND((SUM(CASE WHEN assigned_driver_id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) as utilization_rate
            ')
            ->first();
        
        $vehicleUtilization = (object) [
            'total' => $utilizationData->total ?? 0,
            'assigned' => $utilizationData->assigned ?? 0,
            'utilization_rate' => $utilizationData->utilization_rate ?? 0
        ];

        $costData = (object) ['monthly_cost' => 0, 'ytd_cost' => 0];
        if ($hasVehicleMaintenanceTable) {
            $costData = DB::table($maintenanceTable)
                ->where('status', '!=', 'deleted')
                ->selectRaw('
                    SUM(CASE WHEN created_at >= ? THEN COALESCE(cost, 0) ELSE 0 END) as monthly_cost,
                    SUM(COALESCE(cost, 0)) as ytd_cost
                ', [$startOfMonth])
                ->first();
        }
        
        $maintenanceCosts = (object) [
            'monthly_cost' => (int)($costData->monthly_cost ?? 0),
            'ytd_cost' => (int)($costData->ytd_cost ?? 0)
        ];

        // 4. Maintenance Data
        $maintenanceDueVehicles = 0;
        if ($hasVehicleMaintenanceTable && $maintenanceDueColumn) {
            $maintenanceDueVehicles = Vehicle::where('status', '!=', 'deleted')
                ->whereHas('maintenances', function($query) use ($thirtyDaysFromNow, $maintenanceDueColumn) {
                    $query->where('status', '!=', 'deleted')
                          ->whereIn('status', ['pending', 'scheduled', 'waiting', 'dispatched'])
                          ->where($maintenanceDueColumn, '<=', $thirtyDaysFromNow);
                })
                ->count();
        }

        // Get upcoming maintenance with vehicle details
        $upcomingVehicleMaintenance = collect();
        if ($hasVehicleMaintenanceTable && $maintenanceDueColumn) {
            $upcomingVehicleMaintenance = Maintenance::where('status', '!=', 'deleted')
                ->with(['vehicle' => function($q) {
                    $q->select('id', 'plate_number', 'make', 'model', 'make_model');
                }])
                ->where('status', '!=', 'completed')
                ->where($maintenanceDueColumn, '<=', $thirtyDaysFromNow)
                ->where($maintenanceDueColumn, '>=', $now)
                ->orderBy($maintenanceDueColumn, 'asc')
                ->take(5)
                ->get()
                ->map(function($item) use ($maintenanceDueColumn) {
                    return (object) [
                        'vehicle' => (object) [
                            'plate_number' => optional($item->vehicle)->plate_number ?? 'N/A',
                            'make_model' => optional($item->vehicle)->make_model ?? (($item->vehicle->make ?? '') . ' ' . ($item->vehicle->model ?? 'Unknown'))
                        ],
                        'next_service_due' => data_get($item, $maintenanceDueColumn),
                        'maintenance_type' => $item->maintenance_type ?? 'Service'
                    ];
                });
        }

        // 5. Asset Statistics (if Asset model exists)
        $totalAssets = 0;
        $maintenanceDueAssets = 0;
        if (class_exists(\App\Models\Asset::class)) {
            $totalAssets = Asset::where('status', '!=', 'deleted')->count();
            if ($hasVehicleMaintenanceTable && $maintenanceDueColumn) {
                $maintenanceDueAssets = Maintenance::where('status', '!=', 'deleted')
                    ->whereIn('status', ['pending', 'scheduled', 'waiting', 'dispatched'])
                    ->where($maintenanceDueColumn, '<=', $thirtyDaysFromNow)
                    ->count();
            }
        }

        // 6. Regions and Offices
        $activeRegions = Region::where('status', '!=', 'deleted')->count();
        
        $totalOffices = 0;
        if (Schema::hasTable('offices')) {
            $totalOffices = DB::table('offices')->where('status', '!=', 'deleted')->count();
        }

        // 7. Recent Vehicles
        $recentVehicles = Vehicle::where('status', '!=', 'deleted')
            ->with(['region:id,name', 'assignedDriver:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($vehicle) {
                return (object) [
                    'plate_number' => $vehicle->plate_number,
                    'region' => $vehicle->region ? (object) ['name' => $vehicle->region->name] : null,
                    'assignedDriver' => $vehicle->assignedDriver ? (object) ['name' => $vehicle->assignedDriver->name] : null,
                    'created_at' => $vehicle->created_at->toDateString()
                ];
            });

        // 8. Vehicle Status Distribution
        $statusCounts = Vehicle::where('status', '!=', 'deleted')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        // If no status field, use assignment status
        if (empty($statusCounts)) {
            $vehicleStatus = [
                'Operational' => Vehicle::whereNotNull('assigned_driver_id')->where('status', 'operational')->count(),
                'Needs Repair' => Vehicle::where('status', 'needs_repair')->count(),
                'In Shop' => Vehicle::where('status', 'in_shop')->count(),
                'Unassigned' => $unassignedVehicles
            ];
        } else {
            $vehicleStatus = $statusCounts;
        }

        // 9. Monthly Summary
        $monthlySummary = (object) [
            'vehicles_added' => Vehicle::where('status', '!=', 'deleted')
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
            'maintenance_completed' => $hasVehicleMaintenanceTable
                ? Maintenance::where('status', '!=', 'deleted')
                    ->where('status', 'completed')
                    ->when($maintenanceCompletedAtColumn, function ($query) use ($maintenanceCompletedAtColumn, $now) {
                        $query->whereYear($maintenanceCompletedAtColumn, $now->year)
                            ->whereMonth($maintenanceCompletedAtColumn, $now->month);
                    })
                    ->count()
                : 0,
            'drivers_added' => Driver::where('status', '!=', 'deleted')
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count(),
        ];

        // 10. Fuel Efficiency Data (if fuel logs exist)
        $fuelEfficiency = collect();
        try {
            if (DB::getSchemaBuilder()->hasTable('fuel_logs')) {
                $fuelEfficiency = DB::table('fuel_logs')
                    ->join('vehicles', 'fuel_logs.vehicle_id', '=', 'vehicles.id')
                    ->select(DB::raw('COALESCE(vehicles.make, "") || " " || COALESCE(vehicles.model, "") as make_model'), DB::raw('AVG(km_per_litre) as avg_km_per_litre'))
                    ->where('fuel_logs.status', '!=', 'deleted')
                    ->whereNotNull('km_per_litre')
                    ->groupBy('vehicles.id')
                    ->orderBy('avg_km_per_litre', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($item) {
                        return (object) [
                            'vehicle' => $item->make_model ?? 'Unknown',
                            'avg_km_per_litre' => round($item->avg_km_per_litre, 1)
                        ];
                    });
            }
        } catch (\Exception $e) {
            Log::warning('Fuel logs query failed: ' . $e->getMessage());
        }

        // If no fuel data, provide sample structure
        if ($fuelEfficiency->isEmpty()) {
            $fuelEfficiency = collect([
                (object) ['vehicle' => 'Toyota Hilux', 'avg_km_per_litre' => 9.2],
                (object) ['vehicle' => 'Isuzu D-Max', 'avg_km_per_litre' => 10.1],
                (object) ['vehicle' => 'Ford Ranger', 'avg_km_per_litre' => 8.7],
            ]);
        }

        // 11. Expiring Documents
        $expiringDocuments = Document::where('status', '!=', 'deleted')
            ->where('expiry_date', '>=', $now)
            ->where('expiry_date', '<=', $sixtyDaysFromNow)
            ->with(['vehicle' => function($q) {
                $q->select('id', 'plate_number');
            }])
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get()
            ->map(function($doc) {
                return (object) [
                    'document_type' => ucfirst($doc->document_type),
                    'vehicle' => $doc->vehicle ? (object) ['plate_number' => $doc->vehicle->plate_number] : null,
                    'expiry_date' => $doc->expiry_date->toDateString()
                ];
            });

        // 12. Map Data for Regions
        $regionCoords = [
            'Accra West' => ['lat' => 5.5786, 'lng' => -0.3233],
            'Accra East' => ['lat' => 5.6724, 'lng' => -0.1637],
            'Accra Central' => ['lat' => 5.5487, 'lng' => -0.2012],
            'ATMA' => ['lat' => 5.6702, 'lng' => -0.0039],
            'Tema' => ['lat' => 5.6667, 'lng' => -0.0167],
        ];

        $mapData = $vehiclesByRegion->map(function($item) use ($regionCoords) {
            $coords = $regionCoords[$item->region_name] ?? null;
            if ($coords) {
                return (object) [
                    'name' => $item->region_name,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'count' => $item->count
                ];
            }
            return null;
        })->filter()->values();

        return compact(
            'criticalAlerts',
            'totalVehicles',
            'vehiclesByRegion',
            'activeDrivers',
            'unassignedVehicles',
            'vehicleUtilization',
            'maintenanceCosts',
            'maintenanceDueVehicles',
            'upcomingVehicleMaintenance',
            'totalAssets',
            'activeRegions',
            'maintenanceDueAssets',
            'totalOffices',
            'recentVehicles',
            'vehicleStatus',
            'fuelEfficiency',
            'monthlySummary',
            'mapData',
            'expiringDocuments'
        );
    }

    /**
     * Gather partial dashboard data when full query fails.
     *
     * @param array &$variables
     * @return void
     */
    private function gatherPartialDashboardData(&$variables)
    {
        $now = now();
        
        // Try to get basic counts that are most critical
        try {
            $variables['totalVehicles'] = Vehicle::where('status', '!=', 'deleted')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get total vehicles: ' . $e->getMessage());
        }
        
        try {
            $variables['activeDrivers'] = Driver::where('status', '!=', 'deleted')
                ->whereHas('vehicle')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get active drivers: ' . $e->getMessage());
        }
        
        try {
            $variables['unassignedVehicles'] = Vehicle::where('status', '!=', 'deleted')
                ->whereNull('assigned_driver_id')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get unassigned vehicles: ' . $e->getMessage());
        }
        
        try {
            $variables['activeRegions'] = Region::where('status', '!=', 'deleted')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get active regions: ' . $e->getMessage());
        }
    }

    /**
     * Clear all dashboard cache.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache()
    {
        $keys = [
            'dashboard_complete_data',
            'dashboard_critical_alerts',
            'dashboard_total_vehicles',
            'dashboard_vehicles_by_region',
            'dashboard_active_drivers',
            'dashboard_unassigned_vehicles',
            'dashboard_vehicle_utilization',
            'dashboard_maintenance_costs',
            'dashboard_maintenance_due_vehicles',
            'dashboard_upcoming_maintenance',
            'dashboard_total_assets',
            'dashboard_active_regions',
            'dashboard_maintenance_due_assets',
            'dashboard_total_offices',
            'dashboard_recent_vehicles',
            'dashboard_vehicle_status',
            'dashboard_fuel_efficiency',
            'dashboard_monthly_summary',
            'dashboard_expiring_documents',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info('Dashboard cache cleared by user');
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Dashboard cache cleared successfully.']);
        }
        
        return back()->with('success', 'Dashboard cache cleared successfully.');
    }

    /**
     * Get dashboard data via AJAX for real-time updates.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRefreshData()
    {
        try {
            // Clear cache to get fresh data
            Cache::forget('dashboard_complete_data');
            $data = $this->gatherDashboardData();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'refreshed_at' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('AJAX dashboard refresh failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }
}
