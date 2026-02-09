<?php

namespace App\Notifications;

use App\Models\AccountantInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountantInvitationNotification extends Notification
{

    public function __construct(
        public AccountantInvitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->invitation->user->businessSettings?->company_name
            ?? $this->invitation->user->name;

        return (new MailMessage)
            ->subject('Invitation à accéder aux exports comptables - faktur.lu')
            ->greeting('Bonjour' . ($this->invitation->name ? ' ' . $this->invitation->name : '') . ',')
            ->line("**{$userName}** vous invite à accéder à ses exports comptables sur faktur.lu.")
            ->line('En acceptant cette invitation, vous pourrez télécharger :')
            ->line('• Les exports FAIA (XML) pour l\'AED')
            ->line('• Les exports Excel des factures')
            ->line('• Les archives PDF des factures')
            ->action('Accepter l\'invitation', $this->invitation->getAcceptUrl())
            ->line('Cette invitation expire le ' . $this->invitation->expires_at->format('d/m/Y') . '.')
            ->line('Si vous n\'êtes pas le comptable de ' . $userName . ', vous pouvez ignorer cet email.')
            ->salutation('L\'équipe faktur.lu');
    }
}
