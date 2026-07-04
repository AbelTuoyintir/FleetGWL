<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_vehicles_from_their_company()
    {
        // Create two companies
        $company1 = Company::create(['name' => 'Company One', 'slug' => 'comp-1']);
        $company2 = Company::create(['name' => 'Company Two', 'slug' => 'comp-2']);

        // Create a user for company 1
        $user1 = User::factory()->create([
            'company_id' => $company1->id,
            'role' => 'fleet_manager'
        ]);

        // Create vehicles for both companies manually
        Vehicle::create([
            'registration_number' => 'ABC-123',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'chassis_number' => 'CH-123',
            'vehicle_type' => 'sedan',
            'status' => 'active',
            'company_id' => $company1->id
        ]);

        Vehicle::create([
            'registration_number' => 'XYZ-789',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2022,
            'chassis_number' => 'CH-789',
            'vehicle_type' => 'sedan',
            'status' => 'active',
            'company_id' => $company2->id
        ]);

        // Act as user 1
        $this->actingAs($user1);

        // Manually set tenant_id as middleware would
        app()->instance('tenant_id', $user1->company_id);

        // Check that user 1 only sees company 1's vehicle
        $vehicles = Vehicle::all();

        $this->assertCount(1, $vehicles);
        $this->assertEquals('ABC-123', $vehicles->first()->registration_number);
        $this->assertEquals($company1->id, $vehicles->first()->company_id);
    }

    public function test_new_records_are_automatically_assigned_to_user_company()
    {
        $company = Company::create(['name' => 'Test Company', 'slug' => 'test-comp']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        // Manually set tenant_id as middleware would
        app()->instance('tenant_id', $user->company_id);

        // Create a vehicle without explicitly setting company_id
        $vehicle = Vehicle::create([
            'registration_number' => 'NEW-111',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2021,
            'chassis_number' => 'CH-NEW',
            'vehicle_type' => 'sedan',
            'status' => 'active'
        ]);

        $this->assertEquals($company->id, $vehicle->company_id);
    }
}
