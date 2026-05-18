<?php

namespace App\Mail;

use App\Models\HR\HrEvent;
use App\Models\HR\HrEventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrEventInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HrEvent $event,
        public HrEventParticipant $participant,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_hr_event_invitation', ['title' => $this->event->title]),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->resolveLocale());

        return new Content(
            markdown: 'emails.hr-event-invitation',
            with: [
                'event' => $this->event,
                'participant' => $this->participant,
                'recipientName' => $this->participant->display_name,
            ],
        );
    }

    protected function resolveLocale(): string
    {
        // Use participant employee's user locale if available, otherwise app default
        $employee = $this->participant->employee;
        return $employee?->account?->locale ?? app()->getLocale();
    }
}
