<?php

namespace App\Mail;

use App\Models\HR\Employee;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectMemberRemoved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public Employee $employee,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_project_member_removed', ['project' => $this->project->title]),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->resolveLocale());

        return new Content(
            markdown: 'emails.project-member-removed',
            with: [
                'project' => $this->project,
                'employee' => $this->employee,
                'recipientName' => trim($this->employee->first_name.' '.$this->employee->last_name),
            ],
        );
    }

    protected function resolveLocale(): string
    {
        return $this->employee->account?->locale ?? app()->getLocale();
    }
}
