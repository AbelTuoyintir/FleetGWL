<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SentinelBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_cannot_access_fuel_management_bypass()
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('fuel-management.index'));

        // Should now return 403 Forbidden
        $response->assertStatus(403);
    }

    public function test_driver_cannot_access_mileage_logs_admin()
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get(route('mileage-logs.index'));

        $response->assertStatus(403);
    }
}
