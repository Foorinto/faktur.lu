<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les versions DE et LB de l'article « TVA 2026 : quatre taux » pointent vers
 * le calculateur de TVA sous un segment anglais (« /tools/ ») qui n'existe pas
 * dans ces langues. Vérifié en production : les deux renvoient un 404.
 *
 * Les segments réellement déclarés (routes tools.vat_calculator.*) sont
 * « werkzeuge » en allemand et « handgeschir » en luxembourgeois. Les versions
 * FR, EN et PT utilisaient déjà le bon segment.
 */
return new class extends Migration
{
    private const FIXES = [
        '/de/tools/mwst-rechner' => '/de/werkzeuge/mwst-rechner',
        '/lb/tools/tva-rechner' => '/lb/handgeschir/tva-rechner',
    ];

    public function up(): void
    {
        $this->apply(self::FIXES);
    }

    public function down(): void
    {
        $this->apply(array_flip(self::FIXES));
    }

    /** @param  array<string, string>  $map */
    private function apply(array $map): void
    {
        foreach ($map as $from => $to) {
            $posts = DB::table('blog_posts')
                ->where('content', 'like', '%' . $from . '%')
                ->get(['id', 'content']);

            foreach ($posts as $post) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => str_replace($from, $to, $post->content),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
