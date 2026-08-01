<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « 5 erreurs fréquentes sur une facture freelance ».
 *
 * 1. Français — cinq corrections.
 *
 *    a) Chapô cassé : « plus de de nombreuses factures freelance contiennent
 *       au moins une erreur ». Une statistique a manifestement été retirée
 *       sans recoudre la phrase, publiée telle quelle.
 *
 *    b) Le numéro RCS était listé parmi les mentions obligatoires sans sa
 *       condition. L'article « mentions obligatoires », déjà corrigé, précise
 *       que cette obligation vient de la législation sur le registre de
 *       commerce et non de la loi TVA : les deux articles se contredisaient.
 *
 *    c) Taux 14 % décrit comme visant « certains services spécifiques ». Il
 *       porte en réalité surtout sur des biens (vins tranquilles, combustibles
 *       minéraux solides, mazout de chauffage, imprimés publicitaires).
 *
 *    d) Taux 3 % : la liste omettait la restauration et l'hôtellerie, qui sont
 *       la particularité luxembourgeoise la plus notable de ce taux, et citait
 *       la presse sans source. Alignée sur l'article TVA déjà vérifié.
 *
 *    e) Délai de 30 jours : court à compter de la RÉCEPTION de la facture
 *       (loi modifiée du 18 avril 2004), pas de son émission.
 *
 *    Le CTA promettait « Zéro risque d'erreur ». Remplacé par l'énoncé de ce
 *    que le produit fait réellement.
 *
 * 2. DE, EN, LB, PT — traductions reconstruites à 100 %. Le luxembourgeois
 *    tombait à 2 910 caractères contre 6 203 en français, soit 47 %.
 */
return new class extends Migration
{
    private const KEY = '5-erreurs-frequentes-facture-freelance-luxembourg';

    private const FR_FIXES = [
        [
            'Pourtant, <strong>plus de de nombreuses factures freelance contiennent au moins une erreur</strong>.',
            'Pourtant, <strong>beaucoup de factures freelance contiennent au moins une erreur</strong>.',
        ],
        [
            '<li>Votre <strong>numéro RCS</strong> ou matricule</li>',
            '<li>Votre <strong>numéro RCS</strong> si vous êtes commerçant ou société inscrite - obligation issue de la législation sur le registre de commerce, et non de la loi TVA</li>',
        ],
        [
            '<li><strong>14%</strong> : taux intermédiaire (certains services spécifiques)</li>',
            '<li><strong>14%</strong> : taux intermédiaire (vins tranquilles, combustibles minéraux solides, mazout de chauffage, imprimés publicitaires)</li>',
        ],
        [
            '<li><strong>3%</strong> : taux super-réduit (alimentation, livres, presse)</li>',
            '<li><strong>3%</strong> : taux super-réduit (alimentation, restauration, hôtellerie, livres, médicaments)</li>',
        ],
        [
            "<li><strong>Date d'échéance</strong> : sans échéance précisée, le délai légal est de 30 jours, mais votre client peut l'ignorer</li>",
            "<li><strong>Date d'échéance</strong> : sans échéance précisée, le délai légal est de 30 jours à compter de la <strong>réception</strong> de la facture, mais votre client peut l'ignorer</li>",
        ],
        [
            "faktur.lu vérifie automatiquement chaque facture : numérotation, mentions légales, TVA, immuabilité. Zéro risque d'erreur.",
            'faktur.lu verrouille la numérotation, ajoute les mentions obligatoires et applique le taux de TVA correspondant au client. Une facture finalisée reste immuable.',
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
<p class="lead">Als Freiberufler in Luxemburg sind Ihre Rechnungen Ihr berufliches Aushaengeschild und zugleich ein Rechtsdokument. Dennoch enthalten <strong>viele Freelancer-Rechnungen mindestens einen Fehler</strong>. Hier sind die fuenf haeufigsten – und wie Sie sie vermeiden.</p>

<h2>Fehler 1: Nicht fortlaufende Nummerierung</h2>

<p>Das luxemburgische Recht verlangt eine <strong>streng fortlaufende Nummerierung</strong> Ihrer Rechnungen. Das bedeutet:</p>

<ul>
    <li><strong>Keine Luecke</strong>: Ist Ihre letzte Rechnung F-2026-042, muss die naechste F-2026-043 sein</li>
    <li><strong>Keine Doppelung</strong>: Zwei Rechnungen duerfen nicht dieselbe Nummer tragen</li>
    <li><strong>Keine Aenderung</strong>: Eine Nummer laesst sich nach der Ausstellung nicht mehr aendern</li>
</ul>

<p><strong>Warum ist das gravierend?</strong> Bei einer Steuerpruefung legt eine unstimmige Nummerierung den Verdacht nahe, dass Rechnungen geloescht oder verschwiegen wurden. Fuer die AED ist das ein <strong>Warnsignal</strong>.</p>

<p><strong>Loesung:</strong> Nutzen Sie eine Software, die die Nummern automatisch vergibt. Mit faktur.lu ist die Nummerierung fortlaufend, lueckenlos und unveraenderlich.</p>

<h2>Fehler 2: Fehlende Pflichtangaben</h2>

<p>Eine Rechnung in Luxemburg muss zwingend enthalten:</p>

<ul>
    <li><strong>Name und Anschrift</strong> Ihres Unternehmens</li>
    <li>Ihre <strong>MwSt-Nummer</strong> (oder den Befreiungshinweis)</li>
    <li>Ihre <strong>RCS-Nummer</strong>, sofern Sie als Kaufmann oder Gesellschaft eingetragen sind – eine Pflicht aus dem Handelsregisterrecht, nicht aus dem MwSt-Gesetz</li>
    <li>Name und Anschrift des <strong>Kunden</strong></li>
    <li>Die <strong>Rechnungsnummer</strong></li>
    <li>Das <strong>Ausstellungsdatum</strong></li>
    <li>Die <strong>detaillierte Beschreibung</strong> der Leistung</li>
    <li>Den <strong>Nettobetrag, den MwSt-Satz und den Bruttobetrag</strong></li>
</ul>

<p><strong>Der klassische Fehler:</strong> die MwSt-Nummer des Kunden bei innergemeinschaftlichen Rechnungen vergessen, oder den Befreiungsgrund nicht angeben, wenn die MwSt 0 % betraegt.</p>

<p><strong>Loesung:</strong> faktur.lu ergaenzt die Pflichtangaben automatisch, je nach Kundentyp und MwSt-Szenario.</p>

<h2>Fehler 3: Falscher MwSt-Satz</h2>

<p>In Luxemburg gibt es <strong>vier MwSt-Saetze</strong>:</p>

<ul>
    <li><strong>17 %</strong>: Normalsatz (die meisten Dienstleistungen)</li>
    <li><strong>14 %</strong>: Zwischensatz (stille Weine, feste Mineralbrennstoffe, Heizoel, Werbedrucksachen)</li>
    <li><strong>8 %</strong>: ermaessigter Satz (Strom, Gas, Friseurleistungen …)</li>
    <li><strong>3 %</strong>: stark ermaessigter Satz (Lebensmittel, Gastronomie, Hotellerie, Buecher, Arzneimittel)</li>
</ul>

<p><strong>Die haeufigen Fehler:</strong></p>

<ul>
    <li>20 % anwenden (franzoesischer Satz) statt 17 %</li>
    <li>Einem innergemeinschaftlichen Kunden MwSt berechnen (Reverse Charge)</li>
    <li>Die MwSt bei einem luxemburgischen B2C-Kunden vergessen</li>
    <li>Den Befreiungshinweis nicht angeben, wenn man unter der Schwelle liegt</li>
</ul>

<p><strong>Loesung:</strong> faktur.lu bestimmt Satz und Hinweis automatisch nach Land und Kundentyp.</p>

<h2>Fehler 4: Keine Zahlungsbedingungen</h2>

<p>Viele Freiberufler vergessen, die <strong>Zahlungsmodalitaeten</strong> auf ihren Rechnungen anzugeben:</p>

<ul>
    <li><strong>Faelligkeitsdatum</strong>: ohne angegebene Faelligkeit betraegt die gesetzliche Frist 30 Tage ab <strong>Erhalt</strong> der Rechnung – Ihr Kunde kann das aber ignorieren</li>
    <li><strong>Zahlungsweg</strong>: geben Sie Ihre IBAN an, das erleichtert die Ueberweisung</li>
    <li><strong>Verzugszinsen</strong>: nennen Sie den anwendbaren Satz (EZB-Satz plus 8 Punkte)</li>
    <li><strong>Pauschalentschaedigung</strong>: die 40 EUR fuer Beitreibungskosten</li>
</ul>

<p><strong>Warum das zaehlt:</strong> ohne klare Bedingungen fehlt Ihnen bei Zahlungsausfall die vertragliche Grundlage, um Verzugszinsen geltend zu machen.</p>

<h2>Fehler 5: Eine finalisierte Rechnung aendern</h2>

<p>Sobald eine Rechnung an den Kunden versandt ist, ist sie <strong>unveraenderlich</strong>. Sie koennen nicht:</p>

<ul>
    <li>Den Betrag aendern</li>
    <li>Den Kunden aendern</li>
    <li>Die Rechnung loeschen</li>
    <li>Die Nummer aendern</li>
</ul>

<p>Ist Ihnen ein Fehler unterlaufen, ist die <strong>einzige rechtlich zulaessige Loesung</strong> eine <strong>Gutschrift</strong>, welche die fehlerhafte Rechnung storniert, gefolgt von einer neuen, korrigierten Rechnung.</p>

<p><strong>Der klassische Fehler:</strong> die Excel- oder Word-Datei der Rechnung aendern und eine „korrigierte Fassung" erneut versenden. Bei einer Steuerpruefung kann das als <strong>Faelschung</strong> gewertet werden.</p>

<h2>Bonus: die Checkliste der perfekten Rechnung</h2>

<p>Pruefen Sie vor dem Versand jeder Rechnung:</p>

<ul>
    <li>&#9745; Korrekte fortlaufende Nummer</li>
    <li>&#9745; Ausstellungs- und Faelligkeitsdatum</li>
    <li>&#9745; Alle Pflichtangaben vorhanden</li>
    <li>&#9745; Richtiger MwSt-Satz je nach Szenario</li>
    <li>&#9745; IBAN und Zahlungsbedingungen</li>
    <li>&#9745; Netto-, MwSt- und Bruttobetraege korrekt</li>
    <li>&#9745; Klare Beschreibung der Leistung</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben →</a></li><li><a href="/de/blog/gutschrift-luxemburg-korrekt-erstellen" class="text-primary-500 hover:text-primary-600 text-sm">Gutschrift →</a></li><li><a href="/de/blog/vollstaendiger-leitfaden-rechnungsstellung-luxemburg-2026" class="text-primary-500 hover:text-primary-600 text-sm">vollstaendiger Leitfaden →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fehlerfrei fakturieren mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu sperrt die Nummerierung, ergaenzt die Pflichtangaben und wendet den zum Kunden passenden MwSt-Satz an. Eine finalisierte Rechnung bleibt unveraenderlich.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead">As a freelancer in Luxembourg, your invoices are both your professional shop window and a legal document. Yet <strong>many freelance invoices contain at least one mistake</strong>. Here are the five most common ones – and how to avoid them.</p>

<h2>Mistake 1: Non-sequential numbering</h2>

<p>Luxembourg law requires <strong>strictly sequential numbering</strong> of your invoices. That means:</p>

<ul>
    <li><strong>No gaps</strong>: if your last invoice is F-2026-042, the next must be F-2026-043</li>
    <li><strong>No duplicates</strong>: two invoices cannot share the same number</li>
    <li><strong>No changes</strong>: you cannot alter a number once the invoice has been issued</li>
</ul>

<p><strong>Why it matters:</strong> in a tax audit, inconsistent numbering suggests that invoices have been deleted or concealed. For the AED, that is a <strong>red flag</strong>.</p>

<p><strong>Solution:</strong> use software that generates the numbers automatically. With faktur.lu, numbering is sequential, gapless and immutable.</p>

<h2>Mistake 2: Missing legal information</h2>

<p>An invoice in Luxembourg must contain:</p>

<ul>
    <li>The <strong>name and address</strong> of your business</li>
    <li>Your <strong>VAT number</strong> (or the exemption mention)</li>
    <li>Your <strong>RCS number</strong> if you are a registered trader or company – an obligation arising from company-register legislation, not from the VAT law</li>
    <li>The <strong>client's</strong> name and address</li>
    <li>The <strong>invoice number</strong></li>
    <li>The <strong>date of issue</strong></li>
    <li>A <strong>detailed description</strong> of the service</li>
    <li>The <strong>net amount, the VAT rate and the gross amount</strong></li>
</ul>

<p><strong>The classic slip:</strong> forgetting the client's VAT number on intra-EU invoices, or failing to state the grounds for exemption when VAT is at 0%.</p>

<p><strong>Solution:</strong> faktur.lu adds the mandatory information automatically, according to the client type and the VAT scenario.</p>

<h2>Mistake 3: The wrong VAT rate</h2>

<p>Luxembourg has <strong>four VAT rates</strong>:</p>

<ul>
    <li><strong>17%</strong>: standard rate (most services)</li>
    <li><strong>14%</strong>: intermediate rate (still wines, solid mineral fuels, heating oil, advertising print)</li>
    <li><strong>8%</strong>: reduced rate (electricity, gas, hairdressing…)</li>
    <li><strong>3%</strong>: super-reduced rate (food, restaurants, hotels, books, medicines)</li>
</ul>

<p><strong>The common errors:</strong></p>

<ul>
    <li>Applying 20% (the French rate) instead of 17%</li>
    <li>Charging VAT to an intra-EU client (reverse charge applies)</li>
    <li>Forgetting VAT for a Luxembourg B2C client</li>
    <li>Failing to state the VAT exemption when below the threshold</li>
</ul>

<p><strong>Solution:</strong> faktur.lu determines the right rate and the right mention automatically, based on country and client type.</p>

<h2>Mistake 4: No payment terms</h2>

<p>Many freelancers forget to set out the <strong>payment terms</strong> on their invoices:</p>

<ul>
    <li><strong>Due date</strong>: with no due date stated, the legal period is 30 days from <strong>receipt</strong> of the invoice – but your client may well ignore it</li>
    <li><strong>Payment method</strong>: give your IBAN to make the transfer easy</li>
    <li><strong>Late-payment interest</strong>: state the applicable rate (ECB rate plus 8 points)</li>
    <li><strong>Fixed compensation</strong>: the EUR 40 for recovery costs</li>
</ul>

<p><strong>Why this counts:</strong> without clear terms, you have no contractual basis on which to claim late-payment interest if the invoice goes unpaid.</p>

<h2>Mistake 5: Editing a finalised invoice</h2>

<p>Once an invoice has been sent to the client, it is <strong>immutable</strong>. You cannot:</p>

<ul>
    <li>Change the amount</li>
    <li>Change the client</li>
    <li>Delete the invoice</li>
    <li>Change the number</li>
</ul>

<p>If you made a mistake, the <strong>only lawful route</strong> is to issue a <strong>credit note</strong> cancelling the incorrect invoice, then create a new, corrected one.</p>

<p><strong>The classic slip:</strong> editing the Excel or Word file and resending a "corrected version". In a tax audit, that can be treated as <strong>falsification</strong>.</p>

<h2>Bonus: the perfect-invoice checklist</h2>

<p>Before sending each invoice, check:</p>

<ul>
    <li>&#9745; Correct sequential number</li>
    <li>&#9745; Date of issue and due date</li>
    <li>&#9745; All mandatory information present</li>
    <li>&#9745; Right VAT rate for the scenario</li>
    <li>&#9745; IBAN and payment terms</li>
    <li>&#9745; Net, VAT and gross amounts correct</li>
    <li>&#9745; Clear description of the service</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mandatory information →</a></li><li><a href="/en/blog/credit-note-luxembourg-how-to-issue-correctly" class="text-primary-500 hover:text-primary-600 text-sm">credit note →</a></li><li><a href="/en/blog/complete-guide-invoicing-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">complete guide →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice without mistakes using faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu locks the numbering, adds the mandatory information and applies the VAT rate matching the client. A finalised invoice stays immutable.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">Als Freelancer zu Lëtzebuerg sinn Är Rechnungen Är berufflech Visitekaart an zugläich e legalt Dokument. Trotzdem enthalen <strong>vill Freelancer-Rechnungen op mannst ee Feeler</strong>. Hei sinn déi fënnef heefegst – a wéi Dir se vermeit.</p>

<h2>Feeler 1: Net sequentiell Nummeréierung</h2>

<p>D'Lëtzebuerger Gesetz verlaangt eng <strong>strikt sequentiell Nummeréierung</strong> vun Äre Rechnungen. Dat heescht:</p>

<ul>
    <li><strong>Kee Lach</strong>: Ass Är lescht Rechnung F-2026-042, muss déi nächst F-2026-043 sinn</li>
    <li><strong>Keng Duebel</strong>: Zwou Rechnunge kënnen net déiselwecht Nummer hunn</li>
    <li><strong>Keng Ännerung</strong>: Eng Nummer kann no der Erausgab net méi geännert ginn</li>
</ul>

<p><strong>Firwat ass dat grav?</strong> Bei enger Steierkontroll léisst eng inkohärent Nummeréierung vermuten, datt Rechnunge geläscht oder verstoppt goufen. Fir d'AED ass dat e <strong>Warnsignal</strong>.</p>

<p><strong>Léisung:</strong> Benotzt eng Software, déi d'Nummeren automatesch generéiert. Mat faktur.lu ass d'Nummeréierung sequentiell, ouni Lach an onverännerlech.</p>

<h2>Feeler 2: Feelend Pflichtmentiounen</h2>

<p>Eng Rechnung zu Lëtzebuerg muss obligatoresch enthalen:</p>

<ul>
    <li><strong>Numm an Adress</strong> vun Ärem Betrib</li>
    <li>Är <strong>TVA-Nummer</strong> (oder d'Mentioun vun der Befreiung)</li>
    <li>Är <strong>RCS-Nummer</strong>, wann Dir Händler oder ageschriwwe Gesellschaft sidd – eng Obligatioun aus dem Handelsregisterrecht, net aus dem TVA-Gesetz</li>
    <li>Numm an Adress vum <strong>Client</strong></li>
    <li>D'<strong>Rechnungsnummer</strong></li>
    <li>D'<strong>Datum vun der Erausgab</strong></li>
    <li>D'<strong>detailléiert Beschreiwung</strong> vun der Leeschtung</li>
    <li>De <strong>Montant HT, den TVA-Saz an de Montant TTC</strong></li>
</ul>

<p><strong>De klassesche Feeler:</strong> d'TVA-Nummer vum Client bei intracommunautäre Rechnunge vergiessen, oder de Grond vun der Exonératioun net uginn, wann d'TVA op 0 % steet.</p>

<p><strong>Léisung:</strong> faktur.lu setzt d'Pflichtmentiounen automatesch derbäi, no Clienttyp an TVA-Szenario.</p>

<h2>Feeler 3: Falschen TVA-Saz</h2>

<p>Zu Lëtzebuerg ginn et <strong>véier TVA-Sätz</strong>:</p>

<ul>
    <li><strong>17 %</strong>: Normalsaz (déi meescht Servicer)</li>
    <li><strong>14 %</strong>: Zwëschesaz (stëll Wäiner, fest Mineralbrennstoffer, Heizueleg, Reklammdrock)</li>
    <li><strong>8 %</strong>: reduzéierte Saz (Stroum, Gas, Coiffure …)</li>
    <li><strong>3 %</strong>: staark reduzéierte Saz (Liewensmëttel, Restauratioun, Hotellerie, Bicher, Medikamenter)</li>
</ul>

<p><strong>Déi heefeg Feeler:</strong></p>

<ul>
    <li>20 % uwenden (franséische Saz) amplaz 17 %</li>
    <li>Engem intracommunautäre Client TVA verrechnen (Autoliquidatioun)</li>
    <li>D'TVA bei engem Lëtzebuerger B2C-Client vergiessen</li>
    <li>D'TVA-Franchise net ernimmen, wann een ënner der Schwell läit</li>
</ul>

<p><strong>Léisung:</strong> faktur.lu bestëmmt de richtege Saz an déi richteg Mentioun automatesch no Land a Clienttyp.</p>

<h2>Feeler 4: Keng Bezuelungskonditiounen</h2>

<p>Vill Freelancer vergiessen, d'<strong>Bezuelungsmodalitéiten</strong> op hire Rechnungen unzeginn:</p>

<ul>
    <li><strong>Fällegkeetsdatum</strong>: ouni ugewisen Fällegkeet ass déi gesetzlech Frist 30 Deeg ab <strong>Empfang</strong> vun der Rechnung – Äre Client kann dat awer ignoréieren</li>
    <li><strong>Bezuelungsmëttel</strong>: gitt Är IBAN un, dat erliichtert d'Iwwerweisung</li>
    <li><strong>Verzuchszënsen</strong>: nennt de gëltege Saz (BCE-Saz plus 8 Punkten)</li>
    <li><strong>Forfaitaresch Entschiedegung</strong>: déi 40 EUR fir Recouvrementskäschten</li>
</ul>

<p><strong>Firwat dat zielt:</strong> ouni kloer Konditiounen hutt Dir bei engem Impayé keng vertraglech Basis fir Verzuchszënsen ze fuerderen.</p>

<h2>Feeler 5: Eng finaliséiert Rechnung änneren</h2>

<p>Soubal eng Rechnung un de Client geschéckt ass, ass se <strong>onverännerlech</strong>. Dir kënnt net:</p>

<ul>
    <li>De Montant änneren</li>
    <li>De Client änneren</li>
    <li>D'Rechnung läschen</li>
    <li>D'Nummer änneren</li>
</ul>

<p>Wann Dir e Feeler gemaach hutt, ass déi <strong>eenzeg legal Léisung</strong> en <strong>Avoir</strong> erauszeginn, deen déi falsch Rechnung annuléiert, an duerno eng nei, korrigéiert Rechnung ze erstellen.</p>

<p><strong>De klassesche Feeler:</strong> den Excel- oder Word-Fichier vun der Rechnung änneren an eng „korrigéiert Versioun" nei schécken. Bei enger Steierkontroll kann dat als <strong>Fälschung</strong> gewäert ginn.</p>

<h2>Bonus: d'Checklëscht vun der perfekter Rechnung</h2>

<p>Ier Dir all Rechnung verschéckt, kontrolléiert:</p>

<ul>
    <li>&#9745; Richteg sequentiell Nummer</li>
    <li>&#9745; Datum vun der Erausgab a Fällegkeetsdatum</li>
    <li>&#9745; All Pflichtmentioune präsent</li>
    <li>&#9745; Richtegen TVA-Saz no Szenario</li>
    <li>&#9745; IBAN a Bezuelungskonditiounen</li>
    <li>&#9745; Montanten HT, TVA an TTC korrekt</li>
    <li>&#9745; Kloer Beschreiwung vun der Leeschtung</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtmentiounen →</a></li><li><a href="/lb/blog/gutschrift-letzebuerg-richteg-erstellen" class="text-primary-500 hover:text-primary-600 text-sm">Avoir →</a></li><li><a href="/lb/blog/komplette-guide-rechnungsstellung-letzebuerg-2026" class="text-primary-500 hover:text-primary-600 text-sm">komplette Guide →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert ouni Feeler mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu spaart d'Nummeréierung, setzt d'Pflichtmentiounen derbäi a wennt den TVA-Saz un, deen zum Client passt. Eng finaliséiert Rechnung bleift onverännerlech.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">Enquanto freelancer no Luxemburgo, as suas faturas são o seu cartão de visita profissional e, ao mesmo tempo, um documento legal. Ainda assim, <strong>muitas faturas de freelancers contêm pelo menos um erro</strong>. Eis os cinco mais frequentes – e como evitá-los.</p>

<h2>Erro 1: Numeração não sequencial</h2>

<p>A lei luxemburguesa exige uma <strong>numeração estritamente sequencial</strong> das suas faturas. Isso significa:</p>

<ul>
    <li><strong>Sem falhas</strong>: se a sua última fatura é F-2026-042, a seguinte tem de ser F-2026-043</li>
    <li><strong>Sem duplicados</strong>: duas faturas não podem ter o mesmo número</li>
    <li><strong>Sem alterações</strong>: não pode mudar um número depois de emitida a fatura</li>
</ul>

<p><strong>Porque é grave?</strong> Numa inspeção fiscal, uma numeração incoerente leva a supor que foram eliminadas ou dissimuladas faturas. Para a AED, é um <strong>sinal de alerta</strong>.</p>

<p><strong>Solução:</strong> use um software que gere os números automaticamente. Com o faktur.lu, a numeração é sequencial, sem falhas e imutável.</p>

<h2>Erro 2: Menções legais em falta</h2>

<p>Uma fatura no Luxemburgo tem obrigatoriamente de conter:</p>

<ul>
    <li>O <strong>nome e a morada</strong> da sua empresa</li>
    <li>O seu <strong>número de IVA</strong> (ou a menção de isenção)</li>
    <li>O seu <strong>número RCS</strong> se for comerciante ou sociedade inscrita – obrigação decorrente da legislação do registo comercial, e não da lei do IVA</li>
    <li>O nome e a morada do <strong>cliente</strong></li>
    <li>O <strong>número da fatura</strong></li>
    <li>A <strong>data de emissão</strong></li>
    <li>A <strong>descrição detalhada</strong> da prestação</li>
    <li>O <strong>valor sem IVA, a taxa de IVA e o valor com IVA</strong></li>
</ul>

<p><strong>O erro clássico:</strong> esquecer o número de IVA do cliente nas faturas intracomunitárias, ou não indicar o motivo de isenção quando o IVA está a 0%.</p>

<p><strong>Solução:</strong> o faktur.lu acrescenta automaticamente as menções obrigatórias consoante o tipo de cliente e o cenário de IVA.</p>

<h2>Erro 3: Taxa de IVA errada</h2>

<p>No Luxemburgo existem <strong>quatro taxas de IVA</strong>:</p>

<ul>
    <li><strong>17%</strong>: taxa normal (a maioria das prestações de serviços)</li>
    <li><strong>14%</strong>: taxa intermédia (vinhos tranquilos, combustíveis minerais sólidos, gasóleo de aquecimento, impressos publicitários)</li>
    <li><strong>8%</strong>: taxa reduzida (eletricidade, gás, cabeleireiro…)</li>
    <li><strong>3%</strong>: taxa super-reduzida (alimentação, restauração, hotelaria, livros, medicamentos)</li>
</ul>

<p><strong>Os erros correntes:</strong></p>

<ul>
    <li>Aplicar 20% (taxa francesa) em vez de 17%</li>
    <li>Faturar com IVA a um cliente intracomunitário (autoliquidação)</li>
    <li>Esquecer o IVA para um cliente luxemburguês B2C</li>
    <li>Não mencionar a isenção de IVA quando se está abaixo do limiar</li>
</ul>

<p><strong>Solução:</strong> o faktur.lu determina automaticamente a taxa e a menção corretas consoante o país e o tipo de cliente.</p>

<h2>Erro 4: Ausência de condições de pagamento</h2>

<p>Muitos freelancers esquecem-se de precisar as <strong>modalidades de pagamento</strong> nas suas faturas:</p>

<ul>
    <li><strong>Data de vencimento</strong>: sem vencimento indicado, o prazo legal é de 30 dias a contar da <strong>receção</strong> da fatura – mas o seu cliente pode ignorá-lo</li>
    <li><strong>Meio de pagamento</strong>: indique o seu IBAN para facilitar a transferência</li>
    <li><strong>Juros de mora</strong>: mencione a taxa aplicável (taxa do BCE mais 8 pontos)</li>
    <li><strong>Indemnização fixa</strong>: os 40 EUR de custos de cobrança</li>
</ul>

<p><strong>Porque é importante:</strong> sem condições claras, não tem base contratual para reclamar juros de mora em caso de não pagamento.</p>

<h2>Erro 5: Alterar uma fatura finalizada</h2>

<p>Assim que uma fatura é enviada ao cliente, torna-se <strong>imutável</strong>. Não pode:</p>

<ul>
    <li>Alterar o montante</li>
    <li>Mudar o cliente</li>
    <li>Eliminar a fatura</li>
    <li>Mudar o número</li>
</ul>

<p>Se cometeu um erro, a <strong>única solução legal</strong> é emitir uma <strong>nota de crédito</strong> que anule a fatura errada e depois criar uma nova fatura corrigida.</p>

<p><strong>O erro clássico:</strong> alterar o ficheiro Excel ou Word da fatura e reenviar uma «versão corrigida». Numa inspeção fiscal, isso pode ser considerado <strong>falsificação</strong>.</p>

<h2>Bónus: a checklist da fatura perfeita</h2>

<p>Antes de enviar cada fatura, verifique:</p>

<ul>
    <li>&#9745; Número sequencial correto</li>
    <li>&#9745; Data de emissão e data de vencimento</li>
    <li>&#9745; Todas as menções legais presentes</li>
    <li>&#9745; Taxa de IVA certa para o cenário</li>
    <li>&#9745; IBAN e condições de pagamento</li>
    <li>&#9745; Valores sem IVA, IVA e com IVA corretos</li>
    <li>&#9745; Descrição clara da prestação</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa" class="text-primary-500 hover:text-primary-600 text-sm">menções obrigatórias →</a></li><li><a href="/pt/blog/nota-de-credito-no-luxemburgo-como-a-estabelecer-corretamente" class="text-primary-500 hover:text-primary-600 text-sm">nota de crédito →</a></li><li><a href="/pt/blog/guia-completo-da-faturacao-no-luxemburgo-em-2026" class="text-primary-500 hover:text-primary-600 text-sm">guia completo →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature sem erros com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">O faktur.lu bloqueia a numeração, acrescenta as menções obrigatórias e aplica a taxa de IVA correspondente ao cliente. Uma fatura finalizada permanece imutável.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
