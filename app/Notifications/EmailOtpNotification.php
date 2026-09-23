<?php

namespace App\Notifications;

use App\Auth\EmailOtp;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le code de vérification envoyé par e-mail. Pas de file d'attente : la
 * personne attend devant l'écran de connexion.
 */
class EmailOtpNotification extends Notification
{
    public function __construct(public string $code, string $langue = 'fr')
    {
        $this->locale = $langue;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('marque.nom');

        return (new MailMessage)
            ->subject(__('app.email_otp.mail.subject', ['app' => $app, 'code' => $this->code]))
            ->line(__('app.email_otp.mail.intro', ['app' => $app]))
            ->line('# '.implode(' ', str_split($this->code, 3)))
            ->line(__('app.email_otp.mail.validity', ['minutes' => EmailOtp::VALIDITY_MINUTES]))
            ->line(__('app.email_otp.mail.ignore'));
    }
}
