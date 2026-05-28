<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Add a translated "related articles" section to the EN/DE/LB/PT versions
 * of the 5 scheduled June 2026 articles, mirroring the FR internal linking.
 *
 * Target articles are resolved dynamically by translation_key, so the
 * locale-specific slug is always correct. Idempotent: skips a post whose
 * content already contains the localized section header.
 */
return new class extends Migration
{
    public function up(): void
    {
        // source translation_key => [target translation_keys]
        $linkMap = [
            'controle-fiscal-aed-luxembourg-2026-preparation' => [
                'faia-luxembourg-fichier-audit-informatise-guide',
                'mentions-obligatoires-facture-luxembourg',
                'archivage-factures-luxembourg-duree-legale-format',
            ],
            'faia-luxembourg-guide-fichier-audit-informatise-2026' => [
                'controle-fiscal-aed-luxembourg-2026-preparation',
                'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'mentions-obligatoires-facture-luxembourg',
            ],
            'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg' => [
                'tva-luxembourg-taux-calcul-obligations',
                'mentions-obligatoires-facture-luxembourg',
                'freelance-luxembourg-facturer-conformite',
            ],
            'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques' => [
                'franchise-tva-luxembourg-seuil-obligations-regime-normal',
                'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'mentions-obligatoires-facture-luxembourg',
            ],
            'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire' => [
                'controle-fiscal-aed-luxembourg-2026-preparation',
                'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'mentions-obligatoires-facture-luxembourg',
            ],
        ];

        $headers = [
            'en' => 'Read more',
            'de' => 'Mehr erfahren',
            'lb' => 'Méi liesen',
            'pt' => 'Saiba mais',
        ];

        $ctaPattern = '<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">';

        foreach (['en', 'de', 'lb', 'pt'] as $locale) {
            foreach ($linkMap as $sourceKey => $targetKeys) {
                $post = BlogPost::where('locale', $locale)->where('translation_key', $sourceKey)->first();
                if (!$post) {
                    continue;
                }
                if (str_contains($post->content, '<h2>' . $headers[$locale] . '</h2>')) {
                    continue; // already linked
                }

                $lis = '';
                foreach ($targetKeys as $tk) {
                    $target = BlogPost::where('locale', $locale)->where('translation_key', $tk)->first();
                    if (!$target) {
                        continue;
                    }
                    $lis .= '    <li><a href="/' . $locale . '/blog/' . $target->slug . '">'
                        . e($target->title) . '</a></li>' . "\n";
                }
                if ($lis === '') {
                    continue;
                }

                $section = '<h2>' . $headers[$locale] . '</h2>' . "\n" . '<ul>' . "\n" . $lis . '</ul>' . "\n\n";
                $newContent = str_replace($ctaPattern, $section . $ctaPattern, $post->content);

                if ($newContent !== $post->content) {
                    $post->update(['content' => $newContent]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: the related-article sections are part of the article content.
    }
};
