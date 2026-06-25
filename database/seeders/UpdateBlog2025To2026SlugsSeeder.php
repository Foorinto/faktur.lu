<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

/**
 * Met à jour les blog posts existants ayant un slug se terminant par "-2025"
 * (ou contenant "2025" dans le titre/meta) pour les passer à "2026".
 *
 * Idempotent : peut être exécuté plusieurs fois sans effet de bord.
 *
 * Exécution : php artisan db:seed --class=UpdateBlog2025To2026SlugsSeeder
 *
 * Note SEO : les redirections 301 anciens slugs → nouveaux slugs sont gérées
 * dans routes/web.php pour préserver les backlinks et l'indexation Google.
 */
class UpdateBlog2025To2026SlugsSeeder extends Seeder
{
    /**
     * Mapping ancien slug → nouveau slug pour chaque article × langue.
     * Ces slugs doivent matcher EXACTEMENT ceux générés par les autres seeders.
     */
    public const SLUG_MAP = [
        // Article 1 - Création entreprise Luxembourg
        'creer-entreprise-individuelle-luxembourg-guide-2025' => 'creer-entreprise-individuelle-luxembourg-guide-2026',
        'einzelunternehmen-luxemburg-gruenden-leitfaden-2025' => 'einzelunternehmen-luxemburg-gruenden-leitfaden-2026',
        'sole-proprietorship-luxembourg-guide-2025' => 'sole-proprietorship-luxembourg-guide-2026',
        'eenzelentreprise-letzebuerg-grenden-guide-2025' => 'eenzelentreprise-letzebuerg-grenden-guide-2026',
        'criar-uma-empresa-individual-no-luxemburgo-guia-completo-2025' => 'criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026',

        // Article 2 - Création entreprise France
        'creer-entreprise-individuelle-france-guide-2025' => 'creer-entreprise-individuelle-france-guide-2026',
        'einzelunternehmen-frankreich-gruenden-leitfaden-2025' => 'einzelunternehmen-frankreich-gruenden-leitfaden-2026',
        'sole-proprietorship-france-guide-2025' => 'sole-proprietorship-france-guide-2026',
        'eenzelentreprise-frankreich-grenden-guide-2025' => 'eenzelentreprise-frankreich-grenden-guide-2026',
        'criar-uma-empresa-individual-em-franca-guia-completo-2025' => 'criar-uma-empresa-individual-em-franca-guia-completo-2026',

        // Article 3 - Création entreprise Belgique
        'creer-entreprise-individuelle-belgique-guide-2025' => 'creer-entreprise-individuelle-belgique-guide-2026',
        'einzelunternehmen-belgien-gruenden-leitfaden-2025' => 'einzelunternehmen-belgien-gruenden-leitfaden-2026',
        'sole-proprietorship-belgium-guide-2025' => 'sole-proprietorship-belgium-guide-2026',
        'eenzelentreprise-belgien-grenden-guide-2025' => 'eenzelentreprise-belgien-grenden-guide-2026',
        'criar-uma-empresa-individual-na-belgica-guia-completo-2025' => 'criar-uma-empresa-individual-na-belgica-guia-completo-2026',

        // Article 4 - Création entreprise Allemagne
        'creer-entreprise-individuelle-allemagne-guide-2025' => 'creer-entreprise-individuelle-allemagne-guide-2026',
        'einzelunternehmen-deutschland-gruenden-leitfaden-2025' => 'einzelunternehmen-deutschland-gruenden-leitfaden-2026',
        'sole-proprietorship-germany-guide-2025' => 'sole-proprietorship-germany-guide-2026',
        'eenzelentreprise-deutschland-grenden-guide-2025' => 'eenzelentreprise-deutschland-grenden-guide-2026',
        'criar-uma-empresa-individual-na-alemanha-guia-completo-2025' => 'criar-uma-empresa-individual-na-alemanha-guia-completo-2026',
    ];

    /**
     * Mapping de remplacement dans les titres et meta_titles.
     */
    private const TITLE_REPLACEMENTS = [
        'Guide complet 2025'           => 'Guide complet 2026',
        '| Guide 2025'                 => '| Guide 2026',
        'Vollständiger Leitfaden 2025' => 'Vollständiger Leitfaden 2026',
        '| Leitfaden 2025'             => '| Leitfaden 2026',
        'Complete Guide 2025'          => 'Complete Guide 2026',
        'Komplette Guide 2025'         => 'Komplette Guide 2026',
        'Guia completo 2025'           => 'Guia completo 2026',
        '| Guia 2025'                  => '| Guia 2026',
    ];

    /**
     * Mapping de remplacement dans le contenu HTML des articles.
     * IMPORTANT : on remplace UNIQUEMENT les labels d'année (Seuil 2025, Taux 2025…).
     * On NE TOUCHE PAS aux dates historiques ("depuis le 1er octobre 2025" en Wallonie),
     * qui sont des faits ne devant pas être modifiés.
     */
    private const CONTENT_REPLACEMENTS = [
        // FR
        'Seuil de CA (2025)'           => 'Seuil de CA (2026)',
        'Montant (2025)'               => 'Montant (2026)',
        'Taux 2025'                    => 'Taux 2026',
        'Cotisation minimale 2025'     => 'Cotisation minimale 2026',
        // DE
        'Umsatzschwelle (2025)'        => 'Umsatzschwelle (2026)',
        'Betrag (2025)'                => 'Betrag (2026)',
        'Satz 2025'                    => 'Satz 2026',
        'Mindestbeitrag 2025'          => 'Mindestbeitrag 2026',
        // EN
        'Turnover Threshold (2025)'    => 'Turnover Threshold (2026)',
        'Amount (2025)'                => 'Amount (2026)',
        'Rate 2025'                    => 'Rate 2026',
        'Minimum contribution 2025'    => 'Minimum contribution 2026',
        // LB
        'Ëmsazschwelle (2025)'         => 'Ëmsazschwelle (2026)',
        // PT
        'Limiar de VN (2025)'          => 'Limiar de VN (2026)',
        'Montante (2025)'              => 'Montante (2026)',
        'Taxa 2025'                    => 'Taxa 2026',
        'Contribuição mínima 2025'     => 'Contribuição mínima 2026',
    ];

    public function run(): void
    {
        $updated = 0;
        $skipped = 0;

        foreach (self::SLUG_MAP as $oldSlug => $newSlug) {
            $post = BlogPost::where('slug', $oldSlug)->first();
            if (!$post) {
                $skipped++;
                continue;
            }

            $newTitle = strtr($post->title, self::TITLE_REPLACEMENTS);
            $newMetaTitle = $post->meta_title
                ? strtr($post->meta_title, self::TITLE_REPLACEMENTS)
                : $post->meta_title;
            $newContent = strtr($post->content, self::CONTENT_REPLACEMENTS);

            $post->update([
                'slug' => $newSlug,
                'title' => $newTitle,
                'meta_title' => $newMetaTitle,
                'content' => $newContent,
            ]);

            $updated++;
            $this->command->info("Updated: $oldSlug → $newSlug");
        }

        $this->command->info("Summary: $updated updated, $skipped skipped (not in DB).");
    }
}
