<?php

namespace App\Jobs;

use App\Mail\ReminderMail;
use App\Models\EmailSettings;
use App\Models\Invoice;
use App\Models\InvoiceEmail;
use App\Models\User;
use App\Services\AbuseProtectionService;
use App\Services\EmailProviderService;
use App\Services\PlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(EmailProviderService $emailProviderService, PlanService $plans): void
    {
        // Get all users with reminders enabled
        //
        // Un compte désactivé (fraude, FEAT-138) ne relance plus personne :
        // la désactivation coupe la connexion, elle doit aussi couper les
        // envois partis de nos serveurs en son nom.
        $users = User::whereHas('emailSettings', function ($query) {
            $query->where('reminders_enabled', true);
        })->where(fn ($query) => $query->where('is_active', true)->orWhereNull('is_active'))
            ->get();

        foreach ($users as $user) {
            // Les relances automatiques sont une fonctionnalité Pro : le réglage
            // « rappels activés » ne suffit pas, le plan doit la porter (écart
            // relevé le 2026-09-24 : le job servait tout le monde).
            if (! $plans->hasFeature($user, 'email_reminders')) {
                continue;
            }

            $this->processUserReminders($user, $emailProviderService);
        }
    }

    /**
     * Process reminders for a specific user.
     */
    protected function processUserReminders(User $user, EmailProviderService $emailProviderService): void
    {
        $settings = $user->emailSettings;
        if (!$settings || !$settings->reminders_enabled) {
            return;
        }

        $reminderLevels = $settings->reminder_levels ?? EmailSettings::defaultReminderLevels();

        // Get unpaid, overdue invoices that are not excluded from reminders
        $overdueInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT])
            ->where('type', Invoice::TYPE_INVOICE)
            ->where('exclude_from_reminders', false)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay())
            ->whereHas('client', function ($query) {
                $query->where('exclude_from_reminders', false);
            })
            ->with(['client', 'emails'])
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->processInvoiceReminder($invoice, $reminderLevels, $user, $emailProviderService);
        }
    }

    /**
     * Process reminder for a specific invoice.
     */
    protected function processInvoiceReminder(Invoice $invoice, array $reminderLevels, User $user, EmailProviderService $emailProviderService): void
    {
        // Nombre de jours depuis l'échéance (positif si en retard)
        $daysOverdue = $invoice->due_at->startOfDay()->diffInDays(now()->startOfDay(), false);

        // Determine which reminder level to send
        $levelToSend = null;
        foreach ([3, 2, 1] as $level) {
            $levelConfig = $reminderLevels[$level] ?? null;
            if (!$levelConfig || !$levelConfig['enabled']) {
                continue;
            }

            $daysAfterDue = $levelConfig['days_after_due'] ?? ($level * 7);

            if ($daysOverdue >= $daysAfterDue) {
                $levelToSend = $level;
                break;
            }
        }

        if (!$levelToSend) {
            return;
        }

        // Check if this reminder level was already sent
        $reminderType = match ($levelToSend) {
            1 => InvoiceEmail::TYPE_REMINDER_1,
            2 => InvoiceEmail::TYPE_REMINDER_2,
            3 => InvoiceEmail::TYPE_REMINDER_3,
            default => InvoiceEmail::TYPE_REMINDER_1,
        };

        $alreadySent = $invoice->emails()
            ->where('type', $reminderType)
            ->where('status', InvoiceEmail::STATUS_SENT)
            ->exists();

        if ($alreadySent) {
            return;
        }

        // Plafond d'envois de l'essai (FEAT-138) : une relance automatique est
        // un document parti de nos serveurs, comme un envoi à la main. La
        // relance n'est pas marquée envoyée : elle partira un autre jour.
        if (! app(AbuseProtectionService::class)->canSendDocumentEmail($user)) {
            Log::info("Relance de la facture {$invoice->id} reportée : plafond d'envois de l'essai atteint (compte #{$user->id}).");

            return;
        }

        // Get recipient email from buyer snapshot
        $recipientEmail = $invoice->buyer_snapshot['email'] ?? $invoice->client?->email;
        if (!$recipientEmail) {
            Log::warning("Cannot send reminder for invoice {$invoice->id}: no recipient email");
            return;
        }

        // Prepare the message using the template
        $levelConfig = $reminderLevels[$levelToSend];
        $subject = $this->replaceVariables($levelConfig['subject'] ?? '', $invoice, $daysOverdue, $user);
        $message = $this->replaceVariables($levelConfig['body'] ?? '', $invoice, $daysOverdue, $user);

        try {
            // Get the mailer for this user (uses their configured provider)
            $mailer = $emailProviderService->getMailerForUser($user);

            $mail = new ReminderMail($invoice, $levelToSend, $message);
            $mail->subject($subject);

            $mailer->to($recipientEmail)->send($mail);

            // Record the email
            $invoice->emails()->create([
                'type' => $reminderType,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $message,
                'status' => InvoiceEmail::STATUS_SENT,
                'sent_at' => now(),
            ]);

            Log::info("Sent reminder level {$levelToSend} for invoice {$invoice->number} to {$recipientEmail}");
        } catch (\Exception $e) {
            $invoice->emails()->create([
                'type' => $reminderType,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $message,
                'status' => InvoiceEmail::STATUS_FAILED,
                'sent_at' => now(),
            ]);

            Log::error("Failed to send reminder for invoice {$invoice->id}: {$e->getMessage()}");
        }
    }

    /**
     * Replace template variables with actual values.
     */
    protected function replaceVariables(string $template, Invoice $invoice, int $daysOverdue, User $user): string
    {
        $seller = $invoice->seller_snapshot ?? [];
        $buyer = $invoice->buyer_snapshot ?? [];

        $replacements = [
            '{numero}' => $invoice->number,
            '{client_nom}' => $buyer['name'] ?? 'Client',
            '{montant}' => number_format($invoice->total_ttc, 2, ',', ' ') . ' ' . $invoice->currency,
            '{date_echeance}' => $invoice->due_at?->format('d/m/Y'),
            '{jours_retard}' => (string) $daysOverdue,
            '{entreprise}' => $seller['company_name'] ?? $user->name,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
