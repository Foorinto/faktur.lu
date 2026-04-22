<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignBlogTranslationKeys extends Command
{
    protected $signature = 'blog:assign-translation-keys {--dry-run : Affiche les changements sans les appliquer}';

    protected $description = 'Assigne des translation_key aux articles FR existants (base pour lier les traductions)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Attribution des translation_key aux articles FR...');

        $frPosts = BlogPost::where('locale', 'fr')
            ->whereNull('translation_key')
            ->get();

        if ($frPosts->isEmpty()) {
            $this->info('Aucun article FR sans translation_key.');
            return 0;
        }

        $this->info("Trouvés : {$frPosts->count()} article(s) FR");

        foreach ($frPosts as $post) {
            $translationKey = Str::slug($post->slug);

            $this->line("  - {$post->title}");
            $this->line("    → translation_key: {$translationKey}");

            if (!$dryRun) {
                $post->update(['translation_key' => $translationKey]);
            }
        }

        if ($dryRun) {
            $this->warn('Mode dry-run : aucun changement appliqué.');
            $this->info('Lancez sans --dry-run pour appliquer.');
        } else {
            $this->info("\n✓ {$frPosts->count()} article(s) FR mis à jour.");
            $this->info('');
            $this->info('Prochaine étape : dans l\'admin, ouvrez chaque article non-FR');
            $this->info('et copiez-y la même translation_key que sa version FR correspondante.');
        }

        return 0;
    }
}
