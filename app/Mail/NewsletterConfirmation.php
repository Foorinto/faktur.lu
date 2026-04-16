<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'fr' => 'Confirmez votre inscription à la newsletter faktur.lu',
            'en' => 'Confirm your faktur.lu newsletter subscription',
            'de' => 'Bestätigen Sie Ihre faktur.lu Newsletter-Anmeldung',
            'lb' => 'Bestätegt Är faktur.lu Newsletter-Umeldung',
        ];

        return new Envelope(
            subject: $subjects[$this->subscriber->locale] ?? $subjects['fr'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-confirmation',
            with: [
                'subscriber' => $this->subscriber,
                'confirmUrl' => route('newsletter.confirm', $this->subscriber->confirm_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
