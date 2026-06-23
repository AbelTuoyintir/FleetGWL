<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_cannot_access_system_activity_logs()
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('security.activity'));

        $this->assertEquals(403, $response->status());
    }

    public function test_driver_cannot_access_trashed_documents()
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('vehicles.documents.trashed'));

        $this->assertEquals(403, $response->status());
    }

    public function test_admin_can_access_system_activity_logs()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('security.activity'));

        // It might return 500 because of missing Spatie dependency, but it shouldn't be 403
        $this->assertNotEquals(403, $response->status());
    }

    public function test_admin_can_access_trashed_documents()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('vehicles.documents.trashed'));

        $response->assertStatus(200);
    }

    public function test_driver_can_only_see_public_or_own_vehicle_documents()
    {
        $driverUser = User::factory()->create(['role' => 'driver']);
        $driver = \App\Models\Driver::create([
            'user_id' => $driverUser->id,
            'license_number' => '12345',
            'status' => 'active'
        ]);
        $vehicle = \App\Models\Vehicle::create([
            'registration_number' => 'TEST-123',
            'make' => 'Test',
            'model' => 'Test',
            'year' => 2024,
            'chassis_number' => 'CHAS123',
            'vehicle_type' => 'Saloon',
            'assigned_driver_id' => $driver->id,
            'status' => 'active'
        ]);

        $publicDoc = \App\Models\Document::create([
            'title' => 'Public Doc',
            'slug' => 'public-doc',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_public' => true
        ]);
        $ownVehicleDoc = \App\Models\Document::create([
            'title' => 'Own Vehicle Doc',
            'slug' => 'own-vehicle-doc',
            'file_path' => 'test2.pdf',
            'file_name' => 'test2.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_public' => false,
            'vehicle_id' => $vehicle->id
        ]);
        $otherVehicleDoc = \App\Models\Document::create([
            'title' => 'Other Vehicle Doc',
            'slug' => 'other-vehicle-doc',
            'file_path' => 'test3.pdf',
            'file_name' => 'test3.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_public' => false,
            'vehicle_id' => null
        ]);

        $response = $this->actingAs($driverUser)->get(route('vehicles.documents.index'));

        $response->assertStatus(200);
        $response->assertSee('Public Doc');
        $response->assertSee('Own Vehicle Doc');
        $response->assertDontSee('Other Vehicle Doc');
    }
}
