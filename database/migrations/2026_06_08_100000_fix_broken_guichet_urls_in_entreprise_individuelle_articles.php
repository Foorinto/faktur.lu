<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige 2 liens externes 404 vers guichet.public.lu dans les articles
 * "creer-entreprise-individuelle-luxembourg-guide-2026" (5 langues).
 *
 * 1. La page "entreprise-individuelle (indépendant)" a été restructurée :
 *    avant : .../forme-juridique/entreprise-individuelle_societe-personnes/entreprise-individuelle.html (404)
 *    après : .../forme-juridique/entreprise-individuelle-societe-personnes.html (200)
 *
 * 2. La version FR contenait en plus des accents UTF-8 dans le path
 *    (création-developpement, société-personnes) — guichet.public.lu n'accepte que de l'ASCII.
 *
 * Idempotent : un simple str_replace, sans effet si les URLs ont déjà été corrigées.
 */
return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            // URL 1 (entreprise-individuelle) — toutes les variantes accent/non-accent vers la page restructurée
            'https://guichet.public.lu/fr/entreprises/création-developpement/forme-juridique/entreprise-individuelle_société-personnes/entreprise-individuelle.html'
                => 'https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html',
            'https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle_societe-personnes/entreprise-individuelle.html'
                => 'https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html',

            // URL 2 (autorisation-etablissement) — uniquement la variante FR avec accents
            'https://guichet.public.lu/fr/entreprises/création-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html'
                => 'https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html',
        ];

        $posts = DB::table('blog_posts')
            ->whereIn('slug', [
                'creer-entreprise-individuelle-luxembourg-guide-2026',
                'sole-proprietorship-luxembourg-guide-2026',
                'einzelunternehmen-luxemburg-gruenden-leitfaden-2026',
                'eenzelentreprise-letzebuerg-grenden-guide-2026',
                'criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026',
            ])
            ->get(['id', 'content']);

        $fixed = 0;
        foreach ($posts as $post) {
            $newContent = str_replace(array_keys($replacements), array_values($replacements), $post->content);
            if ($newContent !== $post->content) {
                DB::table('blog_posts')->where('id', $post->id)->update(['content' => $newContent]);
                $fixed++;
            }
        }

        echo "  → {$fixed} article(s) corrigé(s) sur " . $posts->count() . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les URLs réparées valent mieux que les cassées.
    }
};
