<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\MileageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MileageLogPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_statistics_query_count()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $vehicle = Vehicle::create([
            'registration_number' => 'REG123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vehicle_type' => 'Saloon',
            'chassis_number' => 'CH123',
            'status' => 'active'
        ]);

        $driver = Driver::create([
            'user_id' => $admin->id,
            'license_number' => 'LIC123',
            'status' => 'active'
        ]);

        // Create 100 mileage logs
        // 10 will have > 5000km (alerts), 90 will have 100km
        $expectedDistance = 0;
        for ($i = 0; $i < 100; $i++) {
            $start = ($i * 10000) + 1; // avoid 0
            $isAlert = $i < 10;
            $distance = $isAlert ? 6000 : 100;
            $end = $start + $distance;

            MileageLog::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'start_mileage' => $start,
                'end_mileage' => $end,
                'date' => now()->subDays($i)->toDateString(),
            ]);
            $expectedDistance += $distance;
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->getJson(route('mileage-logs.statistics'));

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        fwrite(STDOUT, "\nMileageLog statistics total query count: " . $queryCount . "\n");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_distance' => $expectedDistance,
                'total_logs' => 100,
                'avg_distance' => $expectedDistance / 100,
                'service_alerts' => 10
            ]);
    }
}
