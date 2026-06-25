<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * German translations of the 5 scheduled June 2026 long-tail articles.
 * Idempotent: skips any article whose slug already exists.
 */
class BlogScheduledJune2026DeSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip DE: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'cover_image' => $article['cover_image'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'de',
                'translation_key' => $article['translation_key'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Created DE: {$article['slug']}");
        }
    }

    protected function articles(): array
    {
        return [
            [
                'category_id' => 2,
                'title' => "AED-Steuerpruefung Luxemburg: Vorbereitung in 2026",
                'slug' => 'aed-steuerpruefung-luxemburg-2026-vorbereitung',
                'translation_key' => 'controle-fiscal-aed-luxembourg-2026-preparation',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => 'AED-Steuerpruefung Luxemburg 2026: Vorbereitungsleitfaden | faktur.lu',
                'meta_description' => "Alles ueber AED-Steuerpruefungen in Luxemburg: Vorbereitung, FAIA, erforderliche Dokumente, typischer Ablauf. Praxisleitfaden 2026 fuer Freiberufler und KMU.",
                'excerpt' => "Eine Pruefungsmitteilung von der AED beunruhigt jeden Selbststaendigen in Luxemburg. Hier erfahren Sie, was vorzubereiten ist und wie faktur.lu Ihnen hilft, die Pruefung in 30 Minuten statt 3 Tagen zu bestehen.",
                'published_at' => '2026-06-01 09:00:00',
                'content' => <<<HTML
<p class="lead">Eine Pruefungsmitteilung von der <strong>Verwaltung fuer Eintragung, Domaenen und Mehrwertsteuer (AED)</strong> beunruhigt jeden Selbststaendigen und KMU-Leiter in Luxemburg. Mit guter Vorbereitung verlaeuft eine Steuerpruefung jedoch in wenigen Stunden ohne Stress. So bereiten Sie sich 2026 vor.</p>

<h2>Wer ist von einer AED-Pruefung betroffen?</h2>

<p>Jedes in Luxemburg mehrwertsteuerlich registrierte Unternehmen kann von der AED geprueft werden. In der Praxis zielt die Verwaltung ab auf:</p>

<ul>
    <li><strong>Schnell wachsende Unternehmen</strong> mit ungewoehnlichen Umsatzschwankungen</li>
    <li><strong>Risikobranchen</strong> (Handel, Gastronomie, Bauwesen, Beratung)</li>
    <li><strong>Atypische MwSt-Erklaerungen</strong> (wiederholtes MwSt-Guthaben, grosse innergemeinschaftliche Vorgaenge)</li>
    <li><strong>Zufallspruefungen</strong>: zwischen 3% und 5% der Unternehmen werden jaehrlich nach statistischen Kriterien geprueft</li>
</ul>

<p>Eine Pruefung kann <strong>die letzten 5 Jahre</strong> umfassen (Standardverjaehrung, 10 Jahre bei Betrugsverdacht).</p>

<h2>Die 3 Arten der AED-Pruefung</h2>

<h3>1. Schriftliche Pruefung</h3>

<p>Die AED bittet Sie, Unterlagen per Post oder elektronisch zu senden. Kein Besuch. Dies ist die haeufigste und am wenigsten belastende Art.</p>

<h3>2. Vor-Ort-Pruefung</h3>

<p>Ein AED-Pruefer kommt zu Ihnen, um Buchhaltung, Rechnungen und Belege zu pruefen. Schriftlich mit mindestens 8 Tagen Vorankuendigung angekuendigt.</p>

<h3>3. Unangekuendigte Pruefung</h3>

<p>Selten, fuer schwere Betrugsverdachtsfaelle reserviert. Der Pruefer erscheint ohne Vorankuendigung. Sie haben das Recht, die Anwesenheit Ihres Treuhaenders waehrend der Pruefung zu verlangen.</p>

<h2>Von der AED angeforderte Dokumente</h2>

<p>Die Liste variiert je nach Taetigkeit, aber folgende Dokumente werden systematisch geprueft:</p>

<ul>
    <li><strong>Alle ausgestellten und erhaltenen Rechnungen</strong> der Pruefperiode, mit obligatorischen LIVA-Angaben</li>
    <li><strong>Die FAIA-Datei</strong> (Standardisierte Pruefdatei der AED) in Version 2.01</li>
    <li><strong>Das Einnahmen-/Ausgabenbuch</strong> oder die vollstaendige Buchhaltung je nach Regime</li>
    <li><strong>Die monatlichen oder vierteljaehrlichen MwSt-Erklaerungen</strong></li>
    <li><strong>Berufliche Kontoauszuege</strong> der Periode</li>
    <li><strong>Belege fuer abgezogene Ausgaben</strong> (Spesennotizen, Lieferantenrechnungen)</li>
    <li><strong>Wichtige Kunden-/Lieferantenvertraege</strong></li>
    <li><strong>Nachweis des Reverse-Charge-Verfahrens</strong> fuer innergemeinschaftliches B2B (ueber VIES validierte MwSt-Nummern)</li>
</ul>

<h2>FAIA: der kritische Pruefpunkt</h2>

<p>Die <strong>FAIA</strong> ist seit 2020 das Herzstueck aller AED-Pruefungen. Wenn Sie keine FAIA gemaess Version 2.01 vorlegen koennen, gilt Ihre Buchhaltung als nicht standardkonform.</p>

<p>Die FAIA ist eine strukturierte XML-Datei mit:</p>

<ul>
    <li>Unternehmenskopfdaten (Firma, MwSt-Nummer, RCS, Periode)</li>
    <li>Alle Verkaufsrechnungen der Pruefperiode</li>
    <li>Zugehoerige Buchungen</li>
    <li>Soll-/Habensummen pro Bewegung</li>
</ul>

<p>Wenn Sie mit Excel oder einem System rechnen, das keine FAIA generiert, muessen Sie sie manuell rekonstruieren - was <strong>mehrere Arbeitstage</strong> dauern kann und Fehlerquellen birgt.</p>

<h2>Ablauf einer Vor-Ort-Pruefung</h2>

<ol>
    <li><strong>Schriftliche Mitteilung</strong> mit mindestens 8 Tagen Frist (ausser unangekuendigte Pruefung)</li>
    <li><strong>Ankunft der AED-Pruefer</strong> mit Pruefauftrag</li>
    <li><strong>Dokumentenanforderung</strong> nach vorgefertigter Liste</li>
    <li><strong>Buchungspruefung</strong> und Abgleich mit MwSt-Erklaerungen</li>
    <li><strong>Gespraech</strong> ueber ungewoehnliche Vorgaenge oder Abweichungen</li>
    <li><strong>Mitteilung der Schlussfolgerungen</strong> per Post innerhalb von 60 Tagen</li>
</ol>

<p>Sie haben dann <strong>30 Tage zum Widerspruch</strong> per Beschwerde beim AED-Direktor.</p>

<h2>Die 5 Fehler, die teuer werden</h2>

<ol>
    <li><strong>Nicht-sequenzielle Rechnungsnummerierung</strong> (Artikel 61 LIVA): Luecken oder Duplikate = Vermutung verdeckter Einnahmen</li>
    <li><strong>Fehlende LIVA-Angaben</strong> auf Rechnungen: MwSt-Nummer, RCS, Matricule, Ausstellungsdatum, etc.</li>
    <li><strong>Undokumentierter B2B-Reverse-Charge</strong>: ohne VIES-Validierung der Kunden-MwSt-Nummer kann die AED als steuerpflichtigen Vorgang umqualifizieren</li>
    <li><strong>Fehlende oder unleserliche Ausgabenbelege</strong> (handgeschriebene Notiz, verblasster Beleg)</li>
    <li><strong>Inkonsistenzen zwischen MwSt-Erklaerung und Rechnungen</strong>: schon kleine Abweichungen loesen vertiefte Pruefungen aus</li>
</ol>

<h2>In 5 Schritten auf die Pruefung vorbereiten</h2>

<p>Ob Freiberufler oder KMU, hier die Checkliste ab Mitteilungserhalt:</p>

<ol>
    <li><strong>FAIA vorbereiten</strong> fuer die angeforderte Periode - idealerweise per Klick aus Ihrer Rechnungssoftware</li>
    <li><strong>Alle ausgestellten Rechnungen drucken oder exportieren</strong> (PDF/A fuer Rechtsarchivierung)</li>
    <li><strong>Konsistenz pruefen</strong> mit Ihren eingereichten MwSt-Erklaerungen</li>
    <li><strong>Pro Monat ein Dossier</strong>: Rechnungen + Kontoauszuege + zugehoerige MwSt-Erklaerung</li>
    <li><strong>Treuhaender informieren</strong> und um seine Anwesenheit waehrend der Pruefung bitten (empfohlen)</li>
</ol>

<h2>Wie faktur.lu Sie vorbereitet</h2>

<p>Mit einer Luxemburg-konformen Rechnungsplattform laesst sich die AED-Pruefung <strong>in 30 Minuten statt mehreren Tagen</strong> vorbereiten:</p>

<ul>
    <li>Automatische sequenzielle Nummerierung gemaess <strong>Artikel 61 LIVA</strong></li>
    <li>Obligatorische LIVA-Angaben automatisch auf jeder Rechnung generiert</li>
    <li>Echtzeit-VIES-Validierung fuer innergemeinschaftliche B2B-Vorgaenge</li>
    <li><strong>FAIA 2.01</strong>-Export per Klick fuer jede Periode</li>
    <li><strong>PDF/A</strong>-Archivierung der Rechnungen (gesetzliche 10-Jahres-Norm, Art. 16 Handelsgesetzbuch)</li>
    <li>Treuhaender-Portal: Ihr Treuhaender erhaelt alles ohne E-Mail-Pingpong</li>
</ul>

<p><a href="/de/register" class="text-primary-500 hover:underline font-medium">Kostenlos mit faktur.lu starten</a> und Ihre naechste Pruefung stressfrei vorbereiten.</p>

<h2>FAQ - AED-Steuerpruefung</h2>

<h3>Wie lange dauert eine AED-Pruefung?</h3>
<p>Schriftliche Pruefung: 2 bis 4 Wochen (je nach Einreichungszeit). Vor-Ort-Pruefung: typischerweise 1 bis 3 Tage im Unternehmen + 30 bis 60 Tage AED-interne Analyse.</p>

<h3>Kann ich eine Pruefung verweigern?</h3>
<p>Nein. Pruefungsverweigerung wird mit einer Verwaltungsstrafe sanktioniert und Bosheit vermutet. Sie koennen jedoch aus berechtigtem Grund eine Verschiebung beantragen (Krankheit, laengere Abwesenheit) und die Anwesenheit Ihres Treuhaenders verlangen.</p>

<h3>Welche Strafen drohen bei Fehlern?</h3>
<p>Variabel: 10% bis 50% der nicht erklaerten MwSt bei gutglaeubigen Versaeumnissen, bis 200% bei nachweislichem Betrug. Pauschalstrafen (50 EUR bis 25.000 EUR) koennen auch fuer formale Versaeumnisse verhaengt werden.</p>

<h3>Kann die Pruefung alte Jahre umfassen?</h3>
<p>Ja: Verjaehrung 5 Jahre bei gutglaeubigen Versaeumnissen, 10 Jahre bei Betrugsverdacht. Daher ist 10-jaehrige Archivierung entscheidend (Pflicht, Art. 16 Handelsgesetzbuch).</p>

<h3>Was tun, wenn ich mit den Schlussfolgerungen nicht einverstanden bin?</h3>
<p>Sie haben 30 Tage, um schriftlich Beschwerde beim AED-Direktor einzulegen, Argumente und Belege beizufuegen. Bei Ablehnung Rechtsmittel beim Verwaltungsgericht innerhalb 3 Monaten.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereiten Sie Ihre naechste Pruefung heute vor</h3>
    <p class="text-primary-800 mb-4">Automatische Nummerierung, FAIA 2.01 per Klick, konforme LIVA-Angaben, 10-Jahres-PDF/A-Archivierung. AED-Pruefung wird Routine.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos starten</a>
</div>
HTML,
            ],

            [
                'category_id' => 2,
                'title' => "FAIA Luxemburg: vollstaendiger Leitfaden zur Pruefdatei in 2026",
                'slug' => 'faia-luxemburg-vollstaendiger-leitfaden-pruefdatei-2026',
                'translation_key' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
                'meta_title' => 'FAIA Luxemburg 2026: vollstaendiger Leitfaden zur AED-Pruefdatei | faktur.lu',
                'meta_description' => "FAIA fuer Luxemburger Freiberufler und KMU erklaert: was es ist, wer betroffen ist, wie eine konforme FAIA 2.01 erstellt wird. Gratis-Validator inklusive.",
                'excerpt' => "FAIA ist das von der AED bei Steuerpruefungen geforderte Format. Hier alles 2026 wichtige: XML-Datei-Struktur, Betroffene, Generierung und kostenlose Validierung.",
                'published_at' => '2026-06-08 09:00:00',
                'content' => <<<HTML
<p class="lead">Die <strong>FAIA</strong> (Standardisierte Pruefdatei der AED) ist der von der luxemburgischen Steuerverwaltung bei jeder MwSt-Pruefung geforderte Standard. Wenige Freiberufler und KMU wissen wirklich, was sie enthaelt. Dieser Leitfaden erklaert alles 2026.</p>

<h2>Was ist die FAIA?</h2>

<p>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei nach dem internationalen <strong>SAF-T</strong>-Standard (Standard Audit File for Tax) der OECD. Sie buendelt alle Buchhaltungsdaten eines Unternehmens ueber einen bestimmten Zeitraum in einem von AED-Tools maschinenlesbaren Format.</p>

<p>Die aktuell gueltige Version ist <strong>Version 2.01</strong>, von der AED 2020 veroeffentlicht. Das offizielle XSD-Schema ist auf der Verwaltungswebsite verfuegbar und bei Konformitaetsstreitigkeiten massgeblich.</p>

<h2>Wer muss eine FAIA bereitstellen?</h2>

<p>Jedes mehrwertsteuerlich in Luxemburg registrierte Unternehmen <strong>muss bei einer Steuerpruefung eine konforme FAIA produzieren koennen</strong>. Dies betrifft:</p>

<ul>
    <li><strong>Freiberufler und Selbststaendige</strong>, einschliesslich unter MwSt-Befreiungsregime</li>
    <li><strong>KMU</strong> jeden Regimes (vereinfacht oder normal)</li>
    <li><strong>Handelsgesellschaften</strong> (SARL, SA, SAS)</li>
    <li><strong>Vereine</strong>, die MwSt-pflichtige wirtschaftliche Taetigkeit ausueben</li>
</ul>

<p>Die Pflicht ist nicht monatliche Erstellung - es genuegt, sie <strong>auf AED-Anfrage</strong> innerhalb angemessener Frist (typisch 15 Tage nach Mitteilung) produzieren zu koennen.</p>

<h2>Was enthaelt eine FAIA?</h2>

<p>Eine FAIA 2.01 ist in mehrere Pflichtsektionen strukturiert:</p>

<h3>1. Kopfdaten (Header)</h3>

<ul>
    <li>Firma, MwSt-Nummer, RCS, Matricule</li>
    <li>Unternehmensadresse</li>
    <li>Abgedeckter Zeitraum (Start- und Enddatum)</li>
    <li>Erstellungsdatum der Datei</li>
    <li>Waehrung (EUR)</li>
</ul>

<h3>2. Kontenplan (MasterFiles)</h3>

<p>Liste der verwendeten Konten (Kunden, Lieferanten, Sachkonten). Fuer Freiberufler oder kleine Struktur kann diese Sektion minimal sein.</p>

<h3>3. Verkaufsrechnungen (SalesInvoices)</h3>

<ul>
    <li>Rechnungsnummer (sequenziell, gemaess Artikel 61 LIVA)</li>
    <li>Abschlussdatum</li>
    <li>Kunde: Name, MwSt-Nummer, Adresse</li>
    <li>Rechnungspositionen: Beschreibung, Menge, Stueckpreis, MwSt</li>
    <li>Summen netto, MwSt, brutto</li>
</ul>

<h3>4. Buchungen (GeneralLedgerEntries)</h3>

<p>Zugehoerige Buchhaltungseintraege (Soll/Haben pro Konto).</p>

<h3>5. Fussbereich (Footer)</h3>

<p>Kontrollsummen: Gesamtsoll, Gesamthaben, Transaktionsanzahl.</p>

<h2>Die 4 haeufigsten FAIA-Fehler</h2>

<ol>
    <li><strong>Rechnungsnummerierung nicht konform</strong> mit Artikel 61 LIVA (Luecken oder Duplikate). Der offizielle Validator weist die Datei ab.</li>
    <li><strong>Fehlende Pflichtfelder</strong>: Kunden-MwSt-Nummer, vollstaendige Adresse, Reverse-Charge-Hinweis fuer innergemeinschaftliches B2B.</li>
    <li><strong>Inkonsistente Summen</strong> zwischen Sektionen (z. B. Rechnungssumme ≠ deklarierte Total Sales Invoices).</li>
    <li><strong>Falsches Datumsformat</strong>: FAIA erfordert ISO 8601 (YYYY-MM-DD), nicht DD/MM/YYYY.</li>
</ol>

<h2>Wie eine konforme FAIA erstellen?</h2>

<p>Drei Optionen je nach Situation:</p>

<h3>Option 1 - FAIA-native Rechnungssoftware</h3>

<p>Die einfachste und sicherste Methode. Eine Software wie <a href="/de" class="text-primary-500 hover:underline font-medium">faktur.lu</a> erstellt FAIA 2.01 auf Abruf, fuer jede Periode, per Klick. Die Datei ist automatisch XSD-konform.</p>

<h3>Option 2 - Export aus Buchhaltungssoftware</h3>

<p>Sage BOB 50, Sage 100 und die meisten professionellen Buchhaltungssoftwares ermoeglichen FAIA-Export. Pruefen Sie mit Ihrem Anbieter, dass Version 2.01 unterstuetzt wird.</p>

<h3>Option 3 - Manuelle Erstellung (abgeraten)</h3>

<p>Theoretisch mit XML-Entwickler moeglich, aber fehleranfaellig. Nur fuer Extremfaelle (Legacy-Daten, Migration).</p>

<h2>FAIA kostenlos validieren</h2>

<p>Bevor Sie Ihre FAIA an die AED senden, validieren Sie sie gegen das offizielle Schema, um Fehler vor dem Pruefer zu entdecken.</p>

<p>faktur.lu bietet einen <a href="/de/faia-validator" class="text-primary-500 hover:underline font-medium">kostenlosen FAIA-Validator</a>, der:</p>

<ul>
    <li>XML-Konformitaet mit AED-XSD-Schema 2.01 prueft</li>
    <li>Fehlende Pflichtfelder erkennt</li>
    <li>Summen-Konsistenz prueft</li>
    <li>Sequenzialitaet der Rechnungsnummern prueft</li>
    <li>Keine Daten speichert (Datei bleibt auf Ihrem Rechner)</li>
</ul>

<h2>FAQ - FAIA</h2>

<h3>Muss ich jaehrlich eine FAIA senden?</h3>
<p>Nein. FAIA wird nur <strong>auf AED-Anfrage</strong> bei einer Pruefung erstellt. Sie muessen sie aber rasch (binnen 15 Tagen) erzeugen koennen.</p>

<h3>Was passiert, wenn ich keine FAIA liefern kann?</h3>
<p>Der Pruefer betrachtet Ihre Buchhaltung als nicht standardkonform und kann eine <strong>Verwaltungsstrafe</strong> verhaengen oder Ihren Umsatz pauschal schaetzen (zu Ihren Lasten). Im schlimmsten Fall: MwSt-Nachzahlung mit Zuschlag.</p>

<h3>Ist FAIA bei MwSt-Befreiung obligatorisch?</h3>
<p>Ja. Auch unter Befreiung (Artikel 56 ter LIVA) muessen Sie auf Anfrage eine FAIA produzieren koennen. Die FAIA enthaelt dann "MwSt nicht anwendbar"-Hinweise pro Rechnung.</p>

<h3>Wie weiss ich vor einer Pruefung, dass meine FAIA konform ist?</h3>
<p>Nutzen Sie einen <a href="/de/faia-validator" class="text-primary-500 hover:underline font-medium">FAIA-Validator</a>, der sie mit dem offiziellen AED-XSD-Schema 2.01 vergleicht. Kostenlos, ohne Registrierung, 5 Sekunden.</p>

<h3>Kann mein Treuhaender die FAIA fuer mich erstellen?</h3>
<p>Ja, Ihr Treuhaender kann FAIA aus seinem eigenen Tool erzeugen oder Ihre ueber ein Treuhaender-Portal abrufen. Bei faktur.lu laden Sie Ihren Treuhaender mit Leseberechtigung ein und er greift per Klick auf die FAIA zu.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 per Klick, AED-konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt Ihre FAIA fuer jede Periode, automatisch gegen das offizielle XSD-Schema validiert. Ab dem Gratis-Plan enthalten.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis testen</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artikel 21 LIVA: Reverse-Charge bei innergemeinschaftlichem B2B fuer Luxemburger Freiberufler",
                'slug' => 'artikel-21-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg',
                'translation_key' => 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200&h=630&fit=crop',
                'meta_title' => 'Artikel 21 LIVA: B2B-Reverse-Charge in der EU Luxemburg | faktur.lu',
                'meta_description' => "B2B-Kunde in Europa rechnen Sie? Artikel 21 LIVA schreibt den Reverse-Charge vor. Hier die Regeln, Pflichtangaben, VIES-Validierung und Fallen 2026.",
                'excerpt' => "Sind Sie Luxemburger Freiberufler und rechnen einen B2B-Kunden in Frankreich, Belgien oder Deutschland? Artikel 21 LIVA gilt: MwSt-Reverse-Charge. Hier die Regeln, Pflichtangaben und Falle-Vermeidung.",
                'published_at' => '2026-06-15 09:00:00',
                'content' => <<<HTML
<p class="lead">Sind Sie Luxemburger Freiberufler und rechnen einen B2B-Kunden in Frankreich, Deutschland oder Belgien? <strong>Artikel 21 LIVA</strong> gilt: Reverse-Charge. Vergessen Sie den Hinweis oder fakturieren Sie irrtuemlich luxemburgische MwSt, riskieren Sie eine Steuernachpruefung. Hier die klaren Regeln.</p>

<h2>Artikel 21 LIVA in Kuerze</h2>

<p><strong>Artikel 21 des luxemburgischen MwSt-Gesetzes (LIVA)</strong> setzt das europaeische Reverse-Charge-Prinzip fuer Dienstleistungen zwischen Steuerpflichtigen in zwei EU-Mitgliedstaaten um.</p>

<p>Konkret: wenn ein Luxemburger Freiberufler eine Dienstleistung an ein Unternehmen in einem anderen EU-Land fakturiert:</p>

<ul>
    <li>Luxemburgische MwSt ist <strong>nicht geschuldet</strong></li>
    <li>Der auslaendische Kunde <strong>schuldet die MwSt selbst</strong> in seinem Land zum lokalen Satz</li>
    <li>Die Rechnung erwaehnt ausdruecklich <strong>"Autoliquidation, article 21 LIVA"</strong></li>
</ul>

<h2>Wann gilt Artikel 21 LIVA?</h2>

<p>Drei kumulative Bedingungen muessen erfuellt sein:</p>

<ol>
    <li><strong>Dienstleistung</strong> (nicht Warenverkauf - andere Regeln)</li>
    <li><strong>Geschaeftskunde (B2B)</strong> in einem anderen EU-Mitgliedstaat</li>
    <li><strong>Gueltige innergemeinschaftliche MwSt-Nummer</strong> des Kunden (VIES-Validierung Pflicht)</li>
</ol>

<p>Fehlt eine Bedingung, aendert sich die Regel:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">MwSt-Regel</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B-Kunde in EU mit gueltiger MwSt-Nummer</td><td class="border border-gray-300 px-4 py-2"><strong>Reverse-Charge (Art. 21 LIVA)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C (Verbraucher) in EU</td><td class="border border-gray-300 px-4 py-2">Luxemburgische MwSt 17% (oder OSS bei Schwellenueberschreitung)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B in EU ohne validierte MwSt-Nummer</td><td class="border border-gray-300 px-4 py-2">Luxemburgische MwSt 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Drittland-Kunde (B2B oder B2C)</td><td class="border border-gray-300 px-4 py-2">Befreiung mit angepasster Angabe</td></tr>
    </tbody>
</table>

<h2>VIES-Validierung, kritischer Schritt</h2>

<p>VIES (VAT Information Exchange System) ist der EU-Dienst zur Echtzeit-Pruefung einer innergemeinschaftlichen MwSt-Nummer.</p>

<p>Vor einer Reverse-Charge-Rechnung <strong>muessen Sie unbedingt</strong> die Kunden-MwSt-Nummer ueber VIES validieren. Ist die Nummer am Rechnungsdatum nicht gueltig, betrachtet die AED die Leistung als in Luxemburg steuerpflichtig und fordert nicht erhobene MwSt nach.</p>

<p>Der offizielle VIES-Dienst ist unter <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a> erreichbar. In der Praxis sollte Ihre Rechnungssoftware dies automatisch pro Rechnung tun.</p>

<h2>Pflichtangaben auf der Rechnung</h2>

<p>Eine Reverse-Charge-Rechnung nach Artikel 21 LIVA muss zusaetzlich zu ueblichen Angaben enthalten:</p>

<ul>
    <li>Ihre <strong>luxemburgische MwSt-Nummer</strong> (LU + 8 Ziffern)</li>
    <li>Die <strong>Kunden-MwSt-Nummer</strong> (Laenderpraefix + Ziffern)</li>
    <li>Die genaue Erwaehnung: <strong>"Autoliquidation, article 21 LIVA"</strong> (oder Aequivalent in Kundensprache)</li>
    <li><strong>Keine MwSt zugefuegt</strong> auf Summe (MwSt = 0 EUR)</li>
    <li>Nettosumme = Bruttosumme</li>
</ul>

<p><strong>Wichtig</strong>: Der "Reverse-Charge"-Hinweis muss sichtbar und verstaendlich sein. Die AED hat schon Unternehmen sanktioniert, die vage Formulierungen wie "MwSt nicht anwendbar" ohne Praezisierung verwendeten.</p>

<h2>Konkretes Beispiel</h2>

<p>Marie ist freiberufliche UX-Designerin in Luxemburg-Stadt. Sie rechnet <strong>2.500 EUR netto</strong> an <strong>Acme SA</strong>, Pariser Agentur (MwSt-Nummer FR12345678901, ueber VIES validiert).</p>

<p>Ihre Rechnung enthaelt:</p>

<ul>
    <li><strong>Aussteller</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Empfaenger</strong>: Acme SA, 10 rue de Rivoli 75001 Paris, FR12345678901</li>
    <li><strong>Leistung</strong>: UX-Design Website - 2.500,00 EUR</li>
    <li><strong>MwSt (0%)</strong>: 0,00 EUR</li>
    <li><strong>Bruttosumme</strong>: 2.500,00 EUR</li>
    <li><strong>Hinweis</strong>: <em>"Autoliquidation, article 21 LIVA - MwSt vom Empfaenger in seinem Land geschuldet."</em></li>
</ul>

<p>Acme SA erklaert franzoesische MwSt (20%) in ihrer eigenen MwSt-Erklaerung, als erhobene und abziehbare MwSt zugleich (neutraler Vorgang).</p>

<h2>Die 3 teuren Fehler</h2>

<ol>
    <li><strong>VIES-Validierung vergessen</strong>: stellt sich die MwSt-Nummer als ungueltig oder ausgesetzt heraus, muessen Sie luxemburgische MwSt (17%) erheben. Ohne zahlenden Kunden geht das auf Ihre Kosten.</li>
    <li><strong>Fehlender oder vager "Reverse-Charge"-Hinweis</strong>: AED qualifiziert als in Luxemburg steuerpflichtig um.</li>
    <li><strong>Falsche buchhalterische Behandlung</strong>: Der Vorgang muss in der luxemburgischen MwSt-Erklaerung (Rubrik innergemeinschaftliche Dienstleistungen) und der <strong>zusammenfassenden VIES-Meldung</strong> (monatlich oder vierteljaehrlich) erscheinen.</li>
</ol>

<h2>Wie faktur.lu Sie schuetzt</h2>

<p>faktur.lu erkennt automatisch Reverse-Charge-Bedingungen und wendet Artikel 21 LIVA an:</p>

<ul>
    <li><strong>Echtzeit-VIES-Validierung</strong> bei Eingabe eines innergemeinschaftlichen B2B-Kunden</li>
    <li>Hinweis <strong>"Autoliquidation, article 21 LIVA"</strong> automatisch auf der Rechnung</li>
    <li><strong>MwSt auf 0 erzwungen</strong> fuer betroffene Leistungen, Berechnungen geprueft</li>
    <li><strong>Zusammenfassende VIES-Meldung</strong> automatisch am Periodenende erstellt</li>
    <li>Hinweise uebersetzt in Kundensprache (FR, DE, EN, PT)</li>
</ul>

<p>Sie vermeiden Fehler und rechnen auslaendische Kunden voll konform ab.</p>

<h2>FAQ - Artikel 21 LIVA</h2>

<h3>Und wenn mein Kunde keine MwSt-Nummer hat?</h3>
<p>Ohne ueber VIES validierte MwSt-Nummer gilt kein Reverse-Charge. Sie muessen luxemburgische MwSt (17% Standard) fakturieren. Gleiches gilt fuer Privatkunden (B2C).</p>

<h3>Wie ist es mit Warenlieferungen (nicht Dienstleistungen)?</h3>
<p>Fuer innergemeinschaftliche B2B-Warenverkaeufe gilt <strong>Artikel 43 LIVA</strong> (innergemeinschaftlich befreite Lieferungen). Regeln und Angaben unterscheiden sich.</p>

<h3>Muss der Vorgang der AED gemeldet werden?</h3>
<p>Ja. Der Vorgang erscheint in:</p>
<ul>
    <li>Ihrer <strong>luxemburgischen MwSt-Erklaerung</strong> (Feld innergemeinschaftliche Dienstleistungen)</li>
    <li>Der <strong>zusammenfassenden VIES-Meldung</strong> (monatlich bei Umsatz > 50.000 EUR pro 12 Monate, sonst vierteljaehrlich)</li>
</ul>

<h3>Kann der Kunde Reverse-Charge ablehnen?</h3>
<p>Nein, es ist eine EU-Pflichtregel. Sie koennen aber die Rechnungsstellung ablehnen, wenn dem Kunden eine validierte MwSt-Nummer fehlt (oder mit Standard-luxemburgischer MwSt fakturieren).</p>

<h3>Was passiert bei einer AED-Pruefung?</h3>
<p>Die AED prueft den <strong>VIES-Validierungsnachweis</strong> am Rechnungsdatum (Screenshot oder automatisches Log genuegt). Ohne diesen Nachweis wird der Vorgang als steuerpflichtig umqualifiziert und Sie schulden MwSt + Strafen.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Reverse-Charge, fehlerfrei</h3>
    <p class="text-primary-800 mb-4">faktur.lu validiert VIES in Echtzeit, wendet Artikel 21 LIVA automatisch an, erstellt zusammenfassende Meldungen. Kein Stress mehr fuer europaeische Kunden.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis starten</a>
</div>
HTML,
            ],

            [
                'category_id' => 1,
                'title' => "Luxemburgische MwSt 2026: die 4 Saetze (17%, 14%, 8%, 3%) erklaert",
                'slug' => 'luxemburgische-mwst-2026-vier-saetze-17-14-8-3-erklaert',
                'translation_key' => 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'cover_image' => 'https://images.unsplash.com/photo-1543286386-713bdd548da4?w=1200&h=630&fit=crop',
                'meta_title' => 'Luxemburgische MwSt 2026: Leitfaden der 4 Saetze (17%, 14%, 8%, 3%) | faktur.lu',
                'meta_description' => "Welcher MwSt-Satz in Luxemburg 2026? Standard 17%, Zwischen 14%, ermaessigt 8%, super-ermaessigt 3%. Praxisleitfaden mit konkreten Sektorbeispielen.",
                'excerpt' => "Luxemburg wendet 4 MwSt-Saetze an. Welcher wofuer? Hier der Praxisleitfaden 2026 mit konkreten Beispielen pro Satz: Dienstleistungen, Lebensmittel, Buecher, Gastronomie, Gas, Strom.",
                'published_at' => '2026-06-22 09:00:00',
                'content' => <<<HTML
<p class="lead">Luxemburg wendet 2026 <strong>4 verschiedene MwSt-Saetze</strong> an: 17% (Standard), 14% (Zwischen), 8% (ermaessigt) und 3% (super-ermaessigt). Den richtigen Satz zu waehlen ist entscheidend fuer die Steuerkonformitaet. Hier der vollstaendige Leitfaden 2026 mit konkreten Beispielen.</p>

<h2>Die 4 MwSt-Saetze 2026</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Satz</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Bezeichnung</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anwendung</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td><td class="border border-gray-300 px-4 py-2">Standard</td><td class="border border-gray-300 px-4 py-2">Mehrheit der Waren und Dienstleistungen</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td><td class="border border-gray-300 px-4 py-2">Zwischen</td><td class="border border-gray-300 px-4 py-2">Weine, bestimmte Brennstoffe</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td><td class="border border-gray-300 px-4 py-2">Ermaessigt</td><td class="border border-gray-300 px-4 py-2">Gas, Strom, Friseur, bestimmte Arbeiten</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td><td class="border border-gray-300 px-4 py-2">Super-ermaessigt</td><td class="border border-gray-300 px-4 py-2">Lebensmittel, Buecher, Medikamente, oeffentlicher Verkehr</td></tr>
    </tbody>
</table>

<p>Diese Saetze sind seit <strong>1. Januar 2024</strong> in Kraft (der Standardsatz kehrte nach der voruebergehenden Senkung 2023 von 16% auf 17% zurueck).</p>

<h2>Standardsatz 17%: Standard</h2>

<p>Der Standardsatz gilt fuer <strong>alle steuerpflichtigen Vorgaenge</strong>, die nicht explizit einem anderen Satz unterliegen. Konkret:</p>

<ul>
    <li><strong>Dienstleistungen</strong>: Beratung, Design, Entwicklung, Marketing, Schulung, etc.</li>
    <li><strong>Nicht-Lebensmittel-Waren</strong>: IT-Material, Moebel, Kleidung, etc.</li>
    <li><strong>Berufsausruestung-Vermietung</strong></li>
    <li><strong>Hotelunterbringung</strong> (4 Sterne und mehr)</li>
    <li><strong>Gastronomie</strong> (alkoholische Getraenke)</li>
</ul>

<p>Es ist <strong>der Standardsatz</strong>, den Sie bei Zweifel anwenden, bis Sie die Lage mit Ihrem Treuhaender klaeren.</p>

<h2>Zwischensatz 14%: Weine und Brennstoffe</h2>

<p>Der Zwischensatz betrifft hauptsaechlich:</p>

<ul>
    <li><strong>Weine</strong> (still und Schaumwein, ausser Champagner)</li>
    <li><strong>Bestimmte feste Brennstoffe</strong> (Kohle, Brennholz)</li>
    <li><strong>Einige Werbepublikationen</strong></li>
</ul>

<p>Dieser Satz ist selten. Wenn Sie nicht im Wein- oder Heizungssektor sind, werden Sie ihn wahrscheinlich nie verwenden.</p>

<h2>Ermaessigter Satz 8%: Energie und persoenliche Dienstleistungen</h2>

<p>Der ermaessigte Satz gilt in vielen Sektoren:</p>

<ul>
    <li><strong>Gas- und Stromlieferung</strong></li>
    <li><strong>Friseur</strong></li>
    <li><strong>Gastronomie</strong> (ohne alkoholische Getraenke)</li>
    <li><strong>Hotelunterbringung</strong> (1 bis 3 Sterne)</li>
    <li><strong>Renovierungsarbeiten</strong> in alten Wohnungen (unter Bedingungen)</li>
    <li><strong>Live-Vorstellungen</strong> (Theater, Konzerte)</li>
    <li><strong>Schuster, Fahrradreparatur, Kleidungsaenderung</strong></li>
</ul>

<h2>Super-ermaessigter Satz 3%: wesentliche Produkte</h2>

<p>Der niedrigste Satz deckt als wesentlich eingestufte Produkte:</p>

<ul>
    <li><strong>Grundnahrungsmittel</strong> (Brot, Milch, Fleisch, Gemuese, Obst)</li>
    <li><strong>Buecher, Zeitungen, Zeitschriften</strong> (Papier und digital seit 2020)</li>
    <li><strong>Medikamente</strong> (verschreibungspflichtig oder rezeptfrei)</li>
    <li><strong>Oeffentlicher Personenverkehr</strong> (Bus, Bahn, Tram)</li>
    <li><strong>Landwirtschaftliche Produktion</strong> (Saatgut, Duenger)</li>
    <li><strong>Wasser aus oeffentlichen Netzen</strong></li>
    <li><strong>Kunstwerke</strong> (unter bestimmten Bedingungen)</li>
</ul>

<h2>Haeufige Sonderfaelle</h2>

<h3>Gastronomie: 8% oder 17%?</h3>

<p>Vor Ort servierte Speisen im Restaurant: <strong>8%</strong>, aber alkoholische Getraenke bleiben bei <strong>17%</strong>. Eine zu einer Mahlzeit servierte Weinflasche wird getrennt fakturiert.</p>

<h3>Hotellerie: 8% oder 17%?</h3>

<p>Unterbringung in 1- bis 3-Sterne-Hotels: <strong>8%</strong>. Ab 4 Sternen: <strong>17%</strong>. Nebendienste (Spa, Zimmerservice) folgen eigenen Regelungen.</p>

<h3>E-Books: 3% oder 17%?</h3>

<p>Seit 2020 gilt fuer <strong>digitale Buecher</strong> derselbe Satz wie fuer Papierbuecher, also <strong>3%</strong>. Aber Streaming-Abos (Netflix, Spotify) bleiben bei 17%.</p>

<h3>Renovierungsarbeiten: 8% oder 17%?</h3>

<p>Renovierungen in Wohnungen <strong>aelter als 2 Jahre</strong> erhalten den ermaessigten Satz <strong>8%</strong>, wenn Material und Arbeit gemeinsam vom Bauunternehmen fakturiert werden. Neubau: 17%.</p>

<h2>Wie MwSt in Luxemburg berechnen</h2>

<p>Wenn Sie 1.000 EUR netto an einen Luxemburger Kunden zum Standardsatz 17% fakturieren:</p>

<ul>
    <li><strong>Netto</strong>: 1.000,00 EUR</li>
    <li><strong>MwSt (17%)</strong>: 170,00 EUR</li>
    <li><strong>Brutto</strong>: 1.170,00 EUR</li>
</ul>

<p>Umgekehrt, wenn Sie die Bruttosumme kennen und das Netto suchen:</p>

<ul>
    <li><strong>Netto</strong> = Brutto ÷ (1 + Satz/100)</li>
    <li><strong>Beispiel</strong>: 1.170 EUR Brutto ÷ 1,17 = 1.000 EUR Netto</li>
</ul>

<p>Zeit sparen mit unserem <a href="/de/tools/mwst-rechner" class="text-primary-500 hover:underline font-medium">kostenlosen Luxemburger MwSt-Rechner</a>, der alle 4 Saetze unterstuetzt.</p>

<h2>Falsche Saetze: die Folgen</h2>

<p>Den falschen Satz anzuwenden ist keine Bagatelle:</p>

<ul>
    <li><strong>MwSt-Unterfakturierung</strong> (Satz zu niedrig): AED-Nachforderung + Verzugszinsen + Strafen (10% bis 50%)</li>
    <li><strong>MwSt-Ueberfakturierung</strong> (Satz zu hoch): Kunde kann Rueckerstattung verlangen, und Sie muessen mit AED regularisieren</li>
    <li><strong>Vorsteuer-Abzugsverweigerung</strong> beim Kunden: bei offensichtlichem Fehler kann Kunde den Vorsteuerabzug verweigern</li>
</ul>

<h2>Wie faktur.lu Fehler vermeidet</h2>

<p>faktur.lu wendet den richtigen Satz automatisch je nach Leistungsart an:</p>

<ul>
    <li>Satzauswahl nach <strong>Leistungstyp</strong> bei Produkt-/Servicekonfiguration</li>
    <li>Automatische Erkennung von <strong>B2B in EU</strong> (Reverse-Charge Art. 21 LIVA)</li>
    <li><strong>Satz 0</strong> bei Befreiung (Art. 56 ter)</li>
    <li>Obligatorische LIVA-Angaben auf jeder Rechnung</li>
</ul>

<h2>FAQ - MwSt Luxemburg</h2>

<h3>Ist der Standardsatz 2026 wirklich 17%?</h3>
<p>Ja, seit 1. Januar 2024. Der Satz war 2023 voruebergehend bei 16% zur Kaufkraftstuetzung, dann zurueck zum historischen 17%.</p>

<h3>Ist meine Taetigkeit MwSt-befreit?</h3>
<p>Manche Taetigkeiten sind befreit (Gesundheit, Bildung, Versicherung, Wohnungsvermietung), was aber auch bedeutet, dass Sie keine <strong>Vorsteuer</strong> bei Einkaeufen <strong>zurueckholen</strong> koennen. Pruefen Sie Ihren Fall mit dem Treuhaender.</p>

<h3>Wann auf einer offenen Rechnung den Satz wechseln?</h3>
<p>Aendert sich der Satz zwischen Angebot und Lieferung, gilt der Satz <strong>am Rechnungsabschlussdatum</strong> (nicht das Angebotsdatum).</p>

<h3>Was bei grenzueberschreitendem B2C?</h3>
<p>Seit 2021 gilt das <strong>OSS (One Stop Shop)</strong>-Regime: ab 10.000 EUR innergemeinschaftlicher B2C-Verkaeufe pro Jahr muessen Sie den Zielland-Satz anwenden. Sonst koennen Sie luxemburgische MwSt anwenden.</p>

<h3>Wie einen bestimmten Satz bei einer Pruefung rechtfertigen?</h3>
<p>Bewahren Sie <strong>Leistungsnachweise</strong> auf: Lieferscheine, Vertraege, technische Datenblaetter. Die AED kann die Nomenklatur oder Zusammensetzung sehen wollen, um den angewandten Satz zu validieren.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">MwSt Luxemburg in 5 Sekunden berechnen</h3>
    <p class="text-primary-800 mb-4">Unser kostenloser MwSt-Rechner unterstuetzt die 4 luxemburgischen Saetze (17%, 14%, 8%, 3%). Ohne Registrierung, ohne Karte.</p>
    <a href="/de/tools/mwst-rechner" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Rechner oeffnen</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artikel 61 LIVA: warum Ihre Rechnungsnummerierung sequenziell sein muss",
                'slug' => 'artikel-61-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht',
                'translation_key' => 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'cover_image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?w=1200&h=630&fit=crop',
                'meta_title' => 'Artikel 61 LIVA Luxemburg: sequenzielle Nummerierung Pflicht | faktur.lu',
                'meta_description' => "Artikel 61 LIVA verlangt fortlaufende Nummerierung Ihrer Rechnungen in Luxemburg. Luecken, Duplikate, Formate: was zu wissen ist und wie automatisieren.",
                'excerpt' => "Ihre Rechnungen in Luxemburg muessen eine eindeutige, sequenzielle und fortlaufende Nummer tragen. Das ist Artikel 61 LIVA. Eine Luecke oder ein Duplikat kann eine Nachforderung ausloesen. Hier wie konform bleiben.",
                'published_at' => '2026-06-29 09:00:00',
                'content' => <<<HTML
<p class="lead"><strong>Artikel 61 LIVA</strong> setzt eine einfache aber grundlegende Regel: Ihre luxemburgischen Rechnungen muessen eine <strong>eindeutige, sequenzielle und fortlaufende Nummer</strong> tragen. Eine Luecke oder ein Duplikat kann eine Steuernachforderung ausloesen. Hier die Regel erklaert und wie nie wieder dran denken.</p>

<h2>Was sagt Artikel 61 LIVA genau?</h2>

<p>Artikel 61 des luxemburgischen MwSt-Gesetzes praezisiert, dass jede von einem Steuerpflichtigen ausgestellte Rechnung eine <strong>sequenzielle Nummer</strong> tragen muss, <strong>chronologisch und fortlaufend</strong> pro Geschaeftsjahr vergeben.</p>

<p>Drei kumulative Regeln:</p>

<ol>
    <li><strong>Eindeutigkeit</strong>: zwei Rechnungen koennen nie dieselbe Nummer tragen</li>
    <li><strong>Sequenzialitaet</strong>: Nummern folgen der Reihe (1, 2, 3, 4...)</li>
    <li><strong>Fortlaufigkeit</strong>: keine Luecke (kein Sprung von 5 zu 8 ohne 6 und 7)</li>
</ol>

<p>Diese Pflicht gilt fuer <strong>alle MwSt-Pflichtigen</strong> in Luxemburg, einschliesslich unter Befreiung (Artikel 56 ter), auch wenn die Rechnung den Hinweis "MwSt nicht anwendbar" traegt.</p>

<h2>Warum diese Regel</h2>

<p>Das Ziel ist einfach: <strong>Umsatzverdeckung verhindern</strong>. Ohne fortlaufende Nummerierung koennte ein Unternehmen leicht bestimmte Rechnungen in der MwSt-Erklaerung "vergessen". Sequenzialitaet erlaubt der AED, auf einen Blick zu pruefen, ob alle ausgestellten Rechnungen erklaert wurden.</p>

<p>Deshalb enthaelt die <strong>FAIA</strong> (Standardisierte Pruefdatei der AED) systematisch die Liste der Rechnungsnummern der Pruefperiode. Eine in FAIA entdeckte Luecke loest sofort eine Erklaerungsanforderung aus.</p>

<h2>Akzeptierte Formate</h2>

<p>Artikel 61 schreibt kein eindeutiges Format vor. Sie koennen jede Konvention nutzen, solange Sequenzialitaet eingehalten wird:</p>

<ul>
    <li><strong>Einfache Nummerierung</strong>: 1, 2, 3, 4...</li>
    <li><strong>Mit Praefix</strong>: F-001, F-002, F-003...</li>
    <li><strong>Mit Jahr</strong>: 2026-001, 2026-002, 2026-003...</li>
    <li><strong>Mit Praefix + Jahr</strong>: F-2026-001, F-2026-002...</li>
    <li><strong>Mit Kunde</strong> (abgeraten): ACME-001, ACME-002 (bricht globale Fortlaufigkeit)</li>
</ul>

<p><strong>Tipp</strong>: eine Nummer mit <strong>Jahr + sequenziellem Zaehler</strong> (z. B. F-2026-001) ist am lesbarsten und erlaubt jaehrlichen Neustart. Das ist die haeufigste Praxis in Luxemburg.</p>

<h2>Jaehrlicher Reset: erlaubt oder nicht?</h2>

<p>Ja, Sie koennen die Nummerierung jaehrlich bei <strong>1 jaehrlich</strong> neu starten. Das ist sogar gaengige Praxis. Wichtig ist, dass <strong>innerhalb eines Jahres</strong> die Sequenz fortlaufend bleibt.</p>

<p>Gueltiges Beispiel:</p>

<ul>
    <li>F-2025-148 (letzte Rechnung 2025)</li>
    <li>F-2026-001 (erste Rechnung 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>Der Wechsel von 148 zu 001 ist erlaubt, weil Jahresechsel. Aber innerhalb 2026 koennen Sie nicht von F-2026-001 zu F-2026-003 springen ohne F-2026-002 ausgestellt zu haben.</p>

<h2>Was bei Fehlern?</h2>

<h3>Fall 1 - Duplikat ausgestellt</h3>

<p>Die AED betrachtet eine der beiden Rechnungen als <strong>fiktiv oder betruegerisch</strong>. Sie muessen:</p>

<ol>
    <li>Eine <strong>Gutschrift</strong> auf einer der beiden ausstellen (Buchungsannulation)</li>
    <li>Eine <strong>neue Rechnung</strong> mit der naechsten Nummer ausstellen</li>
    <li>Die 3 Dokumente aufbewahren (die 2 Originalrechnungen + Gutschrift)</li>
</ol>

<h3>Fall 2 - Luecke in der Sequenz</h3>

<p>Schwerwiegender Fall. Sie muessen die <strong>Luecke erklaeren</strong> koennen. Drei akzeptierte Erklaerungen:</p>

<ul>
    <li>Die Rechnung wurde ausgestellt dann <strong>per Gutschrift annulliert</strong> (beide Dokumente aufbewahren)</li>
    <li>Technischer Fehler: Entwurf nicht abgeschlossen. Besser abschliessen und archivieren, auch wenn die Leistung nicht stattfand (mit "Rechnung annulliert"-Hinweis)</li>
    <li>Dokumentierter IT-Bug (selten)</li>
</ul>

<p>Ohne glaubwuerdige Erklaerung vermutet die AED Verdeckung und kann den fehlenden Umsatz <strong>pauschal schaetzen</strong>.</p>

<h3>Fall 3 - Abgeschlossene Rechnung loeschen</h3>

<p>Rechtlich unmoeglich. Eine abgeschlossene Rechnung muss 10 Jahre archiviert werden (Art. 16 Handelsgesetzbuch). Zur Annulation:</p>

<ul>
    <li>Eine <strong>Gutschrift</strong> ueber denselben Betrag ausstellen (separate Nummerierung typisch: AV-2026-001)</li>
    <li>Beide Dokumente aufbewahren (Rechnung + Gutschrift)</li>
</ul>

<h2>Klassische Fallen</h2>

<ol>
    <li><strong>Manuelle Excel-Nummerierung</strong>: sehr hohes Duplikat-/Luecken-Risiko (vergessene letzte Rechnung, kaputte Formel, fehlgeschlagenes Copy-Paste)</li>
    <li><strong>Mehrere parallele Serien</strong> (eine pro Kunde oder Projekt): schwer bei Pruefung zu rechtfertigen</li>
    <li><strong>Reset mitten im Jahr</strong> ohne Geschaeftsjahreswechsel: verboten</li>
    <li><strong>Loeschen abgeschlossener Rechnungen</strong> ohne Ersatzgutschrift: Verstoss gegen Art. 16 Handelsgesetzbuch + Art. 61 LIVA</li>
</ol>

<h2>Wie ohne Aufwand automatisieren</h2>

<p>Eine Rechnungssoftware wie <a href="/de" class="text-primary-500 hover:underline font-medium">faktur.lu</a> uebernimmt die Nummerierung:</p>

<ul>
    <li><strong>Automatische sequenzielle Nummerierung</strong>: unmoeglich, Duplikate zu erzeugen</li>
    <li><strong>Keine Luecken moeglich</strong>: bei jedem Abschluss erhoeht sich der Zaehler</li>
    <li><strong>Anpassbares Format</strong>: Praefix, Jahr, Zaehlerlaenge, alles konfigurierbar</li>
    <li><strong>Automatischer jaehrlicher Reset</strong>: Zaehler jaehrlich auf 1 (oder nicht, je nach Praeferenz)</li>
    <li><strong>Getrennte Sequenzen</strong> fuer Rechnungen, Gutschriften und Angebote (unabhaengige aber jeweils fortlaufende Zaehler)</li>
    <li><strong>Eindeutigkeitspruefung</strong> bei jeder Erstellung</li>
</ul>

<p>Damit ist Artikel 61 LIVA kein Problem mehr: Ihre Nummerierung ist per Konstruktion konform.</p>

<h2>FAQ - Artikel 61 LIVA</h2>

<h3>Und wenn ich unter MwSt-Befreiung (Art. 56 ter) fakturiere?</h3>
<p>Artikel 61 LIVA gilt <strong>trotzdem</strong>. Jede ausgestellte Rechnung, auch ohne erhobene MwSt, muss eine sequenzielle und fortlaufende Nummer tragen.</p>

<h3>Kann ich pro Kunde nummerieren (ACME-001, ACME-002...)?</h3>
<p>Technisch ja - aber sehr abgeraten. Bei einer Pruefung verlangt die AED <strong>globale</strong> Sequenzialitaet. Sie muessten dann beweisen, dass es ueber alle Kundenserien hinweg keine Luecke gibt, was komplex und riskant ist.</p>

<h3>Betrifft das auch Angebote?</h3>
<p>Nein, Artikel 61 LIVA betrifft nur <strong>Rechnungen und Gutschriften</strong>. Sie koennen Angebote nummerieren wie Sie wollen (sequenzielle Nummerierung empfohlen fuer Eigentracking).</p>

<h3>Kann ich getrennte Serien fuer Rechnungen und Gutschriften haben?</h3>
<p>Ja, sogar empfohlen. Eine Serie fuer Rechnungen (F-2026-001, F-2026-002...) und eine fuer Gutschriften (AV-2026-001, AV-2026-002...). Jede muss fortlaufend, aber unabhaengig sein.</p>

<h3>Was bei Software-Migration mitten im Jahr?</h3>
<p>Sie muessen die <strong>Sequenz fortsetzen</strong> in der neuen Software. Waren Sie bei F-2026-148 in der alten, muss Ihre erste Rechnung in der neuen F-2026-149 sein. faktur.lu erlaubt Startzaehler-Konfiguration bei Anmeldung zur Luecken-Vermeidung.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Konforme Nummerierung ohne nachzudenken</h3>
    <p class="text-primary-800 mb-4">faktur.lu uebernimmt Ihre sequenzielle Nummerierung automatisch, Artikel 61 LIVA per Konstruktion konform. Anpassbares Format, konfigurierbarer Jahresreset.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis starten</a>
</div>
HTML,
            ],
        ];
    }
}
