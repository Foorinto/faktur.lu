<?php

use App\Security\EncryptedValueRepair;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Remettre au propre les valeurs chiffrées illisibles (correctif du 2026-09-22).
 *
 * Les migrations de chiffrement de juillet (IBAN) et d'août (données des
 * salariés) avaient laissé les valeurs vides telles quelles, et le cast
 * `encrypted` lève une exception dessus : pour ces comptes, le tableau de
 * bord, les réglages ou la fiche du salarié répondaient une erreur 500.
 *
 * Une valeur vide est chiffrée vide, une valeur en clair est chiffrée, le
 * reste n'est pas touché. Idempotent, sans retour en arrière : rien n'est
 * perdu, le vide reste vide et le clair devient lisible par l'application.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bilan = (new EncryptedValueRepair)->run();

        Log::info('Valeurs chiffrées illisibles remises au propre', $bilan);
    }

    public function down(): void
    {
        // Volontairement vide.
    }
};
