<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MileageLogPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_statistics_performance()
    {
        // Setup: Create 100 vehicles and 1000 mileage logs
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $vehicles = [];
        for ($i = 0; $i < 100; $i++) {
            $vehicles[] = [
                'registration_number' => 'VEH-'.$i,
                'make' => 'Toyota',
                'model' => 'Hilux',
                'year' => 2022,
                'status' => 'active',
                'chassis_number' => 'CH-'.$i,
                'vehicle_type' => 'Pickup',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($vehicles, 50) as $chunk) {
            DB::table('vehicles')->insert($chunk);
        }
        $vehicleIds = DB::table('vehicles')->pluck('id')->toArray();

        $logs = [];
        for ($i = 0; $i < 1000; $i++) {
            $logs[] = [
                'vehicle_id' => $vehicleIds[array_rand($vehicleIds)],
                'start_mileage' => $i * 10,
                'end_mileage' => ($i * 10) + rand(50, 200),
                'date' => now()->subDays(rand(0, 30))->toDateString(),
                'service_alert' => rand(0, 1) === 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($logs, 50) as $chunk) {
            DB::table('mileage_logs')->insert($chunk);
        }

        // Measure current performance
        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = microtime(true);
        $response = $this->actingAs($admin)->getJson(route('mileage-logs.statistics'));
        $end = microtime(true);

        $duration = ($end - $start) * 1000;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        fwrite(STDOUT, "\nMileage Statistics Performance (Before):\n");
        fwrite(STDOUT, 'Time: '.number_format($duration, 2)."ms\n");
        fwrite(STDOUT, 'Queries: '.$queryCount."\n");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'total_distance',
            'total_logs',
            'avg_distance',
            'service_alerts',
            'total_vehicles',
        ]);
    }
}
