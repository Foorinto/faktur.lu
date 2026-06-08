<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige 2 noms de domaines accentués qui ne résolvent pas (DNS),
 * introduits par les migrations d'accentuation FR sur 2 articles :
 *
 *   - https://bpifrance-création.fr/          → https://bpifrance-creation.fr/
 *   - https://économie.fgov.be/...            → https://economie.fgov.be/...
 *
 * Les accents dans un *path* d'URL peuvent fonctionner (UTF-8 encodé),
 * mais les accents dans un *nom de domaine* nécessitent l'encodage
 * punycode (xn--...) sinon le DNS échoue avec NXDOMAIN.
 *
 * Audit complet effectué sur les 25 URLs externes des 145 articles :
 * ces 2 cas sont les seuls problèmes restants après la migration
 * 2026_06_08_100000.
 *
 * Idempotent : simple str_replace, aucun effet si déjà corrigé.
 */
return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'https://bpifrance-création.fr/' => 'https://bpifrance-creation.fr/',
            'https://économie.fgov.be/fr/themes/entreprises/créer-une-entreprise/demarches-pour-un-travailleur'
                => 'https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur',
        ];

        $posts = DB::table('blog_posts')
            ->where(function ($q) use ($replacements) {
                foreach (array_keys($replacements) as $bad) {
                    $q->orWhere('content', 'like', '%' . $bad . '%');
                }
            })
            ->get(['id', 'content']);

        $fixed = 0;
        foreach ($posts as $post) {
            $newContent = str_replace(array_keys($replacements), array_values($replacements), $post->content);
            if ($newContent !== $post->content) {
                DB::table('blog_posts')->where('id', $post->id)->update(['content' => $newContent]);
                $fixed++;
            }
        }

        echo "  → {$fixed} article(s) corrigé(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les URLs réparées valent mieux que les cassées.
    }
};
