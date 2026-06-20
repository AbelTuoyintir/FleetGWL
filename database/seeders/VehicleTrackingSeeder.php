<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\User;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VehicleTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $region = Region::firstOrCreate(
            ['name' => 'Accra West'],
            [
                'code' => 'AW-' . Str::random(5),
                'status' => 'active'
            ]
        );

        for ($i = 1; $i <= 5; $i++) {
            $email = "driver$i" . Str::random(5) . "@example.com";
            $user = User::create([
                'name' => "Driver $i",
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'driver',
                'online_status' => $i % 2 == 0 ? 'online' : 'offline'
            ]);

            $driver = Driver::create([
                'user_id' => $user->id,
                'license_number' => "LIC-" . Str::random(8),
                'status' => 'active'
            ]);

            Vehicle::create([
                'registration_number' => "GWL-" . (3000 + $i),
                'make' => 'Toyota',
                'model' => 'Hilux',
                'year' => 2023,
                'status' => 'active',
                'assigned_driver_id' => $driver->id,
                'region_id' => $region->id,
                'current_latitude' => 5.6037 + (mt_rand(-100, 100) / 10000),
                'current_longitude' => -0.1870 + (mt_rand(-100, 100) / 10000),
                'last_seen_at' => now(),
                'chassis_number' => Str::random(17),
                'engine_number' => Str::random(10),
                'vehicle_type' => 'Pickup'
            ]);
        }
    }
}
