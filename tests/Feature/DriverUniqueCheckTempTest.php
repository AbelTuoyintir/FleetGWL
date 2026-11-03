<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverUniqueCheckTempTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_check(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingUser = User::factory()->create(['role' => 'driver']);

        // create a driver for existingUser
        \App\Models\Driver::create([
            'user_id' => $existingUser->id,
            'license_number' => 'LIC-1',
            'status' => 'active',
        ]);

        // now try to create another driver with same user_id (should fail validation)
        $response = $this->actingAs($admin)->post(route('drivers.store'), [
            'registration_mode' => 'new',
            'user_id' => $existingUser->id,
            'name' => 'Another Driver',
            'email' => 'another@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'license_number' => 'LIC-2',
        ]);

        dump($response->status());
        dump(session('errors') ? session('errors')->all() : 'no errors');
    }
}
