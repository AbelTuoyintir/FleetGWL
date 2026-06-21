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

        if (str_contains($lowerMsg, 'track') || str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'map')) {
            return "You can view live vehicle locations and historical routes in the 'Live Tracking' section under Vehicle Registry.";
        }

        if (str_contains($lowerMsg, 'fuel')) {
            return "You can manage fuel logs under the 'Fuel Management' section in the sidebar. You can record fuel purchases and track consumption analytics there.";
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
            return "Comprehensive fleet reports, including utilization and cost analysis, are available in the 'Fleet Reports' section.";
        }

        if (str_contains($lowerMsg, 'mileage')) {
            return "Record and monitor vehicle distances in the 'Mileage Management' section of the sidebar.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance')) {
            return "Manage vehicle insurance policies, roadworthy certificates, and other digital documents in the 'Insurance & Docs' module.";
        }

        if (str_contains($lowerMsg, 'setting')) {
            return "System configurations and fleet-wide preferences can be adjusted in the 'Fleet Settings' menu.";
        }

        return "I'm your 24/7 AI support agent for the GWL Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
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
