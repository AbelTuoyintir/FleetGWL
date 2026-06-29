<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_is_throttled()
    {
        $url = route('password.email');

        // 3 requests allowed, 4th should be throttled
        for ($i = 0; $i < 3; $i++) {
            $this->post($url, ['email' => 'test@example.com'])
                ->assertStatus(302); // Redirect back (with error or success)
        }

        $this->post($url, ['email' => 'test@example.com'])
            ->assertStatus(429);
    }

    public function test_reset_password_is_throttled()
    {
        $url = route('password.update');

        // 5 requests allowed, 6th should be throttled
        for ($i = 0; $i < 5; $i++) {
            $this->post($url, [
                'token' => 'test-token',
                'email' => 'test@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password'
            ])->assertStatus(302);
        }

        $this->post($url, [
            'token' => 'test-token',
            'email' => 'test@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password'
        ])->assertStatus(429);
    }

    public function test_ai_support_chat_is_throttled()
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'AI']]]], 200)]);
        $url = route('ai-support.chat');

        // 10 requests allowed
        for ($i = 0; $i < 10; $i++) {
            $this->postJson($url, ['message' => 'test message'])
                ->assertStatus(200);
        }

        $this->postJson($url, ['message' => 'test message'])
            ->assertStatus(429);
    }

    public function test_ai_support_history_is_throttled()
    {
        $url = route('ai-support.history');

        // 20 requests allowed
        for ($i = 0; $i < 20; $i++) {
            $this->getJson($url)
                ->assertStatus(200);
        }

        $this->getJson($url)
            ->assertStatus(429);
    }
}
