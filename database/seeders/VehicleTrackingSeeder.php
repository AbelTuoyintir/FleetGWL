<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Region;
use App\Models\District;
use App\Models\Station;
use App\Models\VehicleLocationHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class VehicleTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid unique constraints
        VehicleLocationHistory::truncate();
        Vehicle::truncate();
        Driver::truncate();
        User::where('role', 'driver')->delete();

        // 1. Create a Region
        $region = Region::firstOrCreate(
            ['code' => 'ACC'],
            ['name' => 'Greater Accra']
        );

        // 2. Create a District
        $district = District::firstOrCreate(
            ['code' => 'ACC-CENTRAL', 'region_id' => $region->id],
            ['name' => 'Accra Central']
        );

        // 3. Create a Station
        $station = Station::firstOrCreate(
            ['code' => 'HQ-STATION'],
            [
                'name' => 'Headquarters Station',
                'district_id' => $district->id,
                'region_id' => $region->id
            ]
        );

        $vehicleData = [
            ['reg' => 'GW-101-24', 'make' => 'Toyota', 'model' => 'Hilux', 'lat' => 5.6037, 'lng' => -0.1870],
            ['reg' => 'GW-202-24', 'make' => 'Nissan', 'model' => 'Hardbody', 'lat' => 5.6147, 'lng' => -0.1760],
            ['reg' => 'GW-303-24', 'make' => 'Mitsubishi', 'model' => 'L200', 'lat' => 5.5927, 'lng' => -0.1980],
            ['reg' => 'GW-404-24', 'make' => 'Ford', 'model' => 'Ranger', 'lat' => 5.6257, 'lng' => -0.2090],
            ['reg' => 'GW-505-24', 'make' => 'Isuzu', 'model' => 'D-Max', 'lat' => 5.5817, 'lng' => -0.1650],
        ];

        foreach ($vehicleData as $index => $data) {
            // Create a user for the driver
            $user = User::create([
                'name' => "Driver " . ($index + 1),
                'email' => "driver{$index}@gwc.com",
                'password' => Hash::make('password'),
                'role' => 'driver',
                'online_status' => 'online',
                'last_active_at' => now(),
            ]);

            // Create the driver
            $driver = Driver::create([
                'user_id' => $user->id,
                'license_number' => 'DL-' . rand(100000, 999999),
                'status' => 'active',
            ]);

            // Create the vehicle
            $vehicle = Vehicle::create([
                'registration_number' => $data['reg'],
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => 2024,
                'chassis_number' => 'CHAS' . rand(1000000, 9999999),
                'engine_number' => 'ENG' . rand(1000000, 9999999),
                'status' => 'active',
                'vehicle_type' => 'pickup',
                'region_id' => $region->id,
                'district_id' => $district->id,
                'station_id' => $station->id,
                'assigned_driver_id' => $driver->id,
                'current_latitude' => $data['lat'],
                'current_longitude' => $data['lng'],
                'last_seen_at' => now(),
            ]);

            // Create some history for each vehicle
            for ($i = 10; $i >= 0; $i--) {
                VehicleLocationHistory::create([
                    'vehicle_id' => $vehicle->id,
                    'latitude' => $data['lat'] - ($i * 0.001),
                    'longitude' => $data['lng'] - ($i * 0.001),
                    'speed' => rand(20, 60),
                    'heading' => 45,
                    'recorded_at' => now()->subMinutes($i * 5),
                ]);
            }
        }
    }
}
