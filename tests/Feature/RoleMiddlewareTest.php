<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('role:admin')->get('/_test/role-admin-only', function () {
            return response('ok', 200);
        });
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/_test/role-admin-only');

        $response->assertRedirect(route('login'));
    }

    public function test_user_with_wrong_role_gets_forbidden(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $response = $this->actingAs($driver)->get('/_test/role-admin-only');

        $response->assertForbidden();
    }

    public function test_user_with_allowed_role_can_access_route(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/_test/role-admin-only');

        $response->assertOk();
        $response->assertSee('ok');
    }
}
