<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte batch 4 — Peppol + 5 articles pratiques freelance (rewrites complets),
 * 3 articles à corrections chirurgicales (str_replace ciblé).
 *
 * REWRITES COMPLETS (5) :
 *
 * 1. peppol-b2g-luxembourg-guide-complet-2026
 *    ✗ Seuil 30 000 € marchés publics → aucun seuil (loi 13/12/2021)
 *    ✗ Schéma Peppol LU "0184" → 9938 (LU:VAT)
 *    ✗ ViDA "2028" → 1er juillet 2030 (B2B intra-UE)
 *    ✗ "Plus de 35 pays" → 100+ pays
 *    + Calendrier B2G échelonné (18 mai 2022 / 18 oct 2022 / 18 mars 2023)
 *    + Canal alternatif MyGuichet.lu mentionné
 *    + Format XRechnung 3.0.1 mentionné
 *
 * 2. freelance-luxembourg-facturer-conformite
 *    ✗ Mention B2B intra-UE "Art. 44 loi 12/02/1979" → "Art. 196 directive 2006/112/CE"
 *    + Encadré base légale correcte (17 LIVA vs 44 directive vs 196 directive)
 *    + Seuil franchise 50 000 € mis en avant
 *
 * 3. relancer-client-impaye-luxembourg
 *    ✗ "Le taux d'intérêt est environ 12 % en 2026" → ~10,15 % (BCE 2,15 + 8)
 *    ✗ Chiffre "30 % des factures payées en retard" non sourcé → retiré
 *    + Précision : mise en demeure non requise pour faire courir les intérêts
 *
 * 4. factur-x-zugferd-facturation-electronique-europeenne
 *    ✗ ViDA "2028-2030" → 1er juillet 2030 précis
 *    + Calendrier français e-invoicing précisé (1er sept 2026 réception, 1er sept 2027 TPE/PME émission)
 *    + Allemagne : obligation réception depuis 1er janvier 2025
 *
 * 5. facturer-etranger-depuis-luxembourg
 *    ✗ Mention B2B intra-UE "Article 44 directive" → "Article 196"
 *    + Nuance B2C UE (services classiques par défaut LU, mais exceptions immobilier/transport/événementiel)
 *    + Mention export hors UE plus précise
 *
 * CORRECTIONS CHIRURGICALES (3) via str_replace :
 *
 * 6. choisir-logiciel-facturation-luxembourg-comparatif
 *    ✗ "seuil de 35 000 EUR" → "seuil de 50 000 EUR" (franchise 2026)
 *
 * 7. excel-vs-logiciel-facturation-pourquoi-switch
 *    ✗ Chiffre "40 % des freelances facturent avec un tableur" non sourcé → reformulé
 *    ✗ Chiffre "5 heures par mois" → reformulé en "plusieurs heures"
 *
 * 8. 5-erreurs-frequentes-facture-freelance-luxembourg
 *    ✗ Chiffre "Plus de 60 % des factures freelance contiennent au moins une erreur" → retiré
 *
 * Le 9e article du batch (automatiser-facturation-7-conseils-gagner-temps)
 * ne contient pas d'erreur factuelle critique selon l'audit — non modifié.
 *
 * Idempotent.
 */
return new class extends Migration
{
    private const REWRITES = [
        [
            'slug' => 'peppol-b2g-luxembourg-guide-complet-2026',
            'file' => 'peppol-b2g-luxembourg-2026.html',
            'title' => 'Peppol B2G au Luxembourg : guide complet 2026',
            'meta_title' => 'Peppol B2G Luxembourg 2026 : guide complet | faktur.lu',
            'meta_description' => 'Peppol au Luxembourg en 2026 : obligation B2G depuis 2022-2023 sans seuil de montant, schéma 9938 (LU:VAT), format BIS Billing 3.0, ViDA B2B intra-UE au 1er juillet 2030.',
        ],
        [
            'slug' => 'freelance-luxembourg-facturer-conformite',
            'file' => 'freelance-luxembourg-facturer-conformite.html',
            'title' => 'Freelance au Luxembourg : comment facturer en toute conformité (2026)',
            'meta_title' => 'Freelance Luxembourg : facturer en conformité 2026 | faktur.lu',
            'meta_description' => 'Guide complet 2026 pour facturer en tant que freelance au Luxembourg : statut, immatriculation TVA, mentions (art. 63 LIVA), autoliquidation B2B intra-UE (art. 196 directive), franchise 50 000 €.',
        ],
        [
            'slug' => 'relancer-client-impaye-luxembourg',
            'file' => 'relancer-client-impaye-luxembourg.html',
            'title' => 'Comment relancer un client qui ne paie pas au Luxembourg (procédure 2026)',
            'meta_title' => 'Relancer un client impayé Luxembourg : procédure 2026 | faktur.lu',
            'meta_description' => 'Procédure de relance et recouvrement au Luxembourg : rappel amical, mise en demeure, intérêts de retard (taux BCE + 8 points ≈ 10 % en 2026), indemnité 40 €, injonction de payer.',
        ],
        [
            'slug' => 'factur-x-zugferd-facturation-electronique-europeenne',
            'file' => 'factur-x-zugferd-facturation-electronique.html',
            'title' => 'Factur-X / ZUGFeRD : la facturation électronique européenne expliquée (2026)',
            'meta_title' => 'Factur-X / ZUGFeRD : guide 2026 facturation électronique | faktur.lu',
            'meta_description' => 'Factur-X et ZUGFeRD en 2026 : format hybride PDF/A-3 + XML, profils (EN 16931), calendrier ViDA (1er juillet 2030 pour B2B intra-UE), France (1er sept 2026/2027).',
        ],
        [
            'slug' => 'facturer-etranger-depuis-luxembourg',
            'file' => 'facturer-etranger-depuis-luxembourg.html',
            'title' => 'Comment facturer à l\'étranger depuis le Luxembourg (guide TVA 2026)',
            'meta_title' => 'Facturer à l\'étranger depuis Luxembourg : guide TVA 2026 | faktur.lu',
            'meta_description' => 'Règles TVA pour facturer à l\'étranger depuis le Luxembourg : B2B intra-UE (autoliquidation art. 196), B2C UE (OSS 10 000 €), export hors UE, cas spécial Suisse. Tableau récapitulatif.',
        ],
    ];

    public function up(): void
    {
        // === Partie 1 : 5 rewrites complets ===
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

            echo "  → {$rewrite['slug']} : refonte {$updated} OK" . PHP_EOL;
            $totalUpdated += $updated;
        }

        // === Partie 2 : 3 corrections chirurgicales (str_replace) ===
        // Patterns prenant en compte la mise en forme HTML interne (<strong>, etc.)
        $surgicalFixes = [
            'choisir-logiciel-facturation-luxembourg-comparatif' => [
                'seuil de 35 000 EUR' => 'seuil de 50 000 EUR (article 57bis LIVA depuis 2025)',
            ],
            'excel-vs-logiciel-facturation-pourquoi-switch' => [
                '40% des freelances et micro-entreprises</strong> au Luxembourg facturent avec un tableur'
                    => 'beaucoup de freelances et micro-entreprises</strong> au Luxembourg facturent encore avec un tableur',
                '<strong>5 heures par mois</strong>' => '<strong>plusieurs heures par mois</strong>',
                'en moyenne 5 heures par mois' => 'plusieurs heures par mois',
            ],
            '5-erreurs-frequentes-facture-freelance-luxembourg' => [
                'plus de <strong>60%</strong>' => '<strong>de nombreuses</strong>',
                'plus de <strong>60 %</strong>' => '<strong>de nombreuses</strong>',
                '60% des factures freelance contiennent au moins une erreur'
                    => 'de nombreuses factures freelance contiennent au moins une erreur',
                '60 % des factures freelance contiennent au moins une erreur'
                    => 'de nombreuses factures freelance contiennent au moins une erreur',
            ],
        ];

        foreach ($surgicalFixes as $slug => $replacements) {
            $post = DB::table('blog_posts')
                ->where('slug', $slug)
                ->where('locale', 'fr')
                ->first(['id', 'content']);

            if (!$post) {
                echo "  → {$slug} : article introuvable, skip" . PHP_EOL;
                continue;
            }

            $newContent = str_replace(array_keys($replacements), array_values($replacements), $post->content);

            if ($newContent !== $post->content) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);
                echo "  → {$slug} : fix chirurgical OK" . PHP_EOL;
                $totalUpdated++;
            } else {
                echo "  → {$slug} : déjà corrigé, skip" . PHP_EOL;
            }
        }

        echo "  → Total batch 4 : {$totalUpdated} article(s) modifié(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les corrections factuelles sont conformes à l'audit.
    }
};
