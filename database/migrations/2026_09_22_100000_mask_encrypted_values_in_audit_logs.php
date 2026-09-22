<?php

use App\Security\AuditLogMasker;
use Illuminate\Database\Migrations\Migration;

/**
 * Rattrapage du journal d'audit (FEAT-123, étape 3 du plan de sécurité).
 *
 * Le journal recopiait en clair les attributs que la base chiffre : IBAN des
 * réglages, IBAN et données personnelles des salariés. Les nouvelles entrées
 * sont masquées à la source depuis le même commit ; celles qui existent déjà
 * sont reprises ici.
 *
 * Ne modifie que les colonnes JSON `old_values` et `new_values`, clé par clé,
 * et seulement les clés concernées. Rien d'autre n'est touché, aucune ligne
 * n'est supprimée. Rejouable sans effet : la routine est idempotente.
 *
 * Pas de `down()` : on ne remet pas en clair ce qu'on a masqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new AuditLogMasker)->run();
    }

    public function down(): void
    {
        // Volontairement vide.
    }
};
