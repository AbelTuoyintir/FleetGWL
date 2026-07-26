<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverTrackingController extends Controller
{
    /**
     * Update the driver's vehicle GPS location in real-time.
     * Called via AJAX from the driver's browser using navigator.geolocation.watchPosition().
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed'     => 'nullable|numeric|min:0|max:999',
            'heading'   => 'nullable|numeric|between:0,360',
            'accuracy'  => 'nullable|numeric|min:0',
        ]);

        $driver = Driver::where('user_id', Auth::id())->first();

        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found.'], 404);
        }

        $vehicle = Vehicle::where('assigned_driver_id', $driver->id)->first();

        if (!$vehicle) {
            return response()->json(['success' => false, 'message' => 'No vehicle assigned to you.'], 404);
        }

        $latitude  = $request->latitude;
        $longitude = $request->longitude;
        $speed     = $request->speed ?? 0;
        $heading   = $request->heading ?? 0;

        // Update the vehicle's current location in real-time
        $vehicle->update([
            'current_latitude'  => $latitude,
            'current_longitude' => $longitude,
            'last_seen_at'      => now(),
        ]);

        // Store in location history for route playback
        VehicleLocationHistory::create([
            'vehicle_id'  => $vehicle->id,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'speed'       => $speed,
            'heading'     => $heading,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data'    => [
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'speed'     => $speed,
                'heading'   => $heading,
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);
    }
}

