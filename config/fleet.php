<?php
// config/fleet.php
return [
    'notifications' => [
        'driver' => [
            'cooldown_minutes' => 60, // Send at most every 60 minutes
            'send_for_unusual_hours' => true, // Notify for night shifts
            'send_for_ip_change' => true, // Notify when IP changes
            'use_daily_digest' => false, // Send daily summary instead
            'suspicious_threshold' => 3, // Failed attempts before flagging
        ],
        'technician' => [
            'cooldown_hours' => 12,
            'always_notify_admin' => false,
        ],
        'admin' => [
            'always_notify' => true,
            'notify_other_admins' => true,
            'send_sms_for_suspicious' => true,
        ],
    ],
];