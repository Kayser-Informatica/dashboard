<?php

namespace App\Mail;

use App\Models\MonitoredService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceRecoveredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitoredService $service
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->service->client?->name ?? 'Cliente';
        $serviceName = $this->service->name;

        return new Envelope(
            subject: "🟢 [RECUPERADO] {$clientName} - {$serviceName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-recovered',
        );
    }
}
