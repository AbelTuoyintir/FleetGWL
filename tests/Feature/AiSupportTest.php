<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SupportChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

class AiSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_ai_support()
    {
        $this->postJson(route('ai-support.chat'), ['message' => 'Hello'])
            ->assertStatus(401);

        $this->getJson(route('ai-support.history'))
            ->assertStatus(401);
    }

    public function test_user_can_send_message_and_get_ai_response()
    {
        config(['openai.api_key' => 'test-key']);
        config(['services.openai.api_key' => 'test-key']);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'To log fuel, go to the Fuel Management section.',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'How to log fuel?']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'ai_message' => 'To log fuel, go to the Fuel Management section.',
            ]);

        $this->assertDatabaseHas('support_messages', [
            'message' => 'How to log fuel?',
            'sender_type' => 'user'
        ]);

        $this->assertDatabaseHas('support_messages', [
            'message' => 'To log fuel, go to the Fuel Management section.',
            'sender_type' => 'ai'
        ]);
    }

    public function test_user_can_get_chat_history()
    {
        $user = User::factory()->create();

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
