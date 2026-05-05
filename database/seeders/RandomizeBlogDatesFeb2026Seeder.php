<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Attribue à chaque blog post une date de publication (et création) aléatoire
 * entre le 1er février 2026 et le 1er mai 2026, avec une heure aléatoire.
 *
 * IMPORTANT — Cohérence multilingue :
 * Les articles qui partagent une même `translation_key` (= traductions du même
 * article original) reçoivent TOUS la MÊME date. Sinon, on aurait par ex la
 * version FR datée du 12 mars et la version PT du même article datée du 27 avril,
 * ce qui n'a aucun sens.
 *
 * Articles sans translation_key : reçoivent leur propre date aléatoire.
 *
 * Idempotence : NON. Chaque exécution génère de nouvelles dates aléatoires.
 * À lancer manuellement (pas dans deploy.sh).
 *
 * Exécution : php artisan db:seed --class=RandomizeBlogDatesFeb2026Seeder
 */
class RandomizeBlogDatesFeb2026Seeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::create(2026, 2, 1, 0, 0, 0);
        $end = Carbon::create(2026, 5, 1, 23, 59, 59);
        $rangeSeconds = abs($end->diffInSeconds($start));

        $now = now();

        // 1. Articles avec translation_key : grouper et assigner la même date à tous
        $groupedKeys = BlogPost::whereNotNull('translation_key')
            ->distinct()
            ->pluck('translation_key');

        $countGrouped = 0;
        foreach ($groupedKeys as $key) {
            $randomDate = $start->copy()->addSeconds(random_int(0, $rangeSeconds));

            $affected = BlogPost::where('translation_key', $key)->get();
            foreach ($affected as $post) {
                $post->timestamps = false;
                $post->published_at = $randomDate;
                $post->created_at = $randomDate;
                $post->updated_at = $now;
                $post->save();
                $countGrouped++;
            }
        }

        // 2. Articles SANS translation_key : date aléatoire individuelle
        $standalonePosts = BlogPost::whereNull('translation_key')->get();
        $countStandalone = 0;
        foreach ($standalonePosts as $post) {
            $randomDate = $start->copy()->addSeconds(random_int(0, $rangeSeconds));
            $post->timestamps = false;
            $post->published_at = $randomDate;
            $post->created_at = $randomDate;
            $post->updated_at = $now;
            $post->save();
            $countStandalone++;
        }

        $totalKeys = $groupedKeys->count();
        $this->command->info("Randomized publication dates between 2026-02-01 and 2026-05-01:");
        $this->command->info("  - {$countGrouped} translated posts across {$totalKeys} translation groups (each group shares the same date)");
        $this->command->info("  - {$countStandalone} standalone posts (without translation_key)");
    }
}
