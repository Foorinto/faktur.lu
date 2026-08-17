<?php

namespace App\Rules;

use App\Services\Accounting\PcnAccountService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Le compte saisi doit être un compte du plan comptable normalisé.
 *
 * Le contrôle est en DEUX TEMPS, et ce n'est plus faute de catalogue : les deux
 * classes utiles sont embarquées, les charges et les produits.
 *
 * La raison est que le PCN fixe des comptes NORMALISÉS, quand une comptabilité
 * réelle les subdivise. Le plan connaît « 7021 Ventes de produits finis » ; il
 * ne connaît pas 702000, qui est pourtant le compte de ventes par défaut de
 * faktur.lu, ni les sous-comptes qu'un comptable ouvre par client ou par
 * activité. Exiger l'appartenance à la liste ferait rejeter notre propre
 * paramétrage.
 *
 * En classe 6 la vérification reste stricte : elle porte sur les catégories de
 * dépenses, dont les comptes sont proposés par le sélecteur et n'ont jamais eu
 * à être subdivisés. Ailleurs, on s'en tient à la forme — c'est le sélecteur,
 * et non le validateur, qui guide vers le bon compte.
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

        // Hors classe 6, on s'en tient à la forme : l'absence d'un compte de la
        // liste ne prouve pas qu'il est faux, seulement qu'il est subdivisé.
        if (str_starts_with($compte, '6') && ! $catalogue->exists($compte)) {
            $fail('Le compte :input ne figure pas au plan comptable normalisé.');
        }
    }
}
