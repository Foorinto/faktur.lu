<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification
{
    public function __construct(
        public OrganizationInvitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $orgName = $this->invitation->organization->name;
        $ownerName = $this->invitation->organization->owner->businessSettings?->company_name
            ?? $this->invitation->organization->owner->name;

        return (new MailMessage)
            ->subject("Invitation à rejoindre {$orgName} - faktur.lu")
            ->greeting('Bonjour' . ($this->invitation->name ? ' ' . $this->invitation->name : '') . ',')
            ->line("**{$ownerName}** vous invite à rejoindre l'organisation **{$orgName}** sur faktur.lu.")
            ->line('En acceptant cette invitation, vous pourrez :')
            ->line('• Voir et gérer les projets de l\'équipe')
            ->line('• Suivre votre temps de travail')
            ->line('• Créer et gérer des tâches')
            ->action('Accepter l\'invitation', $this->invitation->getAcceptUrl())
            ->line('Cette invitation expire le ' . $this->invitation->expires_at->format('d/m/Y') . '.')
            ->line('Si vous ne connaissez pas ' . $ownerName . ', vous pouvez ignorer cet email.')
            ->salutation('L\'équipe faktur.lu');
    }
}
