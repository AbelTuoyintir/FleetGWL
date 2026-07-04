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

    protected function setUp(): void
    {
        parent::setUp();

        if (empty(config('app.key'))) {
            $this->artisan('key:generate');
        }
    }

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
            'status' => 'active',
        ]);

        // Create some mileage logs
        // We use 6000 km distance for even indexes to trigger the > 5000 service alert logic
        for ($i = 0; $i < 10; $i++) {
            $distance = ($i % 2 == 0) ? 6000 : 100;
            MileageLog::create([
                'vehicle_id' => $vehicle->id,
                'start_mileage' => 0,
                'end_mileage' => $distance,
                'date' => now()->subDays($i)->toDateString(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get('/mileage-logs/statistics');

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total_distance', 30500); // 5 * 6000 + 5 * 100
        $response->assertJsonPath('total_logs', 10);
        $response->assertJsonPath('avg_distance', 3050);
        $response->assertJsonPath('service_alerts', 5);

        // Expecting around 3-5 queries (auth, session, counts)
        $this->assertLessThan(10, $queryCount);
    }
}
