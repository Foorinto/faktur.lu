<?php

namespace App\Mail;

use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class SatisfactionSurveyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public SatisfactionSurvey $survey,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.survey_email_subject', [], $this->resolveLocale()),
        );
    }

    public function content(): Content
    {
        $locale = $this->resolveLocale();

        return new Content(
            markdown: 'emails.satisfaction-survey',
            with: [
                'greeting' => __('app.survey_email_greeting', ['name' => $this->user->name], $locale),
                'intro' => __('app.survey_email_intro', [], $locale),
                'ctaLabel' => __('app.survey_email_cta', [], $locale),
                'regards' => __('app.email_regards', [], $locale),
                'signature' => __('app.email_team_signature', [], $locale),
                'surveyUrl' => $this->survey->getSurveyUrl($locale),
            ],
        );
    }

    protected function resolveLocale(): string
    {
        return $this->user->locale ?? App::getLocale();
    }

    public function attachments(): array
    {
        return [];
    }
}
