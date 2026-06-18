<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;

class UpdateVehicleLocations extends Command
{
    protected $signature = 'vehicles:update-locations';
    protected $description = 'Simulate vehicle location updates and record history';

    public function handle()
    {
        $vehicles = Vehicle::where('status', 'active')->get();

        foreach ($vehicles as $vehicle) {
            // Move vehicle slightly
            $driftLat = (mt_rand(-50, 50) / 100000);
            $driftLng = (mt_rand(-50, 50) / 100000);

            if (is_null($vehicle->current_latitude)) {
                $vehicle->current_latitude = 5.6037;
                $vehicle->current_longitude = -0.1870;
            }

            $vehicle->current_latitude += $driftLat;
            $vehicle->current_longitude += $driftLng;
            $vehicle->last_seen_at = now();
            $vehicle->save();

            // Record history
            VehicleLocationHistory::create([
                'vehicle_id' => $vehicle->id,
                'latitude' => $vehicle->current_latitude,
                'longitude' => $vehicle->current_longitude,
                'speed' => rand(10, 80),
                'heading' => rand(0, 359),
                'recorded_at' => now()
            ]);
        }

        $this->info('Updated locations for ' . $vehicles->count() . ' vehicles.');
    }
}
