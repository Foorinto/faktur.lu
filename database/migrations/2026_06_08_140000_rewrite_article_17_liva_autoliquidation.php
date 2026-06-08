<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte intégrale de l'article FR « Article 21 LIVA : autoliquidation TVA B2B intra-UE ».
 *
 * Audit factuel de juin 2026 — la base légale citée était fausse :
 *   - Article 21 LIVA = fait générateur / exigibilité de la TVA (PAS l'autoliquidation)
 *   - L'autoliquidation B2B intra-UE est régie par l'article 17 LIVA
 *     (lieu d'imposition, transposition art. 44 directive 2006/112/CE)
 *     et par l'article 196 de la directive 2006/112/CE (désigne le preneur comme redevable).
 *
 * Cette migration :
 *   1. Change le slug FR : article-21-liva-... → article-17-liva-...
 *   2. Met à jour le titre, le meta_title, le meta_description, le content
 *   3. Met à jour l'unique lien interne pointant vers l'ancien slug
 *      (dans l'article tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques)
 *
 * Le redirect 301 pour l'ancien slug est défini dans routes/web.php.
 *
 * Les 4 autres versions linguistiques (DE/EN/LB/PT) conservent leur slug
 * pour le moment — elles seront refondues dans un batch ultérieur.
 *
 * Idempotent : aucune action si le nouveau slug existe déjà.
 */
return new class extends Migration
{
    private const OLD_SLUG = 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg';
    private const NEW_SLUG = 'article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg';

    public function up(): void
    {
        // Si déjà migré, ne rien faire
        $alreadyMigrated = DB::table('blog_posts')
            ->where('slug', self::NEW_SLUG)
            ->where('locale', 'fr')
            ->exists();

        if ($alreadyMigrated) {
            echo "  → article FR déjà migré (slug = " . self::NEW_SLUG . "), skip" . PHP_EOL;
            return;
        }

        $newContent = file_get_contents(
            database_path('seeders/content/article_rewrites/article-17-liva-autoliquidation.html')
        );

        if ($newContent === false) {
            throw new \RuntimeException('Fichier de réécriture article-17-liva-autoliquidation.html introuvable');
        }

        // 1. Mise à jour de l'article FR
        $updated = DB::table('blog_posts')
            ->where('slug', self::OLD_SLUG)
            ->where('locale', 'fr')
            ->update([
                'slug' => self::NEW_SLUG,
                'title' => 'Article 17 LIVA : autoliquidation TVA B2B intra-UE pour les freelances luxembourgeois',
                'meta_title' => 'Article 17 LIVA : autoliquidation TVA B2B intra-UE | faktur.lu',
                'meta_description' => 'Autoliquidation TVA pour les freelances luxembourgeois facturant en B2B intra-UE : base légale (art. 17 LIVA + art. 196 directive), mentions obligatoires, validation VIES, exemples concrets.',
                'content' => $newContent,
                'updated_at' => now(),
            ]);

        echo "  → {$updated} article FR mis à jour (slug renommé)" . PHP_EOL;

        // 2. Mise à jour des liens internes pointant vers l'ancien slug
        $linkedPosts = DB::table('blog_posts')
            ->where('content', 'like', '%' . self::OLD_SLUG . '%')
            ->get(['id', 'content']);

        $relinkedCount = 0;
        foreach ($linkedPosts as $post) {
            $newPostContent = str_replace(self::OLD_SLUG, self::NEW_SLUG, $post->content);
            if ($newPostContent !== $post->content) {
                DB::table('blog_posts')->where('id', $post->id)->update(['content' => $newPostContent]);
                $relinkedCount++;
            }
        }

        echo "  → {$relinkedCount} article(s) avec lien interne corrigé" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — la base légale corrigée est conforme à la LIVA.
    }
};
