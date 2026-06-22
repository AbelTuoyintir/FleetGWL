<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AiSupportService
{
    public function getOrCreateChat(int $userId)
    {
        return SupportChat::firstOrCreate(
            ['user_id' => $userId, 'status' => 'active'],
            ['subject' => 'AI System Support']
        );
    }

    public function generateAiResponse(string $userMessage, int $userId): string
    {
        $history = $this->getChatHistory($userId)->slice(-10);
        $systemPrompt = "You are a 24/7 AI support agent for the Ghana Water Limited (GWL) Fleet Management system.
        You help users with fleet unit registration, live tracking, fuel management, maintenance schedules, driver hub assignments, reports, analytics, and document compliance.
        Be helpful, professional, and concise. If you don't know something about the specific system implementation, refer them to the system administrator.
        Current date: " . now()->toDateTimeString();

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->sender_type === 'user' ? 'user' : 'assistant',
                'content' => $msg->message
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // 1. Try OpenAI
        $response = $this->generateOpenAiResponse($messages);
        if ($response) return $response;

        // 2. Try Ollama (Local LLM)
        $response = $this->generateOllamaResponse($messages);
        if ($response) return $response;

        // 3. Fallback to Simulated Keyword Matching
        return $this->generateSimulatedResponse($userMessage);
    }

    protected function generateOpenAiResponse(array $messages): ?string
    {
        $apiKey = config('openai.api_key');
        if (!$apiKey) return null;

        try {
            $response = OpenAI::chat()->create([
                'model' => config('openai.model', 'gpt-4o'),
                'messages' => $messages,
            ]);

            return $response->choices[0]->message->content;
        } catch (Exception $e) {
            Log::error("OpenAI Error: " . $e->getMessage());
            return null;
        }
    }

    protected function generateOllamaResponse(array $messages): ?string
    {
        $baseUrl = config('services.ollama.base_url');
        if (!$baseUrl) return null;

        try {
            $response = Http::timeout(30)->post("{$baseUrl}/api/chat", [
                'model' => config('services.ollama.model', 'llama3'),
                'messages' => $messages,
                'stream' => false,
            ]);

            if ($response->successful()) {
                return $response->json('message.content');
            }
        } catch (Exception $e) {
            Log::error("Ollama Error: " . $e->getMessage());
        }

        return null;
    }

    protected function generateSimulatedResponse(string $userMessage): string
    {
        // Simulated AI logic for now
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

        // Save user message
        SupportMessage::create([
            'support_chat_id' => $chat->id,
            'sender_type' => 'user',
            'message' => $messageText
        ]);

        // Generate and save AI response
        $aiResponseText = $this->generateAiResponse($messageText, $userId);

        return SupportMessage::create([
            'support_chat_id' => $chat->id,
            'sender_type' => 'ai',
            'message' => $aiResponseText
        ]);
    }

    public function getChatHistory(int $userId)
    {
        $chat = $this->getOrCreateChat($userId);
        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }
}
