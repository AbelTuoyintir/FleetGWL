<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminSuspiciousActivityAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $securityContext;
    public $request;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $securityContext, $request)
    {
        $this->user = $user;
        $this->securityContext = $securityContext;
        $this->request = $request;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('🚨 Admin Alert: Suspicious Activity Detected')
                    ->text('emails.admin-suspicious-activity')
                    ->with([
                        'user' => $this->user,
                        'context' => $this->securityContext,
                        'ip' => $this->request->ip(),
                    ]);
    }
}

