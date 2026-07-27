<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Guest users cannot upload location.
     */
    public function test_guest_cannot_upload_location()
    {
        $response = $this->postJson('/driver/location', [
            'latitude' => 5.6037,
            'longitude' => -0.1870,
        ]);

        $response->assertStatus(401);
    }

    /**
     * A logged-in driver can successfully upload location.
     */
    public function test_driver_can_upload_location_successfully()
    {
        // 1. Setup User and Driver
        $user = User::factory()->create([
            'role' => 'driver',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'license_number' => 'GH-DRV-12345',
            'status' => 'active',
        ]);

        // 2. Setup assigned Vehicle
        $vehicle = Vehicle::create([
            'registration_number' => 'GW-101-26',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'chassis_number' => 'TOYOTA1234567890X',
            'vehicle_type' => 'pickup',
            'status' => 'active',
            'assigned_driver_id' => $driver->id,
        ]);

        // 3. Post telemetry coordinates
        $response = $this->actingAs($user)->postJson('/driver/location', [
            'latitude' => 5.6123,
            'longitude' => -0.1945,
            'speed' => 45.5,
            'heading' => 180,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Location updated successfully.',
        ]);

        // 4. Assert vehicle is updated in database
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'current_latitude' => 5.6123,
            'current_longitude' => -0.1945,
            'speed' => 45.5,
            'heading' => 180,
        ]);

        // 5. Assert location history breadcrumb is stored
        $this->assertDatabaseHas('vehicle_location_histories', [
            'vehicle_id' => $vehicle->id,
            'latitude' => 5.6123,
            'longitude' => -0.1945,
            'speed' => 45.5,
            'heading' => 180,
        ]);
    }

    /**
     * Validation errors are thrown for bad GPS coordinates bounds.
     */
    public function test_driver_location_validation_errors()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $driver = Driver::create([
            'user_id' => $user->id,
            'license_number' => 'GH-DRV-99999',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/driver/location', [
            'latitude' => 150.0, // Invalid latitude (> 90)
            'longitude' => -187.0, // Invalid longitude (< -180)
            'speed' => -5, // Invalid speed (< 0)
            'heading' => 400, // Invalid heading (> 360)
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude', 'speed', 'heading']);
    }

    /**
     * Admin tracking controller reads real GPS coordinates when they are updated recently.
     */
    public function test_admin_tracking_reads_real_gps_when_recent()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $driver = Driver::create([
            'user_id' => $user->id,
            'license_number' => 'GH-DRV-123',
            'status' => 'active',
        ]);

        // Set last seen at to now (which is within the last 5 minutes)
        $vehicle = Vehicle::create([
            'registration_number' => 'GW-REAL-01',
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2023,
            'chassis_number' => 'CRUISER777',
            'vehicle_type' => 'suv',
            'status' => 'active',
            'assigned_driver_id' => $driver->id,
            'current_latitude' => 5.5555,
            'current_longitude' => -0.1111,
            'speed' => 95.0, // Speeding!
            'heading' => 90,
            'last_seen_at' => now(),
        ]);

        // Let's create an admin user to make the request to the tracking endpoint
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@gwc.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Access the tracking locations data API
        $response = $this->actingAs($admin)->getJson('/vehicles/tracking/data');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.current_latitude', 5.5555);
        $response->assertJsonPath('data.0.current_longitude', -0.1111);
        $response->assertJsonPath('data.0.speed', 95);
        $response->assertJsonPath('data.0.heading', 90);
    }
}
