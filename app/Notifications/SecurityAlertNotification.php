<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Le mail d'alerte de sécurité : ce qui a changé, quand, d'où, et le lien
 * « ce n'était pas moi » qui gèle le compte.
 *
 * Une seule classe pour tous les événements, avec les textes par type dans
 * les traductions : cinq mails qui se ressemblent presque finiraient par
 * diverger sur un détail que personne ne relit.
 */
class SecurityAlertNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, string>  $details
     */
    public function __construct(
        public string $event,
        public array $details,
        public string $lockUrl,
        string $langue = 'fr',
        public ?string $ip = null,
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

        $mail = (new MailMessage)
            ->subject(__("app.security_alert.subject.{$this->event}", ['app' => $app]))
            ->line(__("app.security_alert.intro.{$this->event}", ['app' => $app]));

        foreach ($this->details as $cle => $valeur) {
            $mail->line(__("app.security_alert.detail.{$cle}", ['value' => $valeur]));
        }

        $mail->line(__('app.security_alert.when', [
            'date' => now()->locale($this->locale)->isoFormat('LLLL'),
            'ip' => $this->ip ?? '?',
        ]));

        return $mail
            ->line(__('app.security_alert.if_you'))
            ->line(__('app.security_alert.if_not_you'))
            ->action(__('app.security_alert.button'), $this->lockUrl)
            ->line(__('app.security_alert.after_lock'));
    }
}
