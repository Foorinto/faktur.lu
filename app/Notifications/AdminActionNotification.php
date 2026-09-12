<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient un utilisateur qu'un administrateur de la plateforme a agi sur son
 * compte (réinitialisation de mot de passe ou de 2FA).
 *
 * Détection : l'utilisateur doit savoir qu'une action sensible a eu lieu sur
 * son compte, même légitime, et pouvoir réagir si elle ne l'est pas.
 */
class AdminActionNotification extends Notification
{
    use Queueable;

    /** @param string $type 'password_reset' | '2fa_reset' */
    public function __construct(
        public string $type,
        string $langue = 'fr',
    ) {
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

        return (new MailMessage())
            ->subject(__("app.admin_action.{$this->type}.subject", ['app' => $app]))
            ->line(__("app.admin_action.{$this->type}.line"))
            ->line(__('app.admin_action.warning', ['app' => $app]));
    }
}
