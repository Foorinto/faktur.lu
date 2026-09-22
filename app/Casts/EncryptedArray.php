<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Un tableau chiffré au repos, qui ne casse jamais la page qui le lit.
 *
 * Même filet que EncryptedText, pour ce que Laravel cast en `encrypted:array` :
 * une valeur vide vaut null, un JSON en clair d'avant le chiffrement se lit,
 * un chiffré illisible vaut null. L'écriture chiffre toujours le JSON.
 */
class EncryptedArray implements CastsAttributes, EncryptsAttribute
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            $clair = Crypt::decryptString($value);
        } catch (DecryptException) {
            $clair = (string) $value;
        }

        $tableau = json_decode($clair, true);

        return is_array($tableau) ? $tableau : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString(json_encode($value));
    }
}
