<?php

namespace App\Notifications;

use App\Models\Accountant;
use App\Models\AccountantDownload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountantDownloadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Accountant $accountant,
        public string $exportType,
        public string $period
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
        $exportTypeLabel = AccountantDownload::TYPES[$this->exportType] ?? $this->exportType;

        return (new MailMessage)
            ->subject('Téléchargement comptable - faktur.lu')
            ->greeting('Bonjour,')
            ->line("Votre comptable **{$this->accountant->display_name}** vient de télécharger un export.")
            ->line("**Type :** {$exportTypeLabel}")
            ->line("**Période :** {$this->period}")
            ->line("**Date :** " . now()->format('d/m/Y à H:i'))
            ->action('Gérer les accès comptables', route('settings.accountant'))
            ->line('Si vous n\'avez pas autorisé ce téléchargement, vous pouvez révoquer l\'accès de ce comptable.')
            ->salutation('L\'équipe faktur.lu');
    }
}
