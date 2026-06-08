<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte intégrale de l'article FR « Article 61 LIVA : numérotation séquentielle ».
 *
 * Audit factuel de juin 2026 — la base légale citée était fausse :
 *   - L'article 61 LIVA traite de la personne redevable de la TVA (autoliquidation interne).
 *   - L'obligation de numérotation séquentielle figure à l'article 63 LIVA, point 3°.
 *
 * Cette migration :
 *   1. Change le slug FR : article-61-liva-... → article-63-liva-...
 *   2. Met à jour titre, meta_title, meta_description, content
 *   3. Met à jour les liens internes pointant vers l'ancien slug
 *   4. Corrige aussi la mention « franchise art. 56 ter » → « art. 57bis »
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
    private const OLD_SLUG = 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire';
    private const NEW_SLUG = 'article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire';

    public function up(): void
    {
        $alreadyMigrated = DB::table('blog_posts')
            ->where('slug', self::NEW_SLUG)
            ->where('locale', 'fr')
            ->exists();

        if ($alreadyMigrated) {
            echo "  → article FR déjà migré (slug = " . self::NEW_SLUG . "), skip" . PHP_EOL;
            return;
        }

        $newContent = file_get_contents(
            database_path('seeders/content/article_rewrites/article-63-liva-numerotation.html')
        );

        if ($newContent === false) {
            throw new \RuntimeException('Fichier de réécriture article-63-liva-numerotation.html introuvable');
        }

        $updated = DB::table('blog_posts')
            ->where('slug', self::OLD_SLUG)
            ->where('locale', 'fr')
            ->update([
                'slug' => self::NEW_SLUG,
                'title' => 'Article 63 LIVA : la numérotation séquentielle des factures luxembourgeoises (point 3°)',
                'meta_title' => 'Article 63 LIVA : numérotation séquentielle des factures Luxembourg | faktur.lu',
                'meta_description' => 'L\'article 63 LIVA impose une numérotation unique, séquentielle et continue des factures luxembourgeoises. Règles, formats acceptés, sanctions (art. 77 LIVA), automatisation.',
                'content' => $newContent,
                'updated_at' => now(),
            ]);

        echo "  → {$updated} article FR mis à jour (slug renommé)" . PHP_EOL;

        // Mise à jour des liens internes
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
