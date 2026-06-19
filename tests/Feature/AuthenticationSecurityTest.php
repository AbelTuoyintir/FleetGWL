<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_generic_error_for_non_existent_user()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Invalid credentials.']);
    }

    public function test_login_returns_generic_error_for_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('correct_password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Invalid credentials.']);
    }

    public function test_login_returns_generic_error_for_inactive_account()
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Invalid credentials.']);
    }
}
