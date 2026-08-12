<?php

namespace App\Support;

/**
 * Identité de la version en vigueur de l'accord de traitement des données.
 *
 * L'en-tête du document affichait « Dernière mise à jour : {{ date('d/m/Y') }} » :
 * la date changeait donc chaque jour, et deux clients consultant le même texte
 * à deux semaines d'intervalle lisaient deux dates différentes. Sur un document
 * contractuel, c'est la garantie de ne jamais pouvoir dire ce qui a été accepté.
 *
 * La version est désormais figée ici, et c'est elle qui est enregistrée au
 * moment de l'acceptation. Toute modification de fond du DPA doit s'accompagner
 * d'un incrément : c'est ce qui permettra, le jour venu, de distinguer les
 * comptes ayant accepté l'ancienne rédaction de ceux ayant accepté la nouvelle.
 */
final class DpaDocument
{
    public const VERSION = '1.1';

    /** Date d'entrée en vigueur de cette version (format ISO). */
    public const EFFECTIVE_DATE = '2026-08-12';

    public static function effectiveDate(): \Carbon\CarbonImmutable
    {
        return \Carbon\CarbonImmutable::parse(self::EFFECTIVE_DATE);
    }

    /** Libellé court, tel qu'affiché en en-tête et dans le profil. */
    public static function label(): string
    {
        return 'Version '.self::VERSION.' du '.self::effectiveDate()->format('d/m/Y');
    }
}
