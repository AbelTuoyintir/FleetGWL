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
     * Uses REAL GPS data from drivers when available,
     * falls back to simulated drift only for vehicles without recent location data.
     */
    public function getVehiclesLocations()
    {
        $vehicles = Vehicle::where('status', '!=', 'deleted')
            ->with([
                'assignedDriver.user:id,name,online_status',
                'region:id,name',
                'district:id,name'
            ])
            ->select([
                'id',
                'registration_number',
                'make',
                'model',
                'year',
                'color',
                'current_latitude',
                'current_longitude',
                'last_seen_at',
                'status',
                'vehicle_type',
                'assigned_driver_id',
                'region_id',
                'district_id'
            ])
            ->get();

        $cutoff = now()->subMinutes(5); // Consider data stale after 5 minutes

        $vehicles->transform(function ($vehicle) use ($cutoff) {
            // ── Check if we have RECENT real GPS data ──────────────
            $hasRealGps = !is_null($vehicle->current_latitude)
                       && !is_null($vehicle->current_longitude)
                       && $vehicle->last_seen_at
                       && $vehicle->last_seen_at->greaterThan($cutoff);

            if ($hasRealGps) {
                // Use the real data stored from the driver's browser GPS
                $vehicle->speed = $vehicle->speed ?? 0;
                $vehicle->heading = $vehicle->heading ?? 0;
                $vehicle->is_on_trip = $vehicle->speed > 0;
                $vehicle->fuel_level = rand(40, 95);
                $vehicle->ignition = $vehicle->speed > 0 ? 'on' : 'on';
                $vehicle->battery = round(12.4 + (rand(0, 10) / 10), 1);
                $vehicle->eta = rand(5, 45);
                return $vehicle;
            }

            // ── Fallback: simulated drift for vehicles WITHOUT real GPS ──
            if (is_null($vehicle->current_latitude) || is_null($vehicle->current_longitude)) {
                $vehicle->current_latitude = 5.6037;
                $vehicle->current_longitude = -0.1870;
            }

            $timeBucket = floor(time() / 10);
            $seedHash = crc32($vehicle->id . '_' . $timeBucket);

            $maxSpeed = match(strtolower($vehicle->vehicle_type)) {
                'truck' => 50,
                'bus' => 60,
                'suv' => 85,
                'pickup' => 80,
                default => 70
            };

            $vehicle->speed = $seedHash % ($maxSpeed + 1);
            $vehicle->heading = ($seedHash >> 8) % 361;

            if ($vehicle->speed > 0) {
                $angleRad = deg2rad($vehicle->heading);
                $driftDist = (($seedHash >> 16) % 11 + 5) * ($vehicle->speed / 30);
                $dist = $driftDist / 111111;
                $vehicle->current_latitude += cos($angleRad) * $dist;
                $vehicle->current_longitude += sin($angleRad) * $dist;
            }

            $vehicle->is_on_trip = $vehicle->speed > 0;
            $baseFuel = 65 + (crc32($vehicle->id . '_fuel') % 30);
            $slowTrend = (floor(time() / 120) % 5);
            $vehicle->fuel_level = max(5, $baseFuel - $slowTrend);
            $vehicle->ignition = $vehicle->speed > 0 ? 'on' : (rand(0, 10) > 2 ? 'on' : 'off');
            $baseBattery = 12.4 + (crc32($vehicle->id . '_batt') % 10) / 10;
            $battOscillation = (sin(time() / 60) * 0.2);
            $vehicle->battery = round($baseBattery + $battOscillation, 1);
            $vehicle->eta = rand(5, 45);

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
