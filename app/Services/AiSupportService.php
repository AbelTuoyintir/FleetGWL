<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;

class AiSupportService
{
    public function getOrCreateChat(int $userId)
    {
        return SupportChat::firstOrCreate(
            ['user_id' => $userId, 'status' => 'active'],
            ['subject' => 'AI System Support']
        );
    }

    public function generateAiResponse(string $userMessage): string
    {
        // Simulated AI logic for now
        $lowerMsg = strtolower($userMessage);

        if (str_contains($lowerMsg, 'fuel')) {
            return "You can manage fuel logs under the 'Fuel Management' section in the sidebar. You can record fuel purchases and track consumption analytics there.";
        }

        if (str_contains($lowerMsg, 'track') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'map')) {
            return "The 'Live Tracking' map (under Vehicle Registry) shows the real-time position of all active vehicles in your fleet.";
        }

        if (str_contains($lowerMsg, 'vehicle') || str_contains($lowerMsg, 'fleet')) {
            return "The 'Vehicle Registry' allows you to manage all fleet units, track their live locations, and monitor their current status.";
        }

        if (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'service')) {
            return "Maintenance schedules and history can be found in the 'Maintenance' menu. You can also view upcoming service reminders for all vehicles.";
        }

        if (str_contains($lowerMsg, 'driver')) {
            return "The 'Driver Hub' is where you can manage driver information and assign them to specific vehicles.";
        }

        if (str_contains($lowerMsg, 'report') || str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'stats')) {
            return "Platform analytics and reports are available in the 'Fleet Reports' and 'Dashboard' sections, providing insights on utilization and costs.";
        }

        if (str_contains($lowerMsg, 'mileage')) {
            return "Mileage logs can be recorded and reviewed in the 'Mileage Management' section to track vehicle usage and distance traveled.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance')) {
            return "Vehicle insurance, roadworthiness, and other critical documents can be managed in the 'Insurance & Docs' section.";
        }

        if (str_contains($lowerMsg, 'setting')) {
            return "System configurations and fleet-wide settings can be adjusted in the 'Fleet Settings' area.";
        }

        if (str_contains($lowerMsg, 'help')) {
            return "I can help you with: \n- Vehicle tracking & locations\n- Fuel & mileage logs\n- Maintenance scheduling\n- Driver assignments\n- Reports & documents\nWhat would you like to know more about?";
        }

        return "I'm your 24/7 AI support agent for the GWL Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today? Type 'help' for a list of topics.";
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
        $aiResponseText = $this->generateAiResponse($messageText);

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
