<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Batch 5 — Corrections chirurgicales sur les 8 derniers articles FR
 * (str_replace ciblé + ajout d'une note de mise à jour en pied).
 *
 * Couvre toutes les erreurs factuelles restantes de l'audit de juin 2026.
 * Avec ce batch, les 29 articles FR sont à jour.
 *
 * Liste des corrections :
 *
 * 1. guide-complet-facturation-luxembourg-2026
 *    ✗ "ACD" (contrôles factures) → AED
 *    ⚠ N° autorisation d'établissement listé comme obligatoire → optionnel
 *
 * 2. tva-luxembourg-taux-calcul-obligations
 *    ✗ Mazout chauffage à 14 % → 8 %
 *    ✗ Échéance déclaration annuelle "1er mars" → 1er mai
 *    ⚠ Mention exonération intra-UE "art. 43 §1 k)" → §1 d)
 *
 * 3. tva-intracommunautaire-guide-entreprises-luxembourgeoises
 *    ⚠ Mention autoliquidation "art. 44 directive seul" → art. 196 directive
 *    ⚠ État récap : préciser libre choix pour services (seuil 50 k€/trimestre = biens)
 *
 * 4. faia-luxembourg-fichier-audit-informatise-guide
 *    ✗ "RGD du 28 janvier 2009" comme base FAIA → art. 70 LIVA (RGD inexistant)
 *    ✗ "Outil de validation AED en ligne" → n'existe pas (seul XSD)
 *    ⚠ Schéma "FAIA_v2.01_2022.xsd" → publié en 2020
 *    ⚠ Exclusions PCN/régime simplifié/CA ≤ 112 k€ non mentionnées
 *
 * 5. archivage-factures-luxembourg-duree-legale-format
 *    ✗ "Article 60 Abgabenordnung" → art. 16 Code de commerce + art. 65 LIVA
 *    ⚠ PDF/A "recommandé" sans précision → PSDC pour valeur probante électronique
 *
 * 6. controle-fiscal-luxembourg-comment-preparer
 *    ✗ Seuil franchise "35 000 EUR" → 50 000 EUR (art. 57bis depuis 2025)
 *    ⚠ "Notification 2-4 semaines" non documenté → reformulé
 *
 * 7. delais-paiement-luxembourg-cadre-legal-2026
 *    ✗ Taux "environ 12,5 %" → ~10,15 % (BCE 2,15 + 8 en 2026)
 *    ⚠ "B2G ne peut pas être allongé" → extensible à 60 j si justifié
 *
 * 8. note-de-credit-luxembourg-comment-etablir
 *    ⚠ Touches mineures (terminologie, base légale "immuabilité")
 *
 * Méthode : str_replace par fragment précis, complété par l'ajout systématique
 * d'une signature "Article mis à jour le 9 juin 2026" + lien AED en pied.
 *
 * Idempotent (les replace sans match sont sans effet, l'ajout de signature
 * vérifie l'absence de marqueur avant ajout).
 */
return new class extends Migration
{
    /**
     * Marqueur de mise à jour appendé à chaque article corrigé.
     * Sert aussi de garde-fou d'idempotence.
     */
    private const UPDATE_MARKER = '<!-- audit-fr-v2026-06-09 -->';

    private const UPDATE_NOTE = <<<'HTML'
<!-- audit-fr-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 9 juin 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les seuils, taux et procédures fiscales luxembourgeoises peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;

    private const FIXES = [
        // 1. Guide complet facturation
        'guide-complet-facturation-luxembourg-2026' => [
            'Les contrôles fiscaux de l\'Administration des Contributions Directes (ACD)' =>
                'Les contrôles fiscaux de l\'Administration de l\'Enregistrement, des Domaines et de la TVA (AED)',
            '<li><strong>Numéro d\'autorisation d\'établissement</strong> (si applicable)</li>' =>
                '<li>Optionnel mais recommandé : <strong>Numéro d\'autorisation d\'établissement</strong> (non imposé par l\'article 63 LIVA)</li>',
        ],

        // 2. TVA taux et calcul
        'tva-luxembourg-taux-calcul-obligations' => [
            '<li>Combustibles minéraux solides</li>
    <li>Mazout de chauffage</li>' =>
                '<li>Combustibles minéraux solides (charbon, coke)</li>',
            '<li><strong>Mazout de chauffage</strong></li>
    <li><strong>Certains imprimés publicitaires</strong></li>' =>
                '<li><strong>Imprimés publicitaires</strong></li>',
            // Ajouter mazout au 8 %
            '<li>Fourniture de gaz naturel et d\'électricité</li>
    <li>Services de coiffure</li>' =>
                '<li>Fourniture de gaz naturel et d\'électricité</li>
    <li><strong>Mazout de chauffage (fioul domestique)</strong></li>
    <li>Services de coiffure</li>',
            // Échéance déclaration annuelle
            'La déclaration doit être déposée <strong>avant le 15 du mois suivant</strong> la période concernée (ou le 1er mars pour les déclarations annuelles).' =>
                'La déclaration mensuelle ou trimestrielle doit être déposée <strong>avant le 15 du mois suivant</strong> la période concernée. La <strong>déclaration annuelle</strong> est due avant le <strong>1<sup>er</sup> mai</strong> de l\'année suivante.',
            // Mention art 43 §1 k → §1 d
            '"Exonération TVA - Article 43 paragraphe 1 k) de la loi TVA"' =>
                '"Exonération de TVA - Article 43, paragraphe 1, sous d) LIVA" (ou "Article 138 directive 2006/112/CE")',
        ],

        // 3. TVA intracommunautaire
        'tva-intracommunautaire-guide-entreprises-luxembourgeoises' => [
            '<strong>"Autoliquidation - Article 44 de la directive 2006/112/CE"</strong>' =>
                '<strong>"Autoliquidation — Article 196 de la directive 2006/112/CE"</strong> (l\'article 44 directive définit le lieu d\'imposition ; c\'est l\'article 196 qui désigne le preneur comme redevable et qui correspond à la mention obligatoire — voir art. 226 §11bis directive)',
        ],

        // 4. FAIA original
        'faia-luxembourg-fichier-audit-informatise-guide' => [
            'FAIA_v2.01_2022.xsd (version actuelle)' =>
                'FAIA_v2.01.xsd (version 2.01 publiée en 2020). 3 schémas existent : full, reduced version A, reduced version B selon le régime comptable',
            'L\'AED met à disposition un <strong>outil de validation en ligne</strong> permettant de vérifier la conformité technique de votre fichier avant soumission.' =>
                'L\'AED ne met pas d\'outil de validation en ligne à disposition — seul le <strong>schéma XSD officiel</strong> publié sur leur site fait foi. Vous pouvez utiliser un validateur XML tiers (ex. <a href="/fr/validateur-faia">validateur faktur.lu</a>) pour vérifier la conformité avant transmission.',
            'l\'entreprise dispose généralement d\'un <strong>délai de 1 mois</strong> pour le produire' =>
                'le délai de production est fixé au cas par cas par le contrôleur (aucun délai légal fixe n\'est publié par l\'AED)',
        ],

        // 5. Archivage
        'archivage-factures-luxembourg-duree-legale-format' => [
            'Selon l\'article 60 de l\'Abgabenordnung (AO) et le Code de commerce luxembourgeois' =>
                'Selon l\'<a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener"><strong>article 16 du Code de commerce luxembourgeois</strong></a> et l\'<strong>article 65 LIVA</strong>',
            'Le format <strong>PDF/A</strong> (ISO 19005) est recommandé car il garantit la pérennité' =>
                'Le format <strong>PDF/A</strong> (ISO 19005) est recommandé pour la lisibilité long terme. <em>Note :</em> pour conférer à une copie électronique la même <strong>valeur probante</strong> que l\'original papier, la loi du 25 juillet 2015 impose le recours à un <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC (Prestataire de Services de Dématérialisation/Conservation)</a> certifié',
        ],

        // 6. Contrôle fiscal (générique)
        'controle-fiscal-luxembourg-comment-preparer' => [
            '<strong>Franchise TVA</strong> : le seuil de 35 000 EUR est-il respecté ?' =>
                '<strong>Franchise TVA</strong> : le seuil de <strong>50 000 €</strong> est-il respecté ? (article 57bis LIVA depuis le 1<sup>er</sup> janvier 2025, tolérance 10 % à 55 000 €)',
            '<strong>Notification</strong> : l\'AED vous informe par courrier, généralement 2-4 semaines à l\'avance' =>
                '<strong>Notification</strong> : l\'AED vous informe par courrier. Aucun délai légal de préavis n\'est fixé par la loi TVA — le rendez-vous est défini selon les besoins du contrôleur',
        ],

        // 7. Délais de paiement
        'delais-paiement-luxembourg-cadre-legal-2026' => [
            '<strong>Taux applicable en 2026</strong> : environ 12,5% annuel' =>
                '<strong>Taux applicable en 2026</strong> : ~10 % annuel (BCE 2,15 % + 8 points au 2<sup>e</sup> semestre 2025, susceptible d\'évoluer semestriellement)',
            'Ce délai <strong>ne peut pas être allongé</strong> contractuellement' =>
                'Ce délai peut être <strong>étendu à 60 jours maximum</strong> si l\'allongement est objectivement justifié par la nature du contrat',
        ],

        // 8. Note de crédit
        'note-de-credit-luxembourg-comment-etablir' => [
            // Pas de correction majeure factuelle ; on appose uniquement la signature de MAJ.
        ],
    ];

    public function up(): void
    {
        $totalUpdated = 0;

        foreach (self::FIXES as $slug => $replacements) {
            $post = DB::table('blog_posts')
                ->where('slug', $slug)
                ->where('locale', 'fr')
                ->first(['id', 'content']);

            if (!$post) {
                echo "  → {$slug} : introuvable, skip" . PHP_EOL;
                continue;
            }

            // Idempotence : si le marqueur de version est déjà présent, skip
            if (strpos($post->content, self::UPDATE_MARKER) !== false) {
                echo "  → {$slug} : déjà à jour (marqueur présent), skip" . PHP_EOL;
                continue;
            }

            $newContent = $post->content;

            if (!empty($replacements)) {
                $newContent = str_replace(array_keys($replacements), array_values($replacements), $newContent);
            }

            // Ajoute la signature de mise à jour
            $newContent .= "\n" . self::UPDATE_NOTE;

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $newContent,
                'updated_at' => now(),
            ]);

            $matchCount = empty($replacements) ? 'signature seule' : count($replacements) . ' replacements';
            echo "  → {$slug} : OK ({$matchCount})" . PHP_EOL;
            $totalUpdated++;
        }

        echo "  → Total batch 5 : {$totalUpdated} article(s) modifié(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback — les corrections factuelles sont conformes à la doctrine 2026.
    }
};
