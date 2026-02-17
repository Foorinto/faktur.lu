<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = $this->getTranslations();

        foreach ($translations as $translation) {
            BlogPost::create($translation);
        }

        $this->command->info('Created ' . count($translations) . ' blog post translations.');
    }

    /**
     * Get all translations.
     */
    private function getTranslations(): array
    {
        return array_merge(
            $this->getArticle1Translations(),
            $this->getArticle2Translations(),
            $this->getArticle3Translations(),
            $this->getArticle4Translations(),
            $this->getArticle5Translations(),
            $this->getArticle6Translations(),
            $this->getArticle7Translations(),
            $this->getArticle8Translations(),
            $this->getArticle9Translations(),
        );
    }

    /**
     * Article 1: Guide complet de la facturation au Luxembourg
     */
    private function getArticle1Translations(): array
    {
        $baseData = [
            'category_id' => 1,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Vollständiger Leitfaden zur Rechnungsstellung in Luxemburg 2026',
                'slug' => 'vollstaendiger-leitfaden-rechnungsstellung-luxemburg-2026',
                'excerpt' => 'Entdecken Sie alle Rechnungsstellungsregeln in Luxemburg: Pflichtangaben, Nummerierung, MwSt., Aufbewahrung von Dokumenten. Der Referenzleitfaden für Unternehmen und Freiberufler.',
                'meta_title' => 'Rechnungsstellung Luxemburg 2026: Vollständiger Leitfaden der Regeln und Pflichten',
                'meta_description' => 'Vollständiger Leitfaden zur Rechnungsstellung in Luxemburg 2026. Pflichtangaben, MwSt., Nummerierung, FAIA: alles, was Sie wissen müssen, um konform zu fakturieren.',
                'content' => $this->getArticle1ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Complete Guide to Invoicing in Luxembourg 2026',
                'slug' => 'complete-guide-invoicing-luxembourg-2026',
                'excerpt' => 'Discover all invoicing rules in Luxembourg: mandatory information, numbering, VAT, document retention. The reference guide for businesses and freelancers.',
                'meta_title' => 'Luxembourg Invoicing 2026: Complete Guide to Rules and Obligations',
                'meta_description' => 'Complete guide to invoicing in Luxembourg in 2026. Mandatory mentions, VAT, numbering, FAIA: everything you need to know to invoice in compliance.',
                'content' => $this->getArticle1ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Komplette Guide fir d\'Rechnungsstellung zu Lëtzebuerg 2026',
                'slug' => 'komplette-guide-rechnungsstellung-letzebuerg-2026',
                'excerpt' => 'Entdeckt all Rechnungsstellungsreegelen zu Lëtzebuerg: Pflichtinformatiounen, Nummeréierung, TVA, Dokumenter-Archivéierung. De Referenzguide fir Entreprisen a Freelanceren.',
                'meta_title' => 'Rechnungsstellung Lëtzebuerg 2026: Komplette Guide vun de Reegelen an Obligatiounen',
                'meta_description' => 'Komplette Guide fir d\'Rechnungsstellung zu Lëtzebuerg an 2026. Pflichtmentiounen, TVA, Nummeréierung, FAIA: alles wat Dir wësse musst fir konform ze fakturéieren.',
                'content' => $this->getArticle1ContentLB(),
            ]),
        ];
    }

    /**
     * Article 2: FAIA Luxembourg
     */
    private function getArticle2Translations(): array
    {
        $baseData = [
            'category_id' => 2,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'FAIA Luxemburg: Alles über die informatisierte Audit-Datei',
                'slug' => 'faia-luxemburg-informatisierte-audit-datei-leitfaden',
                'excerpt' => 'Die FAIA (Fichier d\'Audit Informatisé) ist in Luxemburg Pflicht. Erfahren Sie, was sie enthält, wer sie erstellen muss und wie Sie eine konforme FAIA-Datei generieren.',
                'meta_title' => 'FAIA Luxemburg: Vollständiger Leitfaden zur informatisierten Audit-Datei',
                'meta_description' => 'Alles über FAIA in Luxemburg: Definition, Pflichten, Dateiinhalt, wie man sie generiert. Praktischer Leitfaden zur Einhaltung der AED-Vorschriften.',
                'content' => $this->getArticle2ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'FAIA Luxembourg: Everything About the Computerized Audit File',
                'slug' => 'faia-luxembourg-computerized-audit-file-guide',
                'excerpt' => 'The FAIA (Fichier d\'Audit Informatisé) is mandatory in Luxembourg. Discover what it contains, who must produce it, and how to generate a compliant FAIA file.',
                'meta_title' => 'FAIA Luxembourg: Complete Guide to the Computerized Audit File',
                'meta_description' => 'Everything about FAIA in Luxembourg: definition, obligations, file contents, how to generate it. Practical guide for AED compliance.',
                'content' => $this->getArticle2ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'FAIA Lëtzebuerg: Alles iwwer de informatiséierte Audit-Fichier',
                'slug' => 'faia-letzebuerg-informatiseierte-audit-fichier-guide',
                'excerpt' => 'De FAIA (Fichier d\'Audit Informatisé) ass zu Lëtzebuerg obligatoresch. Entdeckt wat en enthält, wien en muss produzéieren, a wéi een e konforme FAIA-Fichier generéiert.',
                'meta_title' => 'FAIA Lëtzebuerg: Komplette Guide zum informatiséierte Audit-Fichier',
                'meta_description' => 'Alles iwwer FAIA zu Lëtzebuerg: Definitioun, Obligatiounen, Fichier-Inhalt, wéi een en generéiert. Prakteschen Guide fir AED-Konformitéit.',
                'content' => $this->getArticle2ContentLB(),
            ]),
        ];
    }

    /**
     * Article 3: TVA au Luxembourg
     */
    private function getArticle3Translations(): array
    {
        $baseData = [
            'category_id' => 2,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'MwSt. in Luxemburg: Sätze, Berechnung und Pflichten für Unternehmen',
                'slug' => 'mwst-luxemburg-saetze-berechnung-pflichten',
                'excerpt' => 'Beherrschen Sie die luxemburgische MwSt.: die verschiedenen Sätze (17%, 14%, 8%, 3%), Berechnung, Erklärungen und Befreiungsfälle. Vollständiger Leitfaden für Unternehmen.',
                'meta_title' => 'MwSt. Luxemburg 2026: Sätze, Berechnung und Steuerpflichten',
                'meta_description' => 'Vollständiger Leitfaden zur MwSt. in Luxemburg: Normalsatz 17%, ermäßigte Sätze, Berechnung, vierteljährliche Erklärungen. Alles für die MwSt.-Verwaltung Ihres Unternehmens.',
                'content' => $this->getArticle3ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'VAT in Luxembourg: Rates, Calculation and Obligations for Businesses',
                'slug' => 'vat-luxembourg-rates-calculation-obligations',
                'excerpt' => 'Master Luxembourg VAT: the different rates (17%, 14%, 8%, 3%), calculation, declarations, and exemption cases. Complete guide for businesses.',
                'meta_title' => 'VAT Luxembourg 2026: Rates, Calculation and Tax Obligations',
                'meta_description' => 'Complete guide to VAT in Luxembourg: standard rate 17%, reduced rates, calculation, quarterly declarations. Everything for managing your business VAT.',
                'content' => $this->getArticle3ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'TVA zu Lëtzebuerg: Tariffer, Berechnung an Obligatiounen fir Entreprisen',
                'slug' => 'tva-letzebuerg-tariffer-berechnung-obligatiounen',
                'excerpt' => 'Beherrscht d\'Lëtzebuerger TVA: déi verschidden Tariffer (17%, 14%, 8%, 3%), Berechnung, Deklaratiounen, an Exonératiounsfäll. Komplette Guide fir Entreprisen.',
                'meta_title' => 'TVA Lëtzebuerg 2026: Tariffer, Berechnung a Steierflichten',
                'meta_description' => 'Komplette Guide iwwer d\'TVA zu Lëtzebuerg: Normaltarif 17%, reduzéiert Tariffer, Berechnung, trimesteriell Deklaratiounen. Alles fir d\'TVA-Gestioun vun Ärer Entreprise.',
                'content' => $this->getArticle3ContentLB(),
            ]),
        ];
    }

    /**
     * Article 4: Freelance au Luxembourg
     */
    private function getArticle4Translations(): array
    {
        $baseData = [
            'category_id' => 3,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Freiberufler in Luxemburg: konform fakturieren',
                'slug' => 'freiberufler-luxemburg-konform-fakturieren',
                'excerpt' => 'Sie sind Freiberufler in Luxemburg? Erfahren Sie, wie Sie konforme Rechnungen erstellen, die MwSt. verwalten und alle luxemburgischen gesetzlichen Pflichten erfüllen.',
                'meta_title' => 'Freiberufler Luxemburg: Leitfaden zur konformen Rechnungsstellung 2026',
                'meta_description' => 'Vollständiger Leitfaden für Freiberufler in Luxemburg: konforme Rechnungen erstellen, MwSt. verwalten, FAIA-Pflichten. Alles für legales Fakturieren.',
                'content' => $this->getArticle4ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Freelancer in Luxembourg: How to Invoice in Compliance',
                'slug' => 'freelancer-luxembourg-invoice-compliance',
                'excerpt' => 'Are you a freelancer in Luxembourg? Discover how to create compliant invoices, manage VAT, and meet all Luxembourg legal obligations.',
                'meta_title' => 'Freelancer Luxembourg: Compliant Invoicing Guide 2026',
                'meta_description' => 'Complete guide for freelancers in Luxembourg: create compliant invoices, manage VAT, FAIA obligations. Everything for legal invoicing.',
                'content' => $this->getArticle4ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Freelancer zu Lëtzebuerg: wéi fakturéiert een konform',
                'slug' => 'freelancer-letzebuerg-konform-fakturieren',
                'excerpt' => 'Dir sidd Freelancer zu Lëtzebuerg? Entdeckt wéi Dir konform Rechnungen erstellt, d\'TVA verwaltt, an all Lëtzebuerger legal Obligatiounen erfëllt.',
                'meta_title' => 'Freelancer Lëtzebuerg: Guide fir konform Rechnungsstellung 2026',
                'meta_description' => 'Komplette Guide fir Freelanceren zu Lëtzebuerg: konform Rechnungen erstellen, TVA verwalten, FAIA-Obligatiounen. Alles fir legal ze fakturéieren.',
                'content' => $this->getArticle4ContentLB(),
            ]),
        ];
    }

    /**
     * Article 5: Mentions obligatoires sur une facture
     */
    private function getArticle5Translations(): array
    {
        $baseData = [
            'category_id' => 1,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Pflichtangaben auf einer Rechnung in Luxemburg: Vollständige Checkliste',
                'slug' => 'pflichtangaben-rechnung-luxemburg',
                'excerpt' => 'Welche Pflichtangaben müssen auf einer luxemburgischen Rechnung stehen? Entdecken Sie die vollständige Checkliste für gesetzeskonforme Rechnungen.',
                'meta_title' => 'Pflichtangaben Rechnung Luxemburg: Checkliste 2026',
                'meta_description' => 'Vollständige Liste der Pflichtangaben auf einer Rechnung in Luxemburg. Praktische Checkliste für konforme Rechnungen und zur Vermeidung von Sanktionen.',
                'content' => $this->getArticle5ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Mandatory Information on a Luxembourg Invoice: Complete Checklist',
                'slug' => 'mandatory-information-invoice-luxembourg',
                'excerpt' => 'What mandatory information must appear on a Luxembourg invoice? Discover the complete checklist for creating legally compliant invoices.',
                'meta_title' => 'Mandatory Invoice Information Luxembourg: Checklist 2026',
                'meta_description' => 'Complete list of mandatory information on a Luxembourg invoice. Practical checklist for creating compliant invoices and avoiding penalties.',
                'content' => $this->getArticle5ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Pflichtinformatiounen op enger Rechnung zu Lëtzebuerg: Komplett Checklëscht',
                'slug' => 'pflichtinformatiounen-rechnung-letzebuerg',
                'excerpt' => 'Wéi eng Pflichtinformatiounen musse op enger Lëtzebuerger Rechnung stoen? Entdeckt d\'komplett Checklëscht fir gesetzskonform Rechnungen ze erstellen.',
                'meta_title' => 'Pflichtangaben Rechnung Lëtzebuerg: Checklëscht 2026',
                'meta_description' => 'Komplett Lëscht vun de Pflichtangaben op enger Rechnung zu Lëtzebuerg. Praktescht Checklëscht fir konform Rechnungen a fir Sanktiounen ze vermeiden.',
                'content' => $this->getArticle5ContentLB(),
            ]),
        ];
    }

    /**
     * Article 6: Créer une entreprise individuelle au Luxembourg
     */
    private function getArticle6Translations(): array
    {
        $baseData = [
            'category_id' => 5,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Einzelunternehmen in Luxemburg gründen: Vollständiger Leitfaden 2025',
                'slug' => 'einzelunternehmen-luxemburg-gruenden-leitfaden-2025',
                'excerpt' => 'Entdecken Sie alle Schritte zur Gründung Ihres Einzelunternehmens in Luxemburg: Niederlassungsgenehmigung, RCS-Registrierung, Sozialversicherungsbeiträge und steuerliche Pflichten.',
                'meta_title' => 'Einzelunternehmen in Luxemburg gründen | Leitfaden 2025',
                'meta_description' => 'Vollständiger Leitfaden zur Gründung eines Einzelunternehmens in Luxemburg: Schritte, Kosten (100-150€), Fristen (1-3 Monate), MwSt.-Pflichten und Sozialversicherungsbeiträge.',
                'content' => $this->getArticle6ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Starting a Sole Proprietorship in Luxembourg: Complete Guide 2025',
                'slug' => 'sole-proprietorship-luxembourg-guide-2025',
                'excerpt' => 'Discover all the steps to create your sole proprietorship in Luxembourg: establishment authorization, RCS registration, social contributions and tax obligations.',
                'meta_title' => 'Start a Sole Proprietorship in Luxembourg | Guide 2025',
                'meta_description' => 'Complete guide to starting a sole proprietorship in Luxembourg: procedures, costs (€100-150), timelines (1-3 months), VAT obligations and social contributions.',
                'content' => $this->getArticle6ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Eenzelentreprise zu Lëtzebuerg grënnen: Komplette Guide 2025',
                'slug' => 'eenzelentreprise-letzebuerg-grenden-guide-2025',
                'excerpt' => 'Entdeckt all Schrëtt fir Är Eenzelentreprise zu Lëtzebuerg ze grënnen: Etabléierungsautoriséierung, RCS-Aschreiwung, Sozialversécherungsbäiträg an Steierflichten.',
                'meta_title' => 'Eenzelentreprise zu Lëtzebuerg grënnen | Guide 2025',
                'meta_description' => 'Komplette Guide fir eng Eenzelentreprise zu Lëtzebuerg ze grënnen: Démarchen, Käschten (100-150€), Delaien (1-3 Méint), TVA-Obligatiounen a Sozialbäiträg.',
                'content' => $this->getArticle6ContentLB(),
            ]),
        ];
    }

    /**
     * Article 7: Créer une entreprise individuelle en France
     */
    private function getArticle7Translations(): array
    {
        $baseData = [
            'category_id' => 5,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Einzelunternehmen in Frankreich gründen: Vollständiger Leitfaden 2025',
                'slug' => 'einzelunternehmen-frankreich-gruenden-leitfaden-2025',
                'excerpt' => 'Alles Wissenswerte zur Gründung Ihres Einzelunternehmens oder Micro-Entreprise in Frankreich: Schritte über INPI, Steuersystem, URSSAF-Beiträge und Pflichten.',
                'meta_title' => 'Einzelunternehmen in Frankreich gründen | Leitfaden 2025',
                'meta_description' => 'Vollständiger Leitfaden zur Gründung eines Einzelunternehmens in Frankreich: kostenlose Micro-Entreprise, INPI-Schalter, SIRET in 1-2 Wochen, Beiträge 12-25%.',
                'content' => $this->getArticle7ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Starting a Sole Proprietorship in France: Complete Guide 2025',
                'slug' => 'sole-proprietorship-france-guide-2025',
                'excerpt' => 'Everything you need to know to create your sole proprietorship or micro-enterprise in France: INPI procedures, tax regime, URSSAF contributions and obligations.',
                'meta_title' => 'Start a Sole Proprietorship in France | Guide 2025',
                'meta_description' => 'Complete guide to creating a sole proprietorship in France: free micro-enterprise, INPI portal, SIRET in 1-2 weeks, contributions 12-25%.',
                'content' => $this->getArticle7ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Eenzelentreprise a Frankräich grënnen: Komplette Guide 2025',
                'slug' => 'eenzelentreprise-frankreich-grenden-guide-2025',
                'excerpt' => 'Alles wat Dir wësse musst fir Är Eenzelentreprise oder Micro-Entreprise a Frankräich ze grënnen: Prozeduren iwwer INPI, Steierregime, URSSAF-Bäiträg an Obligatiounen.',
                'meta_title' => 'Eenzelentreprise a Frankräich grënnen | Guide 2025',
                'meta_description' => 'Komplette Guide fir eng Eenzelentreprise a Frankräich ze grënnen: gratis Micro-Entreprise, INPI-Guichet, SIRET an 1-2 Wochen, Bäiträg 12-25%.',
                'content' => $this->getArticle7ContentLB(),
            ]),
        ];
    }

    /**
     * Article 8: Créer une entreprise individuelle en Belgique
     */
    private function getArticle8Translations(): array
    {
        $baseData = [
            'category_id' => 5,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Einzelunternehmen in Belgien gründen: Vollständiger Leitfaden 2025',
                'slug' => 'einzelunternehmen-belgien-gruenden-leitfaden-2025',
                'excerpt' => 'Wie wird man Selbständiger in Belgien: BCE-Registrierung über Unternehmensschalter, Sozialversicherungskasse, MwSt.-Pflichten und INASTI-Beiträge.',
                'meta_title' => 'Einzelunternehmen in Belgien gründen | Leitfaden 2025',
                'meta_description' => 'Vollständiger Leitfaden zur Gründung eines Einzelunternehmens in Belgien: Kosten (~200-500€), Frist (1-2 Wochen), Sozialbeiträge 20,5%, MwSt.-Franchise.',
                'content' => $this->getArticle8ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Starting a Sole Proprietorship in Belgium: Complete Guide 2025',
                'slug' => 'sole-proprietorship-belgium-guide-2025',
                'excerpt' => 'How to become self-employed in Belgium: BCE registration through a business counter, social insurance fund affiliation, VAT obligations and INASTI contributions.',
                'meta_title' => 'Start a Sole Proprietorship in Belgium | Guide 2025',
                'meta_description' => 'Complete guide to creating a sole proprietorship in Belgium: costs (~€200-500), timeline (1-2 weeks), social contributions 20.5%, VAT exemption threshold.',
                'content' => $this->getArticle8ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Eenzelentreprise a Belsch grënnen: Komplette Guide 2025',
                'slug' => 'eenzelentreprise-belgien-grenden-guide-2025',
                'excerpt' => 'Wéi gëtt een Selbstännegen a Belsch: BCE-Registréierung iwwer en Entrepriseguichet, Sozialversécherungskasse, TVA-Obligatiounen an INASTI-Bäiträg.',
                'meta_title' => 'Eenzelentreprise a Belsch grënnen | Guide 2025',
                'meta_description' => 'Komplette Guide fir eng Eenzelentreprise a Belsch ze grënnen: Käschten (~200-500€), Delai (1-2 Wochen), Sozialbäiträg 20,5%, TVA-Franchise.',
                'content' => $this->getArticle8ContentLB(),
            ]),
        ];
    }

    /**
     * Article 9: Créer une entreprise individuelle en Allemagne
     */
    private function getArticle9Translations(): array
    {
        $baseData = [
            'category_id' => 5,
            'status' => 'published',
            'published_at' => now(),
            'author_id' => null,
        ];

        return [
            // German
            array_merge($baseData, [
                'locale' => 'de',
                'title' => 'Einzelunternehmen in Deutschland gründen: Vollständiger Leitfaden 2025',
                'slug' => 'einzelunternehmen-deutschland-gruenden-leitfaden-2025',
                'excerpt' => 'Alles Wissenswerte zur Gründung Ihres Einzelunternehmens oder zur Tätigkeit als Freiberufler in Deutschland: Gewerbeanmeldung, Finanzamt, Kleinunternehmerregelung und Steuerpflichten.',
                'meta_title' => 'Einzelunternehmen in Deutschland gründen | Leitfaden 2025',
                'meta_description' => 'Vollständiger Leitfaden zur Gründung eines Einzelunternehmens in Deutschland: Gewerbeanmeldung (15-60€), Frist 1-3 Tage, Kleinunternehmerregelung, IHK-Pflichten.',
                'content' => $this->getArticle9ContentDE(),
            ]),
            // English
            array_merge($baseData, [
                'locale' => 'en',
                'title' => 'Starting a Sole Proprietorship in Germany: Complete Guide 2025',
                'slug' => 'sole-proprietorship-germany-guide-2025',
                'excerpt' => 'Everything you need to know to start your Einzelunternehmen or become a Freiberufler in Germany: Gewerbeanmeldung, Finanzamt, Kleinunternehmerregelung and tax obligations.',
                'meta_title' => 'Start a Sole Proprietorship in Germany | Guide 2025',
                'meta_description' => 'Complete guide to starting a sole proprietorship in Germany: Gewerbeanmeldung (€15-60), 1-3 days timeline, Kleinunternehmerregelung, IHK obligations.',
                'content' => $this->getArticle9ContentEN(),
            ]),
            // Luxembourgish
            array_merge($baseData, [
                'locale' => 'lb',
                'title' => 'Eenzelentreprise an Däitschland grënnen: Komplette Guide 2025',
                'slug' => 'eenzelentreprise-deutschland-grenden-guide-2025',
                'excerpt' => 'Alles wat Dir wësse musst fir Äert Einzelunternehmen ze grënnen oder als Freiberufler an Däitschland ze schaffen: Gewerbeanmeldung, Finanzamt, Kleinunternehmerregelung a Steierflichten.',
                'meta_title' => 'Eenzelentreprise an Däitschland grënnen | Guide 2025',
                'meta_description' => 'Komplette Guide fir eng Eenzelentreprise an Däitschland ze grënnen: Gewerbeanmeldung (15-60€), Delai 1-3 Deeg, Kleinunternehmerregelung, IHK-Obligatiounen.',
                'content' => $this->getArticle9ContentLB(),
            ]),
        ];
    }

    // Content methods for each article and language
    // Article 1 - Guide facturation Luxembourg
    private function getArticle1ContentDE(): string
    {
        return '<p class="lead">Die Rechnungsstellung in Luxemburg unterliegt präzisen Regeln, die durch die Steuergesetzgebung definiert sind. Ob Sie ein KMU, Freiberufler oder ein großes Unternehmen sind, dieser Leitfaden erklärt alles, was Sie für konforme Rechnungsstellung wissen müssen.</p>

<h2>Warum die Konformität Ihrer Rechnungen wichtig ist</h2>

<p>In Luxemburg ist eine Rechnung nicht nur ein einfaches Geschäftsdokument. Es ist ein <strong>offizielles Buchhaltungsdokument</strong>, das als Grundlage dient für:</p>

<ul>
    <li>Die Berechnung und Erstattung der MwSt.</li>
    <li>Steuerprüfungen der Administration des Contributions Directes (ACD)</li>
    <li>Die Generierung der FAIA-Datei für die Administration de l\'Enregistrement et des Domaines (AED)</li>
    <li>Den Nachweis Ihrer Geschäftstransaktionen</li>
</ul>

<p>Eine nicht konforme Rechnung kann zur <strong>Ablehnung des MwSt.-Abzugs</strong> für Ihren Kunden und zu <strong>finanziellen Sanktionen</strong> für Ihr Unternehmen führen.</p>

<h2>Pflichtangaben auf einer luxemburgischen Rechnung</h2>

<p>Gemäß Artikel 63 des luxemburgischen MwSt.-Gesetzes muss jede Rechnung folgende Informationen enthalten:</p>

<h3>Informationen zum Aussteller</h3>

<ul>
    <li><strong>Name oder Firmenbezeichnung</strong> Ihres Unternehmens</li>
    <li><strong>Vollständige Adresse</strong> des Firmensitzes</li>
    <li><strong>Innergemeinschaftliche MwSt.-Nummer</strong> (Format LU + 8 Ziffern)</li>
    <li><strong>Niederlassungsgenehmigungsnummer</strong> (falls zutreffend)</li>
</ul>

<h3>Informationen zum Kunden</h3>

<ul>
    <li><strong>Name oder Firmenbezeichnung</strong> des Kunden</li>
    <li><strong>Vollständige Adresse</strong></li>
    <li><strong>MwSt.-Nummer</strong> (obligatorisch für B2B-Transaktionen innerhalb der EU)</li>
</ul>

<h3>Rechnungsinformationen</h3>

<ul>
    <li><strong>Eindeutige Rechnungsnummer</strong> in chronologischer Reihenfolge</li>
    <li><strong>Ausstellungsdatum</strong> der Rechnung</li>
    <li><strong>Liefer- oder Leistungsdatum</strong> (falls abweichend)</li>
</ul>

<h3>Leistungsbeschreibung</h3>

<ul>
    <li><strong>Klare Beschreibung</strong> der Waren oder Dienstleistungen</li>
    <li><strong>Menge</strong> und <strong>Nettoeinzelpreis</strong></li>
    <li><strong>Anwendbarer MwSt.-Satz</strong> für jede Position</li>
    <li><strong>MwSt.-Betrag</strong> pro Satz</li>
    <li><strong>Netto-, MwSt.- und Bruttosumme</strong></li>
</ul>

<h2>Rechnungsnummerierung</h2>

<p>Die Nummerierung Ihrer Rechnungen muss strenge Regeln befolgen:</p>

<ul>
    <li><strong>Eindeutige und chronologische Reihenfolge</strong>: keine Lücken in der Nummerierung</li>
    <li><strong>Freies aber konsistentes Format</strong> (z.B.: 2026-0001, FAC-2026-001)</li>
    <li><strong>Eine Serie</strong> pro Geschäftsjahr (außer in besonderen Fällen)</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Verwenden Sie eine Rechnungssoftware wie faktur.lu, um automatisch eine konforme Nummerierung zu gewährleisten und Fehler zu vermeiden.</p>
</div>

<h2>Die verschiedenen MwSt.-Sätze in Luxemburg</h2>

<p>Luxemburg wendet vier MwSt.-Sätze an:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Satz</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anwendung</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Normalsatz (Mehrheit der Waren und Dienstleistungen)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Zwischensatz (Weine, bestimmte Brennstoffe)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Ermäßigter Satz (Gas, Elektrizität, Friseur)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Stark ermäßigter Satz (Lebensmittel, Bücher, Medikamente)</td>
        </tr>
    </tbody>
</table>

<h2>Fazit</h2>

<p>Die Rechnungsstellung in Luxemburg erfordert Sorgfalt und Konformität. Mit einer <strong>geeigneten Rechnungssoftware</strong> wie faktur.lu stellen Sie sicher, dass Sie alle gesetzlichen Pflichten erfüllen und dabei wertvolle Zeit sparen.</p>';
    }

    private function getArticle1ContentEN(): string
    {
        return '<p class="lead">Invoicing in Luxembourg follows precise rules defined by tax legislation. Whether you are an SME, freelancer, or large company, this guide explains everything you need to know to invoice in compliance.</p>

<h2>Why Invoice Compliance is Essential</h2>

<p>In Luxembourg, an invoice is not just a simple commercial document. It is an <strong>official accounting document</strong> that serves as the basis for:</p>

<ul>
    <li>VAT calculation and recovery</li>
    <li>Tax audits by the Administration des Contributions Directes (ACD)</li>
    <li>Generating the FAIA file for the Administration de l\'Enregistrement et des Domaines (AED)</li>
    <li>Proof of your commercial transactions</li>
</ul>

<p>A non-compliant invoice can lead to <strong>rejection of VAT deduction</strong> for your client and <strong>financial penalties</strong> for your business.</p>

<h2>Mandatory Information on a Luxembourg Invoice</h2>

<p>According to Article 63 of the Luxembourg VAT Law, every invoice must contain the following information:</p>

<h3>Issuer Information</h3>

<ul>
    <li><strong>Name or company name</strong> of your business</li>
    <li><strong>Complete address</strong> of the registered office</li>
    <li><strong>Intra-community VAT number</strong> (format LU + 8 digits)</li>
    <li><strong>Establishment authorization number</strong> (if applicable)</li>
</ul>

<h3>Client Information</h3>

<ul>
    <li><strong>Name or company name</strong> of the client</li>
    <li><strong>Complete address</strong></li>
    <li><strong>VAT number</strong> (mandatory for B2B intra-community transactions)</li>
</ul>

<h3>Invoice Information</h3>

<ul>
    <li><strong>Unique invoice number</strong> following a chronological sequence</li>
    <li><strong>Issue date</strong> of the invoice</li>
    <li><strong>Delivery or service date</strong> (if different)</li>
</ul>

<h3>Service Details</h3>

<ul>
    <li><strong>Clear description</strong> of goods or services</li>
    <li><strong>Quantity</strong> and <strong>net unit price</strong></li>
    <li><strong>Applicable VAT rate</strong> for each line</li>
    <li><strong>VAT amount</strong> per rate</li>
    <li><strong>Net, VAT, and gross total</strong></li>
</ul>

<h2>Invoice Numbering</h2>

<p>Your invoice numbering must follow strict rules:</p>

<ul>
    <li><strong>Unique and chronological sequence</strong>: no gaps in numbering</li>
    <li><strong>Free but consistent format</strong> (e.g.: 2026-0001, INV-2026-001)</li>
    <li><strong>One series</strong> per fiscal year (except in special cases)</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tip</p>
    <p class="text-purple-700">Use invoicing software like faktur.lu to automatically ensure compliant numbering and avoid errors.</p>
</div>

<h2>VAT Rates in Luxembourg</h2>

<p>Luxembourg applies four VAT rates:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Rate</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Standard rate (majority of goods and services)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Intermediate rate (wines, certain fuels)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Reduced rate (gas, electricity, hairdressing)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Super-reduced rate (food, books, medicine)</td>
        </tr>
    </tbody>
</table>

<h2>Conclusion</h2>

<p>Invoicing in Luxembourg requires rigor and compliance. By using <strong>suitable invoicing software</strong> like faktur.lu, you ensure compliance with all legal obligations while saving valuable time.</p>';
    }

    private function getArticle1ContentLB(): string
    {
        return '<p class="lead">D\'Rechnungsstellung zu Lëtzebuerg folgt präzisen Reegelen, déi duerch d\'Steiergesetzgebung definéiert sinn. Ob Dir e KMU, Freelancer oder eng grouss Entreprise sidd, dëse Guide erkläert alles wat Dir wësse musst fir konform ze fakturéieren.</p>

<h2>Firwat d\'Konformitéit vun Äre Rechnungen wichteg ass</h2>

<p>Zu Lëtzebuerg ass eng Rechnung net nëmmen en einfacht Geschäftsdokument. Et ass en <strong>offiziellt Comptabilitéitsdokument</strong> dat als Basis déngt fir:</p>

<ul>
    <li>D\'Berechnung an d\'Récupératioun vun der TVA</li>
    <li>Steierkontrollen vun der Administration des Contributions Directes (ACD)</li>
    <li>D\'Generéierung vum FAIA-Fichier fir d\'Administration de l\'Enregistrement et des Domaines (AED)</li>
    <li>De Beweis vun Äre kommerziellen Transaktiounen</li>
</ul>

<p>Eng net-konform Rechnung kann zur <strong>Refus vum TVA-Ofzuch</strong> fir Äre Client an zu <strong>finanziellen Sanktiounen</strong> fir Är Entreprise féieren.</p>

<h2>Pflichtangaben op enger Lëtzebuerger Rechnung</h2>

<p>Laut Artikel 63 vum Lëtzebuerger TVA-Gesetz muss all Rechnung déi folgend Informatiounen enthalen:</p>

<h3>Informatiounen iwwer den Aussteller</h3>

<ul>
    <li><strong>Numm oder Firmenbezeechnung</strong> vun Ärer Entreprise</li>
    <li><strong>Komplett Adress</strong> vum Firmesëtz</li>
    <li><strong>Intracommunautär TVA-Nummer</strong> (Format LU + 8 Zifferen)</li>
    <li><strong>Etabléierungsautoriséierungsnummer</strong> (falls zoutreffend)</li>
</ul>

<h3>Informatiounen iwwer de Client</h3>

<ul>
    <li><strong>Numm oder Firmenbezeechnung</strong> vum Client</li>
    <li><strong>Komplett Adress</strong></li>
    <li><strong>TVA-Nummer</strong> (obligatoresch fir B2B-Transaktiounen bannen der EU)</li>
</ul>

<h3>Rechnungsinformatiounen</h3>

<ul>
    <li><strong>Eenzegaarteg Rechnungsnummer</strong> an chronologescher Reiefolleg</li>
    <li><strong>Ausstellungsdatum</strong> vun der Rechnung</li>
    <li><strong>Liwwer- oder Leeschtungsdatum</strong> (falls anescht)</li>
</ul>

<h2>Fazit</h2>

<p>D\'Rechnungsstellung zu Lëtzebuerg erfuerdert Suergfalt a Konformitéit. Mat enger <strong>passender Rechnungssoftware</strong> wéi faktur.lu garantéiert Dir datt Dir all legal Obligatiounen erfëllt an dobäi wäertvoll Zäit spuert.</p>';
    }

    // Article 2 - FAIA Luxembourg
    private function getArticle2ContentDE(): string
    {
        return '<p class="lead">Die FAIA (Fichier d\'Audit Informatisé) ist ein standardisiertes Dateiformat, das in Luxemburg für Steuerprüfungen obligatorisch ist. Erfahren Sie alles über diese wichtige Anforderung.</p>

<h2>Was ist FAIA?</h2>

<p>FAIA steht für <strong>Fichier d\'Audit Informatisé</strong> - eine standardisierte XML-Datei, die Ihre Buchhaltungsdaten in einem von der luxemburgischen Steuerverwaltung (AED) definierten Format enthält.</p>

<h2>Wer muss FAIA erstellen?</h2>

<p>Jedes Unternehmen in Luxemburg, das <strong>Buchhaltungs- oder Fakturierungssoftware</strong> verwendet, muss in der Lage sein, auf Anfrage eine FAIA-Datei zu erstellen.</p>

<h2>Was enthält eine FAIA-Datei?</h2>

<ul>
    <li>Alle Buchhaltungsbuchungen</li>
    <li>Ausgestellte und erhaltene Rechnungen</li>
    <li>Kunden- und Lieferanteninformationen</li>
    <li>Zahlungsinformationen</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generiert automatisch Ihre FAIA-Datei</p>
    <p class="text-green-700">Unsere Software erstellt mit einem Klick eine konforme FAIA-Datei, bereit zur Übermittlung an die AED bei einer Prüfung.</p>
</div>';
    }

    private function getArticle2ContentEN(): string
    {
        return '<p class="lead">FAIA (Fichier d\'Audit Informatisé) is a standardized file format mandatory in Luxembourg for tax audits. Learn everything about this important requirement.</p>

<h2>What is FAIA?</h2>

<p>FAIA stands for <strong>Fichier d\'Audit Informatisé</strong> - a standardized XML file containing your accounting data in a format defined by the Luxembourg tax administration (AED).</p>

<h2>Who must produce FAIA?</h2>

<p>Any business in Luxembourg using <strong>accounting or invoicing software</strong> must be able to produce a FAIA file upon request.</p>

<h2>What does a FAIA file contain?</h2>

<ul>
    <li>All accounting entries</li>
    <li>Issued and received invoices</li>
    <li>Customer and supplier information</li>
    <li>Payment information</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu automatically generates your FAIA file</p>
    <p class="text-green-700">Our software produces a compliant FAIA file with one click, ready to submit to the AED during an audit.</p>
</div>';
    }

    private function getArticle2ContentLB(): string
    {
        return '<p class="lead">De FAIA (Fichier d\'Audit Informatisé) ass e standardiséiert Dateiformat dat zu Lëtzebuerg fir Steierkontrollen obligatoresch ass. Léiert alles iwwer dës wichteg Ufuerderung.</p>

<h2>Wat ass FAIA?</h2>

<p>FAIA steet fir <strong>Fichier d\'Audit Informatisé</strong> - en standardiséierte XML-Fichier deen Är Comptabilitéitsdaten an engem Format enthält dat vun der Lëtzebuerger Steierverwaltung (AED) definéiert ass.</p>

<h2>Wien muss FAIA erstellen?</h2>

<p>All Entreprise zu Lëtzebuerg déi <strong>Comptabilitéits- oder Fakturéierungssoftware</strong> benotzt muss fäeg sinn op Ufro eng FAIA-Datei ze erstellen.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generéiert automatesch Är FAIA-Datei</p>
    <p class="text-green-700">Eis Software erstellt mat engem Klick eng konform FAIA-Datei, prett fir un d\'AED bei enger Kontroll ze iwwerginn.</p>
</div>';
    }

    // Article 3 - TVA Luxembourg
    private function getArticle3ContentDE(): string
    {
        return '<p class="lead">Die Mehrwertsteuer (MwSt.) in Luxemburg folgt spezifischen Regeln. Hier finden Sie alles über Sätze, Berechnung und Ihre Pflichten als Unternehmen.</p>

<h2>MwSt.-Sätze in Luxemburg</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2">Satz</th>
            <th class="border border-gray-300 px-4 py-2">Anwendung</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Normalsatz</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Zwischensatz (Weine, Brennstoffe)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Ermäßigter Satz (Gas, Strom)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Stark ermäßigt (Lebensmittel, Bücher)</td>
        </tr>
    </tbody>
</table>

<h2>Kleinunternehmerregelung</h2>

<p>In Luxemburg sind Unternehmen mit einem Jahresumsatz unter <strong>35.000 €</strong> von der MwSt. befreit (Franchise).</p>';
    }

    private function getArticle3ContentEN(): string
    {
        return '<p class="lead">Value Added Tax (VAT) in Luxembourg follows specific rules. Here you will find everything about rates, calculation, and your obligations as a business.</p>

<h2>VAT Rates in Luxembourg</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2">Rate</th>
            <th class="border border-gray-300 px-4 py-2">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Standard rate</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Intermediate rate (wines, fuels)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Reduced rate (gas, electricity)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Super-reduced (food, books)</td>
        </tr>
    </tbody>
</table>

<h2>Small Business Exemption</h2>

<p>In Luxembourg, businesses with annual turnover below <strong>€35,000</strong> are exempt from VAT (franchise regime).</p>';
    }

    private function getArticle3ContentLB(): string
    {
        return '<p class="lead">D\'TVA (Taxe sur la Valeur Ajoutée) zu Lëtzebuerg folgt spezifeschen Reegelen. Hei fannt Dir alles iwwer Tariffer, Berechnung an Är Obligatiounen als Entreprise.</p>

<h2>TVA-Tariffer zu Lëtzebuerg</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2">Tarif</th>
            <th class="border border-gray-300 px-4 py-2">Applikatioun</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Normaltarif</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Mëttleren Tarif (Wäiner, Brennstoffer)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Reduzéierten Tarif (Gas, Stroum)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Super-reduzéiert (Iessen, Bicher)</td>
        </tr>
    </tbody>
</table>

<h2>Klengentreprisereglement</h2>

<p>Zu Lëtzebuerg sinn Entreprisen mat engem Joresëmsaz ënner <strong>35.000 €</strong> vun der TVA befreit (Franchise-Regime).</p>';
    }

    // Simplified content methods for remaining articles
    private function getArticle4ContentDE(): string
    {
        return '<p class="lead">Als Freiberufler in Luxemburg müssen Sie spezifische Regeln für die Rechnungsstellung befolgen. Dieser Leitfaden hilft Ihnen, konform zu fakturieren.</p>

<h2>Freiberufler-Status in Luxemburg</h2>
<p>Als Freiberufler in Luxemburg benötigen Sie eine <strong>Niederlassungsgenehmigung</strong> und müssen sich bei der Sozialversicherung anmelden.</p>

<h2>Pflichtangaben auf Ihren Rechnungen</h2>
<ul>
    <li>Ihre vollständigen Kontaktdaten</li>
    <li>MwSt.-Nummer (falls zutreffend)</li>
    <li>Genaue Beschreibung der Dienstleistungen</li>
    <li>Beträge und anwendbare MwSt.</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 faktur.lu für Freiberufler</p>
    <p class="text-purple-700">Unsere Software ist perfekt für Freiberufler geeignet und enthält alle luxemburgischen Compliance-Funktionen.</p>
</div>';
    }

    private function getArticle4ContentEN(): string
    {
        return '<p class="lead">As a freelancer in Luxembourg, you must follow specific invoicing rules. This guide helps you invoice in compliance.</p>

<h2>Freelancer Status in Luxembourg</h2>
<p>As a freelancer in Luxembourg, you need an <strong>establishment authorization</strong> and must register with social security.</p>

<h2>Mandatory Information on Your Invoices</h2>
<ul>
    <li>Your complete contact details</li>
    <li>VAT number (if applicable)</li>
    <li>Precise description of services</li>
    <li>Amounts and applicable VAT</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 faktur.lu for Freelancers</p>
    <p class="text-purple-700">Our software is perfectly suited for freelancers and includes all Luxembourg compliance features.</p>
</div>';
    }

    private function getArticle4ContentLB(): string
    {
        return '<p class="lead">Als Freelancer zu Lëtzebuerg musst Dir spezifesch Reegelen fir d\'Rechnungsstellung befollegen. Dëse Guide hëlleft Iech konform ze fakturéieren.</p>

<h2>Freelancer-Status zu Lëtzebuerg</h2>
<p>Als Freelancer zu Lëtzebuerg braucht Dir eng <strong>Etabléierungsautoriséierung</strong> an Dir musst Iech bei der Sozialversécherung umellen.</p>

<h2>Pflichtangaben op Äre Rechnungen</h2>
<ul>
    <li>Är komplett Kontaktinformatiounen</li>
    <li>TVA-Nummer (falls zoutreffend)</li>
    <li>Genau Beschreiwung vun de Servicer</li>
    <li>Beträg an applicabel TVA</li>
</ul>';
    }

    private function getArticle5ContentDE(): string
    {
        return '<p class="lead">Diese Checkliste enthält alle Pflichtangaben, die auf einer luxemburgischen Rechnung erscheinen müssen.</p>

<h2>Checkliste der Pflichtangaben</h2>

<h3>✅ Angaben zum Aussteller</h3>
<ul>
    <li>Name/Firmenbezeichnung</li>
    <li>Vollständige Adresse</li>
    <li>MwSt.-Nummer (LU + 8 Ziffern)</li>
</ul>

<h3>✅ Angaben zum Kunden</h3>
<ul>
    <li>Name/Firmenbezeichnung</li>
    <li>Vollständige Adresse</li>
    <li>MwSt.-Nummer (für B2B)</li>
</ul>

<h3>✅ Rechnungsdetails</h3>
<ul>
    <li>Eindeutige Rechnungsnummer</li>
    <li>Ausstellungsdatum</li>
    <li>Liefer-/Leistungsdatum</li>
    <li>Detaillierte Beschreibung</li>
    <li>Beträge (netto, MwSt., brutto)</li>
</ul>';
    }

    private function getArticle5ContentEN(): string
    {
        return '<p class="lead">This checklist contains all mandatory information that must appear on a Luxembourg invoice.</p>

<h2>Mandatory Information Checklist</h2>

<h3>✅ Issuer Information</h3>
<ul>
    <li>Name/Company name</li>
    <li>Complete address</li>
    <li>VAT number (LU + 8 digits)</li>
</ul>

<h3>✅ Client Information</h3>
<ul>
    <li>Name/Company name</li>
    <li>Complete address</li>
    <li>VAT number (for B2B)</li>
</ul>

<h3>✅ Invoice Details</h3>
<ul>
    <li>Unique invoice number</li>
    <li>Issue date</li>
    <li>Delivery/service date</li>
    <li>Detailed description</li>
    <li>Amounts (net, VAT, gross)</li>
</ul>';
    }

    private function getArticle5ContentLB(): string
    {
        return '<p class="lead">Dës Checklëscht enthält all Pflichtangaben déi op enger Lëtzebuerger Rechnung musse stoen.</p>

<h2>Checklëscht vun de Pflichtangaben</h2>

<h3>✅ Informatiounen iwwer den Aussteller</h3>
<ul>
    <li>Numm/Firmenbezeechnung</li>
    <li>Komplett Adress</li>
    <li>TVA-Nummer (LU + 8 Zifferen)</li>
</ul>

<h3>✅ Informatiounen iwwer de Client</h3>
<ul>
    <li>Numm/Firmenbezeechnung</li>
    <li>Komplett Adress</li>
    <li>TVA-Nummer (fir B2B)</li>
</ul>

<h3>✅ Rechnungsdetailer</h3>
<ul>
    <li>Eenzegaarteg Rechnungsnummer</li>
    <li>Ausstellungsdatum</li>
    <li>Liwwer-/Leeschtungsdatum</li>
    <li>Detailléiert Beschreiwung</li>
    <li>Beträg (netto, TVA, brutto)</li>
</ul>';
    }

    // Articles 6-9: Business creation guides
    private function getArticle6ContentDE(): string
    {
        return '<p class="lead">Alles, was Sie über die Gründung eines Einzelunternehmens in Luxemburg wissen müssen.</p>

<h2>Schritte zur Gründung</h2>
<ol>
    <li><strong>Niederlassungsgenehmigung</strong> beim Wirtschaftsministerium beantragen</li>
    <li><strong>Handelsregister (RCS)</strong> - Eintragung</li>
    <li><strong>Sozialversicherung</strong> - Anmeldung beim CCSS</li>
    <li><strong>MwSt.-Registrierung</strong> bei der AED (falls erforderlich)</li>
</ol>

<h2>Kosten und Fristen</h2>
<ul>
    <li>Kosten: ca. 100-150 €</li>
    <li>Dauer: 1-3 Monate</li>
</ul>';
    }

    private function getArticle6ContentEN(): string
    {
        return '<p class="lead">Everything you need to know about starting a sole proprietorship in Luxembourg.</p>

<h2>Steps to Create Your Business</h2>
<ol>
    <li><strong>Establishment authorization</strong> from the Ministry of Economy</li>
    <li><strong>Trade Register (RCS)</strong> registration</li>
    <li><strong>Social security</strong> registration with CCSS</li>
    <li><strong>VAT registration</strong> with AED (if required)</li>
</ol>

<h2>Costs and Timeline</h2>
<ul>
    <li>Cost: approximately €100-150</li>
    <li>Duration: 1-3 months</li>
</ul>';
    }

    private function getArticle6ContentLB(): string
    {
        return '<p class="lead">Alles wat Dir wësse musst iwwer d\'Grënnung vun enger Eenzelentreprise zu Lëtzebuerg.</p>

<h2>Schrëtt fir Är Entreprise ze grënnen</h2>
<ol>
    <li><strong>Etabléierungsautoriséierung</strong> vum Wirtschaftsministère</li>
    <li><strong>Handelsregëster (RCS)</strong> Aschreiwung</li>
    <li><strong>Sozialversécherung</strong> Umeldung beim CCSS</li>
    <li><strong>TVA-Registréierung</strong> bei der AED (falls néideg)</li>
</ol>

<h2>Käschten an Delaien</h2>
<ul>
    <li>Käschten: ongeféier 100-150 €</li>
    <li>Dauer: 1-3 Méint</li>
</ul>';
    }

    private function getArticle7ContentDE(): string
    {
        return '<p class="lead">Alles Wissenswerte über die Gründung eines Einzelunternehmens oder einer Micro-Entreprise in Frankreich.</p>

<h2>Schritte zur Gründung</h2>
<ol>
    <li><strong>Guichet unique INPI</strong> - Online-Anmeldung</li>
    <li><strong>SIRET-Nummer</strong> erhalten (1-2 Wochen)</li>
    <li><strong>Steuersystem</strong> wählen (Micro-BIC oder Micro-BNC)</li>
</ol>

<h2>Kosten und Fristen</h2>
<ul>
    <li>Kosten: Kostenlos für Micro-Entreprise</li>
    <li>Sozialabgaben: 12-25% des Umsatzes</li>
</ul>';
    }

    private function getArticle7ContentEN(): string
    {
        return '<p class="lead">Everything you need to know about starting a sole proprietorship or micro-enterprise in France.</p>

<h2>Steps to Create Your Business</h2>
<ol>
    <li><strong>INPI one-stop shop</strong> - Online registration</li>
    <li><strong>SIRET number</strong> received (1-2 weeks)</li>
    <li><strong>Tax regime</strong> selection (Micro-BIC or Micro-BNC)</li>
</ol>

<h2>Costs and Timeline</h2>
<ul>
    <li>Cost: Free for micro-enterprise</li>
    <li>Social contributions: 12-25% of turnover</li>
</ul>';
    }

    private function getArticle7ContentLB(): string
    {
        return '<p class="lead">Alles wat Dir wësse musst iwwer d\'Grënnung vun enger Eenzelentreprise oder Micro-Entreprise a Frankräich.</p>

<h2>Schrëtt fir Är Entreprise ze grënnen</h2>
<ol>
    <li><strong>Guichet unique INPI</strong> - Online-Umeldung</li>
    <li><strong>SIRET-Nummer</strong> kréien (1-2 Wochen)</li>
    <li><strong>Steierregime</strong> wielen (Micro-BIC oder Micro-BNC)</li>
</ol>

<h2>Käschten an Delaien</h2>
<ul>
    <li>Käschten: Gratis fir Micro-Entreprise</li>
    <li>Sozialbäiträg: 12-25% vum Ëmsaz</li>
</ul>';
    }

    private function getArticle8ContentDE(): string
    {
        return '<p class="lead">Wie wird man Selbständiger in Belgien? Hier finden Sie alle Schritte und Anforderungen.</p>

<h2>Schritte zur Gründung</h2>
<ol>
    <li><strong>Unternehmensschalter</strong> (guichet d\'entreprises) aufsuchen</li>
    <li><strong>BCE-Registrierung</strong> (Crossroads Bank for Enterprises)</li>
    <li><strong>Sozialversicherungskasse</strong> beitreten</li>
    <li><strong>MwSt.-Registrierung</strong> (falls erforderlich)</li>
</ol>

<h2>Kosten und Fristen</h2>
<ul>
    <li>Kosten: ca. 200-500 €</li>
    <li>Dauer: 1-2 Wochen</li>
    <li>Sozialabgaben: 20,5%</li>
</ul>';
    }

    private function getArticle8ContentEN(): string
    {
        return '<p class="lead">How to become self-employed in Belgium? Here are all the steps and requirements.</p>

<h2>Steps to Create Your Business</h2>
<ol>
    <li><strong>Business counter</strong> (guichet d\'entreprises) visit</li>
    <li><strong>BCE registration</strong> (Crossroads Bank for Enterprises)</li>
    <li><strong>Social insurance fund</strong> affiliation</li>
    <li><strong>VAT registration</strong> (if required)</li>
</ol>

<h2>Costs and Timeline</h2>
<ul>
    <li>Cost: approximately €200-500</li>
    <li>Duration: 1-2 weeks</li>
    <li>Social contributions: 20.5%</li>
</ul>';
    }

    private function getArticle8ContentLB(): string
    {
        return '<p class="lead">Wéi gëtt een Selbstännegen a Belsch? Hei sinn all Schrëtt an Ufuerderungen.</p>

<h2>Schrëtt fir Är Entreprise ze grënnen</h2>
<ol>
    <li><strong>Entrepriseguichet</strong> besichen</li>
    <li><strong>BCE-Registréierung</strong></li>
    <li><strong>Sozialversécherungskass</strong> bäitrieden</li>
    <li><strong>TVA-Registréierung</strong> (falls néideg)</li>
</ol>

<h2>Käschten an Delaien</h2>
<ul>
    <li>Käschten: ongeféier 200-500 €</li>
    <li>Dauer: 1-2 Wochen</li>
    <li>Sozialbäiträg: 20,5%</li>
</ul>';
    }

    private function getArticle9ContentDE(): string
    {
        return '<p class="lead">Alles über die Gründung eines Einzelunternehmens oder die Tätigkeit als Freiberufler in Deutschland.</p>

<h2>Schritte zur Gründung</h2>
<ol>
    <li><strong>Gewerbeanmeldung</strong> beim Gewerbeamt (für Gewerbetreibende)</li>
    <li><strong>Finanzamt</strong> - Steuerliche Erfassung</li>
    <li><strong>IHK-Mitgliedschaft</strong> (automatisch für Gewerbetreibende)</li>
</ol>

<h2>Kleinunternehmerregelung</h2>
<p>Unter 22.000 € Jahresumsatz können Sie die <strong>Kleinunternehmerregelung</strong> (§ 19 UStG) nutzen und sind von der MwSt. befreit.</p>

<h2>Kosten und Fristen</h2>
<ul>
    <li>Gewerbeanmeldung: 15-60 €</li>
    <li>Dauer: 1-3 Tage</li>
</ul>';
    }

    private function getArticle9ContentEN(): string
    {
        return '<p class="lead">Everything about starting a sole proprietorship or working as a freelancer in Germany.</p>

<h2>Steps to Create Your Business</h2>
<ol>
    <li><strong>Business registration</strong> (Gewerbeanmeldung) at the trade office (for traders)</li>
    <li><strong>Tax office</strong> (Finanzamt) - Tax registration</li>
    <li><strong>IHK membership</strong> (automatic for traders)</li>
</ol>

<h2>Small Business Regulation</h2>
<p>Below €22,000 annual turnover, you can use the <strong>Kleinunternehmerregelung</strong> (§ 19 UStG) and be exempt from VAT.</p>

<h2>Costs and Timeline</h2>
<ul>
    <li>Business registration: €15-60</li>
    <li>Duration: 1-3 days</li>
</ul>';
    }

    private function getArticle9ContentLB(): string
    {
        return '<p class="lead">Alles iwwer d\'Grënnung vun engem Einzelunternehmen oder d\'Aarbecht als Freiberufler an Däitschland.</p>

<h2>Schrëtt fir Är Entreprise ze grënnen</h2>
<ol>
    <li><strong>Gewerbeanmeldung</strong> beim Gewerbeamt (fir Gewerbetreibende)</li>
    <li><strong>Finanzamt</strong> - Steiererfassung</li>
    <li><strong>IHK-Memberschaft</strong> (automatesch fir Gewerbetreibende)</li>
</ol>

<h2>Kleinunternehmerregelung</h2>
<p>Ënner 22.000 € Joresëmsaz kënnt Dir d\'<strong>Kleinunternehmerregelung</strong> (§ 19 UStG) notzen a sidd vun der MwSt. befreit.</p>

<h2>Käschten an Delaien</h2>
<ul>
    <li>Gewerbeanmeldung: 15-60 €</li>
    <li>Dauer: 1-3 Deeg</li>
</ul>';
    }
}
