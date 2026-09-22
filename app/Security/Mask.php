<?php

namespace App\Security;

/**
 * Masquer ce qui ne doit pas traverser en clair : mails, journal d'audit.
 *
 * Un IBAN masqué garde son début et sa fin, « LU28 **** **** **** 0000 » :
 * assez pour reconnaître son compte, pas assez pour le recopier. Les autres
 * données chiffrées en base (adresse, téléphone, nationalité d'un salarié)
 * n'ont pas d'équivalent lisible : on retient qu'elles ont changé, pas ce
 * qu'elles valaient.
 */
class Mask
{
    public const HIDDEN = '***';

    /** Les attributs qui se lisent comme un IBAN, quel que soit le modèle. */
    public const IBAN_KEYS = ['iban', 'bank_iban'];

    public static function iban(?string $iban): string
    {
        $propre = strtoupper((string) preg_replace('/\s+/', '', (string) $iban));

        if ($propre === '') {
            return '';
        }

        // Déjà masqué : on ne masque pas deux fois, sinon la fin disparaît.
        if (str_contains($propre, '*')) {
            return trim(chunk_split($propre, 4, ' '));
        }

        if (strlen($propre) < 8) {
            return str_repeat('*', strlen($propre));
        }

        $masque = substr($propre, 0, 4).str_repeat('*', strlen($propre) - 8).substr($propre, -4);

        return trim(chunk_split($masque, 4, ' '));
    }

    /**
     * La valeur masquée d'un attribut, selon son nom.
     */
    public static function value(string $key, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (in_array($key, self::IBAN_KEYS, true)) {
            return self::iban((string) $value);
        }

        return self::HIDDEN;
    }
}
