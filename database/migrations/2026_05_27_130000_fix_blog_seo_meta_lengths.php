<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shorten meta_title and meta_description on blog posts where they exceed
 * Google's typical truncation limits (60 chars title, 160 chars description).
 *
 * Idempotent: re-running yields the same values.
 */
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            // ───── FR (9 existing articles + 5 June 2026) ─────
            ['fr', 'peppol-b2g-luxembourg-guide-complet-2026',
                'Peppol B2G Luxembourg : guide complet 2026 | faktur.lu',
                null],
            ['fr', 'franchise-tva-luxembourg-seuil-obligations-regime-normal',
                'Franchise TVA Luxembourg : seuil 35 000 EUR | faktur.lu',
                null],
            ['fr', 'choisir-logiciel-facturation-luxembourg-comparatif',
                'Logiciel facturation Luxembourg : comparatif 2026 | faktur.lu',
                null],
            ['fr', 'guide-complet-facturation-luxembourg-2026',
                'Facturation Luxembourg 2026 : guide complet | faktur.lu',
                null],
            ['fr', 'livre-des-recettes-luxembourg-obligations-modele',
                'Livre des recettes Luxembourg : obligations 2026 | faktur.lu',
                null],
            ['fr', 'controle-fiscal-aed-luxembourg-2026-preparation',
                'Contrôle fiscal AED Luxembourg 2026 : guide | faktur.lu',
                "Comment se préparer à un contrôle fiscal AED au Luxembourg : FAIA, documents, déroulement. Guide pratique 2026 pour freelances et PME."],
            ['fr', 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'FAIA Luxembourg 2026 : guide fichier audit AED | faktur.lu',
                null],
            ['fr', 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'Article 21 LIVA : autoliquidation B2B intra-UE | faktur.lu',
                "Vous facturez un client B2B en Europe ? L'article 21 LIVA impose l'autoliquidation. Règles, mentions, validation VIES en 2026."],
            ['fr', 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'TVA Luxembourg 2026 : les 4 taux expliqués | faktur.lu',
                "Quel taux de TVA au Luxembourg en 2026 ? Standard 17%, intermédiaire 14%, réduit 8%, super-réduit 3%. Guide avec exemples par secteur."],
            ['fr', 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'Article 61 LIVA : numérotation séquentielle | faktur.lu',
                "L'article 61 LIVA impose une numérotation continue de vos factures au Luxembourg. Trous, doublons, formats : comment rester conforme."],

            // ───── EN (4 June 2026 article titles) ─────
            ['en', 'article-21-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers',
                'Article 21 LIVA: B2B reverse charge Luxembourg | faktur.lu',
                null],
            ['en', 'article-61-liva-sequential-invoice-numbering-luxembourg-mandatory',
                'Article 61 LIVA: mandatory sequential numbering | faktur.lu',
                null],
            ['en', 'faia-luxembourg-standard-audit-file-complete-guide-2026',
                'FAIA Luxembourg 2026: AED audit file guide | faktur.lu',
                null],
            ['en', 'luxembourg-vat-2026-four-rates-17-14-8-3-explained',
                'Luxembourg VAT 2026: the 4 rates explained | faktur.lu',
                null],

            // ───── DE (5 June 2026 article titles) ─────
            ['de', 'artikel-21-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg',
                'Artikel 21 LIVA: B2B-Reverse-Charge EU Luxemburg | faktur.lu',
                null],
            ['de', 'artikel-61-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht',
                'Artikel 61 LIVA: sequenzielle Nummerierung | faktur.lu',
                null],
            ['de', 'aed-steuerpruefung-luxemburg-2026-vorbereitung',
                'AED-Steuerpruefung Luxemburg 2026: Leitfaden | faktur.lu',
                null],
            ['de', 'faia-luxemburg-vollstaendiger-leitfaden-pruefdatei-2026',
                'FAIA Luxemburg 2026: AED-Pruefdatei-Leitfaden | faktur.lu',
                null],
            ['de', 'luxemburgische-mwst-2026-vier-saetze-17-14-8-3-erklaert',
                'Luxemburgische MwSt 2026: die 4 Saetze | faktur.lu',
                null],

            // ───── LB (4 June 2026 article titles) ─────
            ['lb', 'artikel-21-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg',
                'Artikel 21 LIVA: Autoliquidatioun B2B EU | faktur.lu',
                null],
            ['lb', 'artikel-61-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch',
                'Artikel 61 LIVA: sequentiell Nummeréierung | faktur.lu',
                null],
            ['lb', 'aed-steierkontroll-letzebuerg-2026-virbereedung',
                'AED-Steierkontroll Lëtzebuerg 2026: Guide | faktur.lu',
                null],
            ['lb', 'letzebuerger-tva-2026-veier-satz-17-14-8-3-erklaert',
                'Lëtzebuerger TVA 2026: déi 4 Sätz | faktur.lu',
                null],

            // ───── PT (5 June 2026 article titles) ─────
            ['pt', 'artigo-21-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo',
                'Artigo 21 LIVA: autoliquidação B2B intra-UE | faktur.lu',
                null],
            ['pt', 'artigo-61-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria',
                'Artigo 61 LIVA: numeração sequencial | faktur.lu',
                null],
            ['pt', 'auditoria-fiscal-aed-luxemburgo-2026-preparacao',
                'Auditoria fiscal AED Luxemburgo 2026: guia | faktur.lu',
                null],
            ['pt', 'faia-luxemburgo-guia-completo-ficheiro-auditoria-2026',
                'FAIA Luxemburgo 2026: guia ficheiro auditoria | faktur.lu',
                null],
            ['pt', 'iva-luxemburgo-2026-quatro-taxas-17-14-8-3-explicadas',
                'IVA Luxemburgo 2026: as 4 taxas explicadas | faktur.lu',
                null],
        ];

        foreach ($updates as [$locale, $slug, $title, $desc]) {
            $data = ['meta_title' => $title];
            if ($desc !== null) {
                $data['meta_description'] = $desc;
            }
            DB::table('blog_posts')->where('locale', $locale)->where('slug', $slug)->update($data);
        }
    }

    public function down(): void
    {
        // No-op: reverting these values would lose the optimized SEO meta tags
        // and require restoring the long versions. If needed, restore from
        // a database backup or from the seeder files.
    }
};
