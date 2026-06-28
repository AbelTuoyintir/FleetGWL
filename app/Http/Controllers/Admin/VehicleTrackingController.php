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

            // Speed ranges based on vehicle type
            $maxSpeed = match(strtolower($vehicle->vehicle_type)) {
                'truck' => 50,
                'bus' => 60,
                'suv' => 85,
                'pickup' => 80,
                default => 70
            };

            $vehicle->speed = $seedHash % ($maxSpeed + 1);
            $vehicle->heading = ($seedHash >> 8) % 361; // 0-360

            if ($vehicle->speed > 0) {
                $angleRad = deg2rad($vehicle->heading);
                // Drift by ~5-25 meters per 10s depending on speed
                $driftDist = (($seedHash >> 16) % 11 + 5) * ($vehicle->speed / 30);
                $dist = $driftDist / 111111;
                $vehicle->current_latitude += cos($angleRad) * $dist;
                $vehicle->current_longitude += sin($angleRad) * $dist;
            }

            // Dynamic properties for UI
            $vehicle->is_on_trip = $vehicle->speed > 0 || (rand(0, 10) > 4);

            // Deterministic drift for fuel and battery to simulate consumption
            // Base values derived from vehicle ID to stay consistent across refreshes
            $baseFuel = 65 + (crc32($vehicle->id . '_fuel') % 30); // 65-95%
            $slowTrend = (floor(time() / 120) % 5); // Drops 1% every 2 mins, cycles every 10 mins
            $vehicle->fuel_level = max(5, $baseFuel - $slowTrend);

            $vehicle->ignition = $vehicle->speed > 0 ? 'on' : (rand(0, 10) > 2 ? 'on' : 'off');

            $baseBattery = 12.4 + (crc32($vehicle->id . '_batt') % 10) / 10; // 12.4 - 13.4V
            $battOscillation = (sin(time() / 60) * 0.2); // Smoothly oscillate +/- 0.2V
            $vehicle->battery = round($baseBattery + $battOscillation, 1);
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
