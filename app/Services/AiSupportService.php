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
    - Vehicle Registry & Live Tracking: View locations, history, and status. Features car-shaped SVG markers that rotate based on heading. Smooth movement via CSS transitions. Polling every 5 seconds.
    - Vehicle Indicators: Blue markers mean 'On Trip', while Green markers mean 'Available/Idling'.
    - Follow Mode: Locked camera on a specific vehicle.
    - History Playback: Visualize paths taken in the last 24 hours.
    - Fuel Management: Log purchases, consumption, and costs.
    - Maintenance: Service schedules, history, reminders, and alerts.
    - Driver Hub: Assignments and online status (Auto-set to 'Online' upon login).
    - Reports: Utilization, cost, and fuel efficiency.
    - Documents: Insurance and roadworthiness tracking (Insurance & Docs).
    - Map Themes: Light, Dark, and Satellite modes (toggle via top-right control).
    - Troubleshooting: Use the 'Sync' button to refresh data immediately. If a vehicle seems stuck, check the 'Last Update' timestamp on the Detail Card; if it's over 5 minutes, the driver may be in a poor signal area.

    Be professional, helpful, and concise.";

    public function getOrCreateChat(?int $userId, string $sessionId = null)
    {
        try {
            $query = SupportChat::where('status', 'active');

            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            } else {
                return null;
            }

            $chat = $query->first();

            if (!$chat) {
                $chat = SupportChat::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'subject' => 'AI System Support',
                    'status' => 'active'
                ]);
            }

            return $chat;
        } catch (\Throwable $e) {
            Log::error('Error in getOrCreateChat: ' . $e->getMessage());
            return null;
        }
    }

    public function processMessage(?int $userId, string $messageText, string $sessionId = null)
    {
        $chat = $this->getOrCreateChat($userId, $sessionId);
        $history = collect();

        if ($chat) {
            // Save user message
            SupportMessage::create([
                'support_chat_id' => $chat->id,
                'sender_type' => 'user',
                'message' => $messageText
            ]);

            // Get last 10 messages for context
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

            // Always explicitly append the current message if not already in history loop
            // but the way history is fetched (last 10) it might already be there.
            // Let's ensure it's there.

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

        if (str_contains($lowerMsg, 'troubleshoot') || str_contains($lowerMsg, 'not loading') || str_contains($lowerMsg, 'stuck')) {
            return "If the map isn't loading, check your internet connection. If a vehicle appears stuck, check its 'Last Update' timestamp; if it's over 5 minutes old, the driver may be in a poor signal area. You can also try the 'Sync' button to force a refresh.";
        }

        if (str_contains($lowerMsg, 'track') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'map')) {
            return "You can view live vehicle locations and historical routes in the 'Live Tracking' section. The map uses car-shaped SVG markers that rotate based on heading and move smoothly every 5 seconds. You can switch between Light, Dark, and Satellite themes.";
        }

        if (str_contains($lowerMsg, 'follow')) {
            return "In 'Live Tracking', click on a vehicle to open its detail card and select 'Follow'. This locks the camera to that vehicle as it moves.";
        }

        if (str_contains($lowerMsg, 'history') || str_contains($lowerMsg, 'playback')) {
            return "You can visualize a vehicle's path from the last 24 hours by clicking 'History' on its detail card in the 'Live Tracking' view.";
        }

        if (str_contains($lowerMsg, 'fuel')) {
            return "Manage fuel consumption and costs under 'Fuel Management'. You can log new fuel purchases, analyze consumption patterns, and view efficiency reports.";
        }

        if (str_contains($lowerMsg, 'vehicle') || str_contains($lowerMsg, 'fleet')) {
            return "The 'Vehicle Registry' is your central hub. You can add new vehicles, update their status (Active, In Shop), and see an overview of your entire fleet's health.";
        }

        if (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'service') || str_contains($lowerMsg, 'repair')) {
            return "Stay on top of fleet health in the 'Maintenance' section. View service schedules, maintenance history, and set up reminders for upcoming tasks like oil changes.";
        }

        if (str_contains($lowerMsg, 'driver')) {
            return "The 'Driver Hub' allows you to manage your team, assign drivers to vehicles, and track their online/offline status.";
        }

        if (str_contains($lowerMsg, 'report') || str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'stats')) {
            return "Visit 'Fleet Reports' for deep insights into vehicle utilization, cost analysis, and fuel efficiency metrics.";
        }

        if (str_contains($lowerMsg, 'mileage')) {
            return "Mileage logs and analytics are available in the 'Mileage Management' menu to track distance covered and analyze usage trends.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance') || str_contains($lowerMsg, 'roadworthy')) {
            return "Manage vehicle paperwork in 'Insurance & Docs'. Track insurance policies and roadworthiness certificates, including their expiry dates.";
        }

        if (str_contains($lowerMsg, 'theme') || str_contains($lowerMsg, 'dark mode') || str_contains($lowerMsg, 'satellite')) {
            return "The tracking map supports three themes: Light (general use), Dark (night monitoring), and Satellite (real-world terrain). Switch themes using the control in the top-right of the map.";
        }

        if (str_contains($lowerMsg, 'sync') || str_contains($lowerMsg, 'refresh')) {
            return "Use the 'Sync' button in the top-right Map Controls to force an immediate refresh of all vehicle data.";
        }

        if (str_contains($lowerMsg, 'color') || str_contains($lowerMsg, 'blue') || str_contains($lowerMsg, 'green')) {
            return "On the map, Blue car markers indicate a vehicle is currently on an active trip, while Green markers indicate the vehicle is available or idling.";
        }


        if (str_contains($lowerMsg, 'online') || str_contains($lowerMsg, 'status')) {
            return "Drivers are automatically set to 'Online' when they log in. You can track their real-time status and vehicle assignments in the Driver Hub.";
        }

        if (str_contains($lowerMsg, 'help')) {
            return "I can help you with: Live Tracking (Follow Mode, History), Fuel Management, Maintenance, Driver Hub, Reports, and Insurance Documents. Just ask me about any of these topics!";
        }

        return "I'm your 24/7 AI support agent for the Ghana Water Limited Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
    }

    public function getChatHistory(?int $userId, string $sessionId = null)
    {
        $chat = $this->getOrCreateChat($userId, $sessionId);
        if (!$chat) {
            return collect();
        }

        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }
}
