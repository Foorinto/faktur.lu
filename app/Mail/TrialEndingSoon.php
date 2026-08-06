<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingSoon extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public int $daysRemaining
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        return new Envelope(
            subject: __('app.mail_subject_trial_ending_soon', [
                'days' => $this->daysRemaining,
                'app' => 'faktur.lu',
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = $this->resolveLocale() === 'pt' ? 'emails.pt.trial-ending-soon' : 'emails.trial-ending-soon';

        return new Content(
            markdown: $template,
            with: [
                'user' => $this->user,
                'daysRemaining' => $this->daysRemaining,
                'subscriptionUrl' => route('subscription.index'),
                // FEAT-105 : ce que le plan Gratuit changerait pour CE compte.
                // Tableau vide si rien ne le gênerait, et le courriel n'affiche
                // alors rien plutôt que d'inventer une contrainte.
                'freePlanImpact' => app(\App\Services\PlanService::class)->freePlanImpact($this->user),
                // Prix et plafonds lus dans les plans plutôt qu'écrits dans les
                // traductions : ils y avaient dérivé sans que personne ne le
                // voie. Le courriel annonçait 4 € et 9 € au lieu de 5 € et 15 €,
                // et 10 clients au lieu de 100. Sous-vendre est déjà fâcheux ;
                // afficher un prix inférieur à celui du paiement l'est plus.
                'essentiel' => \App\Models\Plan::essentiel(),
                'pro' => \App\Models\Plan::pro(),
            ],
        );
    }

    /**
     * Resolve the locale of the user.
     */
    protected function resolveLocale(): string
    {
        return $this->user->locale ?? app()->getLocale();
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
