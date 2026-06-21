<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SupportChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'How to log fuel?']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure(['ai_message']);

        $this->assertDatabaseHas('support_messages', [
            'message' => 'How to log fuel?',
            'sender_type' => 'user'
        ]);

        $this->assertDatabaseHas('support_messages', [
            'sender_type' => 'ai'
        ]);
    }

    public function test_ai_responds_to_new_keywords()
    {
        $user = User::factory()->create();

        $keywords = [
            'track' => 'Live Tracking',
            'report' => 'Fleet Reports',
            'mileage' => 'Mileage Management',
            'insurance' => 'Insurance & Docs',
            'setting' => 'Fleet Settings'
        ];

        foreach ($keywords as $keyword => $expectedSnippet) {
            $response = $this->actingAs($user)
                ->postJson(route('ai-support.chat'), ['message' => "Tell me about $keyword"]);

            $response->assertStatus(200)
                ->assertJsonFragment(['status' => 'success']);

            $this->assertStringContainsString($expectedSnippet, $response->json('ai_message'));
        }
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
