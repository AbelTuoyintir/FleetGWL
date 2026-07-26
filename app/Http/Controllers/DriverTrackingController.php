<?php

namespace App\Http\Controllers;

use App\Models\VehicleLocationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverTrackingController extends Controller
{
    /**
     * Update the driver's current location, speed, and heading.
     */
    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'integer', 'between:0,360'],
        ]);

        $user = auth()->user();

        // Ensure the authenticated user has an associated driver record
        if (!$user || !$user->driver) {
            return response()->json([
                'success' => false,
                'message' => 'Authenticated driver profile not found.'
            ], 404);
        }

        $driver = $user->driver;
        $vehicle = $driver->vehicle;

        // Ensure the driver has an assigned vehicle
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'No assigned vehicle found for this driver.'
            ], 404);
        }

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $speed = $validated['speed'] ?? 0;
        $heading = $validated['heading'] ?? 0;

        // 1. Update the current state of the assigned vehicle
        $vehicle->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'speed' => $speed,
            'heading' => $heading,
            'last_seen_at' => now(),
        ]);

        // 2. Store the historical record in vehicle_location_histories
        VehicleLocationHistory::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed' => $speed,
            'heading' => $heading,
            'recorded_at' => now(),
        ]);

        Log::info('[DriverTracking] Telemetry updated successfully', [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed' => $speed,
            'heading' => $heading,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.'
        ]);
    }
}
