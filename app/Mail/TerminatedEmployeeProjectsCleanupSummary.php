<?php

namespace App\Mail;

use App\Models\HR\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TerminatedEmployeeProjectsCleanupSummary extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public array $projects,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_terminated_employee_cleanup', [
                'name' => trim($this->employee->first_name.' '.$this->employee->last_name),
            ]),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->resolveLocale());

        return new Content(
            markdown: 'emails.terminated-employee-cleanup',
            with: [
                'employee' => $this->employee,
                'projects' => $this->projects,
            ],
        );
    }

    protected function resolveLocale(): string
    {
        return $this->employee->user?->locale ?? app()->getLocale();
    }
}
