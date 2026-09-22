<?php

namespace App\Casts;

/**
 * Un IBAN chiffré au repos (voir EncryptedText).
 *
 * Une valeur d'avant le chiffrement n'est rendue que si elle se lit comme un
 * IBAN, et remise au format sans espaces, en majuscules.
 */
class EncryptedIban extends EncryptedText
{
    protected function fromLegacy(string $value): ?string
    {
        return self::looksLikeIban($value) ? self::normalize($value) : null;
    }

    public static function looksLikeIban(?string $value): bool
    {
        return (bool) preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{10,30}$/', self::normalize($value));
    }

    public static function normalize(?string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $value));
    }
}
