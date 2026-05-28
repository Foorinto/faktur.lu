<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Add a "Articles connexes" section to the 4 FR "créer une entreprise
 * individuelle" guides (LU / FR / BE / DE), which were a fully isolated
 * cluster (no inbound, no outbound internal links).
 *
 * Links interconnect the geographic cluster (cross-border audience) and
 * point at otherwise-orphaned articles (tva 4 taux) to spread link equity.
 *
 * Idempotent: skips a post whose content already has an "Articles connexes"
 * block.
 */
return new class extends Migration
{
    public function up(): void
    {
        $links = [
            'creer-entreprise-individuelle-luxembourg-guide-2026' => [
                'creer-entreprise-individuelle-france-guide-2026',
                'creer-entreprise-individuelle-belgique-guide-2026',
                'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
            ],
            'creer-entreprise-individuelle-france-guide-2026' => [
                'creer-entreprise-individuelle-luxembourg-guide-2026',
                'creer-entreprise-individuelle-allemagne-guide-2026',
                'freelance-luxembourg-facturer-conformite',
            ],
            'creer-entreprise-individuelle-belgique-guide-2026' => [
                'creer-entreprise-individuelle-luxembourg-guide-2026',
                'mentions-obligatoires-facture-luxembourg',
                'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
            ],
            'creer-entreprise-individuelle-allemagne-guide-2026' => [
                'creer-entreprise-individuelle-luxembourg-guide-2026',
                'freelance-luxembourg-facturer-conformite',
                'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
            ],
        ];

        foreach ($links as $sourceSlug => $targetSlugs) {
            $post = BlogPost::where('locale', 'fr')->where('slug', $sourceSlug)->first();
            if (!$post) {
                continue;
            }
            if (str_contains($post->content, 'Articles connexes')) {
                continue;
            }

            $lis = '';
            foreach ($targetSlugs as $ts) {
                $target = BlogPost::where('locale', 'fr')->where('slug', $ts)->first();
                if (!$target) {
                    continue;
                }
                $lis .= '<li><a href="/fr/blog/' . $target->slug . '" '
                    . 'class="text-primary-500 hover:text-primary-600 text-sm">'
                    . e($target->title) . ' →</a></li>';
            }
            if ($lis === '') {
                continue;
            }

            $section = '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">'
                . '<h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3>'
                . '<ul class="space-y-1">' . $lis . '</ul></div>';

            $post->update(['content' => $post->content . $section]);
        }
    }

    public function down(): void
    {
        // No-op: the related-articles section becomes part of the content.
    }
};
