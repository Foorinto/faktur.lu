<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shorten meta_title / meta_description on the existing EN/DE/LB/PT blog
 * translations (the versions of the 9 original FR articles) that exceeded
 * Google's truncation limits.
 *
 * Idempotent: re-running yields the same values. No-op down().
 */
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            // ───── EN (3 titles) ─────
            ['en', 'vat-exemption-luxembourg-threshold-obligations-normal-regime',
                'VAT Exemption Luxembourg: EUR 35,000 Threshold | faktur.lu', null],
            ['en', 'complete-guide-invoicing-luxembourg-2026',
                'Luxembourg Invoicing 2026: Complete Guide | faktur.lu', null],
            ['en', 'revenue-book-luxembourg-obligations-template',
                'Revenue Book Luxembourg: Obligations & Template | faktur.lu', null],

            // ───── DE (8 titles + 2 descriptions) ─────
            ['de', 'freiberufler-luxemburg-konform-fakturieren',
                'Freiberufler Luxemburg: konform fakturieren | faktur.lu', null],
            ['de', 'steuerpruefung-luxemburg-vorbereiten',
                'Steuerprüfung Luxemburg: Vorbereitung | faktur.lu', null],
            ['de', 'innergemeinschaftliche-mwst-leitfaden-luxemburgische-unternehmen',
                'Innergemeinschaftliche MwSt Luxemburg: Leitfaden | faktur.lu', null],
            ['de', 'mwst-befreiung-luxemburg-schwelle-pflichten-normalregime',
                'MwSt-Befreiung Luxemburg: 35.000 EUR Schwelle | faktur.lu', null],
            ['de', 'faia-luxemburg-informatisierte-audit-datei-leitfaden',
                'FAIA Luxemburg: Leitfaden zur Audit-Datei | faktur.lu', null],
            ['de', 'rechnungsarchivierung-luxemburg-gesetzliche-dauer-format',
                'Rechnungsarchivierung Luxemburg: Dauer & Format | faktur.lu', null],
            ['de', 'vollstaendiger-leitfaden-rechnungsstellung-luxemburg-2026',
                'Rechnungsstellung Luxemburg 2026: Leitfaden | faktur.lu', null],
            ['de', 'einnahmenbuch-luxemburg-pflichten-vorlage',
                'Einnahmenbuch Luxemburg: Pflichten & Vorlage | faktur.lu', null],
            ['de', 'einzelunternehmen-luxemburg-gruenden-leitfaden-2026',
                null,
                'Leitfaden zur Gründung eines Einzelunternehmens in Luxemburg: Schritte, Kosten (100-150€), Fristen, MwSt.-Pflichten und Sozialbeiträge.'],
            ['de', 'mwst-luxemburg-saetze-berechnung-pflichten',
                null,
                'Leitfaden zur MwSt. in Luxemburg: Normalsatz 17%, ermäßigte Sätze, Berechnung und vierteljährliche Erklärungen für Ihr Unternehmen.'],

            // ───── LB (6 titles + 1 description) ─────
            ['lb', 'peppol-b2g-letzebuerg-komplette-guide-2026',
                'Peppol B2G Lëtzebuerg: komplette Guide 2026 | faktur.lu', null],
            ['lb', 'innergemeinschaftlech-tva-guide-letzebuergesch-entreprisen',
                'Innergemeinschaftlech TVA Lëtzebuerg: Guide | faktur.lu', null],
            ['lb', 'faia-letzebuerg-informatiseierte-audit-fichier-guide',
                'FAIA Lëtzebuerg: Guide zum Audit-Fichier | faktur.lu', null],
            ['lb', 'rechnungsarchiveierung-letzebuerg-gesetzlech-dauer-format',
                'Rechnungsarchivéierung Lëtzebuerg: Dauer a Format | faktur.lu', null],
            ['lb', 'komplette-guide-rechnungsstellung-letzebuerg-2026',
                'Rechnungsstellung Lëtzebuerg 2026: Guide | faktur.lu', null],
            ['lb', 'akommessbuch-letzebuerg-obligatiounen-modell',
                'Akommessbuch Lëtzebuerg: Obligatiounen a Modell | faktur.lu', null],
            ['lb', 'tva-letzebuerg-tariffer-berechnung-obligatiounen',
                null,
                "Guide iwwer d'TVA zu Lëtzebuerg: Normaltarif 17%, reduzéiert Tariffer, Berechnung an trimesteriell Deklaratiounen fir Är Entreprise."],

            // ───── PT (3 titles) ─────
            ['pt', 'faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado',
                'FAIA Luxemburgo: guia do ficheiro de auditoria | faktur.lu', null],
            ['pt', 'como-escolher-o-seu-software-de-faturacao-no-luxemburgo',
                'Software de faturação Luxemburgo: como escolher | faktur.lu', null],
            ['pt', 'como-faturar-para-o-estrangeiro-a-partir-do-luxemburgo',
                'Faturar para o estrangeiro: guia IVA Luxemburgo | faktur.lu', null],
        ];

        foreach ($updates as [$locale, $slug, $title, $desc]) {
            $data = [];
            if ($title !== null) {
                $data['meta_title'] = $title;
            }
            if ($desc !== null) {
                $data['meta_description'] = $desc;
            }
            if ($data) {
                DB::table('blog_posts')->where('locale', $locale)->where('slug', $slug)->update($data);
            }
        }
    }

    public function down(): void
    {
        // No-op: reverting would restore truncated-prone long meta tags.
    }
};
