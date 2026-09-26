<?php

namespace App\Rules;

use App\Models\AbuseEvent;
use App\Services\AbuseProtectionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Refuse une adresse jetable, ou d'un domaine réservé, à l'inscription
 * (FEAT-138).
 *
 * Les comptes frauduleux de septembre 2026 venaient tous d'adresses
 * temporaires : un compte qui ne peut pas recevoir de mail dans trois jours
 * n'a pas d'usage honnête d'un logiciel de facturation. Le message reste
 * neutre, sans nommer la liste ni le domaine.
 */
class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $protection = app(AbuseProtectionService::class);
        $domaine = Str::lower(Str::afterLast($value, '@'));

        // Domaine réservé (.test, example.com...) : aucun mail ne peut y
        // arriver, le compte ne validerait jamais son adresse.
        if ($protection->isReservedDomain($value)) {
            Log::info('Inscription refusée : domaine réservé.', ['domaine' => $domaine]);
            $protection->record(AbuseEvent::TYPE_RESERVED_DOMAIN, $domaine);

            $fail(__('app.validation_undeliverable_email'));

            return;
        }

        if ($protection->isDisposableEmail($value)) {
            Log::info('Inscription refusée : adresse jetable.', ['domaine' => $domaine]);
            $protection->record(AbuseEvent::TYPE_DISPOSABLE_EMAIL, $domaine);

            $fail(__('app.validation_disposable_email'));
        }
    }
}
