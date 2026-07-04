<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSupportEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_ask_about_sync_in_fallback()
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'how to sync data?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('Sync\' button', $response->json('ai_message'));
    }

    public function test_user_can_ask_about_colors_in_fallback()
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'what does the green marker mean?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('Green markers', $response->json('ai_message'));
    }

    public function test_user_can_ask_about_troubleshooting_in_fallback()
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'the map is not loading']);

        $response->assertStatus(200);
        $this->assertStringContainsString('check your internet connection', $response->json('ai_message'));
    }

    public function test_user_can_ask_about_online_status_in_fallback()
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'how to go online?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('automatically set to \'Online\'', $response->json('ai_message'));
    }
}
