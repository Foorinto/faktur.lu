<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Un texte chiffré au repos, qui ne casse jamais la page qui le lit.
 *
 * ⚠️ Le cast `encrypted` de Laravel lève une exception sur toute valeur qu'il
 * ne sait pas déchiffrer. Or la base porte encore des lignes d'avant le
 * chiffrement (les IBAN en juillet 2026, les données des salariés en août) :
 * des valeurs vides, laissées telles quelles par les migrations, et peut-être
 * des valeurs en clair. Pour ces lignes, toute page qui les lit répondait une
 * erreur 500, constaté en production le 2026-09-22 par un « The payload is
 * invalid » sur le tableau de bord.
 *
 * Ici, une valeur vide vaut null, une valeur en clair d'avant le chiffrement
 * est rendue telle quelle, et seul un chiffré illisible est rendu nul (la
 * clé, elle, est surveillée par EncryptionKeyGuard). L'écriture chiffre
 * toujours, le vide compris : `business_settings.iban` refuse le NULL, et
 * c'est la chaîne vide brute qui cassait la lecture. La migration du
 * 2026-09-22 remet les lignes anciennes au propre ; ce cast est le filet si
 * elle en manquait une.
 */
class EncryptedText implements CastsAttributes, EncryptsAttribute
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            $clair = Crypt::decryptString($value);
        } catch (DecryptException) {
            return $this->fromLegacy((string) $value);
        }

        return trim($clair) === '' ? null : $clair;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString((string) $value);
    }

    /**
     * Une valeur que la clé ne déchiffre pas : d'avant le chiffrement, donc
     * lisible telle quelle, sauf si elle a la forme d'un chiffré.
     */
    protected function fromLegacy(string $value): ?string
    {
        return self::looksEncrypted($value) ? null : $value;
    }

    /** La forme d'un chiffré Laravel : du JSON en base64, qui commence par « eyJ ». */
    public static function looksEncrypted(string $value): bool
    {
        return str_starts_with($value, 'eyJ');
    }
}
