<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire l'accent des noms de domaine cites dans le texte des articles.
 *
 * UpdateBlogContentFixesSeeder appliquait 'economie' => 'économie' au libelle
 * visible des liens, transformant « economie.gouv.fr » en
 * « économie.gouv.fr » a chaque deploiement. Le seeder est desormais corrige
 * (il masque les domaines comme il masquait deja les href), mais la valeur
 * fautive reste en base : rien ne la rectifie retroactivement.
 *
 * Le remplacement porte sur le domaine complet, jamais sur le mot seul : la
 * prose « notre économie » reste correctement accentuee.
 */
return new class extends Migration
{
    private const DOMAINS = [
        'économie.gouv.fr' => 'economie.gouv.fr',
    ];

    public function up(): void
    {
        $this->apply(self::DOMAINS);
    }

    public function down(): void
    {
        // Reintroduire un accent dans un nom de domaine n'aurait pas de sens.
    }

    /** @param  array<string, string>  $map */
    private function apply(array $map): void
    {
        $fixed = 0;

        foreach ($map as $from => $to) {
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
};
