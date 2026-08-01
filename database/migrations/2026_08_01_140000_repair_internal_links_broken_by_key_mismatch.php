<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repare les liens internes de 31 articles, casses par ma propre
 * migration 2026_08_01_100000_fix_cross_locale_blog_link_slugs.
 *
 * Cette migration resolvait chaque lien par slug -> translation_key -> slug
 * publie dans la langue cible. Elle a tourne en production AVANT la reparation
 * de l'appariement (2026_08_01_130000), donc alors que 27 lignes portaient
 * encore le translation_key d'un autre article. La resolution a donc designe
 * les mauvaises cibles : les articles allemands, anglais et luxembourgeois
 * pointent vers les guides « creer une entreprise individuelle » la ou ils
 * devraient pointer vers les articles piliers.
 *
 * Rejouer la meme resolution ne corrigerait rien : les cibles erronees existent
 * et sont publiees dans la bonne langue, donc l'algorithme les considere
 * valides. Seul le contenu de reference porte l'intention editoriale.
 *
 * 27 de ces articles ne divergeaient QUE par leurs liens internes. Les quatre
 * autres portaient en production des valeurs perimees que les migrations
 * editoriales n'avaient jamais atteintes, le seeder portugais les ayant
 * ecrasees a chaque deploiement avant sa correction :
 *   - pt/criar-uma-empresa-individual-no-luxemburgo : seuil de franchise TVA
 *     a 35 000 EUR au lieu de 50 000 EUR (en vigueur depuis 2025) ;
 *   - pt/criar-uma-empresa-individual-em-franca : seuils micro-entreprise
 *     perimes (188 700 / 77 700 au lieu de 203 100 / 83 600) ;
 *   - pt/criar-uma-empresa-individual-na-belgica : caisse d'assurances
 *     sociales et duree de conservation ;
 *   - fr/factur-x-zugferd : « economie.gouv.fr » ecrit avec un accent, ce
 *     qu'un nom de domaine ne peut pas porter.
 *
 * DEUX ARTICLES SONT VOLONTAIREMENT EXCLUS. fr/tva-luxembourg-taux-calcul-
 * obligations et fr/guide-complet-facturation-luxembourg-2026 portent en
 * production des encarts « Bon a savoir » qui n'existent nulle part dans le
 * depot : ils ont ete ajoutes directement via l'administration. Les ecraser
 * detruirait un travail editorial. Le generateur ecarte desormais tout article
 * dont la version servie contient un bloc absent de la reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fixed = 0;

        foreach ($this->articles() as $article) {
            $fixed += DB::table('blog_posts')
                ->where('slug', $article['slug'])
                ->where('locale', $article['locale'])
                ->update([
                    'content' => $article['content'],
                    'updated_at' => now(),
                ]);
        }

        echo "  {$fixed} article(s) relie(s) correctement\n";
    }

    public function down(): void
    {
        // Restaurer des liens pointant vers le mauvais article n'aurait pas de sens.
    }

    /** @return array<int, array{slug: string, locale: string, content: string}> */
    private function articles(): array
    {
        return [
            [
                'slug' => 'aus-luxemburg-ins-ausland-fakturieren',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Sie sind in Luxemburg ansässig und fakturieren Kunden im Ausland? Die MwSt.-Regeln unterscheiden sich erheblich je nach geografischer Zone und Kundentyp. Hier ist ein klarer Leitfaden für jede Situation, mit den richtigen Vermerken und Rechtsgrundlagen (Stand 2026).</p>

<h2>Fall 1: Unternehmenskunde in der EU (innergemeinschaftliches B2B)</h2>

<p>Das ist der häufigste Fall für luxemburgische Freiberufler und KMU. Beispiel: Ein luxemburgischer Berater fakturiert einer deutschen Gesellschaft.</p>

<h3>Anzuwendende Regeln</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong> – Ort der Besteuerung beim Leistungsempfänger (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">Art. 17 LIVA, Umsetzung von Art. 44 der Richtlinie 2006/112/EG</a>)</li>
    <li>Der Kunde meldet die MwSt. in seinem Land (<strong>Umkehrung der Steuerschuldnerschaft / Reverse Charge</strong>) – Art. 196 der Richtlinie benennt ihn als Steuerschuldner</li>
    <li>Sie müssen die MwSt.-Nummer des Kunden über <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> prüfen</li>
    <li>Pflichtvermerk (Art. 226 §11bis der Richtlinie): <em>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Vermerk Artikel 196, nicht 44</p>
    <p>Viele Vorlagen machen den Fehler, „Artikel 44 der Richtlinie 2006/112/EG" zu nennen. Artikel 44 bestimmt den <strong>Ort der Besteuerung</strong>. Der anzubringende Pflichtvermerk verweist auf <strong>Artikel 196</strong> (der den Leistungsempfänger als Steuerschuldner benennt). Bevorzugen Sie den Vermerk zu Artikel 196.</p>
</div>

<h3>Erforderliche Unterlagen</h3>
<ul>
    <li>Ihre luxemburgische MwSt.-Nummer auf der Rechnung</li>
    <li>Die MwSt.-Nummer des Kunden (über VIES geprüft, Nachweis aufbewahren)</li>
    <li>Meldung in der <strong>Zusammenfassenden Meldung</strong> – deren Periodizität legt die AED nach Ihrer Situation fest</li>
</ul>

<h2>Fall 2: Privatkunde in der EU (B2C)</h2>

<p>Sie verkaufen an eine Privatperson in einem anderen EU-Land. Die Regeln hängen von der Art der Leistung ab:</p>

<h3>Klassische Dienstleistungen (Beratung, Design usw.)</h3>
<ul>
    <li>Sie berechnen standardmäßig die <strong>luxemburgische MwSt. (17 %)</strong></li>
    <li>Kein Reverse Charge gegenüber Privatpersonen</li>
    <li><strong>Achtung:</strong> Bestimmte B2C-Leistungen unterliegen Sonderregeln (Immobilien, Beförderung, Kultur- und Sportveranstaltungen vor Ort…) und werden dort besteuert, wo sie ausgeführt werden. Trifft das auf Sie zu, klären Sie es mit Ihrem Treuhänder.</li>
</ul>

<h3>Elektronische Dienstleistungen (SaaS, Online-Schulungen usw.)</h3>
<ul>
    <li>Sie berechnen die <strong>MwSt. des Kundenlandes</strong></li>
    <li>Über das Verfahren <strong>OSS (One-Stop Shop)</strong>: eine einzige Erklärung für alle EU-Länder</li>
    <li>Schwelle: <strong>10 000 €/Jahr</strong> an B2C-Umsätzen in der EU (Summe aus Fernverkäufen von Waren und TBE-Leistungen). Darunter können Sie die luxemburgische MwSt. anwenden.</li>
</ul>

<h2>Fall 3: Kunde außerhalb der EU (Ausfuhr)</h2>

<p>Sie fakturieren einem Kunden in der Schweiz, den Vereinigten Staaten, dem Vereinigten Königreich oder einem beliebigen anderen Drittland.</p>

<h3>Dienstleistungen</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong> – Leistungsort außerhalb der EU (Art. 17 LIVA)</li>
    <li>Empfohlener Vermerk: <em>„MwSt. nicht anwendbar – Leistungsort außerhalb der EU"</em></li>
    <li>Keine Zusammenfassende Meldung nötig (nur für innergemeinschaftlichen Verkehr)</li>
</ul>

<h3>Waren (Ausfuhr in Drittländer)</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt.</strong> (steuerfreie Ausfuhr, Art. 43 §1 a) LIVA)</li>
    <li>Sie müssen den <strong>Ausfuhrnachweis</strong> aufbewahren (Zolldokument)</li>
    <li>Vermerk: <em>„MwSt.-Befreiung – Artikel 146 der Richtlinie 2006/112/EG"</em></li>
</ul>

<h3>Der Sonderfall Nordirland</h3>

<p>Das Vereinigte Königreich pauschal als „Drittland" einzuordnen, stimmt für Dienstleistungen, ist aber <strong>falsch für Waren</strong>. Nach dem Protokoll zu Irland / Nordirland gilt das MwSt.-Recht der Union weiterhin für <strong>Waren</strong> nach und aus Nordirland.</p>

<ul>
    <li><strong>Waren nach Nordirland</strong>: innergemeinschaftliche Lieferung, keine Ausfuhr. Der Kunde hat eine MwSt.-Nummer mit dem Präfix <strong>„XI"</strong>, die über VIES zu prüfen ist, und der Umsatz erscheint in Ihrer Zusammenfassenden Meldung</li>
    <li><strong>Dienstleistungen nach Nordirland</strong>: Drittlandsregime, wie für das übrige Vereinigte Königreich</li>
    <li>Eine gültige MwSt.-Nummer mit dem richtigen Präfix ist eine <strong>materielle Voraussetzung</strong> der Befreiung: Mit einer „GB"-Nummer lässt sich der Umsatz nicht als innergemeinschaftliche Lieferung behandeln</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ein Fehler, der in beide Richtungen teuer wird</p>
    <p>Behandeln Sie einen Warenverkauf nach Nordirland als Ausfuhr, suchen Sie nach einem Zollnachweis, den es nie geben wird, und lassen die Zusammenfassende Meldung aus. Umgekehrt melden Sie bei einer als innergemeinschaftlich behandelten Dienstleistung einen Umsatz, der dort nicht hingehört. Maßgeblich ist die <strong>Art des Umsatzes</strong>, nicht das Land.</p>
</div>

<h2>Sonderfall: die Schweiz</h2>

<p>Die Schweiz gehört nicht zur EU. Viele luxemburgische Freiberufler fakturieren Schweizer Kunden. Die Regeln:</p>

<ul>
    <li><strong>B2B-Dienstleistungen</strong>: fakturieren Sie <strong>ohne MwSt.</strong>, der Schweizer Kunde meldet die Steuer über die <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">Bezugsteuer (ESTV)</a></li>
    <li><strong>B2C-Dienstleistungen</strong>: je nach Art der Leistung kann luxemburgische MwSt. anfallen (insbesondere elektronische Leistungen)</li>
    <li>Fakturieren Sie in <strong>EUR oder CHF</strong> nach Absprache mit dem Kunden</li>
    <li>Keine Zusammenfassende Meldung (nur für innergemeinschaftlichen Verkehr)</li>
</ul>

<h2>Übersichtstabelle</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Szenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">MwSt.</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Vermerk</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxemburg</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (innergemeinschaftlich)</td><td class="border border-gray-300 px-4 py-2">0 % (Reverse Charge)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Richtlinie 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (klassische Leistungen)</td><td class="border border-gray-300 px-4 py-2">17 % LU (Standard)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / elektronische Leistungen) – &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">MwSt. des Kundenlandes</td><td class="border border-gray-300 px-4 py-2">OSS-Verfahren</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Drittland</td><td class="border border-gray-300 px-4 py-2">0 % (in LU nicht steuerbar)</td><td class="border border-gray-300 px-4 py-2">„MwSt. nicht anwendbar – Leistungsort außerhalb der EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Warenausfuhr in Drittländer</td><td class="border border-gray-300 px-4 py-2">0 % (befreit)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Richtlinie 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Waren nach Nordirland</strong></td><td class="border border-gray-300 px-4 py-2">0 % (innergemeinschaftliche Lieferung)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Richtlinie – Kundennummer mit Präfix „XI"</td></tr>
    </tbody>
</table>

<h2>Fremdwährungen</h2>

<p>Fakturieren Sie in Fremdwährung, muss der MwSt.-Betrag für Ihre luxemburgische Erklärung <strong>in Euro umgerechnet</strong> werden. Zwei Grundsätze gelten:</p>

<ul>
    <li>Wählen Sie eine <strong>konstante Umrechnungsmethode</strong> und wenden Sie sie auf alle Umsätze des Geschäftsjahres an</li>
    <li>Sorgen Sie für <strong>Übereinstimmung zwischen Rechnung und Buchhaltung</strong>: derselbe Kurs muss sich in beiden wiederfinden</li>
</ul>

<p>Welcher Referenzkurs zu wählen ist, hängt von Ihrer Situation ab: lassen Sie das einmal von Ihrem Treuhänder bestätigen, statt es Rechnung für Rechnung zu improvisieren.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Nicht mit dem Zollkurs verwechseln</p>
    <p>Die <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Zollverwaltung veröffentlicht eigene Wechselkurse</a>, die der Ermittlung des <strong>Zollwerts</strong> der Waren dienen. Das ist ein anderer Kurs als jener für die Umrechnung der MwSt.-Bemessungsgrundlage. Wer Waren ausführt, hat mit beiden zu tun.</p>
</div>

<h2>Was sich mit ViDA ändert (2027-2030)</h2>

<p>Das europäische Paket <strong>ViDA</strong> („MwSt. im digitalen Zeitalter") ist verabschiedet. Anders als oft zu lesen, beginnt es nicht 2030: der Zeitplan ist <strong>gestaffelt</strong>, und die ersten Fristen kommen deutlich früher.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Termin</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Was in Kraft tritt</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2027</td><td class="border border-gray-300 px-4 py-2">Erste Erweiterung des einheitlichen Schalters OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Juli 2028</td><td class="border border-gray-300 px-4 py-2">Verpflichtender Reverse Charge in bestimmten Fällen, Plattformregeln, weitere Erweiterung des OSS auf inländische B2C-Umsätze</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1. Juli 2030</td><td class="border border-gray-300 px-4 py-2">Verpflichtende elektronische Rechnungsstellung und digitale Meldung für innergemeinschaftliche B2B-Umsätze</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2035</td><td class="border border-gray-300 px-4 py-2">Harmonisierung bestehender nationaler Systeme mit der europäischen Norm</td></tr>
    </tbody>
</table>

<p>Wer regelmäßig Kunden in der EU fakturiert, den betreffen zuerst <strong>2027 und 2028</strong>, nicht 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Die innergemeinschaftlichen und internationalen MwSt.-Regeln entwickeln sich, und der ViDA-Zeitplan kann noch präzisiert werden. Diese Seite wird regelmäßig aktualisiert – für Ihre Situation wenden Sie sich an Ihren Treuhänder oder die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestimmung des Leistungsorts</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu – Innergemeinschaftliche Umsätze</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinie 2006/112/EG – Artikel 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung innergemeinschaftlicher MwSt.-Nummern</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Europäische Kommission – Ort der Besteuerung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/artikel-17-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Artikel 17 LIVA: Reverse Charge B2B innergemeinschaftlich →</a></li><li><a href="/de/blog/innergemeinschaftliche-mwst-leitfaden-luxemburgische-unternehmen" class="text-primary-500 hover:text-primary-600 text-sm">Innergemeinschaftliche MwSt. – vollständiger Leitfaden →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">Vergleich: Rechnungssoftware wählen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturieren Sie ins Ausland rechtskonform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt das MwSt.-Szenario automatisch anhand des Kunden (Land, B2B/B2C) und setzt den richtigen Vermerk. VIES-Prüfung integriert.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'einnahmenbuch-luxemburg-pflichten-vorlage',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Das <strong>Einnahmenbuch</strong> ist eine einfache Buchführungsunterlage, die in Luxemburg von Selbstständigen, Freiberuflern und Kleinunternehmen geführt wird, die keine doppelte Buchführung betreiben. Es erfasst chronologisch sämtliche vereinnahmten Erlöse. Hier ist alles Wesentliche für 2026.</p>

<h2>Was ist das Einnahmenbuch?</h2>

<p>Das Einnahmenbuch ist ein chronologisches Register aller ausgestellten Rechnungen und erhaltenen Zahlungen Ihres Unternehmens. Es ist die einfachste Form der gesetzlich verlangten Buchführung.</p>

<p>Grundlage ist <strong>Artikel 65 Absatz 2 des MwSt.-Gesetzes</strong>, der jedem Steuerpflichtigen auferlegt, „eine Buchhaltung zu führen, die hinreichend detailliert ist, um die Anwendung der MwSt. und deren Kontrolle durch die Verwaltung zu ermöglichen". Das Gesetz schreibt kein einheitliches Muster vor, sondern ein zu erreichendes Ergebnis. Für einen Selbstständigen in vereinfachter Buchführung ist das Einnahmenbuch der übliche Weg dorthin.</p>

<p>Anders als das Hauptbuch handelt es sich um eine <strong>vereinfachte</strong> Unterlage für <strong>Selbstständige, Freiberufler und Kleinunternehmen</strong> ohne doppelte Buchführung.</p>

<h2>Wer muss ein Einnahmenbuch führen?</h2>

<ul>
    <li><strong>Selbstständige und freie Berufe</strong>, die keine vollständige kaufmännische Buchführung betreiben</li>
    <li><strong>Kleine Einzelunternehmen</strong>, auch in der MwSt.-Freigrenze (Artikel 57bis LIVA, Schwelle 50 000 € netto seit dem 1. Januar 2025)</li>
    <li><strong>Kaufleute in vereinfachter Buchführung</strong>, deren Umsatz die Schwelle zur vollständigen kaufmännischen Buchführung nicht überschreitet</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Hinweis</p>
    <p><strong>Gesellschaften mit doppelter Buchführung</strong> (SARL, SA usw.) müssen kein gesondertes Einnahmenbuch führen: Hauptbuch und Journale erfüllen diese Funktion. Die Praxis betrifft vor allem Selbstständige in vereinfachter Buchführung.</p>
</div>

<h2>Was muss das Einnahmenbuch enthalten?</h2>

<p>Jeder Eintrag muss ausweisen:</p>

<ul>
    <li><strong>Das Datum</strong> der Rechnung oder der Zahlung</li>
    <li><strong>Die Rechnungsnummer</strong> (fortlaufende Nummerierung, Art. 63 LIVA)</li>
    <li><strong>Den Namen des Kunden</strong></li>
    <li><strong>Die Beschreibung</strong> der Leistung oder des verkauften Gegenstands</li>
    <li><strong>Den Nettobetrag</strong></li>
    <li><strong>Den angewandten MwSt.-Satz</strong> (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
    <li><strong>Den MwSt.-Betrag</strong></li>
    <li><strong>Den Bruttobetrag</strong></li>
</ul>

<h2>Format und Aufbewahrung</h2>

<p>Das Einnahmenbuch kann geführt werden:</p>

<ul>
    <li><strong>In Papierform</strong>: in einem eigenen Heft, ohne Streichungen und Leerstellen</li>
    <li><strong>In digitaler Form</strong>: über eine Rechnungssoftware, eine Tabellenkalkulation oder ein PDF, mit Integritätsgarantien</li>
</ul>

<p>Es ist <strong>zehn Jahre ab seinem Abschluss</strong> aufzubewahren (Art. 65 Absatz 4 des MwSt.-Gesetzes und Artikel 16 des Handelsgesetzbuchs). Beachten Sie die Nuance: Bei <strong>Büchern</strong> läuft die Frist ab dem Abschluss, bei <strong>Rechnungen</strong> dagegen ab ihrem Ausstellungsdatum. Siehe die <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Buchführungspflichten auf Guichet.lu</a>.</p>

<h2>Das Einnahmenbuch mit faktur.lu erzeugen</h2>

<p>Das Einnahmenbuch entsteht automatisch aus Ihren Rechnungen — Sie müssen nichts neu erfassen:</p>

<ol>
    <li>Gehen Sie zu <strong>Buchhaltung &gt; Einnahmenbuch</strong></li>
    <li>Wählen Sie den gewünschten Zeitraum (Monat, Quartal, Jahr)</li>
    <li>Sehen Sie jede Rechnung im Detail mit MwSt.-Aufschlüsselung</li>
    <li>Exportieren Sie als <strong>PDF</strong> oder <strong>CSV</strong> für Ihren Treuhänder</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ab dem Tarif Essentiel verfügbar</p>
    <p>Das Einnahmenbuch gehört zu den Buchhaltungsexporten und ist in den Tarifen <strong>Essentiel</strong> und <strong>Pro</strong> enthalten. Der Gratis-Tarif bietet es nicht — der <strong>FAIA-Export ist dort hingegen enthalten</strong>, was nicht auf der Hand liegt: die beiden Funktionen liegen nicht auf derselben Tarifstufe.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Nicht zu verwechseln: AED und ACD</p>
    <p>Die <strong>AED</strong> (Registrierungs-, Domänen- und MwSt.-Verwaltung) betreut die MwSt. und die Rechnungsprüfungen. Die <strong>ACD</strong> (Verwaltung der direkten Steuern) betreut die Einkommen- und Körperschaftsteuer. Das Einnahmenbuch dient <strong>beiden</strong>: der AED als MwSt.-Beleg, der ACD als Grundlage Ihres steuerpflichtigen Ergebnisses. Ein Dokument, zwei Verwaltungen.</p>
</div>

<h2>Einnahmenbuch oder FAIA-Export?</h2>

<p>Verwechseln Sie die beiden nicht:</p>

<ul>
    <li>Das <strong>Einnahmenbuch</strong> ist eine laufende Übersicht, im Alltag genutzt und an den Treuhänder weitergegeben</li>
    <li>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei (von der AED angepasster SAF-T-Standard), nur bei einer Prüfung verlangt, und nur wenn <strong>vier Bedingungen gleichzeitig erfüllt</strong> sind: dem normierten Kontenplan unterliegen, keine vereinfachte Regelung in Anspruch nehmen, über 112 000 € Umsatz und etwa 500 Buchungstransaktionen jährlich liegen</li>
</ul>

<p>Anders gesagt: Wer ein Einnahmenbuch führt, weil er in vereinfachter Buchführung ist, unterliegt der FAIA höchstwahrscheinlich <strong>nicht</strong>. Siehe unseren <a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden">vollständigen FAIA-Leitfaden</a>.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 65 (Buchführung und Aufbewahrung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten der Unternehmen</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg →</a></li><li><a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden" class="text-primary-500 hover:text-primary-600 text-sm">Die FAIA-Datei →</a></li><li><a href="/de/blog/rechnungsarchivierung-luxemburg-gesetzliche-dauer-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivierung von Rechnungen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Ihr Einnahmenbuch, ohne Neuerfassung</h3>
    <p class="text-primary-800 mb-4">faktur.lu baut Ihr Einnahmenbuch aus Ihren Rechnungen auf und exportiert es als PDF oder CSV für Ihren Treuhänder. Ab dem Tarif Essentiel enthalten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'innergemeinschaftliche-mwst-leitfaden-luxemburgische-unternehmen',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Sie stellen Kunden in anderen EU-Ländern Rechnungen? Die <strong>innergemeinschaftliche Mehrwertsteuer</strong> folgt besonderen Regeln, die jeder luxemburgische Unternehmer beherrschen sollte. Dieser Leitfaden erklärt sie Ihnen.</p>

<h2>Was ist die innergemeinschaftliche Mehrwertsteuer?</h2>

<p>Die innergemeinschaftliche MwSt. ist die Regelung für den Waren- und Dienstleistungsverkehr zwischen Unternehmen in verschiedenen Ländern der <strong>Europäischen Union</strong>. Grundprinzip ist die <strong>Umkehrung der Steuerschuldnerschaft</strong> (Reverse Charge): Nicht der Verkäufer, sondern der Käufer meldet und zahlt die MwSt. in seinem Land.</p>

<h2>Die innergemeinschaftliche MwSt.-Nummer</h2>

<p>In Luxemburg hat Ihre innergemeinschaftliche MwSt.-Nummer das Format <strong>LU + 8 Ziffern</strong> (Beispiel: LU12345678). Diese Nummer ist:</p>

<ul>
    <li>Von der <strong>AED</strong> (Registrierungs-, Domänen- und MwSt.-Verwaltung) zugeteilt</li>
    <li>Für jede innergemeinschaftliche Transaktion zwingend erforderlich</li>
    <li>Über das <strong>VIES-System</strong> der Europäischen Kommission überprüfbar</li>
</ul>

<p><strong>Tipp:</strong> faktur.lu prüft MwSt.-Nummern automatisch über VIES, sobald Sie einen innergemeinschaftlichen Kunden anlegen.</p>

<h2>Regeln der innergemeinschaftlichen Fakturierung</h2>

<h3>Verkauf von B2B-Dienstleistungen (häufigster Fall)</h3>

<p>Wenn Sie eine Dienstleistung an ein Unternehmen in einem anderen EU-Land verkaufen:</p>

<ol>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong></li>
    <li>Sie vermerken auf der Rechnung: <strong>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</strong> (Artikel 44 der Richtlinie bestimmt den Ort der Besteuerung; Artikel 196 benennt den Leistungsempfänger als Steuerschuldner und entspricht der Pflichtangabe – siehe Art. 226 §11bis der Richtlinie)</li>
    <li>Sie geben Ihre MwSt.-Nummer <strong>und</strong> die des Kunden an</li>
    <li>Der Kunde meldet die MwSt. in seinem eigenen Land (Reverse-Charge-Verfahren)</li>
</ol>

<h3>Verkauf von B2B-Waren</h3>

<p>Für Warenlieferungen an ein EU-Unternehmen:</p>

<ol>
    <li>Sie fakturieren <strong>ohne MwSt.</strong> (steuerfreie innergemeinschaftliche Lieferung)</li>
    <li>Sie vermerken: <strong>„Steuerfreie innergemeinschaftliche Lieferung – Artikel 138 der Richtlinie 2006/112/EG"</strong></li>
    <li>Sie müssen nachweisen, dass die Waren Luxemburg verlassen haben</li>
    <li>Die Transaktion muss in Ihrer <strong>Zusammenfassenden Meldung</strong> erscheinen</li>
</ol>

<h3>Verkauf an Privatpersonen (B2C)</h3>

<p>Für Verkäufe an Privatpersonen in der EU gelten andere Regeln. Maßgeblich ist eine <strong>einheitliche Schwelle von 10 000 € pro Jahr</strong>:</p>

<ul>
    <li><strong>Unter 10 000 € pro Jahr</strong>: Sie berechnen die <strong>luxemburgische MwSt.</strong>, wie bei einem lokalen Kunden</li>
    <li><strong>Über 10 000 €</strong>: Sie wenden die <strong>MwSt. des Kundenlandes</strong> an und melden sie über den einheitlichen Schalter <strong>OSS</strong></li>
    <li><strong>Diese Schwelle gilt gemeinsam</strong> für Fernverkäufe von Waren <em>und</em> für elektronische, Telekommunikations- und Rundfunkdienstleistungen – über alle EU-Länder hinweg (ohne Luxemburg). Zu verfolgen ist also die <strong>Summe</strong> Ihrer europäischen B2C-Umsätze, nicht jede Kategorie einzeln</li>
    <li><strong>Andere Dienstleistungen</strong> (Beratung, Präsenzschulungen…): in der Regel luxemburgische MwSt., mit Ausnahmen je nach Art der Leistung</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Die Überschreitung wirkt sofort</p>
    <p>Der Umsatz, der die 10 000 € überschreitet, ist <strong>bereits</strong> im Land des Kunden steuerpflichtig. Frühere Verkäufe bleiben in Luxemburg steuerpflichtig. Verfolgen Sie Ihre Summe daher unterjährig, statt sie zum Jahresabschluss zu entdecken.</p>
</div>

<h2>Meldepflichten</h2>

<p>Als luxemburgisches Unternehmen mit innergemeinschaftlichen Umsätzen müssen Sie:</p>

<ul>
    <li><strong>Periodische MwSt.-Erklärung</strong>: Ihre innergemeinschaftlichen Umsätze in den vorgesehenen Feldern angeben</li>
    <li><strong>Zusammenfassende Meldung</strong>: monatliche oder vierteljährliche Aufstellung aller innergemeinschaftlichen Verkäufe nach Kunde und Land</li>
    <li><strong>Intrastat</strong>: statistische Meldung für Warenbewegungen oberhalb bestimmter Schwellen</li>
</ul>

<h2>VIES-Prüfung: warum sie entscheidend ist</h2>

<p>Bevor Sie einem EU-Kunden ohne MwSt. fakturieren, <strong>müssen</strong> Sie die Gültigkeit seiner MwSt.-Nummer über das <strong>VIES-System</strong> (VAT Information Exchange System) prüfen. Ist die Nummer ungültig:</p>

<ul>
    <li>Müssen Sie <strong>mit luxemburgischer MwSt.</strong> fakturieren</li>
    <li>Riskieren Sie eine <strong>Steuernachforderung</strong>, wenn Sie ohne Prüfung steuerfrei fakturieren</li>
    <li>Bewahren Sie einen <strong>Nachweis der VIES-Prüfung</strong> auf (Screenshot oder Protokoll)</li>
</ul>

<p>faktur.lu prüft jede innergemeinschaftliche MwSt.-Nummer automatisch und protokolliert die Validierung.</p>

<h2>Häufige Praxisfälle</h2>

<h3>Luxemburgischer Berater fakturiert einen deutschen Kunden</h3>
<p>Sie fakturieren ohne MwSt. mit Reverse-Charge-Vermerk. Der deutsche Kunde meldet die deutsche MwSt. (19 %) in seiner eigenen Erklärung. Sie melden den Vorgang in Ihrer Zusammenfassenden Meldung.</p>

<h3>Luxemburgische Webagentur fakturiert einen französischen Kunden</h3>
<p>Gleiches Prinzip: Rechnung ohne MwSt., Reverse Charge. Der französische Kunde meldet 20 % französische MwSt. Prüfen Sie vor der Fakturierung seine MwSt.-Nummer über VIES.</p>

<h3>Luxemburgischer Onlinehändler verkauft an eine belgische Privatperson</h3>
<p>Solange Ihre kumulierten europäischen B2C-Umsätze (Fernverkäufe + elektronische Dienstleistungen) unter 10 000 EUR/Jahr bleiben, berechnen Sie die luxemburgische MwSt. Darüber wenden Sie die MwSt. des Kundenlandes an (21 % in Belgien) über das Verfahren <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Pflichtangaben auf der Rechnung</h2>

<p>Jede innergemeinschaftliche Rechnung muss enthalten:</p>

<ul>
    <li>Ihre luxemburgische MwSt.-Nummer</li>
    <li>Die MwSt.-Nummer des Kunden</li>
    <li>Den gesetzlichen Befreiungsvermerk (Reverse Charge oder innergemeinschaftliche Lieferung)</li>
    <li>Den Nettobetrag und den Vermerk „MwSt. 0 %"</li>
</ul>

<p>faktur.lu erkennt das MwSt.-Szenario automatisch nach Land und Kundentyp und setzt die passenden gesetzlichen Vermerke.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung von MwSt.-Nummern (Europäische Kommission)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Richtlinie 2006/112/EG (konsolidierte Fassung)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/mwst-luxemburg-saetze-berechnung-pflichten" class="text-primary-500 hover:text-primary-600 text-sm">MwSt. in Luxemburg →</a></li><li><a href="/de/blog/aus-luxemburg-ins-ausland-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Ins Ausland fakturieren →</a></li><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">Vergleich: Rechnungssoftware wählen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturieren Sie EU-weit rechtskonform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt innergemeinschaftliche MwSt.-Szenarien automatisch, prüft MwSt.-Nummern über VIES und setzt die richtigen gesetzlichen Vermerke.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Luxemburgische Schwellen, Sätze und Steuerverfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich jedoch an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'mwst-befreiung-luxemburg-schwelle-pflichten-normalregime',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">In Luxemburg können Kleinunternehmen mit einem Nettojahresumsatz bis <strong>50 000 €</strong> die <strong>MwSt.-Freigrenzenregelung</strong> in Anspruch nehmen (Artikel 57bis des luxemburgischen MwSt.-Gesetzes). Diese Schwelle wurde am 1. Januar 2025 von 35 000 € auf 50 000 € angehoben — zeitgleich mit einer noch wenig bekannten <strong>grenzüberschreitenden Regelung</strong>. Hier ist alles Wissenswerte für 2026.</p>

<h2>Was ist die MwSt.-Freigrenze?</h2>

<p>Die MwSt.-Freigrenze (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">Artikel 57bis LIVA</a>) ist eine <strong>Sonderregelung</strong>, die es Kleinunternehmen erlaubt, ihre Lieferungen und Leistungen von der MwSt. zu befreien. Konkret:</p>

<ul>
    <li>Sie berechnen Ihren Kunden <strong>keine MwSt.</strong></li>
    <li>Sie führen <strong>keine MwSt.</strong> an den Staat ab</li>
    <li>Sie sind von der Pflicht befreit, <strong>ordentliche MwSt.-Erklärungen</strong> einzureichen</li>
</ul>

<p>Im Gegenzug können Sie die auf Ihre betrieblichen Einkäufe gezahlte <strong>MwSt. nicht zurückholen</strong>.</p>

<h2>Voraussetzungen</h2>

<ul>
    <li>Nettojahresumsatz von <strong>höchstens 50 000 €</strong> im Kalenderjahr</li>
    <li><strong>Toleranz von 10 %</strong>: Überschreiten Sie unterjährig, ohne <strong>55 000 €</strong> zu übersteigen, bleiben Sie bis zum 31. Dezember in der Freigrenze</li>
    <li>Sitz der wirtschaftlichen Tätigkeit in Luxemburg (eine bloße feste Niederlassung genügt nicht)</li>
    <li>Die Regelung ist <strong>optional</strong>: Sie können das Normalregime vorziehen</li>
</ul>

<p><strong>Von der Freigrenze ausgeschlossene Umsätze:</strong> gelegentliche Umsätze im Sinne von Artikel 12 der Richtlinie 2006/112/EG sowie <strong>Lieferungen neuer Fahrzeuge</strong> in einen anderen Mitgliedstaat. Ausgeschlossen sind ferner Steuerpflichtige der MwSt.-Gruppe, der Pauschalregelung für Land- und Forstwirte oder mit unvereinbaren Immobilienoptionen.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wussten Sie schon?</p>
    <p>Die Schwelle wurde zum 1. Januar 2025 von 35 000 € auf 50 000 € angehoben, im Rahmen der Umsetzung der <a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Richtlinie (EU) 2020/285</a>. Die Obergrenze, die ein Mitgliedstaat festlegen darf, liegt bei 85 000 €; Luxemburg sieht keine sektoriellen Schwellen vor.</p>
</div>

<h2>Wovon die Freigrenze Sie nicht befreit</h2>

<p>Das ist der am häufigsten missverstandene Punkt der Regelung, und er betrifft viele. In der Freigrenze zu sein bedeutet <strong>nicht, von allen Meldepflichten befreit zu sein</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Wenn Sie gewerbliche Kunden in der EU fakturieren</p>
    <p class="text-red-700">Sobald Sie <strong>innergemeinschaftliche Dienstleistungen</strong> erbringen — oder nach Artikel 61 des MwSt.-Gesetzes in Luxemburg steuerschuldnerisch werden — müssen Sie <strong>zwingend</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>eine <strong>vereinfachte Jahreserklärung</strong> über das Portal <strong>eCDF</strong> einreichen, und zwar <strong>vor dem 1. März</strong> des folgenden Kalenderjahres;</li>
        <li><strong>Zusammenfassende Meldungen</strong> zu diesen innergemeinschaftlichen Dienstleistungen abgeben.</li>
    </ul>
    <p class="text-red-700 mt-2">Ein einziger gewerblicher Kunde in Deutschland, Frankreich oder Belgien löst beide Pflichten aus.</p>
</div>

<p>Außerhalb dieses Falls teilt der Steuerpflichtige in der Freigrenze seinen Jahresumsatz seinem Steueramt mit — per Post, per E-Mail oder über das von der AED bereitgestellte Formular der vereinfachten Jahreserklärung.</p>

<p>Fallen Sie <strong>innerhalb desselben Jahres</strong> unter die Freigrenze und dann unter das Normalregime, ist der unter der Freigrenze erzielte Umsatz in <strong>Feld 481</strong> der im Normalregime abzugebenden MwSt.-Erklärung einzutragen.</p>

<h2>Pflichtangaben auf Ihren Rechnungen</h2>

<p>Auch in der Freigrenze müssen Ihre Rechnungen folgenden <strong>exakten Vermerk</strong> tragen:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979"
</blockquote>

<p>Außerdem gilt:</p>
<ul>
    <li><strong>Keine MwSt. ausweisen</strong> (keine MwSt.-Zeile auf der Rechnung)</li>
    <li>Keinen <strong>MwSt.-Satz</strong> angeben</li>
    <li>Nur den <strong>Nettobetrag</strong> nennen (keine Unterscheidung netto/brutto)</li>
</ul>

<p>Gute Nachricht seit dem 1. Januar 2025: Wer die Freigrenze in Anspruch nimmt, darf <strong>vereinfachte Rechnungen</strong> ausstellen.</p>

<h2>Vorteile der Freigrenze</h2>

<ul>
    <li><strong>Einfachheit</strong>: keine ordentliche MwSt.-Erklärung, vereinfachte Fakturierung erlaubt</li>
    <li><strong>B2C-Wettbewerbsfähigkeit</strong>: bei gleichem Nettopreis ist Ihre Rechnung für eine Privatperson rund <strong>14,5 % günstiger</strong> als die eines Wettbewerbers im Normalregime (17 % MwSt. auf 117 € brutto). Achtung: dieser Vorsprung wird durch die nicht erstattete MwSt. auf Ihre eigenen Einkäufe geschmälert</li>
    <li><strong>Liquidität</strong>: keine Verschiebung zwischen vereinnahmter und abgeführter MwSt.</li>
    <li><strong>Weniger Papierkram</strong>: vereinfachte Verwaltung</li>
</ul>

<h2>Nachteile</h2>

<ul>
    <li><strong>Kein Vorsteuerabzug</strong>: Sie zahlen MwSt. auf Ihre Einkäufe, ohne sie zurückzuholen (entscheidend, wenn Sie investieren)</li>
    <li><strong>B2B-Nachteil</strong>: gewerbliche Kunden ziehen aus Ihren Rechnungen keine MwSt. ab, Ihr Preis kommt sie also teurer</li>
    <li><strong>Image</strong>: manche B2B-Kunden arbeiten lieber mit steuerpflichtigen Unternehmen</li>
    <li><strong>Restpflichten</strong>: sobald Sie Leistungen an EU-Unternehmer fakturieren, werden vereinfachte Jahreserklärung und Zusammenfassende Meldungen wieder verpflichtend</li>
    <li><strong>Enge Obergrenze</strong>: jenseits von Schwelle und Toleranz ist der Wechsel ins Normalregime zwingend</li>
</ul>

<h2>Die grenzüberschreitende Freigrenzenregelung (seit 2025)</h2>

<p>Das ist die zweite Neuerung vom 1. Januar 2025, weit weniger kommentiert als die Schwellenanhebung. Bis dahin galt die Freigrenze nur im Sitzstaat. Nun kann ein luxemburgisches Kleinunternehmen die <strong>Freigrenze auch in anderen Mitgliedstaaten</strong> nutzen.</p>

<h3>Die Bedingungen</h3>

<ul>
    <li>Die <strong>nationale Schwelle jedes Mitgliedstaats</strong> einhalten, in dem Sie die Freigrenze wollen (die Schwellen variieren: siehe das <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">EU-Portal SME VAT rules</a>)</li>
    <li>Einen Umsatz von <strong>unter 100 000 € in der gesamten Union</strong> erzielen — die „Unionsschwelle", die Ihre Umsätze in Luxemburg einschließt</li>
    <li>Den <strong>Sitz Ihrer wirtschaftlichen Tätigkeit</strong> in Luxemburg haben. Wer seinen Sitz in einem Drittland hat, kann sie nicht nutzen, auch nicht mit fester Niederlassung in der EU</li>
</ul>

<h3>Die „EX"-Nummer</h3>

<p>Das Verfahren läuft über eine <strong>vorherige Mitteilung</strong> in Ihrem Unternehmensbereich auf <strong>MyGuichet.lu</strong>. Die AED leitet den Antrag an die betroffenen Mitgliedstaaten weiter. Sobald mindestens einer zustimmt, erhalten Sie eine Identifikationsnummer <strong>„EX"</strong> der Form <strong>LU12345678-EX</strong> — in der Regel binnen <strong>35 Arbeitstagen</strong>.</p>

<p>Die Freigrenze gilt in einem Mitgliedstaat erst <strong>ab dem Datum der Mitteilung oder Bestätigung</strong> dieser Nummer. Wichtig: eine <strong>rückwirkende Identifizierung ist nicht möglich</strong>. Vor Erhalt der Nummer getätigte Verkäufe lassen sich nicht nachträglich einbeziehen.</p>

<h3>Ihre Meldepflichten</h3>

<ul>
    <li><strong>Vierteljährliche Meldung</strong> an die AED des Gesamtumsatzes in <strong>allen</strong> Mitgliedstaaten — Luxemburg eingeschlossen</li>
    <li>Im Gegenzug müssen Sie sich in den Mitgliedstaaten, in denen Sie nicht ansässig sind, für die von der Freigrenze erfassten Umsätze weder registrieren noch MwSt.-Erklärungen abgeben</li>
    <li>Für die dort <strong>nicht erfassten</strong> Umsätze (insbesondere innergemeinschaftliche Erwerbe) gelten weiterhin die örtlichen Pflichten</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Wenn Sie die Unionsschwelle überschreiten</p>
    <p>Über 100 000 € Umsatz in der Union verlieren Sie die Freigrenze in den Mitgliedstaaten, in denen Sie nicht ansässig sind — <strong>auch im folgenden Kalenderjahr</strong>, selbst wenn die nationalen Schwellen dort eingehalten werden. Die nationale Freigrenze in Luxemburg behalten Sie hingegen, solange Sie dort die Voraussetzungen erfüllen.</p>
</div>

<h2>Wann ins Normalregime wechseln?</h2>

<h3>Zwingender Wechsel (Schwellenüberschreitung)</h3>

<p>Die Regel hängt vom <strong>Ausmaß der Überschreitung</strong> der 50 000-€-Schwelle ab:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Wirkung</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Überschreitung um höchstens 10 % (Umsatz bis 55 000 €)</td><td class="p-2 border-b">Sie bleiben bis zum 31. Dezember in der Freigrenze. Wechsel ins Normalregime am <strong>1. Januar des Folgejahres</strong>.</td></tr>
        <tr><td class="p-2 border-b">Überschreitung um mehr als 10 % (Umsatz über 55 000 €)</td><td class="p-2 border-b">Die Freigrenze entfällt <strong>ab dem Tag nach der Überschreitung</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Das offizielle Beispiel der AED</p>
    <p>Ein Steuerpflichtiger erreicht 51 000 €: Überschreitung unter 10 %, er bleibt in der Freigrenze. Am <strong>8. September 2025</strong> bringt ein Verkauf von 5 000 € seinen Umsatz auf <strong>56 000 €</strong>. Die Überschreitung liegt über 10 %: er ist <strong>ab dem 9. September 2025</strong> von der Freigrenze ausgeschlossen und bleibt es das ganze Jahr 2026.</p>
    <p class="mt-2">Beachten Sie die Mechanik: es ist der <strong>Folgetag</strong>. Die Rechnung, die die Obergrenze reißt, wird noch ohne MwSt. ausgestellt; erst die nächste trägt sie.</p>
</div>

<p><strong>In beiden Fällen</strong> schließt die Überschreitung der Schwelle im Kalenderjahr Sie für das <strong>gesamte folgende Kalenderjahr</strong> von der Freigrenze aus, unabhängig vom Prozentsatz.</p>

<p>Sie müssen dann:</p>

<ol>
    <li>Die <strong>AED</strong> über MyGuichet.lu oder Ihr Steueramt über den Regimewechsel informieren</li>
    <li>Nach obigem Zeitplan mit der MwSt.-Berechnung beginnen</li>
    <li>Die von der AED festgelegte Erklärungsperiodizität einhalten</li>
</ol>

<h3>Freiwilliger Wechsel (Option für das Normalregime)</h3>

<p>Sie können <strong>auch unterhalb der Schwelle</strong> für das Normalregime optieren, mit Antrag bei Ihrem Steueramt. <strong>Die Option wirkt ab dem 1. Tag des Folgemonats</strong> und bindet Sie für <strong>mindestens ein Kalenderjahr</strong>: es ist keine Hin- und Rückfahrt.</p>

<p>Oft die richtige Wahl, wenn:</p>

<ul>
    <li>Ihre Kunden überwiegend <strong>Unternehmen (B2B)</strong> sind, die MwSt. abziehen</li>
    <li>Sie <strong>größere Investitionen</strong> haben (Ausrüstung, Firmenfahrzeug) und die Vorsteuer zurückholen wollen</li>
    <li>Sie sich der Schwelle nähern und lieber <strong>vorausschauend</strong> handeln, statt unterjährig die Preise zu ändern</li>
    <li>Sie <strong>international (innergemeinschaftliches B2B)</strong> fakturieren und eine aktive MwSt.-Nummer brauchen</li>
</ul>

<p>Ebenfalls zu beachten: bei <strong>Betriebsaufgabe</strong> ist Ihrem Steueramt binnen <strong>fünfzehn Tagen</strong> eine Erklärung zu übermitteln. Die Freigrenze endet mit dem Datum der Aufgabe.</p>

<h2>Erklärungsperiodizität nach dem Wechsel</h2>

<p>Im Normalregime richtet sich die Periodizität nach Ihrem Nettojahresumsatz. <strong>Die Jahreserklärung tritt zu den periodischen Erklärungen hinzu, sie ersetzt sie nicht:</strong></p>

<ul>
    <li><strong>Umsatz &lt; 112 000 €</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz &gt; 620 000 €</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<p>Die AED bestimmt Ihr Regime – Sie wählen es nicht selbst.</p>

<h2>Auswirkung auf Ihre Rechnungen mit faktur.lu</h2>

<p>faktur.lu beherrscht beide Regime:</p>

<ul>
    <li><strong>MwSt.-Freigrenze</strong>: die Rechnungen tragen automatisch den Vermerk „MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979", ohne MwSt.-Zeile und ohne Netto-/Bruttounterscheidung</li>
    <li><strong>Normalregime</strong>: die MwSt. wird automatisch mit dem richtigen Satz berechnet (17 %, 14 %, 8 % oder 3 %)</li>
    <li><strong>Schwellenwarnung</strong>: faktur.lu meldet sich, wenn Sie sich der Schwelle von 50 000 € (und der Toleranz bei 55 000 €) nähern</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen, Sätze und MwSt.-Verfahren können sich ändern, und die grenzüberschreitende Regelung ist neu. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Sonderregelung für Kleinunternehmen (Freigrenze)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Freigrenze (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Europäische Kommission – Nationale Freigrenzen je Mitgliedstaat</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Richtlinie (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/mwst-luxemburg-saetze-berechnung-pflichten" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Sätze in Luxemburg →</a></li><li><a href="/de/blog/freiberufler-luxemburg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freiberufler Luxemburg: konform fakturieren →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verwalten Sie Ihre MwSt.-Freigrenze gelassen</h3>
    <p class="text-primary-800 mb-4">faktur.lu passt sich automatisch Ihrem MwSt.-Regime an und warnt Sie vor der Überschreitung der Schwelle von 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'peppol-b2g-luxemburg-vollstaendiger-leitfaden-2026',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Seit 2022-2023 ist die elektronische Rechnungsstellung über <strong>Peppol</strong> für Lieferanten des öffentlichen Sektors in Luxemburg verpflichtend, <strong>unabhängig vom Rechnungsbetrag</strong>. Dieser Leitfaden erklärt Ihnen alles, was Sie 2026 zur Einhaltung wissen müssen.</p>

<h2>Was ist Peppol?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> ist ein internationales Netzwerk für elektronische Rechnungsstellung, über das Geschäftsdokumente (Rechnungen, Bestellungen) standardisiert zwischen Unternehmen und öffentlichen Verwaltungen ausgetauscht werden. Betrieben von <a href="https://peppol.org" target="_blank" rel="noopener">OpenPeppol</a>, erstreckt es sich inzwischen auf über 100 Länder und zählt mehrere Millionen registrierte Teilnehmer.</p>

<p>In Luxemburg ist Peppol der offizielle Kanal für die elektronische B2G-Rechnungsstellung (Business-to-Government). Jedes Unternehmen, das dem Staat, den Gemeinden oder öffentlichen Einrichtungen Rechnungen stellt, muss dieses Format nutzen.</p>

<h2>Wer ist betroffen?</h2>

<p>Wenn Sie eine der folgenden Stellen beliefern, müssen Sie über Peppol fakturieren:</p>

<ul>
    <li><strong>Den luxemburgischen Staat</strong> und seine Ministerien</li>
    <li><strong>Die luxemburgischen Gemeinden</strong></li>
    <li><strong>Öffentliche Einrichtungen</strong> (Krankenhäuser, Schulen usw.)</li>
    <li><strong>Alle öffentlichen Aufträge und Konzessionsverträge</strong>, unabhängig vom Betrag</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Keine Betragsschwelle</p>
    <p>Anders als vielfach angenommen gibt es <strong>keine Mindestschwelle</strong> (etwa „30 000 €") für die Pflicht. Das <a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Gesetz vom 13. Dezember 2021</a> schreibt die elektronische Rechnungsstellung für <strong>alle</strong> Rechnungen im Rahmen eines öffentlichen Auftrags oder Konzessionsvertrags vor, vom kleinsten bis zum größten Betrag.</p>
</div>

<h3>Zeitplan der Einführung</h3>

<p>Die Pflicht trat gestaffelt in Kraft, je nach Größe des Wirtschaftsteilnehmers:</p>

<ul>
    <li><strong>18. Mai 2022</strong>: große Wirtschaftsteilnehmer</li>
    <li><strong>18. Oktober 2022</strong>: mittelgroße Wirtschaftsteilnehmer</li>
    <li><strong>18. März 2023</strong>: kleine Wirtschaftsteilnehmer und neu gegründete Unternehmen</li>
</ul>

<p>Seit dem 18. März 2023 sind somit <strong>alle</strong> Lieferanten des luxemburgischen öffentlichen Sektors betroffen.</p>

<h2>Wie funktioniert Peppol?</h2>

<p>Das Peppol-Netzwerk beruht auf einem Vier-Ecken-Modell (4-corner model):</p>

<ol>
    <li><strong>Der Absender</strong> (Ihr Unternehmen) erstellt die Rechnung</li>
    <li><strong>Der sendende Zugangspunkt</strong> (Ihre Rechnungssoftware oder deren Access Point) stellt die Rechnung in das Peppol-Netz ein</li>
    <li><strong>Der empfangende Zugangspunkt</strong> (auf Verwaltungsseite) nimmt die Rechnung entgegen</li>
    <li><strong>Der Empfänger</strong> (die öffentliche Verwaltung) verarbeitet die Rechnung</li>
</ol>

<p>Jeder Netzteilnehmer wird durch eine eindeutige <strong>Peppol Participant ID</strong> identifiziert. In Luxemburg ist das Standardschema <strong>9938</strong> (LU:VAT), aufbauend auf der MwSt.-Nummer. Format: <code>9938:LU########</code>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">⚠ Klassischer Fehler: Schema 0184</p>
    <p>Viele Quellen verwechseln das. Das Schema <strong>0184</strong> gehört zum <strong>dänischen</strong> Unternehmensregister (DIGSTORG/CVR), nicht zu Luxemburg. Das richtige LU-Schema ist <strong>9938</strong>. Prüfen Sie es in der <a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">offiziellen EAS-Codeliste von Peppol</a>.</p>
</div>

<h2>Das Format UBL und Peppol BIS Billing 3.0</h2>

<p>Peppol-Rechnungen in Luxemburg verwenden das Format <strong>Peppol BIS Billing 3.0</strong>, aufbauend auf dem XML-Standard <strong>UBL (Universal Business Language)</strong> und konform mit der europäischen Norm <strong>EN 16931</strong>. Ihre Rechnung muss enthalten:</p>

<ul>
    <li>Die Angaben des Absenders (Name, Anschrift, MwSt.-Nummer)</li>
    <li>Die Angaben des Empfängers (Name, Peppol Participant ID)</li>
    <li>Die Rechnungspositionen (Beschreibung, Menge, Einzelpreis)</li>
    <li>Die nach Sätzen aufgeschlüsselten MwSt.-Beträge</li>
    <li>Die Summen (netto, MwSt., brutto)</li>
    <li>Die Bestell- oder Vertragsreferenzen</li>
</ul>

<p>Das Format <strong>XRechnung 3.0.1</strong> (deutsche, EN-16931-konforme Norm) wird von den luxemburgischen Verwaltungen ebenfalls akzeptiert.</p>

<h2>Alternativen, wenn Sie nicht an Peppol angebunden sind</h2>

<p>Haben Sie noch keine an Peppol angebundene Software, steht Ihnen der offizielle Alternativkanal offen:</p>

<ul>
    <li><strong>MyGuichet.lu</strong>: Onlineformulare, über die sich eine konforme elektronische Rechnung manuell an die empfangenden Verwaltungen übermitteln lässt</li>
</ul>

<p>Das vollständige Verfahren finden Sie auf <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu – Übermittlung einer elektronischen Rechnung für einen öffentlichen Auftrag</a>.</p>

<h2>Peppol und faktur.lu: was heute verfügbar ist</h2>

<p>Wir sind an dieser Stelle lieber genau, als ein Häkchen zu setzen.</p>

<p><strong>Ab sofort verfügbar:</strong> faktur.lu erzeugt die <strong>Peppol-BIS-Billing-3.0-Datei (UBL)</strong> Ihrer Rechnungen, konform mit dem vom luxemburgischen öffentlichen Sektor verlangten Format. Sie laden sie aus der Rechnung herunter und übermitteln sie über den Kanal Ihrer Wahl — insbesondere über den Zugangspunkt des <strong>CTIE</strong>, den öffentliche Stellen ohne eigenen Zugangspunkt nutzen.</p>

<p><strong>In Produktivsetzung:</strong> die automatische Übermittlung über einen integrierten Zugangspunkt, die Ihnen diesen manuellen Schritt erspart. Das Format steht technisch bereit, der Rollout für alle Konten noch nicht. Wir aktualisieren diese Seite, sobald es so weit ist.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In der Zwischenzeit sind Sie regelkonform</p>
    <p>Die gesetzliche Pflicht betrifft das <strong>Format</strong> der Rechnung und ihre elektronische Übermittlung, nicht das Werkzeug, mit dem sie zugestellt wird. Eine konforme BIS-3.0-Datei, über den Kanal Ihres öffentlichen Auftraggebers eingereicht, erfüllt die Pflicht.</p>
</div>

<h2>Vorteile der Peppol-Rechnungsstellung</h2>

<ul>
    <li><strong>Schnellere Bearbeitung</strong>: Peppol-Rechnungen werden automatisiert verarbeitet, was die Zahlungsfristen verkürzt</li>
    <li><strong>Weniger Fehler</strong>: das strukturierte Format beseitigt Erfassungsfehler</li>
    <li><strong>Nachverfolgbarkeit</strong>: jede Rechnung ist im Netz durchgängig nachvollziehbar</li>
    <li><strong>Konformität</strong>: Sie erfüllen die luxemburgischen Pflichten (Gesetz vom 13. Dezember 2021)</li>
    <li><strong>Internationale Reichweite</strong>: Peppol ist inzwischen in über <strong>100 Ländern</strong> im Einsatz, mit Dutzenden nationaler Peppol Authorities</li>
</ul>

<h2>Häufige Fragen</h2>

<h3>Ist die Peppol-Rechnungsstellung im B2B Pflicht?</h3>

<p>In Luxemburg für inländisches B2B noch nicht. Das europäische Paket <strong>ViDA (VAT in the Digital Age)</strong>, 2025 angenommen, sieht die Pflicht zur elektronischen Rechnungsstellung für <strong>innergemeinschaftliche B2B-Umsätze</strong> ab dem <strong>1. Juli 2030</strong> vor. Für das inländische B2B ist die vollständige Harmonisierung bis 2035 vorgesehen. Siehe <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäische Kommission – ViDA</a>.</p>

<h3>Was kostet der Versand über Peppol?</h3>

<p>Die Erzeugung der Peppol-BIS-3.0-Datei ist in Ihrem faktur.lu-Abonnement enthalten, ohne Aufpreis je Rechnung. Das Peppol-Netz selbst stellt dem Absender nichts in Rechnung: etwaige Kosten hängen vom genutzten Zugangspunkt ab.</p>

<h3>Wie finde ich die Peppol-ID einer Verwaltung?</h3>

<p>Die Peppol Participant IDs der luxemburgischen Verwaltungen stehen im <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Sie lassen sich auch direkt in faktur.lu beim Anlegen des Kunden suchen.</p>

<h3>Welches Peppol-Schema gilt in Luxemburg?</h3>

<p>Das Standardschema ist <strong>9938</strong> (LU:VAT, auf Basis der MwSt.-Nummer). Vollständiges Format: <code>9938:LU########</code>. Nicht mit 0184 zu verwechseln, dem dänischen Schema.</p>

<h3>Welche Sanktionen drohen bei Nichteinhaltung?</h3>

<p>Die Verwaltung kann die Bearbeitung einer nicht konformen Papier- oder PDF-Rechnung verweigern und deren erneute Übermittlung über Peppol verlangen, was die Zahlung verzögert. Für die formalen Sanktionen im Einzelnen konsultieren Sie das Gesetz vom 13. Dezember 2021 oder Ihren Treuhänder.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Der ViDA-Zeitplan, die Schwellen und die Peppol Authorities entwickeln sich. Diese Seite wird regelmäßig aktualisiert — für Ihre Situation konsultieren Sie <a href="https://guichet.public.lu/" target="_blank" rel="noopener">Guichet.lu</a> und die offizielle Peppol-Dokumentation.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Gesetz vom 13. Dezember 2021 zur elektronischen B2G-Rechnungsstellung</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu – Übermittlung einer elektronischen Rechnung</a></li>
    <li><a href="https://mindigital.gouvernement.lu/fr/dossiers/2021/facturation-electronique.html" target="_blank" rel="noopener">Digitalisierungsministerium – Elektronische Rechnungsstellung</a></li>
    <li><a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">Peppol – EAS-Codeliste (Identifikationsschemata)</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäische Kommission – VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/factur-x-zugferd-europaeische-elektronische-rechnungsstellung" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">Rechnungssoftware wählen →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer LU-Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Bereit für die Peppol-Rechnungsstellung?</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt die konforme Peppol-BIS-Billing-3.0-Datei Ihrer Rechnungen, bereit zur Übermittlung an den öffentlichen Sektor. Legen Sie Ihr kostenloses Konto an und testen Sie es in wenigen Minuten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'rechnungsarchivierung-luxemburg-gesetzliche-dauer-format',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Die Archivierung von Rechnungen ist in Luxemburg gesetzlich vorgeschrieben: <strong>zehn Jahre</strong>. Doch der <em>Beginn</em> dieser Frist und der <em>Ort</em>, an dem die Archive liegen dürfen, sind genauer geregelt, als man denkt. Hier steht, was Artikel 65 des MwSt.-Gesetzes tatsächlich sagt.</p>

<h2>Gesetzliche Aufbewahrungsdauer</h2>

<h3>Rechnungen: zehn Jahre ab dem Ausstellungsdatum</h3>

<p><strong>Artikel 65 Absatz 4</strong> des <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">MwSt.-Gesetzes</a> ist eindeutig:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    „Diese Rechnungen und Rechnungskopien sind für einen Zeitraum von zehn Jahren <strong>ab ihrem Ausstellungsdatum</strong> aufzubewahren."
</blockquote>

<p>Eine am <strong>15. März 2026</strong> ausgestellte Rechnung ist somit bis zum <strong>15. März 2036</strong> aufzubewahren — und nicht bis Ende 2036. Die Regel gilt für ausgestellte wie für erhaltene Rechnungen.</p>

<h3>Bücher und sonstige Unterlagen</h3>

<p>Für die übrigen Unterlagen ändert sich der Fristbeginn:</p>

<ul>
    <li><strong>Handelsbücher</strong>: zehn Jahre ab ihrem <strong>Abschluss</strong></li>
    <li><strong>Sonstige Unterlagen</strong>: zehn Jahre ab ihrem <strong>Datum</strong></li>
    <li><strong>Register elektronischer Schnittstellen</strong> (Marktplätze, Plattformen): zehn Jahre ab dem <strong>31. Dezember des Jahres des Umsatzes</strong></li>
</ul>

<p>Hinzu kommt die allgemeine Pflicht des <strong>Handelsgesetzbuchs</strong> (Artikel 16), das ebenfalls zehn Jahre für Bücher und Geschäftskorrespondenz vorsieht.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Eine häufige Verwechslung</p>
    <p>Viele Ratgeber lassen die Rechnungsfrist ab dem Ende des Geschäftsjahres laufen. Das ist die Regel für <strong>Bücher</strong>, nicht für Rechnungen. In der Praxis führt eine Ausrichtung aller Löschungen auf das Geschäftsjahresende dazu, dass Sie etwas länger aufbewahren als nötig: vorsichtig, aber nicht die Regel.</p>
</div>

<h2>Wo dürfen Sie Ihre Archive speichern?</h2>

<p>Das ist die Frage, die sich bei jedem Onlinedienst stellt, und Artikel 65 beantwortet sie in Absatz 6.</p>

<h3>Der Grundsatz</h3>

<p>Sie <strong>bestimmen den Speicherort frei</strong>, unter einer Bedingung: der Verwaltung „<strong>ohne unangemessene Verzögerung, auf jedes Verlangen hin</strong>" sämtliche Rechnungen, Informationen, Bücher und Unterlagen zur Verfügung zu stellen.</p>

<h3>Die Grenzen</h3>

<ul>
    <li><strong>Länder ohne Amtshilfe</strong>: Eine Speicherung in einem Land oder Gebiet, mit dem kein Amtshilfeinstrument vergleichbarer Reichweite und kein elektronisches Zugriffsrecht besteht, ist untersagt</li>
    <li><strong>Papierarchive</strong>: Ein in Luxemburg ansässiger Steuerpflichtiger muss seine Rechnungen dort speichern, <strong>wenn die Speicherung nicht elektronisch</strong> mit vollständigem Onlinezugriff erfolgt. Konkret: Ihre Ordner bleiben in Luxemburg</li>
    <li><strong>Meldepflicht</strong>: Liegt der Speicherort <strong>außerhalb des luxemburgischen Hoheitsgebiets</strong>, müssen Sie ihn der Verwaltung melden — in der <strong>Jahreserklärung</strong> nach Artikel 64 Absatz 7</li>
    <li><strong>Speicherung in einem anderen Mitgliedstaat</strong>: Sie müssen den Bediensteten der Verwaltung ein Recht auf <strong>elektronischen Zugriff, Herunterladen und Nutzung</strong> dieser Rechnungen gewährleisten</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Zu prüfen, wenn Sie einen Onlinedienst nutzen</p>
    <p>Fragen Sie Ihren Anbieter, <strong>wo die Daten physisch gehostet werden</strong>. Liegen die Server außerhalb Luxemburgs, greift die Meldepflicht in Ihrer Jahreserklärung — auch bei einem europäischen Dienst. Eine einfache Formalität, aber leicht zu vergessen.</p>
</div>

<h2>Zulässige Archivierungsformate</h2>

<p>Artikel 65 Absatz 5 stellt die inhaltliche Anforderung: <strong>Echtheit der Herkunft</strong>, <strong>Unversehrtheit des Inhalts</strong> und <strong>Lesbarkeit</strong> müssen während des gesamten Aufbewahrungszeitraums gewährleistet sein.</p>

<h3>Papierarchivierung</h3>
<p>Papieroriginale sind trocken und zugänglich aufzubewahren, unverändert und — wie oben gesehen — auf luxemburgischem Hoheitsgebiet.</p>

<h3>Digitale Archivierung</h3>
<p>Die elektronische Speicherung ist gültig, „sofern die Daten, die die Echtheit der Herkunft und die Unversehrtheit des Inhalts gewährleisten, ebenfalls elektronisch gespeichert werden". In der Praxis:</p>

<ul>
    <li>Das Format muss die <strong>Unversehrtheit</strong> des Dokuments sichern</li>
    <li>Das Dokument muss während der gesamten Dauer <strong>lesbar</strong> bleiben</li>
    <li>Das Format <strong>PDF/A</strong> (ISO 19005) wird für die Langzeitlesbarkeit empfohlen</li>
    <li>Ein <strong>digitaler Fingerabdruck</strong> (Hash) belegt, dass das Dokument nicht verändert wurde</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitalisierung und Beweiskraft</p>
    <p>Damit eine elektronische Kopie dieselbe <strong>Beweiskraft</strong> wie das Papieroriginal hat, knüpft das <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vom 25. Juli 2015</a> diese Vermutung an die Einschaltung eines zertifizierten <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (Dienstleister für Dematerialisierung oder Aufbewahrung). Ohne PSDC zu digitalisieren bleibt möglich, doch die Kopie genießt diese Vermutung nicht: im Streitfall müssen Sie ihre Übereinstimmung selbst nachweisen.</p>
</div>

<h2>Warum das Format PDF/A?</h2>

<p><strong>PDF/A</strong> ist eine für die Langzeitarchivierung entwickelte ISO-Norm. Anders als ein Standard-PDF:</p>

<ul>
    <li>Es <strong>bettet alle verwendeten Schriften ein</strong> (keine externe Abhängigkeit)</li>
    <li>Es <strong>verbietet JavaScript</strong> und Multimedia-Elemente</li>
    <li>Es soll sicherstellen, dass das Dokument in <strong>zehn, zwanzig oder fünfzig Jahren lesbar</strong> bleibt</li>
    <li>Es ist bei europäischen Verwaltungen <strong>weithin anerkannt</strong></li>
</ul>

<h2>Rechnungen mit faktur.lu archivieren</h2>

<p>faktur.lu bietet eine eigene Archivierung, verfügbar in den Tarifen, die diese Funktion enthalten:</p>

<ol>
    <li>Sie archivieren eine <strong>finalisierte</strong> Rechnung — einzeln oder im Stapel</li>
    <li>Das Dokument wird standardmäßig nach <strong>PDF/A-1b</strong> konvertiert; <strong>PDF/A-3b</strong> steht zur Verfügung, wenn Anhänge eingebettet werden müssen</li>
    <li>Ein <strong>SHA-256-Fingerabdruck</strong> wird berechnet und gespeichert, sodass sich die Unversehrtheit des Archivs später prüfen lässt</li>
    <li>Eine <strong>Zehnjahresfrist</strong> wird mit dem Archiv hinterlegt</li>
    <li>Sie können Ihre Archive jederzeit herunterladen</li>
</ol>

<p class="text-sm text-slate-500"><em>Technischer Hinweis: Die PDF/A-Konvertierung stützt sich serverseitig auf Ghostscript. Ist das Werkzeug nicht verfügbar, wird das Dokument als Standard-PDF mit seinem Fingerabdruck aufbewahrt — das tatsächlich erzielte Format wird mit dem Archiv gespeichert, Sie wissen also immer, was Sie haben.</em></p>

<h2>Risiken bei Nichteinhaltung</h2>

<p>Bei einer Steuerprüfung kann das Fehlen von Rechnungen oder eine nicht konforme Archivierung Folgendes nach sich ziehen:</p>

<ul>
    <li>Die <strong>Verwerfung des Vorsteuerabzugs</strong> für die fehlenden Rechnungen</li>
    <li><strong>Verwaltungsbußgelder</strong> (250 € bis 10 000 € je Verstoß, Art. 77 LIVA) und bis zu <strong>25 000 € pro Verzugstag</strong> nach Verwarnung, wenn die Vorlage der Unterlagen verweigert wird</li>
    <li>Eine <strong>Schätzung von Amts wegen</strong> durch die Verwaltung</li>
</ul>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 65 (Aufbewahrung und Speicherung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten der Unternehmen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vom 25. Juli 2015 über die elektronische Archivierung</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS – Zertifizierte PSDC-Dienstleister</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Datei →</a></li><li><a href="/de/blog/steuerpruefung-luxemburg-vorbereiten" class="text-primary-500 hover:text-primary-600 text-sm">Steuerprüfung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A-Archivierung mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archivieren Sie Ihre finalisierten Rechnungen als PDF/A mit SHA-256-Fingerabdruck und Zehnjahresfrist, einzeln oder im Stapel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Die Aufbewahrungsregeln können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'rechnungssoftware-luxemburg-richtige-waehlen-vergleich',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurz gefasst</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 Kriterien</strong> für die Wahl: luxemburgische Konformität (Pflichtangaben Art. 63, Nummerierung, 4 MwSt.-Sätze, Freigrenze 50 000 €), vollständiger Zyklus (Angebote, Gutschriften, wiederkehrende Rechnungen), Einfachheit, Komplettpreis, Sicherheit (EU-Hosting, 2FA), Anbindung an den Treuhänder.</li><li>Die <strong>FAIA</strong> wird nicht von allen verlangt: vier kumulative Bedingungen gelten. Sie bleibt gleichwohl das Kriterium, das Sie schützt, <strong>wenn Sie wachsen</strong>.</li><li>Vorsicht bei Lockpreisen: Vergleichen Sie, was <strong>tatsächlich enthalten</strong> ist, nicht nur den angezeigten Tarif.</li></ul></div>
<p class="lead">Eine Rechnungssoftware als Selbstständiger oder KMU in Luxemburg zu wählen heißt nicht, ein weiteres „Rechnungstool" zu wählen. Es heißt, ein Werkzeug zu wählen, das Sie <strong>mit den ganz spezifischen Regeln des Großherzogtums konform macht</strong> (Pflichtangaben, MwSt., Nummerierung, Archivierung). Hier sind die 6 Kriterien, die wirklich zählen, und wie Sie sie prüfen.</p>

<h2>Warum Luxemburg den Unterschied macht</h2>
<p>Eine „generische" Rechnungssoftware (französisch, belgisch, international) erfüllt oft die Grundanforderungen, ignoriert aber die luxemburgischen Pflichten. Die Registrierungs-, Domänen- und MwSt.-Verwaltung (AED) kann bei einer Prüfung exakte Rechtsvermerke, eine einwandfreie Nummerierung und — unter bestimmten Bedingungen — eine Audit-Datei in genauem Format verlangen. Die richtige Software muss also <strong>für</strong> Luxemburg gedacht sein, nicht bloß „in" Luxemburg verfügbar.</p>

<h2>Kriterium 1: die luxemburgische Konformität</h2>
<p>Das ist das zentrale Kriterium. Prüfen Sie genau:</p>
<ul>
    <li><strong>Pflichtangaben (Artikel 63 LIVA)</strong>: Kontaktdaten, MwSt.-Nummer, eindeutige fortlaufende Nummer, Sätze und Beträge — automatisch und korrekt angebracht.</li>
    <li><strong>Lückenlose fortlaufende Nummerierung</strong>: ohne Lücke und ohne Dublette, gesperrt sobald die Rechnung finalisiert ist.</li>
    <li><strong>Die 4 luxemburgischen MwSt.-Sätze</strong>: 17 % (normal), 14 % (mittel), 8 % (ermäßigt) und 3 % (stark ermäßigt), je nach Szenario angewandt.</li>
    <li><strong>Freigrenzenregelung</strong>: Handhabung der Schwelle von <strong>50 000 €</strong> (Art. 57bis LIVA) mit dem passenden Vermerk und einer Warnung bei Annäherung.</li>
    <li><strong>Internationale MwSt.-Szenarien</strong>: innergemeinschaftlicher Reverse Charge, Ausfuhr, Freigrenze — mit automatisch gesetztem Rechtsvermerk.</li>
    <li><strong>Zehnjährige Archivierung</strong> in einem auf Langzeitlesbarkeit ausgelegten Format (PDF/A) mit Integritätsnachweis.</li>
    <li><strong>FAIA-2.01-Export</strong>: siehe unten, das Kriterium ist differenzierter, als oft behauptet.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Die FAIA betrifft nicht jeden</p>
    <p>Viele Vergleiche — bisher auch diese Seite — stellen den FAIA-Export als K.-o.-Kriterium dar. Das ist übertrieben. Nach der FAQ der AED setzt die Pflicht <strong>vier gleichzeitig erfüllte Bedingungen</strong> voraus: dem normierten Kontenplan unterliegen, keine vereinfachte Regelung in Anspruch nehmen, über 112 000 € Umsatz erzielen und etwa 500 Buchungstransaktionen jährlich überschreiten. Viele Selbstständige fallen heute nicht darunter.</p>
    <p class="mt-2">Das macht das Kriterium nicht wertlos: Es wird entscheidend <strong>an dem Tag, an dem Ihr Geschäft diese Schwellen überschreitet</strong>, und ein Werkzeugwechsel zu diesem Zeitpunkt kostet weit mehr, als ihn vorausgesehen zu haben. Siehe unseren <a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden" class="text-primary-500 hover:text-primary-600">vollständigen FAIA-Leitfaden</a>.</p>
</div>

<h2>Kriterium 2: die wesentlichen Funktionen</h2>
<p>Über die Rechnung hinaus braucht Ihr Geschäft einen vollständigen Zyklus: <strong>Angebote</strong> (in Rechnungen umwandelbar), <strong>Gutschriften</strong>, <strong>Anzahlungsrechnungen</strong> (mit korrekter MwSt.-Entstehung), <strong>wiederkehrende Rechnungen</strong>, Verfolgung der <strong>Ausgaben</strong> und der <strong>Vorsteuer</strong>, Mehrwährungsfähigkeit und <strong>Zahlungserinnerungen</strong>. Je mehr der Zyklus abgedeckt ist, desto weniger jonglieren Sie zwischen Werkzeugen.</p>

<h2>Kriterium 3: die Einfachheit</h2>
<p>Sie sind kein Buchhalter: Die Software soll Ihnen Zeit sparen, nicht kosten. Testen Sie die <strong>Zeit bis zur ersten konformen Rechnung</strong>, die Klarheit der Oberfläche und die Verfügbarkeit auf Deutsch (und idealerweise in der Sprache Ihrer Kunden). Ein gutes Werkzeug nutzt man ohne Schulung.</p>

<h2>Kriterium 4: ein zu Ihrem Geschäft passender Preis</h2>
<p>Misstrauen Sie Lockpreisen: Schauen Sie, was <strong>tatsächlich enthalten</strong> ist. Ein „günstiger" Tarif, der Buchhaltungsexporte oder die Rechnungsanzahl extra berechnet, kann teurer sein als ein Komplettpaket. Prüfen Sie ein kostenloses Einstiegsangebot und die Transparenz der Stufen.</p>

<h2>Kriterium 5: Sicherheit und DSGVO-Konformität</h2>
<p>Ihre Kunden- und Finanzdaten sind sensibel. Verlangen Sie <strong>europäisches Hosting</strong> (DSGVO), Verschlüsselung, Zwei-Faktor-Authentifizierung, <strong>regelmäßige Sicherungen</strong> und eine strikte Datentrennung zwischen Konten. Hosting außerhalb der EU ist ein Warnsignal — und bei Ihren Rechnungsarchiven verpflichtet es Sie, den Speicherort der Verwaltung zu melden.</p>

<h2>Kriterium 6: die Anbindung an Ihren Treuhänder</h2>
<p>Der eigentliche Zeit- und Geldgewinn entsteht bei Ihrem Buchhalter. Eine Software, die saubere, integrierbare Daten exportiert (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — oder ein <strong>eigenes Portal</strong>, über das der Treuhänder Ihre Daten lesend abruft — erspart ihm die Neuerfassung und senkt damit Ihr Honorar.</p>

<h2>Und die elektronische Rechnungsstellung?</h2>
<p>Zwei getrennte Termine, die oft verwechselt werden:</p>
<ul>
    <li><strong>B2G (luxemburgischer öffentlicher Sektor)</strong>: Die elektronische Rechnungsstellung ist bereits Pflicht, um Staat und Gemeinden zu fakturieren. Wer mit dem öffentlichen Sektor arbeitet, braucht sie sofort.</li>
    <li><strong>ViDA (europäische Reform)</strong>: Der Zeitplan ist gestaffelt — Erweiterung des OSS-Schalters im Januar 2027, verpflichtender Reverse Charge und Plattformregeln im Juli 2028, dann verpflichtende elektronische Rechnungsstellung und digitale Meldung für innergemeinschaftliche B2B-Umsätze zum 1. Juli 2030. Zuerst kommen <strong>2027 und 2028</strong>, nicht 2030.</li>
</ul>
<p>Fragen Sie Ihren Anbieter also nicht „machen Sie Peppol?", sondern „<strong>zu welchem Termin und für welchen Umfang</strong>?". Ein datiertes Vorhaben ist mehr wert als ein Häkchen.</p>

<h2>Schnelle Vergleichstabelle</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Kriterium</th><th class="text-left p-2 bg-slate-100">Was zu verlangen ist</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU-Konformität</td><td class="p-2 border-b">Pflichtangaben Art. 63, Nummerierung, 4 MwSt.-Sätze, Freigrenze 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Vollständiger Zyklus</td><td class="p-2 border-b">Angebote, Gutschriften, Anzahlungen, wiederkehrende Rechnungen, Ausgaben, Mahnungen</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Verfügbar an dem Tag, an dem Sie die Schwellen überschreiten</td></tr>
        <tr><td class="p-2 border-b">E-Rechnung</td><td class="p-2 border-b">B2G heute; datierte Roadmap für ViDA</td></tr>
        <tr><td class="p-2 border-b">Archivierung</td><td class="p-2 border-b">PDF/A, 10 Jahre, Integritätsnachweis</td></tr>
        <tr><td class="p-2 border-b">Sicherheit</td><td class="p-2 border-b">EU-Hosting, 2FA, Sicherungen</td></tr>
        <tr><td class="p-2 border-b">Treuhänder</td><td class="p-2 border-b">Exporte FAIA/Sage/CSV, Buchhalterportal</td></tr>
        <tr><td class="p-2 border-b">Preis</td><td class="p-2 border-b">Alles inklusive, kostenloses Angebot, Transparenz</td></tr>
    </tbody>
</table>

<h2>Excel oder Software: lohnt sich der Schritt?</h2>
<p>Excel wirkt kostenlos, garantiert aber weder die lückenlose Nummerierung noch die Pflichtangaben, und es erzeugt keine FAIA an dem Tag, an dem sie verlangt wird. Sobald Sie mehr als ein paar Rechnungen im Monat stellen, spart eine konforme Software Zeit und schafft Sicherheit. Wir vertiefen den Vergleich in unserem Artikel <a href="/de/blog/excel-vs-rechnungssoftware-warum-wechseln">Excel gegen Rechnungssoftware</a>.</p>

<h2>Wo faktur.lu bei diesen Kriterien steht</h2>
<p>faktur.lu wurde <strong>speziell für Luxemburg</strong> entwickelt. Was heute verfügbar ist:</p>
<ul>
    <li>Vermerke des Artikels 63 und fortlaufende Nummerierung automatisch, die 4 MwSt.-Sätze, innergemeinschaftliche und Ausfuhr-Szenarien</li>
    <li>Handhabung der Freigrenze von 50 000 € mit Warnung bei Annäherung</li>
    <li><strong>FAIA-2.01</strong>-Export sowie Buchhaltungsexporte <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Treuhänderportal</strong> mit Lesezugriff</li>
    <li><strong>PDF/A</strong>-Archivierung mit SHA-256-Fingerabdruck und Zehnjahresfrist</li>
    <li>Europäisches Hosting, Zwei-Faktor-Authentifizierung, Datentrennung je Konto</li>
    <li>Oberfläche in <strong>5 Sprachen</strong> (FR, DE, EN, LB, PT) und <strong>kostenloses Einstiegsangebot</strong></li>
</ul>
<p>Was <strong>in Arbeit</strong> ist: die Produktivsetzung des Peppol-Versands für alle Konten und danach der Empfang von Lieferantenrechnungen. Das Format steht technisch bereit, der Rollout noch nicht — wir schreiben es lieber, als ein Häkchen zu setzen.</p>

<h2>FAQ — Rechnungssoftware in Luxemburg wählen</h2>
<h3>Ist eine Rechnungssoftware in Luxemburg Pflicht?</h3>
<p>Nein, aber Ihre Rechnungen müssen die gesetzlichen Vorgaben erfüllen (Vermerke, Nummerierung, MwSt., Archivierung). Eine konforme Software ist der einfachste Weg, all das sicherzustellen.</p>
<h3>Wird die FAIA-Datei von allen verlangt?</h3>
<p>Nein. Die FAQ der AED nennt vier kumulative Bedingungen: normierter Kontenplan, keine vereinfachte Regelung, Umsatz über 112 000 € und etwa 500 Buchungstransaktionen jährlich. Fehlt eine, sind Sie nicht betroffen — das Werkzeug sollte Ihrem Wachstum aber folgen können.</p>
<h3>Darf man in Luxemburg mit Excel fakturieren?</h3>
<p>Förmlich verboten ist es nicht, doch Excel garantiert weder die lückenlose Nummerierung noch die Pflichtangaben. Das Prüfungsrisiko ist real, sobald das Geschäft wächst.</p>
<h3>Was kostet eine Rechnungssoftware?</h3>
<p>Von kostenlos (eingeschränkte Angebote) bis etwa 15-30 €/Monat für eine vollständige Lösung. Der richtige Reflex: vergleichen, was <strong>enthalten</strong> ist, statt nur den Einstiegspreis.</p>
<h3>Welche Software bei MwSt.-Freigrenze?</h3>
<p>Wählen Sie ein Werkzeug, das die Freigrenzenregelung beherrscht (Schwelle 50 000 €), den passenden Vermerk automatisch setzt und Sie <strong>vor Erreichen der Schwelle warnt</strong>. Prüfen Sie auch die <strong>Restpflichten</strong>: sobald Sie Leistungen an EU-Unternehmer fakturieren, bleiben eine vereinfachte Jahreserklärung und Zusammenfassende Meldungen verpflichtend.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Eine für Luxemburg gedachte Software, kostenlos zum Start</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt konforme Rechnungen (Vermerke, MwSt., Nummerierung) und die Exporte für Ihren Treuhänder — in wenigen Minuten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>
ARTICLE_HTML,
            ],
            [
                'slug' => 'steuerpruefung-luxemburg-vorbereiten',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Eine Prüfungsankündigung der <strong>Registrierungs-, Domänen- und MwSt.-Verwaltung (AED)</strong> beunruhigt jeden Selbstständigen und KMU-Leiter in Luxemburg. Mit guter Vorbereitung ist eine Prüfung jedoch in wenigen Stunden erledigt. Hier erfahren Sie, wie Sie sich vorbereiten — und eine Verjährungsregel, die fast alle falsch wiedergeben.</p>

<h2>Wer ist von einer AED-Prüfung betroffen?</h2>

<p>Jedes in Luxemburg MwSt.-registrierte Unternehmen kann geprüft werden. In der Praxis zielt die Verwaltung auf:</p>

<ul>
    <li><strong>Schnell wachsende Unternehmen</strong> mit ungewöhnlich schwankendem Umsatz</li>
    <li><strong>Risikobranchen</strong>, die die AED identifiziert (Handel, Gastronomie, Bau, Beratung)</li>
    <li><strong>Auffällige MwSt.-Erklärungen</strong> (wiederkehrendes MwSt.-Guthaben, bedeutende innergemeinschaftliche Umsätze)</li>
    <li><strong>Stichprobenprüfungen</strong> nach statistischen Kriterien</li>
</ul>

<h2>Wie weit zurück darf die AED gehen?</h2>

<p>Das ist der am häufigsten missverstandene Punkt, und der Fehler findet sich überall — bis vor Kurzem auch auf dieser Seite. <strong>Artikel 81 des MwSt.-Gesetzes</strong> ist jedoch eindeutig:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Die Klage des Fiskus auf Zahlung der Steuer und der Geldbußen verjährt in <strong>fünf Jahren</strong> ab dem 31. Dezember des Jahres, in dem der einzuziehende Betrag fällig geworden ist."</p>
</blockquote>

<p>Bei der <strong>MwSt.</strong> beträgt die Verjährung also <strong>fünf Jahre</strong>. Der Ausdruck „zehn Jahre" kommt im gesamten MwSt.-Gesetz nur für die <em>Aufbewahrung</em> von Unterlagen vor — nie für die Verjährung.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Verwechseln Sie MwSt. nicht mit direkten Steuern</p>
    <p class="text-red-700">Die auf <strong>zehn Jahre</strong> verlängerte Verjährung bei Nichterklärung oder unrichtiger Erklärung existiert tatsächlich — aber für die <strong>direkten Steuern</strong> (§144 AO und Art. 10 des Gesetzes vom 27. November 1933), verwaltet von der ACD. Sie gilt <strong>nicht</strong> für die MwSt., die der AED untersteht. Das Gegenteil zu glauben kann dazu führen, dass Sie eine Nachforderung für bereits verjährte Jahre akzeptieren.</p>
</div>

<p>Zwei Einschränkungen gleichwohl. Die Verjährung kann <strong>unterbrochen</strong> werden (Art. 2244 ff. Zivilgesetzbuch oder Verzicht des Steuerpflichtigen): Dann läuft eine neue Frist, die am Ende des vierten Jahres nach der letzten Unterbrechungshandlung eintritt. Und die Frist läuft ab dem <strong>31. Dezember</strong> des Fälligkeitsjahres, nicht ab dem Datum des Umsatzes.</p>

<p>Schließlich sind Verjährung und Archivierung zweierlei: Sie müssen Ihre Rechnungen <strong>zehn Jahre</strong> aufbewahren (Art. 65 LIVA und Art. 16 Handelsgesetzbuch), unabhängig von der fünfjährigen Verjährung.</p>

<h2>Die 3 Arten der AED-Prüfung</h2>

<h3>1. Prüfung nach Aktenlage</h3>

<p>Die AED fordert Unterlagen per Post oder elektronisch an. Kein Besuch vor Ort. Die häufigste und leichteste Form.</p>

<h3>2. Prüfung vor Ort</h3>

<p>Ein Bediensteter kommt in Ihre Räume, um Bücher, Rechnungen und Ausgabenbelege zu prüfen. Angekündigt per Brief — das MwSt.-Gesetz sieht keine Ankündigungsfrist vor, die AED vereinbart den Termin im Einzelfall.</p>

<h3>3. Unangekündigte Prüfung</h3>

<p>Selten, schwerem Betrugsverdacht vorbehalten. Der Bedienstete erscheint ohne Vorankündigung. Sie können die Anwesenheit Ihres Treuhänders verlangen.</p>

<h2>Unterlagen, die die AED anfordern kann</h2>

<ul>
    <li><strong>Alle ausgestellten und erhaltenen Rechnungen</strong> des geprüften Zeitraums, mit den Pflichtangaben nach Art. 63 LIVA</li>
    <li><strong>Die FAIA-Datei</strong> im Format 2.01 — für die dazu verpflichteten Steuerpflichtigen, siehe unten</li>
    <li><strong>Das Einnahmen-Ausgaben-Buch</strong> oder die vollständige Buchhaltung je nach Regime</li>
    <li><strong>Die eingereichten MwSt.-Erklärungen</strong>, periodische und jährliche</li>
    <li><strong>Die geschäftlichen Kontoauszüge</strong> des Zeitraums</li>
    <li><strong>Die Belege der abgezogenen Ausgaben</strong> (Spesen, Lieferantenrechnungen)</li>
    <li><strong>Wesentliche Verträge</strong> mit Kunden und Lieferanten</li>
    <li><strong>Den Nachweis des Reverse Charge</strong> beim innergemeinschaftlichen B2B: über VIES geprüfte MwSt.-Nummern (Art. 17 LIVA, Art. 196 der Richtlinie)</li>
</ul>

<h2>FAIA: der kritische Punkt — aber nicht für jeden</h2>

<p>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei, abgeleitet vom SAF-T-Standard, die den Unternehmenskopf, die Ausgangsrechnungen des Zeitraums, die zugehörigen Buchungen und die Kontrollsummen bündelt. Das geltende Schema ist die Version <strong>2.01</strong>, deren letzte von der AED veröffentlichte Aktualisierung aus dem Juli 2020 stammt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Vier kumulative Bedingungen</p>
    <p>Die FAIA betrifft nicht alle Steuerpflichtigen. Nach der FAQ der AED setzt die Pflicht voraus, <strong>gleichzeitig</strong>: dem normierten Kontenplan (PCN) zu unterliegen, keine vereinfachte Regelung in Anspruch zu nehmen, über 112 000 € Umsatz zu erzielen und etwa <strong>500 Buchungstransaktionen</strong> jährlich zu überschreiten. Eine Transaktion ist eine vollständige Buchungskette, keine Rechnung. Fehlt eine einzige Bedingung, sind Sie nicht betroffen — siehe unseren <a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden">vollständigen FAIA-Leitfaden</a>.</p>
</div>

<p>Sind Sie verpflichtet und fakturieren mit einer Tabellenkalkulation, müssen Sie die Datei von Hand rekonstruieren: mehrere Arbeitstage und ein hohes Fehlerrisiko.</p>

<h2>Wie eine Prüfung vor Ort abläuft</h2>

<ol>
    <li><strong>Schriftliche Ankündigung</strong> der AED, meist per Einschreiben</li>
    <li><strong>Eintreffen der Bediensteten</strong> mit Prüfungsauftrag</li>
    <li><strong>Anforderung der Unterlagen</strong> nach vorbereiteter Liste</li>
    <li><strong>Prüfung der Buchungen</strong> und Abgleich mit den MwSt.-Erklärungen</li>
    <li><strong>Gespräch</strong> über ungewöhnliche Vorgänge oder festgestellte Abweichungen</li>
    <li><strong>Mitteilung der Feststellungen</strong> per Brief</li>
</ol>

<h2>Die 5 teuersten Fehler</h2>

<ol>
    <li><strong>Nicht fortlaufende Rechnungsnummerierung</strong> (Art. 63 LIVA, Ziffer 3°): Lücken oder Dubletten begründen die Vermutung nicht erklärter Umsätze</li>
    <li><strong>Fehlende Pflichtangaben</strong> auf den Rechnungen (Art. 63 LIVA)</li>
    <li><strong>Nicht dokumentierter innergemeinschaftlicher Reverse Charge</strong>: ohne VIES-Prüfung der Kundennummer kann die AED den Umsatz als in Luxemburg steuerpflichtig umqualifizieren</li>
    <li><strong>Fehlende oder unleserliche Ausgabenbelege</strong></li>
    <li><strong>Unstimmigkeiten zwischen MwSt.-Erklärungen und Rechnungen</strong>: schon eine kleine Abweichung löst eine vertiefte Prüfung aus</li>
</ol>

<h2>Welche Sanktionen bei Fehlern?</h2>

<ul>
    <li><strong>Formale Verstöße</strong> (fehlende Angabe, FAIA nicht geliefert, verspätete Erklärung): Verwaltungsbußgeld von <strong>250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
    <li><strong>Verstoß, der dem Fiskus Einnahmen entzogen hat</strong>: Geldbuße von <strong>10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze, und bei einem größeren Streit weit schwerer als die vorige</li>
    <li><strong>Weigerung, Rechnungen und Buchhaltungsunterlagen vorzulegen</strong>: bis zu <strong>25 000 € pro Verzugstag</strong>, nach Verwarnung</li>
    <li><strong>Schwere Steuerhinterziehung oder Steuerbetrug</strong>: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt., Freiheitsstrafe von einem Monat bis fünf Jahren, Aberkennung der bürgerlichen Rechte für 5 bis 10 Jahre (Art. 80 LIVA)</li>
</ul>

<h2>Die Prüfung in 5 Schritten vorbereiten</h2>

<ol>
    <li><strong>Die FAIA vorbereiten</strong> für den verlangten Zeitraum, sofern Sie verpflichtet sind</li>
    <li><strong>Alle ausgestellten Rechnungen exportieren</strong>, idealerweise als PDF/A</li>
    <li><strong>Die Stimmigkeit prüfen</strong> mit den eingereichten MwSt.-Erklärungen</li>
    <li><strong>Pro Monat eine Akte bilden</strong>: Rechnungen, Kontoauszüge, zugehörige Erklärung</li>
    <li><strong>Ihren Treuhänder informieren</strong> und um Anwesenheit während der Prüfung bitten</li>
</ol>

<h2>Die Feststellungen anfechten</h2>

<p>Sind Sie nicht einverstanden, ist das Verfahren geregelt:</p>

<ol>
    <li><strong>Schriftlicher, begründeter Einspruch</strong> bei der Verwaltung, innerhalb von <strong>drei Monaten</strong> ab der Mitteilung</li>
    <li>Bei Zurückweisung wird <strong>der Direktor der Verwaltung befasst</strong>, und seine Entscheidung tritt an die Stelle der vorherigen</li>
    <li><strong>Klage</strong> vor dem <strong>Bezirksgericht Luxemburg in Zivilsachen</strong> — die MwSt. gehört zur ordentlichen Gerichtsbarkeit, nicht zur Verwaltungsgerichtsbarkeit wie die direkten Steuern. Die Klageschrift ist binnen <strong>drei Monaten</strong> nach der Direktorialentscheidung zuzustellen, <strong>bei sonstigem Ausschluss</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Das Schweigen der Verwaltung blockiert Sie nicht</p>
    <p>Ergeht binnen <strong>sechs Monaten</strong> nach Ihrem Einspruch keine Entscheidung, dürfen Sie ihn als zurückgewiesen betrachten und unmittelbar Klage erheben. In diesem Fall läuft die Dreimonatsfrist nicht — das Warten führt also nicht zum Ausschluss.</p>
</div>

<h2>Wie faktur.lu Sie vorbereitet</h2>

<ul>
    <li>Automatische fortlaufende Nummerierung nach <strong>Artikel 63 LIVA</strong></li>
    <li>Pflichtangaben auf jeder Rechnung erzeugt</li>
    <li>VIES-Prüfung für innergemeinschaftliche B2B-Umsätze</li>
    <li><strong>FAIA-2.01</strong>-Export für jeden Zeitraum</li>
    <li><strong>PDF/A</strong>-Archivierung mit Integritätsnachweis</li>
    <li>Treuhänderportal mit Lesezugriff, ohne E-Mail-Pingpong</li>
</ul>

<h2>Häufige Fragen</h2>

<h3>Wie lange dauert eine Prüfung?</h3>
<p>Nach Aktenlage: zwei bis vier Wochen, je nachdem, wie schnell Sie die Unterlagen senden. Vor Ort: meist ein bis drei Tage im Unternehmen, danach mehrere Wochen Auswertung bei der AED.</p>

<h3>Kann ich eine Prüfung verweigern?</h3>
<p>Nein. Die Weigerung wird sanktioniert und begründet die Vermutung der Bösgläubigkeit. Sie können jedoch aus berechtigtem Grund eine Verschiebung beantragen und die Anwesenheit Ihres Treuhänders verlangen.</p>

<h3>Muss ich trotzdem zehn Jahre archivieren, wenn die Verjährung fünf Jahre beträgt?</h3>
<p>Ja. Es sind zwei voneinander unabhängige Pflichten: Die zehnjährige Aufbewahrung folgt aus Art. 65 LIVA und Art. 16 Handelsgesetzbuch, nicht aus der Verjährungsfrist.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Verfahren, Sanktionen und Fristen können sich ändern. Diese Seite wird regelmäßig aktualisiert — für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">MwSt.-Sanktionen und Rechtsbehelfe</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Anwendungsbereich)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<h2>Weiterführend</h2>
<ul>
    <li><a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden">FAIA Luxemburg: alles zur informatisierten Audit-Datei</a></li>
    <li><a href="/de/blog/pflichtangaben-rechnung-luxemburg">Pflichtangaben auf einer luxemburgischen Rechnung</a></li>
    <li><a href="/de/blog/rechnungsarchivierung-luxemburg-gesetzliche-dauer-format">Archivierung von Rechnungen: Dauer und Format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereiten Sie Ihre nächste Prüfung schon heute vor</h3>
    <p class="text-primary-800 mb-4">Automatische Nummerierung, FAIA 2.01 mit einem Klick, konforme Angaben, PDF/A-Archivierung. Die Prüfung wird zur Formsache.</p>
    <a href="/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos starten</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'zahlungsfristen-luxemburg-rechtlicher-rahmen-2026',
                'locale' => 'de',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">In Luxemburg sind die Zahlungsfristen gesetzlich geregelt. Ob Sie einem Unternehmen, einer Verwaltung oder einer Privatperson eine Rechnung stellen — hier sind die Regeln, die Ihre Liquidität schützen.</p>

<h2>Gesetzliche Zahlungsfristen</h2>

<h3>B2B-Transaktionen (zwischen Unternehmen)</h3>

<p>Das <strong>geänderte Gesetz vom 18. April 2004</strong> über Zahlungsfristen und Verzugszinsen legt Folgendes fest:</p>

<ul>
    <li><strong>Standardfrist</strong>: 30 Tage ab Eingang der Rechnung</li>
    <li><strong>Vertragliche Höchstfrist</strong>: 60 Tage</li>
    <li>Die Parteien können eine Frist von <strong>über 60 Tagen</strong> nur vereinbaren, wenn dies keinen <strong>offensichtlichen Missbrauch</strong> gegenüber dem Gläubiger darstellt</li>
</ul>

<p>Dieses Gesetz wurde durch das <strong>Gesetz vom 29. März 2013</strong> geändert, das die Richtlinie 2011/7/EU umsetzt. Erst diese Revision hat den Aufschlag auf 8 Punkte angehoben und die Pauschale von 40 EUR eingeführt: Artikel, die nur den Text von 2004 zitieren, beschreiben eine überholte Regelung.</p>

<h3>B2G-Transaktionen (mit dem öffentlichen Sektor)</h3>

<p>Für Behörden gelten strengere Fristen:</p>

<ul>
    <li><strong>Höchstfrist</strong>: 30 Tage ab Eingang der Rechnung</li>
    <li>Diese Frist kann auf <strong>höchstens 60 Tage</strong> verlängert werden, wenn dies ausdrücklich vertraglich vorgesehen und durch die Natur des Vertrags objektiv gerechtfertigt ist</li>
    <li>Das Abnahme- oder Überprüfungsverfahren darf <strong>30 Tage</strong> nicht überschreiten</li>
</ul>

<h2>Ab wann läuft die Frist?</h2>

<p>Die Zahlungsfrist beginnt zu laufen ab:</p>

<ul>
    <li>Dem <strong>Eingangsdatum der Rechnung</strong> beim Schuldner</li>
    <li>Oder dem <strong>Empfangsdatum der Waren/Dienstleistungen</strong>, wenn das Rechnungsdatum ungewiss ist oder die Rechnung vorher versandt wurde</li>
    <li>Oder dem <strong>Datum der Abnahme oder Überprüfung</strong>, wenn ein solches Verfahren vertraglich oder gesetzlich vorgesehen ist</li>
</ul>

<p><strong>Tipp:</strong> Geben Sie das <strong>Fälligkeitsdatum</strong> stets deutlich auf Ihrer Rechnung an. Mit faktur.lu wird es automatisch berechnet (standardmäßig 30 Tage, anpassbar).</p>

<h2>Verzugszinsen</h2>

<p>Bei Verzug sind Zinsen <strong>automatisch</strong> geschuldet, ohne dass eine Mahnung erforderlich wäre. Der Satz ist jedoch <strong>nicht derselbe, je nachdem wer Ihr Kunde ist</strong> — das ist die häufigste Verwechslung.</p>

<h3>Ihr Kunde ist ein Unternehmer</h3>

<ul>
    <li><strong>Formel</strong>: der zu Beginn jedes Halbjahres im Mémorial B veröffentlichte EZB-Referenzsatz, <strong>zuzüglich 8 Prozentpunkte</strong></li>
    <li><strong>1. Halbjahr 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>Der Satz wird <strong>halbjährlich angepasst</strong>: prüfen Sie den geltenden Satz, bevor Sie eine Forderung beziffern</li>
    <li>Die Zinsen laufen ab dem Tag nach der Fälligkeit</li>
</ul>

<h3>Ihr Kunde ist eine Privatperson</h3>

<p>Die Regelung für Handelsgeschäfte gilt nicht. Maßgeblich ist der <strong>gesetzliche Zinssatz</strong>, der jährlich festgelegt wird: <strong>3,75 % für 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Der Unterschied ist erheblich</p>
    <p>10,15 % gegenüber 3,75 %: Wer einem Verbraucher den Handelssatz berechnet, fordert fast das Dreifache des Geschuldeten. Die Forderung wird angreifbar, und Ihre Glaubwürdigkeit leidet genau dann, wenn Sie ernst genommen werden müssen.</p>
</div>

<h2>Pauschale Beitreibungsentschädigung</h2>

<p>Zusätzlich zu den Verzugszinsen hat der Gläubiger Anspruch auf eine <strong>Pauschale von 40 EUR</strong> für Beitreibungskosten, geschuldet <strong>je unbezahlter Rechnung</strong>, automatisch und ohne Kostennachweis.</p>

<p>Übersteigen die tatsächlichen Beitreibungskosten 40 EUR, kann der Gläubiger den effektiven Betrag gegen Vorlage von Belegen verlangen.</p>

<p>Auch diese Pauschale gehört zur Regelung für Handelsgeschäfte: Sie ist gegenüber einem privaten Kunden nicht zu fordern.</p>

<h2>Bewährte Praxis auf Ihren Rechnungen</h2>

<p>Um sich im Streitfall abzusichern, geben Sie auf jeder Rechnung an:</p>

<ul>
    <li>Das genaue <strong>Fälligkeitsdatum</strong> (nicht nur „30 Tage")</li>
    <li>Die <strong>Zahlungsmodalitäten</strong> (Überweisung, mit IBAN)</li>
    <li>Den Hinweis auf die geltenden <strong>Verzugszinsen</strong></li>
    <li>Den Hinweis auf die <strong>Pauschale von 40 EUR</strong> bei gewerblichen Kunden</li>
</ul>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Zahlungsfristen und Verzugszinsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministerium – Gesetzlicher Zinssatz (halbjährlich aktualisiert)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Gesetz vom 18. April 2004 (Ursprungstext)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/unbezahlte-rechnung-luxemburg-richtig-mahnen" class="text-primary-500 hover:text-primary-600 text-sm">Einen Kunden mahnen →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verwalten Sie Ihre Zahlungsfristen mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu berechnet Fälligkeitsdaten automatisch, erkennt Verzögerungen und versendet Mahnungen. Behalten Sie Ihre Liquidität im Griff.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Halbjährlich zu prüfen</p>
    <p>Der für Handelsgeschäfte geltende Satz ändert sich zweimal jährlich. Diese Seite wird regelmäßig aktualisiert — prüfen Sie aber vor der Bezifferung einer Forderung den Satz des laufenden Halbjahres beim <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministerium</a>, und wenden Sie sich für Ihre persönliche Situation an Ihren Treuhänder.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'choose-invoicing-software-luxembourg-comparison',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">In brief</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 criteria</strong> to choose by: Luxembourg compliance (art. 63 details, numbering, 4 VAT rates, €50,000 exemption), full cycle (quotes, credit notes, recurring invoices), simplicity, all-in pricing, security (EU hosting, 2FA), accountant integration.</li><li><strong>FAIA</strong> is not required of everyone: four cumulative conditions apply. It remains the criterion that protects you <strong>as you grow</strong>.</li><li>Beware headline pricing: compare what is <strong>actually included</strong>, not just the advertised rate.</li></ul></div>
<p class="lead">Choosing invoicing software as a freelancer or SME in Luxembourg is not about picking one more "invoice tool". It is about picking one that keeps you <strong>compliant with rules specific to the Grand Duchy</strong> (mandatory details, VAT, numbering, archiving). Here are the 6 criteria that genuinely matter, and how to assess them.</p>

<h2>Why Luxembourg changes the picture</h2>
<p>A "generic" invoicing tool (French, Belgian, international) often ticks the basic boxes but ignores Luxembourg obligations. The Registration Duties, Estates and VAT Authority (AED) can require, during an audit, exact legal wording, flawless numbering and — under certain conditions — an audit file in a precise format. The right software must therefore be built <strong>for</strong> Luxembourg, not merely "available" in Luxembourg.</p>

<h2>Criterion 1: Luxembourg compliance</h2>
<p>This is the central criterion. Check precisely:</p>
<ul>
    <li><strong>Mandatory details (article 63 LIVA)</strong>: contact details, VAT number, unique sequential number, rates and amounts — applied automatically and correctly.</li>
    <li><strong>Unbroken sequential numbering</strong>: no gaps, no duplicates, locked once the invoice is finalised.</li>
    <li><strong>The 4 Luxembourg VAT rates</strong>: 17% (standard), 14% (intermediate), 8% (reduced) and 3% (super-reduced), applied to the right scenario.</li>
    <li><strong>Exemption regime</strong>: handling of the <strong>€50,000</strong> threshold (art. 57bis LIVA) with the correct wording and an alert as you approach it.</li>
    <li><strong>International VAT scenarios</strong>: intra-EU reverse charge, export, exemption — with the right legal wording applied automatically.</li>
    <li><strong>10-year archiving</strong>, in a format designed for long-term legibility (PDF/A) with an integrity fingerprint.</li>
    <li><strong>FAIA 2.01 export</strong>: see below — the criterion is more nuanced than usually claimed.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">FAIA does not concern everyone</p>
    <p>Many comparisons — including, until now, this page — present FAIA export as a deal-breaker. That overstates it. According to the AED's FAQ, the obligation requires <strong>four conditions met at once</strong>: being subject to the standard chart of accounts, not benefiting from a simplified regime, exceeding €112,000 in turnover, and exceeding roughly 500 accounting transactions a year. Many freelancers are not covered today.</p>
    <p class="mt-2">That does not make the criterion useless: it becomes decisive <strong>the day your business crosses those thresholds</strong>, and switching tools at that point costs far more than having planned for it. See our <a href="/en/blog/faia-luxembourg-computerized-audit-file-guide" class="text-primary-500 hover:text-primary-600">complete FAIA guide</a>.</p>
</div>

<h2>Criterion 2: essential features</h2>
<p>Beyond the invoice, your business needs a full cycle: <strong>quotes</strong> (convertible into invoices), <strong>credit notes</strong>, <strong>deposit invoices</strong> (with the right VAT chargeability), <strong>recurring invoices</strong>, tracking of <strong>expenses</strong> and <strong>deductible VAT</strong>, multi-currency handling, and <strong>payment reminders</strong>. The more of the cycle the software covers, the less you juggle between tools.</p>

<h2>Criterion 3: ease of use</h2>
<p>You are not an accountant: the software should save you time, not cost it. Test the <strong>time to issue your first compliant invoice</strong>, the clarity of the interface and availability in English (and ideally in your clients' language). A good tool needs no training.</p>

<h2>Criterion 4: pricing that fits your business</h2>
<p>Beware headline pricing: look at what is <strong>actually included</strong>. A "cheap" plan that charges extra for accounting exports or invoice volume can cost more than an all-in package. Check for a free tier to start with, and transparency across tiers.</p>

<h2>Criterion 5: security and GDPR compliance</h2>
<p>Your client and financial data are sensitive. Insist on <strong>European hosting</strong> (GDPR), encryption, two-factor authentication, <strong>regular backups</strong> and strict data isolation between accounts. Hosting outside the EU is a warning sign — and, for your invoice archives, it obliges you to declare the place of storage to the administration.</p>

<h2>Criterion 6: integration with your accountant</h2>
<p>The real saving in time and money happens with your accountant. Software that exports clean, importable data (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — or offers a <strong>dedicated portal</strong> where the accountant reads your data — spares them re-keying, and lowers your fees.</p>

<h2>What about e-invoicing?</h2>
<p>Two separate deadlines, often conflated:</p>
<ul>
    <li><strong>B2G (Luxembourg public sector)</strong>: e-invoicing is already mandatory to invoice the State and municipalities. If you work with the public sector, this is an immediate need.</li>
    <li><strong>ViDA (European reform)</strong>: the timetable is staggered — OSS extension in January 2027, mandatory reverse charge and platform rules in July 2028, then mandatory e-invoicing and digital reporting for intra-EU B2B on 1 July 2030. <strong>2027 and 2028</strong> come first, not 2030.</li>
</ul>
<p>So ask your vendor not "do you do Peppol?" but "<strong>by when, and for what scope</strong>?". A dated plan beats a ticked box.</p>

<h2>Quick comparison grid</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Criterion</th><th class="text-left p-2 bg-slate-100">What to require</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU compliance</td><td class="p-2 border-b">Art. 63 details, numbering, 4 VAT rates, €50,000 exemption</td></tr>
        <tr><td class="p-2 border-b">Full cycle</td><td class="p-2 border-b">Quotes, credit notes, deposits, recurring, expenses, reminders</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Available the day you cross the thresholds</td></tr>
        <tr><td class="p-2 border-b">E-invoicing</td><td class="p-2 border-b">B2G today; a dated roadmap for ViDA</td></tr>
        <tr><td class="p-2 border-b">Archiving</td><td class="p-2 border-b">PDF/A, 10 years, integrity fingerprint</td></tr>
        <tr><td class="p-2 border-b">Security</td><td class="p-2 border-b">EU hosting, 2FA, backups</td></tr>
        <tr><td class="p-2 border-b">Accountant</td><td class="p-2 border-b">FAIA/Sage/CSV exports, accountant portal</td></tr>
        <tr><td class="p-2 border-b">Price</td><td class="p-2 border-b">All-in, free tier, transparency</td></tr>
    </tbody>
</table>

<h2>Excel or software: is the switch worth it?</h2>
<p>Excel looks free, but it guarantees neither unbroken numbering nor mandatory details, and it will not produce a FAIA file the day one is required. Once you issue more than a handful of invoices a month, compliant software saves time and reduces risk. We go into this in our article <a href="/en/blog/excel-vs-invoicing-software-why-switch">Excel vs invoicing software</a>.</p>

<h2>Where faktur.lu stands on these criteria</h2>
<p>faktur.lu was built <strong>specifically for Luxembourg</strong>. What is available today:</p>
<ul>
    <li>Article 63 wording and sequential numbering applied automatically, the 4 VAT rates, intra-EU and export scenarios</li>
    <li>Handling of the €50,000 exemption threshold with an alert as you approach it</li>
    <li><strong>FAIA 2.01</strong> export, plus accounting exports for <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li>Read-only <strong>accountant portal</strong></li>
    <li><strong>PDF/A</strong> archiving with a SHA-256 fingerprint and a ten-year expiry</li>
    <li>European hosting, two-factor authentication, per-account data isolation</li>
    <li>Interface in <strong>5 languages</strong> (FR, DE, EN, LB, PT) and a <strong>free tier to start</strong></li>
</ul>
<p>What is <strong>in progress</strong>: rolling Peppol sending into production for all accounts, then receiving supplier invoices. The format is technically ready, the deployment is not — we would rather say so than tick a box.</p>

<h2>FAQ — choosing invoicing software in Luxembourg</h2>
<h3>Is invoicing software mandatory in Luxembourg?</h3>
<p>No, but your invoices must meet the legal requirements (details, numbering, VAT, archiving). Compliant software is the simplest way to guarantee all of that.</p>
<h3>Is the FAIA file required of everyone?</h3>
<p>No. The AED's FAQ sets four cumulative conditions: standard chart of accounts, no simplified regime, turnover above €112,000, and roughly 500 accounting transactions a year. If one is missing, you are not covered — but the tool should keep up as you grow.</p>
<h3>Can you invoice with Excel in Luxembourg?</h3>
<p>Nothing formally forbids it, but Excel guarantees neither unbroken numbering nor mandatory details. The audit risk is real once activity picks up.</p>
<h3>How much does invoicing software cost?</h3>
<p>From free (limited offers) to around €15-30/month for a full solution. The right instinct: compare what is <strong>included</strong> rather than the headline price alone.</p>
<h3>Which software if I am under the VAT exemption?</h3>
<p>Choose a tool that handles the exemption regime (€50,000 threshold), adds the correct wording automatically and <strong>alerts you as you approach the threshold</strong>. Check too that it accounts for the <strong>residual obligations</strong>: as soon as you invoice services to EU businesses, a simplified annual return and recapitulative statements remain mandatory.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Software built for Luxembourg, free to start</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates compliant invoices (wording, VAT, numbering) and the exports your accountant needs — in minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>
ARTICLE_HTML,
            ],
            [
                'slug' => 'intra-community-vat-guide-luxembourg-businesses',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Do you invoice clients in other EU countries? <strong>Intra-community VAT</strong> follows specific rules that every Luxembourg business owner should master. This guide explains them.</p>

<h2>What is intra-community VAT?</h2>

<p>Intra-community VAT is the VAT regime applying to trade in goods and services between businesses located in different <strong>European Union</strong> countries. The core principle is the <strong>reverse charge</strong>: it is the buyer, not the seller, who declares and pays the VAT in their own country.</p>

<h2>The intra-community VAT number</h2>

<p>In Luxembourg, your intra-community VAT number has the format <strong>LU + 8 digits</strong> (example: LU12345678). This number is:</p>

<ul>
    <li>Issued by the <strong>AED</strong> (Registration Duties, Estates and VAT Authority)</li>
    <li>Mandatory for every intra-community transaction</li>
    <li>Verifiable through the European Commission's <strong>VIES system</strong></li>
</ul>

<p><strong>Tip:</strong> faktur.lu automatically checks VAT numbers through VIES when you add an intra-community client.</p>

<h2>Intra-community invoicing rules</h2>

<h3>Selling B2B services (the most common case)</h3>

<p>When you sell a service to a business located in another EU country:</p>

<ol>
    <li>You invoice <strong>excluding VAT (0%)</strong></li>
    <li>You state on the invoice: <strong>"Reverse charge - Article 196 of Directive 2006/112/EC"</strong> (Article 44 of the Directive determines the place of supply; it is Article 196 that designates the customer as liable and corresponds to the mandatory wording - see art. 226 §11bis of the Directive)</li>
    <li>You state your VAT number <strong>and</strong> your client's</li>
    <li>The client declares the VAT in their own country (reverse charge mechanism)</li>
</ol>

<h3>Selling B2B goods</h3>

<p>For supplies of goods to an EU business:</p>

<ol>
    <li>You invoice <strong>excluding VAT</strong> (exempt intra-community supply)</li>
    <li>You state: <strong>"Exempt intra-community supply - Article 138 of Directive 2006/112/EC"</strong></li>
    <li>You must prove that the goods have left Luxembourg</li>
    <li>The transaction must appear in your <strong>recapitulative statement</strong></li>
</ol>

<h3>Selling to a private individual (B2C)</h3>

<p>Different rules apply to sales to private individuals in the EU. A <strong>single threshold of €10,000 per year</strong> governs them:</p>

<ul>
    <li><strong>Below €10,000 per year</strong>: you charge <strong>Luxembourg VAT</strong>, as you would to a local client</li>
    <li><strong>Above €10,000</strong>: you apply the <strong>VAT of the client's country</strong> and declare it through the <strong>OSS</strong> one-stop shop</li>
    <li><strong>This threshold is shared</strong> between distance sales of goods <em>and</em> electronic, telecommunications and broadcasting services - across all EU countries combined (excluding Luxembourg). What you must track is therefore your <strong>combined</strong> European B2C sales, not each category separately</li>
    <li><strong>Other services</strong> (consulting, in-person training…): generally Luxembourg VAT, with exceptions depending on the nature of the service</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Crossing the threshold takes effect immediately</p>
    <p>The transaction that takes you past €10,000 is <strong>already</strong> taxable in the client's country. Earlier sales remain taxable in Luxembourg. Track your running total during the year rather than discovering it at year-end.</p>
</div>

<h2>Mandatory declarations</h2>

<p>As a Luxembourg business carrying out intra-community transactions, you must file:</p>

<ul>
    <li><strong>Periodic VAT return</strong>: report your intra-community transactions in the appropriate boxes</li>
    <li><strong>Recapitulative statement</strong>: a monthly or quarterly return listing all intra-community sales by client and country</li>
    <li><strong>Intrastat</strong>: a statistical declaration for movements of goods above certain thresholds</li>
</ul>

<h2>VIES validation: why it matters</h2>

<p>Before invoicing an EU client without VAT, you <strong>must verify</strong> that their VAT number is valid through the <strong>VIES</strong> (VAT Information Exchange System). If the number is invalid:</p>

<ul>
    <li>You must invoice <strong>with Luxembourg VAT</strong></li>
    <li>You risk a <strong>tax reassessment</strong> if you invoice VAT-free without checking</li>
    <li>Keep <strong>evidence of the VIES check</strong> (screenshot or log)</li>
</ul>

<p>faktur.lu automatically checks every intra-community VAT number and keeps a log of the validation.</p>

<h2>Common practical cases</h2>

<h3>A Luxembourg consultant invoicing a German client</h3>
<p>You invoice VAT-free with a reverse charge mention. The German client declares German VAT (19%) in their own return. You report the transaction in your recapitulative statement.</p>

<h3>A Luxembourg web agency invoicing a French client</h3>
<p>Same principle: VAT-free invoice, reverse charge. The French client declares 20% French VAT. You must check their VAT number on VIES before invoicing.</p>

<h3>A Luxembourg e-commerce business selling to a Belgian consumer</h3>
<p>As long as your combined European B2C sales (distance sales of goods + electronic services) stay below €10,000 per year, you charge Luxembourg VAT. Above that, you apply the VAT of the client's country (21% in Belgium) through the <strong>OSS (One-Stop Shop)</strong> scheme.</p>

<h2>Mandatory invoice details</h2>

<p>Every intra-community invoice must state:</p>

<ul>
    <li>Your Luxembourg VAT number</li>
    <li>The client's VAT number</li>
    <li>The legal exemption wording (reverse charge or intra-community supply)</li>
    <li>The net amount and the mention "VAT 0%"</li>
</ul>

<p>faktur.lu automatically detects the VAT scenario based on country and client type, and applies the correct legal wording.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - VAT number validation (European Commission)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Directive 2006/112/EC (consolidated text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/vat-luxembourg-rates-calculation-obligations" class="text-primary-500 hover:text-primary-600 text-sm">VAT in Luxembourg →</a></li><li><a href="/en/blog/invoice-foreign-clients-from-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Invoicing abroad →</a></li><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">Comparison: choosing invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice across the EU in full compliance</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically detects intra-community VAT scenarios, verifies VAT numbers through VIES and applies the correct legal wording.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Luxembourg thresholds, rates and tax procedures may change. This page is updated regularly, but for your own situation, consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'invoice-archiving-luxembourg-legal-duration-format',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Archiving invoices is a legal obligation in Luxembourg: <strong>ten years</strong>. But when that period <em>starts</em>, and <em>where</em> the archives may sit, are more precisely regulated than most people assume. Here is what article 65 of the VAT law actually says.</p>

<h2>Statutory retention period</h2>

<h3>Invoices: ten years from the date of issue</h3>

<p><strong>Article 65, paragraph 4</strong> of the <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">VAT law</a> is explicit:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    "Those invoices and copies of invoices must be stored for a period of ten years <strong>from their date of issue</strong>."
</blockquote>

<p>An invoice issued on <strong>15 March 2026</strong> must therefore be kept until <strong>15 March 2036</strong> — not until the end of 2036. The rule applies to invoices you issue as well as those you receive.</p>

<h3>Books and other documents</h3>

<p>For other records, the starting point changes:</p>

<ul>
    <li><strong>Accounting books</strong>: ten years from their <strong>closing</strong></li>
    <li><strong>Other documents</strong>: ten years from their <strong>date</strong></li>
    <li><strong>Electronic interface registers</strong> (marketplaces, platforms): ten years from <strong>31 December of the year of the transaction</strong></li>
</ul>

<p>On top of this sits the general obligation of the <strong>Commercial Code</strong> (article 16), which also requires ten years for books and commercial correspondence.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A common confusion</p>
    <p>Many guides run the invoice period from the end of the financial year. That is the rule for <strong>books</strong>, not for invoices. In practice, aligning all your purges on the year-end means keeping things slightly longer than required: prudent, but not the rule.</p>
</div>

<h2>Where may you store your archives?</h2>

<p>This is the question any online service raises, and article 65 answers it in paragraph 6.</p>

<h3>The principle</h3>

<p>You <strong>freely determine the place of storage</strong>, on one condition: making available to the administration, "<strong>without undue delay, on any request from it</strong>", all the invoices, information, books and documents concerned.</p>

<h3>The limits</h3>

<ul>
    <li><strong>Countries without mutual assistance</strong>: storage is prohibited in any country or territory with which there is no mutual assistance instrument of comparable scope, nor any electronic right of access</li>
    <li><strong>Paper archives</strong>: a taxable person established in Luxembourg must store invoices there <strong>where storage is not electronic</strong> with full online access. In practice, your binders stay in Luxembourg</li>
    <li><strong>Mandatory declaration</strong>: if the place of storage is <strong>outside Luxembourg territory</strong>, you must declare it to the administration — in the <strong>annual return</strong> provided for by article 64, paragraph 7</li>
    <li><strong>Storage in another Member State</strong>: you must guarantee officials a right of <strong>electronic access, download and use</strong> of those invoices</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Worth checking if you use an online service</p>
    <p>Ask your provider <strong>where the data is physically hosted</strong>. If the servers sit outside Luxembourg, the declaration obligation in your annual return applies — even for a European service. A simple formality, but an easy one to forget.</p>
</div>

<h2>Accepted archiving formats</h2>

<p>Article 65, paragraph 5 sets the substantive requirement: <strong>authenticity of origin</strong>, <strong>integrity of content</strong> and <strong>legibility</strong> must be ensured throughout the storage period.</p>

<h3>Paper archiving</h3>
<p>Paper originals must be kept in a dry, accessible place, unaltered and — as seen above — on Luxembourg territory.</p>

<h3>Digital archiving</h3>
<p>Electronic storage is valid "provided that the data guaranteeing the authenticity of origin and the integrity of content are also stored electronically". In practice:</p>

<ul>
    <li>The format must guarantee the document's <strong>integrity</strong></li>
    <li>The document must remain <strong>legible</strong> throughout the retention period</li>
    <li>The <strong>PDF/A</strong> format (ISO 19005) is recommended for long-term legibility</li>
    <li>A <strong>digital fingerprint</strong> (hash) demonstrates the document has not been altered</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitisation and probative value</p>
    <p>For an electronic copy to carry the same <strong>probative value</strong> as the paper original, the <a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">law of 25 July 2015</a> rests that presumption on using a certified <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (dematerialisation or conservation service provider). Digitising without a PSDC remains possible, but the copy does not enjoy that presumption: if challenged, it is for you to prove its fidelity.</p>
</div>

<h2>Why the PDF/A format?</h2>

<p><strong>PDF/A</strong> is an ISO standard designed for long-term archiving. Unlike a standard PDF:</p>

<ul>
    <li>It <strong>embeds every font</strong> used (no external dependency)</li>
    <li>It <strong>forbids JavaScript</strong> and multimedia elements</li>
    <li>It aims to guarantee the document stays <strong>legible in ten, twenty or fifty years</strong></li>
    <li>It is <strong>widely recognised</strong> by European administrations</li>
</ul>

<h2>Archiving invoices with faktur.lu</h2>

<p>faktur.lu offers dedicated archiving, available on the plans that include the feature:</p>

<ol>
    <li>You archive a <strong>finalised</strong> invoice — individually or in batches</li>
    <li>The document is converted to <strong>PDF/A-1b</strong> by default; <strong>PDF/A-3b</strong> is available if you need to embed attachments</li>
    <li>A <strong>SHA-256 fingerprint</strong> is computed and stored, so the archive's integrity can be verified later</li>
    <li>A <strong>ten-year expiry</strong> is recorded with the archive</li>
    <li>You can download your archives at any time</li>
</ol>

<p class="text-sm text-slate-500"><em>Technical note: PDF/A conversion relies on Ghostscript server-side. If the tool is unavailable, the document is kept as a standard PDF with its fingerprint — the format actually obtained is recorded with the archive, so you always know what you have.</em></p>

<h2>Risks of non-compliance</h2>

<p>During a tax audit, missing invoices or non-compliant archiving can lead to:</p>

<ul>
    <li><strong>Refusal of VAT deductions</strong> on the missing invoices</li>
    <li><strong>Administrative fines</strong> (€250 to €10,000 per infringement, art. 77 LIVA), and up to <strong>€25,000 per day of delay</strong> after a warning where records are not produced during an audit</li>
    <li><strong>Assessment on the administration's own estimate</strong></li>
</ul>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - article 65 (retention and storage)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations of businesses</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Law of 25 July 2015 on electronic archiving</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS - Certified PSDC providers</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/faia-luxembourg-computerized-audit-file-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA file →</a></li><li><a href="/en/blog/tax-audit-luxembourg-how-to-prepare" class="text-primary-500 hover:text-primary-600 text-sm">Tax audits →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A archiving with faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archive your finalised invoices as PDF/A with a SHA-256 fingerprint and a ten-year expiry, individually or in batches.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Retention rules may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'invoice-foreign-clients-from-luxembourg',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Based in Luxembourg and invoicing clients abroad? VAT rules vary considerably depending on the geographical zone and the type of client. Here is a clear guide for each situation, with the right wording and legal bases (updated 2026).</p>

<h2>Case 1: Business client in the EU (intra-community B2B)</h2>

<p>This is the most common case for Luxembourg freelancers and SMEs. Example: a Luxembourg consultant invoices a German company.</p>

<h3>Rules to apply</h3>
<ul>
    <li>You invoice <strong>excluding VAT (0%)</strong> - place of supply is the customer's (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">art. 17 LIVA, transposing art. 44 of Directive 2006/112/EC</a>)</li>
    <li>The client declares the VAT in their country (<strong>reverse charge</strong>) - art. 196 of the Directive designates them as liable</li>
    <li>You must check the client's VAT number on <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li>Mandatory wording (art. 226 §11bis of the Directive): <em>"Reverse charge - Article 196 of Directive 2006/112/EC"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Article 196, not 44</p>
    <p>Many templates make the mistake of citing "article 44 of Directive 2006/112/EC". Article 44 defines the <strong>place of supply</strong>. The mandatory wording to apply refers to <strong>article 196</strong> (which designates the customer as liable for the VAT). Prefer the article 196 wording.</p>
</div>

<h3>Required documents</h3>
<ul>
    <li>Your Luxembourg VAT number on the invoice</li>
    <li>The client's VAT number (VIES-checked, keep the evidence)</li>
    <li>Reporting in the <strong>recapitulative statement</strong> — its frequency is set by the AED according to your situation</li>
</ul>

<h2>Case 2: Private client in the EU (B2C)</h2>

<p>You sell to a private individual in another EU country. The rules depend on the type of supply:</p>

<h3>Standard services (consulting, design, etc.)</h3>
<ul>
    <li>You charge <strong>Luxembourg VAT (17%)</strong> by default</li>
    <li>No reverse charge for private individuals</li>
    <li><strong>Careful:</strong> some B2C services follow specific rules (immovable property, transport, cultural or sporting events on site…) and are taxed where they are performed. If that is your case, check with your accountant.</li>
</ul>

<h3>Electronic services (SaaS, online training, etc.)</h3>
<ul>
    <li>You charge the <strong>VAT of the client's country</strong></li>
    <li>Through the <strong>OSS (One-Stop Shop)</strong> scheme: a single return for all EU countries</li>
    <li>Threshold: <strong>€10,000/year</strong> of B2C sales in the EU (combining distance sales of goods and TBE services). Below that, you may apply Luxembourg VAT.</li>
</ul>

<h2>Case 3: Client outside the EU (export)</h2>

<p>You invoice a client in Switzerland, the United States, the United Kingdom or any other non-EU country.</p>

<h3>Services</h3>
<ul>
    <li>You invoice <strong>excluding VAT (0%)</strong> - service supplied outside the EU (art. 17 LIVA)</li>
    <li>Recommended wording: <em>"VAT not applicable - service supplied outside the EU"</em></li>
    <li>No recapitulative statement needed (reserved for intra-EU trade)</li>
</ul>

<h3>Goods (export outside the EU)</h3>
<ul>
    <li>You invoice <strong>excluding VAT</strong> (exempt export, art. 43 §1 a) LIVA)</li>
    <li>You must keep <strong>proof of export</strong> (customs document)</li>
    <li>Wording: <em>"VAT exemption - Article 146 of Directive 2006/112/EC"</em></li>
</ul>

<h3>The special case of Northern Ireland</h3>

<p>Placing the United Kingdom wholesale in the "non-EU" bucket is correct for services but <strong>wrong for goods</strong>. Under the Ireland / Northern Ireland Protocol, EU VAT legislation continues to apply to <strong>goods</strong> to and from Northern Ireland.</p>

<ul>
    <li><strong>Goods to Northern Ireland</strong>: intra-community supply, not an export. The client holds a VAT number prefixed <strong>"XI"</strong>, to be validated on VIES, and the transaction appears in your recapitulative statement</li>
    <li><strong>Services to Northern Ireland</strong>: third-country regime, as for the rest of the United Kingdom</li>
    <li>A valid VAT number with the right prefix is a <strong>substantive condition</strong> of the exemption: a "GB" number does not allow the transaction to be treated as an intra-community supply</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A mistake that costs in both directions</p>
    <p>Treating a sale of goods to Northern Ireland as an export sends you hunting for customs proof that will never exist, and skips the recapitulative statement. Conversely, treating a service as intra-community makes you report a transaction that does not belong there. The test is the <strong>nature of the transaction</strong>, not the country.</p>
</div>

<h2>Special case: Switzerland</h2>

<p>Switzerland is not in the EU. Many Luxembourg freelancers invoice Swiss clients. The rules:</p>

<ul>
    <li><strong>B2B services</strong>: invoice <strong>excluding VAT</strong>; the Swiss client declares the tax through the <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">acquisition tax mechanism (ESTV)</a></li>
    <li><strong>B2C services</strong>: depending on the type of service, Luxembourg VAT may apply (electronic services in particular)</li>
    <li>Invoice in <strong>EUR or CHF</strong> as agreed with the client</li>
    <li>No recapitulative statement (reserved for intra-EU trade)</li>
</ul>

<h2>Summary table</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Scenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">VAT</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wording</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxembourg</td><td class="border border-gray-300 px-4 py-2">17%</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (intra-EU)</td><td class="border border-gray-300 px-4 py-2">0% (reverse charge)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Directive 2006/112/EC</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (standard services)</td><td class="border border-gray-300 px-4 py-2">17% LU (default)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / electronic services) - &gt; €10k</td><td class="border border-gray-300 px-4 py-2">VAT of client's country</td><td class="border border-gray-300 px-4 py-2">OSS scheme</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (not taxable in LU)</td><td class="border border-gray-300 px-4 py-2">"VAT not applicable - service supplied outside the EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export of goods outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (exempt)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Directive 2006/112/EC</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Goods to Northern Ireland</strong></td><td class="border border-gray-300 px-4 py-2">0% (intra-community supply)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Directive - client number prefixed "XI"</td></tr>
    </tbody>
</table>

<h2>Multi-currency</h2>

<p>If you invoice in a foreign currency, the VAT amount must be <strong>converted into euros</strong> for your Luxembourg return. Two principles hold:</p>

<ul>
    <li>Adopt a <strong>consistent conversion method</strong> and apply it to every transaction of the financial year</li>
    <li>Ensure <strong>consistency between the invoice and the accounts</strong>: the same rate must appear in both</li>
</ul>

<p>Which reference rate to use depends on your situation: have your accountant confirm it once and for all, rather than improvising invoice by invoice.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Do not confuse it with the customs rate</p>
    <p>The <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Customs Administration publishes its own exchange rates</a>, used to establish the <strong>customs value</strong> of goods. That is a distinct rate from the one used to convert the VAT taxable base. If you export goods, you will handle both.</p>
</div>

<h2>What changes with ViDA (2027-2030)</h2>

<p>The European <strong>ViDA</strong> package ("VAT in the Digital Age") has been adopted. Contrary to what is often written, it does not start in 2030: the timetable is <strong>staggered</strong>, and the first deadlines arrive well before.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Deadline</th>
            <th class="border border-gray-300 px-4 py-2 text-left">What takes effect</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">January 2027</td><td class="border border-gray-300 px-4 py-2">First extension of the OSS one-stop shop</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">July 2028</td><td class="border border-gray-300 px-4 py-2">Mandatory reverse charge in certain cases, platform rules, further extension of the OSS to domestic B2C supplies</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1 July 2030</td><td class="border border-gray-300 px-4 py-2">Mandatory e-invoicing and digital reporting for intra-EU B2B transactions</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">January 2035</td><td class="border border-gray-300 px-4 py-2">Harmonisation of existing national systems with the European standard</td></tr>
    </tbody>
</table>

<p>If you regularly invoice clients in the EU, it is <strong>2027 and 2028</strong> that concern you first, not 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Intra-EU and international VAT rules evolve, and the ViDA timetable may still be refined. This page is updated regularly, but for your situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED - Determining the place of supply</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu - Intra-community transactions</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/EC - articles 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Intra-EU VAT validation</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">European Commission - Place of taxation</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/article-17-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers" class="text-primary-500 hover:text-primary-600 text-sm">Article 17 LIVA: intra-EU B2B reverse charge →</a></li><li><a href="/en/blog/intra-community-vat-guide-luxembourg-businesses" class="text-primary-500 hover:text-primary-600 text-sm">Intra-community VAT - complete guide →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">Comparison: choosing invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice abroad in full compliance</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically detects the VAT scenario from the client (country, B2B/B2C) and applies the right wording. Built-in VIES validation.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'payment-terms-luxembourg-legal-framework-2026',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">In Luxembourg, payment terms are governed by law. Whether you invoice a business, a public authority or a private individual, here are the rules that protect your cash flow.</p>

<h2>Statutory payment terms</h2>

<h3>B2B transactions (between businesses)</h3>

<p>The <strong>amended law of 18 April 2004</strong> on payment terms and late payment interest sets out the following rules:</p>

<ul>
    <li><strong>Default term</strong>: 30 days from receipt of the invoice</li>
    <li><strong>Maximum contractual term</strong>: 60 days</li>
    <li>The parties may agree on a term <strong>longer than 60 days</strong> only where this does not constitute a <strong>manifest abuse</strong> towards the creditor</li>
</ul>

<p>That law was <strong>amended by the law of 29 March 2013</strong>, which transposes Directive 2011/7/EU. It is this revision that raised the margin to 8 points and created the €40 fixed compensation: articles still citing only the 2004 text describe a superseded regime.</p>

<h3>B2G transactions (with the public sector)</h3>

<p>Terms are stricter for public authorities:</p>

<ul>
    <li><strong>Maximum term</strong>: 30 days from receipt of the invoice</li>
    <li>This may be <strong>extended to 60 days maximum</strong> where expressly agreed in the contract and objectively justified by its particular nature</li>
    <li>The acceptance or verification procedure may not exceed <strong>30 days</strong></li>
</ul>

<h2>When does the clock start?</h2>

<p>The payment period starts running from:</p>

<ul>
    <li>The <strong>date the debtor receives the invoice</strong></li>
    <li>Or the <strong>date the goods/services are received</strong>, where the invoice date is uncertain or the invoice was sent beforehand</li>
    <li>Or the <strong>date of acceptance or verification</strong>, where such a procedure is provided for by contract or by law</li>
</ul>

<p><strong>Tip:</strong> always state the <strong>due date</strong> clearly on your invoice. With faktur.lu it is calculated automatically (30 days by default, customisable).</p>

<h2>Late payment interest</h2>

<p>In the event of late payment, interest is due <strong>automatically</strong>, with no need to send a formal notice. The rate, however, is <strong>not the same depending on who your client is</strong> — this is the most common confusion.</p>

<h3>Your client is a business</h3>

<ul>
    <li><strong>Formula</strong>: the ECB reference rate published in the Mémorial B at the start of each half-year, <strong>plus 8 percentage points</strong></li>
    <li><strong>First half of 2026</strong>: <strong>10.15%</strong> (2.15% + 8)</li>
    <li>The rate is <strong>revised every six months</strong>: check the one in force before quantifying a claim</li>
    <li>Interest runs from the day after the due date</li>
</ul>

<h3>Your client is a private individual</h3>

<p>The commercial transactions regime does not apply. The <strong>statutory interest rate</strong> governs instead, set annually: <strong>3.75% for 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The gap matters</p>
    <p>10.15% against 3.75%: charging a consumer the commercial rate means claiming nearly three times what is owed. The claim becomes contestable, and your credibility suffers at exactly the moment you need to be taken seriously.</p>
</div>

<h2>Fixed recovery compensation</h2>

<p>On top of late payment interest, the creditor is entitled to <strong>fixed compensation of €40</strong> for recovery costs, due <strong>per unpaid invoice</strong>, automatically and without any proof of expenditure.</p>

<p>Where actual recovery costs exceed €40, the creditor may claim the effective amount on production of supporting documents.</p>

<p>This compensation also belongs to the commercial transactions regime: it cannot be claimed from a private client.</p>

<h2>Best practice on your invoices</h2>

<p>To protect yourself in the event of a dispute, state on every invoice:</p>

<ul>
    <li>The precise <strong>due date</strong> (not merely "30 days")</li>
    <li>The <strong>payment details</strong> (bank transfer, with IBAN)</li>
    <li>A mention of the applicable <strong>late payment interest</strong></li>
    <li>A mention of the <strong>€40 fixed compensation</strong> for your business clients</li>
</ul>

<h2>Official sources</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu - Payment terms and late payment interest</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministry of Justice - Statutory interest rate (updated each half-year)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux - Law of 18 April 2004 (original text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/chase-unpaid-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Chasing an unpaid client →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory invoice details →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Manage your payment terms with faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu calculates due dates automatically, detects late payments and sends reminders. Stay in control of your cash flow.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every six months</p>
    <p>The rate applying to commercial transactions changes twice a year. This page is updated regularly, but before quantifying a claim, check the current half-year's rate with the <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Ministry of Justice</a>, and for your own situation consult your accountant.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'peppol-b2g-luxembourg-complete-guide-2026',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Since 2022-2023, electronic invoicing through <strong>Peppol</strong> has been mandatory for suppliers to the Luxembourg public sector, <strong>whatever the invoice amount</strong>. This guide explains everything you need to know to comply in 2026.</p>

<h2>What is Peppol?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> is an international e-invoicing network for exchanging business documents (invoices, purchase orders) in a standardised way between businesses and public administrations. Run by <a href="https://peppol.org" target="_blank" rel="noopener">OpenPeppol</a>, it now spans more than 100 countries and counts several million registered participants.</p>

<p>In Luxembourg, Peppol is the official channel for B2G (Business-to-Government) e-invoicing. Any business invoicing the State, the municipalities or public institutions must use this format.</p>

<h2>Who is affected?</h2>

<p>If you supply any of the following, you must invoice through Peppol:</p>

<ul>
    <li><strong>The Luxembourg State</strong> and its ministries</li>
    <li><strong>Luxembourg municipalities</strong></li>
    <li><strong>Public institutions</strong> (hospitals, schools, etc.)</li>
    <li><strong>All public contracts and concession contracts</strong>, whatever the amount</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ No minimum amount</p>
    <p>Contrary to a widespread belief, there is <strong>no minimum threshold</strong> (such as "€30,000") for the obligation. The <a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">law of 13 December 2021</a> requires electronic invoicing for <strong>every</strong> invoice issued under a public contract or concession contract, from the smallest to the largest.</p>
</div>

<h3>Implementation timetable</h3>

<p>The obligation came into force in stages, according to the size of the economic operator:</p>

<ul>
    <li><strong>18 May 2022</strong>: large economic operators</li>
    <li><strong>18 October 2022</strong>: medium-sized economic operators</li>
    <li><strong>18 March 2023</strong>: small operators and newly created businesses</li>
</ul>

<p>Since 18 March 2023, therefore, <strong>every</strong> supplier to the Luxembourg public sector is covered.</p>

<h2>How does Peppol work?</h2>

<p>The Peppol network runs on a four-corner model:</p>

<ol>
    <li><strong>The sender</strong> (your business) creates the invoice</li>
    <li><strong>The sending access point</strong> (your invoicing software or its access point) puts the invoice onto the Peppol network</li>
    <li><strong>The receiving access point</strong> (on the administration's side) receives the invoice</li>
    <li><strong>The recipient</strong> (the public administration) processes the invoice</li>
</ol>

<p>Every participant on the network is identified by a unique <strong>Peppol Participant ID</strong>. In Luxembourg the standard scheme is <strong>9938</strong> (LU:VAT), based on the VAT number. Format: <code>9938:LU########</code>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">⚠ A classic mistake: scheme 0184</p>
    <p>Many sources get this wrong. Scheme <strong>0184</strong> belongs to the <strong>Danish</strong> business register (DIGSTORG/CVR), not to Luxembourg. The correct LU scheme is <strong>9938</strong>. Check it in the <a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">official Peppol EAS code list</a>.</p>
</div>

<h2>The UBL format and Peppol BIS Billing 3.0</h2>

<p>Peppol invoices in Luxembourg use the <strong>Peppol BIS Billing 3.0</strong> format, built on the <strong>UBL (Universal Business Language)</strong> XML standard and compliant with the European standard <strong>EN 16931</strong>. Your invoice must contain:</p>

<ul>
    <li>The sender's details (name, address, VAT number)</li>
    <li>The recipient's details (name, Peppol Participant ID)</li>
    <li>The invoice lines (description, quantity, unit price)</li>
    <li>The VAT amounts broken down by rate</li>
    <li>The totals (net, VAT, gross)</li>
    <li>The order or contract references</li>
</ul>

<p>The <strong>XRechnung 3.0.1</strong> format (the German EN 16931-compliant standard) is also accepted by Luxembourg administrations.</p>

<h2>Alternatives if you are not connected to Peppol</h2>

<p>If you do not yet have software connected to Peppol, the official alternative channel is open to you:</p>

<ul>
    <li><strong>MyGuichet.lu</strong>: online forms allowing you to submit a compliant electronic invoice manually to the receiving administrations</li>
</ul>

<p>See the full procedure on <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Submitting an electronic invoice for a public contract</a>.</p>

<h2>Peppol and faktur.lu: what is available today</h2>

<p>We would rather be precise here than tick a box.</p>

<p><strong>Available now:</strong> faktur.lu generates the <strong>Peppol BIS Billing 3.0 (UBL) file</strong> for your invoices, compliant with the format required by the Luxembourg public sector. You download it from the invoice and submit it through the channel of your choice — notably the <strong>CTIE</strong> access point, which public bodies without their own access point use.</p>

<p><strong>Being rolled out:</strong> automatic transmission through an integrated access point, which will remove that manual step. The format is technically ready; the rollout across all accounts is not. We will update this page the day it is.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In the meantime, you are compliant</p>
    <p>The legal obligation concerns the <strong>format</strong> of the invoice and its electronic transmission, not the tool used to deliver it. A compliant BIS 3.0 file, submitted through your public buyer's channel, satisfies the obligation.</p>
</div>

<h2>Benefits of Peppol invoicing</h2>

<ul>
    <li><strong>Faster processing</strong>: Peppol invoices are processed automatically, shortening payment times</li>
    <li><strong>Fewer errors</strong>: the structured format removes manual keying mistakes</li>
    <li><strong>Traceability</strong>: every invoice is tracked end to end across the network</li>
    <li><strong>Compliance</strong>: you meet the Luxembourg legal obligations (law of 13 December 2021)</li>
    <li><strong>International reach</strong>: Peppol is now deployed in more than <strong>100 countries</strong>, with dozens of national Peppol Authorities</li>
</ul>

<h2>Frequently asked questions</h2>

<h3>Is Peppol invoicing mandatory for B2B?</h3>

<p>Not yet in Luxembourg for domestic B2B. The European <strong>ViDA (VAT in the Digital Age)</strong> package, adopted in 2025, provides for mandatory e-invoicing on <strong>intra-EU B2B</strong> transactions from <strong>1 July 2030</strong>. For domestic B2B, full harmonisation is planned by 2035. See <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">European Commission - ViDA</a>.</p>

<h3>How much does Peppol submission cost?</h3>

<p>Generating the Peppol BIS 3.0 file is included in your faktur.lu subscription, with no per-invoice surcharge. The Peppol network itself does not bill the sender: any cost depends on the access point used to route the document.</p>

<h3>How do I find an administration's Peppol ID?</h3>

<p>The Peppol Participant IDs of Luxembourg administrations are available in the <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. You can also search for them directly in faktur.lu when creating the client.</p>

<h3>Which Peppol scheme applies in Luxembourg?</h3>

<p>The standard scheme is <strong>9938</strong> (LU:VAT, based on the VAT number). Full format: <code>9938:LU########</code>. Not to be confused with 0184, the Danish scheme.</p>

<h3>What are the penalties for non-compliance?</h3>

<p>The administration can refuse to process a non-compliant paper or PDF invoice and require it to be resubmitted through Peppol, which delays payment. For the detail of formal penalties, consult the law of 13 December 2021 or your accountant.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>The ViDA timetable, thresholds and Peppol Authorities evolve. This page is updated regularly, but for your situation consult <a href="https://guichet.public.lu/" target="_blank" rel="noopener">Guichet.lu</a> and the official Peppol documentation.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Law of 13 December 2021 on B2G electronic invoicing</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Submitting an electronic invoice</a></li>
    <li><a href="https://mindigital.gouvernement.lu/fr/dossiers/2021/facturation-electronique.html" target="_blank" rel="noopener">Ministry of Digitalisation - Electronic invoicing</a></li>
    <li><a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">Peppol - EAS code list (identifier schemes)</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">European Commission - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/factur-x-zugferd-european-electronic-invoicing-explained" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">Choosing invoicing software →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a LU invoice →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Ready to invoice through Peppol?</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates the compliant Peppol BIS Billing 3.0 file for your invoices, ready to submit to the public sector. Create your free account and try it in minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'revenue-book-luxembourg-obligations-template',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">The <strong>revenue book</strong> is a simple accounting record kept in Luxembourg by freelancers, self-employed people and small businesses that do not run double-entry bookkeeping. It lists every payment received, in chronological order. Here is everything you need to know in 2026.</p>

<h2>What is the revenue book?</h2>

<p>The revenue book is a chronological register of all the invoices you issue and the payments you receive. It is the simplest form of the bookkeeping the law requires.</p>

<p>Its basis is <strong>article 65, paragraph 2, of the VAT law</strong>, which requires every taxable person to "keep accounts in sufficient detail to allow VAT to be applied and checked by the administration". The law prescribes no single template: it sets a result to achieve. For a self-employed person on simplified bookkeeping, the revenue book is the usual way to get there.</p>

<p>Unlike a general ledger, it is a <strong>simplified</strong> record, aimed at <strong>freelancers, self-employed people and small businesses</strong> without double-entry bookkeeping.</p>

<h2>Who must keep a revenue book?</h2>

<ul>
    <li><strong>Self-employed people and liberal professions</strong> who do not keep full commercial accounts</li>
    <li><strong>Small sole proprietorships</strong>, including those under the VAT exemption (article 57bis LIVA, €50,000 net threshold since 1 January 2025)</li>
    <li><strong>Traders on simplified bookkeeping</strong> whose turnover stays below the threshold for full commercial accounts</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Worth noting</p>
    <p><strong>Companies keeping double-entry accounts</strong> (SARL, SA, etc.) do not need a separate revenue book: their general ledger and journals serve that purpose. The practice mainly concerns self-employed people on simplified bookkeeping.</p>
</div>

<h2>What must the revenue book contain?</h2>

<p>Each entry must show:</p>

<ul>
    <li><strong>The date</strong> of the invoice or payment</li>
    <li><strong>The invoice number</strong> (sequential numbering, art. 63 LIVA)</li>
    <li><strong>The client's name</strong></li>
    <li><strong>A description</strong> of the service or goods sold</li>
    <li><strong>The net amount</strong></li>
    <li><strong>The VAT rate applied</strong> (17%, 14%, 8%, 3% or 0%)</li>
    <li><strong>The VAT amount</strong></li>
    <li><strong>The gross amount</strong></li>
</ul>

<h2>Format and retention</h2>

<p>The revenue book may be kept:</p>

<ul>
    <li><strong>On paper</strong>: in a dedicated notebook, with no crossings-out or blanks</li>
    <li><strong>Digitally</strong>: through invoicing software, a spreadsheet or a PDF, with integrity guarantees</li>
</ul>

<p>It must be kept for <strong>ten years from its closing</strong> (art. 65 paragraph 4 of the VAT law, and article 16 of the Commercial Code). Note the nuance: for <strong>books</strong>, the period runs from the closing, whereas for <strong>invoices</strong> it runs from the date of issue. See the <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">accounting obligations on Guichet.lu</a>.</p>

<h2>Generating your revenue book with faktur.lu</h2>

<p>The revenue book is built automatically from your invoices — nothing to re-key:</p>

<ol>
    <li>Go to <strong>Accounting &gt; Revenue book</strong></li>
    <li>Select the period you want (month, quarter, year)</li>
    <li>Review each invoice in detail with its VAT breakdown</li>
    <li>Export as <strong>PDF</strong> or <strong>CSV</strong> for your accountant</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Available from the Essentiel plan</p>
    <p>The revenue book is part of the accounting exports, included in the <strong>Essentiel</strong> and <strong>Pro</strong> plans. The free plan does not offer it — the <strong>FAIA export, however, is included</strong> there, which is not intuitive: the two features sit at different plan levels.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Not to be confused: AED and ACD</p>
    <p>The <strong>AED</strong> (Registration Duties, Estates and VAT Authority) handles VAT and invoice audits. The <strong>ACD</strong> (Direct Tax Authority) handles income tax and corporate tax. The revenue book serves <strong>both</strong>: the AED as VAT evidence, the ACD as the basis for your taxable result. One document, two authorities.</p>
</div>

<h2>Revenue book or FAIA export?</h2>

<p>Do not confuse the two:</p>

<ul>
    <li>The <strong>revenue book</strong> is a day-to-day record, used routinely and passed to your accountant</li>
    <li>The <strong>FAIA</strong> is a structured XML file (the SAF-T standard as adapted by the AED), required only during an audit, and only where <strong>four conditions are met at once</strong>: being subject to the standard chart of accounts, not benefiting from a simplified regime, exceeding €112,000 in turnover and around 500 accounting transactions a year</li>
</ul>

<p>In other words: if you keep a revenue book because you are on simplified bookkeeping, you are most likely <strong>not</strong> subject to the FAIA. See our <a href="/en/blog/faia-luxembourg-computerized-audit-file-guide">complete FAIA guide</a>.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - article 65 (accounts and retention)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations of businesses</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption →</a></li><li><a href="/en/blog/faia-luxembourg-computerized-audit-file-guide" class="text-primary-500 hover:text-primary-600 text-sm">The FAIA file →</a></li><li><a href="/en/blog/invoice-archiving-luxembourg-legal-duration-format" class="text-primary-500 hover:text-primary-600 text-sm">Archiving invoices →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Your revenue book, without re-keying</h3>
    <p class="text-primary-800 mb-4">faktur.lu builds your revenue book from your invoices and exports it as PDF or CSV for your accountant. Included from the Essentiel plan.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'tax-audit-luxembourg-how-to-prepare',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Receiving an audit notice from the <strong>Registration Duties, Estates and VAT Authority (AED)</strong> unsettles every freelancer and SME owner in Luxembourg. Yet with proper preparation an audit takes a few hours. Here is how to get ready — and one limitation rule almost everyone gets wrong.</p>

<h2>Who gets audited by the AED?</h2>

<p>Any VAT-registered business in Luxembourg can be audited. In practice the administration targets:</p>

<ul>
    <li><strong>Fast-growing businesses</strong> whose turnover fluctuates unusually</li>
    <li><strong>Risk sectors</strong> identified by the AED (retail, hospitality, construction, consulting)</li>
    <li><strong>Atypical VAT returns</strong> (recurring VAT credit, significant intra-EU transactions)</li>
    <li><strong>Random audits</strong> based on statistical criteria</li>
</ul>

<h2>How far back can the AED go?</h2>

<p>This is the most misunderstood point of the subject, and the error is everywhere — including, until recently, on this page. <strong>Article 81 of the VAT law</strong> is unambiguous:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>"The Treasury's action for payment of the tax and of fines is time-barred after <strong>five years</strong> from 31 December of the year in which the amount to be collected became due."</p>
</blockquote>

<p>For <strong>VAT</strong>, the limitation period is therefore <strong>five years</strong>. The phrase "ten years" appears in the entire VAT law only for the <em>retention</em> of documents — never for limitation.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Do not confuse VAT with direct taxes</p>
    <p class="text-red-700">The <strong>ten-year</strong> limitation for non-declaration or inaccurate declaration does exist — but for <strong>direct taxes</strong> (§144 AO and art. 10 of the law of 27 November 1933), administered by the ACD. It does <strong>not</strong> apply to VAT, which falls under the AED. Believing otherwise can lead you to accept a reassessment covering years that are already time-barred.</p>
</div>

<p>Two caveats nonetheless. The limitation can be <strong>interrupted</strong> (art. 2244 ff. of the Civil Code, or a waiver by the taxable person): a fresh period then runs, completed at the end of the fourth year following the last interrupting act. And it runs from <strong>31 December</strong> of the year the amount fell due, not from the date of the transaction.</p>

<p>Finally, limitation and archiving are two different things: you must keep your invoices for <strong>ten years</strong> (art. 65 LIVA and art. 16 of the Commercial Code), regardless of the five-year limitation.</p>

<h2>The 3 types of AED audit</h2>

<h3>1. Desk audit</h3>

<p>The AED asks you to send documents by post or electronically. No site visit. The most common and lightest form.</p>

<h3>2. On-site audit</h3>

<p>An officer visits your premises to check your books, invoices and expense receipts. Announced by letter — the VAT law sets no notice period, the AED arranges the appointment case by case.</p>

<h3>3. Unannounced audit</h3>

<p>Rare, reserved for suspicion of serious fraud. The officer arrives without notice. You may ask for your accountant to be present.</p>

<h2>Documents the AED may request</h2>

<ul>
    <li><strong>Every invoice issued and received</strong> in the audited period, with the mandatory details of art. 63 LIVA</li>
    <li><strong>The FAIA file</strong> in format 2.01 — for taxable persons required to produce it, see below</li>
    <li><strong>The receipts and expenses book</strong> or full accounts depending on your regime</li>
    <li><strong>The VAT returns</strong> filed, periodic and annual</li>
    <li><strong>Business bank statements</strong> for the period</li>
    <li><strong>Supporting documents for deducted expenses</strong> (expense claims, supplier invoices)</li>
    <li><strong>Significant contracts</strong> with customers and suppliers</li>
    <li><strong>Evidence of reverse charge</strong> for intra-EU B2B: VAT numbers validated through VIES (art. 17 LIVA, art. 196 of the Directive)</li>
</ul>

<h2>FAIA: the critical point — but not for everyone</h2>

<p>The <strong>FAIA</strong> is a structured XML file, derived from the SAF-T standard, gathering the company header, the period's sales invoices, the related accounting entries and the control totals. The current schema is version <strong>2.01</strong>, whose last update published by the AED dates from July 2020.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Four cumulative conditions</p>
    <p>The FAIA does not concern every taxable person. According to the AED's FAQ, the obligation requires meeting <strong>at the same time</strong>: being subject to the standard chart of accounts (PCN), not benefiting from a simplified regime, exceeding €112,000 in turnover, and exceeding roughly <strong>500 accounting transactions</strong> a year. A transaction is an entire posting chain, not an invoice. If a single condition is missing, you are not covered — see our <a href="/en/blog/faia-luxembourg-computerized-audit-file-guide">complete FAIA guide</a>.</p>
</div>

<p>If you are covered and invoice from a spreadsheet, you will have to rebuild the file by hand: several days of work and a high risk of error.</p>

<h2>How an on-site audit unfolds</h2>

<ol>
    <li><strong>Written notice</strong> from the AED, usually by registered post</li>
    <li><strong>Arrival of the officers</strong> with an audit warrant</li>
    <li><strong>Request for documents</strong> from a pre-established list</li>
    <li><strong>Verification of entries</strong> and cross-checking against the VAT returns</li>
    <li><strong>Interview</strong> on non-standard transactions or detected discrepancies</li>
    <li><strong>Notification of conclusions</strong> by letter</li>
</ol>

<h2>The 5 costly mistakes</h2>

<ol>
    <li><strong>Non-sequential invoice numbering</strong> (art. 63 LIVA, point 3°): gaps or duplicates raise a presumption of undeclared turnover</li>
    <li><strong>Missing mandatory details</strong> on invoices (art. 63 LIVA)</li>
    <li><strong>Undocumented intra-EU reverse charge</strong>: without VIES validation of the customer's number, the AED can reclassify the transaction as taxable in Luxembourg</li>
    <li><strong>Missing or illegible expense receipts</strong></li>
    <li><strong>Inconsistencies between VAT returns and invoices</strong>: even a small gap triggers a deeper review</li>
</ol>

<h2>What penalties apply?</h2>

<ul>
    <li><strong>Formal breaches</strong> (missing wording, FAIA not supplied, late return): administrative fine of <strong>€250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
    <li><strong>Breach that deprived the Treasury of revenue</strong>: fine of <strong>10% to 50% of the VAT at stake</strong> — proportional, hence uncapped, and far heavier than the previous one on a significant dispute</li>
    <li><strong>Refusal to produce invoices and accounting records</strong>: up to <strong>€25,000 per day of delay</strong>, after a warning</li>
    <li><strong>Aggravated tax fraud or tax swindling</strong>: criminal fine of €25,000 to ten times the VAT amount, imprisonment from one month to five years, loss of civic rights for 5 to 10 years (art. 80 LIVA)</li>
</ul>

<h2>Preparing your audit in 5 steps</h2>

<ol>
    <li><strong>Prepare the FAIA</strong> for the requested period, if you are covered</li>
    <li><strong>Export every invoice issued</strong>, ideally as PDF/A</li>
    <li><strong>Check consistency</strong> with the VAT returns you filed</li>
    <li><strong>Build a file per month</strong>: invoices, bank statements, matching return</li>
    <li><strong>Tell your accountant</strong> and ask them to attend the audit</li>
</ol>

<h2>Challenging the conclusions</h2>

<p>If you disagree, the procedure is defined:</p>

<ol>
    <li><strong>Written, reasoned claim</strong> to the administration, within <strong>three months</strong> of the notification</li>
    <li>If rejected, <strong>the director of the administration is seized</strong> and their decision replaces the earlier one</li>
    <li><strong>Court action</strong> before the <strong>Luxembourg district court sitting in civil matters</strong> — VAT falls under the ordinary courts, not the administrative courts as direct taxes do. The writ must be served within <strong>three months</strong> of the director's decision, <strong>on pain of foreclosure</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The administration's silence does not block you</p>
    <p>If no decision comes within <strong>six months</strong> of your claim, you may treat it as rejected and go straight to court. In that case the three-month deadline does not run — waiting cannot foreclose you.</p>
</div>

<h2>How faktur.lu prepares you</h2>

<ul>
    <li>Automatic sequential numbering compliant with <strong>article 63 LIVA</strong></li>
    <li>Mandatory details generated on every invoice</li>
    <li>VIES validation for intra-EU B2B transactions</li>
    <li><strong>FAIA 2.01</strong> export for any period</li>
    <li><strong>PDF/A</strong> archiving with an integrity fingerprint</li>
    <li>Read-only accountant portal, no email ping-pong</li>
</ul>

<h2>Frequently asked questions</h2>

<h3>How long does an audit last?</h3>
<p>A desk audit: two to four weeks, depending on how fast you send the documents. An on-site audit: usually one to three days on the premises, then several weeks of analysis at the AED.</p>

<h3>Can I refuse an audit?</h3>
<p>No. Refusal is penalised and raises a presumption of bad faith. You may, however, request a postponement on legitimate grounds and insist on your accountant being present.</p>

<h3>Must I still archive for ten years if the limitation is five?</h3>
<p>Yes. These are two independent obligations: the ten-year retention comes from art. 65 LIVA and art. 16 of the Commercial Code, not from the limitation period.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Procedures, penalties and deadlines may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - articles 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">VAT sanctions and remedies</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAIA FAQ (scope)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<h2>Going further</h2>
<ul>
    <li><a href="/en/blog/faia-luxembourg-computerized-audit-file-guide">FAIA Luxembourg: everything about the computerised audit file</a></li>
    <li><a href="/en/blog/mandatory-information-invoice-luxembourg">Mandatory details on a Luxembourg invoice</a></li>
    <li><a href="/en/blog/invoice-archiving-luxembourg-legal-duration-format">Archiving invoices: legal duration and format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare your next audit today</h3>
    <p class="text-primary-800 mb-4">Automatic numbering, FAIA 2.01 in one click, compliant wording, PDF/A archiving. The audit becomes a formality.</p>
    <a href="/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Start for free</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'vat-exemption-luxembourg-threshold-obligations-normal-regime',
                'locale' => 'en',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">In Luxembourg, small businesses with annual net turnover not exceeding <strong>€50,000</strong> can use the <strong>VAT exemption regime</strong> (article 57bis of the Luxembourg VAT law). That threshold rose from €35,000 to €50,000 on 1 January 2025, alongside a still little-known <strong>cross-border scheme</strong>. Here is everything you need to know in 2026.</p>

<h2>What is the VAT exemption regime?</h2>

<p>The VAT exemption (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">article 57bis LIVA</a>) is a <strong>special scheme</strong> allowing small businesses to exempt from VAT the goods and services they supply. In practice you:</p>

<ul>
    <li><strong>Charge no VAT</strong> to your clients</li>
    <li><strong>Remit no VAT</strong> to the State</li>
    <li>Are relieved of the obligation to file <strong>ordinary VAT returns</strong></li>
</ul>

<p>In exchange, you <strong>cannot reclaim the VAT</strong> paid on your business purchases.</p>

<h2>Eligibility conditions</h2>

<ul>
    <li>Annual net turnover <strong>of €50,000 or less</strong> over the calendar year</li>
    <li><strong>10% tolerance</strong>: if you exceed it during the year without going beyond <strong>€55,000</strong>, you stay exempt until 31 December</li>
    <li>Seat of economic activity in Luxembourg (a mere fixed establishment is not enough)</li>
    <li>The scheme is <strong>optional</strong>: you may prefer the normal regime</li>
</ul>

<p><strong>Transactions excluded from the exemption:</strong> occasional transactions referred to in article 12 of Directive 2006/112/EC, and <strong>supplies of new means of transport</strong> to another Member State. Also excluded are taxable persons under the VAT group scheme, the flat-rate scheme for farmers and foresters, or holding incompatible immovable property options.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Did you know?</p>
    <p>The threshold rose from €35,000 to €50,000 on 1 January 2025, as part of transposing <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Directive (EU) 2020/285</a>. The maximum a Member State may set is €85,000; Luxembourg provides for no sectoral thresholds.</p>
</div>

<h2>What the exemption does not free you from</h2>

<p>This is the most misunderstood part of the scheme, and it affects a lot of people. Being exempt <strong>does not mean being free of every reporting obligation</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ If you invoice business clients in the EU</p>
    <p class="text-red-700">As soon as you supply <strong>intra-community services</strong> — or become liable for VAT in Luxembourg under article 61 of the VAT law — you <strong>must</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>file a <strong>simplified annual return</strong> through the <strong>eCDF</strong> portal, <strong>before 1 March</strong> of the following calendar year;</li>
        <li>submit <strong>recapitulative statements</strong> for those intra-community services.</li>
    </ul>
    <p class="text-red-700 mt-2">A single business client in Germany, France or Belgium triggers both obligations.</p>
</div>

<p>Outside that case, an exempt taxable person reports annual turnover to their tax office — by post, by email, or using the simplified annual return form provided by the AED.</p>

<p>Finally, if you fall under the exemption and then the normal regime <strong>within the same year</strong>, the turnover achieved under the exemption goes in <strong>box 481</strong> of the VAT return filed under the normal regime.</p>

<h2>Mandatory wording on your invoices</h2>

<p>Even when exempt, your invoices must carry the following <strong>exact wording</strong>:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "VAT not applicable – Article 57bis of the amended law of 12 February 1979"
</blockquote>

<p>You must also:</p>
<ul>
    <li><strong>Show no VAT</strong> (no VAT line on the invoice)</li>
    <li>State no <strong>VAT rate</strong></li>
    <li>Show only the <strong>net amount</strong> (no net/gross distinction)</li>
</ul>

<p>Good news since 1 January 2025: a taxable person using the exemption may issue <strong>simplified invoices</strong>.</p>

<h2>Advantages of the exemption</h2>

<ul>
    <li><strong>Simplicity</strong>: no ordinary VAT return, simplified invoicing allowed</li>
    <li><strong>B2C competitiveness</strong>: at the same net price, your invoice is around <strong>14.5% cheaper</strong> for a consumer than a normal-regime competitor's (17% VAT on €117 gross). Note, though, that this edge is eroded by the VAT you cannot reclaim on your own purchases</li>
    <li><strong>Cash flow</strong>: no gap between VAT collected and VAT remitted</li>
    <li><strong>Less paperwork</strong>: simplified administration</li>
</ul>

<h2>Drawbacks</h2>

<ul>
    <li><strong>No input VAT deduction</strong>: you pay VAT on purchases without reclaiming it (decisive if you invest)</li>
    <li><strong>B2B disadvantage</strong>: business clients deduct no VAT from your invoices, so your price costs them more</li>
    <li><strong>Image</strong>: some B2B clients prefer working with VAT-registered businesses</li>
    <li><strong>Residual obligations</strong>: as soon as you invoice services to EU businesses, the simplified annual return and recapitulative statements become mandatory again</li>
    <li><strong>Binding ceiling</strong>: beyond the threshold and its tolerance, switching to the normal regime is compulsory</li>
</ul>

<h2>The cross-border exemption scheme (since 2025)</h2>

<p>This is the second change of 1 January 2025, far less commented on than the threshold increase. Until then, the exemption applied only in your country of establishment. Now a Luxembourg small business can <strong>use the exemption in other Member States</strong>.</p>

<h3>The conditions</h3>

<ul>
    <li>Respect the <strong>national threshold of each Member State</strong> where you want the exemption (thresholds vary: see the <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">European SME VAT rules portal</a>)</li>
    <li>Achieve turnover <strong>below €100,000 across the whole Union</strong> — the "Union threshold", which includes your Luxembourg sales</li>
    <li>Have the <strong>seat of your economic activity</strong> in Luxembourg. A taxable person seated in a third country cannot use it, even with a fixed establishment in the EU</li>
</ul>

<h3>The "EX" number</h3>

<p>The procedure runs through a <strong>prior notification</strong> in your business area on <strong>MyGuichet.lu</strong>. The AED forwards the request to the Member States concerned. As soon as at least one accepts, you receive an <strong>"EX"</strong> identification number of the form <strong>LU12345678-EX</strong> — generally within <strong>35 working days</strong>.</p>

<p>The exemption only starts applying in a Member State <strong>from the date that number is communicated or confirmed</strong>. Important: <strong>retroactive identification is not possible</strong>. Sales made before you obtain the number cannot be brought in afterwards.</p>

<h3>Your reporting obligations</h3>

<ul>
    <li><strong>Quarterly declaration</strong> to the AED of total turnover across <strong>all</strong> Member States — Luxembourg included</li>
    <li>In exchange, you need not register or file VAT returns in Member States where you are not established, for transactions covered by the exemption</li>
    <li>Transactions <strong>not covered</strong> by the exemption there (intra-community acquisitions in particular) remain subject to local obligations</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">If you exceed the Union threshold</p>
    <p>Beyond €100,000 of turnover in the Union, you lose the exemption in Member States where you are not established — <strong>including the following calendar year</strong>, even if the national thresholds are respected there. You do, however, keep the national exemption in Luxembourg as long as you meet the conditions.</p>
</div>

<h2>When to move to the normal regime?</h2>

<h3>Compulsory switch (exceeding the threshold)</h3>

<p>The rule depends on <strong>how far</strong> you exceed the €50,000 threshold:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Effect</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Exceeded by 10% at most (turnover up to €55,000)</td><td class="p-2 border-b">You stay exempt until 31 December. Switch to the normal regime on <strong>1 January of the following year</strong>.</td></tr>
        <tr><td class="p-2 border-b">Exceeded by more than 10% (turnover above €55,000)</td><td class="p-2 border-b">The exemption ceases to apply <strong>from the day after the threshold is exceeded</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The AED's official example</p>
    <p>A taxable person reaches €51,000: exceeded by less than 10%, they stay exempt. On <strong>8 September 2025</strong>, a €5,000 sale brings turnover to <strong>€56,000</strong>. The excess is over 10%: they are excluded from the exemption <strong>from 9 September 2025</strong>, and remain excluded throughout 2026.</p>
    <p class="mt-2">Note the mechanics: it is the <strong>following day</strong>. The invoice that breaks the ceiling is still issued exempt; it is the next one that carries VAT.</p>
</div>

<p><strong>In both cases</strong>, exceeding the threshold during a calendar year excludes you from the exemption for the <strong>whole of the following calendar year</strong>, whatever the percentage.</p>

<p>You must then:</p>

<ol>
    <li>Inform the <strong>AED</strong> of the change of regime via MyGuichet.lu or your tax office</li>
    <li>Start charging VAT on the timetable above</li>
    <li>Comply with the filing frequency determined by the AED</li>
</ol>

<h3>Voluntary switch (opting for the normal regime)</h3>

<p>You may opt for the normal regime <strong>even below the threshold</strong>, by applying to your tax office. <strong>The option takes effect on the 1st day of the following month</strong> and commits you for <strong>at least one calendar year</strong>: it is not a round trip.</p>

<p>It is often the right call if:</p>

<ul>
    <li>Your clients are mostly <strong>businesses (B2B)</strong> that deduct VAT</li>
    <li>You have <strong>significant investments</strong> (equipment, company vehicle) and want to reclaim input VAT</li>
    <li>You are approaching the threshold and prefer to <strong>plan ahead</strong> rather than change your pricing mid-year</li>
    <li>You invoice <strong>internationally (intra-EU B2B)</strong> and need an active VAT number</li>
</ul>

<p>Also worth noting: on <strong>cessation of activity</strong>, a declaration must reach your tax office within <strong>fifteen days</strong>. The exemption ends on the date of cessation.</p>

<h2>VAT filing frequency after the switch</h2>

<p>Once in the normal regime, frequency depends on your annual net turnover. <strong>The annual return is in addition to the periodic ones, not instead of them:</strong></p>

<ul>
    <li><strong>Turnover &lt; €112,000</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover &gt; €620,000</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<p>The AED determines your regime — you do not choose it.</p>

<h2>Impact on your invoices with faktur.lu</h2>

<p>faktur.lu handles both regimes:</p>

<ul>
    <li><strong>VAT exemption</strong>: invoices automatically display "VAT not applicable – Article 57bis of the amended law of 12 February 1979", with no VAT line and no net/gross distinction</li>
    <li><strong>Normal regime</strong>: VAT is calculated automatically at the right rate (17%, 14%, 8% or 3%)</li>
    <li><strong>Threshold alert</strong>: faktur.lu warns you as you approach €50,000 (and the €55,000 tolerance) so you can plan the switch</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>VAT thresholds, rates and procedures may change, and the cross-border scheme is recent. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Special scheme for small enterprises (exemption)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – Exemption FAQ (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">European Commission – National exemption thresholds by Member State</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Directive (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/vat-luxembourg-rates-calculation-obligations" class="text-primary-500 hover:text-primary-600 text-sm">VAT rates in Luxembourg →</a></li><li><a href="/en/blog/freelancer-luxembourg-invoice-compliance" class="text-primary-500 hover:text-primary-600 text-sm">Freelancing in Luxembourg: invoicing in compliance →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Manage your VAT exemption with confidence</h3>
    <p class="text-primary-800 mb-4">faktur.lu adapts automatically to your VAT regime and alerts you before you cross the €50,000 threshold.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'factur-x-zugferd-facturation-electronique-europeenne',
                'locale' => 'fr',
                'content' => <<<'ARTICLE_HTML'
<p class="lead"><strong>Factur-X</strong> (appelé <strong>ZUGFeRD</strong> en Allemagne) est le standard de facturation électronique adopté par la France et l'Allemagne, en cours d'adoption dans toute l'Union européenne. Voici tout ce que vous devez savoir en 2026.</p>

<h2>Qu'est-ce que Factur-X ?</h2>

<p>Factur-X est un format de facture <strong>« hybride »</strong> qui combine deux éléments dans un seul fichier PDF/A-3 :</p>

<ul>
    <li>La <strong>partie visuelle</strong> : le PDF lisible que vous voyez à l'écran et pouvez imprimer</li>
    <li>La <strong>partie structurée</strong> : un fichier XML intégré dans le PDF, contenant les données de la facture dans un format normalisé</li>
</ul>

<p>Ce double format permet à la fois aux <strong>humains</strong> de lire la facture et aux <strong>logiciels</strong> de l'intégrer automatiquement dans leur comptabilité.</p>

<h2>Factur-X vs ZUGFeRD : quelle différence ?</h2>

<p>Aucune différence technique. Il s'agit du <strong>même standard</strong> :</p>

<ul>
    <li><strong>Factur-X</strong> est le nom utilisé en France et dans les pays francophones (porté par <a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> est le nom utilisé en Allemagne (porté par le <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Les deux sont basés sur la norme européenne <strong>EN 16931</strong></li>
</ul>

<h2>Les profils Factur-X</h2>

<p>Factur-X propose plusieurs niveaux de détail :</p>

<ul>
    <li><strong>Minimum</strong> : informations de base (expéditeur, destinataire, montant total)</li>
    <li><strong>Basic WL</strong> : sans lignes de détail, mais avec TVA ventilée</li>
    <li><strong>Basic</strong> : avec les lignes de facture détaillées</li>
    <li><strong>EN 16931</strong> (anciennement « Comfort ») : profil complet conforme à la norme européenne - <strong>recommandé</strong></li>
    <li><strong>Extended</strong> : données supplémentaires pour des besoins spécifiques</li>
</ul>

<p>faktur.lu génère des factures Factur-X au profil <strong>EN 16931</strong>, le plus largement accepté.</p>

<h2>Pourquoi Factur-X est important</h2>

<h3>Pour votre entreprise</h3>
<ul>
    <li><strong>Traitement automatisé</strong> : vos clients peuvent importer vos factures dans leur comptabilité sans ressaisie</li>
    <li><strong>Réduction des erreurs</strong> : les données structurées éliminent les erreurs de saisie manuelle</li>
    <li><strong>Paiement plus rapide</strong> : un traitement automatisé = délai de paiement réduit</li>
    <li><strong>Image professionnelle</strong> : vous montrez que votre entreprise est à la pointe</li>
</ul>

<h3>Pour l'Union européenne</h3>
<ul>
    <li><strong>Lutte contre la fraude TVA</strong> : les données structurées permettent des contrôles automatisés</li>
    <li><strong>Harmonisation</strong> : un seul format pour tous les pays de l'UE</li>
    <li><strong>Directive ViDA (VAT in the Digital Age)</strong> : adoptée en mars 2025, elle rendra la facturation électronique obligatoire pour les transactions <strong>B2B intracommunautaires</strong> à compter du <strong>1<sup>er</sup> juillet 2030</strong> (et l'harmonisation domestique d'ici 2035). Voir <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Commission européenne - ViDA</a>.</li>
</ul>

<h2>Est-ce obligatoire au Luxembourg ?</h2>

<p>En 2026, Factur-X n'est <strong>pas encore obligatoire</strong> pour le B2B domestique au Luxembourg. Cependant :</p>

<ul>
    <li>La <strong>France</strong> rend la facturation électronique obligatoire selon ce calendrier : <strong>réception obligatoire pour toutes les entreprises au 1<sup>er</sup> septembre 2026</strong>, émission obligatoire pour les ETI/grandes entreprises à la même date, et pour les TPE/PME au <strong>1<sup>er</sup> septembre 2027</strong>. Voir <a href="https://www.economie.gouv.fr/tout-savoir-sur-la-facturation-electronique-pour-les-entreprises" target="_blank" rel="noopener">economie.gouv.fr</a>.</li>
    <li>L'<strong>Allemagne</strong> a rendu la facturation électronique B2B obligatoire à réception depuis le 1<sup>er</sup> janvier 2025, et l'émission le sera progressivement d'ici 2028.</li>
    <li>La <strong>directive ViDA</strong> de l'UE prévoit l'obligation B2B intra-UE au 1<sup>er</sup> juillet 2030.</li>
    <li>Le <strong>secteur public</strong> luxembourgeois utilise déjà Peppol (obligatoire depuis 2022-2023).</li>
</ul>

<p><strong>Anticipez :</strong> en adoptant Factur-X maintenant, vous êtes prêt pour les futures obligations et vous facilitez la vie de vos clients qui utilisent déjà ce format - notamment les clients français à partir de septembre 2026.</p>

<h2>Générer une facture Factur-X avec faktur.lu</h2>

<p>La génération Factur-X est incluse dans le <strong>plan Pro</strong>. Elle fonctionne à la demande, sur une facture <strong>finalisée</strong> :</p>

<ol>
    <li>Créez et <strong>finalisez</strong> votre facture — un brouillon ne peut pas être converti</li>
    <li>Téléchargez le fichier <strong>Factur-X</strong> : un PDF/A-3 contenant le XML intégré</li>
    <li>Ou récupérez le <strong>XML seul</strong>, si votre client ne veut que les données structurées</li>
    <li>Transmettez-le à votre client, qui l'importe dans sa comptabilité sans ressaisie</li>
</ol>

<p>Le profil utilisé est <strong>EN 16931</strong> par défaut, le plus largement accepté.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Une précision qui évite une mauvaise surprise</p>
    <p>Le PDF standard que vous envoyez habituellement <strong>ne contient pas</strong> le XML Factur-X : c'est un fichier distinct, à générer explicitement. Tant que vos clients ne le réclament pas, rien ne vous oblige à changer vos habitudes — mais le jour où un client français vous le demande, c'est disponible en un clic.</p>
</div>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les calendriers ViDA (UE), France et Allemagne évoluent. Cette page est mise à jour régulièrement, mais consultez les sources officielles ci-dessous pour les dates exactes applicables à votre cas.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Commission européenne - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/tout-savoir-sur-la-facturation-electronique-pour-les-entreprises" target="_blank" rel="noopener">France - economie.gouv.fr - Facturation électronique</a></li>
    <li><a href="https://fnfe-mpe.org/factur-x/" target="_blank" rel="noopener">FNFE-MPE - Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD - ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 4 juin 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Luxembourg →</a></li><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Choisir son logiciel de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Générez des factures Factur-X automatiquement</h3>
    <p class="text-primary-800 mb-4">Le plan Pro génère le fichier Factur-X (PDF/A-3 avec XML intégré) de vos factures finalisées, au profil EN 16931. Anticipez les obligations européennes à venir.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>

ARTICLE_HTML,
            ],
            [
                'slug' => 'akommessbuch-letzebuerg-obligatiounen-modell',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">D'<strong>Recettebuch</strong> ass en einfacht comptabelt Dokument, dat zu Lëtzebuerg vun Onofhängegen, Freelancer a klengen Entreprise gefouert gëtt, déi keng duebel Comptabilitéit hunn. Et erfaasst chronologesch all agenomm Recetten. Hei ass alles Wesentlecht fir 2026.</p>

<h2>Wat ass d'Recettebuch?</h2>

<p>D'Recettebuch ass e chronologescht Regëster vun alle Rechnungen, déi Dir ausstellt, a vun alle Bezuelungen, déi Dir kritt. Et ass déi einfachst Form vun der Comptabilitéit, déi d'Gesetz verlaangt.</p>

<p>D'Grondlag ass den <strong>Artikel 65, Paragraf 2, vum TVA-Gesetz</strong>, deen all Assujetti opleet, „eng Comptabilitéit ze féieren, déi genuch detailléiert ass, fir d'Applikatioun vun der TVA an hir Kontroll duerch d'Administratioun ze erlaben". D'Gesetz schreift kee eenzegt Muster vir, mä e Resultat, dat z'erreechen ass. Fir en Onofhängegen a vereinfachter Comptabilitéit ass d'Recettebuch dee gewinnte Wee dohin.</p>

<p>Am Géigesaz zum Haaptbuch handelt et sech ëm en <strong>vereinfacht</strong> Dokument, geduecht fir <strong>Onofhängeger, Freelancer a kleng Entreprisen</strong> ouni duebel Comptabilitéit.</p>

<h2>Wien muss e Recettebuch féieren?</h2>

<ul>
    <li><strong>Onofhängeger a fräi Beruffer</strong>, déi keng komplett kommerziell Comptabilitéit féieren</li>
    <li><strong>Kleng Eenzelentreprisen</strong>, och an der TVA-Franchise (Artikel 57bis LIVA, Seuil 50 000 € ouni TVA zanter dem 1. Januar 2025)</li>
    <li><strong>Händler a vereinfachter Comptabilitéit</strong>, deenen hiren Ëmsaz de Seuil fir déi komplett kommerziell Comptabilitéit net iwwerschreit</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Ze bemierken</p>
    <p><strong>Gesellschafte mat duebeler Comptabilitéit</strong> (SARL, SA asw.) mussen kee separaat Recettebuch féieren: hiert Haaptbuch an hir Journaler erfëllen dës Funktioun. D'Praxis betrëfft virun allem Onofhängeger a vereinfachter Comptabilitéit.</p>
</div>

<h2>Wat muss d'Recettebuch enthalen?</h2>

<p>All Antrag muss ausweisen:</p>

<ul>
    <li><strong>D'Datum</strong> vun der Rechnung oder vun der Bezuelung</li>
    <li><strong>D'Rechnungsnummer</strong> (fortlafend Nummeréierung, Art. 63 LIVA)</li>
    <li><strong>Den Numm vum Client</strong></li>
    <li><strong>D'Beschreiwung</strong> vun der Leeschtung oder vum verkaafte Gutt</li>
    <li><strong>De Betrag ouni TVA</strong></li>
    <li><strong>Den applizéierten TVA-Taux</strong> (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
    <li><strong>Den TVA-Betrag</strong></li>
    <li><strong>De Betrag mat TVA</strong></li>
</ul>

<h2>Format an Opbewahrung</h2>

<p>D'Recettebuch kann gefouert ginn:</p>

<ul>
    <li><strong>A Pabeierform</strong>: an engem eegene Heft, ouni Duerchstreechungen a Läschten</li>
    <li><strong>An digitaler Form</strong>: iwwer eng Fakturatiounssoftware, en Tabelleblat oder e PDF, mat Integritéitsgarantien</li>
</ul>

<p>Et muss <strong>zéng Joer vu sengem Ofschloss un</strong> opbewahrt ginn (Art. 65 Paragraf 4 vum TVA-Gesetz an Artikel 16 vum Handelsgesetzbuch). Bemierkt d'Nuance: bei <strong>Bicher</strong> leeft d'Frist vum Ofschloss un, bei <strong>Rechnungen</strong> dogéint vun hirem Ausstellungsdatum un. Kuckt d'<a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">comptabel Flichten op Guichet.lu</a>.</p>

<h2>Säi Recettebuch mat faktur.lu generéieren</h2>

<p>D'Recettebuch gëtt automatesch aus Äre Rechnungen opgebaut — Dir musst näischt nei erfaassen:</p>

<ol>
    <li>Gitt op <strong>Comptabilitéit &gt; Recettebuch</strong></li>
    <li>Wielt déi gewënschte Period (Mount, Trimester, Joer)</li>
    <li>Kuckt all Rechnung am Detail mat der TVA-Opdeelung</li>
    <li>Exportéiert als <strong>PDF</strong> oder <strong>CSV</strong> fir Är Fiduciaire</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Disponibel ab dem Plang Essentiel</p>
    <p>D'Recettebuch gehéiert zu de comptabelen Exporter an ass an de Pläng <strong>Essentiel</strong> a <strong>Pro</strong> abegraff. De Gratis-Plang bitt et net — den <strong>FAIA-Export ass do hannergéint abegraff</strong>, wat net op der Hand läit: déi zwou Funktioune leien net um selwechten Niveau vun der Offer.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Net ze verwiesselen: AED an ACD</p>
    <p>D'<strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA) këmmert sech ëm d'TVA an d'Rechnungskontrollen. D'<strong>ACD</strong> (Administration des contributions directes) këmmert sech ëm d'Akommessteier an d'Gesellschaftssteier. D'Recettebuch déngt <strong>allen zwee</strong>: der AED als TVA-Beleg, der ACD als Basis vun Ärem steierflichtege Resultat. Ee Dokument, zwou Administratiounen.</p>
</div>

<h2>Recettebuch oder FAIA-Export?</h2>

<p>Verwiesselt déi zwee net:</p>

<ul>
    <li>D'<strong>Recettebuch</strong> ass en alldeeglecht Iwwersiichtsdokument, dat Dir Ärer Fiduciaire weiderginn</li>
    <li>De <strong>FAIA</strong> ass e strukturéierten XML-Fichier (SAF-T-Standard, vun der AED ugepasst), nëmme bei enger Kontroll verlaangt, an nëmme wa <strong>véier Bedingunge gläichzäiteg erfëllt</strong> sinn: dem normaliséierte Comptesplang ënnerleien, kee vereinfachte Regime a Usproch huelen, iwwer 112 000 € Ëmsaz an ongeféier 500 comptabel Transaktioune pro Joer leien</li>
</ul>

<p>Anescht gesot: wien e Recettebuch féiert, well hien a vereinfachter Comptabilitéit ass, ënnerleit dem FAIA héchstwahrscheinlech <strong>net</strong>. Kuckt eise <a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide">komplette FAIA-Guide</a>.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikel 65 (Comptabilitéit an Opbewahrung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichte vun den Entreprisen</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg →</a></li><li><a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide" class="text-primary-500 hover:text-primary-600 text-sm">De FAIA-Fichier →</a></li><li><a href="/lb/blog/rechnungsarchiveierung-letzebuerg-gesetzlech-dauer-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivéierung vu Rechnungen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Äert Recettebuch, ouni Neierfaassung</h3>
    <p class="text-primary-800 mb-4">faktur.lu baut Äert Recettebuch aus Äre Rechnungen op an exportéiert et als PDF oder CSV fir Är Fiduciaire. Abegraff ab dem Plang Essentiel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'bezuelungsfristen-letzebuerg-rechtleche-kader-2026',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Zu Lëtzebuerg sinn d'Bezuelungsfriste gesetzlech gereegelt. Egal ob Dir enger Entreprise, enger Verwaltung oder enger Privatpersoun eng Rechnung stellt — hei sinn d'Reegelen, déi Är Tresorerie schützen.</p>

<h2>Gesetzlech Bezuelungsfristen</h2>

<h3>B2B-Transaktiounen (tëscht Entreprisen)</h3>

<p>D'<strong>geännert Gesetz vum 18. Abrëll 2004</strong> iwwer d'Bezuelungsfristen an d'Verzuchszënse leet Folgendes fest:</p>

<ul>
    <li><strong>Standardfrist</strong>: 30 Deeg vum Empfang vun der Rechnung un</li>
    <li><strong>Maximal vertraglech Frist</strong>: 60 Deeg</li>
    <li>D'Parteie kënnen eng Frist vun <strong>iwwer 60 Deeg</strong> nëmme vereinbaren, wann dat kee <strong>offensichtleche Mëssbrauch</strong> géintiwwer dem Gleewege duerstellt</li>
</ul>

<p>Dëst Gesetz gouf duerch d'<strong>Gesetz vum 29. Mäerz 2013</strong> geännert, dat d'Direktiv 2011/7/EU ëmsetzt. Eréischt dës Revisioun huet den Zouschlag op 8 Punkte gehéicht an d'Pauschal vun 40 EUR agefouert: Artikelen, déi nëmmen den Text vun 2004 zitéieren, beschreiwen eng iwwerhuelt Reegelung.</p>

<h3>B2G-Transaktiounen (mam ëffentleche Secteur)</h3>

<p>Fir Verwaltunge gëlle méi strikt Fristen:</p>

<ul>
    <li><strong>Maximal Frist</strong>: 30 Deeg vum Empfang vun der Rechnung un</li>
    <li>Dës Frist kann op <strong>maximal 60 Deeg</strong> verlängert ginn, wann dat ausdrécklech am Kontrakt virgesinn an duerch d'Natur vun deem Kontrakt objektiv gerechtfäerdegt ass</li>
    <li>D'Ofnam- oder Iwwerpréifungsprozedur däerf <strong>30 Deeg</strong> net iwwerschreiden</li>
</ul>

<h2>Vu wéini leeft d'Frist?</h2>

<p>D'Bezuelungsfrist fänkt un ze lafen ab:</p>

<ul>
    <li>Dem <strong>Datum vum Empfang vun der Rechnung</strong> beim Schëllner</li>
    <li>Oder dem <strong>Datum vum Empfang vun de Wueren/Déngschtleeschtungen</strong>, wann d'Datum vun der Rechnung onsécher ass oder d'Rechnung virdru geschéckt gouf</li>
    <li>Oder dem <strong>Datum vun der Ofnam oder der Iwwerpréifung</strong>, wann esou eng Prozedur am Kontrakt oder am Gesetz virgesinn ass</li>
</ul>

<p><strong>Tipp:</strong> Gitt d'<strong>Fälegkeetsdatum</strong> ëmmer kloer op Ärer Rechnung un. Mat faktur.lu gëtt et automatesch berechent (standardméisseg 30 Deeg, upassbar).</p>

<h2>Verzuchszënsen</h2>

<p>Bei Verzuch si Zënsen <strong>automatesch</strong> geschëllt, ouni datt eng Mise en demeure néideg wier. De Taux ass awer <strong>net dee selwechte je nodeem wien Äre Client ass</strong> — dat ass déi heefegst Verwiesslung.</p>

<h3>Äre Client ass e Professionnel</h3>

<ul>
    <li><strong>Formel</strong>: den Referenztaux vun der BCE, deen am Ufank vun all Semester am Mémorial B publizéiert gëtt, <strong>plus 8 Prozentpunkten</strong></li>
    <li><strong>1. Semester 2026</strong>: <strong>10,15 %</strong> (2,15 % + 8)</li>
    <li>Den Taux gëtt <strong>all Semester ugepasst</strong>: iwwerpréift dee gëltegen, ier Dir eng Fuerderung bezifferet</li>
    <li>D'Zënse lafen ab dem Dag no der Fälegkeet</li>
</ul>

<h3>Äre Client ass eng Privatpersoun</h3>

<p>D'Reegelung fir Handelstransaktioune gëllt net. Et ass de <strong>gesetzlechen Zënssaz</strong>, dee spillt, all Joer festgeluecht: <strong>3,75 % fir 2026</strong>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Den Ënnerscheed ass bedeitend</p>
    <p>10,15 % géint 3,75 %: Wien engem Konsument den Handelstaux berechent, freet bal dat Dräifacht vun deem, wat geschëllt ass. D'Fuerderung gëtt ufechtbar, an Är Glafwierdegkeet leit genee an deem Moment, wou Dir eescht geholl musst ginn.</p>
</div>

<h2>Pauschal Recouvrementsentschiedegung</h2>

<p>Zousätzlech zu de Verzuchszënsen huet de Gleewege Recht op eng <strong>Pauschal vun 40 EUR</strong> fir Recouvrementskäschten, geschëllt <strong>pro onbezuelter Rechnung</strong>, automatesch an ouni Käschtennoweis.</p>

<p>Wann déi tatsächlech Recouvrementskäschten 40 EUR iwwerschreiden, kann de Gleewege de effektive Betrag géint Virlag vu Beleeger verlaangen.</p>

<p>Och dës Pauschal gehéiert zur Reegelung fir Handelstransaktiounen: si gëtt net vun engem private Client gefuerdert.</p>

<h2>Bewäert Praxis op Äre Rechnungen</h2>

<p>Fir Iech am Sträitfall ofzesécheren, gitt op all Rechnung un:</p>

<ul>
    <li>Dat genaut <strong>Fälegkeetsdatum</strong> (net nëmmen „30 Deeg")</li>
    <li>D'<strong>Bezuelungsmodalitéiten</strong> (Iwwerweisung, mat IBAN)</li>
    <li>D'Mentioun vun de gëltege <strong>Verzuchszënsen</strong></li>
    <li>D'Mentioun vun der <strong>Pauschal vun 40 EUR</strong> fir Är professionell Clienten</li>
</ul>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/facturation/encaissement/interets-retard.html" target="_blank" rel="noopener">Guichet.lu – Bezuelungsfristen a Verzuchszënsen</a></li>
    <li><a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministère – Gesetzlechen Zënssaz (all Semester aktualiséiert)</a></li>
    <li><a href="https://data.legilux.public.lu/filestore/eli/etat/leg/loi/2004/04/18/n8/jo/fr/html/eli-etat-leg-loi-2004-04-18-n8-jo-fr-html.html" target="_blank" rel="noopener">Legilux – Gesetz vum 18. Abrëll 2004 (Originaltext)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/client-mahnen-net-bezuelt-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">E Client relancéieren →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Geréiert Är Bezuelungsfristen mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu berechent d'Fälegkeetsdaten automatesch, erkennt Verspéidungen a schéckt Relancen. Behaalt d'Kontroll iwwer Är Tresorerie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Semester ze préiwen</p>
    <p>Den Taux fir Handelstransaktiounen ännert zweemol am Joer. Dës Säit gëtt reegelméisseg aktualiséiert — mä ier Dir eng Fuerderung bezifferet, préift den Taux vum lafende Semester beim <a href="https://mj.gouvernement.lu/fr/service-citoyens/taux-interet-legal.html" target="_blank" rel="noopener">Justizministère</a>, a fir Är perséinlech Situatioun frot Är Fiduciaire.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'innergemeinschaftlech-tva-guide-letzebuergesch-entreprisen',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Fakturéiert Dir Clienten an anere Länner vun der Europäescher Unioun? D'<strong>innergemeinschaftlech TVA</strong> follegt spezifesche Reegelen, déi all Lëtzebuerger Entrepreneur beherrsche soll. Dëse Guide erkläert se Iech.</p>

<h2>Wat ass d'innergemeinschaftlech TVA?</h2>

<p>D'innergemeinschaftlech TVA ass d'Regime, dat op den Handel mat Wueren an Déngschtleeschtungen tëscht Entreprisen a verschiddene Länner vun der <strong>Europäescher Unioun</strong> applizéiert gëtt. D'Grondprinzip ass d'<strong>Autoliquidatioun</strong>: net de Verkeefer, mä de Keefer deklaréiert a bezuelt d'TVA a sengem Land.</p>

<h2>D'innergemeinschaftlech TVA-Nummer</h2>

<p>Zu Lëtzebuerg huet Är innergemeinschaftlech TVA-Nummer d'Format <strong>LU + 8 Zifferen</strong> (Beispill: LU12345678). Dës Nummer ass:</p>

<ul>
    <li>Vun der <strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA) zougedeelt</li>
    <li>Obligatoresch fir all innergemeinschaftlech Transaktioun</li>
    <li>Iwwer de <strong>VIES-System</strong> vun der Europäescher Kommissioun iwwerpréifbar</li>
</ul>

<p><strong>Tipp:</strong> faktur.lu iwwerpréift TVA-Nummeren automatesch iwwer VIES, wann Dir en innergemeinschaftleche Client uleet.</p>

<h2>Reegele vun der innergemeinschaftlecher Fakturatioun</h2>

<h3>Verkaf vu B2B-Déngschtleeschtungen (heefegste Fall)</h3>

<p>Wann Dir eng Déngschtleeschtung un eng Entreprise an engem anere EU-Land verkeeft:</p>

<ol>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong></li>
    <li>Dir vermierkt op der Rechnung: <strong>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</strong> (den Artikel 44 vun der Direktiv bestëmmt den Ort vun der Besteierung; et ass den Artikel 196, deen de Client als Schëllner bezeechent an der obligatorescher Mentioun entsprécht – kuckt Art. 226 §11bis vun der Direktiv)</li>
    <li>Dir gitt Är TVA-Nummer <strong>an</strong> déi vum Client un</li>
    <li>De Client deklaréiert d'TVA a sengem eegene Land (Reverse-Charge-Mechanismus)</li>
</ol>

<h3>Verkaf vu B2B-Wueren</h3>

<p>Fir Wuerelieferungen un eng EU-Entreprise:</p>

<ol>
    <li>Dir fakturéiert <strong>ouni TVA</strong> (befreit innergemeinschaftlech Liwwerung)</li>
    <li>Dir vermierkt: <strong>„Befreit innergemeinschaftlech Liwwerung – Artikel 138 vun der Direktiv 2006/112/EG"</strong></li>
    <li>Dir musst beweisen, datt d'Wueren Lëtzebuerg verlooss hunn</li>
    <li>D'Transaktioun muss an Ärem <strong>Récapitulatif</strong> erschéngen</li>
</ol>

<h3>Verkaf un eng Privatpersoun (B2C)</h3>

<p>Fir Verkeef un Privatpersounen an der EU gëllen aner Reegelen. Eng <strong>eenzeg Schwell vun 10 000 € pro Joer</strong> ass entscheedend:</p>

<ul>
    <li><strong>Ënner 10 000 € pro Joer</strong>: Dir fakturéiert d'<strong>Lëtzebuerger TVA</strong>, wéi bei engem lokale Client</li>
    <li><strong>Iwwer 10 000 €</strong>: Dir applizéiert d'<strong>TVA vum Land vum Client</strong> an deklaréiert se iwwer de <strong>OSS</strong>-Guichet</li>
    <li><strong>Dës Schwell ass gemeinsam</strong> fir Fernverkeef vu Wueren <em>an</em> elektronesch, Telekom- an audiovisuell Déngschtleeschtungen – iwwer all EU-Länner zesummen (ouni Lëtzebuerg). Ze verfollegen ass dofir d'<strong>Zomm</strong> vun Äre europäesche B2C-Verkeef, net all Kategorie eenzel</li>
    <li><strong>Aner Déngschtleeschtungen</strong> (Beroodung, Formatioun a Presenz…): normalerweis Lëtzebuerger TVA, mat Ausnamen no der Aart vun der Leeschtung</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">D'Iwwerschreidung wierkt direkt</p>
    <p>D'Operatioun, déi iwwer 10 000 € geet, ass <strong>scho</strong> am Land vum Client steierflichteg. Fréier Verkeef bleiwen zu Lëtzebuerg steierflichteg. Verfollegt dofir Är Zomm am Laf vum Joer, amplaz se beim Ofschloss z'entdecken.</p>
</div>

<h2>Obligatoresch Deklaratiounen</h2>

<p>Als Lëtzebuerger Entreprise mat innergemeinschaftlechen Operatioune musst Dir:</p>

<ul>
    <li><strong>Periodesch TVA-Deklaratioun</strong>: Är innergemeinschaftlech Operatiounen an de virgesinne Felder uginn</li>
    <li><strong>Récapitulatif</strong>: monatlech oder trimestriell Opstellung vun alle innergemeinschaftleche Verkeef pro Client a Land</li>
    <li><strong>Intrastat</strong>: statistesch Deklaratioun fir Wuerebeweegungen iwwer gewësse Schwellen</li>
</ul>

<h2>VIES-Validatioun: firwat dat entscheedend ass</h2>

<p>Ier Dir engem EU-Client ouni TVA fakturéiert, <strong>musst Dir iwwerpréiwen</strong>, ob seng TVA-Nummer gëlteg ass, iwwer de <strong>VIES-System</strong> (VAT Information Exchange System). Wann d'Nummer ongëlteg ass:</p>

<ul>
    <li>Musst Dir <strong>mat Lëtzebuerger TVA</strong> fakturéieren</li>
    <li>Riskéiert Dir eng <strong>Steiernofuerderung</strong>, wann Dir ouni Iwwerpréiwung ouni TVA fakturéiert</li>
    <li>Haalt e <strong>Beweis vun der VIES-Iwwerpréiwung</strong> op (Screenshot oder Log)</li>
</ul>

<p>faktur.lu iwwerpréift all innergemeinschaftlech TVA-Nummer automatesch a protokolléiert d'Validatioun.</p>

<h2>Heefeg Praxisfäll</h2>

<h3>Lëtzebuerger Beroder fakturéiert en däitsche Client</h3>
<p>Dir fakturéiert ouni TVA mat der Mentioun vun der Autoliquidatioun. Den däitsche Client deklaréiert déi däitsch TVA (19 %) a senger eegener Deklaratioun. Dir deklaréiert d'Operatioun an Ärem Récapitulatif.</p>

<h3>Lëtzebuerger Webagence fakturéiert e franséische Client</h3>
<p>Selwecht Prinzip: Rechnung ouni TVA, Autoliquidatioun. De franséische Client deklaréiert 20 % franséisch TVA. Dir musst seng TVA-Nummer op VIES iwwerpréiwen ier Dir fakturéiert.</p>

<h3>Lëtzebuerger E-Commerce verkeeft un eng belsch Privatpersoun</h3>
<p>Soulaang Är kumuléiert europäesch B2C-Verkeef (Fernverkeef + elektronesch Déngschtleeschtungen) ënner 10 000 EUR/Joer bleiwen, fakturéiert Dir d'Lëtzebuerger TVA. Doriwwer applizéiert Dir d'TVA vum Land vum Client (21 % an der Belsch) iwwer de Regime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Obligatoresch Mentiounen op der Rechnung</h2>

<p>All innergemeinschaftlech Rechnung muss enthalen:</p>

<ul>
    <li>Är Lëtzebuerger TVA-Nummer</li>
    <li>D'TVA-Nummer vum Client</li>
    <li>D'gesetzlech Mentioun vun der Befreiung (Autoliquidatioun oder innergemeinschaftlech Liwwerung)</li>
    <li>De Betrag ouni TVA an d'Mentioun „TVA 0 %"</li>
</ul>

<p>faktur.lu erkennt d'TVA-Szenario automatesch no Land an Zort vu Client, an applizéiert déi richteg gesetzlech Mentiounen.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun TVA-Nummeren (Europäesch Kommissioun)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Direktiv 2006/112/EG (konsolidéierten Text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/tva-letzebuerg-tariffer-berechnung-obligatiounen" class="text-primary-500 hover:text-primary-600 text-sm">TVA zu Lëtzebuerg →</a></li><li><a href="/lb/blog/vu-letzebuerg-aus-ausland-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">An d'Ausland fakturéieren →</a></li><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Verglach: Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert an der ganzer EU konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt innergemeinschaftlech TVA-Szenarien automatesch, iwwerpréift TVA-Nummeren iwwer VIES an applizéiert déi richteg gesetzlech Mentiounen.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>Lëtzebuerger Schwellen, Tariffer a Steierprozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot awer Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'peppol-b2g-letzebuerg-komplette-guide-2026',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Zanter 2022-2023 ass d'elektronesch Fakturatioun iwwer <strong>Peppol</strong> fir d'Fournisseure vum ëffentleche Secteur zu Lëtzebuerg obligatoresch, <strong>egal wéi héich de Rechnungsbetrag ass</strong>. Dëse Guide erkläert Iech alles, wat Dir 2026 fir d'Konformitéit wësse musst.</p>

<h2>Wat ass Peppol?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> ass en internationaalt Netzwierk fir elektronesch Fakturatioun, iwwer dat Geschäftsdokumenter (Rechnungen, Bestellungen) standardiséiert tëscht Entreprisen an ëffentleche Verwaltungen ausgetosch ginn. Bedriwwe vun <a href="https://peppol.org" target="_blank" rel="noopener">OpenPeppol</a>, erstreckt et sech mëttlerweil op iwwer 100 Länner an zielt e puer Millioune registréiert Participanten.</p>

<p>Zu Lëtzebuerg ass Peppol de offizielle Kanal fir d'elektronesch B2G-Fakturatioun (Business-to-Government). All Entreprise, déi dem Staat, de Gemengen oder ëffentlechen Etablissementer Rechnunge stellt, muss dëst Format notzen.</p>

<h2>Wien ass betraff?</h2>

<p>Wann Dir eng vun dëse Stellen ubelieft, musst Dir iwwer Peppol fakturéieren:</p>

<ul>
    <li><strong>De Lëtzebuerger Staat</strong> a seng Ministèren</li>
    <li><strong>D'Lëtzebuerger Gemengen</strong></li>
    <li><strong>Déi ëffentlech Etablissementer</strong> (Spideeler, Schoulen asw.)</li>
    <li><strong>All ëffentlech Maartverträg a Konzessiounskontrakter</strong>, onofhängeg vum Betrag</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Kee Betragsseuil</p>
    <p>Am Géigesaz zu enger verbreeter Virstellung gëtt et <strong>kee Mindestseuil</strong> (zum Beispill „30 000 €") fir d'Flicht. D'<a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Gesetz vum 13. Dezember 2021</a> schreift d'elektronesch Fakturatioun fir <strong>all</strong> Rechnung am Kader vun engem ëffentleche Maart oder engem Konzessiounskontrakt vir, vum klengsten bis zum gréisste Betrag.</p>
</div>

<h3>Kalenner vun der Aféierung</h3>

<p>D'Flicht ass gestaffelt a Kraaft getrueden, no der Gréisst vum Wirtschaftsteilnehmer:</p>

<ul>
    <li><strong>18. Mee 2022</strong>: grouss Wirtschaftsteilnehmer</li>
    <li><strong>18. Oktober 2022</strong>: mëttelgrouss Wirtschaftsteilnehmer</li>
    <li><strong>18. Mäerz 2023</strong>: kleng Teilnehmer an nei gegrënnten Entreprisen</li>
</ul>

<p>Zanter dem 18. Mäerz 2023 sinn also <strong>all</strong> Fournisseure vum Lëtzebuerger ëffentleche Secteur betraff.</p>

<h2>Wéi funktionéiert Peppol?</h2>

<p>D'Peppol-Netzwierk baséiert op engem Véier-Ecken-Modell (4-corner model):</p>

<ol>
    <li><strong>Den Ofsender</strong> (Är Entreprise) erstellt d'Rechnung</li>
    <li><strong>De sendenden Zougangspunkt</strong> (Är Fakturatiounssoftware oder hire Access Point) stellt d'Rechnung an d'Peppol-Netz</li>
    <li><strong>De empfaangenden Zougangspunkt</strong> (op der Säit vun der Verwaltung) hëlt d'Rechnung entgéint</li>
    <li><strong>Den Empfänger</strong> (déi ëffentlech Verwaltung) verschafft d'Rechnung</li>
</ol>

<p>All Netzteilnehmer gëtt duerch eng eenzegaarteg <strong>Peppol Participant ID</strong> identifizéiert. Zu Lëtzebuerg ass d'Standardschema <strong>9938</strong> (LU:VAT), op Basis vun der TVA-Nummer. Format: <code>9938:LU########</code>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">⚠ Klassesche Feeler: Schema 0184</p>
    <p>Vill Quelle verwiessele dat. D'Schema <strong>0184</strong> gehéiert zum <strong>däneschen</strong> Entreprisesregëster (DIGSTORG/CVR), net zu Lëtzebuerg. Dat richtegt LU-Schema ass <strong>9938</strong>. Préift et an der <a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">offizieller EAS-Codelëscht vu Peppol</a>.</p>
</div>

<h2>D'Format UBL a Peppol BIS Billing 3.0</h2>

<p>Peppol-Rechnungen zu Lëtzebuerg notzen d'Format <strong>Peppol BIS Billing 3.0</strong>, op Basis vum XML-Standard <strong>UBL (Universal Business Language)</strong> a konform mat der europäescher Norm <strong>EN 16931</strong>. Är Rechnung muss enthalen:</p>

<ul>
    <li>D'Informatioune vum Ofsender (Numm, Adress, TVA-Nummer)</li>
    <li>D'Informatioune vum Empfänger (Numm, Peppol Participant ID)</li>
    <li>D'Rechnungslinnen (Beschreiwung, Quantitéit, Eenheetspräis)</li>
    <li>D'TVA-Beträg no Tariffer opgedeelt</li>
    <li>D'Totaler (ouni TVA, TVA, mat TVA)</li>
    <li>D'Bestell- oder Kontraktreferenzen</li>
</ul>

<p>D'Format <strong>XRechnung 3.0.1</strong> (däitsch Norm, konform mat EN 16931) gëtt vun de Lëtzebuerger Verwaltungen och akzeptéiert.</p>

<h2>Alternativen, wann Dir net u Peppol ugebonne sidd</h2>

<p>Hutt Dir nach keng Software, déi u Peppol ugebonnen ass, steet Iech den offiziellen Alternativkanal op:</p>

<ul>
    <li><strong>MyGuichet.lu</strong>: Onlineformulairen, iwwer déi eng konform elektronesch Rechnung manuell un déi empfaangend Verwaltunge kann iwwerdroe ginn</li>
</ul>

<p>Déi komplett Prozedur fannt Dir op <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu – Iwwerdroung vun enger elektronescher Rechnung fir en ëffentleche Maart</a>.</p>

<h2>Peppol a faktur.lu: wat haut disponibel ass</h2>

<p>Mir si bei dësem Punkt léiwer präzis, wéi datt mir eng Case ukräizen.</p>

<p><strong>Ab elo disponibel:</strong> faktur.lu generéiert de <strong>Peppol-BIS-Billing-3.0-Fichier (UBL)</strong> vun Äre Rechnungen, konform mam Format, dat de Lëtzebuerger ëffentleche Secteur verlaangt. Dir luet en aus der Rechnung erof an iwwerdroet en iwwer de Kanal vun Ärer Wiel — besonnesch iwwer den Zougangspunkt vum <strong>CTIE</strong>, deen ëffentlech Organismen ouni eegene Punkt notzen.</p>

<p><strong>An der Produktivsetzung:</strong> déi automatesch Iwwerdroung iwwer en integréierten Zougangspunkt, déi Iech dëse manuelle Schrëtt erspuert. D'Format ass technesch prett, den Deploiement fir all Konten nach net. Mir aktualiséieren dës Säit, soubal et souwäit ass.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Bis dohinner sidd Dir a Rei</p>
    <p>D'gesetzlech Flicht betrëfft d'<strong>Format</strong> vun der Rechnung an hir elektronesch Iwwerdroung, net d'Instrument, mat deem se zougestallt gëtt. E konforme BIS-3.0-Fichier, iwwer de Kanal vun Ärem ëffentlechen Optraggeber agereecht, erfëllt d'Flicht.</p>
</div>

<h2>Virdeeler vun der Peppol-Fakturatioun</h2>

<ul>
    <li><strong>Méi séier Veraarbechtung</strong>: Peppol-Rechnunge ginn automatesch veraarbecht, wat d'Bezuelungsfristen ofkierzt</li>
    <li><strong>Manner Feeler</strong>: dat strukturéiert Format eliminéiert Erfaassungsfeeler</li>
    <li><strong>Nofollegbarkeet</strong>: all Rechnung ass am Netz duerchgängeg nofollegbar</li>
    <li><strong>Konformitéit</strong>: Dir erfëllt d'Lëtzebuerger gesetzlech Flichten (Gesetz vum 13. Dezember 2021)</li>
    <li><strong>International Reechwäit</strong>: Peppol ass mëttlerweil an iwwer <strong>100 Länner</strong> am Asaz, mat Dosende vun nationale Peppol Authorities</li>
</ul>

<h2>Heefeg Froen</h2>

<h3>Ass d'Peppol-Fakturatioun am B2B obligatoresch?</h3>

<p>Zu Lëtzebuerg fir inlännescht B2B nach net. De europäesche Paquet <strong>ViDA (VAT in the Digital Age)</strong>, 2025 ugeholl, gesäit d'Flicht zur elektronescher Fakturatioun fir <strong>innergemeinschaftlech B2B-Operatiounen</strong> ab dem <strong>1. Juli 2030</strong> vir. Fir dat inlännescht B2B ass déi vollstänneg Harmoniséierung bis 2035 virgesinn. Kuckt <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäesch Kommissioun – ViDA</a>.</p>

<h3>Wat kascht den Envoi iwwer Peppol?</h3>

<p>D'Generéierung vum Peppol-BIS-3.0-Fichier ass an Ärem faktur.lu-Abonnement abegraff, ouni Opschlag pro Rechnung. D'Peppol-Netz selwer stellt dem Ofsender näischt a Rechnung: eventuell Käschten hänke vum benotzten Zougangspunkt of.</p>

<h3>Wéi fannen ech d'Peppol-ID vun enger Verwaltung?</h3>

<p>D'Peppol Participant IDs vun de Lëtzebuerger Verwaltunge stinn am <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Dir kënnt se och direkt a faktur.lu beim Uleeë vum Client sichen.</p>

<h3>Wéi eng Peppol-Schema gëllt zu Lëtzebuerg?</h3>

<p>D'Standardschema ass <strong>9938</strong> (LU:VAT, op Basis vun der TVA-Nummer). Vollstännegt Format: <code>9938:LU########</code>. Net mat 0184 ze verwiesselen, dem däneschen Schema.</p>

<h3>Wéi eng Sanktioune riskéiert een bei Netkonformitéit?</h3>

<p>D'Verwaltung kann d'Veraarbechtung vun enger net konformer Pabeier- oder PDF-Rechnung refuséieren an hir nei Iwwerdroung iwwer Peppol verlaangen, wat d'Bezuelung verzögert. Fir d'Detailer vun de formelle Sanktioune kuckt d'Gesetz vum 13. Dezember 2021 oder frot Är Fiduciaire.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>De ViDA-Kalenner, d'Seuiler an d'Peppol Authorities entwéckele sech. Dës Säit gëtt reegelméisseg aktualiséiert — fir Är Situatioun kuckt <a href="https://guichet.public.lu/" target="_blank" rel="noopener">Guichet.lu</a> an déi offiziell Peppol-Dokumentatioun.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Gesetz vum 13. Dezember 2021 iwwer d'elektronesch B2G-Fakturatioun</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu – Iwwerdroung vun enger elektronescher Rechnung</a></li>
    <li><a href="https://mindigital.gouvernement.lu/fr/dossiers/2021/facturation-electronique.html" target="_blank" rel="noopener">Digitaliséierungsministère – Elektronesch Fakturatioun</a></li>
    <li><a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">Peppol – EAS-Codelëscht (Identifikatiounsschemae)</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäesch Kommissioun – VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/factur-x-zugferd-europaesch-elektronesch-rechnungsstellung" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Fakturatiounssoftware wielen →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger LU-Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Prett fir iwwer Peppol ze fakturéieren?</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert de konforme Peppol-BIS-Billing-3.0-Fichier vun Äre Rechnungen, prett fir un den ëffentleche Secteur iwwerdroen ze ginn. Leet Äre gratis Kont un an testt et a wéinege Minutten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'rechnungsarchiveierung-letzebuerg-gesetzlech-dauer-format',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">D'Archivéierung vu Rechnungen ass zu Lëtzebuerg eng gesetzlech Flicht: <strong>zéng Joer</strong>. Mä d'Fro vum <em>Ufank</em> vun dëser Frist, an déi vum <em>Plaz</em>, wou d'Archive leie kënnen, sinn méi genee gereegelt wéi ee mengt. Hei steet, wat den Artikel 65 vum TVA-Gesetz tatsächlech seet.</p>

<h2>Gesetzlech Opbewahrungsdauer</h2>

<h3>Rechnungen: zéng Joer vum Ausstellungsdatum un</h3>

<p>Den <strong>Artikel 65, Paragraf 4</strong> vum <a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">TVA-Gesetz</a> ass eendeiteg:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    „Dës Rechnungen a Rechnungskopie musse fir eng Period vun zéng Joer <strong>vun hirem Ausstellungsdatum un</strong> gespäichert ginn."
</blockquote>

<p>Eng den <strong>15. Mäerz 2026</strong> ausgestallt Rechnung muss also bis den <strong>15. Mäerz 2036</strong> opbewahrt ginn — an net bis Enn 2036. D'Reegel gëllt fir ausgestallt wéi fir erhalen Rechnungen.</p>

<h3>Bicher an aner Dokumenter</h3>

<p>Fir déi aner Stécker ännert sech den Ufank vun der Frist:</p>

<ul>
    <li><strong>Comptabilitéitsbicher</strong>: zéng Joer vun hirem <strong>Ofschloss</strong> un</li>
    <li><strong>Aner Dokumenter</strong>: zéng Joer vun hirem <strong>Datum</strong> un</li>
    <li><strong>Registere vun elektroneschen Interfaces</strong> (Marchéplazen, Plattformen): zéng Joer vum <strong>31. Dezember vum Joer vun der Operatioun</strong> un</li>
</ul>

<p>Dobäi kënnt déi allgemeng Flicht vum <strong>Handelsgesetzbuch</strong> (Artikel 16), dat och zéng Joer fir Bicher a Geschäftskorrespondenz virgesäit.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Eng heefeg Verwiesslung</p>
    <p>Vill Guiden loossen d'Frist fir Rechnungen vum Enn vum Exercice un lafen. Dat ass d'Reegel fir <strong>Bicher</strong>, net fir Rechnungen. An der Praxis féiert eng Ausriichtung vun alle Läschungen um Exercice-Enn dozou, datt Dir e bësse méi laang opbewahrt wéi néideg: virsiichteg, mä net d'Reegel.</p>
</div>

<h2>Wou däerft Dir Är Archive späicheren?</h2>

<p>Dat ass d'Fro, déi sech bei all Onlinedéngscht stellt, an den Artikel 65 äntwert doropper am Paragraf 6.</p>

<h3>De Prinzip</h3>

<p>Dir <strong>bestëmmt d'Späicherplaz fräi</strong>, ënner enger Bedingung: der Administratioun „<strong>ouni ongerechtfäerdegt Verspéidung, op all Ufuerderung vun hirer Säit</strong>" all d'Rechnungen, Informatiounen, Bicher an Dokumenter zur Verfügung ze stellen.</p>

<h3>D'Grenzen</h3>

<ul>
    <li><strong>Länner ouni géigesäiteg Hëllef</strong>: et ass verbueden, an engem Land oder Territoire ze späicheren, mat deem et keen Instrument vu géigesäiteger Hëllef vu vergläichbarer Reechwäit gëtt, an och kee elektronescht Zougrëffsrecht</li>
    <li><strong>Pabeierarchiven</strong>: en zu Lëtzebuerg etabléierten Assujetti muss seng Rechnungen do späicheren, <strong>wann d'Späicherung net elektronesch</strong> mat komplettem Online-Zougrëff geschitt. Konkret: Är Classeure bleiwen zu Lëtzebuerg</li>
    <li><strong>Obligatoresch Deklaratioun</strong>: läit d'Späicherplaz <strong>ausserhalb vum Lëtzebuerger Territoire</strong>, musst Dir se der Administratioun deklaréieren — an der <strong>Jooresdeklaratioun</strong> no Artikel 64, Paragraf 7</li>
    <li><strong>Späicherung an engem anere Member-Staat</strong>: Dir musst den Agente vun der Administratioun e Recht op <strong>elektroneschen Zougrëff, Eroflueden a Notzung</strong> vun dëse Rechnunge garantéieren</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ze préiwen, wann Dir en Onlinedéngscht notzt</p>
    <p>Frot Äre Prestataire, <strong>wou d'Donnéeën physesch gehost ginn</strong>. Leien d'Serveren ausserhalb vu Lëtzebuerg, gëllt d'Deklaratiounsflicht an Ärer Jooresdeklaratioun — och bei engem europäeschen Déngscht. Eng einfach Formalitéit, mä eng, déi ee liicht vergësst.</p>
</div>

<h2>Akzeptéiert Archivéierungsformater</h2>

<p>Den Artikel 65, Paragraf 5, stellt d'inhaltlech Ufuerderung: d'<strong>Autentizitéit vun der Hierkonft</strong>, d'<strong>Integritéit vum Inhalt</strong> an d'<strong>Liesbarkeet</strong> mussen déi ganz Späicherperiod laang garantéiert sinn.</p>

<h3>Pabeierarchivéierung</h3>
<p>Pabeieroriginaler musse dréchen an accessibel opbewahrt ginn, ouni Verännerung an — wéi hei uewen gesinn — um Lëtzebuerger Territoire.</p>

<h3>Digital Archivéierung</h3>
<p>D'elektronesch Späicherung ass gëlteg, „ënner der Bedingung, datt d'Donnéeën, déi d'Autentizitéit vun der Hierkonft an d'Integritéit vum Inhalt garantéieren, och elektronesch gespäichert ginn". An der Praxis:</p>

<ul>
    <li>D'Format muss d'<strong>Integritéit</strong> vum Dokument garantéieren</li>
    <li>D'Dokument muss déi ganz Dauer laang <strong>liesbar</strong> bleiwen</li>
    <li>D'Format <strong>PDF/A</strong> (ISO 19005) gëtt fir d'Laangzäitliesbarkeet recommandéiert</li>
    <li>En <strong>digitalen Ofdrock</strong> (Hash) beweist, datt d'Dokument net verännert gouf</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Digitaliséierung a Beweiskraaft</p>
    <p>Fir datt eng elektronesch Kopie déi selwecht <strong>Beweiskraaft</strong> wéi den Originalpabeier huet, knäppt d'<a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vum 25. Juli 2015</a> dës Vermutung un de Recours op e certifiéierte <a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">PSDC</a> (Prestataire vun Dematerialiséierungs- oder Konservatiounsdéngschter). Ouni PSDC ze digitaliséiere bleift méiglech, mä d'Kopie profitéiert net vun dëser Vermutung: bei enger Kontestatioun musst Dir hir Treiheet selwer beweisen.</p>
</div>

<h2>Firwat d'Format PDF/A?</h2>

<p><strong>PDF/A</strong> ass eng ISO-Norm, déi fir d'Laangzäitarchivéierung entworf gouf. Am Géigesaz zu engem Standard-PDF:</p>

<ul>
    <li>Et <strong>embarquéiert all benotzte Schrëften</strong> (keng extern Ofhängegkeet)</li>
    <li>Et <strong>verbitt JavaScript</strong> a Multimedia-Elementer</li>
    <li>Et zielt drop of, ze garantéieren, datt d'Dokument an <strong>zéng, zwanzeg oder fofzeg Joer liesbar</strong> bleift</li>
    <li>Et ass bei europäesche Verwaltunge <strong>wäit unerkannt</strong></li>
</ul>

<h2>Rechnunge mat faktur.lu archivéieren</h2>

<p>faktur.lu bitt eng eege Archivéierung un, disponibel an de Pläng, déi d'Funktionalitéit enthalen:</p>

<ol>
    <li>Dir archivéiert eng <strong>finaliséiert</strong> Rechnung — eenzel oder am Lot</li>
    <li>D'Dokument gëtt standardméisseg an <strong>PDF/A-1b</strong> konvertéiert; <strong>PDF/A-3b</strong> ass disponibel, wann Dir Uschlëss musst embarquéieren</li>
    <li>En <strong>SHA-256-Ofdrock</strong> gëtt berechent a gespäichert, sou datt d'Integritéit vum Archiv duerno kann iwwerpréift ginn</li>
    <li>Eng <strong>Zéngjoresfrist</strong> gëtt mam Archiv hannerluecht</li>
    <li>Dir kënnt Är Archiven zu all Moment eroflueden</li>
</ol>

<p class="text-sm text-slate-500"><em>Technesch Präzisioun: d'PDF/A-Konversioun stëtzt sech serversäiteg op Ghostscript. Ass d'Tool net disponibel, gëtt d'Dokument als Standard-PDF mat sengem Ofdrock opbewahrt — dat tatsächlech erreechte Format gëtt mam Archiv gespäichert, Dir wësst also ëmmer, wat Dir hutt.</em></p>

<h2>Risike bei Netkonformitéit</h2>

<p>Bei enger Steierkontroll kann d'Feele vu Rechnungen oder eng net konform Archivéierung Folgendes no sech zéien:</p>

<ul>
    <li>De <strong>Refus vum TVA-Ofzuch</strong> op de feelende Rechnungen</li>
    <li><strong>Administrativ Geldstrofen</strong> (250 € bis 10 000 € pro Infraktioun, Art. 77 LIVA), a bis zu <strong>25 000 € pro Dag Verspéidung</strong> no engem Avertissement, wann d'Stécker bei enger Kontroll net virgeluecht ginn</li>
    <li>Eng <strong>Taxatioun vun Amts wéinst</strong> duerch d'Administratioun</li>
</ul>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikel 65 (Opbewahrung a Späicherung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichte vun den Entreprisen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2015/07/25/n1/jo" target="_blank" rel="noopener">Gesetz vum 25. Juli 2015 iwwer déi elektronesch Archivéierung</a></li>
    <li><a href="https://ilnas.gouvernement.lu/" target="_blank" rel="noopener">ILNAS – Certifiéiert PSDC-Prestataire</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Fichier →</a></li><li><a href="/lb/blog/steierprefung-letzebuerg-virbereden" class="text-primary-500 hover:text-primary-600 text-sm">Steierkontroll →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">PDF/A-Archivéierung mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">Archivéiert Är finaliséiert Rechnungen an PDF/A mat SHA-256-Ofdrock an Zéngjoresfrist, eenzel oder am Lot.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Opbewahrungsreegele kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'rechnungssoftware-letzebuerg-richteg-wielen-verglach',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">A kuerz</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 Critèren</strong> fir ze wielen: Lëtzebuerger Konformitéit (Mentiounen Art. 63, Nummeréierung, 4 TVA-Tariffer, Franchise 50 000 €), kompletten Zyklus (Devisen, Avoiren, récurrent Rechnungen), Einfachheet, Komplettpräis, Sécherheet (EU-Hosting, 2FA), Integratioun mat der Fiduciaire.</li><li>De <strong>FAIA</strong> gëtt net vun alle verlaangt: véier kumulativ Bedingunge gëllen. En bleift awer de Critère, deen Iech schützt, <strong>wann Dir wuesst</strong>.</li><li>Oppassen bei Lockpräisser: vergläicht, wat <strong>tatsächlech abegraff</strong> ass, net nëmmen den ugewisenen Tarif.</li></ul></div>
<p class="lead">Eng Fakturatiounssoftware als Onofhängegen oder KMU zu Lëtzebuerg ze wielen heescht net, nach en „Rechnungstool" ze wielen. Et heescht, en Instrument ze wielen, dat Iech <strong>mat de ganz spezifesche Reegele vum Grand-Duché konform</strong> mécht (obligatoresch Mentiounen, TVA, Nummeréierung, Archivéierung). Hei sinn déi 6 Critèren, déi wierklech zielen, a wéi Dir se evaluéiert.</p>

<h2>Firwat Lëtzebuerg den Ënnerscheed mécht</h2>
<p>Eng „generesch" Fakturatiounssoftware (franséisch, belsch, international) erfëllt dacks d'Grondufuerderungen, ignoréiert awer d'Lëtzebuerger Flichten. D'Administration de l'enregistrement, des domaines et de la TVA (AED) kann bei enger Kontroll genee gesetzlech Mentiounen, eng onbeanstandbar Nummeréierung an — ënner gewësse Bedingungen — en Auditfichier an engem präzise Format verlaangen. Déi richteg Software muss also <strong>fir</strong> Lëtzebuerg geduecht sinn, net nëmmen „zu" Lëtzebuerg disponibel.</p>

<h2>Critère 1: d'Lëtzebuerger Konformitéit</h2>
<p>Dat ass den zentrale Critère. Iwwerpréift genee:</p>
<ul>
    <li><strong>Obligatoresch Mentiounen (Artikel 63 LIVA)</strong>: Kontaktdaten, TVA-Nummer, eenzeg fortlafend Nummer, Tariffer a Betrag — automatesch a korrekt ugebruecht.</li>
    <li><strong>Fortlafend Nummeréierung ouni Ënnerbriechung</strong>: ouni Lack an ouni Duebel, gespaart soubal d'Rechnung finaliséiert ass.</li>
    <li><strong>Déi 4 Lëtzebuerger TVA-Tariffer</strong>: 17 % (normal), 14 % (intermediär), 8 % (reduzéiert) an 3 % (superreduzéiert), no dem richtege Szenario applizéiert.</li>
    <li><strong>Franchiseregime</strong>: Gestioun vum Seuil vun <strong>50 000 €</strong> (Art. 57bis LIVA) mat der passender Mentioun an engem Alert bei Untéierung.</li>
    <li><strong>International TVA-Szenarien</strong>: innergemeinschaftlech Autoliquidatioun, Export, Franchise — mat der automatesch gesatener gesetzlecher Mentioun.</li>
    <li><strong>Archivéierung iwwer 10 Joer</strong>, an engem Format fir d'Laangzäitliesbarkeet (PDF/A) mat engem Integritéitsofdrock.</li>
    <li><strong>FAIA-2.01-Export</strong>: kuckt hei ënnen, de Critère ass méi nuancéiert wéi dacks gesot gëtt.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">De FAIA betrëfft net jiddereen</p>
    <p>Vill Verglach — bis elo och dës Säit — stellen de FAIA-Export als eliminatoresch duer. Dat ass iwwerdriwwen. No der FAQ vun der AED setzt d'Flicht <strong>véier gläichzäiteg erfëllte Bedingungen</strong> viraus: dem normaliséierte Comptesplang ënnerleien, kee vereinfachte Regime a Usproch huelen, iwwer 112 000 € Ëmsaz realiséieren, an ongeféier 500 comptabel Transaktioune pro Joer iwwerschreiden. Vill Onofhängeger falen haut net dorënner.</p>
    <p class="mt-2">Dat mécht de Critère net wäertlos: en gëtt entscheedend <strong>um Dag, wou Är Aktivitéit dës Seuiler iwwerschreit</strong>, an d'Instrument zu deem Moment ze wiesselen kascht vill méi, wéi et virgesinn ze hunn. Kuckt eise <a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide" class="text-primary-500 hover:text-primary-600">komplette FAIA-Guide</a>.</p>
</div>

<h2>Critère 2: déi wesentlech Funktionalitéiten</h2>
<p>Iwwer d'Rechnung eraus brauch Är Aktivitéit e kompletten Zyklus: <strong>Devisen</strong> (a Rechnungen ëmwandelbar), <strong>Avoiren / Kreditnoten</strong>, <strong>Akontsrechnungen</strong> (mat der richteger TVA-Exigibilitéit), <strong>récurrent Rechnungen</strong>, Verfollegung vun den <strong>Ausgaben</strong> an der <strong>ofzéibarer TVA</strong>, Multi-Devise-Gestioun, a <strong>Bezuelungsrelancen</strong>. Wat d'Software méi vum Zyklus ofdeckt, wat Dir manner tëscht Instrumenter jongléiert.</p>

<h2>Critère 3: d'Einfachheet</h2>
<p>Dir sidd kee Comptabel: d'Software soll Iech Zäit spueren, net kaschten. Test d'<strong>Zäit bis zur éischter konformer Rechnung</strong>, d'Kloerheet vum Interface an d'Disponibilitéit op Lëtzebuergesch (an idealerweis an der Sprooch vun Äre Clienten). E gutt Instrument benotzt ee ouni Formatioun.</p>

<h2>Critère 4: e Präis, deen zu Ärer Aktivitéit passt</h2>
<p>Mësstraut de Lockpräisser: kuckt, wat <strong>tatsächlech abegraff</strong> ass. E „bëllegen" Forfait, deen d'comptabel Exporter oder d'Zuel vu Rechnungen extra berechent, ka méi deier ginn wéi e Komplettpaket. Préift, ob et eng gratis Offer fir den Ufank gëtt, an d'Transparenz vun de Paliere.</p>

<h2>Critère 5: Sécherheet a RGPD-Konformitéit</h2>
<p>Är Clients- a Finanzdaten si sensibel. Verlaangt en <strong>europäescht Hosting</strong> (RGPD), Verschlësselung, Zwee-Faktor-Authentifikatioun, <strong>reegelméisseg Backups</strong> an eng strikt Datentrennung tëscht Konten. En Hosting ausserhalb vun der EU ass en Alarmsignal — a wat Är Rechnungsarchiven ugeet, verflicht et Iech, d'Späicherplaz der Administratioun ze deklaréieren.</p>

<h2>Critère 6: d'Integratioun mat Ärer Fiduciaire</h2>
<p>De richtege Gewënn u Zäit (a Suen) spillt sech bei Ärem Comptabel of. Eng Software, déi propper an integréierbar Daten exportéiert (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — oder souguer e <strong>dediéierte Portal</strong>, wou d'Fiduciaire Är Daten a Lieszougrëff hëlt — erspuert him d'Neierfaassung a reduzéiert domat Är Honoraren.</p>

<h2>A wat ass mat der elektronescher Fakturatioun?</h2>
<p>Zwee getrennten Terminer, déi dacks verwiesselt ginn:</p>
<ul>
    <li><strong>B2G (Lëtzebuerger ëffentleche Secteur)</strong>: d'elektronesch Fakturatioun ass scho Flicht, fir de Staat an d'Gemengen ze fakturéieren. Wien mam ëffentleche Secteur schafft, brauch se direkt.</li>
    <li><strong>ViDA (europäesch Reform)</strong>: de Kalenner ass gestaffelt — Erweiderung vum OSS-Guichet am Januar 2027, obligatoresch Autoliquidatioun a Plattformreegelen am Juli 2028, duerno obligatoresch elektronesch Fakturatioun an digital Deklaratioun fir innergemeinschaftlech B2B-Operatiounen den 1. Juli 2030. Als éischt kommen <strong>2027 an 2028</strong>, net 2030.</li>
</ul>
<p>Frot Äre Editeur also net „maacht Dir Peppol?", mä „<strong>bis wéini, a fir wéi ee Perimeter</strong>?". E datéierte Projet ass méi wäert wéi eng ugekräizte Case.</p>

<h2>Séier Verglachstabell</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Wat ze verlaangen ass</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU-Konformitéit</td><td class="p-2 border-b">Mentiounen Art. 63, Nummeréierung, 4 TVA-Tariffer, Franchise 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Kompletten Zyklus</td><td class="p-2 border-b">Devisen, Avoiren, Akonten, récurrent Rechnungen, Ausgaben, Relancen</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Disponibel um Dag, wou Dir d'Seuiler iwwerschreit</td></tr>
        <tr><td class="p-2 border-b">E-Fakturatioun</td><td class="p-2 border-b">B2G haut; datéiert Roadmap fir ViDA</td></tr>
        <tr><td class="p-2 border-b">Archivéierung</td><td class="p-2 border-b">PDF/A, 10 Joer, Integritéitsofdrock</td></tr>
        <tr><td class="p-2 border-b">Sécherheet</td><td class="p-2 border-b">EU-Hosting, 2FA, Backups</td></tr>
        <tr><td class="p-2 border-b">Fiduciaire</td><td class="p-2 border-b">Exporter FAIA/Sage/CSV, Comptabelsportal</td></tr>
        <tr><td class="p-2 border-b">Präis</td><td class="p-2 border-b">Alles abegraff, gratis Offer, Transparenz</td></tr>
    </tbody>
</table>

<h2>Excel oder Software: soll ee wierklech de Schrëtt maachen?</h2>
<p>Excel schéngt gratis, mä et garantéiert weder déi fortlafend Nummeréierung nach déi gesetzlech Mentiounen, an et produzéiert kee FAIA um Dag, wou deen néideg gëtt. Soubal Dir méi wéi e puer Rechnungen am Mount ausstellt, spuert eng konform Software Zäit a schaaft Sécherheet. Mir vertiefen de Verglach an eisem Artikel <a href="/lb/blog/excel-vs-rechnungssoftware-firwat-wiesselen">Excel géint Fakturatiounssoftware</a>.</p>

<h2>Wou faktur.lu bei dëse Critère steet</h2>
<p>faktur.lu gouf <strong>speziell fir Lëtzebuerg</strong> entwéckelt. Wat haut disponibel ass:</p>
<ul>
    <li>Mentioune vum Artikel 63 a fortlafend Nummeréierung automatesch, déi 4 TVA-Tariffer, innergemeinschaftlech an Export-Szenarien</li>
    <li>Gestioun vun der Franchise vun 50 000 € mat engem Alert bei Untéierung</li>
    <li><strong>FAIA-2.01</strong>-Export, a comptabel Exporter <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Fiduciaire-Portal</strong> a Lieszougrëff</li>
    <li><strong>PDF/A</strong>-Archivéierung mat SHA-256-Ofdrock an Zéngjoresfrist</li>
    <li>Europäescht Hosting, Zwee-Faktor-Authentifikatioun, Datentrennung pro Kont</li>
    <li>Interface a <strong>5 Sproochen</strong> (FR, DE, EN, LB, PT) an eng <strong>gratis Offer fir unzefänken</strong></li>
</ul>
<p>Wat <strong>am Gaang</strong> ass: d'Produktivsetzung vum Peppol-Versand fir all Konten, duerno den Empfang vu Fournisseursrechnungen. D'Format ass technesch prett, den Deploiement nach net — mir schreiwen et léiwer, wéi eng Case unzekräizen.</p>

<h2>FAQ — seng Fakturatiounssoftware zu Lëtzebuerg wielen</h2>
<h3>Ass eng Fakturatiounssoftware zu Lëtzebuerg obligatoresch?</h3>
<p>Neen, mä Är Rechnunge mussen déi gesetzlech Flichten erfëllen (Mentiounen, Nummeréierung, TVA, Archivéierung). Eng konform Software ass dee einfachste Wee, dat alles ze garantéieren.</p>
<h3>Gëtt de FAIA-Fichier vun alle verlaangt?</h3>
<p>Neen. D'FAQ vun der AED setzt véier kumulativ Bedingungen: normaliséierte Comptesplang, kee vereinfachte Regime, Ëmsaz iwwer 112 000 €, an ongeféier 500 comptabel Transaktioune pro Joer. Feelt eng, sidd Dir net betraff — mä d'Instrument soll Ärem Wuesstem kënne follegen.</p>
<h3>Kann een zu Lëtzebuerg mat Excel fakturéieren?</h3>
<p>Näischt verbitt et formell, mä Excel garantéiert weder déi fortlafend Nummeréierung nach déi obligatoresch Mentiounen. De Risiko bei enger Kontroll ass reell, soubal d'Aktivitéit wuesst.</p>
<h3>Wat kascht eng Fakturatiounssoftware?</h3>
<p>Vu gratis (limitéiert Offeren) bis ongeféier 15-30 €/Mount fir eng komplett Léisung. De richtege Reflex: vergläichen, wat <strong>abegraff</strong> ass, an net nëmmen den Ufankspräis.</p>
<h3>Wéi eng Software, wann ech an der TVA-Franchise sinn?</h3>
<p>Wielt en Instrument, dat de Franchiseregime beherrscht (Seuil 50 000 €), déi passend Mentioun automatesch setzt an Iech <strong>bei Untéierung vum Seuil alertéiert</strong>. Préift och, ob et déi <strong>Restflichten</strong> berücksichtegt: soubal Dir Leeschtungen un EU-Professioneller fakturéiert, bleiwen eng vereinfacht Jooresdeklaratioun a Récapitulatiffen obligatoresch.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Eng Software fir Lëtzebuerg geduecht, gratis fir unzefänken</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert konform Rechnungen (Mentiounen, TVA, Nummeréierung) an d'Exporter fir Är Fiduciaire — a wéinege Minutten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>
ARTICLE_HTML,
            ],
            [
                'slug' => 'steierprefung-letzebuerg-virbereden',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Eng Kontrollnotifikatioun vun der <strong>Administration de l'enregistrement, des domaines et de la TVA (AED)</strong> beonrouegt all Onofhängegen a KMU-Chef zu Lëtzebuerg. Mat enger gudder Virbereedung ass eng Kontroll awer a puer Stonnen erleedegt. Hei ass, wéi Dir Iech virbereet — an eng Verjärungsreegel, déi bal jiddereen falsch widderhëlt.</p>

<h2>Wien ass vun enger AED-Kontroll betraff?</h2>

<p>All Entreprise, déi zu Lëtzebuerg TVA-registréiert ass, kann kontrolléiert ginn. An der Praxis zielt d'Administratioun op:</p>

<ul>
    <li><strong>Séier wuessend Entreprisen</strong>, deenen hiren Ëmsaz ongewéinlech schwankt</li>
    <li><strong>Risikosecteuren</strong>, déi d'AED identifizéiert (Handel, Restauratioun, Bau, Beroodung)</li>
    <li><strong>Atypesch TVA-Deklaratiounen</strong> (widderkéierend TVA-Kredit, bedeitend innergemeinschaftlech Operatiounen)</li>
    <li><strong>Zoufälleg Kontrollen</strong> no statistesche Critèren</li>
</ul>

<h2>Wéi wäit zréck däerf d'AED goen?</h2>

<p>Dat ass de meescht mëssverstanene Punkt vum Sujet, an de Feeler fënnt een iwwerall — bis viru Kuerzem och op dëser Säit. Den <strong>Artikel 81 vum TVA-Gesetz</strong> ass awer eendeiteg:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„D'Aktioun vum Trésor op Bezuelung vun der Steier a vun de Geldstrofe verjäert no <strong>fënnef Joer</strong> vum 31. Dezember vum Joer un, an deem de Betrag, deen anzezéien ass, fälleg gouf."</p>
</blockquote>

<p>Bei der <strong>TVA</strong> ass d'Verjärung also <strong>fënnef Joer</strong>. D'Wuert „zéng Joer" kënnt am ganzen TVA-Gesetz nëmme fir d'<em>Opbewahrung</em> vun Dokumenter vir — ni fir d'Verjärung.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Verwiesselt TVA net mat direkte Steieren</p>
    <p class="text-red-700">D'Verjärung, déi op <strong>zéng Joer</strong> verlängert gëtt bei Netdeklaratioun oder ongenauer Deklaratioun, existéiert tatsächlech — mä fir déi <strong>direkt Steieren</strong> (§144 AO an Art. 10 vum Gesetz vum 27. November 1933), déi vun der ACD verwalt ginn. Si gëllt <strong>net</strong> fir d'TVA, déi ënner d'AED fält. D'Géigendeel ze gleewe kann dozou féieren, datt Dir eng Nofuerderung fir schonn verjäerte Joren akzeptéiert.</p>
</div>

<p>Zwou Nuancen awer. D'Verjärung kann <strong>ënnerbrach</strong> ginn (Art. 2244 ff. vum Zivilgesetzbuch, oder e Verzicht vum Assujetti): dann leeft eng nei Frist, déi um Enn vum véierte Joer no der leschter Ënnerbriechungshandlung erreecht ass. An d'Frist leeft vum <strong>31. Dezember</strong> vum Joer vun der Fällegkeet un, net vum Datum vun der Operatioun.</p>

<p>Schlussendlech sinn d'Verjärung an d'Archivéierung zwou verschidde Saachen: Dir musst Är Rechnungen <strong>zéng Joer</strong> opbewahren (Art. 65 LIVA an Art. 16 vum Handelsgesetzbuch), onofhängeg vun der Verjärung vu fënnef Joer.</p>

<h2>Déi 3 Zorte vun AED-Kontroll</h2>

<h3>1. Kontroll op Stécker</h3>

<p>D'AED freet Iech, Dokumenter per Post oder elektronesch ze schécken. Kee Besuch op der Plaz. Déi heefegst an déi mannsten opwänneg Form.</p>

<h3>2. Kontroll op der Plaz</h3>

<p>En Agent kënnt an Är Raim, fir Är Bicher, Rechnungen an Ausgabebeleeger ze préiwen. Per Bréif ugekënnegt — d'TVA-Gesetz gesäit keng Virwarnfrist vir, d'AED vereinbart den Rendez-vous vun Fall zu Fall.</p>

<h3>3. Onugekënnegt Kontroll</h3>

<p>Rar, engem schwéiere Bedrugsverdacht virbehalen. Den Agent kënnt ouni Virwarnung. Dir kënnt d'Presenz vun Ärer Fiduciaire verlaangen.</p>

<h2>Dokumenter, déi d'AED ka verlaangen</h2>

<ul>
    <li><strong>All Är ausgestallt an erhalen Rechnungen</strong> vun der kontrolléierter Period, mat de obligatoresche Mentioune vum Art. 63 LIVA</li>
    <li><strong>De FAIA-Fichier</strong> am Format 2.01 — fir déi Assujettien, déi dozou verflicht sinn, kuckt hei ënnen</li>
    <li><strong>D'Recetten- an Ausgabebuch</strong> oder déi komplett Comptabilitéit no Ärem Regime</li>
    <li><strong>Déi ofgi TVA-Deklaratiounen</strong>, periodesch a jäerlech</li>
    <li><strong>D'berufflech Kontosauszich</strong> vun der Period</li>
    <li><strong>D'Beleeger vun den ofgezunnen Ausgaben</strong> (Spesen, Fournisseursrechnungen)</li>
    <li><strong>Déi wesentlech Kontrakter</strong> mat Clienten a Fournisseuren</li>
    <li><strong>De Beweis vun der Autoliquidatioun</strong> beim innergemeinschaftleche B2B: TVA-Nummeren, iwwer VIES validéiert (Art. 17 LIVA, Art. 196 vun der Direktiv)</li>
</ul>

<h2>FAIA: de kriteschen Punkt — mä net fir jiddereen</h2>

<p>De <strong>FAIA</strong> ass e strukturéierten XML-Fichier, ofgeleet vum SAF-T-Standard, deen de Kapp vun der Entreprise, d'Verkafsrechnunge vun der Period, déi zougehéiereg Écritureën an d'Kontrolltotaler sammelt. D'Schema a Kraaft ass d'Versioun <strong>2.01</strong>, deenen hir lescht vun der AED publizéiert Aktualiséierung aus dem Juli 2020 stamt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Véier kumulativ Bedingungen</p>
    <p>De FAIA betrëfft net all Assujetti. No der FAQ vun der AED setzt d'Flicht viraus, <strong>gläichzäiteg</strong>: dem normaliséierte Comptesplang (PCN) ze ënnerleien, kee vereinfachte Regime a Usproch ze huelen, iwwer 112 000 € Ëmsaz ze realiséieren, an ongeféier <strong>500 comptabel Transaktiounen</strong> pro Joer ze iwwerschreiden. Eng Transaktioun ass eng ganz Comptabiliséierungskette, keng Rechnung. Feelt eng eenzeg Bedingung, sidd Dir net betraff — kuckt eise <a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide">komplette FAIA-Guide</a>.</p>
</div>

<p>Wann Dir dozou verflicht sidd an op engem Tabelleblat fakturéiert, musst Dir de Fichier mat der Hand rekonstruéieren: e puer Aarbechtsdeeg an en héije Feelerrisiko.</p>

<h2>Wéi eng Kontroll op der Plaz oflaf</h2>

<ol>
    <li><strong>Schrëftlech Notifikatioun</strong> vun der AED, allgemeng per Aschreiwen</li>
    <li><strong>Ukunft vun den Agenten</strong> mat engem Kontrollopdrag</li>
    <li><strong>Ufro vun den Dokumenter</strong> no enger virbereeter Lëscht</li>
    <li><strong>Iwwerpréiwung vun den Écritureën</strong> an Ofgläich mat den TVA-Deklaratiounen</li>
    <li><strong>Gespréich</strong> iwwer net-standard Operatiounen oder festgestallt Ofwäichungen</li>
    <li><strong>Notifikatioun vun de Schlussfolgerungen</strong> per Bréif</li>
</ol>

<h2>Déi 5 Feeler, déi deier kommen</h2>

<ol>
    <li><strong>Net-sequenziell Rechnungsnummeréierung</strong> (Art. 63 LIVA, Punkt 3°): Lacken oder Duebele gëllen als Vermutung vun net deklaréiertem Ëmsaz</li>
    <li><strong>Feelend obligatoresch Mentiounen</strong> op de Rechnungen (Art. 63 LIVA)</li>
    <li><strong>Net dokumentéiert innergemeinschaftlech Autoliquidatioun</strong>: ouni VIES-Validatioun vun der Clientsnummer kann d'AED d'Operatioun als zu Lëtzebuerg steierflichteg requalifizéieren</li>
    <li><strong>Feelend oder onliesbar Ausgabebeleeger</strong></li>
    <li><strong>Inkohärenzen tëscht TVA-Deklaratiounen a Rechnungen</strong>: schonn eng kleng Ofwäichung léist eng vertéift Iwwerpréiwung aus</li>
</ol>

<h2>Wéi eng Sanktioune bei Feeler?</h2>

<ul>
    <li><strong>Formell Manktemer</strong> (feelend Mentioun, FAIA net geliwwert, verspéidet Deklaratioun): administrativ Geldstrof vun <strong>250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
    <li><strong>Manktem, dat de Staat ëm Recetten bruecht huet</strong>: Geldstrof vun <strong>10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong, a bei engem gréissere Sträit vill méi schwéier wéi déi virescht</li>
    <li><strong>Refus, Rechnungen a comptabel Stécker ze weisen</strong>: bis zu <strong>25 000 € pro Dag Verspéidung</strong>, no engem Avertissement</li>
    <li><strong>Schwéier Steierhannerzéiung oder Steierbedruch</strong>: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, Prisong vun engem Mount bis fënnef Joer, Verloscht vun de biergerleche Rechter fir 5 bis 10 Joer (Art. 80 LIVA)</li>
</ul>

<h2>Seng Kontroll a 5 Schrëtt virbereeden</h2>

<ol>
    <li><strong>De FAIA virbereeden</strong> fir déi gefroten Period, wann Dir dozou verflicht sidd</li>
    <li><strong>All ausgestallt Rechnungen exportéieren</strong>, idealerweis als PDF/A</li>
    <li><strong>D'Kohärenz préiwen</strong> mat den ofgin TVA-Deklaratiounen</li>
    <li><strong>Pro Mount en Dossier bilden</strong>: Rechnungen, Kontosauszich, zougehéiereg Deklaratioun</li>
    <li><strong>Är Fiduciaire informéieren</strong> a si froen, bei der Kontroll present ze sinn</li>
</ol>

<h2>D'Schlussfolgerungen ufechten</h2>

<p>Wann Dir net d'accord sidd, ass d'Prozedur gereegelt:</p>

<ol>
    <li><strong>Schrëftlech, begrënnt Reklamatioun</strong> bei der Administratioun, bannent <strong>dräi Méint</strong> no der Notifikatioun</li>
    <li>Bei Ofleenung gëtt <strong>den Direkter vun der Administratioun befaasst</strong>, a seng Entscheedung trëtt un d'Plaz vun der virechter</li>
    <li><strong>Recours duerch Assignatioun</strong> virum <strong>Bezierksgeriicht Lëtzebuerg a Zivilsaachen</strong> — d'TVA fält ënner d'ordentlech Geriichtsbarkeet, net ënner d'Verwaltungsgeriichtsbarkeet wéi déi direkt Steieren. D'Assignatioun muss bannent <strong>dräi Méint</strong> no der Direktorialentscheedung zougestallt ginn, <strong>ënner Strof vun der Forclusioun</strong></li>
</ol>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">D'Stëllschweige vun der Administratioun blockéiert Iech net</p>
    <p>Kënnt bannent <strong>sechs Méint</strong> no Ärer Reklamatioun keng Entscheedung, kënnt Dir se als ofgeleent betruechten an direkt e Recours areechen. An deem Fall leeft d'Dräiméintfrist net — d'Waarde bréngt Iech also net an d'Forclusioun.</p>
</div>

<h2>Wéi faktur.lu Iech virbereet</h2>

<ul>
    <li>Automatesch fortlafend Nummeréierung konform mam <strong>Artikel 63 LIVA</strong></li>
    <li>Obligatoresch Mentiounen op all Rechnung generéiert</li>
    <li>VIES-Validatioun fir innergemeinschaftlech B2B-Operatiounen</li>
    <li><strong>FAIA-2.01</strong>-Export fir all Period</li>
    <li><strong>PDF/A</strong>-Archivéierung mat Integritéitsofdrock</li>
    <li>Fiduciaire-Portal a Lieszougrëff, ouni E-Mail-Pingpong</li>
</ul>

<h2>Heefeg Froen</h2>

<h3>Wéi laang dauert eng Kontroll?</h3>
<p>Eng Kontroll op Stécker: zwou bis véier Wochen, jee nodeem wéi séier Dir d'Dokumenter schéckt. Eng Kontroll op der Plaz: allgemeng een bis dräi Deeg an der Entreprise, duerno e puer Wochen Analys bei der AED.</p>

<h3>Kann ech eng Kontroll refuséieren?</h3>
<p>Neen. De Refus gëtt sanktionéiert an ënnerstellt béise Glawen. Dir kënnt awer aus engem legitime Grond eng Verschiebung froen an d'Presenz vun Ärer Fiduciaire verlaangen.</p>

<h3>Muss ech trotzdem zéng Joer archivéieren, wann d'Verjärung fënnef Joer ass?</h3>
<p>Jo. Dat sinn zwou onofhängeg Flichten: d'Opbewahrung vun zéng Joer kënnt aus dem Art. 65 LIVA an dem Art. 16 vum Handelsgesetzbuch, net aus der Verjärungsfrist.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>Prozeduren, Sanktiounen a Fristen kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert — fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikelen 63, 77, 80, 81</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">TVA-Sanktiounen a Rechtsmëttelen</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Applikatiounsberäich)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichten</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<h2>Fir méi wäit ze goen</h2>
<ul>
    <li><a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide">FAIA Lëtzebuerg: alles iwwer den informatiséierten Auditfichier</a></li>
    <li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung</a></li>
    <li><a href="/lb/blog/rechnungsarchiveierung-letzebuerg-gesetzlech-dauer-format">Archivéierung vu Rechnungen: gesetzlech Dauer a Format</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereet Är nächst Kontroll haut vir</h3>
    <p class="text-primary-800 mb-4">Automatesch Nummeréierung, FAIA 2.01 mat engem Klick, konform Mentiounen, PDF/A-Archivéierung. D'Kontroll gëtt eng Formalitéit.</p>
    <a href="/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis ufänken</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Zu Lëtzebuerg kënne kleng Entreprisen, deenen hire Joresëmsaz ouni TVA 50 000 € net iwwerschreit, vun der <strong>TVA-Franchise</strong> profitéieren (Artikel 57bis vum Lëtzebuerger TVA-Gesetz). Dëse Seuil ass den 1. Januar 2025 vun 35 000 € op 50 000 € eropgesat ginn — zur selwechter Zäit wéi e nach wéineg bekannte <strong>transfrontaliere Regime</strong>. Hei ass alles, wat een 2026 wësse muss.</p>

<h2>Wat ass d'TVA-Franchise?</h2>

<p>D'TVA-Franchise (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">Artikel 57bis LIVA</a>) ass e <strong>besonnesche Regime</strong>, deen et klengen Entreprisen erlaabt, hir Liwwerungen a Leeschtunge vun der TVA ze befreien. Konkret:</p>

<ul>
    <li>Dir fakturéiert Äre Clienten <strong>keng TVA</strong></li>
    <li>Dir féiert <strong>keng TVA</strong> un de Staat of</li>
    <li>Dir sidd vun der Flicht befreit, <strong>ordinär TVA-Deklaratiounen</strong> anzereechen</li>
</ul>

<p>Als Géigeleeschtung kënnt Dir d'TVA op Är berufflech Akeef <strong>net zréckhuelen</strong>.</p>

<h2>Bedingungen</h2>

<ul>
    <li>Joresëmsaz ouni TVA vun <strong>héchstens 50 000 €</strong> am Kalennerjoer</li>
    <li><strong>Toleranz vun 10 %</strong>: iwwerschreit Dir am Laf vum Joer ouni iwwer <strong>55 000 €</strong> ze goen, bleift Dir bis den 31. Dezember an der Franchise</li>
    <li>Sëtz vun der wirtschaftlecher Aktivitéit zu Lëtzebuerg (e bloussen Etablissement stable duergeet net)</li>
    <li>De Regime ass <strong>optional</strong>: Dir kënnt de Normalregime virzéien</li>
</ul>

<p><strong>Vun der Franchise ausgeschloss Operatiounen:</strong> geleeëntlech Operatiounen no Artikel 12 vun der Direktiv 2006/112/EG, an d'<strong>Liwwerunge vun neie Verkéiersmëttel</strong> an en anere Member-Staat. Ausgeschloss sinn ausserdeem Assujettien am Regime vun der TVA-Grupp, am Forfaitsregime fir Bauer a Fierschter, oder mat onvereinbaren Immobiliekoptiounen.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wousst Dir?</p>
    <p>De Seuil gouf den 1. Januar 2025 vun 35 000 € op 50 000 € eropgesat, am Kader vun der Ëmsetzung vun der <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Direktiv (EU) 2020/285</a>. De maximale Plafong, deen e Member-Staat festleeë kann, läit bei 85 000 €; Lëtzebuerg gesäit keng sektoriell Seuilen vir.</p>
</div>

<h2>Wovun d'Franchise Iech net befreit</h2>

<p>Dat ass de meescht mëssverstanene Punkt vum Regime, an en betrëfft vill Leit. An der Franchise ze sinn heescht <strong>net, vun alle Meldeflichte befreit ze sinn</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Wann Dir professionell Clienten an der EU fakturéiert</p>
    <p class="text-red-700">Soubal Dir <strong>innergemeinschaftlech Déngschtleeschtungen</strong> erbréngt — oder no Artikel 61 vum TVA-Gesetz zu Lëtzebuerg Schëllner vun der TVA gitt — musst Dir <strong>obligatoresch</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>eng <strong>vereinfacht Jooresdeklaratioun</strong> iwwer de Portal <strong>eCDF</strong> areechen, a zwar <strong>virum 1. Mäerz</strong> vum nächste Kalennerjoer;</li>
        <li><strong>Récapitulatiffen</strong> zu dësen innergemeinschaftlechen Déngschtleeschtungen ofginn.</li>
    </ul>
    <p class="text-red-700 mt-2">E eenzege professionelle Client an Däitschland, a Frankräich oder an der Belsch léist béid Flichten aus.</p>
</div>

<p>Ausserhalb vun dësem Fall deelt den Assujetti an der Franchise säi Joresëmsaz sengem Steierbüro mat — per Post, per E-Mail, oder mam Formulaire vun der vereinfachter Jooresdeklaratioun, deen d'AED zur Verfügung stellt.</p>

<p>Falls Dir <strong>am selwechte Joer</strong> ënner d'Franchise an duerno ënnert de Normalregime fält, gëtt den ënner der Franchise realiséierten Ëmsaz an d'<strong>Case 481</strong> vun der TVA-Deklaratioun agedroen, déi am Normalregime ofzeginn ass.</p>

<h2>Obligatoresch Mentiounen op Äre Rechnungen</h2>

<p>Och an der Franchise mussen Är Rechnungen dës <strong>genee Mentioun</strong> droen:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979"
</blockquote>

<p>Dir musst ausserdeem:</p>
<ul>
    <li><strong>Keng TVA uginn</strong> (keng TVA-Linn op der Rechnung)</li>
    <li>Kee <strong>TVA-Taux</strong> uginn</li>
    <li>Nëmmen de <strong>Nettobetrag</strong> uginn (keng Ënnerscheedung ouni/mat TVA)</li>
</ul>

<p>Gutt Neiegkeet zanter dem 1. Januar 2025: wien d'Franchise notzt, däerf <strong>vereinfacht Rechnungen</strong> ausstellen.</p>

<h2>Virdeeler vun der Franchise</h2>

<ul>
    <li><strong>Einfachheet</strong>: keng ordinär TVA-Deklaratioun, vereinfacht Fakturatioun erlaabt</li>
    <li><strong>B2C-Konkurrenzfäegkeet</strong>: bei gläichem Nettopräis ass Är Rechnung fir eng Privatpersoun ronn <strong>14,5 % méi bëlleg</strong> wéi déi vun engem Concurrent am Normalregime (17 % TVA op 117 € brutto). Opgepasst awer: dëse Virsprong gëtt duerch déi net erstatt TVA op Är eege Akeef geschmälert</li>
    <li><strong>Tresorerie</strong>: keng Verschiebung tëscht agehuelener an ofgefouerter TVA</li>
    <li><strong>Manner Pabeierkrom</strong>: vereinfacht Administratioun</li>
</ul>

<h2>Nodeeler</h2>

<ul>
    <li><strong>Kee Virsteierofzuch</strong>: Dir bezuelt TVA op Är Akeef, ouni se zréckzehuelen (entscheedend, wann Dir investéiert)</li>
    <li><strong>B2B-Nodeel</strong>: professionell Clienten zéien aus Äre Rechnunge keng TVA of, Äre Präis kënnt hinnen also méi deier</li>
    <li><strong>Image</strong>: gewësse B2B-Clienten schaffe léiwer mat assujettéierten Entreprisen</li>
    <li><strong>Restflichten</strong>: soubal Dir Leeschtungen un EU-Professioneller fakturéiert, gi vereinfacht Jooresdeklaratioun a Récapitulatiffen erëm obligatoresch</li>
    <li><strong>Enke Plafong</strong>: iwwer de Seuil a seng Toleranz eraus ass de Wiessel an den Normalregime zwéngend</li>
</ul>

<h2>Den transfrontaliere Franchiseregime (zanter 2025)</h2>

<p>Dat ass déi zweet Neierung vum 1. Januar 2025, vill manner kommentéiert wéi d'Erhéijung vum Seuil. Bis dohin gouf d'Franchise nëmmen am Etablissementsstaat. Elo kann eng Lëtzebuerger kleng Entreprise <strong>d'Franchise och an anere Member-Staaten</strong> notzen.</p>

<h3>D'Bedingungen</h3>

<ul>
    <li>De <strong>nationale Seuil vun all Member-Staat</strong> respektéieren, an deem Dir d'Franchise wëllt (d'Seuiler variéieren: kuckt de <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">europäesche Portal SME VAT rules</a>)</li>
    <li>En Ëmsaz vun <strong>ënner 100 000 € an der ganzer Unioun</strong> realiséieren — de „Seuil vun der Unioun", deen Är Verkeef zu Lëtzebuerg abezitt</li>
    <li>De <strong>Sëtz vun Ärer wirtschaftlecher Aktivitéit</strong> zu Lëtzebuerg hunn. Wien säi Sëtz an engem Drëttland huet, kann en net notzen, och net mat engem Etablissement stable an der EU</li>
</ul>

<h3>D'Nummer „EX"</h3>

<p>D'Prozedur leeft iwwer eng <strong>virdrun Notifikatioun</strong> an Ärem beruffleche Raum op <strong>MyGuichet.lu</strong>. D'AED leet d'Ufro un déi betraffe Member-Staaten weider. Soubal op mannst ee se akzeptéiert, kritt Dir eng Identifikatiounsnummer <strong>„EX"</strong> vun der Form <strong>LU12345678-EX</strong> — allgemeng bannent <strong>35 Aarbechtsdeeg</strong>.</p>

<p>D'Franchise fänkt an engem Member-Staat eréischt <strong>vum Datum vun der Matdeelung oder Bestätegung</strong> vun dëser Nummer un ze gëllen. Wichteg: eng <strong>réckwierkend Identifizéierung ass net méiglech</strong>. Verkeef virum Erhale vun der Nummer sinn net nozehuelen.</p>

<h3>Är Meldeflichten</h3>

<ul>
    <li><strong>Trimestriell Deklaratioun</strong> un d'AED vum Gesamtëmsaz an <strong>alle</strong> Member-Staaten — Lëtzebuerg abegraff</li>
    <li>Als Géigeleeschtung musst Dir Iech an de Member-Staaten, wou Dir net etabléiert sidd, fir déi vun der Franchise gedeckten Operatiounen weder identifizéieren nach TVA-Deklaratiounen ofginn</li>
    <li>Fir déi do <strong>net gedeckten</strong> Operatiounen (besonnesch innergemeinschaftlech Acquisitiounen) gëllen d'lokal Flichte weider</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Wann Dir de Seuil vun der Unioun iwwerschreit</p>
    <p>Iwwer 100 000 € Ëmsaz an der Unioun verléiert Dir d'Franchise an de Member-Staaten, wou Dir net etabléiert sidd — <strong>och am nächste Kalennerjoer</strong>, och wann d'national Seuiler do respektéiert ginn. D'national Franchise zu Lëtzebuerg behaalt Dir hannergéint, soulaang Dir do d'Bedingungen erfëllt.</p>
</div>

<h2>Wéini an den Normalregime wiesselen?</h2>

<h3>Zwéngende Wiessel (Iwwerschreide vum Seuil)</h3>

<p>D'Reegel hänkt vum <strong>Ausmooss vun der Iwwerschreidung</strong> vum Seuil vun 50 000 € of:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situatioun</th>
            <th class="text-left p-2 bg-slate-100">Effet</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Iwwerschreidung ëm héchstens 10 % (Ëmsaz bis 55 000 €)</td><td class="p-2 border-b">Dir bleift bis den 31. Dezember an der Franchise. Wiessel an den Normalregime den <strong>1. Januar vum nächste Joer</strong>.</td></tr>
        <tr><td class="p-2 border-b">Iwwerschreidung ëm méi wéi 10 % (Ëmsaz iwwer 55 000 €)</td><td class="p-2 border-b">D'Franchise hält op ze gëllen <strong>vum Dag no der Iwwerschreidung un</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Dat offiziellt Beispill vun der AED</p>
    <p>En Assujetti erreecht 51 000 €: Iwwerschreidung ënner 10 %, hie bleift an der Franchise. Den <strong>8. September 2025</strong> bréngt e Verkaf vu 5 000 € säin Ëmsaz op <strong>56 000 €</strong>. D'Iwwerschreidung läit iwwer 10 %: hien ass <strong>vum 9. September 2025 un</strong> aus der Franchise ausgeschloss, a bleift et d'ganzt Joer 2026.</p>
    <p class="mt-2">Bemierkt d'Mechanik: et ass den <strong>Dag duerno</strong>. D'Rechnung, déi de Plafong sprengt, gëtt nach an der Franchise ausgestallt; eréischt déi nächst dréit d'TVA.</p>
</div>

<p><strong>An allen zwee Fäll</strong> schléisst d'Iwwerschreide vum Seuil am Kalennerjoer Iech fir dat <strong>ganzt nächst Kalennerjoer</strong> aus der Franchise aus, egal wéi héich de Prozentsaz ass.</p>

<p>Dir musst dann:</p>

<ol>
    <li>D'<strong>AED</strong> iwwer MyGuichet.lu oder Äre Steierbüro iwwer de Regimewiessel informéieren</li>
    <li>No uewestoenden Zäitplang ufänken TVA ze fakturéieren</li>
    <li>Déi vun der AED festgeluecht Deklaratiounsperiodizitéit anhalen</li>
</ol>

<h3>Fräiwëllege Wiessel (Optioun fir den Normalregime)</h3>

<p>Dir kënnt <strong>och ënner dem Seuil</strong> fir den Normalregime optéieren, mat enger Ufro bei Ärem Steierbüro. <strong>D'Optioun gëllt vum 1. Dag vum nächste Mount un</strong> a bënnt Iech fir <strong>op mannst ee Kalennerjoer</strong>: et ass keng Hin- an Hierfaart.</p>

<p>Dacks déi richteg Wiel, wann:</p>

<ul>
    <li>Är Clienten haaptsächlech <strong>Entreprisen (B2B)</strong> sinn, déi TVA ofzéien</li>
    <li>Dir <strong>wichteg Investissementer</strong> hutt (Material, Beruffsgefier) an d'Virsteier zréckhuele wëllt</li>
    <li>Dir Iech dem Seuil nätert an et virzitt, <strong>virauszekucken</strong>, amplaz d'Tariffer am Laf vum Joer z'änneren</li>
    <li>Dir <strong>international (intra-EU B2B)</strong> fakturéiert an eng aktiv TVA-Nummer braucht</li>
</ul>

<p>Och ze bemierken: bei <strong>Aktivitéitsopgab</strong> muss eng Deklaratioun bannent <strong>fofzéng Deeg</strong> bei Ärem Steierbüro sinn. D'Franchise hält um Datum vun der Opgab op.</p>

<h2>Deklaratiounsperiodizitéit nom Wiessel</h2>

<p>Am Normalregime hänkt d'Periodizitéit vun Ärem Joresëmsaz ouni TVA of. <strong>D'Jooresdeklaratioun kënnt zu de periodeschen Deklaratiounen derbäi, si ersetzt se net:</strong></p>

<ul>
    <li><strong>Ëmsaz &lt; 112 000 €</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz &gt; 620 000 €</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<p>Et ass d'AED, déi Äert Regime bestëmmt — Dir wielt et net.</p>

<h2>Impakt op Är Rechnunge mat faktur.lu</h2>

<p>faktur.lu beherrscht allen zwee Regimer:</p>

<ul>
    <li><strong>TVA-Franchise</strong>: d'Rechnungen weisen automatesch d'Mentioun „TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979", ouni TVA-Linn an ouni Ënnerscheedung</li>
    <li><strong>Normalregime</strong>: d'TVA gëtt automatesch mam richtegen Taux berechent (17 %, 14 %, 8 % oder 3 %)</li>
    <li><strong>Seuil-Alert</strong>: faktur.lu warnt Iech, wann Dir Iech dem Seuil vun 50 000 € (an der Toleranz bei 55 000 €) nätert</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen, Tariffer an TVA-Prozedure kënnen änneren, an den transfrontaliere Regime ass rezent. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Besonnesche Regime fir kleng Entreprisen (Franchise)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Franchise (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Europäesch Kommissioun – National Franchise-Seuiler pro Member-Staat</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Direktiv (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/tva-letzebuerg-tariffer-berechnung-obligatiounen" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Tariffer zu Lëtzebuerg →</a></li><li><a href="/lb/blog/freelancer-letzebuerg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer Lëtzebuerg: konform fakturéieren →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Geréiert Är TVA-Franchise gelooss</h3>
    <p class="text-primary-800 mb-4">faktur.lu passt sech automatesch Ärem TVA-Regime un a warnt Iech virum Iwwerschreide vum Seuil vun 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'vu-letzebuerg-aus-ausland-fakturieren',
                'locale' => 'lb',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Sidd Dir zu Lëtzebuerg baséiert a fakturéiert Dir Clienten am Ausland? D'TVA-Reegele variéiere staark no der geografescher Zon an der Aart vu Client. Hei ass e kloere Guide fir all Situatioun, mat de richtege Mentiounen a gesetzleche Basen (Stand 2026).</p>

<h2>Fall 1: Entreprise-Client an der EU (innergemeinschaftlecht B2B)</h2>

<p>Dat ass de heefegste Fall fir Lëtzebuerger Freelancer a KMU. Beispill: e Lëtzebuerger Beroder fakturéiert enger däitscher Gesellschaft.</p>

<h3>Reegelen, déi ze applizéiere sinn</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong> – Ort vun der Besteierung beim Client (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">Art. 17 LIVA, Ëmsetzung vum Art. 44 vun der Direktiv 2006/112/EG</a>)</li>
    <li>De Client deklaréiert d'TVA a sengem Land (<strong>Autoliquidatioun / Reverse Charge</strong>) – den Art. 196 vun der Direktiv bezeechent en als Schëllner</li>
    <li>Dir musst d'TVA-Nummer vum Client op <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> iwwerpréiwen</li>
    <li>Obligatoresch Mentioun (Art. 226 §11bis Direktiv): <em>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mentioun Artikel 196, net 44</p>
    <p>Vill Virlage maachen de Feeler, „Artikel 44 vun der Direktiv 2006/112/EG" ze nennen. Den Artikel 44 definéiert den <strong>Ort vun der Besteierung</strong>. Déi obligatoresch Mentioun, déi unzebrénge ass, verweist op den <strong>Artikel 196</strong> (deen de Client als Schëllner vun der TVA bezeechent). Bevirzegt d'Mentioun Artikel 196.</p>
</div>

<h3>Néideg Dokumenter</h3>
<ul>
    <li>Är Lëtzebuerger TVA-Nummer op der Rechnung</li>
    <li>D'TVA-Nummer vum Client (op VIES iwwerpréift, Beweis opzebewahren)</li>
    <li>Deklaratioun am <strong>Récapitulatif</strong> – seng Periodizitéit gëtt vun der AED no Ärer Situatioun festgeluecht</li>
</ul>

<h2>Fall 2: Privatclient an der EU (B2C)</h2>

<p>Dir verkeeft un eng Privatpersoun an engem anere EU-Land. D'Reegelen hänken vun der Aart vun der Leeschtung of:</p>

<h3>Klassesch Déngschtleeschtungen (Beroodung, Design asw.)</h3>
<ul>
    <li>Dir applizéiert standardméisseg d'<strong>Lëtzebuerger TVA (17 %)</strong></li>
    <li>Keng Autoliquidatioun bei Privatpersounen</li>
    <li><strong>Opgepasst:</strong> Gewësse B2C-Leeschtungen hu spezifesch Reegelen (Immobilien, Transport, Kultur- a Sportevenementer op der Plaz…) a ginn do besteiert, wou se ausgefouert ginn. Wann dat op Iech zoutrëfft, klärt et mat Ärer Fiduciaire.</li>
</ul>

<h3>Elektronesch Déngschtleeschtungen (SaaS, Online-Formatiounen asw.)</h3>
<ul>
    <li>Dir applizéiert d'<strong>TVA vum Land vum Client</strong></li>
    <li>Iwwer de Regime <strong>OSS (One-Stop Shop)</strong>: eng eenzeg Deklaratioun fir all EU-Länner</li>
    <li>Seuil: <strong>10 000 €/Joer</strong> u B2C-Verkeef an der EU (Zomm vu Fernverkeef vu Wueren an TBE-Leeschtungen). Dorënner kënnt Dir d'Lëtzebuerger TVA applizéieren.</li>
</ul>

<h2>Fall 3: Client ausserhalb vun der EU (Export)</h2>

<p>Dir fakturéiert engem Client an der Schwäiz, an de Vereenegte Staaten, am Vereenegte Kinnekräich oder an engem beliebegen anere Land ausserhalb vun der EU.</p>

<h3>Déngschtleeschtungen</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong> – Déngscht ausserhalb vun der EU lokaliséiert (Art. 17 LIVA)</li>
    <li>Recommandéiert Mentioun: <em>„TVA net applicabel – Déngscht ausserhalb vun der EU lokaliséiert"</em></li>
    <li>Kee Récapitulatif néideg (reservéiert fir innergemeinschaftlechen Handel)</li>
</ul>

<h3>Wueren (Export ausserhalb vun der EU)</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA</strong> (befreiten Export, Art. 43 §1 a) LIVA)</li>
    <li>Dir musst de <strong>Exportbeweis</strong> opbewahren (Zolldokument)</li>
    <li>Mentioun: <em>„TVA-Befreiung – Artikel 146 vun der Direktiv 2006/112/EG"</em></li>
</ul>

<h3>De besonnesche Fall vun Nordirland</h3>

<p>D'Vereenegt Kinnekräich pauschal als „ausserhalb vun der EU" anzestufen, stëmmt fir Déngschtleeschtungen, ass awer <strong>falsch fir Wueren</strong>. No dem Protokoll Irland / Nordirland gëllt d'TVA-Legislatioun vun der Unioun weider fir <strong>Wueren</strong> op an aus Nordirland.</p>

<ul>
    <li><strong>Wueren op Nordirland</strong>: innergemeinschaftlech Liwwerung, kee Export. De Client huet eng TVA-Nummer mam Präfix <strong>„XI"</strong>, déi op VIES ze validéieren ass, an d'Operatioun steet an Ärem Récapitulatif</li>
    <li><strong>Déngschtleeschtungen op Nordirland</strong>: Drëttlandsregime, wéi fir de Rescht vum Vereenegte Kinnekräich</li>
    <li>Eng gëlteg TVA-Nummer mam richtege Präfix ass eng <strong>materiell Bedingung</strong> vun der Befreiung: mat enger „GB"-Nummer léisst sech d'Operatioun net als innergemeinschaftlech Liwwerung behandelen</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">E Feeler, deen an all zwou Richtungen deier gëtt</p>
    <p>Wann Dir e Wuerenverkaf op Nordirland als Export behandelt, sicht Dir no engem Zollbeweis, deen et ni gi wäert, a loosst de Récapitulatif aus. Ëmgedréint deklaréiert Dir bei enger als innergemeinschaftlech behandelter Déngschtleeschtung eng Operatioun, déi do net hikënnt. Entscheedend ass d'<strong>Aart vun der Operatioun</strong>, net d'Land.</p>
</div>

<h2>Besonnesche Fall: d'Schwäiz</h2>

<p>D'Schwäiz ass net an der EU. Vill Lëtzebuerger Freelancer fakturéiere Schwäizer Clienten. D'Reegelen:</p>

<ul>
    <li><strong>B2B-Déngschtleeschtungen</strong>: fakturéiert <strong>ouni TVA</strong>, de Schwäizer Client deklaréiert d'Steier iwwer de Mechanismus vun der <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">Bezuchssteier (ESTV)</a></li>
    <li><strong>B2C-Déngschtleeschtungen</strong>: no der Aart vun der Leeschtung kann d'Lëtzebuerger TVA ufalen (besonnesch elektronesch Leeschtungen)</li>
    <li>Fakturéiert an <strong>EUR oder CHF</strong> no Ofsprooch mam Client</li>
    <li>Kee Récapitulatif (reservéiert fir innergemeinschaftlechen Handel)</li>
</ul>

<h2>Iwwersiichtstabell</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Szenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mentioun</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (innergemeinschaftlech)</td><td class="border border-gray-300 px-4 py-2">0 % (Autoliquidatioun)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Direktiv 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (klassesch Déngschter)</td><td class="border border-gray-300 px-4 py-2">17 % LU (standardméisseg)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / elektronesch Déngschter) – &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">TVA vum Land vum Client</td><td class="border border-gray-300 px-4 py-2">OSS-Regime</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (zu LU net steierbar)</td><td class="border border-gray-300 px-4 py-2">„TVA net applicabel – Déngscht ausserhalb vun der EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export vu Wueren ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (befreit)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Direktiv 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Wueren op Nordirland</strong></td><td class="border border-gray-300 px-4 py-2">0 % (innergemeinschaftlech Liwwerung)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Direktiv – Clientsnummer mam Präfix „XI"</td></tr>
    </tbody>
</table>

<h2>Multi-Devisen</h2>

<p>Wann Dir an enger auslännescher Devise fakturéiert, muss den TVA-Betrag fir Är Lëtzebuerger Deklaratioun <strong>an Euro ëmgerechent</strong> ginn. Zwee Prinzipie gëllen:</p>

<ul>
    <li>Wielt eng <strong>konstant Ëmrechnungsmethod</strong> an applizéiert se op all Är Operatioune vum Exercice</li>
    <li>Suergt fir <strong>Kohärenz tëscht der Rechnung an der Comptabilitéit</strong>: dee selwechten Taux muss sech an allen zwee erëmfannen</li>
</ul>

<p>Wéi ee Referenztaux ze wielen ass, hänkt vun Ärer Situatioun of: loosst dat eemol vun Ärer Fiduciaire bestätegen, amplaz et Rechnung fir Rechnung ze improviséieren.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Net mam Zolltaux verwiesselen</p>
    <p>D'<a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Zollverwaltung publizéiert hir eege Wiesselkueren</a>, déi zur Bestëmmung vum <strong>Zollwäert</strong> vun de Wuere déngen. Dat ass en anere Taux wéi deen, deen zur Ëmrechnung vun der TVA-Steierbasis benotzt gëtt. Wien Wueren exportéiert, huet mat allen zwee ze dinn.</p>
</div>

<h2>Wat sech mat ViDA ännert (2027-2030)</h2>

<p>De europäesche Paquet <strong>ViDA</strong> („TVA am digitalen Zäitalter") ass ugeholl. Am Géigesaz zu deem, wat een dacks liest, fänkt en net 2030 un: de Kalenner ass <strong>gestaffelt</strong>, an déi éischt Termine kommen däitlech méi fréi.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Termin</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wat a Kraaft trëtt</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2027</td><td class="border border-gray-300 px-4 py-2">Éischt Erweiderung vum eenzege Guichet OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Juli 2028</td><td class="border border-gray-300 px-4 py-2">Obligatoresch Autoliquidatioun a gewësse Fäll, Reegele fir Plattformen, weider Erweiderung vum OSS op inlännesch B2C-Verkeef</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1. Juli 2030</td><td class="border border-gray-300 px-4 py-2">Obligatoresch elektronesch Fakturatioun an digital Deklaratioun fir innergemeinschaftlech B2B-Operatiounen</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2035</td><td class="border border-gray-300 px-4 py-2">Harmoniséierung vun de bestoenden nationale Systemer mat der europäescher Norm</td></tr>
    </tbody>
</table>

<p>Wien reegelméisseg Clienten an der EU fakturéiert, dee betreffen als éischt <strong>2027 an 2028</strong>, net 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'innergemeinschaftlech an international TVA-Reegele entwéckele sech, an de ViDA-Kalenner kann nach präziséiert ginn. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är Situatioun frot Är Fiduciaire oder d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestëmmung vum Leeschtungsort</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu – Innergemeinschaftlech Transaktiounen</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Direktiv 2006/112/EG – Artikelen 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun EU-TVA-Nummeren</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Europäesch Kommissioun – Ort vun der Besteierung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/artikel-17-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Artikel 17 LIVA: Autoliquidatioun B2B intra-EU →</a></li><li><a href="/lb/blog/innergemeinschaftlech-tva-guide-letzebuergesch-entreprisen" class="text-primary-500 hover:text-primary-600 text-sm">Innergemeinschaftlech TVA – komplette Guide →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Verglach: Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert an d'Ausland konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt automatesch d'TVA-Szenario no dem Client (Land, B2B/B2C) an applizéiert déi richteg Mentioun. VIES-Validatioun integréiert.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'criar-uma-empresa-individual-em-franca-guia-completo-2026',
                'locale' => 'pt',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">A França oferece um quadro simplificado para criar a sua empresa individual, nomeadamente com o regime da micro-empresa. Desde 2023, todas as formalidades são feitas via o balcão único do INPI. Descubra as etapas, custos e obrigações para se lançar.</p>

<h2>As formas jurídicas para empresa individual</h2>

<h3>Empresa Individual (EI)</h3>

<p>A empresa individual permite exercer uma atividade em nome próprio, sem criação de pessoa coletiva.</p>

<ul>
    <li>Sem capital social exigido</li>
    <li>Sem estatutos a redigir</li>
    <li>Atividades possíveis: comerciais, artesanais, agrícolas ou liberais</li>
    <li><strong>Desde fevereiro de 2022</strong>: o património pessoal e profissional são automaticamente separados</li>
</ul>

<h3>Micro-empresa (Auto-empresário)</h3>

<p>A micro-empresa é um regime simplificado da empresa individual com limiares de volume de negócios:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Limiar de VN (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Venda de mercadorias, alojamento</td><td class="p-2 border-b">203 100 €</td></tr>
        <tr><td class="p-2 border-b">Prestações de serviços</td><td class="p-2 border-b">83 600 €</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Bom saber</p>
    <p>A EIRL deixou de existir desde 15 de maio de 2022. O novo estatuto EI integra agora a separação automática dos patrimónios.</p>
</div>

<h2>Condições e pré-requisitos</h2>

<h3>Condições pessoais</h3>

<ul>
    <li>Ser <strong>maior de idade</strong> (ou menor emancipado)</li>
    <li>Ter um <strong>endereço em França</strong></li>
    <li>Não estar sob tutela ou curatela</li>
    <li>Não estar abrangido por uma proibição de gerir</li>
    <li>Ser de nacionalidade francesa, europeia, ou ter um título de residência que autorize o exercício</li>
</ul>

<h3>Atividades regulamentadas</h3>

<p>Certas profissões exigem diplomas ou qualificações específicas: cabeleireiro, construção, profissões de saúde, etc.</p>

<h2>Etapas de criação via o Balcão Único INPI</h2>

<h3>Etapa 1: Preparação dos documentos</h3>
<ul>
    <li>Documento de identidade (bilhete de identidade ou passaporte) em formato PDF</li>
    <li>Comprovativo de morada (se a atividade for exercida em casa)</li>
    <li>Atestados de qualificação para as atividades regulamentadas</li>
</ul>

<h3>Etapa 2: Criação da conta</h3>
<p>Aceder a <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> e criar uma conta via France Connect (recomendado) ou um identificador INPI.</p>

<h3>Etapa 3: Declaração de atividade</h3>
<ol>
    <li>Clicar em « Declarar »</li>
    <li>Selecionar « Empreendedor individual »</li>
    <li>Preencher: natureza da atividade, endereço, data de início, opções fiscais e sociais</li>
</ol>

<h3>Etapa 4: Validação e acompanhamento</h3>
<ul>
    <li>Anexar os documentos justificativos</li>
    <li>Proceder ao pagamento se necessário</li>
    <li>Acompanhar o progresso a partir do painel de controlo</li>
    <li>Inscrição automática no RNE (Registo Nacional das Empresas)</li>
</ul>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Custo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Atividade comercial</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Atividade artesanal</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Profissão liberal</td><td class="p-2 border-b text-green-600 font-semibold">Gratuito</td></tr>
        <tr><td class="p-2 border-b">Agente comercial</td><td class="p-2 border-b">23,86 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Atenção</p>
    <p>Desconfie dos sites privados que cobram taxas por um serviço normalmente gratuito.</p>
</div>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Etapa</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Declaração online</td><td class="p-2 border-b">Alguns minutos</td></tr>
        <tr><td class="p-2 border-b">Recibo de depósito</td><td class="p-2 border-b">24 horas</td></tr>
        <tr><td class="p-2 border-b">Obtenção do número SIRET</td><td class="p-2 border-b font-semibold">1 a 2 semanas</td></tr>
        <tr><td class="p-2 border-b">Notificação URSSAF</td><td class="p-2 border-b">4 a 10 semanas</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>Contribuições URSSAF</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Taxa 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Compra-revenda</td><td class="p-2 border-b">12,3 %</td></tr>
        <tr><td class="p-2 border-b">Serviços comerciais/artesanais</td><td class="p-2 border-b">21,2 %</td></tr>
        <tr><td class="p-2 border-b">Outros serviços</td><td class="p-2 border-b">25,6 %</td></tr>
        <tr><td class="p-2 border-b">Profissões liberais (Cipav)</td><td class="p-2 border-b">23,2 %</td></tr>
    </tbody>
</table>

<p><strong>Frequência:</strong> Declaração mensal ou trimestral (à escolha). Obrigação de declarar mesmo que o VN seja nulo.</p>

<h3>IVA - Isenção de base</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Limiar de base</th>
            <th class="text-left p-2 bg-slate-100">Limiar majorado</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Venda/Comércio/Alojamento</td><td class="p-2 border-b">85 000 €</td><td class="p-2 border-b">93 500 €</td></tr>
        <tr><td class="p-2 border-b">Prestações de serviços</td><td class="p-2 border-b">37 500 €</td><td class="p-2 border-b">41 250 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« IVA não aplicável, art. 293 B do CGI »</p>
</div>

<h3>CFE (Cotisation Foncière des Entreprises)</h3>

<ul>
    <li><strong>1.º ano:</strong> Isento de pagamento</li>
    <li><strong>Isenção total:</strong> Se VN anual < 5 000 €</li>
    <li><strong>Obrigação:</strong> Apresentar a declaração n.º 1447-C antes de 31 de dezembro do 1.º ano</li>
</ul>

<h3>Obrigações contabilísticas</h3>

<ol>
    <li>Estabelecer <strong>faturas conformes</strong> para cada venda/prestação</li>
    <li>Manter um <strong>livro de receitas</strong> cronológico</li>
    <li>Manter um <strong>registo de compras</strong> (se atividade de venda)</li>
    <li><strong>Conservar os documentos justificativos</strong> durante 10 anos</li>
</ol>

<h2>Apoios disponíveis</h2>

<h3>ACRE (Apoio aos Criadores e Sucessores de Empresa)</h3>

<ul>
    <li><strong>Isenção parcial</strong> das contribuições sociais no 1.º ano (até 50%)</li>
    <li>Condições: desempregados, beneficiários do RSA, jovens dos 18-25 anos, etc.</li>
    <li>Pedido a efetuar no momento da criação ou nos 45 dias seguintes</li>
</ul>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Empreendedor Individual</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">Balcão Único das Formalidades de Empresas</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-empresário</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Criar a sua empresa individual</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Criar uma micro-empresa em França é gratuito e rápido (SIRET em 1-2 semanas). As contribuições sociais variam de 12 a 26% consoante a atividade. A isenção de IVA permite não faturar IVA abaixo de certos limiares.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'criar-uma-empresa-individual-na-belgica-guia-completo-2026',
                'locale' => 'pt',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">A Bélgica oferece um quadro favorável aos trabalhadores independentes com trâmites simplificados desde a supressão dos conhecimentos de gestão de base. Este guia acompanha-o na criação da sua empresa em pessoa singular.</p>

<h2>Forma jurídica: empresa em pessoa singular</h2>

<p>A empresa em pessoa singular (trabalhador independente) é a forma mais simples para exercer sozinho uma atividade económica na Bélgica.</p>

<h3>Características principais</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspeto</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Ato constitutivo</td><td class="p-2 border-b">Nenhum exigido</td></tr>
        <tr><td class="p-2 border-b">Capital mínimo</td><td class="p-2 border-b">Nenhum exigido</td></tr>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b"><strong>Ilimitada</strong> - património pessoal e profissional confundidos</td></tr>
        <tr><td class="p-2 border-b">Estatísticas</td><td class="p-2 border-b">43% das PME belgas (510 346 empresas)</td></tr>
    </tbody>
</table>

<h2>Condições e pré-requisitos</h2>

<h3>Condições gerais</h3>

<ul>
    <li>Ter idade mínima de <strong>18 anos</strong></li>
    <li>Gozar dos seus direitos civis e políticos</li>
    <li>Ser legalmente capaz</li>
</ul>

<h3>Conhecimentos de gestão de base: SUPRIMIDOS</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Boa notícia!</p>
    <p>Os conhecimentos de gestão de base foram suprimidos em todas as regiões:</p>
    <ul class="mt-2">
        <li><strong>Flandres:</strong> desde 2018</li>
        <li><strong>Bruxelas:</strong> desde 15 de janeiro de 2024</li>
        <li><strong>Valónia:</strong> desde 1 de outubro de 2025</li>
    </ul>
</div>

<h3>Acesso à profissão</h3>

<p>Certas profissões regulamentadas exigem ainda <strong>competências profissionais específicas</strong>: cabeleireiro, padeiro, pasteleiro, mecânico, telhador, técnico de aquecimento, restaurador, etc.</p>

<h2>Etapas de criação</h2>

<h3>Etapa 1: Abrir uma conta bancária profissional</h3>
<p>Obrigatória para separar as operações profissionais e privadas.</p>

<h3>Etapa 2: Inscrever-se no Banco-Encruzilhada das Empresas (BCE)</h3>
<ul>
    <li>Passar por um <strong>balcão de empresas autorizado</strong></li>
    <li>Obtenção do <strong>número de empresa</strong> (identificador único)</li>
    <li>Verificação das competências profissionais se necessário</li>
</ul>

<h3>Etapa 3: Ativar o número de IVA</h3>
<ul>
    <li>Junto da Administração geral da Fiscalidade</li>
    <li>Pode ser feito via o balcão de empresas</li>
    <li>Possibilidade de pedir o regime de isenção de IVA (se VN < 25 000 €)</li>
</ul>

<h3>Etapa 4: Filiar-se a uma caixa de seguros sociais</h3>
<p><strong>Obrigatório ANTES do início da atividade</strong>. Filiação possível até 6 meses antes.</p>

<h3>Etapa 5: Filiar-se a uma mutualidade</h3>
<p>Obrigatório para beneficiar do seguro de doença-invalidez.</p>

<h3>Etapa 6: Subscrever os seguros necessários</h3>
<p>Seguro de responsabilidade civil profissional e outros consoante a atividade.</p>

<h2>Os 8 balcões de empresas autorizados</h2>

<ol>
    <li>Liantis (o maior)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Eunomia</li>
    <li>Formalis</li>
</ol>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Rubrica</th>
            <th class="text-left p-2 bg-slate-100">Montante (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscrição BCE via balcão</td><td class="p-2 border-b">109 - 111,50 € (isento de IVA)</td></tr>
        <tr><td class="p-2 border-b">Despesas diversas</td><td class="p-2 border-b">Variável</td></tr>
        <tr><td class="p-2 border-b font-semibold">Orçamento total estimado</td><td class="p-2 border-b font-semibold">200 - 500 €</td></tr>
    </tbody>
</table>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Trâmite</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscrição BCE via balcão</td><td class="p-2 border-b">Imediato a alguns dias</td></tr>
        <tr><td class="p-2 border-b">Ativação IVA</td><td class="p-2 border-b">Alguns dias</td></tr>
        <tr><td class="p-2 border-b">Filiação caixa social</td><td class="p-2 border-b">Imediata</td></tr>
        <tr><td class="p-2 border-b font-semibold">Processo completo</td><td class="p-2 border-b font-semibold">1 a 2 semanas</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>IVA</h3>

<h4>Regime normal</h4>
<ul>
    <li>Declaração periódica de IVA (mensal ou trimestral)</li>
    <li>Faturação com IVA</li>
    <li>Listagem anual de clientes</li>
</ul>

<h4>Regime de isenção (se VN < 25 000 €)</h4>
<ul>
    <li>Sem declaração periódica</li>
    <li>Sem IVA a faturar nem a entregar</li>
    <li>Comunicação do VN anual antes de 31 de março</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« Pequena empresa sujeita ao regime de isenção de imposto - IVA não aplicável (Art. 56bis do Código do IVA) »</p>
</div>

<h3>Contribuições sociais (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Escalão de rendimentos</th>
            <th class="text-left p-2 bg-slate-100">Taxa 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">0 a 73 447,52 €</td><td class="p-2 border-b font-semibold">20,50%</td></tr>
        <tr><td class="p-2 border-b">73 447,52 a 108 238,40 €</td><td class="p-2 border-b">14,16%</td></tr>
        <tr><td class="p-2 border-b">Acima de 108 238,40 €</td><td class="p-2 border-b">Isento</td></tr>
    </tbody>
</table>

<p><strong>Contribuição mínima 2026:</strong> 450,15 €/trimestre (trabalhador independente a título principal)</p>

<p><strong>Funcionamento:</strong></p>
<ul>
    <li>Pagamento <strong>trimestral</strong></li>
    <li>Contribuições inicialmente <strong>provisórias</strong> (baseadas em rendimentos N-3)</li>
    <li>Regularização logo que os rendimentos definitivos sejam conhecidos</li>
</ul>

<h3>Obrigações contabilísticas</h3>

<h4>Contabilidade simplificada (VN < 500 000 €)</h4>
<p>3 diários obrigatórios:</p>
<ol>
    <li><strong>Diário de compras:</strong> lista das despesas</li>
    <li><strong>Diário de vendas:</strong> visão cronológica das faturas</li>
    <li><strong>Diário de tesouraria:</strong> livro de caixa + livro de banco</li>
</ol>

<p><strong>Conservação dos documentos:</strong> 10 anos</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">SPF Economia - Trâmites para um trabalhador independente</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub para empreendedores</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Contribuições sociais</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">SPF Finanças - Regime de isenção de IVA</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>Tornar-se trabalhador independente na Bélgica custa entre 200 e 500 € e demora 1 a 2 semanas. As contribuições sociais representam 20,5% do rendimento. A isenção de IVA é possível se o VN se mantiver abaixo dos 25 000 €/ano.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026',
                'locale' => 'pt',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">O Luxemburgo oferece um ambiente favorável aos empreendedores com trâmites administrativos relativamente simples e custos de criação moderados. Este guia acompanha-o passo a passo na criação da sua empresa individual no Grão-Ducado.</p>

<h2>As formas jurídicas para empresa individual</h2>

<p>No Luxemburgo, o empreendedor independente exerce a sua profissão em nome próprio, na qualidade de:</p>

<ul>
    <li><strong>Comerciante</strong>: para as atividades comerciais</li>
    <li><strong>Artesão</strong>: para as atividades artesanais</li>
    <li><strong>Trabalhador intelectual independente</strong>: para as profissões liberais</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>Não existe equivalente exato ao estatuto de auto-empresário francês no Luxemburgo. A empresa individual é a forma mais próxima e mais simples.</p>
</div>

<h3>Características principais</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspeto</th>
            <th class="text-left p-2 bg-slate-100">Detalhe</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Personalidade jurídica</td><td class="p-2 border-b">Nenhuma - o empreendedor age em nome próprio</td></tr>
        <tr><td class="p-2 border-b">Capital mínimo</td><td class="p-2 border-b">Nenhum capital mínimo exigido</td></tr>
        <tr><td class="p-2 border-b">Responsabilidade</td><td class="p-2 border-b"><strong>Ilimitada</strong> - responsável pelo seu património pessoal</td></tr>
        <tr><td class="p-2 border-b">Formalismo</td><td class="p-2 border-b">Mínimo - sem estatutos a redigir</td></tr>
    </tbody>
</table>

<h2>Condições e pré-requisitos</h2>

<h3>Autorização de estabelecimento (obrigatória)</h3>

<p>Toda a atividade económica exercida de forma habitual exige uma <strong>autorização de estabelecimento prévia</strong>.</p>

<p><strong>Condições a cumprir:</strong></p>

<ul>
    <li><strong>Estabelecimento físico</strong>: instalação material apropriada no Luxemburgo</li>
    <li><strong>Gestão efetiva</strong>: presença física e gestão diária pelo titular</li>
    <li><strong>Honorabilidade profissional</strong>: registo criminal limpo, respeito das obrigações fiscais e sociais anteriores</li>
    <li><strong>Qualificação profissional</strong>: consoante a atividade visada</li>
</ul>

<h3>Qualificações profissionais requeridas</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tipo de atividade</th>
            <th class="text-left p-2 bg-slate-100">Qualificação requerida</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Atividades comerciais</td><td class="p-2 border-b">Geralmente sem diploma específico exigido</td></tr>
        <tr><td class="p-2 border-b">Atividades artesanais</td><td class="p-2 border-b">DAP, CATP ou Diploma de Mestre</td></tr>
        <tr><td class="p-2 border-b">Profissões liberais</td><td class="p-2 border-b">Diplomas específicos consoante a profissão</td></tr>
    </tbody>
</table>

<h2>Etapas de criação detalhadas</h2>

<h3>Etapa 1: Elaboração do projeto</h3>
<ul>
    <li>Redigir um plano de negócios</li>
    <li>Contactar os organismos de acompanhamento (House of Entrepreneurship, Câmara de Comércio, Câmara dos Ofícios)</li>
</ul>

<h3>Etapa 2: Verificação dos pré-requisitos</h3>
<ul>
    <li>Verificar a disponibilidade do nome comercial</li>
    <li>Assegurar-se que possui as qualificações requeridas</li>
    <li>Pedir o reconhecimento dos diplomas se necessário</li>
</ul>

<h3>Etapa 3: Pedido de autorização de estabelecimento</h3>
<p><strong>Onde:</strong> Online via MyGuichet.lu (com certificado LuxTrust) ou por correio postal</p>
<p><strong>Documentos requeridos:</strong></p>
<ul>
    <li>Formulário de pedido</li>
    <li>Comprovativos de qualificação profissional</li>
    <li>Certificado de registo criminal (boletim n.º 3)</li>
    <li>Cópia do bilhete de identidade</li>
    <li>Comprovativo de pagamento da taxa de chancelaria (50 EUR)</li>
</ul>

<h3>Etapa 4: Registo no RCS</h3>
<p><strong>Onde:</strong> Depósito eletrónico no site LBR (Luxembourg Business Registers)</p>
<p><strong>Documentos requeridos:</strong></p>
<ul>
    <li>Formulário de requisição</li>
    <li>Autorização de estabelecimento</li>
    <li>Documento de identidade</li>
    <li>Certidão de casamento / contrato de casamento (se aplicável)</li>
</ul>

<h3>Etapa 5: Inscrição na segurança social</h3>
<p>Inscrição junto do CCSS (Centre Commun de la Sécurité Sociale) como trabalhador independente.</p>

<h3>Etapa 6: Inscrição fiscal</h3>
<ul>
    <li>Inscrição junto da Administração das Contribuições Diretas</li>
    <li>Inscrição no IVA se o volume de negócios > 50 000 EUR</li>
</ul>

<h2>Custos de criação</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Rubrica</th>
            <th class="text-left p-2 bg-slate-100">Montante</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorização de estabelecimento</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">Registo RCS</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Reconhecimento de diploma</td><td class="p-2 border-b">75 EUR (se necessário)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Total estimado</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Prazos médios</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Trâmite</th>
            <th class="text-left p-2 bg-slate-100">Prazo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorização de estabelecimento</td><td class="p-2 border-b">Até 3 meses</td></tr>
        <tr><td class="p-2 border-b">Reconhecimento de diploma</td><td class="p-2 border-b">2 a 6 semanas</td></tr>
        <tr><td class="p-2 border-b">Registo RCS</td><td class="p-2 border-b">Alguns dias</td></tr>
        <tr><td class="p-2 border-b font-semibold">Prazo total estimado</td><td class="p-2 border-b font-semibold">1 a 3 meses</td></tr>
    </tbody>
</table>

<h2>Obrigações após a criação</h2>

<h3>IVA (Imposto sobre o Valor Acrescentado)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situação</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">VN anual ≤ 50 000 EUR</td><td class="p-2 border-b">Isenção de IVA (sem inscrição obrigatória)</td></tr>
        <tr><td class="p-2 border-b">VN anual > 50 000 EUR</td><td class="p-2 border-b">Inscrição obrigatória + declarações periódicas</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Menção obrigatória em isenção</p>
    <p>« IVA não aplicável, art. 57 do Código do IVA luxemburguês (Regime de isenção de imposto) »</p>
</div>

<h3>Contribuições sociais (CCSS)</h3>

<p>As contribuições representam cerca de <strong>25,3%</strong> do rendimento, repartidas da seguinte forma:</p>

<ul>
    <li>Seguro de doença (cuidados): 5,60%</li>
    <li>Seguro de doença (subsídios): 0,50%</li>
    <li>Seguro de dependência: 1,40%</li>
    <li>Seguro de pensão: 17,00%</li>
    <li>Seguro de acidentes: 0,65%</li>
    <li>Saúde no trabalho: 0,14%</li>
</ul>

<h3>Contabilidade</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Volume de negócios anual</th>
            <th class="text-left p-2 bg-slate-100">Obrigação</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100 000 EUR sem IVA</td><td class="p-2 border-b">Contabilidade simplificada</td></tr>
        <tr><td class="p-2 border-b">≥ 100 000 EUR sem IVA</td><td class="p-2 border-b">Contabilidade normalizada obrigatória</td></tr>
    </tbody>
</table>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html" target="_blank" rel="noopener">Guichet.lu - Empresa individual</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Autorização de estabelecimento</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Trabalhadores independentes</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <p>A criação de uma empresa individual no Luxemburgo é relativamente simples e pouco dispendiosa (cerca de 100-150 EUR). O processo demora geralmente 1 a 3 meses e inclui a obtenção da autorização de estabelecimento e o registo no RCS. As contribuições sociais representam cerca de 25% do rendimento.</p>
</div>
ARTICLE_HTML,
            ],
        ];
    }
};
