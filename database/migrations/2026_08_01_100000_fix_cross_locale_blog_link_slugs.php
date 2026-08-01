<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 85 liens internes du blog renvoient un 404.
 *
 * Motif systématique : les traductions DE, EN, LB et PT reprennent le slug
 * FRANÇAIS en changeant seulement le préfixe de langue. Ainsi
 * /de/blog/mentions-obligatoires-facture-luxembourg alors que la version
 * allemande est publiée sous /de/blog/pflichtangaben-rechnung-luxemburg.
 * Les slugs diffèrent par langue : seul le translation_key est commun.
 *
 * La résolution se fait ici depuis la base (slug -> translation_key ->
 * slug publié dans la langue cible), donc la migration s'adapte à l'état
 * réel de chaque environnement plutôt que d'embarquer une table figée.
 *
 * Deux cas ne se résolvent pas ainsi et sont traités par alias :
 *   - controle-fiscal-aed-luxembourg-2026-preparation : doublon archivé lors
 *     de la fusion, redirigé en 301 ; on pointe directement vers le survivant
 *     pour éviter un saut de redirection inutile.
 *   - livre-des-recettes-luxembourg-obligations-modele-conservation : slug qui
 *     n'a jamais existé (suffixe « -conservation » de trop).
 */
return new class extends Migration
{
    /** Slugs sans correspondance directe => translation_key à viser. */
    private const ALIASES = [
        'controle-fiscal-aed-luxembourg-2026-preparation' => 'controle-fiscal-luxembourg-comment-preparer',
        'livre-des-recettes-luxembourg-obligations-modele-conservation' => 'livre-des-recettes-luxembourg-obligations-modele',
    ];

    public function up(): void
    {
        // slug (toutes langues, tous statuts) => translation_key
        $slugToKey = DB::table('blog_posts')
            ->pluck('translation_key', 'slug')
            ->all();

        // translation_key => [locale => slug publié]
        $keyLocaleToSlug = [];
        $published = DB::table('blog_posts')
            ->whereNotNull('published_at')
            ->where('status', 'published')
            ->get(['translation_key', 'locale', 'slug']);

        foreach ($published as $row) {
            $keyLocaleToSlug[$row->translation_key][$row->locale] = $row->slug;
        }

        $rewritten = 0;
        $unresolved = [];

        foreach (DB::table('blog_posts')->get(['id', 'content']) as $post) {
            $content = preg_replace_callback(
                '~href="/([a-z]{2})/blog/([^"#?]+)"~',
                function (array $m) use ($slugToKey, $keyLocaleToSlug, &$unresolved) {
                    [$full, $locale, $slug] = $m;

                    $key = self::ALIASES[$slug] ?? ($slugToKey[$slug] ?? null);
                    $target = $key ? ($keyLocaleToSlug[$key][$locale] ?? null) : null;

                    if ($target === null) {
                        $unresolved["/{$locale}/blog/{$slug}"] = true;

                        return $full;
                    }

                    return "href=\"/{$locale}/blog/{$target}\"";
                },
                $post->content
            );

            if ($content !== $post->content) {
                $rewritten += 1;
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $content,
                    'updated_at' => now(),
                ]);
            }
        }

        echo "  {$rewritten} article(s) réécrit(s)\n";

        foreach (array_keys($unresolved) as $url) {
            echo "  NON RÉSOLU : {$url}\n";
        }
    }

    public function down(): void
    {
        // Restaurer des URL qui renvoient un 404 n'aurait pas de sens.
    }
};
