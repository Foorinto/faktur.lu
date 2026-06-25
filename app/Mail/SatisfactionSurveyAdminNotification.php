<?php

namespace App\Mail;

use App\Models\SatisfactionSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SatisfactionSurveyAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SatisfactionSurvey $survey,
    ) {}

    public function envelope(): Envelope
    {
        $nps = $this->survey->nps_score;
        $name = $this->survey->user?->name ?? '?';

        // Admin notification - kept in FR (the admin reads in French).
        return new Envelope(
            subject: "[faktur.lu Sondage] NPS {$nps}/10 - {$name}",
        );
    }

    public function content(): Content
    {
        $score = (int) $this->survey->nps_score;
        $category = $score >= SatisfactionSurvey::PROMOTER_MIN
            ? 'Promoteur'
            : ($score <= SatisfactionSurvey::DETRACTOR_MAX ? 'Détracteur' : 'Passif');

        return new Content(
            markdown: 'emails.admin.survey-response',
            with: [
                'survey' => $this->survey,
                'user' => $this->survey->user,
                'category' => $category,
                'adminUrl' => config('app.url') . '/' . config('admin.url_prefix', 'admin') . '/surveys',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
