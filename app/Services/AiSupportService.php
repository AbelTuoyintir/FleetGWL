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

        if (str_contains($lowerMsg, 'vehicle') || str_contains($lowerMsg, 'fleet')) {
            return "The 'Vehicle Registry' allows you to manage all fleet units, track their live locations, and monitor their current status.";
        }

        if (str_contains($lowerMsg, 'maintenance') || str_contains($lowerMsg, 'service')) {
            return "Maintenance schedules and history can be found in the 'Maintenance' menu. You can also view upcoming service reminders for all vehicles.";
        }

        if (str_contains($lowerMsg, 'driver')) {
            return "The 'Driver Hub' is where you can manage driver information and assign them to specific vehicles.";
        }

        if (str_contains($lowerMsg, 'location') || str_contains($lowerMsg, 'region') || str_contains($lowerMsg, 'station')) {
            return "Manage operational areas under 'Location Management'. You can define Regions, Districts, and Stations for better fleet organization.";
        }

        if (str_contains($lowerMsg, 'report') || str_contains($lowerMsg, 'analytics') || str_contains($lowerMsg, 'stat')) {
            return "You can access detailed 'Fleet Reports' including Utilization, Cost Analysis, and Fuel Efficiency from the sidebar menu.";
        }

        if (str_contains($lowerMsg, 'mileage') || str_contains($lowerMsg, 'odometer') || str_contains($lowerMsg, 'distance')) {
            return "Track vehicle mileage and view odometer logs in the 'Mileage Management' section. This helps monitor usage and plan servicing.";
        }

        if (str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'insurance') || str_contains($lowerMsg, 'license')) {
            return "Manage vehicle insurance, registration, and other critical documents in the 'Insurance & Docs' section to ensure compliance.";
        }

        return "I'm your 24/7 AI support agent for the GWL Fleet Management system. I can help you with vehicle tracking, fuel management, maintenance schedules, reports, and more. What would you like to know?";
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
