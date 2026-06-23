<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Region;
use App\Models\District;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VehicleControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure app key is set for tests
        if (empty(config('app.key'))) {
            $this->artisan('key:generate');
        }
    }

    public function test_get_vehicle_statistics_query_count()
    {
        $admin = $this->createAdmin();

        // Create some vehicles
        for ($i = 0; $i < 5; $i++) {
            Vehicle::create([
                'registration_number' => 'REG' . $i,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2020,
                'vehicle_type' => 'Saloon',
                'chassis_number' => 'CH' . $i,
                'status' => 'active',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('vehicles.statistics'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        fwrite(STDOUT, "\nVehicle stats query count: " . $queryCount . "\n");

        // Before optimization, it was doing 10 counts just for the stats array.
        // Plus schema checks and other queries. Total was around 12-15.
        // Now it should be significantly less.
        $this->assertLessThan(10, $queryCount, "Vehicle statistics endpoint executing too many queries: " . $queryCount);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_get_form_data_eager_loading()
    {
        $admin = $this->createAdmin();

        // Create 10 drivers
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => 'Driver ' . $i,
                'email' => 'driver' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'driver'
            ]);

            Driver::create([
                'user_id' => $user->id,
                'license_number' => 'LIC' . $i,
                'status' => 'active'
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('vehicles.form-data'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        fwrite(STDOUT, "\nForm data query count: " . $queryCount . "\n");

        // Queries:
        // 1. regions
        // 2. districts
        // 3. stations
        // 4. drivers (with user join/eager load)
        // Expected ~4 queries (excluding schema/session).
        $this->assertLessThan(10, $queryCount, "Form data endpoint executing too many queries: " . $queryCount);
        $response->assertStatus(200);
    }

    private function createAdmin()
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);
    }
}
