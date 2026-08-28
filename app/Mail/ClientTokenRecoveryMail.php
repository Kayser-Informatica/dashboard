<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientTokenRecoveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔑 [RECUPERAÇÃO DE TOKEN] {$this->client->name} - Systems Control",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-token-recovery',
        );
    }
}
