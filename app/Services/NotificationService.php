<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send in-app notification to a user.
     */
    public function sendCallNotification($userId, $title, $message, $type = 'info')
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $title . ': ' . $message,
            'is_read' => false,
            'status' => 'active',
        ]);
    }
}
