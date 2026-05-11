<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\DocumentNumberFormatter;

class OnboardingService
{
    /**
     * Retourne la progression de l'onboarding pour un utilisateur.
     */
    public function getChecklist(User $user): array
    {
        $business = BusinessSettings::where('user_id', $user->id)->first();
        $hasClient = Client::where('user_id', $user->id)->exists();
        $hasInvoice = Invoice::whereHas('client', fn($q) => $q->where('user_id', $user->id))->exists();
        $hasSentInvoice = Invoice::whereHas('client', fn($q) => $q->where('user_id', $user->id))
            ->whereNotNull('sent_at')
            ->exists();
        $hasIban = $business && !empty($business->iban);
        $hasLogo = $business && !empty($business->logo_path);

        $tasks = [
            [
                'key' => 'company',
                'label' => __('app.onboarding_checklist.tasks.company'),
                'completed' => $business && !empty($business->company_name),
                'route' => 'settings.business.edit',
                'hash' => 'company',
            ],
            [
                'key' => 'logo',
                'label' => __('app.onboarding_checklist.tasks.logo'),
                'completed' => $hasLogo,
                'route' => 'settings.business.edit',
                'hash' => 'logo',
            ],
            [
                'key' => 'client',
                'label' => __('app.onboarding_checklist.tasks.client'),
                'completed' => $hasClient,
                'route' => 'clients.create',
                'hash' => null,
            ],
            [
                'key' => 'invoice',
                'label' => __('app.onboarding_checklist.tasks.invoice'),
                'completed' => $hasInvoice,
                'route' => 'invoices.create',
                'hash' => null,
            ],
            [
                'key' => 'send_invoice',
                'label' => __('app.onboarding_checklist.tasks.send_invoice'),
                'completed' => $hasSentInvoice,
                'route' => 'invoices.index',
                'hash' => null,
            ],
            [
                'key' => 'bank',
                'label' => __('app.onboarding_checklist.tasks.bank'),
                'completed' => $hasIban,
                'route' => 'settings.business.edit',
                'hash' => 'bank',
            ],
            [
                'key' => 'numbering',
                'label' => __('app.onboarding_checklist.tasks.numbering'),
                // Considered done if the user either acknowledged it or actively customised
                // any numbering setting away from defaults.
                'completed' => $user->onboarding_numbering_acknowledged_at !== null
                    || $this->numberingHasBeenCustomised($business),
                'route' => 'settings.business.edit',
                'hash' => 'numbering',
                'style' => 'highlight', // red/orange visual treatment to make it stand out
            ],
        ];

        $completed = collect($tasks)->filter(fn($t) => $t['completed'])->count();
        $total = count($tasks);

        return [
            'tasks' => $tasks,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'is_complete' => $completed === $total,
        ];
    }

    /**
     * Did the user move any numbering field away from its default value? Used to
     * mark the "numbering" checklist task as complete without forcing an explicit
     * acknowledgement.
     */
    private function numberingHasBeenCustomised(?BusinessSettings $business): bool
    {
        if (! $business) {
            return false;
        }

        return $business->number_format !== DocumentNumberFormatter::DEFAULT_TEMPLATE
            || $business->invoice_prefix !== 'F'
            || $business->credit_note_prefix !== 'AV'
            || $business->quote_prefix !== 'DEV'
            || $business->invoice_starting_number !== null
            || $business->credit_note_starting_number !== null
            || $business->quote_starting_number !== null
            || $business->number_padding !== 3;
    }

    /**
     * Détermine si la checklist doit être affichée dans le dashboard.
     */
    public function shouldShowChecklist(User $user): bool
    {
        if ($user->onboarding_checklist_dismissed) {
            return false;
        }

        $checklist = $this->getChecklist($user);
        return !$checklist['is_complete'];
    }
}
