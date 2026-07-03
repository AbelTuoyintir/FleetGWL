<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DriverSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_store_does_not_log_passwords()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Mock Log
        Log::shouldReceive('info')
            ->with('Driver store request received', \Mockery::on(function ($data) {
                // Assert that password is NOT in the array passed to Log::info
                return !isset($data['password']) && !isset($data['password_confirmation']);
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('Validation passed', \Mockery::on(function ($data) {
                // Assert that password is NOT in the array passed to Log::info
                return !isset($data['password']) && !isset($data['password_confirmation']);
            }))
            ->once();

        Log::shouldReceive('info')->atLeast()->once(); // For other logs like 'New user created' etc.
        Log::shouldReceive('error')->never();

        $response = $this->actingAs($admin)->post(route('drivers.store'), [
            'registration_mode' => 'new',
            'name' => 'New Driver',
            'email' => 'driver@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'license_number' => 'ABC123456',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'driver@example.com']);
    }
}
