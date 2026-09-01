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
        app()->setLocale($this->subscriber->locale ?? app()->getLocale());

        return new Envelope(
            subject: __('app.mail_subject_newsletter_confirmation', ['app' => config('marque.nom')]),
        );
    }

    public function content(): Content
    {
        $template = ($this->subscriber->locale ?? null) === 'pt'
            ? 'emails.pt.newsletter-confirmation'
            : 'emails.newsletter-confirmation';

        return new Content(
            markdown: $template,
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
