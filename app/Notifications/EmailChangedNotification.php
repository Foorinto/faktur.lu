<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée à l'ANCIENNE adresse quand l'e-mail d'un compte change.
 *
 * C'est le seul signal que reçoit le titulaire légitime si le changement ne
 * vient pas de lui (session volée). Le mot de passe courant est déjà exigé
 * pour opérer le changement ; cette notification est la détection en aval.
 */
class EmailChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $ancienEmail,
        public string $nouvelEmail,
        string $langue = 'fr',
    ) {
        // $locale est la propriété héritée de Notification : Laravel l'utilise
        // pour localiser le rendu du mail.
        $this->locale = $langue;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('marque.nom');

        return (new MailMessage)
            ->subject(__('app.email_changed.subject', ['app' => $app]))
            ->line(__('app.email_changed.intro'))
            ->line(__('app.email_changed.from', ['email' => $this->ancienEmail]))
            ->line(__('app.email_changed.to', ['email' => $this->nouvelEmail]))
            ->line(__('app.email_changed.warning', ['app' => $app]));
    }
}
