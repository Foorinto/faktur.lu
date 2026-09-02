<?php

namespace App\Support;

use Illuminate\Translation\Translator;

/**
 * Traducteur qui connaît le nom de la marque.
 *
 * Les textes du site citent le nom près de mille fois. Ils portent désormais le
 * marqueur `:app`, rempli ici automatiquement : aucun site d'appel n'a à passer
 * le paramètre, donc aucun ne peut l'oublier.
 *
 * ⚠️ C'est la raison d'être de l'injection globale. Paramétrer mille phrases en
 * comptant sur chaque appel pour fournir la valeur, c'était accepter qu'un
 * oubli affiche « :app » en clair à un client. Ici il n'y a rien à fournir.
 *
 * Le paramètre explicitement passé l'emporte : un appel qui veut un autre nom
 * que celui de la configuration reste libre de le dire.
 */
class TraducteurAvecMarque extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        return parent::get(
            $key,
            array_merge(['app' => config('marque.nom')], $replace),
            $locale,
            $fallback
        );
    }
}
