<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\SupportChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_ai_support()
    {
        config(['services.openai.api_key' => 'test-key']);
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'AI Response']]]], 200)
        ]);

        $this->postJson(route('ai-support.chat'), ['message' => 'Hello'])
            ->assertStatus(200)
            ->assertJson(['status' => 'success', 'ai_message' => 'AI Response']);

        $this->getJson(route('ai-support.history'))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_user_can_send_message_and_get_openai_response()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['company_id' => $company->id]);

        // Ensure tenant is bound for test
        app()->instance('tenant_id', $company->id);

        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'OpenAI Response']]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'How to log fuel?']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'ai_message' => 'OpenAI Response'
            ]);

        $this->assertDatabaseHas('support_messages', [
            'message' => 'OpenAI Response',
            'sender_type' => 'ai'
        ]);
    }

    public function test_ai_support_uses_personalized_system_prompt()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['name' => 'John Doe', 'role' => 'admin', 'company_id' => $company->id]);
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) {
                $messages = $request['messages'];
                $systemMessage = collect($messages)->firstWhere('role', 'system')['content'];

                if (str_contains($systemMessage, 'John Doe') && str_contains($systemMessage, 'admin')) {
                    return Http::response([
                        'choices' => [['message' => ['content' => 'Personalized Response']]]
                    ], 200);
                }
                return Http::response([], 400);
            }
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Help me']);

        $response->assertStatus(200)
            ->assertJson(['ai_message' => 'Personalized Response']);
    }

    public function test_user_falls_back_to_ollama_if_openai_fails()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['company_id' => $company->id]);
        config(['services.openai.api_key' => 'test-key']);
        config(['services.ollama.base_url' => 'http://localhost:11434']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([], 500),
            'http://localhost:11434/api/chat' => Http::response([
                'message' => ['content' => 'Ollama Response']
            ], 200)
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'How to log fuel?']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'ai_message' => 'Ollama Response'
            ]);
    }

    public function test_user_falls_back_to_keywords_if_all_apis_fail()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['company_id' => $company->id]);
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            '*' => Http::response([], 500)
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'fuel questions']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertStringContainsString('Manage fuel consumption', $response->json('ai_message'));
    }

    public function test_user_can_ask_about_follow_mode_in_fallback()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'how to follow a vehicle?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('select \'Follow\'', $response->json('ai_message'));
    }

    public function test_user_can_get_chat_history()
    {
        $company = Company::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create(['company_id' => $company->id]);

        // Ensure tenant is bound for test
        app()->instance('tenant_id', $company->id);

        // Mock response
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => 'Hi']]]], 200)
        ]);

        // Send a message first
        $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Hello AI']);

        $response = $this->actingAs($user)
            ->getJson(route('ai-support.history'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(2, 'history'); // User message + AI response
    }
}
