<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Region;
use App\Models\District;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VehicleTrackingSeeder extends Seeder
{
    public function run()
    {
        // Create a Region, District, and Station if they don't exist
        $region = Region::firstOrCreate(['name' => 'Greater Accra'], ['code' => 'GA']);
        $district = District::firstOrCreate([
            'name' => 'Accra Metropolitan',
            'region_id' => $region->id
        ], [
            'code' => 'AMA'
        ]);
        $station = Station::firstOrCreate([
            'name' => 'Headquarters',
            'district_id' => $district->id
        ], [
            'code' => 'HQ',
            'region_id' => $region->id
        ]);

        $driverNames = ['John Doe', 'Jane Smith', 'Kofi Mensah', 'Ama Serwaa'];

        foreach ($driverNames as $index => $name) {
            $email = str_replace(' ', '.', strtolower($name)) . '@gwc.com';
            $user = User::where('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'driver',
                    'online_status' => ($index % 2 == 0) ? 'online' : 'offline',
                ]);
            }

            $driver = Driver::where('user_id', $user->id)->first();
            if (!$driver) {
                $driver = Driver::create([
                    'user_id' => $user->id,
                    'license_number' => 'GHA-' . rand(100000, 999999) . '-' . chr(65 + $index),
                    'status' => 'active',
                ]);
            }

            // Create a vehicle for each driver if they don't have one
            if (!$driver->vehicle) {
                $makes = ['Toyota', 'Nissan', 'Hyundai', 'Isuzu'];
                $models = ['Hilux', 'Hardbody', 'Elantra', 'D-Max'];
                $vIdx = rand(0, 3);

                $vehicle = Vehicle::create([
                    'registration_number' => 'GW-' . rand(1000, 9999) . '-24',
                    'make' => $makes[$vIdx],
                    'model' => $models[$vIdx],
                    'year' => rand(2018, 2024),
                    'color' => 'White',
                    'status' => 'active',
                    'vehicle_type' => 'Pickup',
                    'region_id' => $region->id,
                    'district_id' => $district->id,
                    'station_id' => $station->id,
                    'assigned_driver_id' => $driver->id,
                    'chassis_number' => strtoupper(Str::random(17)),
                    'engine_number' => strtoupper(Str::random(10)),
                    // Near Accra center
                    'current_latitude' => 5.6037 + (mt_rand(-50, 50) / 1000),
                    'current_longitude' => -0.1870 + (mt_rand(-50, 50) / 1000),
                    'last_seen_at' => now(),
                ]);

                // Add some history for the vehicle
                for ($i = 10; $i >= 0; $i--) {
                    DB::table('vehicle_location_histories')->insert([
                        'vehicle_id' => $vehicle->id,
                        'latitude' => $vehicle->current_latitude - ($i * 0.001),
                        'longitude' => $vehicle->current_longitude - ($i * 0.001),
                        'speed' => rand(20, 60),
                        'heading' => 45,
                        'recorded_at' => now()->subMinutes($i * 10),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
