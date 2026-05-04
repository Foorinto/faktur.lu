<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $isPt = $this->resolveLocale() === 'pt';

        $subject = $isPt
            ? 'O seu período de teste do faktur.lu terminou'
            : 'Votre essai faktur.lu est terminé';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = $this->resolveLocale() === 'pt' ? 'emails.pt.trial-expired' : 'emails.trial-expired';

        return new Content(
            markdown: $template,
            with: [
                'user' => $this->user,
                'subscriptionUrl' => route('subscription.index'),
            ],
        );
    }

    /**
     * Resolve the locale of the user.
     */
    protected function resolveLocale(): string
    {
        return $this->user->locale ?? app()->getLocale();
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
