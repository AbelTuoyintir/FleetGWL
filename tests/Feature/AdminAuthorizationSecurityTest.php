<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary data if needed, but for auth checks, basic users should suffice
    }

    /**
     * Test that a driver cannot access administrative fuel management routes.
     */
    public function test_driver_cannot_access_fuel_management()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($driver)->get(route('fuel-management.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access administrative driver management routes.
     */
    public function test_driver_cannot_access_drivers_management()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($driver)->get(route('drivers.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access administrative locations management routes.
     */
    public function test_driver_cannot_access_locations_management()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($driver)->get(route('locations.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access administrative mileage logs routes.
     */
    public function test_driver_cannot_access_mileage_logs_management()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($driver)->get(route('mileage-logs.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access administrative maintenance management routes.
     */
    public function test_driver_cannot_access_maintenance_management()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($driver)->get(route('maintenance.index'));

        $response->assertStatus(403);
    }

    /**
     * Test that an admin can still access these routes.
     */
    public function test_admin_can_access_administrative_routes()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('fuel-management.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('drivers.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('locations.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('mileage-logs.index'))->assertStatus(200);
    }
}
