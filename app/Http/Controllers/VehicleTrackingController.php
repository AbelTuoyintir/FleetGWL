<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

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
     */
    public function getVehiclesLocations()
    {
        $vehicles = Vehicle::where('status', '!=', 'deleted')
            ->with(['assignedDriver:id,name'])
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

        // For demo purposes, if no locations are set, let's generate some random ones around Accra
        $vehicles->transform(function ($vehicle) {
            if (is_null($vehicle->current_latitude) || is_null($vehicle->current_longitude)) {
                // Accra coordinates
                $lat = 5.6037;
                $lng = -0.1870;

                // Add some randomness
                $vehicle->current_latitude = $lat + (mt_rand(-100, 100) / 1000);
                $vehicle->current_longitude = $lng + (mt_rand(-100, 100) / 1000);
                $vehicle->last_seen_at = now();
            }
            return $vehicle;
        });

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }
}
