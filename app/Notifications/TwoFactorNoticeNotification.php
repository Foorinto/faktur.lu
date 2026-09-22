<?php

namespace App\Notifications;

use App\Auth\TrustedDevices;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Les trois mails du passage au second facteur d'un compte exposé : le
 * préavis, le rappel deux jours avant, puis l'annonce que le code par e-mail
 * est actif (voir App\Security\TwoFactorPolicy).
 */
class TwoFactorNoticeNotification extends Notification
{
    public const DEADLINE = 'deadline';

    public const REMINDER = 'reminder';

    public const ENABLED = 'enabled';

    public function __construct(public string $event, public ?Carbon $deadline, string $langue = 'fr')
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
        $date = $this->deadline?->copy()->locale($this->locale)->isoFormat('LL') ?? '';
        $params = ['app' => $app, 'date' => $date, 'days' => TrustedDevices::DAYS];

        return (new MailMessage)
            ->subject(__("app.two_factor_notice.subject.{$this->event}", $params))
            ->line(__("app.two_factor_notice.intro.{$this->event}", $params))
            ->line(__("app.two_factor_notice.what.{$this->event}", $params))
            ->line(__('app.two_factor_notice.alternative', $params))
            ->action(__("app.two_factor_notice.button.{$this->event}"), route('profile.edit').'#two-factor')
            ->line(__("app.two_factor_notice.outro.{$this->event}", $params));
    }
}
