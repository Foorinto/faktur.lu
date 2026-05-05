<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Attribue à chaque blog post une date de publication (et création) aléatoire
 * entre le 1er février 2026 et le 1er mai 2026, avec une heure aléatoire.
 *
 * Utile pour donner un effet de cadence éditoriale réaliste plutôt que d'avoir
 * tous les articles datés du même jour (date du seeding initial).
 *
 * Idempotent : peut être exécuté plusieurs fois (génère de nouvelles dates aléatoires
 * à chaque exécution — à utiliser avec précaution si on veut figer les dates).
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

        $posts = BlogPost::all();
        $count = 0;

        foreach ($posts as $post) {
            $randomDate = $start->copy()->addSeconds(random_int(0, $rangeSeconds));

            // On désactive les timestamps automatiques pour pouvoir setter created_at/updated_at à la main
            $post->timestamps = false;
            $post->published_at = $randomDate;
            $post->created_at = $randomDate;
            // updated_at reflète la dernière modif réelle — on garde celle d'aujourd'hui
            $post->updated_at = now();
            $post->save();

            $count++;
        }

        $this->command->info("Randomized publication dates for {$count} blog posts between 2026-02-01 and 2026-05-01.");
    }
}
