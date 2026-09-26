<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use App\Services\AbuseProtectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte d'administration : un compte signalé vient d'envoyer son premier
 * document depuis nos serveurs (FEAT-138). C'est le moment où une
 * usurpation de marque devient un dommage pour un tiers. Jamais envoyée au
 * compte lui-même.
 */
class FlaggedAccountFirstEmailNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Invoice $invoice,
        public string $recipient,
    ) {}

    public function envelope(): Envelope
    {
        // Notification d'administration : langue par défaut de l'application.
        return new Envelope(
            subject: __('app.mail_subject_flagged_first_email', ['name' => $this->user->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.flagged-first-email',
            with: [
                'user' => $this->user,
                'invoice' => $this->invoice,
                'recipient' => $this->recipient,
                'companyName' => $this->user->businessSettings?->company_name,
                'reason' => AbuseProtectionService::describeReason($this->user->flagged_reason),
                'sentToday' => app(AbuseProtectionService::class)->documentEmailsSentSince($this->user, now()->startOfDay()),
                'adminUrl' => config('app.url').'/'.config('admin.url_prefix', 'admin').'/users/'.$this->user->id,
            ],
        );
    }
}
