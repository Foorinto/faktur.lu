<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Répare le texte abîmé par le dictionnaire d'accents de
 * UpdateBlogContentFixesSeeder.
 *
 * Ce dictionnaire contenait dix paires dont la forme SOURCE est un verbe
 * conjugué valide et la cible un participe passé — 'modifie' => 'modifié',
 * 'declare' => 'déclaré', 'prepare' => 'préparé', etc. Appliquées
 * aveuglément, elles transformaient un verbe en participe, donc en faute de
 * grammaire. Le seeder tournant APRÈS les migrations à chaque déploiement, la
 * correction était réintroduite à chaque fois.
 *
 * Le cas relevé abîmait une citation littérale de la loi, dans l'article sur
 * la note de crédit : l'article 63, paragraphe 2, LIVA assimile à une facture
 * « tout document ou message qui modifie la facture initiale ». La production
 * servait « qui modifié ».
 *
 * Un balayage des 150 pages publiées n'a relevé que cette occurrence : la base
 * de référence était saine, seule la production était touchée.
 *
 * Le seeder est corrigé dans le même lot ; cette migration rattrape ce qu'il a
 * déjà écrit.
 */
return new class extends Migration
{
    private const FIXES = [
        'qui modifié la facture initiale' => 'qui modifie la facture initiale',
    ];

    public function up(): void
    {
        $fixed = 0;

        foreach (self::FIXES as $from => $to) {
            $posts = DB::table('blog_posts')
                ->where('content', 'like', '%'.$from.'%')
                ->get(['id', 'content']);

            foreach ($posts as $post) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => str_replace($from, $to, $post->content),
                    'updated_at' => now(),
                ]);
                $fixed++;
            }
        }

        echo "  {$fixed} article(s) corrige(s)\n";
    }

    public function down(): void
    {
        // Réintroduire une faute de grammaire n'aurait pas de sens.
    }
};
