<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder complet pour les 15 articles SEO + 45 traductions + liens internes.
 * Idempotent : peut être exécuté plusieurs fois sans doublon.
 *
 * Usage : php artisan db:seed --class=BlogSeoCompleteSeeder
 */
class BlogSeoCompleteSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/blog_seo_data.json');
        if (!file_exists($dataPath)) {
            $this->command->error("Fichier blog_seo_data.json introuvable dans database/seeders/");
            return;
        }

        $data = json_decode(file_get_contents($dataPath), true);
        $authorId = DB::table('users')->first()?->id ?? 1;

        // 1. Insérer les nouveaux articles (60 articles : 15 FR + 15 EN + 15 DE + 15 LB)
        $this->command->info("=== Insertion des articles ===");
        $inserted = 0;
        $skipped = 0;

        foreach ($data['new_posts'] as $post) {
            if (DB::table('blog_posts')->where('slug', $post['slug'])->where('locale', $post['locale'])->exists()) {
                $skipped++;
                continue;
            }

            $postId = DB::table('blog_posts')->insertGetId([
                'author_id' => $authorId,
                'category_id' => $post['category_id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'excerpt' => $post['excerpt'],
                'content' => $post['content'],
                'meta_title' => $post['meta_title'],
                'meta_description' => $post['meta_description'],
                'locale' => $post['locale'],
                'status' => 'published',
                'published_at' => now(),
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attach tags
            $tagIds = $data['tags'][$post['slug']] ?? [];
            foreach ($tagIds as $tagId) {
                DB::table('blog_post_tag')->insertOrIgnore([
                    'blog_post_id' => $postId,
                    'blog_tag_id' => $tagId,
                ]);
            }

            $inserted++;
        }

        $this->command->info("Articles insérés : {$inserted}, ignorés (déjà existants) : {$skipped}");

        // 2. Mettre à jour les articles existants avec les liens internes
        $this->command->info("=== Mise à jour des liens internes ===");
        $updated = 0;

        foreach ($data['updated_existing'] as $existing) {
            $post = DB::table('blog_posts')
                ->where('slug', $existing['slug'])
                ->where('locale', 'fr')
                ->first();

            if (!$post) {
                continue;
            }

            // Ne mettre à jour que si l'article n'a pas déjà les liens
            if (str_contains($post->content, 'Articles connexes')) {
                continue;
            }

            DB::table('blog_posts')
                ->where('id', $post->id)
                ->update(['content' => $existing['content'], 'updated_at' => now()]);

            $updated++;
        }

        $this->command->info("Articles existants mis à jour avec liens : {$updated}");
        $this->command->info("=== Terminé ===");
    }
}
