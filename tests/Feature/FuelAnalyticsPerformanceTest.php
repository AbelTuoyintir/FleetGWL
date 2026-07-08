<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\FuelLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FuelAnalyticsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (empty(config('app.key'))) {
            $this->artisan('key:generate');
        }
    }

    public function test_analytics_data_correctness_and_performance()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $v1 = Vehicle::create([
            'registration_number' => 'V1',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'vehicle_type' => 'Saloon',
            'chassis_number' => 'CH1',
            'status' => 'active',
        ]);

        $v2 = Vehicle::create([
            'registration_number' => 'V2',
            'make' => 'Isuzu',
            'model' => 'D-Max',
            'year' => 2021,
            'vehicle_type' => 'Pickup',
            'chassis_number' => 'CH2',
            'status' => 'active',
        ]);

        // Seed some data across different months and fuel types
        $data = [
            ['v' => $v1, 'date' => '2023-01-15', 'qty' => 50, 'cost' => 500, 'type' => 'petrol', 'dist' => 500, 'eff' => 10],
            ['v' => $v1, 'date' => '2023-01-20', 'qty' => 40, 'cost' => 440, 'type' => 'petrol', 'dist' => 440, 'eff' => 11],
            ['v' => $v1, 'date' => '2023-02-10', 'qty' => 60, 'cost' => 660, 'type' => 'petrol', 'dist' => 660, 'eff' => 11],
            ['v' => $v2, 'date' => '2023-01-10', 'qty' => 100, 'cost' => 900, 'type' => 'diesel', 'dist' => 800, 'eff' => 8],
            ['v' => $v2, 'date' => '2023-02-05', 'qty' => 80, 'cost' => 720, 'type' => 'diesel', 'dist' => 720, 'eff' => 9],
        ];

        foreach ($data as $item) {
            FuelLog::create([
                'vehicle_id' => $item['v']->id,
                'date' => $item['date'],
                'odometer' => 1000, // not used in analyticsData grouping
                'fuel_quantity' => $item['qty'],
                'fuel_cost' => $item['cost'],
                'fuel_price_per_unit' => $item['cost'] / $item['qty'],
                'fuel_type' => $item['type'],
                'distance_traveled' => $item['dist'],
                'fuel_efficiency' => $item['eff'],
                'status' => 'recorded'
            ]);
        }

        $startTime = microtime(true);
        $response = $this->actingAs($admin)->get('/fuel-management/analytics-data');
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);

        // Verify months (Jan 2023, Feb 2023)
        $this->assertContains('Jan 2023', $json['data']['months']);
        $this->assertContains('Feb 2023', $json['data']['months']);

        // Verify fuel_data (Jan: 50+40+100=190, Feb: 60+80=140)
        // Note: collect()->groupBy() might preserve order from original collection (ordered by date)
        $janIndex = array_search('Jan 2023', $json['data']['months']);
        $febIndex = array_search('Feb 2023', $json['data']['months']);

        $this->assertEquals(190, $json['data']['fuel_data'][$janIndex]);
        $this->assertEquals(140, $json['data']['fuel_data'][$febIndex]);

        // Verify fuel_types
        $petrolIndex = array_search('petrol', $json['data']['fuel_types']['labels']);
        $dieselIndex = array_search('diesel', $json['data']['fuel_types']['labels']);
        $this->assertEquals(150, $json['data']['fuel_types']['values'][$petrolIndex]); // 50+40+60
        $this->assertEquals(180, $json['data']['fuel_types']['values'][$dieselIndex]); // 100+80

        // Performance check for larger dataset
        $this->seedLargeDataset($v1, $v2);

        $startTime = microtime(true);
        $response = $this->actingAs($admin)->get('/fuel-management/analytics-data');
        $endTime = microtime(true);
        $executionTimeLarge = ($endTime - $startTime) * 1000;

        echo "\nExecution time for 1000 records: {$executionTimeLarge}ms\n";
    }

    private function seedLargeDataset($v1, $v2)
    {
        $records = [];
        $now = Carbon::now();
        for ($i = 0; $i < 1000; $i++) {
            $records[] = [
                'vehicle_id' => ($i % 2 == 0) ? $v1->id : $v2->id,
                'date' => $now->copy()->subDays($i % 365)->toDateString(),
                'odometer' => 1000 + $i,
                'fuel_quantity' => 50,
                'fuel_cost' => 500,
                'fuel_price_per_unit' => 10,
                'fuel_type' => ($i % 2 == 0) ? 'petrol' : 'diesel',
                'distance_traveled' => 500,
                'fuel_efficiency' => 10,
                'status' => 'recorded',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($records, 100) as $chunk) {
            DB::table('fuel_logs')->insert($chunk);
        }
    }
}
