<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les CTA de cinq articles pointent vers /fr/register, /de/register, etc.
 * Cette route n'existe pas : seule la route non préfixée `register` est
 * déclarée (routes/auth.php). Le groupe Route::prefix('{locale}') ne contient
 * aucune route d'inscription, si bien que /fr/register renvoie un 404 — vérifié
 * en production.
 *
 * Les 115 autres CTA du blog utilisent déjà la forme correcte `/register`.
 *
 * Note : huit de ces liens ont été introduits par la reconstruction des
 * traductions des deux articles pivots LIVA, en reproduisant la forme préfixée
 * du français sans vérifier la route. Les articles français, eux, portaient
 * déjà le défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        $posts = DB::table('blog_posts')
            ->where('content', 'like', '%/register"%')
            ->get(['id', 'content']);

        $fixed = 0;

        foreach ($posts as $post) {
            $content = preg_replace(
                '#href="/[a-z]{2}/register"#',
                'href="/register"',
                $post->content,
                -1,
                $count
            );

            if ($count > 0) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $content,
                    'updated_at' => now(),
                ]);
                $fixed += $count;
            }
        }

        // Trace utile si la migration est rejouée sur un autre environnement.
        echo "  {$fixed} lien(s) /xx/register corrigé(s) en /register\n";
    }

    public function down(): void
    {
        // Restaurer une URL qui renvoie un 404 n'aurait pas de sens.
    }
};
