<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Un tableau JSON qui tolère un encodage en double.
 *
 * Certaines lignes anciennes (données de démonstration, imports) portent un
 * JSON encodé deux fois : le cast `array` de Laravel rend alors une chaîne,
 * et tout ce qui attend un tableau explose (le rendu PDF, donc l'archivage
 * nocturne, facture par facture, chaque nuit). Ici, une chaîne qui contient
 * du JSON est décodée jusqu'à obtenir un tableau. L'écriture reste celle de
 * Laravel : un encodage, un seul.
 */
class JsonArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        for ($i = 0; $i < 3 && is_string($value); $i++) {
            $decode = json_decode($value, true);

            if ($decode === null && trim($value) !== 'null') {
                return null;
            }

            $value = $decode;
        }

        return is_array($value) ? $value : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Une chaîne JSON reçue telle quelle n'est pas ré-encodée.
        if (is_string($value)) {
            return json_decode($value, true) !== null ? $value : json_encode($value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
