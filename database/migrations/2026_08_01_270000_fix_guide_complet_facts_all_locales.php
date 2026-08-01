<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Guide complet de la facturation au Luxembourg ».
 *
 * Corrections CIBLÉES dans les cinq langues, sans réécriture. Deux raisons :
 * le français porte en production un encart ajouté dans l'administration et
 * absent du dépôt ; et les quatre traductions sont substantielles — les
 * reconstruire les appauvrirait, ce qu'une première tentative a d'ailleurs
 * fait avant d'être annulée.
 *
 * Quatre erreurs, toutes appuyées sur le texte de la loi.
 *
 * 1. POINT DE DÉPART DE LA CONSERVATION. « 10 ans à partir de la fin de
 *    l'exercice comptable » : l'article 65 LIVA distingue deux régimes. Les
 *    factures se conservent dix ans « À PARTIR DE LEUR DATE D'ÉMISSION »
 *    (point 1°) ; les autres livres et documents, dix ans « à partir de leur
 *    CLÔTURE » (point 2°). L'article appliquait aux factures la règle des
 *    livres — et contredisait l'article « archivage », déjà corrigé.
 *
 * 2. QUI DOIT PRODUIRE UN FAIA. « Toute entreprise utilisant un logiciel de
 *    comptabilité ou de facturation » : faux, et contraire à l'article FAIA du
 *    même blog. La FAQ de l'AED pose quatre conditions cumulatives.
 *
 * 3. NOMBRE DE SÉRIES. « Une seule série par exercice » : l'article 63,
 *    paragraphe 8, point 2° dit l'inverse — « un numéro séquentiel, basé sur
 *    UNE OU PLUSIEURS SÉRIES ».
 *
 * 4. DÉLAI D'ÉMISSION. Le 15 du mois suivant est exact (article 63,
 *    paragraphe 5), mais la règle des acomptes manquait : la facture est due
 *    « au plus tard lors de l'encaissement » de l'acompte.
 *
 * S'y ajoute la parité : le bloc « articles connexes » manquait en DE, EN et
 * LB (1 lien contre 5), et l'encart de prudence en PT.
 */
return new class extends Migration
{
    private const KEY = 'guide-complet-facturation-luxembourg-2026';

    /** locale => [[avant, après], …] */
    private function fixes(): array
    {
        return [
            'fr' => [
                ["<p>Vous devez conserver vos factures pendant <strong>10 ans</strong> à partir de la fin de l'exercice comptable concerné. Cette obligation s'applique aux factures émises ET reçues.</p>",
                 "<p>Vous devez conserver vos factures pendant <strong>10 ans à partir de leur date d'émission</strong> (article 65 LIVA). Cette obligation s'applique aux factures émises ET reçues.</p>\n\n<p>À ne pas confondre : ce point de départ vaut pour les <strong>factures</strong>. Les autres livres et documents comptables se conservent dix ans <strong>à partir de leur clôture</strong>."],
                ["<p>Une facture doit être émise <strong>au plus tard le 15 du mois suivant</strong> la livraison du bien ou l'achèvement de la prestation.</p>",
                 "<p>Une facture doit être émise <strong>au plus tard le 15 du mois suivant</strong> la livraison du bien ou l'achèvement de la prestation (article 63, paragraphe 5, LIVA).</p>\n\n<p>Exception souvent oubliée : en cas d'<strong>acompte</strong> sur une prestation non achevée, la facture est due <strong>dès l'encaissement de cet acompte</strong>."],
                ["<strong>Une seule série</strong> par exercice comptable (sauf cas particuliers)</li>",
                 "<strong>Une ou plusieurs séries</strong> : l'article 63 les autorise expressément - chacune devant rester continue</li>"],
                ["Le <strong>FAIA (Fichier d'Audit Informatisé)</strong> est un fichier XML standardisé que toute entreprise utilisant un logiciel de comptabilité ou de facturation doit pouvoir produire sur demande de l'administration fiscale.</p>",
                 "Le <strong>FAIA (Fichier d'Audit Informatisé)</strong> est un fichier XML standardisé que l'AED peut réclamer lors d'un contrôle.</p>\n\n<p>Il ne concerne pas toutes les entreprises : la FAQ de l'AED pose <strong>quatre conditions cumulatives</strong>, dont l'usage d'un logiciel n'est qu'une. Voir notre <a href=\"/fr/blog/faia-luxembourg-fichier-audit-informatise-guide\">guide du FAIA</a>.</p>"],
            ],
            'de' => [
                ['<p>Sie müssen Ihre Rechnungen <strong>10 Jahre</strong> ab Ende des betreffenden Geschäftsjahres aufbewahren. Diese Pflicht gilt für ausgestellte UND erhaltene Rechnungen.</p>',
                 "<p>Sie müssen Ihre Rechnungen <strong>zehn Jahre ab ihrem Ausstellungsdatum</strong> aufbewahren (Artikel 65 LIVA). Diese Pflicht gilt für ausgestellte UND erhaltene Rechnungen.</p>\n\n<p>Nicht zu verwechseln: dieser Ausgangspunkt gilt für <strong>Rechnungen</strong>. Die übrigen Bücher und Buchhaltungsunterlagen werden zehn Jahre <strong>ab ihrem Abschluss</strong> aufbewahrt.</p>"],
                ['<p>Eine Rechnung muss <strong>spätestens bis zum 15. des Folgemonats</strong> nach Lieferung der Ware oder Erbringung der Dienstleistung ausgestellt werden.</p>',
                 "<p>Eine Rechnung muss <strong>spätestens bis zum 15. des Folgemonats</strong> nach Lieferung der Ware oder Erbringung der Dienstleistung ausgestellt werden (Artikel 63 Absatz 5 LIVA).</p>\n\n<p>Eine oft übersehene Ausnahme: bei einer <strong>Anzahlung</strong> auf eine noch nicht erbrachte Leistung ist die Rechnung <strong>bereits bei deren Vereinnahmung</strong> fällig.</p>"],
                ['<li><strong>Eine Serie</strong> pro Geschäftsjahr (außer in Sonderfällen)</li>',
                 '<li><strong>Eine oder mehrere Serien</strong>: Artikel 63 lässt sie ausdrücklich zu - jede muss für sich lückenlos bleiben</li>'],
                ["<p>Die <strong>FAIA (Fichier d'Audit Informatisé)</strong> ist eine standardisierte XML-Datei, die jedes Unternehmen, das Buchhaltungs- oder Rechnungssoftware verwendet, auf Anfrage der Steuerbehörde vorlegen können muss.</p>",
                 "<p>Die <strong>FAIA (Fichier d'Audit Informatisé)</strong> ist eine standardisierte XML-Datei, die die AED bei einer Prüfung verlangen kann.</p>\n\n<p>Sie betrifft nicht alle Unternehmen: die FAQ der AED stellt <strong>vier kumulative Bedingungen</strong> auf, von denen die Nutzung einer Software nur eine ist. Siehe unseren <a href=\"/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden\">FAIA-Leitfaden</a>.</p>"],
            ],
            'en' => [
                ['<p>You must retain your invoices for <strong>10 years</strong> from the end of the relevant fiscal year. This obligation applies to both issued AND received invoices.</p>',
                 "<p>You must retain your invoices for <strong>ten years from their date of issue</strong> (article 65 LIVA). This obligation applies to both issued AND received invoices.</p>\n\n<p>Not to be confused: this starting point applies to <strong>invoices</strong>. Other books and accounting records are kept for ten years <strong>from their closing</strong>.</p>"],
                ['<p>An invoice must be issued <strong>no later than the 15th of the following month</strong> after delivery of goods or completion of services.</p>',
                 "<p>An invoice must be issued <strong>no later than the 15th of the following month</strong> after delivery of goods or completion of services (article 63(5) LIVA).</p>\n\n<p>An exception that is often overlooked: where an <strong>advance payment</strong> is made for a service not yet completed, the invoice is due <strong>as soon as that advance is collected</strong>.</p>"],
                ['<li><strong>One series</strong> per fiscal year (except in special cases)</li>',
                 '<li><strong>One or more series</strong>: article 63 expressly allows them - each must remain unbroken</li>'],
                ["<p>The <strong>FAIA (Fichier d'Audit Informatisé)</strong> is a standardized XML file that any business using accounting or invoicing software must be able to produce upon request from the tax authorities.</p>",
                 "<p>The <strong>FAIA (Fichier d'Audit Informatisé)</strong> is a standardised XML file that the AED can request during an audit.</p>\n\n<p>It does not concern every business: the AED's FAQ sets out <strong>four cumulative conditions</strong>, of which using software is only one. See our <a href=\"/en/blog/faia-luxembourg-computerized-audit-file-guide\">FAIA guide</a>.</p>"],
            ],
            'lb' => [
                ['<p>Dir musst Är Rechnungen <strong>10 Joer</strong> opbewahren, vum Enn vum concernéierte Geschäftsjoer un. Dës Flicht gëllt fir ausgestallten AN kritt Rechnungen.</p>',
                 "<p>Dir musst Är Rechnungen <strong>zéng Joer vun hirem Datum vun der Erausgab un</strong> opbewahren (Artikel 65 LIVA). Dës Flicht gëllt fir ausgestallten AN kritt Rechnungen.</p>\n\n<p>Net ze verwiesselen: dëse Startpunkt gëllt fir <strong>Rechnungen</strong>. Déi aner Bicher a comptabel Dokumenter ginn zéng Joer <strong>vun hirer Ofschléissung un</strong> opbewaart.</p>"],
                ['<p>Eng Rechnung muss <strong>spéitstens den 15. vum nächste Mount</strong> no der Liwwerung vun de Wueren oder der Leeschtung ausgestallt ginn.</p>',
                 "<p>Eng Rechnung muss <strong>spéitstens den 15. vum nächste Mount</strong> no der Liwwerung vun de Wueren oder der Leeschtung ausgestallt ginn (Artikel 63, Paragraf 5, LIVA).</p>\n\n<p>Eng Ausnam, déi dacks vergiess gëtt: bei engem <strong>Acompte</strong> op eng nach net ofgeschloss Leeschtung ass d'Rechnung <strong>scho beim Encaissement</strong> fälleg.</p>"],
                ['<li><strong>Eng Serie</strong> pro Geschäftsjoer (ausser a Spezialfäll)</li>',
                 '<li><strong>Eng oder méi Serien</strong>: den Artikel 63 léisst se ausdrécklech zou - all Serie muss fir sech kontinuéierlech bleiwen</li>'],
                ["<p>De <strong>FAIA (Fichier d'Audit Informatisé)</strong> ass e standardiséierten XML-Fichier deen all Entreprise, déi Comptabilitéits- oder Fakturéierungssoftware benotzt, op Ufro vun der Steierverwaltung muss virweisen.</p>",
                 "<p>De <strong>FAIA (Fichier d'Audit Informatisé)</strong> ass e standardiséierten XML-Fichier, deen d'AED bei enger Kontroll ufroe kann.</p>\n\n<p>En betrëfft net all Entreprisen: d'FAQ vun der AED stellt <strong>véier kumulativ Konditiounen</strong> op, wouvun eng Software ze benotzen nëmmen eng ass. Kuckt eise <a href=\"/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide\">FAIA-Guide</a>.</p>"],
            ],
            'pt' => [
                ['<p>Deve conservar as suas faturas durante <strong>10 anos</strong> a partir do final do exercício contabilístico em causa. Esta obrigação aplica-se às faturas emitidas E recebidas.</p>',
                 "<p>Deve conservar as suas faturas durante <strong>dez anos a contar da sua data de emissão</strong> (artigo 65 LIVA). Esta obrigação aplica-se às faturas emitidas E recebidas.</p>\n\n<p>A não confundir: este ponto de partida vale para as <strong>faturas</strong>. Os restantes livros e documentos contabilísticos conservam-se dez anos <strong>a contar do seu encerramento</strong>.</p>"],
                ['<p>Uma fatura deve ser emitida <strong>o mais tardar até ao dia 15 do mês seguinte</strong> à entrega do bem ou à conclusão da prestação.</p>',
                 "<p>Uma fatura deve ser emitida <strong>o mais tardar até ao dia 15 do mês seguinte</strong> à entrega do bem ou à conclusão da prestação (artigo 63, n.º 5, LIVA).</p>\n\n<p>Exceção muitas vezes esquecida: havendo <strong>adiantamento</strong> sobre uma prestação ainda não concluída, a fatura é devida <strong>logo no recebimento desse adiantamento</strong>.</p>"],
                ['<li><strong>Uma única série</strong> por exercício contabilístico (salvo casos particulares)</li>',
                 '<li><strong>Uma ou várias séries</strong>: o artigo 63 autoriza-as expressamente - cada uma deve manter-se contínua</li>'],
                ['<p>O <strong>FAIA (Ficheiro de Auditoria Informatizado)</strong> é um ficheiro XML normalizado que qualquer empresa que utilize um software de contabilidade ou de faturação deve poder produzir a pedido da administração fiscal.</p>',
                 "<p>O <strong>FAIA (Ficheiro de Auditoria Informatizado)</strong> é um ficheiro XML normalizado que a AED pode exigir numa inspeção.</p>\n\n<p>Não abrange todas as empresas: a FAQ da AED estabelece <strong>quatro condições cumulativas</strong>, de que usar um software é apenas uma. Veja o nosso <a href=\"/pt/blog/faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado\">guia do FAIA</a>.</p>"],
            ],
        ];
    }

    /** Bloc « articles connexes » absent en DE, EN et LB. */
    private const RELATED = [
        'de' => ['Verwandte Artikel', [
            ['faia-luxemburg-informatisierte-audit-datei-leitfaden', 'FAIA-Export'],
            ['mwst-luxemburg-saetze-berechnung-pflichten', 'MwSt-Sätze'],
            ['pflichtangaben-rechnung-luxemburg', 'Pflichtangaben'],
            ['rechnungssoftware-luxemburg-richtige-waehlen-vergleich', 'Vergleich: die richtige Software wählen'],
        ]],
        'en' => ['Related articles', [
            ['faia-luxembourg-computerized-audit-file-guide', 'FAIA export'],
            ['vat-luxembourg-rates-calculation-obligations', 'VAT rates'],
            ['mandatory-information-invoice-luxembourg', 'mandatory information'],
            ['choose-invoicing-software-luxembourg-comparison', 'Comparison: choosing your invoicing software'],
        ]],
        'lb' => ['Verbonnen Artikelen', [
            ['faia-letzebuerg-informatiseierte-audit-fichier-guide', 'FAIA-Export'],
            ['tva-letzebuerg-tariffer-berechnung-obligatiounen', 'TVA-Sätz'],
            ['pflichtinformatiounen-rechnung-letzebuerg', 'Pflichtmentiounen'],
            ['rechnungssoftware-letzebuerg-richteg-wielen-verglach', 'Verglach: seng Software wielen'],
        ]],
    ];

    /** Encart de prudence absent en portugais. */
    private const PT_DISCLAIMER = '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
        .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
        .'    <p>Os limiares, as taxas e os procedimentos fiscais luxemburgueses podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu <em>fiduciaire</em> ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>'."\n"
        .'</div>';

    public function up(): void
    {
        foreach ($this->fixes() as $locale => $pairs) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = $post->content;
            $applied = 0;

            foreach ($pairs as [$from, $to]) {
                if (str_contains($content, $from)) {
                    $content = str_replace($from, $to, $content);
                    $applied++;
                } else {
                    echo "  !! {$locale} : motif introuvable — ".mb_substr($from, 0, 52)."\n";
                }
            }

            // Bloc « articles connexes » là où il manque.
            if (isset(self::RELATED[$locale])) {
                [$title, $links] = self::RELATED[$locale];

                if (! str_contains($content, $title)) {
                    $items = '';
                    foreach ($links as [$slug, $label]) {
                        $items .= '<li><a href="/'.$locale.'/blog/'.$slug.'" class="text-primary-500 hover:text-primary-600 text-sm">'.$label.' →</a></li>';
                    }

                    $content = rtrim($content)."\n\n".'<div class="mt-8 p-4 bg-slate-50 rounded-xl">'
                        .'<h3 class="text-base font-semibold text-slate-900 mb-3">'.$title.'</h3>'
                        .'<ul class="space-y-1">'.$items.'</ul></div>';
                }
            }

            if ($locale === 'pt' && ! str_contains($content, 'A verificar todos os anos')) {
                $content = rtrim($content)."\n\n".self::PT_DISCLAIMER;
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);

            echo "  {$locale} : {$applied}/4 correction(s)\n";
        }
    }

    public function down(): void
    {
        // Restaurer une règle de conservation fausse n'aurait pas de sens.
    }
};
