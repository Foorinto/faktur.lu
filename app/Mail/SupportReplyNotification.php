<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportReplyNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public string $replyContent
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_support_reply', [
                'reference' => $this->ticket->reference,
                'subject' => $this->ticket->subject,
            ]),
        );
    }

    public function content(): Content
    {
        $template = $this->resolveLocale() === 'pt' ? 'emails.pt.support.reply' : 'emails.support.reply';

        return new Content(
            markdown: $template,
            with: [
                'ticket' => $this->ticket,
                'replyContent' => $this->replyContent,
                'ticketUrl' => config('app.url') . '/support/' . $this->ticket->id,
            ],
        );
    }

    /**
     * Resolve the locale of the ticket owner.
     */
    protected function resolveLocale(): string
    {
        return $this->ticket->user?->locale ?? app()->getLocale();
    }

    public function attachments(): array
    {
        return [];
    }
}
