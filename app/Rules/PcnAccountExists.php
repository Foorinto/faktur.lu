<?php

namespace App\Rules;

use App\Services\Accounting\PcnAccountService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Le compte saisi doit être un compte du plan comptable normalisé.
 *
 * Le contrôle est en DEUX TEMPS, et la raison tient au catalogue : celui-ci ne
 * couvre que la classe 6, les charges, parce qu'il a été généré pour les
 * catégories d'achat (`resources/data/pcn-class6.json`, cf. `pcn:build`). Les
 * comptes de produits — la classe 7, celle qui nous intéresse pour un article —
 * n'y figurent pas.
 *
 * On vérifie donc l'existence quand on peut, la forme sinon. Refuser un compte
 * de produits au motif qu'il manque à un catalogue de charges reviendrait à
 * interdire la fonctionnalité qu'on vient d'écrire.
 *
 * ⚠️ Le jour où `pcn:build --class=7` aura été lancé, ce repli devient inutile :
 * la vérification d'existence couvrira les deux classes.
 */
class PcnAccountExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $compte = (string) $value;

        // Un compte du plan est une suite de chiffres, de la racine à deux ou
        // trois niveaux de détail. Tout le reste est une coquille.
        if (! preg_match('/^\d{3,8}$/', $compte)) {
            $fail('Le compte :input doit être un numéro du plan comptable, de 3 à 8 chiffres.');

            return;
        }

        $catalogue = app(PcnAccountService::class);

        // Le catalogue ne connaît que les charges : hors classe 6, on s'en tient
        // à la forme plutôt que d'opposer une absence qui ne prouve rien.
        if (str_starts_with($compte, '6') && ! $catalogue->exists($compte)) {
            $fail('Le compte :input ne figure pas au plan comptable normalisé.');
        }
    }
}
