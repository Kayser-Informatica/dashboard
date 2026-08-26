<?php

namespace App\Mail;

use App\Models\MonitoredService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitoredService $service,
        public string $alertType = 'overdue', // 'overdue' ou 'failed'
        public ?string $details = null
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->service->client?->name ?? 'Cliente';
        $serviceName = $this->service->name;
        $subjectPrefix = $this->alertType === 'failed' ? '🚨 [FALHA]' : '⚠️ [ATRASO]';

        return new Envelope(
            subject: "{$subjectPrefix} {$clientName} - {$serviceName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-alert',
        );
    }
}
