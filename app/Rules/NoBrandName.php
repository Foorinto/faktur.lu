<?php

namespace App\Rules;

use App\Models\AbuseEvent;
use App\Models\User;
use App\Services\AbuseProtectionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse un nom d'entreprise qui imite une marque (FEAT-138).
 *
 * C'est ce nom qui figure sur le PDF et dans la signature des mails envoyés
 * aux clients : le vecteur principal des fausses factures « Vinted » de
 * septembre 2026. Refus franc, et le compte est signalé à l'administrateur.
 *
 * Un nom déjà enregistré repasse tel quel : un compte existant qui rouvre
 * ses réglages ne doit pas se retrouver bloqué par une règle arrivée après
 * lui. Seul un changement de nom est contrôlé.
 */
class NoBrandName implements ValidationRule
{
    public function __construct(
        private readonly ?string $current = null,
        private readonly ?User $user = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if ($this->current !== null && trim($value) === trim($this->current)) {
            return;
        }

        $protection = app(AbuseProtectionService::class);
        $marque = $protection->matchesBrand($value);

        if ($marque === null) {
            return;
        }

        if ($this->user) {
            $protection->flag($this->user, "company_name:{$marque}");
        }

        $protection->record(AbuseEvent::TYPE_COMPANY_NAME_REFUSED, $marque, $this->user);

        $fail(__('app.validation_brand_name'));
    }
}
