<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tracking_data_api_returns_json()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $vehicle = new Vehicle();
        $vehicle->registration_number = 'ABC-123';
        $vehicle->make = 'Toyota';
        $vehicle->model = 'Camry';
        $vehicle->year = 2020;
        $vehicle->vehicle_type = 'Saloon';
        $vehicle->chassis_number = 'CHASSIS123';
        $vehicle->status = 'active';
        $vehicle->current_latitude = 5.6037;
        $vehicle->current_longitude = -0.1870;
        $vehicle->save();

        $response = $this->actingAs($user)
            ->get(route('vehicles.tracking.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'registration_number',
                    'make',
                    'model',
                    'current_latitude',
                    'current_longitude',
                    'status',
                    'assigned_driver_id'
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('ABC-123', $data[0]['registration_number']);
    }
}
