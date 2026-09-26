<?php

namespace App\Rules;

use App\Services\AbuseProtectionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Refuse une adresse jetable à l'inscription (FEAT-138).
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

        if (app(AbuseProtectionService::class)->isDisposableEmail($value)) {
            Log::info('Inscription refusée : adresse jetable.', ['domaine' => Str::afterLast($value, '@')]);

            $fail(__('app.validation_disposable_email'));
        }
    }
}
