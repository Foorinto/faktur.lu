<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte batch 3 — 4 articles FR "créer entreprise individuelle" (LU, FR, BE, DE).
 *
 * Audit factuel de juin 2026 — corrections appliquées :
 *
 * 1. creer-entreprise-individuelle-luxembourg-guide-2026
 *    ✗ Seuil franchise TVA 35 000 € → 50 000 € (art. 57bis depuis 01/01/2025) + tolérance 55 000 €
 *    ✗ Mention "art. 57 du Code de la TVA" → "art. 57bis de la loi modifiée du 12 février 1979"
 *    ✗ Pension CCSS 17 % → 16 % ; total ~25,3 % → ~24,2 %
 *    ✗ "Santé au travail 0,14 %" listée pour indépendant → retirée (concerne employeurs/salariés)
 *    + tarifs RCS LBR précisés (19 € électronique + 20 € avec assistance)
 *    + sources inline (CCSS, AED, LBR, House of Entrepreneurship)
 *
 * 2. creer-entreprise-individuelle-france-guide-2026
 *    ⚠ Date suppression EIRL "15 mai 2022" → loi du 14 février 2022 (le 15 mai = entrée en vigueur statut unique EI)
 *    ⚠ ACRE 2026 : passage à 25 % au 1er juillet 2026 mentionné (manquait dans la version originale)
 *    + lien Légifrance + délai ACRE 60 jours dès 01/01/2026
 *
 * 3. creer-entreprise-individuelle-belgique-guide-2026
 *    ✗ Tranche INASTI "73 447,52 €" (2025) → 75 024,54 € (2026)
 *    ✗ Plafond INASTI "108 238,40 €" (2025) → 110 562,42 € (2026)
 *    ✗ Cotisation min trimestrielle "450,15 €" → ~890 € (titre principal, revenu plancher 17 374,08 €)
 *    ⚠ Tolérance 10 % franchise TVA BE supprimée depuis 01/01/2025 (encadré ajouté)
 *    ✗ "43 % PME / 510 346 entreprises" non sourcé → ligne retirée
 *    + précisions tarif inscription BCE (111,50 € 2026)
 *
 * 4. creer-entreprise-individuelle-allemagne-guide-2026
 *    ⚠ Gewerbeanmeldung "12,50-60 €" → 15-65 € (plancher 12,50 € rare)
 *    + Freibetrag IHK 15 340 € (personnes physiques non Handelsregister) ajouté
 *    + Exonération 2 ans Existenzgründer (Gewerbeertrag ≤ 25 000 €) ajoutée
 *
 * Idempotent : update direct du contenu, aucun changement de slug.
 */
return new class extends Migration
{
    private const REWRITES = [
        [
            'slug' => 'creer-entreprise-individuelle-luxembourg-guide-2026',
            'file' => 'creer-entreprise-individuelle-luxembourg-2026.html',
            'title' => 'Créer une entreprise individuelle au Luxembourg : Guide complet 2026',
            'meta_title' => 'Créer une entreprise individuelle au Luxembourg 2026 | faktur.lu',
            'meta_description' => 'Guide complet 2026 pour créer une entreprise individuelle au Luxembourg : autorisation d\'établissement (50 €), RCS, CCSS (cotisations ~24 %), franchise TVA art. 57bis (seuil 50 000 €).',
        ],
        [
            'slug' => 'creer-entreprise-individuelle-france-guide-2026',
            'file' => 'creer-entreprise-individuelle-france-2026.html',
            'title' => 'Créer une entreprise individuelle en France : Guide complet 2026',
            'meta_title' => 'Créer une entreprise individuelle en France 2026 | faktur.lu',
            'meta_description' => 'Guide 2026 pour créer une micro-entreprise / EI en France : Guichet Unique INPI, seuils TVA et micro, ACRE (50 % avant le 1er juillet 2026, 25 % après), cotisations URSSAF.',
        ],
        [
            'slug' => 'creer-entreprise-individuelle-belgique-guide-2026',
            'file' => 'creer-entreprise-individuelle-belgique-2026.html',
            'title' => 'Créer une entreprise individuelle en Belgique : Guide complet 2026',
            'meta_title' => 'Créer une entreprise individuelle en Belgique 2026 | faktur.lu',
            'meta_description' => 'Guide 2026 pour devenir indépendant en Belgique : inscription BCE, cotisations INASTI (tranches 2026 actualisées), franchise TVA 25 000 € (tolérance 10 % supprimée depuis 2025).',
        ],
        [
            'slug' => 'creer-entreprise-individuelle-allemagne-guide-2026',
            'file' => 'creer-entreprise-individuelle-allemagne-2026.html',
            'title' => 'Créer une entreprise individuelle en Allemagne : Guide complet 2026',
            'meta_title' => 'Créer une entreprise individuelle en Allemagne 2026 | faktur.lu',
            'meta_description' => 'Guide 2026 pour créer une Einzelunternehmen ou un statut Freiberufler en Allemagne : Gewerbeanmeldung (15-65 €), Kleinunternehmerregelung § 19 UStG, IHK (Freibetrag 15 340 €, exonération 2 ans).',
        ],
    ];

    public function up(): void
    {
        $contentDir = database_path('seeders/content/article_rewrites');
        $totalUpdated = 0;

        foreach (self::REWRITES as $rewrite) {
            $filePath = "{$contentDir}/{$rewrite['file']}";
            $newContent = file_get_contents($filePath);

            if ($newContent === false) {
                throw new \RuntimeException("Fichier de réécriture introuvable : {$filePath}");
            }

            $updated = DB::table('blog_posts')
                ->where('slug', $rewrite['slug'])
                ->where('locale', 'fr')
                ->update([
                    'title' => $rewrite['title'],
                    'meta_title' => $rewrite['meta_title'],
                    'meta_description' => $rewrite['meta_description'],
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);

            echo "  → {$rewrite['slug']} : {$updated} article(s) mis à jour" . PHP_EOL;
            $totalUpdated += $updated;
        }

        echo "  → Total batch 3 : {$totalUpdated} article(s) refondu(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les chiffres 2026 corrigés.
    }
};
