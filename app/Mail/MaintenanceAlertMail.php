<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MaintenanceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;
    public $recipientType;

    public function __construct($alertData, $recipientType)
    {
        $this->alertData = $alertData;
        $this->recipientType = $recipientType;
    }

    public function build()
    {
        $subject = $this->recipientType == 'driver' 
            ? '⚠️ Maintenance Alert: Your assigned vehicle needs service' 
            : '🚨 URGENT: Vehicle Maintenance Required';

        return $this->subject($subject)
                    ->markdown('emails.maintenance-alert')
                    ->with([
                        'alertData' => $this->alertData,
                        'recipientType' => $this->recipientType
                    ]);
    }
}