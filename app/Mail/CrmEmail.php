<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public string $emailSubject,
        public string $emailBody,
        public ?string $senderName = null,
        public ?string $senderEmail = null,
        public ?string $senderPhone = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        $template = $this->resolveLocale() === 'pt' ? 'emails.pt.crm' : 'emails.crm';

        return new Content(
            markdown: $template,
            with: [
                'client' => $this->client,
                'body' => $this->emailBody,
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'senderPhone' => $this->senderPhone,
            ],
        );
    }

    /**
     * Resolve the locale of the recipient (client).
     */
    protected function resolveLocale(): string
    {
        return $this->client->locale ?? app()->getLocale();
    }

    public function attachments(): array
    {
        return [];
    }
}
