<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoleContextAiSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_receives_role_context_for_driver()
    {
        $user = User::factory()->create([
            'name' => 'John Driver',
            'role' => 'driver'
        ]);
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) {
                $messages = $request['messages'];
                $systemPrompt = $messages[0]['content'];

                if (str_contains($systemPrompt, 'John Driver') && str_contains($systemPrompt, 'driver') && str_contains($systemPrompt, 'perspective')) {
                    return Http::response([
                        'choices' => [['message' => ['content' => 'Driver Context Received']]]
                    ], 200);
                }
                return Http::response(['choices' => [['message' => ['content' => 'Error']]]], 200);
            }
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Hello']);

        $response->assertStatus(200)
            ->assertJson(['ai_message' => 'Driver Context Received']);
    }

    public function test_ai_receives_guest_context()
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => function ($request) {
                $messages = $request['messages'];
                $systemPrompt = $messages[0]['content'];

                if (str_contains($systemPrompt, 'guest user')) {
                    return Http::response([
                        'choices' => [['message' => ['content' => 'Guest Context Received']]]
                    ], 200);
                }
                return Http::response(['choices' => [['message' => ['content' => 'Error']]]], 200);
            }
        ]);

        $response = $this->postJson(route('ai-support.chat'), ['message' => 'Hello']);

        $response->assertStatus(200)
            ->assertJson(['ai_message' => 'Guest Context Received']);
    }
}
