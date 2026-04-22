<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignBlogTranslationKeys extends Command
{
    protected $signature = 'blog:assign-translation-keys {--dry-run : Affiche les changements sans les appliquer} {--reset : Vide les translation_key existantes avant}';

    protected $description = 'Auto-assigne des translation_key aux articles blog pour lier les traductions par ordre de creation';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($this->option('reset') && !$dryRun) {
            BlogPost::query()->update(['translation_key' => null]);
            $this->warn('Tous les translation_key ont ete reinitialises.');
        }

        // Step 1: assign translation_key = slug to all FR articles
        $frPosts = BlogPost::where('locale', 'fr')
            ->orderBy('id')
            ->get();

        $this->info("Etape 1 : {$frPosts->count()} articles FR a traiter");
        foreach ($frPosts as $post) {
            if ($post->translation_key) continue;
            $key = Str::slug($post->slug);
            $this->line("  FR#{$post->id} → {$key}");
            if (!$dryRun) {
                $post->update(['translation_key' => $key]);
            }
        }

        // Refresh FR posts (to have translation_key populated)
        $frPosts = BlogPost::where('locale', 'fr')->orderBy('id')->get();

        // Step 2: group non-FR articles into trios (DE, EN, LB) by creation order
        // Each trio corresponds to ONE FR article in order
        $nonFrPosts = BlogPost::whereIn('locale', ['de', 'en', 'lb'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->info("\nEtape 2 : {$nonFrPosts->count()} traductions, groupees en trios (DE/EN/LB)");

        $trios = $nonFrPosts->chunk(3);
        $this->info("Nombre de trios : {$trios->count()}");

        if ($trios->count() !== $frPosts->count()) {
            $this->warn("ATTENTION : {$trios->count()} trios mais {$frPosts->count()} articles FR. Matching imparfait possible.");
        }

        $matched = 0;
        $unmatched = [];

        foreach ($trios as $index => $trio) {
            $frPost = $frPosts[$index] ?? null;

            if (!$frPost) {
                foreach ($trio as $post) {
                    $unmatched[] = $post;
                }
                continue;
            }

            $translationKey = $frPost->translation_key;

            // Verify the trio is actually a DE/EN/LB trio
            $locales = $trio->pluck('locale')->sort()->values()->toArray();
            if ($locales !== ['de', 'en', 'lb']) {
                $this->warn("  Trio #{$index} n'est pas un trio DE/EN/LB valide : " . implode(',', $locales));
            }

            foreach ($trio as $post) {
                $this->line("  {$post->locale}#{$post->id} ({$post->slug})");
                $this->line("    → FR#{$frPost->id} ({$frPost->slug}) [key: {$translationKey}]");

                if (!$dryRun) {
                    $post->update(['translation_key' => $translationKey]);
                }
                $matched++;
            }
        }

        $this->info("\n=== Resume ===");
        $this->info("Articles FR : {$frPosts->count()}");
        $this->info("Traductions matchees : {$matched}");
        $this->warn("Traductions non-matchees : " . count($unmatched));

        if ($dryRun) {
            $this->warn("\nMode dry-run : aucun changement applique.");
            $this->info('Lancez sans --dry-run pour appliquer.');
        } else {
            $this->info("\n✓ Terminé.");
        }

        return 0;
    }
}
