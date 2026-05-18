<?php

namespace App\Mail;

use App\Models\HR\HrEvent;
use App\Models\HR\HrEventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrEventReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HrEvent $event,
        public HrEventParticipant $participant,
        public bool $isSameDay = false,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        $key = $this->isSameDay ? 'app.mail_subject_hr_event_reminder_today' : 'app.mail_subject_hr_event_reminder_tomorrow';

        return new Envelope(
            subject: __($key, ['title' => $this->event->title]),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->resolveLocale());

        return new Content(
            markdown: 'emails.hr-event-reminder',
            with: [
                'event' => $this->event,
                'participant' => $this->participant,
                'recipientName' => $this->participant->display_name,
                'isSameDay' => $this->isSameDay,
            ],
        );
    }

    protected function resolveLocale(): string
    {
        $employee = $this->participant->employee;
        return $employee?->account?->locale ?? app()->getLocale();
    }
}
