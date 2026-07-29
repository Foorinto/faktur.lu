<?php

namespace App\Rules;

use App\Models\BusinessSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Interdit un taux de TVA non nul sur la TVA collectée quand l'entreprise
 * est en franchise de TVA : dans ce régime, aucune TVA ne peut être facturée.
 *
 * À n'appliquer qu'à la TVA collectée (factures, devis, catalogue produits).
 * NE PAS appliquer aux dépenses : la TVA payée aux fournisseurs existe
 * indépendamment du régime du vendeur.
 */
class SalesVatRateAllowed implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // En l'absence de paramètres d'entreprise (onboarding non terminé), on
        // ne bloque pas : mieux vaut laisser passer que verrouiller un compte.
        $settings = BusinessSettings::getInstance();
        if (! $settings?->isVatExempt()) {
            return;
        }

        if ((float) $value != 0.0) {
            $fail(__('app.validation_vat_rate_franchise'));
        }
    }
}
