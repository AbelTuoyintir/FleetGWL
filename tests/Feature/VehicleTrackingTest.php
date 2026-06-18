<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_tracking_page_is_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('vehicles.tracking'));
        $response->assertStatus(200);
    }

    public function test_tracking_data_api_returns_correct_structure()
    {
        Vehicle::create([
            'registration_number' => 'GW-1234-23',
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => 'active',
            'year' => 2023,
            'chassis_number' => 'CH1234',
            'vehicle_type' => 'sedan'
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('vehicles.tracking.data'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'registration_number',
                        'current_latitude',
                        'current_longitude',
                        'speed',
                        'heading',
                        'is_on_trip'
                    ]
                ]
            ]);
    }

    public function test_vehicle_history_api()
    {
        $vehicle = Vehicle::create([
            'registration_number' => 'GW-5678-23',
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => 'active',
            'year' => 2023,
            'chassis_number' => 'CH5678',
            'vehicle_type' => 'sedan'
        ]);

        VehicleLocationHistory::create([
            'vehicle_id' => $vehicle->id,
            'latitude' => 5.6037,
            'longitude' => -0.1870,
            'recorded_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('vehicles.tracking.history', ['id' => $vehicle->id]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['latitude', 'longitude', 'recorded_at']
                ]
            ]);
    }
}
