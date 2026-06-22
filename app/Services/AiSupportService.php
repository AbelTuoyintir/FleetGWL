<?php

namespace App\Services;

use App\Models\SupportChat;
use App\Models\SupportMessage;

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
