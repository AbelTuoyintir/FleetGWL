<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MileageLogController extends Controller
{
    /**
     * Display a listing of mileage logs.
     */
    public function index()
    {
        $vehicles = Vehicle::where('status', 'active')->orderBy('registration_number')->get();
        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        
        return view('admin.mileageLogs.index', compact('vehicles', 'drivers'));
    }

    /**
     * Get mileage logs data for AJAX datatable.
     */
    public function getData(Request $request)
    {
        try {
            $query = MileageLog::with(['vehicle', 'driver'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            // Filter by vehicle search
            if ($request->filled('vehicle_search')) {
                $vehicleSearch = $request->vehicle_search;
                $query->whereHas('vehicle', function($q) use ($vehicleSearch) {
                    $q->where('registration_number', 'LIKE', "%{$vehicleSearch}%")
                      ->orWhere('make', 'LIKE', "%{$vehicleSearch}%")
                      ->orWhere('model', 'LIKE', "%{$vehicleSearch}%");
                });
            }

            // Filter by driver search
            if ($request->filled('driver_search')) {
                $driverSearch = $request->driver_search;
                $query->whereHas('driver', function($q) use ($driverSearch) {
                    $q->where('name', 'LIKE', "%{$driverSearch}%")
                      ->orWhere('email', 'LIKE', "%{$driverSearch}%");
                });
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $logs = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching mileage logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mileage logs'
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard.
     */
    public function getStatistics(Request $request)
    {
        try {
            $query = MileageLog::query();

            // Apply same filters as getData
            if ($request->filled('vehicle_search')) {
                $vehicleSearch = $request->vehicle_search;
                $query->whereHas('vehicle', function($q) use ($vehicleSearch) {
                    $q->where('registration_number', 'LIKE', "%{$vehicleSearch}%");
                });
            }

            if ($request->filled('driver_search')) {
                $driverSearch = $request->driver_search;
                $query->whereHas('driver', function($q) use ($driverSearch) {
                    $q->where('name', 'LIKE', "%{$driverSearch}%");
                });
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $logs = $query->get();
            
            $totalDistance = $logs->sum(function($log) {
                return $log->end_mileage - $log->start_mileage;
            });
            
            $avgDistance = $logs->count() > 0 ? $totalDistance / $logs->count() : 0;
            $serviceAlerts = $logs->where('service_alert', true)->count();
            $totalVehicles = Vehicle::where('status', 'active')->count();

            return response()->json([
                'success' => true,
                'total_distance' => $totalDistance,
                'total_logs' => $logs->count(),
                'avg_distance' => $avgDistance,
                'service_alerts' => $serviceAlerts,
                'total_vehicles' => $totalVehicles
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Store a newly created mileage log.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'required|exists:vehicles,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'start_mileage' => 'required|numeric|min:0',
                'end_mileage' => 'nullable|numeric|gt:start_mileage',
                'date' => 'required|date',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            DB::beginTransaction();

            // Create mileage log
            $mileageLog = MileageLog::create($validated);

            // Update vehicle's current mileage
            $vehicle = Vehicle::find($validated['vehicle_id']);
            if ($vehicle && $validated['end_mileage'] > ($vehicle->mileage ?? 0)) {
                $vehicle->update(['mileage' => $validated['end_mileage']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mileage log created successfully',
                'data' => $mileageLog->load(['vehicle', 'driver'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating mileage log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create mileage log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mileage log data for editing.
     */
    public function getEditData($id)
    {
        try {
            $log = MileageLog::with(['vehicle', 'driver'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $log
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching edit data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Mileage log not found'
            ], 404);
        }
    }

    /**
     * Display the specified mileage log.
     */
    public function show($id)
    {
        try {
            $log = MileageLog::with(['vehicle', 'driver'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $log
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing mileage log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Mileage log not found'
            ], 404);
        }
    }

    /**
     * Update the specified mileage log.
     */
    public function update(Request $request, $id)
    {
        try {
            $log = MileageLog::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'required|exists:vehicles,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'start_mileage' => 'required|numeric|min:0',
                'end_mileage' => 'nullable|numeric|gt:start_mileage',
                'date' => 'required|date',
                'service_alert' => 'boolean',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            DB::beginTransaction();

            // Update the log
            $log->update($validated);

            // Update vehicle's current mileage (check if this is the latest log)
            $latestLog = MileageLog::where('vehicle_id', $validated['vehicle_id'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestLog && $latestLog->id == $log->id) {
                $vehicle = Vehicle::find($validated['vehicle_id']);
                if ($vehicle && $validated['end_mileage'] > ($vehicle->mileage ?? 0)) {
                    $vehicle->update(['mileage' => $validated['end_mileage']]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mileage log updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating mileage log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mileage log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified mileage log (Soft Delete).
     */
    public function destroy($id)
    {
        try {
            $log = MileageLog::findOrFail($id);
            
            DB::beginTransaction();
            
            $vehicleId = $log->vehicle_id;
            $log->delete();
            
            // Update vehicle's current mileage to the latest remaining log
            $latestLog = MileageLog::where('vehicle_id', $vehicleId)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
            
            $vehicle = Vehicle::find($vehicleId);
            if ($vehicle) {
                $newMileage = $latestLog ? $latestLog->end_mileage : $vehicle->mileage;
                $vehicle->update(['mileage' => $newMileage]);
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mileage log deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting mileage log: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete mileage log'
            ], 500);
        }
    }

    /**
     * Export mileage logs to CSV.
     */
    public function export(Request $request)
    {
        try {
            $query = MileageLog::with(['vehicle', 'driver']);

            // Apply filters
            if ($request->filled('vehicle_search')) {
                $vehicleSearch = $request->vehicle_search;
                $query->whereHas('vehicle', function($q) use ($vehicleSearch) {
                    $q->where('registration_number', 'LIKE', "%{$vehicleSearch}%");
                });
            }

            if ($request->filled('driver_search')) {
                $driverSearch = $request->driver_search;
                $query->whereHas('driver', function($q) use ($driverSearch) {
                    $q->where('name', 'LIKE', "%{$driverSearch}%");
                });
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $logs = $query->orderBy('date', 'desc')->get();

            $filename = 'mileage_logs_' . date('Y-m-d_His') . '.csv';
            $handle = fopen('php://temp', 'w+');

            // Add UTF-8 BOM for Excel
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'ID', 'Date', 'Vehicle Registration', 'Vehicle Make', 'Vehicle Model',
                'Driver Name', 'Driver Email', 'Start Mileage (km)', 'End Mileage (km)',
                'Distance (km)', 'Service Alert', 'Notes', 'Created At', 'Updated At'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->date,
                    $log->vehicle->registration_number ?? 'N/A',
                    $log->vehicle->make ?? 'N/A',
                    $log->vehicle->model ?? 'N/A',
                    $log->driver->name ?? 'N/A',
                    $log->driver->email ?? 'N/A',
                    $log->start_mileage,
                    $log->end_mileage,
                    $log->end_mileage - $log->start_mileage,
                    $log->service_alert ? 'Yes' : 'No',
                    $log->notes ?? '',
                    $log->created_at,
                    $log->updated_at
                ]);
            }

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            \Log::error('Error exporting mileage logs: ' . $e->getMessage());
            return back()->with('error', 'Failed to export mileage logs');
        }
    }

    /**
     * Get mileage analytics data.
     */
    public function analytics(Request $request)
    {
        try {
            $query = MileageLog::with(['vehicle', 'driver']);

            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            $logs = $query->get();

            // Group by month
            $monthlyData = $logs->groupBy(function($log) {
                return $log->date->format('F Y');
            })->map(function($group) {
                return [
                    'month' => $group->first()->date->format('F Y'),
                    'total_distance' => $group->sum(function($log) {
                        return $log->end_mileage - $log->start_mileage;
                    }),
                    'total_logs' => $group->count(),
                    'avg_distance' => $group->avg(function($log) {
                        return $log->end_mileage - $log->start_mileage;
                    })
                ];
            })->values();

            // Top vehicles by distance
            $topVehicles = $logs->groupBy('vehicle_id')->map(function($group) {
                $vehicle = $group->first()->vehicle;
                return [
                    'vehicle_name' => $vehicle ? $vehicle->registration_number : 'Unknown',
                    'total_distance' => $group->sum(function($log) {
                        return $log->end_mileage - $log->start_mileage;
                    }),
                    'total_logs' => $group->count()
                ];
            })->sortByDesc('total_distance')->take(5)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_data' => $monthlyData,
                    'top_vehicles' => $topVehicles,
                    'total_logs' => $logs->count(),
                    'total_distance' => $logs->sum(function($log) {
                        return $log->end_mileage - $log->start_mileage;
                    })
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching analytics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics'
            ], 500);
        }
    }
}