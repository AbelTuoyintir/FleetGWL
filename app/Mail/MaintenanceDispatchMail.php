<?php

namespace App\Mail;

use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceDispatchMail extends Mailable
{
    use Queueable, SerializesModels;

    public $maintenance;
    public $pdfContent;

    public function __construct(Maintenance $maintenance, $pdfContent = null)
    {
        $this->maintenance = $maintenance;
        $this->pdfContent = $pdfContent;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Maintenance Dispatch Note - ' . $this->maintenance->vehicle->registration_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-dispatch',
        );
    }

    public function attachments(): array
    {
        if ($this->pdfContent) {
            return [
                Attachment::fromData(fn () => $this->pdfContent, 'Maintenance_Dispatch_Note.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
