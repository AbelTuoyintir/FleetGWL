<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSupportService
{
    public function getOrCreateChat(int $userId)
    {
        // If the required tables were never migrated (e.g. support_chats/support_messages),
        // don't crash the whole app.
        try {
            return SupportChat::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                ['subject' => 'AI System Support']
            );
        } catch (\Throwable $e) {
            return null;
        }
    }


    public function generateAiResponse(string $userMessage, array $history = []): string
    {
        // 1. Try OpenAI
        $openaiKey = config('services.openai.api_key');
        if ($openaiKey) {
            try {
                $messages = [
                    ['role' => 'system', 'content' => "You are a 24/7 AI support agent for the Ghana Water Limited Fleet Management system. Assist users with platform difficulties. System features include Vehicle Tracking, Fuel Management, Maintenance, Driver Hub, Reports, and Documents."]
                ];

                foreach ($history as $h) {
                    $messages[] = ['role' => $h->sender_type === 'user' ? 'user' : 'assistant', 'content' => $h->message];
                }

                $messages[] = ['role' => 'user', 'content' => $userMessage];

                $response = Http::withToken($openaiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => $messages,
                        'max_tokens' => 500,
                    ]);

                if ($response->successful()) {
                    return $response->json('choices.0.message.content');
                }
            } catch (\Throwable $e) {
                Log::error("OpenAI failed: " . $e->getMessage());
            }
        }

        // 2. Fallback to Ollama (Local AI)
        $ollamaBaseUrl = config('services.ollama.base_url');
        if ($ollamaBaseUrl) {
            try {
                $prompt = "You are a 24/7 AI support agent for the Ghana Water Limited Fleet Management system.\n";
                foreach ($history as $h) {
                    $prompt .= ($h->sender_type === 'user' ? "User: " : "AI: ") . $h->message . "\n";
                }
                $prompt .= "User: $userMessage\n";
                $prompt .= "AI:";

                $response = Http::post($ollamaBaseUrl . '/api/chat', [
                    'model' => config('services.ollama.model', 'llama3'),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => false
                ]);

                if ($response->successful()) {
                    return $response->json('message.content');
                }
            } catch (\Throwable $e) {
                Log::error("Ollama failed: " . $e->getMessage());
            }
        }

        // 3. Final Fallback: Simulated keyword matching
        return $this->generateSimulatedResponse($userMessage);
    }

    protected function generateSimulatedResponse(string $userMessage): string
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

    public function processMessage(int $userId, string $messageText)
    {
        $chat = $this->getOrCreateChat($userId);

        // Get recent history for context
        $history = $chat ? $chat->messages()->orderBy('created_at', 'desc')->take(10)->get()->reverse()->values()->all() : [];

        // If DB tables are missing, degrade gracefully.
        if (!$chat) {
            $aiResponseText = $this->generateAiResponse($messageText, $history);
            return (object) [
                'message' => $aiResponseText
            ];
        }

        // Save user message
        SupportMessage::create([
            'support_chat_id' => $chat->id,
            'sender_type' => 'user',
            'message' => $messageText
        ]);

        // Generate and save AI response
        $aiResponseText = $this->generateAiResponse($messageText, $history);

        return SupportMessage::create([
            'support_chat_id' => $chat->id,
            'sender_type' => 'ai',
            'message' => $aiResponseText
        ]);
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
