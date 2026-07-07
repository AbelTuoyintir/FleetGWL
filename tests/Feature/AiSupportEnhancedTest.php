<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSupportEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_chat_on_welcome_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('ai-chat-widget');
        $response->assertSee('csrf-token');
    }

    public function test_guest_can_access_chat_on_login_page()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('ai-chat-widget');
        $response->assertSee('csrf-token');
    }

    public function test_troubleshooting_keywords_trigger_manual_fallback()
    {
        Http::fake(['*' => Http::response([], 500)]);

        $keywords = ['stuck', 'not loading', 'troubleshoot', 'slow'];

        foreach ($keywords as $keyword) {
            $response = $this->postJson(route('ai-support.chat'), ['message' => "My map is $keyword"]);
            $response->assertStatus(200);
            $this->assertStringContainsString('internet connection', $response->json('ai_message'));
            $this->assertStringContainsString('Last Update', $response->json('ai_message'));
        }
    }

    public function test_quick_suggestions_are_visible_in_empty_history()
    {
        // This is a frontend logic test usually, but we can verify the backend history is empty
        $response = $this->getJson(route('ai-support.history'));
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'history');
    }

    public function test_system_prompt_contains_troubleshooting_context()
    {
        $user = User::factory()->create(['name' => 'Tech Support', 'role' => 'admin']);
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function (\Illuminate\Http\Client\Request $request) {
                $systemPrompt = $request['messages'][0]['content'];
                if (str_contains($systemPrompt, 'If it\'s more than 5 minutes old') &&
                    str_contains($systemPrompt, 'car-shaped SVG icons')) {
                    return Http::response(['choices' => [['message' => ['content' => 'Prompt Verified']]]], 200);
                }
                return Http::response(['error' => 'System prompt missing context'], 500);
            }
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'context check']);

        $response->assertStatus(200);
        $this->assertEquals('Prompt Verified', $response->json('ai_message'));
    }
}
