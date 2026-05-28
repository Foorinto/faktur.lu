<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Update blog content that referenced the free plan's old "10 invoices/month"
 * limit to the new "5 invoices/month" value (per locale phrasing).
 *
 * Only the invoice-count phrase is replaced; client counts and other limits
 * are untouched. Idempotent: skips content not containing the old phrase.
 */
return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'fr' => ['10 factures/mois', '5 factures/mois'],
            'en' => ['10 invoices/month', '5 invoices/month'],
            'de' => ['10 Rechnungen/Monat', '5 Rechnungen/Monat'],
            'lb' => ['10 Rechnungen/Mount', '5 Rechnungen/Mount'],
            'pt' => ['10 faturas/mês', '5 faturas/mês'],
        ];

        foreach ($replacements as $locale => [$search, $replace]) {
            BlogPost::where('locale', $locale)
                ->where('content', 'like', '%' . $search . '%')
                ->get()
                ->each(function (BlogPost $post) use ($search, $replace) {
                    $post->update([
                        'content' => str_replace($search, $replace, $post->content),
                    ]);
                });
        }
    }

    public function down(): void
    {
        // No-op: reverting would re-introduce the outdated limit.
    }
};
