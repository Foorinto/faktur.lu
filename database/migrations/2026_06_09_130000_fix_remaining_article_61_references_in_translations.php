<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige les références résiduelles à « Article 61 LIVA » dans le CORPS
 * d'autres articles (AED-2026 et FAIA-2026) qui parlent de numérotation
 * séquentielle et citaient la mauvaise base légale.
 *
 * Les articles dédiés (article-61 → article-63) ont déjà été renommés.
 * Cette migration corrige uniquement les RÉFÉRENCES dans le contenu
 * d'autres articles.
 *
 * Idempotent.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        // Body content references — universal across DE/EN/LB/PT for "61 LIVA" → "63 LIVA"
        // (capitalisations majuscule + minuscule)
        'Article 61 LIVA' => 'Article 63 LIVA',
        'Artikel 61 LIVA' => 'Artikel 63 LIVA',
        'Artigo 61 LIVA' => 'Artigo 63 LIVA',
        'article 61 LIVA' => 'article 63 LIVA',
        'artikel 61 LIVA' => 'artikel 63 LIVA',
        'artigo 61 LIVA' => 'artigo 63 LIVA',
        'Article 21 LIVA' => 'Article 17 LIVA',
        'Artikel 21 LIVA' => 'Artikel 17 LIVA',
        'Artigo 21 LIVA' => 'Artigo 17 LIVA',
        'article 21 LIVA' => 'article 17 LIVA',
        'artikel 21 LIVA' => 'artikel 17 LIVA',
        'artigo 21 LIVA' => 'artigo 17 LIVA',
    ];

    public function up(): void
    {
        $totalUpdated = 0;
        $byLocale = [];

        foreach (['de', 'en', 'lb', 'pt'] as $locale) {
            $byLocale[$locale] = 0;
            $posts = DB::table('blog_posts')
                ->where('locale', $locale)
                ->get(['id', 'slug', 'content']);

            foreach ($posts as $post) {
                $newContent = str_replace(
                    array_keys(self::REPLACEMENTS),
                    array_values(self::REPLACEMENTS),
                    $post->content
                );

                if ($newContent !== $post->content) {
                    DB::table('blog_posts')->where('id', $post->id)->update([
                        'content' => $newContent,
                        'updated_at' => now(),
                    ]);
                    $byLocale[$locale]++;
                    $totalUpdated++;
                }
            }
        }

        foreach ($byLocale as $loc => $n) {
            echo "  → [$loc] {$n} article(s) corrigé(s)" . PHP_EOL;
        }
        echo "  → Total : {$totalUpdated} article(s) modifié(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback.
    }
};
