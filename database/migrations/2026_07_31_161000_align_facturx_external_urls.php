<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article Factur-X : aligne les URL externes des traductions sur celles
 * de la version française.
 *
 * En reconstruisant les quatre traductions, j'ai utilisé deux URL
 * différentes de celles du français :
 *   - economie.gouv.fr/facturation-electronique-entreprises au lieu de
 *     .../tout-savoir-sur-la-facturation-electronique-pour-les-entreprises
 *   - fnfe-mpe.org/ au lieu de fnfe-mpe.org/factur-x/
 *
 * economie.gouv.fr renvoie un 403 à toute requête automatisée, y compris
 * avec un user-agent de navigateur : impossible de départager les deux
 * formes par test. Dans le doute, on retient celle du français, choisie
 * délibérément à la rédaction, plutôt que celle que j'ai composée. La
 * cohérence entre langues vaut mieux qu'un pari sur une URL.
 *
 * fnfe-mpe.org répond 200 dans les deux formes ; le lien profond vers
 * /factur-x/ est plus utile au lecteur.
 */
return new class extends Migration
{
    private const KEY = 'factur-x-zugferd-facturation-electronique-europeenne';

    private const URL_MAP = [
        'https://www.economie.gouv.fr/facturation-electronique-entreprises'
            => 'https://www.economie.gouv.fr/tout-savoir-sur-la-facturation-electronique-pour-les-entreprises',
        'https://fnfe-mpe.org/"'
            => 'https://fnfe-mpe.org/factur-x/"',
    ];

    public function up(): void
    {
        foreach (['de', 'en', 'lb', 'pt'] as $locale) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = str_replace(
                array_keys(self::URL_MAP),
                array_values(self::URL_MAP),
                $post->content
            );

            if ($content !== $post->content) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $content,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Alignement de liens : rien à défaire utilement.
    }
};
