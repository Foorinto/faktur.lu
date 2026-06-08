<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte intégrale de l'article FR « Franchise TVA au Luxembourg ».
 *
 * Audit factuel de juin 2026 — 5 erreurs corrigées :
 *   - Seuil 35 000 € → 50 000 € (depuis le 1er janvier 2025, art. 57bis LIVA)
 *   - Tolérance 10 % (55 000 €) ajoutée
 *   - "12 mois glissants" → année civile
 *   - Procédure sortie de franchise (immédiate si >55 k€, fin d'année civile sinon)
 *   - Sources officielles inline (legilux, pfi.public.lu, eur-lex)
 *
 * Le nouveau contenu HTML vit dans database/seeders/content/article_rewrites/
 * pour rester versionné avec le repo et accessible aux deploys.
 *
 * Idempotent : compare le hash du nouveau contenu, ne réécrit que si différent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $newContent = file_get_contents(
            database_path('seeders/content/article_rewrites/franchise-tva-luxembourg.html')
        );

        if ($newContent === false) {
            throw new \RuntimeException('Fichier de réécriture franchise-tva-luxembourg.html introuvable');
        }

        $updated = DB::table('blog_posts')
            ->where('slug', 'franchise-tva-luxembourg-seuil-obligations-regime-normal')
            ->where('locale', 'fr')
            ->update([
                'content' => $newContent,
                'meta_description' => 'Franchise TVA Luxembourg en 2026 : seuil 50 000 € HT (depuis 2025), tolérance 10 %, mention obligatoire article 57bis, procédure de bascule au régime normal.',
                'updated_at' => now(),
            ]);

        echo "  → {$updated} article FR mis à jour" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — le nouveau contenu est plus juste que l'ancien.
    }
};
