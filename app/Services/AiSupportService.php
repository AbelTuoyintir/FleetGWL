<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;

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

        return "I'm your 24/7 AI support agent for the GWL Fleet Management system. How can I assist you with your fleet, fuel, or maintenance needs today?";
    }

    public function processMessage(int $userId, string $messageText)
    {
        $chat = $this->getOrCreateChat($userId);

        // If DB tables are missing, degrade gracefully.
        if (!$chat) {
            $aiResponseText = $this->generateAiResponse($messageText);
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
        if (!$chat) {
            return collect();
        }

        return $chat->messages()->orderBy('created_at', 'asc')->get();
    }

}
