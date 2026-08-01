<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article pivot « Article 17 LIVA : autoliquidation TVA B2B intra-UE ».
 *
 * 1. Français — deux revendications produit fausses et une sous-estimation :
 *
 *    - « État récapitulatif généré automatiquement en fin de période » : faux.
 *      Aucune fonctionnalité de liste récapitulative n'existe. Le module
 *      comptable expose accounting_export, faia_export, fiscal_summary et
 *      revenue_book — aucun état récapitulatif intracommunautaire. La même
 *      revendication figurait dans le CTA.
 *    - « FR, DE, EN, PT » : le PDF gère cinq langues, luxembourgeois compris
 *      (InvoicePdfService::$supportedLocales). L'article sous-estimait.
 *
 *    Les autres revendications du bloc sont vérifiées et conservées : la
 *    validation VIES existe (ViesProvider, câblée dans ClientForm.vue) et la
 *    détection de l'autoliquidation aussi (VatCalculationService).
 *
 * 2. DE, EN, LB, PT — traductions reconstruites à 100 %. Elles plafonnaient à
 *    8 773–9 343 caractères contre 12 294 en français : deux sections entières
 *    (« Pour aller plus loin », CTA) et cinq liens manquaient.
 *
 * Liens internes : construits à partir du slug réel de chaque langue, jamais du
 * translation_key — les slugs diffèrent d'une langue à l'autre et le
 * référencement acquis en dépend.
 *
 * Liens AED (pfi.public.lu) : les URL françaises sont conservées dans toutes
 * les langues. Ce sont les pages officielles dont l'existence est vérifiée ;
 * parier sur une variante /de/ ou /en/ non testée ferait courir un risque de
 * lien mort. Même arbitrage que pour l'article Factur-X.
 */
return new class extends Migration
{
    private const KEY = 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg';

    /** Corrections ciblées du français : [avant, après] */
    private const FR_FIXES = [
        [
            "    <li><strong>État récapitulatif</strong> généré automatiquement en fin de période</li>\n    <li>Mentions traduites dans la langue du client (FR, DE, EN, PT)</li>",
            "    <li>Mentions traduites dans la langue du client (FR, DE, EN, LB, PT)</li>",
        ],
        [
            "<p>Vous évitez les erreurs et facturez vos clients étrangers en toute conformité.</p>",
            "<p>Vous évitez les erreurs et facturez vos clients étrangers en toute conformité.</p>\n\n<p><strong>À noter</strong> : faktur.lu ne dépose pas l'état récapitulatif à votre place. Vos factures en autoliquidation portent la mention et la TVA à 0, prêtes à être reportées, mais la transmission à l'AED reste à votre charge.</p>",
        ],
        [
            "faktur.lu valide VIES en temps réel, applique l'autoliquidation automatiquement, génère vos états récapitulatifs. Plus de stress pour vos clients européens.",
            "faktur.lu valide VIES en temps réel, applique l'autoliquidation automatiquement et traduit les mentions dans la langue de votre client. Plus de stress pour vos clients européens.",
        ],
    ];

    public function up(): void
    {
        $this->fixFrench();

        foreach ($this->translations() as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Les traductions d'origine étaient incomplètes : rien à restaurer
        // utilement. Seul le français est réversible.
        $post = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'fr')
            ->first(['id', 'content']);

        if (! $post) {
            return;
        }

        $content = $post->content;

        foreach (self::FR_FIXES as [$before, $after]) {
            $content = str_replace($after, $before, $content);
        }

        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => $content,
            'updated_at' => now(),
        ]);
    }

    private function fixFrench(): void
    {
        $post = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'fr')
            ->first(['id', 'content']);

        if (! $post) {
            return;
        }

        $content = $post->content;

        foreach (self::FR_FIXES as [$before, $after]) {
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
<p class="lead">Sie sind Luxemburger Freiberufler und stellen einem B2B-Kunden in Frankreich, Deutschland oder Belgien eine Rechnung? Dann greift das <strong>Reverse-Charge-Verfahren</strong>: Die MwSt schuldet Ihr Kunde in seinem Land, nicht Sie in Luxemburg. Rechtsgrundlage ist <strong>Artikel 17 LIVA</strong> (Umsetzung von Artikel 44 der Richtlinie 2006/112/EG); die Pflichtangabe auf der Rechnung verweist dagegen auf <strong>Artikel 196 der Richtlinie 2006/112/EG</strong>. Hier die Regeln im Klartext.</p>

<h2>Die Rechtsgrundlage in Kuerze</h2>

<p>Zwei Artikel, die man sauber auseinanderhalten muss:</p>

<ul>
    <li><strong>Artikel 17 LIVA</strong> (luxemburgisches Gesetz vom 12. Februar 1979): bestimmt den <strong>Ort der Besteuerung</strong> von Dienstleistungen. Bei einer B2B-Dienstleistung innerhalb der EU ist das der Sitz des Leistungsempfaengers (Umsetzung von Artikel 44 der Richtlinie 2006/112/EG).</li>
    <li><strong>Artikel 196 der Richtlinie 2006/112/EG</strong>: bestimmt den <strong>Leistungsempfaenger</strong> als Steuerschuldner. Das ist die Grundlage des Reverse-Charge auf Kundenseite.</li>
</ul>

<p>Konkret gilt, wenn ein Luxemburger Freiberufler eine Dienstleistung an ein Unternehmen in einem anderen EU-Land fakturiert:</p>

<ul>
    <li>Die <strong>luxemburgische MwSt faellt nicht an</strong> (Besteuerungsort ist beim Leistungsempfaenger)</li>
    <li>Der auslaendische Kunde <strong>versteuert die MwSt im Reverse-Charge-Verfahren</strong> in seinem Land zum dort geltenden Satz</li>
    <li>Die Rechnung traegt ausdruecklich den Hinweis <strong>„Reverse Charge"</strong> (kodifiziert durch Artikel 226 Nr. 11a der Richtlinie 2006/112/EG)</li>
</ul>

<h2>Wann gilt das Reverse-Charge-Verfahren?</h2>

<p>Drei Bedingungen muessen kumulativ erfuellt sein:</p>

<ol>
    <li><strong>Dienstleistung</strong> (fuer Warenlieferungen gilt eine andere Regelung – siehe unten)</li>
    <li><strong>Gewerblicher Kunde (B2B)</strong> in einem anderen EU-Mitgliedstaat</li>
    <li><strong>Gueltige innergemeinschaftliche MwSt-Nummer</strong> des Kunden (Validierung ueber VIES ist Pflicht)</li>
</ol>

<p>Fehlt eine dieser Bedingungen, aendert sich die Regel:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">MwSt-Regel</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B-Kunde in der EU mit gueltiger MwSt-Nummer</td><td class="border border-gray-300 px-4 py-2"><strong>Reverse Charge (Art. 17 LIVA + Art. 196 Richtlinie)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C-Kunde (Privatperson) in der EU</td><td class="border border-gray-300 px-4 py-2">Luxemburgische MwSt 17 % (oder OSS bei Ueberschreiten der Schwelle von 10.000 EUR)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B-Kunde in der EU ohne validierte MwSt-Nummer</td><td class="border border-gray-300 px-4 py-2">Luxemburgische MwSt 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Kunde ausserhalb der EU (B2B oder B2C)</td><td class="border border-gray-300 px-4 py-2">Nicht im Anwendungsbereich der luxemburgischen MwSt (angepasster Hinweis)</td></tr>
    </tbody>
</table>

<h2>Die VIES-Validierung, der kritische Schritt</h2>

<p>VIES (<em>VAT Information Exchange System</em>) ist der europaeische Dienst, mit dem sich die Gueltigkeit einer innergemeinschaftlichen MwSt-Nummer in Echtzeit pruefen laesst.</p>

<p>Bevor Sie eine Rechnung im Reverse-Charge-Verfahren ausstellen, <strong>muessen Sie zwingend</strong> die MwSt-Nummer Ihres Kunden ueber VIES validieren. Ist die Nummer zum Rechnungsdatum ungueltig, geht die AED davon aus, dass die Leistung in Luxemburg steuerbar ist, und fordert die nicht erhobene MwSt nach.</p>

<p>Der offizielle VIES-Dienst ist erreichbar unter <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. In der Praxis sollte Ihre Rechnungssoftware das bei jeder Rechnung automatisch tun und den Validierungsnachweis fuer eine AED-Pruefung aufbewahren.</p>

<h2>Pflichtangaben auf der Rechnung</h2>

<p>Eine Rechnung im Reverse-Charge-Verfahren muss zusaetzlich zu den ueblichen Angaben (Art. 63 LIVA) enthalten:</p>

<ul>
    <li>Ihre <strong>luxemburgische MwSt-Nummer</strong> (LU + 8 Ziffern)</li>
    <li>Die <strong>MwSt-Nummer des Kunden</strong> (Laenderpraefix + Ziffern)</li>
    <li>Den genauen Hinweis <strong>„Reverse Charge"</strong> – gegebenenfalls ergaenzt um <em>„Artikel 196 der Richtlinie 2006/112/EG – die MwSt schuldet der Leistungsempfaenger"</em></li>
    <li><strong>Keine MwSt</strong> auf den Gesamtbetrag (MwSt = 0 EUR)</li>
    <li>Der Nettobetrag, der zugleich der Bruttobetrag ist</li>
</ul>

<p><strong>Wichtig</strong>: Der Hinweis „Reverse Charge" ist durch Artikel 226 Nr. 11a der Richtlinie 2006/112/EG kodifiziert. Eine zu vage Formulierung wie „MwSt nicht anwendbar" ohne naehere Angabe kann von der AED umqualifiziert werden. Verwenden Sie den vollstaendigen Hinweis.</p>

<h2>Konkretes Beispiel</h2>

<p>Marie ist freiberufliche UX-Designerin in Luxemburg-Stadt. Sie stellt <strong>2.500 EUR netto</strong> an <strong>Acme SA</strong> in Rechnung, eine Pariser Agentur (MwSt-Nr. FR12345678901, ueber VIES validiert).</p>

<p>Ihre Rechnung enthaelt:</p>

<ul>
    <li><strong>Aussteller</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Empfaenger</strong>: Acme SA, 10 rue de Rivoli, 75001 Paris, FR12345678901</li>
    <li><strong>Leistung</strong>: UX-Design Website – 2.500,00 EUR</li>
    <li><strong>MwSt (0 %)</strong>: 0,00 EUR</li>
    <li><strong>Gesamtbetrag</strong>: 2.500,00 EUR</li>
    <li><strong>Hinweis</strong>: <em>„Reverse Charge – Artikel 196 der Richtlinie 2006/112/EG – die MwSt schuldet der Leistungsempfaenger."</em></li>
</ul>

<p>Acme SA erklaert die franzoesische MwSt (20 %) in der eigenen Voranmeldung, zugleich als geschuldete und als abziehbare MwSt (fuer sie ein neutraler Vorgang).</p>

<h2>Die drei teuren Fehler</h2>

<ol>
    <li><strong>Die VIES-Validierung vergessen</strong>: Erweist sich die MwSt-Nummer als ungueltig oder ausgesetzt, muessen Sie die luxemburgische MwSt (17 %) erheben. Ohne zahlenden Kunden geht das zu Ihren Lasten.</li>
    <li><strong>Fehlender oder vager Reverse-Charge-Hinweis</strong>: Die AED qualifiziert den Vorgang als in Luxemburg steuerbar um.</li>
    <li><strong>Falsche Behandlung in den Erklaerungen</strong>: Der Vorgang muss in der luxemburgischen MwSt-Erklaerung (Rubrik innergemeinschaftliche Dienstleistungen) <strong>und</strong> in der Zusammenfassenden Meldung erscheinen – siehe naechster Abschnitt.</li>
</ol>

<h2>Zusammenfassende Meldung (VIES-Meldung)</h2>

<p>Alle Ihre innergemeinschaftlichen B2B-Dienstleistungen muessen in einer <strong>Zusammenfassenden Meldung</strong> an die AED uebermittelt werden. Bei <strong>Dienstleistungen</strong> ist die Periodizitaet frei waehlbar (monatlich oder vierteljaehrlich); es gibt keine Pflichtschwelle fuer den Wechsel auf monatlich.</p>

<p><strong>Achtung</strong>: Bei innergemeinschaftlichen <strong>Warenlieferungen</strong> wechselt die Periodizitaet auf monatlich, sobald Ihre Lieferungen <strong>50.000 EUR netto innerhalb eines Quartals</strong> uebersteigen (und nicht „ueber 12 Monate", wie man mitunter liest). Diese Schwelle gilt nicht fuer reine Dienstleistungen.</p>

<h2>Wie faktur.lu Sie schuetzt</h2>

<p>faktur.lu erkennt die Voraussetzungen des Reverse-Charge automatisch und wendet Artikel 17 LIVA an:</p>

<ul>
    <li><strong>VIES-Validierung in Echtzeit</strong>, sobald Sie einen B2B-Kunden aus der EU erfassen</li>
    <li>Hinweis <strong>„Reverse Charge – Artikel 196 der Richtlinie 2006/112/EG"</strong> automatisch auf der Rechnung</li>
    <li><strong>MwSt zwingend auf 0</strong> fuer die betroffenen Leistungen, Berechnungen geprueft</li>
    <li>Hinweise in der Sprache des Kunden (FR, DE, EN, LB, PT)</li>
</ul>

<p>So vermeiden Sie Fehler und fakturieren Ihre auslaendischen Kunden rechtssicher.</p>

<p><strong>Zu beachten</strong>: faktur.lu reicht die Zusammenfassende Meldung nicht fuer Sie ein. Ihre Reverse-Charge-Rechnungen tragen den Hinweis und die MwSt 0 und sind damit uebernahmebereit, die Uebermittlung an die AED bleibt aber Ihre Aufgabe.</p>

<h2>FAQ – Reverse Charge B2B innerhalb der EU</h2>

<h3>Und wenn mein Kunde keine MwSt-Nummer hat?</h3>
<p>Ohne ueber VIES validierte MwSt-Nummer greift das Reverse-Charge-Verfahren nicht. Sie muessen die luxemburgische MwSt berechnen (17 % Normalsatz). Dasselbe gilt fuer Privatpersonen (B2C).</p>

<h3>Was gilt fuer Warenlieferungen (statt Dienstleistungen)?</h3>
<p>Fuer innergemeinschaftliche B2B-Warenverkaeufe gilt <strong>Artikel 43 Absatz 1 Buchstabe d) LIVA</strong> (steuerbefreite innergemeinschaftliche Lieferungen). Der uebliche Rechnungshinweis lautet: <em>„MwSt-Befreiung – Artikel 138 der Richtlinie 2006/112/EG"</em>. Auch die Regeln zur Zusammenfassenden Meldung unterscheiden sich (Wechsel auf monatlich ab 50.000 EUR netto pro Quartal).</p>

<h3>Muss der Vorgang der AED gemeldet werden?</h3>
<p>Ja. Der Vorgang erscheint in:</p>
<ul>
    <li>Ihrer luxemburgischen <strong>MwSt-Erklaerung</strong> (Feld innergemeinschaftliche Dienstleistungen)</li>
    <li>Der <strong>Zusammenfassenden Meldung</strong> – bei reinen Dienstleistungen frei monatlich oder vierteljaehrlich</li>
</ul>

<h3>Kann der Kunde das Reverse-Charge-Verfahren ablehnen?</h3>
<p>Nein, es ist eine zwingende europaeische Regel (Richtlinie 2006/112/EG). Sie koennen allerdings die Rechnungstellung ablehnen, wenn der Kunde keine validierte MwSt-Nummer hat (oder mit luxemburgischer MwSt fakturieren).</p>

<h3>Was passiert bei einer AED-Pruefung?</h3>
<p>Die AED prueft den <strong>VIES-Validierungsnachweis</strong> zum Rechnungsdatum (ein Screenshot oder ein automatisches Protokoll genuegt). Ohne diesen Nachweis wird der Vorgang als steuerbar umqualifiziert und Sie schulden die MwSt zuzueglich Geldbussen (250 bis 10.000 EUR je Verstoss, Art. 77 LIVA) und Verzugszinsen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jaehrlich zu pruefen</p>
    <p>Artikelverweise, Pflichtangaben und MwSt-Verfahren koennen sich aendern. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich jedoch an Ihren Treuhaender oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geaendertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 17, 43, 63, 77</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinie 2006/112/EG – Artikel 44, 196, 226</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestimmung des Leistungsortes</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/etat-recapitulatif.html" target="_blank" rel="noopener">AED – Zusammenfassende Meldung</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES-Validierung (Europaeische Kommission)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026. Zuvor unter dem Titel „Artikel 21 LIVA" veroeffentlicht – die zutreffende Referenz fuer den Besteuerungsort ist Artikel 17 LIVA.</em></p>

<h2>Weiterfuehrende Artikel</h2>
<ul>
    <li><a href="/de/blog/mwst-luxemburg-saetze-berechnung-pflichten">MwSt in Luxemburg: Saetze, Berechnung und Pflichten</a></li>
    <li><a href="/de/blog/pflichtangaben-rechnung-luxemburg">Pflichtangaben auf einer Rechnung in Luxemburg</a></li>
    <li><a href="/de/blog/freiberufler-luxemburg-konform-fakturieren">Freiberufler in Luxemburg: rechtssicher fakturieren</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Reverse Charge ohne Fehlerrisiko</h3>
    <p class="text-primary-800 mb-4">faktur.lu validiert VIES in Echtzeit, wendet das Reverse-Charge-Verfahren automatisch an und uebersetzt die Pflichtangaben in die Sprache Ihres Kunden. Kein Stress mehr mit europaeischen Kunden.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos starten</a>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead">You are a Luxembourg freelancer invoicing a B2B client in France, Germany or Belgium? The <strong>reverse charge</strong> rule applies: the VAT is owed by your client in their own country, not by you in Luxembourg. The legal basis is <strong>article 17 LIVA</strong> (which transposes article 44 of Directive 2006/112/EC), while the mandatory mention on the invoice refers to <strong>article 196 of Directive 2006/112/EC</strong>. Here are the rules, clearly.</p>

<h2>The legal basis in brief</h2>

<p>Two articles that must be kept apart:</p>

<ul>
    <li><strong>Article 17 LIVA</strong> (Luxembourg law of 12 February 1979): defines the <strong>place of taxation</strong> of services. For an intra-EU B2B service, that place is where the customer is established (transposing article 44 of Directive 2006/112/EC).</li>
    <li><strong>Article 196 of Directive 2006/112/EC</strong>: designates the <strong>customer</strong> as liable for the VAT on these transactions. That is the basis of the reverse charge on the client side.</li>
</ul>

<p>In practice, when a Luxembourg freelancer invoices a service to a business established in another EU country:</p>

<ul>
    <li><strong>No Luxembourg VAT is due</strong> (the place of taxation is at the customer's)</li>
    <li>The foreign client <strong>self-assesses the VAT</strong> in their own country at the applicable local rate</li>
    <li>The invoice explicitly carries the mention <strong>"Reverse charge"</strong> (codified by article 226(11a) of Directive 2006/112/EC)</li>
</ul>

<h2>When does the reverse charge apply?</h2>

<p>Three cumulative conditions must be met:</p>

<ol>
    <li><strong>A supply of services</strong> (supplies of goods follow a different regime – see below)</li>
    <li><strong>A business client (B2B)</strong> in another EU Member State</li>
    <li><strong>A valid intra-EU VAT number</strong> for the client (validation via VIES is mandatory)</li>
</ol>

<p>If any one of these conditions is missing, the rule changes:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">VAT rule</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Intra-EU B2B client with a valid VAT number</td><td class="border border-gray-300 px-4 py-2"><strong>Reverse charge (art. 17 LIVA + art. 196 of the Directive)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Intra-EU B2C client (private individual)</td><td class="border border-gray-300 px-4 py-2">Luxembourg VAT 17 % (or OSS once the EUR 10,000 threshold is exceeded)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Intra-EU B2B client without a validated VAT number</td><td class="border border-gray-300 px-4 py-2">Luxembourg VAT 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Non-EU client (B2B or B2C)</td><td class="border border-gray-300 px-4 py-2">Outside the scope of Luxembourg VAT (adapted mention)</td></tr>
    </tbody>
</table>

<h2>VIES validation, the critical step</h2>

<p>VIES (<em>VAT Information Exchange System</em>) is the European service that lets you check the validity of an intra-EU VAT number in real time.</p>

<p>Before issuing a reverse-charge invoice, you <strong>must</strong> validate your client's VAT number through VIES. If the number is not valid on the invoice date, the AED will treat the supply as taxable in Luxembourg and reassess the VAT you did not collect.</p>

<p>The official VIES service is available at <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. In practice, your invoicing software should do this automatically on every invoice and keep the proof of validation for an AED audit.</p>

<h2>Mandatory invoice mentions</h2>

<p>A reverse-charge invoice must carry, in addition to the usual mentions (art. 63 LIVA):</p>

<ul>
    <li>Your <strong>Luxembourg VAT number</strong> (LU + 8 digits)</li>
    <li>The <strong>client's VAT number</strong> (country prefix + digits)</li>
    <li>The exact mention <strong>"Reverse charge"</strong> – optionally followed by <em>"Article 196 of Directive 2006/112/EC – VAT due by the recipient"</em></li>
    <li><strong>No VAT added</strong> to the total (VAT = EUR 0)</li>
    <li>The net total, which is also the gross total</li>
</ul>

<p><strong>Important</strong>: the "Reverse charge" mention is codified by article 226(11a) of Directive 2006/112/EC. A vague wording such as "VAT not applicable", with no further detail, can be requalified by the AED. Use the full mention.</p>

<h2>A concrete example</h2>

<p>Marie is a freelance UX designer in Luxembourg City. She invoices <strong>EUR 2,500 excl. VAT</strong> to <strong>Acme SA</strong>, a Paris agency (VAT no. FR12345678901, validated via VIES).</p>

<p>Her invoice shows:</p>

<ul>
    <li><strong>Issuer</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Recipient</strong>: Acme SA, 10 rue de Rivoli, 75001 Paris, FR12345678901</li>
    <li><strong>Service</strong>: Website UX design – EUR 2,500.00</li>
    <li><strong>VAT (0 %)</strong>: EUR 0.00</li>
    <li><strong>Total</strong>: EUR 2,500.00</li>
    <li><strong>Mention</strong>: <em>"Reverse charge – Article 196 of Directive 2006/112/EC – VAT due by the recipient."</em></li>
</ul>

<p>Acme SA reports the French VAT (20 %) in its own VAT return, both as output VAT and as deductible input VAT (a neutral transaction for them).</p>

<h2>The three costly mistakes</h2>

<ol>
    <li><strong>Skipping VIES validation</strong>: if the VAT number turns out to be invalid or suspended, you must collect Luxembourg VAT (17 %). With no client to pay it, it comes out of your own pocket.</li>
    <li><strong>A missing or vague "Reverse charge" mention</strong>: the AED requalifies the transaction as taxable in Luxembourg.</li>
    <li><strong>Incorrect reporting</strong>: the transaction must appear both in the Luxembourg VAT return (intra-EU services box) and in the <strong>EC Sales List</strong> – see the next section.</li>
</ol>

<h2>EC Sales List (recapitulative statement)</h2>

<p>All your intra-EU B2B services must be reported in an <strong>EC Sales List</strong> filed with the AED. For <strong>services</strong>, the frequency is free (monthly or quarterly); there is no mandatory threshold forcing a switch to monthly.</p>

<p><strong>Careful</strong>: for intra-EU <strong>supplies of goods</strong>, the frequency switches to monthly as soon as your supplies exceed <strong>EUR 50,000 excl. VAT within a quarter</strong> (not "over 12 months", as is sometimes written). That threshold does not apply to pure services.</p>

<h2>How faktur.lu protects you</h2>

<p>faktur.lu automatically detects the reverse-charge conditions and applies article 17 LIVA:</p>

<ul>
    <li><strong>Real-time VIES validation</strong> as soon as you enter an intra-EU B2B client</li>
    <li>The mention <strong>"Reverse charge – Article 196 of Directive 2006/112/EC"</strong> added automatically to the invoice</li>
    <li><strong>VAT forced to 0</strong> on the services concerned, with calculations verified</li>
    <li>Mentions translated into the client's language (FR, DE, EN, LB, PT)</li>
</ul>

<p>You avoid mistakes and invoice your foreign clients in full compliance.</p>

<p><strong>Note</strong>: faktur.lu does not file the EC Sales List on your behalf. Your reverse-charge invoices carry the mention and VAT at 0, ready to be carried over, but submitting them to the AED remains your responsibility.</p>

<h2>FAQ – Intra-EU B2B reverse charge</h2>

<h3>What if my client has no VAT number?</h3>
<p>Without a VAT number validated through VIES, the reverse charge does not apply. You must charge Luxembourg VAT (17 % standard rate). The same goes for private individuals (B2C).</p>

<h3>What about supplies of goods (rather than services)?</h3>
<p>For intra-EU B2B sales of goods, <strong>article 43(1)(d) LIVA</strong> applies (exempt intra-Community supplies). The canonical invoice mention is: <em>"VAT exemption – Article 138 of Directive 2006/112/EC"</em>. The EC Sales List rules also differ (switch to monthly at EUR 50,000 excl. VAT per quarter).</p>

<h3>Must the transaction be reported to the AED?</h3>
<p>Yes. The transaction appears in:</p>
<ul>
    <li>Your Luxembourg <strong>VAT return</strong> (intra-EU supply of services box)</li>
    <li>The <strong>EC Sales List</strong> – freely monthly or quarterly for pure services</li>
</ul>

<h3>Can the client refuse the reverse charge?</h3>
<p>No, it is a mandatory European rule (Directive 2006/112/EC). You may, however, decline to invoice if the client has no validated VAT number (or invoice with standard Luxembourg VAT).</p>

<h3>What happens during an AED audit?</h3>
<p>The AED checks the <strong>proof of VIES validation</strong> as at the invoice date (a screenshot or an automatic log will do). Without that proof, the transaction is requalified as taxable and you owe the VAT plus fines (EUR 250 to EUR 10,000 per breach, art. 77 LIVA) plus late-payment interest.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Article references, mandatory mentions and VAT procedures can change. This page is updated regularly, but for your own situation, consult your fiduciaire or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) – articles 17, 43, 63, 77</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/EC – articles 44, 196, 226</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Determining the place of supply</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/etat-recapitulatif.html" target="_blank" rel="noopener">AED – Intra-Community recapitulative statement</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES validation (European Commission)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026. Previously published under the title "Article 21 LIVA" – the correct reference for the place of taxation is article 17 LIVA.</em></p>

<h2>Further reading</h2>
<ul>
    <li><a href="/en/blog/vat-luxembourg-rates-calculation-obligations">VAT in Luxembourg: rates, calculation and obligations</a></li>
    <li><a href="/en/blog/mandatory-information-invoice-luxembourg">Mandatory information on a Luxembourg invoice</a></li>
    <li><a href="/en/blog/freelancer-luxembourg-invoice-compliance">Freelancer in Luxembourg: how to invoice in full compliance</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Reverse charge, without the risk of error</h3>
    <p class="text-primary-800 mb-4">faktur.lu validates VIES in real time, applies the reverse charge automatically and translates the mentions into your client's language. No more stress with your European clients.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Start for free</a>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">Sidd Dir Lëtzebuerger Freelancer a fakturéiert Dir engem B2B-Client a Frankräich, an Däitschland oder a Belsch? Da gëllt d'<strong>Autoliquidatioun</strong>: d'TVA gëtt vum Client a sengem Land geschuld, net vun Iech zu Lëtzebuerg. D'Rechtsgrondlag ass den <strong>Artikel 17 LIVA</strong> (Ëmsetzung vum Artikel 44 vun der Richtlinn 2006/112/EG), an d'Pflichtmentioun op der Rechnung verweist op den <strong>Artikel 196 vun der Richtlinn 2006/112/EG</strong>. Hei sinn d'Reegelen kloer.</p>

<h2>D'Rechtsgrondlag am Kuerzen</h2>

<p>Zwee Artikelen, déi ee gutt auserneenhale muss:</p>

<ul>
    <li><strong>Artikel 17 LIVA</strong> (Lëtzebuerger Gesetz vum 12. Februar 1979): definéiert de <strong>Besteierungsuert</strong> vu Servicer. Bei engem B2B-Service bannent der EU ass dat do, wou de Client etabléiert ass (Ëmsetzung vum Artikel 44 vun der Richtlinn 2006/112/EG).</li>
    <li><strong>Artikel 196 vun der Richtlinn 2006/112/EG</strong>: bestëmmt de <strong>Client</strong> als Schëllner vun der TVA bei dësen Operatiounen. Dat ass d'Basis vun der Autoliquidatioun op der Client-Säit.</li>
</ul>

<p>Konkret gëllt, wann e Lëtzebuerger Freelancer e Service un en Entreprise an engem anere EU-Land fakturéiert:</p>

<ul>
    <li>D'<strong>Lëtzebuerger TVA ass net geschuld</strong> (de Besteierungsuert ass beim Client)</li>
    <li>Den auslännesche Client <strong>autoliquidéiert d'TVA</strong> a sengem eegene Land zum lokal gëltege Saz</li>
    <li>D'Rechnung dréit ausdrécklech d'Mentioun <strong>„Autoliquidatioun"</strong> (kodifizéiert duerch den Artikel 226 §11bis vun der Richtlinn 2006/112/EG)</li>
</ul>

<h2>Wéini gëllt d'Autoliquidatioun?</h2>

<p>Dräi Bedingunge musse kumulativ erfëllt sinn:</p>

<ol>
    <li><strong>E Service</strong> (Wuerelieferunge falen ënner en anert Regime – kuckt méi ënnen)</li>
    <li><strong>E professionnelle Client (B2B)</strong> an engem anere Memberstaat vun der EU</li>
    <li><strong>Eng gülteg intracommunautär TVA-Nummer</strong> vum Client (Validatioun iwwer VIES ass obligatoresch)</li>
</ol>

<p>Feelt eng vun dëse Bedingungen, ännert d'Reegel:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA-Reegel</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B-Client bannent der EU mat gülteger TVA-Nummer</td><td class="border border-gray-300 px-4 py-2"><strong>Autoliquidatioun (Art. 17 LIVA + Art. 196 Richtlinn)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C-Client (Privatpersoun) bannent der EU</td><td class="border border-gray-300 px-4 py-2">Lëtzebuerger TVA 17 % (oder OSS wann d'Schwell vun 10 000 € iwwerschratt ass)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B-Client bannent der EU ouni validéiert TVA-Nummer</td><td class="border border-gray-300 px-4 py-2">Lëtzebuerger TVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client ausserhalb vun der EU (B2B oder B2C)</td><td class="border border-gray-300 px-4 py-2">Ausserhalb vum Beräich vun der Lëtzebuerger TVA (ugepasste Mentioun)</td></tr>
    </tbody>
</table>

<h2>D'VIES-Validatioun, de kritesche Schrëtt</h2>

<p>VIES (<em>VAT Information Exchange System</em>) ass den europäesche Service, mat deem een d'Gültegkeet vun enger intracommunautärer TVA-Nummer an Echtzäit iwwerpréiwe kann.</p>

<p>Ier Dir eng Rechnung mat Autoliquidatioun erausgitt, <strong>musst Dir zwéngend</strong> d'TVA-Nummer vun Ärem Client iwwer VIES validéieren. Ass d'Nummer um Datum vun der Rechnung net gülteg, geet d'AED dovun aus, datt de Service zu Lëtzebuerg steierbar ass, a fuerdert déi net erhuewen TVA no.</p>

<p>Den offizielle VIES-Service ass ënner <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a> accessibel. An der Praxis soll Är Fakturatiounssoftware dat bei all Rechnung automatesch maachen an de Beweis vun der Validatioun fir eng AED-Kontroll opbewaren.</p>

<h2>Pflichtmentiounen op der Rechnung</h2>

<p>Eng Rechnung mat Autoliquidatioun muss zousätzlech zu de gewéinleche Mentiounen (Art. 63 LIVA) enthalen:</p>

<ul>
    <li>Är <strong>Lëtzebuerger TVA-Nummer</strong> (LU + 8 Zifferen)</li>
    <li>D'<strong>TVA-Nummer vum Client</strong> (Landespräfix + Zifferen)</li>
    <li>Déi genee Mentioun <strong>„Autoliquidatioun"</strong> – eventuell gefollegt vun der Präzisioun <em>„Artikel 196 vun der Richtlinn 2006/112/EG – TVA geschuld vum Client"</em></li>
    <li><strong>Keng TVA</strong> um Total (TVA = 0 €)</li>
    <li>Den Total HT, deen och den Total TTC ass</li>
</ul>

<p><strong>Wichteg</strong>: D'Mentioun „Autoliquidatioun" ass duerch den Artikel 226 §11bis vun der Richtlinn 2006/112/EG kodifizéiert. Eng ze vague Formuléierung wéi „TVA net applicabel" ouni Präzisioun kann vun der AED ëmqualifizéiert ginn. Huelt léiwer déi komplett Mentioun.</p>

<h2>Konkret Beispill</h2>

<p>D'Marie ass freiberuflech UX-Designerin zu Lëtzebuerg-Stad. Si fakturéiert <strong>2 500 € HT</strong> un <strong>Acme SA</strong>, eng Pariser Agence (TVA-Nr. FR12345678901, iwwer VIES validéiert).</p>

<p>Hir Rechnung enthält:</p>

<ul>
    <li><strong>Emetteur</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Destinataire</strong>: Acme SA, 10 rue de Rivoli, 75001 Paris, FR12345678901</li>
    <li><strong>Service</strong>: UX-Design Websäit – 2 500,00 €</li>
    <li><strong>TVA (0 %)</strong>: 0,00 €</li>
    <li><strong>Total TTC</strong>: 2 500,00 €</li>
    <li><strong>Mentioun</strong>: <em>„Autoliquidatioun – Artikel 196 vun der Richtlinn 2006/112/EG – TVA geschuld vum Client."</em></li>
</ul>

<p>D'Acme SA deklaréiert déi franséisch TVA (20 %) a senger eegener TVA-Deklaratioun, gläichzäiteg als agesammelt an als ofzugsfäeg TVA (fir si eng neutral Operatioun).</p>

<h2>Déi 3 Feeler, déi deier kommen</h2>

<ol>
    <li><strong>D'VIES-Validatioun vergiessen</strong>: Weist sech d'TVA-Nummer als ongülteg oder suspendéiert, musst Dir déi Lëtzebuerger TVA (17 %) erhiewen. Ouni Client, deen se bezilt, geet dat aus Ärer eegener Täsch.</li>
    <li><strong>Feelend oder vague Mentioun „Autoliquidatioun"</strong>: D'AED qualifizéiert d'Operatioun als zu Lëtzebuerg steierbar ëm.</li>
    <li><strong>Falsch deklarativ Behandlung</strong>: D'Operatioun muss an der Lëtzebuerger TVA-Deklaratioun (Rubrik intracommunautär Servicer) <strong>an</strong> am récapitulative Relevé erschéngen – kuckt de nächsten Abschnitt.</li>
</ol>

<h2>Récapitulative Relevé (VIES-Deklaratioun)</h2>

<p>All Är B2B-Servicer bannent der EU mussen an engem <strong>récapitulative Relevé</strong> un d'AED iwwermëttelt ginn. Bei <strong>Servicer</strong> ass d'Periodizitéit fräi (méintlech oder trimestriell); et gëtt keng obligatoresch Schwell fir de Wiessel op méintlech.</p>

<p><strong>Opgepasst</strong>: Bei intracommunautäre <strong>Wuerelieferunge</strong> wiesselt d'Periodizitéit op méintlech, soubal Är Lieferungen <strong>50 000 € HT am Laf vun engem Trimester</strong> iwwerschreiden (an net „iwwer 12 Méint", wéi een dat heiansdo liest). Dës Schwell gëllt net fir reng Servicer.</p>

<h2>Wéi faktur.lu Iech schützt</h2>

<p>faktur.lu erkennt automatesch d'Bedingunge vun der Autoliquidatioun a wennt den Artikel 17 LIVA un:</p>

<ul>
    <li><strong>VIES-Validatioun an Echtzäit</strong>, soubal Dir e B2B-Client aus der EU aginn</li>
    <li>Mentioun <strong>„Autoliquidatioun – Artikel 196 vun der Richtlinn 2006/112/EG"</strong> automatesch op der Rechnung</li>
    <li><strong>TVA zwéngend op 0</strong> fir déi betraffe Servicer, Berechnunge kontrolléiert</li>
    <li>Mentiounen an d'Sprooch vum Client iwwersat (FR, DE, EN, LB, PT)</li>
</ul>

<p>Sou vermeit Dir Feeler a fakturéiert Är auslännesch Clienten a voller Konformitéit.</p>

<p><strong>Ze bemierken</strong>: faktur.lu deposéiert de récapitulative Relevé net fir Iech. Är Rechnunge mat Autoliquidatioun droen d'Mentioun an d'TVA op 0 a sinn domat prett fir iwwerholl ze ginn, mä d'Iwwermëttlung un d'AED bleift Är Aufgab.</p>

<h2>FAQ – Autoliquidatioun B2B bannent der EU</h2>

<h3>A wann mäi Client keng TVA-Nummer huet?</h3>
<p>Ouni iwwer VIES validéiert TVA-Nummer gëllt d'Autoliquidatioun net. Dir musst déi Lëtzebuerger TVA fakturéieren (17 % Standardsaz). Dat gëllt och fir Privatpersounen (B2C).</p>

<h3>Wéi ass et mat Wuerelieferungen (net Servicer)?</h3>
<p>Fir B2B-Wuerevekeef bannent der EU gëllt den <strong>Artikel 43 §1 d) LIVA</strong> (befreit intracommunautär Lieferungen). Déi kanonesch Mentioun op der Rechnung ass: <em>„TVA-Befreiung – Artikel 138 vun der Richtlinn 2006/112/EG"</em>. Och d'Reegele fir de récapitulative Relevé sinn anescht (Wiessel op méintlech bei 50 000 € HT pro Trimester).</p>

<h3>Muss d'Operatioun der AED gemellt ginn?</h3>
<p>Jo. D'Operatioun erschéngt an:</p>
<ul>
    <li>Ärer Lëtzebuerger <strong>TVA-Deklaratioun</strong> (Feld intracommunautär Servicer)</li>
    <li>Dem <strong>récapitulative Relevé</strong> – fräi méintlech oder trimestriell bei renge Servicer</li>
</ul>

<h3>Kann de Client d'Autoliquidatioun refuséieren?</h3>
<p>Nee, et ass eng obligatoresch europäesch Reegel (Richtlinn 2006/112/EG). Dogéint kënnt Dir refuséieren ze fakturéieren, wann de Client keng validéiert TVA-Nummer huet (oder mat Lëtzebuerger TVA fakturéieren).</p>

<h3>Wat geschitt bei enger AED-Kontroll?</h3>
<p>D'AED iwwerpréift de <strong>Beweis vun der VIES-Validatioun</strong> um Datum vun der Rechnung (e Screenshot oder en automatescht Log geet duer). Ouni dëse Beweis gëtt d'Operatioun als steierbar ëmqualifizéiert an Dir schëlt d'TVA plus Geldstrofen (250 € bis 10 000 € pro Infraktioun, Art. 77 LIVA) plus Verzuchszënsen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze kontrolléieren</p>
    <p>Artikelreferenzen, Mentiounen an TVA-Prozedure kënne sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Ären Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikelen 17, 43, 63, 77</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinn 2006/112/EG – Artikelen 44, 196, 226</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestëmmung vum Leeschtungsuert</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/etat-recapitulatif.html" target="_blank" rel="noopener">AED – Intracommunautäre récapitulative Relevé</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES-Validatioun (Europäesch Kommissioun)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert. Virdru ënner dem Titel „Artikel 21 LIVA" publizéiert – déi richteg Referenz fir de Besteierungsuert ass den Artikel 17 LIVA.</em></p>

<h2>Fir méi wäit ze goen</h2>
<ul>
    <li><a href="/lb/blog/tva-letzebuerg-tariffer-berechnung-obligatiounen">TVA zu Lëtzebuerg: Tariffer, Berechnung an Obligatiounen</a></li>
    <li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg">Pflichtmentiounen op enger Rechnung zu Lëtzebuerg</a></li>
    <li><a href="/lb/blog/freelancer-letzebuerg-konform-fakturieren">Freelancer zu Lëtzebuerg: konform fakturéieren</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Autoliquidatioun ouni Feelerrisiko</h3>
    <p class="text-primary-800 mb-4">faktur.lu validéiert VIES an Echtzäit, wennt d'Autoliquidatioun automatesch un an iwwersetzt d'Mentiounen an d'Sprooch vun Ärem Client. Kee Stress méi mat Ären europäesche Clienten.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis ufänken</a>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">É freelancer no Luxemburgo e vai faturar a um cliente B2B em França, na Alemanha ou na Bélgica? Aplica-se a regra da <strong>autoliquidação</strong>: o IVA é devido pelo seu cliente no país dele, não por si no Luxemburgo. A base legal é o <strong>artigo 17 LIVA</strong> (que transpõe o artigo 44.º da Diretiva 2006/112/CE), ao passo que a menção obrigatória na fatura remete para o <strong>artigo 196.º da Diretiva 2006/112/CE</strong>. Eis as regras, com clareza.</p>

<h2>A base legal em resumo</h2>

<p>Dois artigos que importa distinguir bem:</p>

<ul>
    <li><strong>Artigo 17 LIVA</strong> (lei luxemburguesa de 12 de fevereiro de 1979): define o <strong>lugar de tributação</strong> das prestações de serviços. Num serviço B2B intra-UE, esse lugar é o da sede do adquirente (transposição do artigo 44.º da Diretiva 2006/112/CE).</li>
    <li><strong>Artigo 196.º da Diretiva 2006/112/CE</strong>: designa o <strong>adquirente</strong> como devedor do IVA nestas operações. É a base da autoliquidação do lado do cliente.</li>
</ul>

<p>Na prática, quando um freelancer luxemburguês fatura uma prestação de serviços a uma empresa situada noutro país da UE:</p>

<ul>
    <li>O <strong>IVA luxemburguês não é devido</strong> (o lugar de tributação é o do adquirente)</li>
    <li>O cliente estrangeiro <strong>autoliquida o IVA</strong> no seu próprio país à taxa local aplicável</li>
    <li>A fatura menciona explicitamente <strong>«Autoliquidação»</strong> (menção codificada pelo artigo 226.º, n.º 11-A, da Diretiva 2006/112/CE)</li>
</ul>

<h2>Quando se aplica a autoliquidação?</h2>

<p>Três condições cumulativas têm de estar preenchidas:</p>

<ol>
    <li><strong>Prestação de serviços</strong> (as transmissões de bens seguem outro regime – ver adiante)</li>
    <li><strong>Cliente profissional (B2B)</strong> noutro Estado-Membro da União Europeia</li>
    <li><strong>Número de IVA intracomunitário válido</strong> do cliente (validação obrigatória através do VIES)</li>
</ol>

<p>Se faltar uma destas condições, a regra muda:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Regra de IVA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente B2B intra-UE com n.º de IVA válido</td><td class="border border-gray-300 px-4 py-2"><strong>Autoliquidação (art. 17 LIVA + art. 196.º da Diretiva)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente B2C (particular) intra-UE</td><td class="border border-gray-300 px-4 py-2">IVA luxemburguês 17 % (ou OSS se ultrapassado o limiar de 10 000 €)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente B2B intra-UE sem n.º de IVA validado</td><td class="border border-gray-300 px-4 py-2">IVA luxemburguês 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cliente fora da UE (B2B ou B2C)</td><td class="border border-gray-300 px-4 py-2">Fora do âmbito do IVA luxemburguês (menção adaptada)</td></tr>
    </tbody>
</table>

<h2>A validação VIES, etapa crítica</h2>

<p>O VIES (<em>VAT Information Exchange System</em>) é o serviço europeu que permite verificar em tempo real a validade de um número de IVA intracomunitário.</p>

<p>Antes de emitir uma fatura em autoliquidação, <strong>tem obrigatoriamente</strong> de validar o n.º de IVA do seu cliente através do VIES. Se o número não for válido à data da fatura, a AED considera que a prestação é tributável no Luxemburgo e liquida-lhe o IVA não cobrado.</p>

<p>O serviço VIES oficial está acessível em <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. Na prática, o seu software de faturação deve fazê-lo automaticamente em cada fatura e conservar a prova da validação para uma inspeção da AED.</p>

<h2>Menções obrigatórias na fatura</h2>

<p>Uma fatura em autoliquidação deve conter, além das menções habituais (art. 63 LIVA):</p>

<ul>
    <li>O seu <strong>número de IVA luxemburguês</strong> (LU + 8 dígitos)</li>
    <li>O <strong>número de IVA do cliente</strong> (prefixo do país + dígitos)</li>
    <li>A menção exata <strong>«Autoliquidação»</strong> – eventualmente seguida da precisão <em>«Artigo 196.º da Diretiva 2006/112/CE – IVA devido pelo adquirente»</em></li>
    <li><strong>Sem IVA acrescentado</strong> ao total (IVA = 0 €)</li>
    <li>O total sem IVA, que passa a ser também o total com IVA</li>
</ul>

<p><strong>Importante</strong>: a menção «Autoliquidação» está codificada no artigo 226.º, n.º 11-A, da Diretiva 2006/112/CE. Uma formulação demasiado vaga como «IVA não aplicável», sem mais precisões, pode ser requalificada pela AED. Prefira a menção completa.</p>

<h2>Exemplo concreto</h2>

<p>A Marie é <em>designer</em> UX freelancer na cidade do Luxemburgo. Fatura <strong>2 500 € sem IVA</strong> à <strong>Acme SA</strong>, uma agência parisiense (n.º de IVA FR12345678901, validado via VIES).</p>

<p>A fatura dela indica:</p>

<ul>
    <li><strong>Emitente</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Destinatário</strong>: Acme SA, 10 rue de Rivoli, 75001 Paris, FR12345678901</li>
    <li><strong>Prestação</strong>: Design UX de website – 2 500,00 €</li>
    <li><strong>IVA (0 %)</strong>: 0,00 €</li>
    <li><strong>Total</strong>: 2 500,00 €</li>
    <li><strong>Menção</strong>: <em>«Autoliquidação – Artigo 196.º da Diretiva 2006/112/CE – IVA devido pelo adquirente.»</em></li>
</ul>

<p>A Acme SA declara o IVA francês (20 %) na sua própria declaração de IVA, simultaneamente como IVA liquidado e como IVA dedutível (operação neutra para eles).</p>

<h2>Os 3 erros que saem caro</h2>

<ol>
    <li><strong>Esquecer a validação VIES</strong>: se o n.º de IVA se revelar inválido ou suspenso, tem de cobrar o IVA luxemburguês (17 %). Sem o cliente para o pagar, sai do seu bolso.</li>
    <li><strong>Menção «Autoliquidação» em falta ou vaga</strong>: a AED requalifica a operação como tributável no Luxemburgo.</li>
    <li><strong>Tratamento declarativo incorreto</strong>: a operação tem de constar da declaração de IVA luxemburguesa (rubrica prestações de serviços intracomunitárias) <strong>e</strong> do mapa recapitulativo – ver a secção seguinte.</li>
</ol>

<h2>Mapa recapitulativo (declaração recapitulativa VIES)</h2>

<p>Todas as suas prestações de serviços B2B intra-UE têm de ser declaradas num <strong>mapa recapitulativo</strong> a transmitir à AED. Para os <strong>serviços</strong>, a periodicidade é livre (mensal ou trimestral); não existe limiar obrigatório de passagem a mensal.</p>

<p><strong>Atenção</strong>: nas <strong>transmissões de bens</strong> intracomunitárias, a periodicidade passa a mensal assim que as suas transmissões excedam <strong>50 000 € sem IVA no decurso de um trimestre</strong> (e não «12 meses», como por vezes se lê). Este limiar não se aplica a serviços puros.</p>

<h2>Como o faktur.lu o protege</h2>

<p>O faktur.lu deteta automaticamente as condições de autoliquidação e aplica o artigo 17 LIVA:</p>

<ul>
    <li><strong>Validação VIES em tempo real</strong> assim que introduz um cliente B2B intra-UE</li>
    <li>Menção <strong>«Autoliquidação – Artigo 196.º da Diretiva 2006/112/CE»</strong> acrescentada automaticamente à fatura</li>
    <li><strong>IVA forçado a 0</strong> nas prestações em causa, com cálculos verificados</li>
    <li>Menções traduzidas na língua do cliente (FR, DE, EN, LB, PT)</li>
</ul>

<p>Evita erros e fatura os seus clientes estrangeiros em plena conformidade.</p>

<p><strong>A ter em conta</strong>: o faktur.lu não entrega o mapa recapitulativo por si. As suas faturas em autoliquidação levam a menção e o IVA a 0, prontas a ser transpostas, mas a transmissão à AED continua a ser da sua responsabilidade.</p>

<h2>FAQ – Autoliquidação B2B intra-UE</h2>

<h3>E se o meu cliente não tiver n.º de IVA?</h3>
<p>Sem n.º de IVA validado através do VIES, a autoliquidação não se aplica. Tem de faturar o IVA luxemburguês (17 %, taxa normal). O mesmo vale para os particulares (B2C).</p>

<h3>E quanto às transmissões de bens (em vez de serviços)?</h3>
<p>Nas vendas de bens B2B intra-UE aplica-se o <strong>artigo 43 §1 d) LIVA</strong> (transmissões intracomunitárias isentas). A menção canónica na fatura é: <em>«Isenção de IVA – Artigo 138.º da Diretiva 2006/112/CE»</em>. As regras do mapa recapitulativo também são diferentes (passagem a mensal aos 50 000 € sem IVA por trimestre).</p>

<h3>É preciso declarar a operação à AED?</h3>
<p>Sim. A operação consta de:</p>
<ul>
    <li>A sua <strong>declaração de IVA</strong> luxemburguesa (campo prestações de serviços intra-UE)</li>
    <li>O <strong>mapa recapitulativo</strong> – livremente mensal ou trimestral para serviços puros</li>
</ul>

<h3>O cliente pode recusar a autoliquidação?</h3>
<p>Não, é uma regra europeia obrigatória (Diretiva 2006/112/CE). Em contrapartida, pode recusar-se a faturar se o cliente não tiver n.º de IVA validado (ou faturar com IVA luxemburguês normal).</p>

<h3>O que acontece numa inspeção da AED?</h3>
<p>A AED verifica a <strong>prova de validação VIES</strong> à data da fatura (uma captura de ecrã ou um registo automático fazem fé). Sem essa prova, a operação é requalificada como tributável e fica a dever o IVA, acrescido de coimas (250 € a 10 000 € por infração, art. 77 LIVA) e juros de mora.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As referências de artigos, as menções e os procedimentos de IVA podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu <em>fiduciaire</em> ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) – artigos 17, 43, 63, 77</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Diretiva 2006/112/CE – artigos 44.º, 196.º, 226.º</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Determinação do lugar da prestação</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/etat-recapitulatif.html" target="_blank" rel="noopener">AED – Mapa recapitulativo intracomunitário</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">Validação VIES (Comissão Europeia)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026. Anteriormente publicado com o título «Artigo 21 LIVA» – a referência correta para o lugar de tributação é o artigo 17 LIVA.</em></p>

<h2>Para saber mais</h2>
<ul>
    <li><a href="/pt/blog/iva-no-luxemburgo-taxas-calculo-e-obrigacoes-para-as-empresas">IVA no Luxemburgo: taxas, cálculo e obrigações</a></li>
    <li><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa">Menções obrigatórias numa fatura no Luxemburgo</a></li>
    <li><a href="/pt/blog/freelancer-no-luxemburgo-como-faturar-em-total-conformidade">Freelancer no Luxemburgo: como faturar em total conformidade</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Autoliquidação, sem risco de erro</h3>
    <p class="text-primary-800 mb-4">O faktur.lu valida o VIES em tempo real, aplica a autoliquidação automaticamente e traduz as menções na língua do seu cliente. Menos stress com os seus clientes europeus.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar gratuitamente</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
