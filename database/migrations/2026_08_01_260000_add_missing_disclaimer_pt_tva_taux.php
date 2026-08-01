<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « TVA Luxembourg : taux, calcul et obligations » — version portugaise.
 *
 * Le portugais était la seule langue dépourvue de l'encart « À vérifier chaque
 * année », celui qui rappelle que seuils, taux et procédures évoluent et qui
 * renvoie le lecteur à sa fiduciaire et à l'AED.
 *
 * Ce n'est pas un détail de mise en page : c'est la clause de prudence qui
 * évite que l'article soit lu comme un conseil fiscal définitif. Toutes les
 * autres langues la portaient.
 *
 * Inséré juste avant la section « Fontes oficiais », à la même place que dans
 * les quatre autres langues.
 */
return new class extends Migration
{
    private const KEY = 'tva-luxembourg-taux-calcul-obligations';

    private const ANCHOR = '<h2>Fontes oficiais</h2>';

    private const BOX = '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
        .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
        .'    <p>Os limiares, as taxas e os procedimentos fiscais luxemburgueses podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l\'Enregistrement, des Domaines et de la TVA)</a>.</p>'."\n"
        .'</div>';

    public function up(): void
    {
        $post = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'pt')
            ->first(['id', 'content']);

        if (! $post || str_contains($post->content, 'A verificar todos os anos')) {
            return;
        }

        if (! str_contains($post->content, self::ANCHOR)) {
            return;
        }

        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => str_replace(self::ANCHOR, self::BOX."\n\n".self::ANCHOR, $post->content),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Retirer une clause de prudence n'aurait pas de sens.
    }
};
