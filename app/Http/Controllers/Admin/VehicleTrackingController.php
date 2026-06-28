<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VehicleTrackingController extends Controller
{
    /**
     * Display the vehicle tracking page.
     */
    public function index()
    {
        return view('admin.vehicles.tracking');
    }

    /**
     * Get vehicles locations for the map.
     * Side-effect free for production scalability.
     */
    public function getVehiclesLocations()
    {
        // Bolt: Eager load assignedDriver.user to prevent N+1 queries when accessing driver names/status
        $vehicles = Vehicle::where('status', '!=', 'deleted')
            ->with(['assignedDriver.user:id,name,online_status'])
            ->select([
                'id',
                'registration_number',
                'make',
                'model',
                'current_latitude',
                'current_longitude',
                'last_seen_at',
                'status',
                'vehicle_type',
                'assigned_driver_id'
            ])
            ->get();

        // Add transient properties for the live dashboard
        $vehicles->transform(function ($vehicle) {
            // If no location exists, provide a default for demo
            if (is_null($vehicle->current_latitude) || is_null($vehicle->current_longitude)) {
                $vehicle->current_latitude = 5.6037;
                $vehicle->current_longitude = -0.1870;
            }

            // SIMULATED DRIFT: For the "live feel" without saving to DB on every GET
            // Consistent heading-based drift based on vehicle ID and current time bucket (every 10s)
            // Use hash-based deterministic simulation to avoid global mt_srand side effects
            $timeBucket = floor(time() / 10);
            $seedHash = crc32($vehicle->id . '_' . $timeBucket);

            $vehicle->speed = $seedHash % 66; // 0-65
            $vehicle->heading = ($seedHash >> 8) % 361; // 0-360

            if ($vehicle->speed > 0) {
                $angleRad = deg2rad($vehicle->heading);
                // Drift by ~5-15 meters per 10s
                $driftDist = (($seedHash >> 16) % 11 + 5);
                $dist = $driftDist / 111111;
                $vehicle->current_latitude += cos($angleRad) * $dist;
                $vehicle->current_longitude += sin($angleRad) * $dist;
            }

            // Dynamic properties for UI
            $vehicle->is_on_trip = (bool)rand(0, 1);
            $vehicle->fuel_level = rand(15, 95); // Simulated fuel percentage
            $vehicle->ignition = $vehicle->speed > 0 ? 'on' : (rand(0, 10) > 2 ? 'on' : 'off'); // Simulated ignition status
            $vehicle->battery = (rand(118, 142) / 10); // Simulated 12V battery voltage
            $vehicle->eta = rand(5, 45); // Simulated ETA in minutes

            return $vehicle;
        });

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    /**
     * Get historical track for a specific vehicle.
     */
    public function getVehicleHistory($id, Request $request)
    {
        $hours = $request->get('hours', 24);

        $history = VehicleLocationHistory::where('vehicle_id', $id)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
