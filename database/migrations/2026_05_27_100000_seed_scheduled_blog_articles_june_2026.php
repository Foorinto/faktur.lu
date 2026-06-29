<?php

use Database\Seeders\BlogScheduledJune2026Seeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-shot data migration to seed the 5 scheduled blog articles
     * published weekly every Monday in June 2026.
     *
     * Idempotent: the seeder itself skips articles whose slug already
     * exists, so re-running on environments where the data was inserted
     * manually is safe.
     */
    public function up(): void
    {
        // Seeding de contenu (prod/staging) : la base de test est montée à neuf
        // sans catégories blog, on saute donc en testing pour éviter l'échec FK.
        if (app()->environment('testing')) {
            return;
        }

        (new BlogScheduledJune2026Seeder())->run();
    }

    public function down(): void
    {
        \DB::table('blog_posts')->whereIn('slug', [
            'controle-fiscal-aed-luxembourg-2026-preparation',
            'faia-luxembourg-guide-fichier-audit-informatise-2026',
            'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
            'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
            'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
        ])->delete();
    }
};
