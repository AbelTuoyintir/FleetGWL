<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSupportService
{
    protected function getSystemPrompt(?User $user): string
    {
        $name = $user ? $user->name : 'Guest';
        $role = $user ? $user->role : 'visitor';

        return "You are a 24/7 AI support agent for the Ghana Water Limited (GWL) Fleet Management system.
        The current user is $name with the role of $role.

        Assist users with questions about:
        - Vehicle Registry & Live Tracking: View locations, history, and status. Features car-shaped SVG markers that rotate based on heading. Smooth movement via CSS transitions (0.8s linear). Polling every 5 seconds from /vehicles/tracking/data.
        - Follow Mode: Locked camera on a specific vehicle. Accessible via the detail card.
        - History Playback: Visualize paths taken in the last 24 hours via dashed blue lines.
        - Fuel Management: Log purchases, consumption, and costs.
        - Maintenance: Service schedules, history, reminders, and alerts.
        - Driver Hub: Assignments and online status. Drivers are automatically 'online' upon login.
        - Reports: Utilization, cost, and fuel efficiency.
        - Documents: Insurance and roadworthiness tracking (Insurance & Docs). Strict validation on PDF, JPG, PNG, DOCX, XLS, CSV.
        - Map Themes: Light, Dark, and Satellite modes.

        Technical details:
        - Map uses Leaflet.js.
        - Real-time updates use L.divIcon for car SVGs.
        - Rotation is based on 'heading' property (0-360 degrees).
        - Speeding alerts trigger above 80 km/h.

        Be professional, helpful, and concise. Address the user by name if available.";
    }

    public function getOrCreateChat(?User $user, string $sessionId = null)
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
        $chat = $this->getOrCreateChat($user, $sessionId);
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

    protected function callOpenAi(string $userMessage, $history, ?User $user)
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

    protected function callOllama(string $userMessage, $history, ?User $user)
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
            return "You can view live vehicle locations and historical routes in the 'Live Tracking' section. The map uses car-shaped SVG markers that rotate based on their heading (0-360°) and move smoothly every 5 seconds using CSS transitions. You can switch between Light, Dark, and Satellite themes in the top-right control.";
        }

        if (str_contains($lowerMsg, 'follow')) {
            return "In 'Live Tracking', click on a vehicle map icon or sidebar entry to open its detail card and select 'Follow'. This locks the camera to that vehicle's real-time position.";
        }

        if (str_contains($lowerMsg, 'history') || str_contains($lowerMsg, 'playback')) {
            return "You can visualize a vehicle's path from the last 24 hours by clicking 'History' on its detail card in 'Live Tracking'. Routes are shown as dashed blue lines on the map.";
        }

        if (str_contains($lowerMsg, 'fuel')) {
            return "Manage fuel consumption and costs under 'Fuel Management'. You can log new fuel purchases, analyze consumption patterns, and view efficiency reports (km per liter).";
        }

        if (str_contains($lowerMsg, 'vehicle') || str_contains($lowerMsg, 'fleet')) {
            return "The 'Vehicle Registry' is your central hub for adding new units, updating their status (Active, In Shop), and assigning drivers.";
        }

        if (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'service') || str_contains($lowerMsg, 'repair')) {
            return "Stay on top of fleet health in 'Maintenance'. View service schedules, maintenance history, and set up reminders for tasks like oil changes or tire rotations.";
        }

        if (str_contains($lowerMsg, 'driver')) {
            return "The 'Driver Hub' allows you to manage your team, assign drivers to vehicles, and track their online/offline status. Drivers are marked online automatically when they log in.";
        }

        if (str_contains($lowerMsg, 'report') || str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'stats')) {
            return "Visit 'Fleet Reports' for deep insights into vehicle utilization, cost analysis, and fuel efficiency metrics. You can also view maintenance analytics.";
        }

        if (str_contains($lowerMsg, 'mileage')) {
            return "Mileage logs and analytics are available in the 'Mileage Management' menu. It tracks distance covered and helps identify vehicles needing maintenance.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance') || str_contains($lowerMsg, 'roadworthy')) {
            return "Manage vehicle paperwork in 'Insurance & Docs'. You can upload and track insurance policies and roadworthiness certificates, including their expiry alerts.";
        }

        if (str_contains($lowerMsg, 'theme') || str_contains($lowerMsg, 'dark mode') || str_contains($lowerMsg, 'satellite')) {
            return "The tracking map supports three themes: Light, Dark (best for night monitoring), and Satellite (real-world terrain). Switch themes using the control in the top-right of the map.";
        }

        if (str_contains($lowerMsg, 'help')) {
            return "I can help you with: Live Tracking (Follow Mode, History), Fuel Management, Maintenance, Driver Hub, Reports, and Insurance Documents. Just ask me about any of these topics!";
        }

        return "I'm your 24/7 AI support agent for the Ghana Water Limited Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
    }

    public function getChatHistory(?User $user, string $sessionId = null)
    {
        $chat = $this->getOrCreateChat($user, $sessionId);
        if (!$chat) {
            return collect();
        }

        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }
}
