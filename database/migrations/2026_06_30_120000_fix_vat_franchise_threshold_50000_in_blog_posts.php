<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Met à jour le SEUIL de franchise TVA luxembourgeoise (35 000 € → 50 000 €,
 * en vigueur depuis le 1er janvier 2025) dans le contenu des articles de blog.
 *
 * Sûreté :
 *  - Lookahead négatif : on NE remplace PAS un « 35 000 » suivi de près par
 *    « 50 000 » (phrases de transition « passé de 35 000 € à 50 000 € » → gardées).
 *  - On ne touche à AUCUNE référence d'article (56ter / 57bis / 57…).
 *  - Idempotent : après passage, plus aucun seuil « 35 000 » isolé ne subsiste.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 35 000 / 35.000 / 35,000  →  50 000 / 50.000 / 50,000
        // sauf si suivi (≤ 25 caractères) d'un « 50 000 » (= transition à conserver).
        $pattern = '/35([ .,])000(?!.{0,25}50[ .,]000)/u';
        $fields = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

        DB::table('blog_posts')->chunkById(100, function ($posts) use ($pattern, $fields) {
            foreach ($posts as $post) {
                $update = [];
                foreach ($fields as $f) {
                    if ($post->$f === null) {
                        continue;
                    }
                    $new = preg_replace($pattern, '50${1}000', $post->$f);
                    if ($new !== $post->$f) {
                        $update[$f] = $new;
                    }
                }
                if ($update) {
                    DB::table('blog_posts')->where('id', $post->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        // Pas de rollback automatique (modification de contenu rédactionnel).
    }
};
