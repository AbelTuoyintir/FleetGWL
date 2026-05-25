<?php
// app/Mail/LoginNotification.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $loginData;
    public $isAdminAlert;

    /**
     * Create a new message instance.
     *
     * @param array $loginData
     * @param bool $isAdminAlert
     */
    public function __construct($loginData, $isAdminAlert = false)
    {
        $this->loginData = $loginData;
        $this->isAdminAlert = $isAdminAlert;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->isAdminAlert 
            ? '⚠️ Security Alert: Admin Account Login Detected'
            : '🔐 Login Notification: Your account was accessed';
            
        return $this->subject($subject)
                    ->markdown('emails.login-notification')
                    ->with([
                        'loginData' => $this->loginData,
                        'isAdminAlert' => $this->isAdminAlert
                    ]);
    }
}