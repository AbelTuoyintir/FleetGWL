<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSupportTroubleshootingTest extends TestCase
{
    use RefreshDatabase;

    public function test_prioritized_troubleshooting_fallback()
    {
        $user = User::factory()->create();

        // Mock API failure to trigger keyword fallback
        Http::fake(['*' => Http::response([], 500)]);

        // Test 'stuck' keyword
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'The vehicle GW-101-24 is stuck']);

        $response->assertStatus(200);
        $this->assertStringContainsString('check the \'Last Update\' timestamp', $response->json('ai_message'));

        // Test 'not loading' keyword
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'The map is not loading']);

        $response->assertStatus(200);
        $this->assertStringContainsString('check your internet connection', $response->json('ai_message'));

        // Test 'jump' keyword
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Why do markers jump?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('browser performance might be throttled', $response->json('ai_message'));
    }

    public function test_new_feature_keywords_fallback()
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response([], 500)]);

        // Test 'job order' keyword
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'How to create a job order?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('create job orders with checklists', $response->json('ai_message'));

        // Test 'dispatch' keyword
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'What is dispatch and release?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('Dispatch\' a vehicle when it starts a service', $response->json('ai_message'));

        // Test 'hierarchy' related keywords
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Tell me about regions and districts']);

        $response->assertStatus(200);
        $this->assertStringContainsString('location hierarchy', $response->json('ai_message'));

        // Test 'bulk' import keywords
        $response = $this->actingAs($user)
            ->postJson(route('ai-support.chat'), ['message' => 'Can I import vehicles in bulk?']);

        $response->assertStatus(200);
        $this->assertStringContainsString('importing vehicles or drivers using CSV or Excel', $response->json('ai_message'));
    }
}
