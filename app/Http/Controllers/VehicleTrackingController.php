<?php

namespace App\Http\Controllers;

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

            // Dynamic properties for UI
            $isOperational = in_array($vehicle->status, ['active', 'operational']);

            $vehicle->is_on_trip = $isOperational && (bool)rand(0, 1);
            $vehicle->speed = $vehicle->is_on_trip ? rand(15, 75) : ($isOperational ? rand(0, 5) : 0);
            $vehicle->heading = rand(0, 360);

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
