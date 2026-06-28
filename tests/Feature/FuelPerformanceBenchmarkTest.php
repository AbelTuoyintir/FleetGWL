<?php

namespace Tests\Feature;

use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_stats_performance_benchmark()
    {
        // 1. Setup: Create a user and a vehicle
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $vehicle = Vehicle::create([
            'registration_number' => 'TEST-001',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'vehicle_type' => 'Pickup',
            'status' => 'active',
            'chassis_number' => 'CHASSIS-001',
            'year' => 2024,
        ]);

        // 2. Seed 1,000 fuel logs
        $count = 1000;
        $logs = [];
        for ($i = 0; $i < $count; $i++) {
            $logs[] = [
                'vehicle_id' => $vehicle->id,
                'date' => now()->subDays(rand(0, 365))->toDateString(),
                'odometer' => 1000 + ($i * 10),
                'fuel_quantity' => 50.5,
                'fuel_cost' => 500.0,
                'fuel_price_per_unit' => 9.9,
                'distance_traveled' => 500.0,
                'fuel_efficiency' => 9.9,
                'cost_per_distance' => 1.0,
                'status' => 'recorded',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Chunk insert for speed
        foreach (array_chunk($logs, 200) as $chunk) {
            FuelLog::insert($chunk);
        }

        // 3. Measure performance
        $this->actingAs($admin);

        $startTime = microtime(true);
        $response = $this->get(route('fuel-management.quick-stats'));
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // in ms

        $response->assertStatus(200);

        // Verify response data structure
        $response->assertJsonStructure([
            'success',
            'total_fuel',
            'total_cost',
            'total_distance',
            'avg_efficiency',
            'avg_cost_per_km'
        ]);

        dump("Performance for $count records: " . round($duration, 2) . "ms");
    }
}
