<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

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
                'label' => 'Configurer votre entreprise',
                'completed' => $business && !empty($business->company_name),
                'route' => 'settings.business.edit',
                'hash' => 'company',
            ],
            [
                'key' => 'logo',
                'label' => 'Ajouter votre logo',
                'completed' => $hasLogo,
                'route' => 'settings.business.edit',
                'hash' => 'logo',
            ],
            [
                'key' => 'client',
                'label' => 'Créer votre premier client',
                'completed' => $hasClient,
                'route' => 'clients.create',
                'hash' => null,
            ],
            [
                'key' => 'invoice',
                'label' => 'Créer votre première facture',
                'completed' => $hasInvoice,
                'route' => 'invoices.create',
                'hash' => null,
            ],
            [
                'key' => 'send_invoice',
                'label' => 'Envoyer votre première facture',
                'completed' => $hasSentInvoice,
                'route' => 'invoices.index',
                'hash' => null,
            ],
            [
                'key' => 'bank',
                'label' => 'Configurer votre compte bancaire',
                'completed' => $hasIban,
                'route' => 'settings.business.edit',
                'hash' => 'bank',
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
