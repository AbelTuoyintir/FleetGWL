<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSupportService
{
    protected $systemPrompt = "You are a 24/7 AI support agent for the Ghana Water Limited (GWL) Fleet Management system.
    Assist users with questions about:
    - Vehicle Registry & Live Tracking: View locations, history, and status.
    - Fuel Management: Log purchases, consumption, and costs.
    - Maintenance: Service schedules, history, and reminders.
    - Driver Hub: Assignments and online status.
    - Reports: Utilization, cost, and fuel efficiency.
    - Documents: Insurance and roadworthiness tracking.
    - Mileage: Logs and analytics.

    Be professional, helpful, and concise.";

    public function getOrCreateChat(int $userId)
    {
        try {
            return SupportChat::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                ['subject' => 'AI System Support']
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function processMessage(int $userId, string $messageText)
    {
        $chat = $this->getOrCreateChat($userId);
        $history = collect();

        if ($chat) {
            // Save user message
            SupportMessage::create([
                'support_chat_id' => $chat->id,
                'sender_type' => 'user',
                'message' => $messageText
            ]);

            // Get last 10 messages for context (excluding the one just saved if we want to pass it explicitly or include it)
            // Let's include the last 10 messages from history.
            $history = $chat->messages()
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();
        }

        // Generate AI response
        $aiResponseText = $this->generateAiResponse($messageText, $history);

        if ($chat) {
            return SupportMessage::create([
                'support_chat_id' => $chat->id,
                'sender_type' => 'ai',
                'message' => $aiResponseText
            ]);
        }

        return (object) ['message' => $aiResponseText];
    }

    public function generateAiResponse(string $userMessage, $history = null): string
    {
        // 1. Try OpenAI
        $openaiResponse = $this->callOpenAi($userMessage, $history);
        if ($openaiResponse) {
            return $openaiResponse;
        }

        // 2. Fallback to Ollama
        $ollamaResponse = $this->callOllama($userMessage, $history);
        if ($ollamaResponse) {
            return $ollamaResponse;
        }

        // 3. Final Fallback: Keyword Matching
        return $this->keywordFallback($userMessage);
    }

    protected function callOpenAi(string $userMessage, $history)
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) return null;

        try {
            $messages = [['role' => 'system', 'content' => $this->systemPrompt]];

            if ($history && $history->isNotEmpty()) {
                foreach ($history as $msg) {
                    $messages[] = [
                        'role' => $msg->sender_type === 'user' ? 'user' : 'assistant',
                        'content' => $msg->message
                    ];
                }
            } else {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }

            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI API call failed: ' . $e->getMessage());
        }

        return null;
    }

    protected function callOllama(string $userMessage, $history)
    {
        $baseUrl = config('services.ollama.base_url');
        $model = config('services.ollama.model');

        try {
            $messages = [['role' => 'system', 'content' => $this->systemPrompt]];
            if ($history && $history->isNotEmpty()) {
                foreach ($history as $msg) {
                    $messages[] = [
                        'role' => $msg->sender_type === 'user' ? 'user' : 'assistant',
                        'content' => $msg->message
                    ];
                }
            } else {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }

            $response = Http::timeout(15)
                ->post("$baseUrl/api/chat", [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                ]);

            if ($response->successful()) {
                return $response->json('message.content');
            }
        } catch (\Throwable $e) {
            Log::error('Ollama API call failed: ' . $e->getMessage());
        }

        return null;
    }

    protected function keywordFallback(string $userMessage): string
    {
        $lowerMsg = strtolower($userMessage);

        if (str_contains($lowerMsg, 'track') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'map')) {
            return "You can view live vehicle locations and historical routes in the 'Live Tracking' section under 'Vehicle Registry'. The map supports real-time updates and multiple view modes (Light, Dark, Satellite).";
        }

        if (str_contains($lowerMsg, 'fuel')) {
            return "Manage fuel consumption and costs under 'Fuel Management'. You can log new fuel purchases, analyze consumption patterns, and view efficiency reports for each vehicle.";
        }

        if (str_contains($lowerMsg, 'vehicle') || str_contains($lowerMsg, 'fleet')) {
            return "The 'Vehicle Registry' is your central hub for all fleet units. You can add new vehicles, update their status, and see an overview of your entire fleet's health.";
        }

        if (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'service')) {
            return "Stay on top of fleet health in the 'Maintenance' section. View service schedules, maintenance history, and set up reminders for upcoming tasks like oil changes or tire rotations.";
        }

        if (str_contains($lowerMsg, 'driver')) {
            return "The 'Driver Hub' allows you to manage your team. You can assign drivers to vehicles, track their online status, and manage their assignments from one place.";
        }

        if (str_contains($lowerMsg, 'report') || str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'stats')) {
            return "Visit the 'Fleet Reports' section for deep insights. We provide utilization reports, cost analysis, and fuel efficiency metrics to help you optimize your fleet operations.";
        }

        if (str_contains($lowerMsg, 'mileage')) {
            return "Mileage logs and analytics are available in the 'Mileage Management' menu. You can track distance covered by vehicles and analyze usage trends over time.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance')) {
            return "Manage all vehicle-related paperwork in 'Insurance & Docs'. You can upload insurance policies, roadworthiness certificates, and track their expiry dates to stay compliant.";
        }

        if (str_contains($lowerMsg, 'setting')) {
            return "System configurations and fleet-wide preferences can be adjusted in the 'Fleet Settings' area.";
        }

        if (str_contains($lowerMsg, 'help')) {
            return "I can help you with: Vehicle Tracking, Fuel Management, Maintenance, Driver Hub, Reports, and Documents. Just ask me about any of these topics!";
        }

        return "I'm your 24/7 AI support agent for the Ghana Water Limited Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
    }

    public function getChatHistory(int $userId)
    {
        $chat = $this->getOrCreateChat($userId);
        if (!$chat) {
            return collect();
        }

        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }
}
