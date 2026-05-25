<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GoogleStyleLoginAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $alertData;
    public $level;

    /**
     * Create a new message instance.
     */
    public function __construct($alertData, $level)
    {
        $this->alertData = $alertData;
        $this->level = $level;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = match($this->level) {
            'critical' => '⚠️ Critical Security Alert: Unusual Sign-in Detected',
            'medium' => '🔐 New Sign-in from Unrecognized Device',
            default => 'ℹ️ Sign-in Notification',
        };

        return $this->subject($subject)
                    ->view('emails.google-style-login-alert-test')
                    
                    // keep subject building fast for login requests
                    
                    ->with([
                        'data' => $this->alertData,
                        'level' => $this->level,
                    ]);
    }
}

