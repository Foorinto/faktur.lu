<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte batch 2 — 4 articles FR sur la conformité luxembourgeoise.
 *
 * Audit factuel de juin 2026 — corrections appliquées :
 *
 * 1. mentions-obligatoires-facture-luxembourg
 *    ✗ Mention franchise "Article 60bis" → "Article 57bis"
 *    ✗ Mention autoliquidation "Article 44 loi LU" → "Article 196 directive 2006/112/CE"
 *    + tableau récapitulatif des mentions par situation, sources inline
 *
 * 2. livre-des-recettes-luxembourg-obligations-modele
 *    ✗ Seuil franchise 35 000 € → 50 000 € (art. 57bis depuis 01/01/2025)
 *    ✗ Base conservation "art. 60 Abgabenordnung" → "art. 16 Code de commerce + art. 65 LIVA"
 *    ✗ Mention "ACD" pour livre des recettes TVA → "AED"
 *    + nuance scope (sociétés en partie double exclues), encadré ACD vs AED
 *
 * 3. controle-fiscal-aed-luxembourg-2026-preparation
 *    ✗ Amendes "10/50/200 %" inventées → 250 € à 10 000 € (art. 77 LIVA),
 *      fraude jusqu'à 10× le montant + emprisonnement (art. 80 LIVA)
 *    ✗ "Délai 30 jours contestation" → "3 mois" (réclamation directeur AED)
 *    ✗ "Recours Tribunal administratif" → "Tribunal d'arrondissement" (matière TVA = juridiction judiciaire)
 *    ✗ "Préavis 8 jours minimum" non documenté → reformulé
 *    + référence article 63 LIVA (et non 61), références 81 LIVA pour prescription
 *
 * 4. faia-luxembourg-guide-fichier-audit-informatise-2026
 *    ✗ "FAIA obligatoire pour tous, y compris franchise" → exonération CA ≤ 112 k€
 *      (FAQ AED) — refonte de la section "Qui doit fournir un FAIA"
 *    ✗ Délai "15 jours" → aucun délai légal fixe, défini au cas par cas
 *    + mention des 3 schémas FAIA (full / reduced A / reduced B)
 *    + correction "outil de validation AED en ligne" (n'existe pas)
 *
 * Idempotent : str_replace silencieux sur contenu identique.
 */
return new class extends Migration
{
    private const REWRITES = [
        [
            'slug' => 'mentions-obligatoires-facture-luxembourg',
            'file' => 'mentions-obligatoires-facture-luxembourg.html',
            'title' => 'Mentions obligatoires sur une facture au Luxembourg : checklist complète (art. 63 LIVA)',
            'meta_title' => 'Mentions obligatoires facture Luxembourg : checklist 2026 | faktur.lu',
            'meta_description' => 'Checklist complète des mentions obligatoires sur une facture luxembourgeoise selon l\'article 63 LIVA : émetteur, client, TVA, autoliquidation (art. 196 directive), franchise (art. 57bis).',
        ],
        [
            'slug' => 'livre-des-recettes-luxembourg-obligations-modele',
            'file' => 'livre-des-recettes-luxembourg.html',
            'title' => 'Livre des recettes au Luxembourg : obligations, modèle et conservation 10 ans',
            'meta_title' => 'Livre des recettes Luxembourg : obligations 2026 | faktur.lu',
            'meta_description' => 'Le livre des recettes au Luxembourg : qui est concerné (indépendants, franchise TVA 50 000 €), contenu obligatoire, conservation 10 ans (art. 16 Code de commerce).',
        ],
        [
            'slug' => 'controle-fiscal-aed-luxembourg-2026-preparation',
            'file' => 'controle-fiscal-aed-2026.html',
            'title' => 'Contrôle fiscal AED Luxembourg : comment s\'y préparer en 2026',
            'meta_title' => 'Contrôle fiscal AED Luxembourg 2026 : guide complet | faktur.lu',
            'meta_description' => 'Contrôle fiscal AED 2026 : types de contrôles, documents demandés, FAIA, sanctions (art. 77 LIVA : 250-10 000 €), recours sous 3 mois, Tribunal d\'arrondissement.',
        ],
        [
            'slug' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
            'file' => 'faia-luxembourg-2026.html',
            'title' => 'FAIA Luxembourg 2026 : qui est concerné et comment générer un fichier conforme',
            'meta_title' => 'FAIA Luxembourg 2026 : guide complet | faktur.lu',
            'meta_description' => 'Le FAIA (Fichier d\'Audit Informatisé AED) en 2026 : version 2.01, qui est tenu d\'en produire (PCN + CA > 112 k€), exemption franchise TVA 57bis, validation gratuite.',
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

        echo "  → Total batch 2 : {$totalUpdated} article(s) refondu(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les corrections sont conformes à la doctrine AED 2026.
    }
};
