<?php

use Database\Seeders\BlogScheduledJune2026DeSeeder;
use Database\Seeders\BlogScheduledJune2026EnSeeder;
use Database\Seeders\BlogScheduledJune2026LbSeeder;
use Database\Seeders\BlogScheduledJune2026PtSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-shot data migration to seed the EN/DE/LB/PT translations of the
     * 5 scheduled June 2026 long-tail blog articles.
     *
     * The FR versions are seeded by 2026_05_27_100000.
     * Each translation has translation_key matching the FR slug so
     * articles can be cross-linked across locales.
     *
     * All seeders are idempotent (skip if slug already exists).
     */
    public function up(): void
    {
        (new BlogScheduledJune2026EnSeeder())->run();
        (new BlogScheduledJune2026DeSeeder())->run();
        (new BlogScheduledJune2026LbSeeder())->run();
        (new BlogScheduledJune2026PtSeeder())->run();
    }

    public function down(): void
    {
        \DB::table('blog_posts')
            ->whereIn('locale', ['en', 'de', 'lb', 'pt'])
            ->whereIn('translation_key', [
                'controle-fiscal-aed-luxembourg-2026-preparation',
                'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
            ])
            ->delete();
    }
};
