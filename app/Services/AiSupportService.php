<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class AiSupportService
{
    public function getSystemPrompt(?User $user): string
    {
        $name = $user ? $user->name : 'Guest';
        $role = $user ? $user->role : 'Unauthenticated User';

        $prompt = "You are a 24/7 AI support agent for the Ghana Water Limited (GWL) Fleet Management system.
        Current User: {$name}
        Role: {$role}

        Assist users with questions about the platform using the following information:

        ### 1. Live Vehicle Tracking (Command Center)
        - **Map Interface:** Real-time visualization of fleet units using car-shaped SVG markers that rotate based on heading.
        - **Color Coding:** Blue (Active Trip), Green (Available/Idling).
        - **Smooth Movement:** CSS transitions provide fluid updates every 5 seconds.
        - **Detail Card:** Click a vehicle to see speed (km/h), status, and last update.
        - **Follow Mode:** Locks the camera to a specific vehicle.
        - **History Playback:** Visualize paths taken in the last 24 hours with granular breadcrumbs (speed, direction).
        - **Map Themes:** Switch between Light, Dark, and Satellite modes (Top-Right control).

        ### 2. Fleet Management
        - **Vehicle Registry:** Central hub for adding vehicles, updating status (Active, In Shop), and viewing health overview.
        - **Fuel Management:** Log purchases, track consumption, and analyze costs/efficiency.
        - **Maintenance:** Manage service schedules, history log, and upcoming reminders (e.g., oil changes).
        - **Insurance & Docs:** Track insurance and roadworthiness expiry dates.

        ### 3. Personnel & Reports
        - **Driver Hub:** Manage driver assignments and online/offline status.
        - **Reports:** Deep insights into utilization, cost analysis, and fuel efficiency.

        ### 4. User Roles
        - **Admins:** Have full access to Command Center, Registry, Reports, and Management tools.
        - **Drivers:** Primarily use the Driver Portal for dashboard, maintenance requests, and mileage logs.

        ### 5. Troubleshooting
        - **Map Issues:** Check internet connection and 'Last Update' timestamp.
        - **Markers:** Jumping markers may indicate browser performance throttling.

        Guidelines:
        - Be professional, helpful, and concise.
        - Address the user as {$name} if they are authenticated.
        - If the user is a driver, prioritize features available in the Driver Portal.
        - If the user is an admin, provide comprehensive fleet oversight instructions.";

        return $prompt;
    }

    public function getOrCreateChat(?int $userId, string $sessionId = null)
    {
        try {
            $query = SupportChat::where('status', 'active');

            if ($user) {
                $query->where('user_id', $user->id);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            } else {
                return null;
            }

            $chat = $query->first();

            if (!$chat) {
                $chat = SupportChat::create([
                    'user_id' => $user ? $user->id : null,
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

    public function processMessage(?User $user, string $messageText, string $sessionId = null)
    {
        $userId = $user ? $user->id : null;
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
        $aiResponseText = $this->generateAiResponse($messageText, $history, $user);

        if ($chat) {
            return SupportMessage::create([
                'support_chat_id' => $chat->id,
                'sender_type' => 'ai',
                'message' => $aiResponseText
            ]);
        }

        return (object) ['message' => $aiResponseText];
    }

    public function generateAiResponse(string $userMessage, $history = null, ?User $user = null): string
    {
        $systemPrompt = $this->getSystemPrompt($user);

        // 1. Try OpenAI
        $openaiResponse = $this->callOpenAi($userMessage, $history, $user);
        if ($openaiResponse) {
            return $openaiResponse;
        }

        // 2. Fallback to Ollama
        $ollamaResponse = $this->callOllama($userMessage, $history, $user);
        if ($ollamaResponse) {
            return $ollamaResponse;
        }

        // 3. Final Fallback: Keyword Matching
        return $this->keywordFallback($userMessage);
    }

    protected function callOpenAi(string $userMessage, $history, ?User $user = null)
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) return null;

        try {
            $messages = [['role' => 'system', 'content' => $this->getSystemPrompt($user)]];

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

    protected function callOllama(string $userMessage, $history, ?User $user = null)
    {
        $baseUrl = config('services.ollama.base_url');
        $model = config('services.ollama.model');

        try {
            $messages = [['role' => 'system', 'content' => $this->getSystemPrompt($user)]];
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

        if (str_contains($lowerMsg, 'help')) {
            return "I can help you with: Live Tracking (Follow Mode, History), Fuel Management, Maintenance, Driver Hub, Reports, and Insurance Documents. Just ask me about any of these topics!";
        }

        return "I'm your 24/7 AI support agent for the Ghana Water Limited Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
    }

    public function getChatHistory(?User $user, string $sessionId = null)
    {
        $userId = $user ? $user->id : null;
        $chat = $this->getOrCreateChat($userId, $sessionId);
        if (!$chat) {
            return collect();
        }

        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }
}
