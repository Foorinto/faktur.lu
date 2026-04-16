<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DripCampaignEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $emailKey,
        public string $emailSubject,
        public string $emailBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.drip-campaign',
            with: [
                'user' => $this->user,
                'body' => $this->emailBody,
                'unsubscribeUrl' => route('drip.unsubscribe', [
                    'user' => $this->user->id,
                    'hash' => hash('sha256', $this->user->email . config('app.key')),
                ]),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
