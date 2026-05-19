<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectCollaboratorInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public User $invitee,
        public bool $isNewUser = false,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_project_collaborator_invitation', ['project' => $this->project->title]),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->resolveLocale());

        return new Content(
            markdown: 'emails.project-collaborator-invitation',
            with: [
                'project' => $this->project,
                'invitee' => $this->invitee,
                'isNewUser' => $this->isNewUser,
            ],
        );
    }

    protected function resolveLocale(): string
    {
        return $this->invitee->locale ?? app()->getLocale();
    }
}
