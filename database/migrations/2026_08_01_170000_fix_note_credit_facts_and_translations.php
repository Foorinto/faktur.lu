<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Note de crédit au Luxembourg : comment l'établir ».
 *
 * 1. Erreur de fond : l'article listait « Insolvabilité du client : créance
 *    irrécupérable » parmi les motifs d'émission d'une note de crédit. Un
 *    simple non-paiement ne s'annule pas par un avoir — la créance reste due,
 *    et l'annuler la ferait disparaître. La régularisation de la TVA sur une
 *    créance irrécouvrable relève de l'article 33 LIVA, qui renvoie à un
 *    règlement grand-ducal pour ses conditions : point à trancher avec la
 *    fiduciaire, pas à affirmer ici.
 *
 *    Le produit confirme la lecture : Invoice::CREDIT_NOTE_REASONS propose
 *    billing_error, return, commercial_discount, cancellation et other.
 *    L'insolvabilité n'y figure pas.
 *
 * 2. Base légale ajoutée. L'article 63, paragraphe 2, LIVA assimile à une
 *    facture « tout document ou message qui modifie la facture initiale et y
 *    fait référence de façon spécifique et non équivoque » : c'est ce qui
 *    fonde la liste des mentions. Le motif, lui, n'est pas exigé par
 *    l'article 63 — l'article le presentait comme obligatoire.
 *
 * 3. Revendication produit : « Votre livre des recettes et votre export FAIA
 *    sont mis à jour instantanément ». Le livre des recettes est derrière
 *    `accounting_exports` (Essentiel+, middleware plan.feature sur les routes) ;
 *    seul l'export FAIA est inclus dans le plan gratuit.
 *
 * DE, EN, LB, PT : 3 588 à 4 082 caractères contre 4 966 en français, avec
 * deux à trois liens contre cinq.
 */
return new class extends Migration
{
    private const KEY = 'note-de-credit-luxembourg-comment-etablir';

    private const FR_FIXES = [
        [
            "Contrairement à une facture, les montants d'une note de crédit sont <strong>négatifs</strong>.",
            "Par convention, ses montants s'affichent en <strong>négatif</strong> - ou assortis de la mention « à déduire ».",
        ],
        [
            "    <li><strong>Annulation de prestation</strong> : le service n'a pas été rendu</li>\n    <li><strong>Insolvabilité du client</strong> : créance irrécupérable</li>\n</ul>",
            "    <li><strong>Annulation de prestation</strong> : le service n'a pas été rendu</li>\n</ul>\n\n"
                ."<p><strong>Le simple non-paiement n'en fait pas partie.</strong> Une facture impayée reste due : l'annuler par une note de crédit ferait disparaître une créance que vous pouvez encore recouvrer. La régularisation de la TVA sur une créance devenue irrécouvrable relève de l'<strong>article 33 LIVA</strong>, qui renvoie à un règlement grand-ducal pour ses conditions - un point à examiner avec votre fiduciaire.</p>",
        ],
        [
            '<p>Une note de crédit au Luxembourg doit contenir les mêmes mentions qu\'une facture, plus :</p>',
            "<p>L'<strong>article 63, paragraphe 2, LIVA</strong> assimile à une facture « tout document ou message qui modifie la facture initiale et y fait référence de façon spécifique et non équivoque ». Une note de crédit porte donc les mêmes mentions qu'une facture, plus :</p>",
        ],
        [
            '    <li>Le <strong>motif</strong> de la note de crédit</li>',
            "    <li>Le <strong>motif</strong> de la note de crédit - non exigé par l'article 63, mais il documente l'opération en cas de contrôle</li>",
        ],
        [
            '<p>La note de crédit a un impact direct sur votre déclaration TVA :</p>',
            "<p>La note de crédit a un impact direct sur votre déclaration TVA. La base d'imposition est celle de la rémunération facturée, « sans préjudice d'une régularisation éventuelle » prévue par l'<strong>article 33 LIVA</strong> :</p>",
        ],
        [
            '<p>La référence à la facture d\'origine est ajoutée automatiquement, et les montants sont calculés en négatif. Votre livre des recettes et votre export FAIA sont mis à jour instantanément.</p>',
            "<p>La référence à la facture d'origine est ajoutée automatiquement, et les montants sont calculés en négatif. Votre export FAIA est mis à jour dans la foulée.</p>\n\n"
                .'<p class="text-sm text-slate-500"><em>Le livre des recettes est disponible à partir du plan Essentiel ; l\'export FAIA est inclus dès le plan gratuit.</em></p>',
        ],
    ];

    public function up(): void
    {
        $this->rewriteFrench(self::FR_FIXES);

        foreach ($this->translations() as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $this->rewriteFrench(array_map('array_reverse', self::FR_FIXES));
    }

    /** @param  array<int, array{0:string,1:string}>  $fixes */
    private function rewriteFrench(array $fixes): void
    {
        $post = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'fr')
            ->first(['id', 'content']);

        if (! $post) {
            return;
        }

        $content = $post->content;

        foreach ($fixes as [$before, $after]) {
            $content = str_replace($before, $after, $content);
        }

        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => $content,
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, string> */
    private function translations(): array
    {
        $de = <<<'HTML'
<p class="lead">Ein Fehler auf einer Rechnung? Ein Kunde schickt Ware zurueck? Dann muessen Sie eine <strong>Gutschrift</strong> ausstellen. So erstellen Sie sie in Luxemburg korrekt.</p>

<h2>Was ist eine Gutschrift?</h2>

<p>Eine Gutschrift ist ein Buchungsbeleg, der eine bereits ausgestellte Rechnung <strong>ganz oder teilweise aufhebt</strong>. Ihre Betraege werden ueblicherweise <strong>negativ</strong> dargestellt – oder mit dem Zusatz „abzuziehen".</p>

<p><strong>Wichtig:</strong> In Luxemburg ist es <strong>untersagt, eine finalisierte Rechnung zu aendern oder zu loeschen</strong> (Grundsatz der Unveraenderlichkeit). Der einzige Weg, einen Fehler zu berichtigen, ist die Gutschrift.</p>

<h2>Wann stellt man eine Gutschrift aus?</h2>

<ul>
    <li><strong>Rechnungsfehler</strong>: falscher Betrag, falsche MwSt, falscher Kunde</li>
    <li><strong>Warenruecksendung</strong>: der Kunde sendet die Bestellung ganz oder teilweise zurueck</li>
    <li><strong>Nachtraeglicher Rabatt</strong>: Einigung auf einen Preisnachlass nach Rechnungstellung</li>
    <li><strong>Stornierung der Leistung</strong>: die Leistung wurde nicht erbracht</li>
</ul>

<p><strong>Die blosse Nichtzahlung gehoert nicht dazu.</strong> Eine unbezahlte Rechnung bleibt geschuldet: sie per Gutschrift aufzuheben, wuerde eine Forderung beseitigen, die Sie noch beitreiben koennen. Die MwSt-Berichtigung bei einer uneinbringlich gewordenen Forderung richtet sich nach <strong>Artikel 33 LIVA</strong>, der fuer die Voraussetzungen auf eine grossherzogliche Verordnung verweist – ein Punkt fuer Ihren Treuhaender.</p>

<h2>Pflichtangaben</h2>

<p><strong>Artikel 63 Absatz 2 LIVA</strong> stellt „jedes Dokument oder jede Nachricht, das bzw. die die urspruengliche Rechnung aendert und spezifisch und eindeutig auf sie Bezug nimmt" einer Rechnung gleich. Eine Gutschrift traegt daher dieselben Angaben wie eine Rechnung, zusaetzlich:</p>

<ul>
    <li>Den deutlich sichtbaren Vermerk <strong>„Gutschrift"</strong></li>
    <li>Den <strong>Bezug zur urspruenglichen Rechnung</strong> (Nummer und Datum)</li>
    <li>Den <strong>Grund</strong> der Gutschrift – von Artikel 63 nicht verlangt, aber er dokumentiert den Vorgang bei einer Pruefung</li>
    <li>Die Betraege im <strong>Negativen</strong> (oder mit dem Zusatz „abzuziehen")</li>
    <li>Die MwSt-Aufteilung (dieselben Saetze wie in der urspruenglichen Rechnung)</li>
    <li>Eine <strong>eigene Nummernserie</strong> (z. B. GS-2026-001 oder AV-2026-001)</li>
</ul>

<h2>Auswirkung auf die MwSt</h2>

<p>Die Gutschrift wirkt sich unmittelbar auf Ihre MwSt-Erklaerung aus. Bemessungsgrundlage ist die in Rechnung gestellte Verguetung, „unbeschadet einer etwaigen Berichtigung" nach <strong>Artikel 33 LIVA</strong>:</p>

<ul>
    <li>Die MwSt der Gutschrift wird von der <strong>geschuldeten MwSt abgezogen</strong></li>
    <li>Sie ist in <strong>derselben Periode</strong> zu erklaeren, in der sie ausgestellt wurde</li>
    <li>Hat der Kunde die MwSt bereits abgezogen, muss er <strong>seine eigene Erklaerung berichtigen</strong></li>
</ul>

<h2>Eine Gutschrift mit faktur.lu erstellen</h2>

<p>Mit faktur.lu ist das Erstellen einer Gutschrift einfach und sicher:</p>

<ol>
    <li>Oeffnen Sie die urspruengliche Rechnung</li>
    <li>Klicken Sie auf <strong>„Gutschrift erstellen"</strong></li>
    <li>Waehlen Sie den Grund</li>
    <li>Entscheiden Sie sich fuer eine vollstaendige oder teilweise Aufhebung</li>
    <li>Die Gutschrift wird mit allen erforderlichen Angaben automatisch erzeugt</li>
</ol>

<p>Der Bezug zur urspruenglichen Rechnung wird automatisch ergaenzt, und die Betraege werden negativ berechnet. Ihr FAIA-Export wird unmittelbar nachgefuehrt.</p>

<p class="text-sm text-slate-500"><em>Das Einnahmenbuch ist ab dem Essentiel-Tarif verfuegbar; der FAIA-Export ist bereits im kostenlosen Tarif enthalten.</em></p>

<h2>Unterschied zwischen Gutschrift und Avoir</h2>

<p>In der Praxis sind <strong>„Gutschrift"</strong> und <strong>„avoir"</strong> in Luxemburg gleichbedeutend. Die Verwaltung verwendet auf Franzoesisch „note de crédit"; „avoir" ist die gaengige Alltagsform.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben →</a></li><li><a href="/de/blog/vollstaendiger-leitfaden-rechnungsstellung-luxemburg-2026" class="text-primary-500 hover:text-primary-600 text-sm">Leitfaden zur Rechnungsstellung →</a></li><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">Vergleich: die richtige Rechnungssoftware waehlen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Erstellen Sie Ihre Gutschriften mit einem Klick</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt Ihre Gutschriften automatisch mit allen Pflichtangaben, verknuepft mit der urspruenglichen Rechnung und im Einklang mit dem luxemburgischen Recht.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jaehrlich zu pruefen</p>
    <p>Luxemburgische Schwellen, Saetze und Steuerverfahren koennen sich aendern. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich an Ihren Treuhaender oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead">A mistake on an invoice? A client returning goods? You need to issue a <strong>credit note</strong>. Here is how to do it correctly in Luxembourg.</p>

<h2>What is a credit note?</h2>

<p>A credit note is an accounting document that <strong>cancels or partially corrects an invoice</strong> already issued. By convention its amounts are shown as <strong>negative</strong> — or marked "to be deducted".</p>

<p><strong>Important:</strong> in Luxembourg it is <strong>forbidden to amend or delete a finalised invoice</strong> (the immutability principle). The only way to correct a mistake is to issue a credit note.</p>

<h2>When should you issue one?</h2>

<ul>
    <li><strong>Invoicing error</strong>: wrong amount, wrong VAT, wrong client</li>
    <li><strong>Returned goods</strong>: the client sends back all or part of the order</li>
    <li><strong>Commercial discount</strong>: a rebate agreed after invoicing</li>
    <li><strong>Cancelled service</strong>: the work was never delivered</li>
</ul>

<p><strong>Simple non-payment is not on that list.</strong> An unpaid invoice remains owed: cancelling it by credit note would wipe out a debt you can still recover. Adjusting the VAT on a debt that has become irrecoverable falls under <strong>article 33 LIVA</strong>, which refers the conditions to a grand-ducal regulation — a point to settle with your fiduciaire.</p>

<h2>Mandatory information</h2>

<p><strong>Article 63(2) LIVA</strong> treats as an invoice "any document or message that amends the initial invoice and refers to it specifically and unambiguously". A credit note therefore carries the same information as an invoice, plus:</p>

<ul>
    <li>The words <strong>"Credit note"</strong>, clearly visible</li>
    <li>The <strong>reference to the original invoice</strong> (number and date)</li>
    <li>The <strong>reason</strong> for the credit note — not required by article 63, but it documents the transaction in an audit</li>
    <li>The amounts in <strong>negative</strong> (or marked "to be deducted")</li>
    <li>The VAT breakdown (same rates as the original invoice)</li>
    <li>A <strong>separate numbering series</strong> (e.g. CN-2026-001 or AV-2026-001)</li>
</ul>

<h2>Impact on VAT</h2>

<p>A credit note has a direct effect on your VAT return. The taxable base is the remuneration invoiced, "without prejudice to any adjustment" provided for by <strong>article 33 LIVA</strong>:</p>

<ul>
    <li>The VAT on the credit note is <strong>deducted from the VAT you collected</strong></li>
    <li>It must be reported in the <strong>same period</strong> in which it was issued</li>
    <li>If the client already deducted the VAT, they must <strong>adjust their own return</strong></li>
</ul>

<h2>Creating a credit note with faktur.lu</h2>

<p>With faktur.lu, creating a credit note is simple and safe:</p>

<ol>
    <li>Open the original invoice</li>
    <li>Click <strong>"Create a credit note"</strong></li>
    <li>Pick the reason</li>
    <li>Choose full or partial cancellation</li>
    <li>The credit note is generated automatically with every required mention</li>
</ol>

<p>The reference to the original invoice is added automatically and the amounts are computed as negative. Your FAIA export is updated straight away.</p>

<p class="text-sm text-slate-500"><em>The revenue book is available from the Essentiel plan; the FAIA export is included from the free plan.</em></p>

<h2>Credit note or "avoir"?</h2>

<p>In practice the terms <strong>"credit note"</strong> and <strong>"avoir"</strong> are interchangeable in Luxembourg. The administration uses "note de crédit" in French, while "avoir" is the common everyday word.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mandatory information →</a></li><li><a href="/en/blog/complete-guide-invoicing-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">invoicing guide →</a></li><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">Comparison: choosing your invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Create your credit notes in one click</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates your credit notes automatically with every mandatory mention, linked to the original invoice and compliant with Luxembourg law.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Luxembourg thresholds, rates and tax procedures can change. This page is updated regularly, but for your own situation consult your fiduciaire or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">E Feeler op enger Rechnung? E Client schéckt eng Wuer zréck? Da musst Dir en <strong>Avoir</strong> erausginn. Hei ass, wéi Dir en zu Lëtzebuerg richteg erstellt.</p>

<h2>Wat ass en Avoir?</h2>

<p>En Avoir ass e comptabelt Dokument, dat eng scho erausgestallte Rechnung <strong>ganz oder deelweis annuléiert</strong>. Seng Montante ginn üblecherweis <strong>negativ</strong> duergestallt – oder mat der Mentioun „ofzezéien".</p>

<p><strong>Wichteg:</strong> Zu Lëtzebuerg ass et <strong>verbueden, eng finaliséiert Rechnung ze änneren oder ze läschen</strong> (Prinzip vun der Onverännerlechkeet). Deen eenzege Wee fir e Feeler ze korrigéieren ass en Avoir.</p>

<h2>Wéini gëtt een Avoir eraus?</h2>

<ul>
    <li><strong>Fakturatiounsfeeler</strong>: falsche Montant, falsch TVA, falsche Client</li>
    <li><strong>Retour vun der Wuer</strong>: de Client schéckt d'Bestellung ganz oder deelweis zréck</li>
    <li><strong>Kommerziell Remise</strong>: Accord iwwer e Rabatt no der Fakturatioun</li>
    <li><strong>Annulatioun vun der Leeschtung</strong>: de Service gouf net erbruecht</li>
</ul>

<p><strong>Dat einfacht Net-Bezuelen gehéiert net dozou.</strong> Eng onbezuelte Rechnung bleift geschëllt: se mat engem Avoir ze annuléiere géif eng Fuerderung verschwannen loossen, déi Dir nach recouvréiere kënnt. D'Regulariséierung vun der TVA op enger onerhëftbarer Fuerderung fällt ënner den <strong>Artikel 33 LIVA</strong>, deen d'Konditiounen engem groussherzogleche Reglement iwwerléisst – e Punkt fir Ären Fiduciaire.</p>

<h2>Pflichtmentiounen</h2>

<p>Den <strong>Artikel 63, Paragraf 2, LIVA</strong> stellt „all Dokument oder Message, dat d'ursprénglech Rechnung ännert a spezifesch an onzweideiteg drop verweist" enger Rechnung gläich. En Avoir dréit dofir déiselwecht Mentioune wéi eng Rechnung, plus:</p>

<ul>
    <li>D'Mentioun <strong>„Avoir"</strong>, kloer siichtbar</li>
    <li>D'<strong>Referenz op d'ursprénglech Rechnung</strong> (Nummer an Datum)</li>
    <li>De <strong>Grond</strong> vum Avoir – net vum Artikel 63 verlaangt, mä en dokumentéiert d'Operatioun bei enger Kontroll</li>
    <li>D'Montanten am <strong>Negativen</strong> (oder mat der Mentioun „ofzezéien")</li>
    <li>D'TVA-Opdeelung (déiselwecht Sätz wéi op der ursprénglecher Rechnung)</li>
    <li>Eng <strong>eege Nummeréierung</strong> (z. B. AV-2026-001)</li>
</ul>

<h2>Impakt op d'TVA</h2>

<p>En Avoir huet en direkten Impakt op Är TVA-Deklaratioun. D'Basis vun der Impositioun ass déi fakturéiert Remuneratioun, „ouni Präjudiz vun enger eventueller Regulariséierung", déi den <strong>Artikel 33 LIVA</strong> virgesäit:</p>

<ul>
    <li>D'TVA vum Avoir gëtt vun der <strong>agesammelter TVA ofgezunn</strong></li>
    <li>Si muss an der <strong>selwechter Period</strong> deklaréiert ginn, an där se erausgi gouf</li>
    <li>Huet de Client d'TVA scho ofgezunn, muss hien <strong>seng eege Deklaratioun regulariséieren</strong></li>
</ul>

<h2>En Avoir mat faktur.lu erstellen</h2>

<p>Mat faktur.lu ass d'Erstelle vun engem Avoir einfach a sécher:</p>

<ol>
    <li>Maacht d'ursprénglech Rechnung op</li>
    <li>Klickt op <strong>„En Avoir erstellen"</strong></li>
    <li>Wielt de Grond</li>
    <li>Entscheet Iech fir eng total oder deelweis Annulatioun</li>
    <li>Den Avoir gëtt automatesch mat alle verlaangte Mentioune generéiert</li>
</ol>

<p>D'Referenz op d'ursprénglech Rechnung gëtt automatesch derbäigesat, an d'Montante gi negativ berechent. Ären FAIA-Export gëtt direkt nogefouert.</p>

<p class="text-sm text-slate-500"><em>D'Akommessbuch ass vum Plang Essentiel un disponibel; den FAIA-Export ass scho vum gratis Plang un abegraff.</em></p>

<h2>Ënnerscheed tëscht „note de crédit" an „avoir"</h2>

<p>An der Praxis sinn <strong>„note de crédit"</strong> an <strong>„avoir"</strong> zu Lëtzebuerg synonym. D'Verwaltung benotzt op Franséisch „note de crédit", mä „avoir" ass de geleefegen Alldagswuert.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtmentiounen →</a></li><li><a href="/lb/blog/komplette-guide-rechnungsstellung-letzebuerg-2026" class="text-primary-500 hover:text-primary-600 text-sm">Guide vun der Fakturatioun →</a></li><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Verglach: seng Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Erstellt Är Avoiren mat engem Klick</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert Är Avoiren automatesch mat alle Pflichtmentiounen, mat der ursprénglecher Rechnung verbonnen a konform mat der Lëtzebuerger Gesetzgebung.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
<p class="text-sm text-slate-500 mt-6"><em>Artikel den 9. Juni 2026 aktualiséiert.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze kontrolléieren</p>
    <p>Lëtzebuerger Schwellen, Sätz a Steierprozedure kënne sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Ären Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">Um erro numa fatura? Um cliente que devolve um produto? Tem de emitir uma <strong>nota de crédito</strong>. Eis como a estabelecer corretamente no Luxemburgo.</p>

<h2>O que é uma nota de crédito?</h2>

<p>Uma nota de crédito é um documento contabilístico que <strong>anula ou corrige parcialmente uma fatura</strong> já emitida. Por convenção, os seus montantes aparecem a <strong>negativo</strong> — ou acompanhados da menção «a deduzir».</p>

<p><strong>Importante:</strong> no Luxemburgo é <strong>proibido alterar ou eliminar uma fatura</strong> depois de finalizada (princípio da imutabilidade). A única forma de corrigir um erro é emitir uma nota de crédito.</p>

<h2>Quando emitir uma nota de crédito?</h2>

<ul>
    <li><strong>Erro de faturação</strong>: montante incorreto, IVA errado, cliente errado</li>
    <li><strong>Devolução de mercadoria</strong>: o cliente devolve toda ou parte da encomenda</li>
    <li><strong>Desconto comercial</strong>: acordo sobre um abatimento após a faturação</li>
    <li><strong>Anulação da prestação</strong>: o serviço não foi prestado</li>
</ul>

<p><strong>O simples não pagamento não faz parte da lista.</strong> Uma fatura por pagar continua a ser devida: anulá-la por nota de crédito faria desaparecer um crédito que ainda pode cobrar. A regularização do IVA sobre um crédito tornado incobrável decorre do <strong>artigo 33 LIVA</strong>, que remete as condições para um regulamento grão-ducal — um ponto a tratar com o seu <em>fiduciaire</em>.</p>

<h2>Menções obrigatórias</h2>

<p>O <strong>artigo 63, n.º 2, LIVA</strong> equipara a uma fatura «qualquer documento ou mensagem que altere a fatura inicial e que a ela faça referência de forma específica e inequívoca». Uma nota de crédito contém, por isso, as mesmas menções de uma fatura, mais:</p>

<ul>
    <li>A menção <strong>«Nota de crédito»</strong>, claramente visível</li>
    <li>A <strong>referência à fatura de origem</strong> (número e data)</li>
    <li>O <strong>motivo</strong> da nota de crédito — não exigido pelo artigo 63, mas documenta a operação numa inspeção</li>
    <li>Os montantes a <strong>negativo</strong> (ou com a menção «a deduzir»)</li>
    <li>A repartição do IVA (as mesmas taxas da fatura de origem)</li>
    <li>Uma <strong>numeração própria</strong> (ex.: NC-2026-001 ou AV-2026-001)</li>
</ul>

<h2>Impacto no IVA</h2>

<p>A nota de crédito tem um impacto direto na sua declaração de IVA. A base tributável é a remuneração faturada, «sem prejuízo de uma eventual regularização» prevista no <strong>artigo 33 LIVA</strong>:</p>

<ul>
    <li>O IVA da nota de crédito é <strong>deduzido ao IVA liquidado</strong></li>
    <li>Deve ser declarado no <strong>mesmo período</strong> em que foi emitida</li>
    <li>Se o cliente já deduziu o IVA, terá de <strong>regularizar a sua própria declaração</strong></li>
</ul>

<h2>Criar uma nota de crédito com o faktur.lu</h2>

<p>Com o faktur.lu, criar uma nota de crédito é simples e seguro:</p>

<ol>
    <li>Abra a fatura de origem</li>
    <li>Clique em <strong>«Criar uma nota de crédito»</strong></li>
    <li>Selecione o motivo</li>
    <li>Escolha uma anulação total ou parcial</li>
    <li>A nota de crédito é gerada automaticamente com todas as menções exigidas</li>
</ol>

<p>A referência à fatura de origem é acrescentada automaticamente e os montantes são calculados a negativo. A sua exportação FAIA é atualizada de imediato.</p>

<p class="text-sm text-slate-500"><em>O livro de receitas está disponível a partir do plano Essentiel; a exportação FAIA está incluída desde o plano gratuito.</em></p>

<h2>Diferença entre nota de crédito e «avoir»</h2>

<p>Na prática, os termos <strong>«nota de crédito»</strong> e <strong>«avoir»</strong> são sinónimos no Luxemburgo. A administração usa em francês «note de crédit», enquanto «avoir» é a forma corrente do dia a dia.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li><li><a href="/pt/blog/guia-completo-da-faturacao-no-luxemburgo-em-2026" class="text-primary-500 hover:text-primary-600 text-sm">guia da faturação →</a></li><li><a href="/pt/blog/como-escolher-o-seu-software-de-faturacao-no-luxemburgo" class="text-primary-500 hover:text-primary-600 text-sm">Comparação: escolher o seu software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Crie as suas notas de crédito num clique</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera automaticamente as suas notas de crédito com todas as menções obrigatórias, ligadas à fatura de origem e conformes à legislação luxemburguesa.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 9 de junho de 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, as taxas e os procedimentos fiscais luxemburgueses podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu <em>fiduciaire</em> ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
