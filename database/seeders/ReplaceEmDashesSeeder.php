<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Remplace le tiret cadratin « — » (U+2014) par un trait d'union « - » dans le
 * contenu des articles de blog stocké EN BASE (le texte UI est, lui, corrigé
 * directement dans les fichiers de langue / Vue).
 *
 * Idempotent : après le 1er passage il n'y a plus de « — », donc c'est un simple
 * scan no-op aux déploiements suivants. saveQuietly() évite de déclencher des
 * events de modèle ; les timestamps sont mis à jour (utile pour lastmod sitemap).
 */
class ReplaceEmDashesSeeder extends Seeder
{
    private const EM_DASH = "\u{2014}";

    private const FIELDS = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'];

    public function run(): void
    {
        $fixed = 0;

        BlogPost::query()->chunkById(100, function ($posts) use (&$fixed) {
            foreach ($posts as $post) {
                $dirty = false;
                foreach (self::FIELDS as $field) {
                    $value = $post->{$field};
                    if (is_string($value) && str_contains($value, self::EM_DASH)) {
                        $post->{$field} = str_replace(self::EM_DASH, '-', $value);
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $post->save();
                    $fixed++;
                }
            }
        });

        $this->command?->info("ReplaceEmDashesSeeder : {$fixed} article(s) corrigé(s).");
    }
}
