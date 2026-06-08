<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte intégrale de l'article FR « TVA Luxembourg 2026 : les 4 taux expliqués ».
 *
 * Audit factuel de juin 2026 — 4 erreurs critiques corrigées :
 *   - Hôtellerie classée à 8 % ou 17 % → 3 % (tous types, sans distinction étoiles)
 *   - Restauration classée à 8 % → 3 % (nourriture), 17 % uniquement pour alcools
 *   - Mention « franchise art. 56 ter » → art. 57bis LIVA
 *   - Sanctions « 10 % à 50 % » (barème non officiel) → 250 € à 10 000 € (art. 77 LIVA)
 *
 * Bonus :
 *   - Mention autoliquidation : art. 17 LIVA + art. 196 directive (et non art. 21)
 *   - Sources officielles inline (pfi.public.lu, legilux)
 *   - Mazout chauffage explicitement à 8 % (et non 14 %)
 */
return new class extends Migration
{
    public function up(): void
    {
        $newContent = file_get_contents(
            database_path('seeders/content/article_rewrites/tva-luxembourg-quatre-taux.html')
        );

        if ($newContent === false) {
            throw new \RuntimeException('Fichier de réécriture tva-luxembourg-quatre-taux.html introuvable');
        }

        $updated = DB::table('blog_posts')
            ->where('slug', 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques')
            ->where('locale', 'fr')
            ->update([
                'content' => $newContent,
                'meta_description' => 'Les 4 taux de TVA au Luxembourg en 2026 : 17 % standard (le plus bas de l\'UE), 14 % intermédiaire, 8 % réduit, 3 % super-réduit (incl. hôtellerie et restauration). Guide complet avec sources officielles.',
                'updated_at' => now(),
            ]);

        echo "  → {$updated} article FR mis à jour" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les taux corrigés sont conformes à la doctrine AED 2026.
    }
};
