<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traductions ALLEMANDES des articles programmés juillet-août 2026.
 * translation_key = slug FR (lie les langues) ; slug = slug allemand localisé.
 * Idempotent (skip si slug existe). published_at = même lundi que le FR.
 */
class BlogScheduledSummer2026DeSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        // Résout l'ID de catégorie par SLUG (robuste : les IDs peuvent différer
        // d'une base à l'autre ; fallback sur 'guides' si une catégorie manque).
        $catMap = [];
        foreach ([1 => 'guides', 2 => 'reglementation', 3 => 'freelances', 4 => 'actualites', 5 => 'guide-creation-entreprise'] as $oldId => $slug) {
            $catMap[$oldId] = \App\Models\BlogCategory::where('slug', $slug)->value('id') ?? 1;
        }

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip DE: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $catMap[$article['category_id']] ?? 1,
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
            // ===== 2026-07-06 =====
            [
                'category_id' => 1,
                'translation_key' => 'travailler-avec-fiduciaire-luxembourg-guide',
                'slug' => 'zusammenarbeit-treuhandgesellschaft-luxemburg-leitfaden',
                'cover_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=630&fit=crop',
                'title' => 'Zusammenarbeit mit einer Treuhandgesellschaft in Luxemburg: der Leitfaden',
                'meta_title' => 'Mit einer Treuhandgesellschaft in Luxemburg arbeiten | faktur.lu',
                'meta_description' => 'Wann Sie eine Treuhandgesellschaft in Luxemburg brauchen, wie Sie die richtige wählen und wie Sie ihr saubere Daten liefern, um Zeit (und Geld) zu sparen.',
                'excerpt' => 'Eine gute Treuhandgesellschaft spart Ihnen Zeit und vermeidet Fehler. Voraussetzung: die richtige Wahl und saubere Daten. So holen Sie das Beste heraus.',
                'published_at' => '2026-07-06 09:00:00',
                'content' => <<<HTML
<p class="lead">In Luxemburg überlassen die meisten Selbständigen und KMU ihre Buchhaltung einer <strong>Treuhandgesellschaft</strong> (Steuer- bzw. Buchhaltungskanzlei). Gut gewählt und gut beliefert, spart sie Ihnen wertvolle Zeit und kostspielige Fehler. So arbeiten Sie effizient mit ihr zusammen.</p>

<h2>Was ist eine Treuhandgesellschaft und wozu dient sie?</h2>
<p>Eine Treuhandgesellschaft übernimmt ganz oder teilweise Ihre buchhalterischen und steuerlichen Pflichten: Buchführung, MwSt.-Erklärungen, Jahresabschluss, Steuererklärung, Beratung. In Luxemburg ist sie für Selbständige nicht gesetzlich vorgeschrieben, aber dringend empfohlen, sobald Ihre Tätigkeit über einige Rechnungen pro Monat hinausgeht.</p>

<h2>Wann sollten Sie eine Treuhandgesellschaft beauftragen?</h2>
<ul>
    <li>Sie überschreiten die MwSt.-Befreiungsgrenze und müssen regelmäßig MwSt. erklären.</li>
    <li>Sie gründen eine Gesellschaft (Sàrl, Sàrl-S) mit Pflicht zur doppelten Buchführung.</li>
    <li>Ihnen fehlt Zeit oder Sicherheit bei Ihren Steuerpflichten.</li>
    <li>Sie möchten optimieren (abzugsfähige Kosten, Abschreibungen), ohne bei einer AED-Prüfung Risiken einzugehen.</li>
</ul>

<h2>Die richtige Treuhandgesellschaft wählen</h2>
<ul>
    <li><strong>Spezialisierung</strong>: Eine auf Selbständige und Kleinbetriebe ausgerichtete Kanzlei versteht Sie besser als eine auf Großkonzerne.</li>
    <li><strong>Reaktionsfähigkeit</strong>: Stellen Sie vor der Unterschrift eine Frage und beobachten Sie die Antwortzeit.</li>
    <li><strong>Werkzeuge</strong>: Eine Kanzlei, die digitale Daten (Buchhaltungsexporte, FAIA) statt PDF-Stapel akzeptiert, spart beiden Seiten Zeit.</li>
    <li><strong>Transparente Honorare</strong>: Monatspauschale oder nach Aufwand? Was genau ist enthalten?</li>
</ul>
<p>Das <strong>Ordre des Experts-Comptables (OEC)</strong> führt ein offizielles Verzeichnis der zugelassenen Fachleute (oec.lu).</p>

<h2>Der eigentliche Hebel: saubere Daten liefern</h2>
<p>Die größte Zeit- (und Kosten-)quelle aufseiten der Treuhandgesellschaft ist die <strong>Neuerfassung</strong>: Ihre PDF- oder Excel-Rechnungen erneut in ihre Software eingeben. Je strukturierter Ihre Daten, desto weniger Stunden berechnet sie und desto weniger Fehler entstehen.</p>
<ul>
    <li>Stellen Sie konforme Rechnungen mit fortlaufender Nummerierung aus.</li>
    <li>Zentralisieren Sie alles in einem Werkzeug statt in verstreuten Dateien.</li>
    <li>Liefern Sie integrationsfertige Exporte: FAIA 2.01, Sage BOB 50, Sage 100 oder CSV.</li>
</ul>

<h2>faktur.lu: ein kostenloses Portal für Ihre Treuhandgesellschaft</h2>
<p>Mit faktur.lu stellen Sie ganz normal Rechnungen aus, und Ihre Treuhandgesellschaft greift <strong>nur lesend</strong> über ein eigenes Buchhalterportal auf Ihre Daten zu: Sie holt Ihre Exporte (FAIA 2.01, Sage, CSV) mit einem Klick ab - ohne Nachfrage und ohne Neuerfassung. Das Portal ist <strong>für die Kanzlei kostenlos</strong>. Nutzt Ihre Treuhandgesellschaft faktur.lu noch nicht, erzählen Sie ihr von unserem <a href="/de/partner">Partnerprogramm</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Sauber abrechnen - Ihr Buchhalter wird es Ihnen danken</h3>
    <p class="text-primary-800 mb-4">faktur.lu erstellt in Luxemburg konforme Rechnungen und Exporte für Ihre Treuhandgesellschaft (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-07-13 =====
            [
                'category_id' => 1,
                'translation_key' => 'faire-devis-professionnel-luxembourg',
                'slug' => 'professionelles-angebot-luxemburg-erstellen',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
                'title' => 'Ein professionelles Angebot in Luxemburg erstellen: Pflichtangaben, Gültigkeit, Umwandlung',
                'meta_title' => 'Professionelles Angebot in Luxemburg erstellen: Leitfaden | faktur.lu',
                'meta_description' => 'Wie Sie in Luxemburg ein professionelles Angebot (Kostenvoranschlag) erstellen: Pflichtangaben, Gültigkeitsdauer, rechtlicher Wert und Umwandlung in eine Rechnung.',
                'excerpt' => 'Ein Angebot bindet Ihren Kunden ebenso wie Sie. Hier die anzugebenden Punkte, der rechtliche Wert, die Gültigkeitsdauer und die saubere Umwandlung in eine Rechnung.',
                'published_at' => '2026-07-13 09:00:00',
                'content' => <<<HTML
<p class="lead">Vor der Rechnung kommt oft das <strong>Angebot</strong> (Kostenvoranschlag). Gut formuliert, steckt es die Leistung ab, beruhigt den Kunden und vermeidet Streit. So erstellen Sie ein professionelles Angebot in Luxemburg.</p>

<h2>Wozu dient ein Angebot?</h2>
<p>Das Angebot ist ein <strong>beziffertes kommerzielles Angebot</strong>: Es beschreibt die Leistung oder die Waren, den Preis und die Bedingungen. In Luxemburg ist es nicht verpflichtend, aber sehr empfehlenswert, sobald eine Leistung einen gewissen Umfang hat: Es schützt beide Seiten, indem es Umfang und Preis vor Beginn festlegt.</p>

<h2>Die Angaben in einem Angebot</h2>
<ul>
    <li>Ihre vollständigen Kontaktdaten (Name, Adresse, MwSt.-Nummer falls steuerpflichtig).</li>
    <li>Die Kontaktdaten des Kunden.</li>
    <li>Eine <strong>Angebotsnummer</strong> und das Ausstellungsdatum.</li>
    <li>Die Aufstellung der Leistungen/Produkte mit Mengen und Einzelpreisen.</li>
    <li>Den Betrag <strong>netto (HT)</strong>, die anwendbare MwSt. und den <strong>Bruttobetrag (TTC)</strong>.</li>
    <li>Die <strong>Gültigkeitsdauer</strong> des Angebots.</li>
    <li>Die Zahlungsbedingungen und gegebenenfalls die geforderte Anzahlung.</li>
</ul>
<p>Sind Sie von der MwSt. befreit, geben Sie den entsprechenden Hinweis statt der MwSt. an.</p>

<h2>Welcher rechtliche Wert?</h2>
<p>Ein vom Kunden <strong>unterschriebenes und angenommenes</strong> Angebot hat vertraglichen Wert: Es bindet beide Seiten an den vereinbarten Umfang und Preis. Das ist Ihr bester Schutz bei Meinungsverschiedenheiten. Lassen Sie das Angebot daher datieren und unterschreiben (Vermerk „Einverstanden"), bevor Sie beginnen.</p>

<h2>Gültigkeitsdauer</h2>
<p>Geben Sie stets eine Gültigkeitsdauer an (z. B. 30 Tage). Danach sind Sie nicht mehr an den genannten Preis gebunden - nützlich, wenn sich Ihre Kosten ändern. Es ist auch ein Verkaufshebel: Ein zeitlich begrenztes Angebot fördert die Entscheidung.</p>

<h2>Vom Angebot zur Rechnung</h2>
<p>Sobald das Angebot angenommen und die Leistung erbracht ist, stellen Sie die <strong>Rechnung</strong> aus. Um Fehler und Doppelerfassung zu vermeiden, wandeln Sie das Angebot am besten <strong>direkt in eine Rechnung um</strong>: Positionen, Beträge und Kontaktdaten werden übernommen, und es bleibt nur die fortlaufende Rechnungsnummer zu vergeben.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Angebot, dann Rechnung mit einem Klick</h3>
    <p class="text-primary-800 mb-4">Mit faktur.lu erstellen Sie Angebote, lassen sie annehmen und wandeln sie ohne Neuerfassung in konforme Rechnungen um.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos ein Angebot erstellen</a>
</div>
HTML,
            ],

            // ===== 2026-07-20 =====
            [
                'category_id' => 1,
                'translation_key' => 'facturation-projet-forfait-regie-luxembourg',
                'slug' => 'projektabrechnung-festpreis-aufwand-luxemburg',
                'cover_image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&h=630&fit=crop',
                'title' => 'Projektabrechnung in Luxemburg: Festpreis oder nach Aufwand?',
                'meta_title' => 'Projektabrechnung: Festpreis vs. Aufwand in Luxemburg | faktur.lu',
                'meta_description' => 'Festpreis oder Abrechnung nach Aufwand für ein Projekt in Luxemburg? Vorteile, Risiken, Anzahlungen und gute Praxis, um ohne böse Überraschung abzurechnen.',
                'excerpt' => 'Ein Projekt zum Festpreis oder nach Aufwand abzurechnen, ändert alles: Risiko, Liquidität, Kundenbeziehung. So wählen und rechnen Sie jedes Modell sauber ab.',
                'published_at' => '2026-07-20 09:00:00',
                'content' => <<<HTML
<p class="lead">Bei der Abrechnung eines Projekts - Entwicklung, Design, Beratung, Bauleistung - stehen sich zwei Modelle gegenüber: der <strong>Festpreis</strong> und die <strong>Abrechnung nach Aufwand</strong>. Die richtige Wahl hängt vom Grad der Unsicherheit und Ihrer Liquidität ab.</p>

<h2>Der Festpreis: ein fixer Preis für einen definierten Umfang</h2>
<p>Sie nennen einen Gesamtpreis für ein präzises Ergebnis. Der Kunde kennt sein Budget im Voraus, was beruhigt. Das Risiko liegt jedoch bei Ihnen: Läuft das Projekt aus dem Ruder, tragen Sie die Mehrkosten.</p>
<p><strong>Wann einsetzen:</strong> klarer und stabiler Umfang, Leistung, die Sie gut beherrschen.</p>
<p><strong>Gute Praxis:</strong> Umfang im Angebot präzise abstecken, Klausel für Anfragen außerhalb des Umfangs vorsehen und die Zahlung aufteilen (Anzahlung + Meilensteine + Restbetrag).</p>

<h2>Nach Aufwand: Abrechnung der geleisteten Zeit</h2>
<p>Sie rechnen die tatsächlich geleisteten Stunden oder Tage zu einem vereinbarten Satz ab. Das Risiko der Überschreitung wechselt zum Kunden, und Sie werden für die gesamte investierte Zeit bezahlt. Im Gegenzug hat der Kunde kein garantiertes Budget.</p>
<p><strong>Wann einsetzen:</strong> unklarer oder sich entwickelnder Umfang, lange Aufträge, Wartung.</p>
<p><strong>Gute Praxis:</strong> Zeit genau erfassen, klaren Stundennachweis liefern und gegebenenfalls eine beruhigende Obergrenze festlegen.</p>

<h2>Vergleich</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Kriterium</th><th class="text-left p-2 bg-slate-100">Festpreis</th><th class="text-left p-2 bg-slate-100">Nach Aufwand</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Überschreitungsrisiko</td><td class="p-2 border-b">Bei Ihnen</td><td class="p-2 border-b">Beim Kunden</td></tr>
        <tr><td class="p-2 border-b">Budgetsicht des Kunden</td><td class="p-2 border-b">Hoch</td><td class="p-2 border-b">Gering</td></tr>
        <tr><td class="p-2 border-b">Ideal für</td><td class="p-2 border-b">Stabilen Umfang</td><td class="p-2 border-b">Sich entwickelnden Umfang</td></tr>
    </tbody>
</table>

<h2>Anzahlungen: schützen Sie Ihre Liquidität</h2>
<p>Bei einem Projekt über mehrere Wochen sollten Sie nicht alles am Ende abrechnen. Verlangen Sie eine <strong>Anzahlung zu Beginn</strong> und rechnen Sie an <strong>Meilensteinen</strong> ab. Das sichert Ihre Liquidität und begrenzt das Risiko von Zahlungsausfällen.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Festpreis, Aufwand, Anzahlungen: faktur.lu erledigt alles</h3>
    <p class="text-primary-800 mb-4">Zeiterfassung, Anzahlungs- und Projektrechnungen - konform und in wenigen Klicks.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-07-27 =====
            [
                'category_id' => 2,
                'translation_key' => 'cotisations-sociales-independant-ccss-luxembourg-2026',
                'slug' => 'sozialbeitraege-selbstaendige-ccss-luxemburg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'title' => 'Sozialbeiträge für Selbständige in Luxemburg (CCSS) im Jahr 2026',
                'meta_title' => 'Sozialbeiträge Selbständige Luxemburg (CCSS) 2026 | faktur.lu',
                'meta_description' => 'Wie viel zahlt ein Selbständiger in Luxemburg an Sozialbeiträgen? CCSS-Sätze, Bemessungsgrundlage, Mindestbasis und Funktionsweise. Leitfaden 2026.',
                'excerpt' => 'Als Selbständiger in Luxemburg zahlen Sie Beiträge an die CCSS auf Ihr Einkommen. So funktioniert es, die Richtsätze und wie Sie diese Lasten einplanen.',
                'published_at' => '2026-07-27 09:00:00',
                'content' => <<<HTML
<p class="lead">Beim Start in die Selbständigkeit in Luxemburg sind die <strong>Sozialbeiträge</strong> oft die böse Überraschung. Sie finanzieren Ihre soziale Absicherung (Kranken-, Renten-, Pflegeversicherung) und werden auf Ihr Einkommen berechnet. Das Wichtigste zum Einplanen.</p>

<h2>Was ist die CCSS?</h2>
<p>Das <strong>Centre Commun de la Sécurité Sociale (CCSS)</strong> ist die Stelle, die Anmeldung und Einzug der Sozialbeiträge in Luxemburg zentralisiert. Ab Beginn Ihrer selbständigen Tätigkeit müssen Sie sich dort anmelden.</p>

<h2>Worauf zahlt man Beiträge?</h2>
<p>Die Beiträge werden auf Ihr <strong>Berufseinkommen</strong> berechnet (den Gewinn, nicht den Umsatz). Zu Beginn erfolgt die Anmeldung oft auf einer vorläufigen Basis, die später anhand Ihrer tatsächlichen, von der Steuerverwaltung mitgeteilten Einkünfte angepasst wird.</p>

<h2>Welche Sätze? (Größenordnung)</h2>
<p>Insgesamt machen die Beiträge eines Selbständigen <strong>etwa 25 %</strong> des Einkommens aus, verteilt auf die verschiedenen Zweige:</p>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Zweig</th><th class="text-left p-2 bg-slate-100">Richtsatz</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Rentenversicherung</td><td class="p-2 border-b">~16 %</td></tr>
        <tr><td class="p-2 border-b">Krankenversicherung (Sach- + Geldleistungen)</td><td class="p-2 border-b">~6 %</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung</td><td class="p-2 border-b">~1,4 %</td></tr>
        <tr><td class="p-2 border-b">Unfall- und Arbeitsmedizin</td><td class="p-2 border-b">~1 %</td></tr>
    </tbody>
</table>
<p class="text-sm text-slate-500">Die genauen Sätze ändern sich und hängen von Ihrer Situation ab. Maßgeblich sind stets die CCSS (ccss.public.lu) oder Ihre Treuhandgesellschaft.</p>

<h2>Mindest- und Höchstbemessungsgrundlage</h2>
<p>Es gibt eine <strong>Mindestbemessungsgrundlage</strong>: Selbst bei geringem Einkommen zahlen Sie auf eine Mindestbasis (gekoppelt an den sozialen Mindestlohn). Am anderen Ende sind die Beiträge ab einem bestimmten Einkommen gedeckelt. Konkret erzeugt auch ein sehr geringes Einkommen Mindestbeiträge - das gehört in Ihre Liquiditätsplanung.</p>

<h2>So planen Sie richtig</h2>
<ul>
    <li><strong>Legen Sie ~25 %</strong> Ihres Gewinns für die Beiträge zurück, zusätzlich zur Steuer.</li>
    <li>Rechnen Sie mit einer <strong>Nachregulierung</strong>: Steigen Ihre Einkünfte, folgt eine Beitragsnachzahlung.</li>
    <li>Führen Sie eine klare Buchhaltung, um Ihren tatsächlichen Gewinn jederzeit zu kennen.</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Zum Merken</p>
    <p>Sozialbeiträge sind keine Steuer: Sie finanzieren Ihre Rente und Ihre Krankenversicherung. Aber sie machen ~25 % des Einkommens aus - ab der ersten Rechnung einzuplanen.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Behalten Sie Ihr Einkommen in Echtzeit im Blick</h3>
    <p class="text-primary-800 mb-4">faktur.lu verfolgt Umsatz und Ausgaben und hilft Ihnen, Beiträge und Steuer stressfrei zurückzulegen.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-08-03 =====
            [
                'category_id' => 3,
                'translation_key' => 'fixer-tjm-freelance-luxembourg',
                'slug' => 'tagessatz-freelancer-luxemburg-festlegen',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'title' => 'Den Tagessatz als Freelancer in Luxemburg festlegen',
                'meta_title' => 'Tagessatz als Freelancer in Luxemburg festlegen: Methode 2026 | faktur.lu',
                'meta_description' => 'Wie Sie als Freelancer in Luxemburg Ihren Tagessatz berechnen: Kosten, Beiträge, nicht abrechenbare Tage und eine einfache Methode, um sich nicht unter Wert zu verkaufen.',
                'excerpt' => 'Viele Freelancer legen ihren Satz nach Gefühl fest und arbeiten am Ende mit Verlust. So berechnen Sie einen Tagessatz, der Ihre Kosten wirklich deckt.',
                'published_at' => '2026-08-03 09:00:00',
                'content' => <<<HTML
<p class="lead">Die klassische Falle für Einsteiger: ein früheres Gehalt durch 20 Tage teilen und das zum Tagessatz machen. Ergebnis: Man arbeitet mit Verlust, ohne es zu merken. So berechnen Sie einen realistischen <strong>Tagessatz</strong> in Luxemburg.</p>

<h2>Ihr Tagessatz ist nicht „Gehalt / 20"</h2>
<p>Ein Angestellter wird für ~220 Tage/Jahr bezahlt, inklusive Urlaub und Krankheit, ohne Lasten zu tragen. Ein Freelancer dagegen rechnet nicht alle Tage ab und trägt alle Kosten. Ihr Satz muss weit mehr als Ihr Wunschgehalt decken.</p>

<h2>Die 4 Posten, die hineingehören</h2>
<ul>
    <li><strong>Ihr gewünschtes Nettoeinkommen.</strong> Was Sie tatsächlich behalten wollen.</li>
    <li><strong>Die Sozialbeiträge (~25 %).</strong> Siehe unseren Artikel zu den <a href="/de/blog/sozialbeitraege-selbstaendige-ccss-luxemburg-2026">CCSS-Beiträgen</a>.</li>
    <li><strong>Die Einkommensteuer.</strong> Progressiv, zurückzulegen.</li>
    <li><strong>Ihre Betriebskosten.</strong> Software, Versicherung, Buchhalter, Material, Weiterbildung, Fahrten.</li>
</ul>

<h2>Die tatsächlich abrechenbaren Tage</h2>
<p>Ziehen Sie von ~252 Arbeitstagen Urlaub, Feiertage, eventuelle Krankheit und vor allem die <strong>nicht abrechenbare</strong> Zeit ab: Akquise, Verwaltung, Angebote, Weiterbildung. In der Praxis rechnen viele Freelancer <strong>180 bis 200 Tage pro Jahr</strong> ab, im ersten Jahr oft weniger.</p>

<h2>Die Berechnungsmethode</h2>
<ol>
    <li>Addieren Sie Ihren <strong>Jahresbedarf</strong>: Nettoeinkommen + Beiträge + zurückgelegte Steuer + Betriebskosten.</li>
    <li>Teilen Sie durch Ihre <strong>realistische Zahl abrechenbarer Tage</strong>.</li>
    <li>Sie erhalten Ihren <strong>Mindesttagessatz</strong> - darunter verlieren Sie Geld.</li>
</ol>
<p><strong>Beispiel:</strong> Beträgt Ihr Jahresbedarf 90 000 € und rechnen Sie 180 Tage ab, liegt Ihr Mindesttagessatz bei 500 €. Jeder niedrigere Satz bedeutet Verlust.</p>

<h2>Über den Mindestsatz hinaus: der Wert</h2>
<p>Der Mindesttagessatz deckt Ihre Kosten; Ihr Endpreis hängt dann von Ihrem <strong>Wert</strong> ab: Expertise, Seltenheit, Ergebnisse für den Kunden. Rechnen Sie nicht Ihre Zeit ab, sondern das, was Sie dem Kunden einbringen.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verfolgen Sie Zeit und Rentabilität</h3>
    <p class="text-primary-800 mb-4">Mit faktur.lu verfolgen Sie abgerechnete Tage, Ausgaben und Umsatz und prüfen, ob Ihr Tagessatz hält, was er verspricht.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-08-10 =====
            [
                'category_id' => 2,
                'translation_key' => 'tva-deductible-depenses-professionnelles-luxembourg',
                'slug' => 'vorsteuer-betriebsausgaben-luxemburg',
                'cover_image' => 'https://images.unsplash.com/photo-1599658880436-c61792e70672?w=1200&h=630&fit=crop',
                'title' => 'Vorsteuer auf Betriebsausgaben in Luxemburg zurückholen',
                'meta_title' => 'Vorsteuer in Luxemburg: MwSt. auf Einkäufe zurückholen | faktur.lu',
                'meta_description' => 'Wie Sie in Luxemburg die MwSt. auf Ihre Betriebsausgaben zurückholen: Voraussetzungen, abzugsfähige und nicht abzugsfähige Ausgaben und Funktionsweise der Erklärung.',
                'excerpt' => 'Die MwSt., die Sie auf Ihre beruflichen Einkäufe zahlen, ist in der Regel erstattungsfähig. Hier die Voraussetzungen, Sonderfälle und die konkrete Funktionsweise.',
                'published_at' => '2026-08-10 09:00:00',
                'content' => <<<HTML
<p class="lead">Wenn Sie in Luxemburg MwSt.-pflichtig sind, berechnen Sie Ihren Kunden MwSt. (vereinnahmte MwSt.), können aber auch die auf Ihre <strong>Betriebsausgaben gezahlte MwSt. zurückholen</strong> (Vorsteuer). Das ist ein zentraler Mechanismus - so funktioniert er.</p>

<h2>Das Prinzip: vereinnahmte MwSt. - Vorsteuer</h2>
<p>Bei jeder Erklärung führen Sie dem Staat die Differenz zwischen der auf Ihren Verkäufen <strong>vereinnahmten</strong> MwSt. und der auf Ihren Einkäufen <strong>abzugsfähigen</strong> MwSt. ab. Haben Sie in einem Zeitraum viel investiert, kann die Vorsteuer die vereinnahmte MwSt. sogar übersteigen: Sie erhalten dann ein MwSt.-Guthaben.</p>

<h2>Die Voraussetzungen für den Vorsteuerabzug</h2>
<ul>
    <li>Sie sind MwSt.-<strong>pflichtig</strong> (nicht befreit).</li>
    <li>Die Ausgabe ist <strong>beruflich</strong> und mit Ihrer steuerpflichtigen Tätigkeit verbunden.</li>
    <li>Sie besitzen eine <strong>konforme Rechnung</strong> mit MwSt. und MwSt.-Nummer des Lieferanten.</li>
</ul>

<h2>In der Regel abzugsfähige Ausgaben</h2>
<p>Material und Verbrauchsgüter, berufliche Software und Abonnements, Honorare (Buchhalter, Anwalt), Miete von Geschäftsräumen, Telekommunikation, Weiterbildung - sofern sie Ihrer Tätigkeit dienen.</p>

<h2>Die Sonderfälle (begrenzter oder ausgeschlossener Abzug)</h2>
<p>Manche Ausgaben sind teilweise oder ganz vom Abzugsrecht ausgeschlossen, insbesondere bei privatem oder repräsentativem Anteil. Gemischt genutzte Ausgaben (privat und beruflich), bestimmte Bewirtungs- oder Fahrzeugkosten können begrenzt sein. Im Zweifel prüfen Sie bei der <strong>AED</strong> oder Ihrer Treuhandgesellschaft.</p>
<p class="text-sm text-slate-500">Die genauen Abzugsregeln stehen im luxemburgischen MwSt.-Gesetz und können sich ändern. Maßgeblich ist die AED oder ein Fachmann.</p>

<h2>So läuft es konkret</h2>
<p>Sie erfassen Ihre Ausgaben samt MwSt. über den Zeitraum und tragen die gesamte Vorsteuer in Ihre <strong>MwSt.-Erklärung</strong> ein (monatlich, vierteljährlich oder jährlich, je nach Regime). Eine gut geführte Ausgabenbuchhaltung verhindert, dass erstattungsfähige MwSt. liegen bleibt - bares Geld, das sonst verloren ginge.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verlieren Sie keine erstattungsfähige MwSt. mehr</h3>
    <p class="text-primary-800 mb-4">faktur.lu erfasst Ihre Ausgaben samt MwSt. und bereitet saubere Daten für Erklärung und Treuhandgesellschaft auf.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-08-17 =====
            [
                'category_id' => 1,
                'translation_key' => 'facture-acompte-luxembourg-regles-mentions',
                'slug' => 'anzahlungsrechnung-luxemburg-regeln-pflichtangaben',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'title' => 'Anzahlungsrechnung in Luxemburg: Regeln, Pflichtangaben und MwSt.',
                'meta_title' => 'Anzahlungsrechnung in Luxemburg: Regeln und Pflichtangaben | faktur.lu',
                'meta_description' => 'Wie Sie in Luxemburg eine Anzahlungsrechnung erstellen: Pflichtangaben, Behandlung der MwSt. und Verknüpfung mit der Schlussrechnung. Praxisleitfaden 2026.',
                'excerpt' => 'Eine Anzahlung zu verlangen schützt Ihre Liquidität, erfordert aber eine echte Rechnung mit eigenen MwSt.-Regeln. So machen Sie es richtig.',
                'published_at' => '2026-08-17 09:00:00',
                'content' => <<<HTML
<p class="lead">Eine <strong>Anzahlung</strong> vor Projektbeginn zu verlangen, schützt hervorragend Ihre Liquidität. Doch eine Anzahlung ist keine bloße „Zahlung": Sie führt zu einer echten <strong>Anzahlungsrechnung</strong> mit eigenen Regeln. So machen Sie es in Luxemburg.</p>

<h2>Warum eine Anzahlung in Rechnung stellen?</h2>
<p>Bei einer langen Leistung oder einem Projekt sichert die Anzahlung Ihre Liquidität, bindet den Kunden und senkt das Risiko eines Zahlungsausfalls. Besonders nützlich bei der <a href="/de/blog/projektabrechnung-festpreis-aufwand-luxemburg">Projektabrechnung</a>.</p>

<h2>Die Angaben einer Anzahlungsrechnung</h2>
<p>Eine Anzahlungsrechnung ist eine vollwertige Rechnung: Sie enthält die üblichen Pflichtangaben (Ihre und die Kontaktdaten des Kunden, Nummer und Datum, MwSt.-Nummer), zusätzlich:</p>
<ul>
    <li>Den klaren Hinweis, dass es sich um eine <strong>Anzahlung</strong> handelt, und auf welchen Auftrag/welches Angebot sie sich bezieht.</li>
    <li>Den <strong>Anzahlungsbetrag</strong> netto, die anwendbare MwSt. und den Bruttobetrag.</li>
    <li>Eine fortlaufende Nummer, wie jede Rechnung.</li>
</ul>

<h2>Die Behandlung der MwSt.</h2>
<p>Wichtig: Bei einer Dienstleistung wird die MwSt. grundsätzlich <strong>bei Vereinnahmung der Anzahlung fällig</strong>, für den erhaltenen Betrag. Sie erklären und führen die MwSt. auf die Anzahlung also ab, sobald sie eingegangen ist, ohne auf die Schlussrechnung zu warten.</p>
<p class="text-sm text-slate-500">Der Zeitpunkt der MwSt.-Fälligkeit hängt von der Art des Umsatzes ab. Im Zweifel bei der AED oder Ihrer Treuhandgesellschaft prüfen.</p>

<h2>Die Schlussrechnung</h2>
<p>Am Ende der Leistung stellen Sie die <strong>Schlussrechnung</strong> aus. Sie weist den Gesamtbetrag der Leistung aus und <strong>zieht die bereits berechnete Anzahlung</strong> (samt MwSt.) ab, sodass nur der Restbetrag berechnet wird. So zahlt der Kunde nie zweimal denselben Betrag, und die gesamte MwSt. bleibt korrekt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Zusammengefasst</p>
    <p>Anzahlung = echte Rechnung, MwSt. bei Vereinnahmung fällig. Die Schlussrechnung weist den Gesamtbetrag aus und zieht die bereits berechnete Anzahlung ab.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Anzahlungen und Restbeträge automatisch verwaltet</h3>
    <p class="text-primary-800 mb-4">faktur.lu erstellt Ihre Anzahlungs- und Schlussrechnungen konform, mit der richtigen MwSt.-Behandlung.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-08-24 =====
            [
                'category_id' => 2,
                'translation_key' => 'impot-independant-luxembourg-2026',
                'slug' => 'einkommensteuer-selbstaendige-luxemburg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'title' => 'Einkommensteuer für Selbständige in Luxemburg: so funktioniert es 2026',
                'meta_title' => 'Einkommensteuer Selbständige Luxemburg 2026: Leitfaden | faktur.lu',
                'meta_description' => 'Wie wird der Selbständige in Luxemburg besteuert? Gewinn, progressiver Tarif, Vorauszahlungen, abzugsfähige Kosten. Leitfaden 2026, um die Steuer richtig einzuplanen.',
                'excerpt' => 'Die Steuer des Selbständigen wird nicht auf den Umsatz, sondern auf den Gewinn berechnet, nach einem progressiven Tarif. Das Wichtigste zum richtigen Einplanen.',
                'published_at' => '2026-08-24 09:00:00',
                'content' => <<<HTML
<p class="lead">Nach den <a href="/de/blog/sozialbeitraege-selbstaendige-ccss-luxemburg-2026">Sozialbeiträgen</a> ist der andere große Posten des Selbständigen die <strong>Einkommensteuer</strong>. Gute Nachricht: Man wird nicht auf alles besteuert, was man einnimmt. So funktioniert es in Luxemburg.</p>

<h2>Besteuert wird der Gewinn, nicht der Umsatz</h2>
<p>Die Bemessungsgrundlage des Selbständigen ist der <strong>Gewinn</strong>: Ihr Umsatz abzüglich Ihrer <strong>abzugsfähigen Betriebskosten</strong> (Ausgaben, Einkäufe, Abschreibungen, Sozialbeiträge…). Je sorgfältiger Ihre Buchhaltung, desto mehr setzen Sie ab, was Ihnen zusteht.</p>

<h2>Ein progressiver Tarif</h2>
<p>Die luxemburgische Einkommensteuer ist <strong>progressiv</strong>: Je höher das zu versteuernde Einkommen, desto höher der Grenzsteuersatz, in Stufen. Ein bescheidener Einkommensteil bleibt steuerfrei, dann steigen die Sätze bis zu einem hohen Spitzengrenzsatz. Hinzu kommt der <strong>Beitrag zum Beschäftigungsfonds</strong> (Solidaritätssteuer). Ihre <strong>Steuerklasse</strong> (je nach Familiensituation) beeinflusst die Berechnung.</p>
<p class="text-sm text-slate-500">Die genauen Stufen und Sätze des Tarifs sind gesetzlich festgelegt und werden regelmäßig angepasst. Maßgeblich ist die Steuerverwaltung (ACD, impotsdirects.public.lu) oder Ihre Treuhandgesellschaft.</p>

<h2>Die vierteljährlichen Vorauszahlungen</h2>
<p>Die ACD verlangt in der Regel <strong>vierteljährliche Steuervorauszahlungen</strong>, geschätzt auf Basis Ihrer früheren Einkünfte. Bei der Jahreserklärung wird der Saldo ausgeglichen: Sie zahlen nach oder erhalten eine Erstattung. Steigen Ihre Einkünfte, rechnen Sie im Folgejahr mit höheren Vorauszahlungen.</p>

<h2>Die Jahreserklärung</h2>
<p>Jährlich füllen Sie Ihre Steuererklärung aus (online über MyGuichet oder mit Ihrer Treuhandgesellschaft). Dort tragen Sie Ihren Gewinn und Ihre abzugsfähigen Posten ein. Hier zählen alle übers Jahr gesammelten Belege.</p>

<h2>So planen Sie richtig</h2>
<ul>
    <li><strong>Legen Sie die Steuer</strong> zusätzlich zu den ~25 % Sozialbeiträgen zurück: Geben Sie nicht alles Eingenommene aus.</li>
    <li>Bewahren Sie alle <strong>Kostenbelege</strong> auf: Jede abzugsfähige Ausgabe senkt Ihre Bemessungsgrundlage.</li>
    <li>Führen Sie Ihre Buchhaltung aktuell, um Ihren tatsächlichen Gewinn zu kennen und Überraschungen zu vermeiden.</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Behalten Sie Ihren Gewinn das ganze Jahr im Blick</h3>
    <p class="text-primary-800 mb-4">faktur.lu verfolgt Einnahmen und Ausgaben und gibt Ihnen einen klaren Blick auf Ihren Gewinn - für Steuer und Beiträge.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],

            // ===== 2026-08-31 =====
            [
                'category_id' => 5,
                'translation_key' => 'sarl-s-vs-entreprise-individuelle-luxembourg',
                'slug' => 'sarl-s-oder-einzelunternehmen-luxemburg',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=630&fit=crop',
                'title' => 'SARL-S oder Einzelunternehmen in Luxemburg: welche Rechtsform wählen?',
                'meta_title' => 'SARL-S oder Einzelunternehmen in Luxemburg? Vergleich | faktur.lu',
                'meta_description' => 'SARL-S oder Einzelunternehmen in Luxemburg: Haftung, Kapital, Buchhaltung, Besteuerung. Klarer Vergleich für die richtige Rechtsform 2026.',
                'excerpt' => 'Als Einzelunternehmen starten oder eine SARL-S gründen? Beide haben Vorteile. Ein klarer Vergleich für die Wahl je nach Situation.',
                'published_at' => '2026-08-31 09:00:00',
                'content' => <<<HTML
<p class="lead">Beim Start in Luxemburg stellt sich immer wieder die Frage: als <strong>Einzelunternehmen</strong> bleiben oder eine <strong>SARL-S</strong> (vereinfachte Gesellschaft mit beschränkter Haftung) gründen? Hier die echten Unterschiede.</p>

<h2>Das Einzelunternehmen</h2>
<p>Die einfachste Form: Sie handeln in eigenem Namen, ohne eine eigene juristische Person zu schaffen. Kein Kapital, wenig Formalismus.</p>
<p><strong>Der Knackpunkt:</strong> Ihre Haftung ist <strong>unbeschränkt</strong> - Ihr Privatvermögen kann bei beruflichen Schulden herangezogen werden.</p>

<h2>Die SARL-S (vereinfachte Gesellschaft)</h2>
<p>Die SARL-S wurde geschaffen, um das Unternehmertum zu erleichtern. Sie ist <strong>natürlichen Personen</strong> vorbehalten, mit einem <strong>reduzierten Startkapital</strong> (zwischen 1 € und 12 000 €), und bietet eine auf die Einlagen <strong>beschränkte Haftung</strong>: Ihr Privatvermögen ist grundsätzlich geschützt.</p>
<p>Im Gegenzug bringt sie mehr Formalismus mit: Gründung, doppelte Buchführung, Gesellschaftspflichten, und ein Teil des Gewinns muss einer gesetzlichen Rücklage zugeführt werden, bis das Kapital einer klassischen SARL erreicht ist.</p>

<h2>Vergleich</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Kriterium</th><th class="text-left p-2 bg-slate-100">Einzelunternehmen</th><th class="text-left p-2 bg-slate-100">SARL-S</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b">Unbeschränkt (Privatvermögen)</td><td class="p-2 border-b">Auf Einlagen beschränkt</td></tr>
        <tr><td class="p-2 border-b">Kapital</td><td class="p-2 border-b">Keines</td><td class="p-2 border-b">1 € bis 12 000 €</td></tr>
        <tr><td class="p-2 border-b">Formalismus</td><td class="p-2 border-b">Minimal</td><td class="p-2 border-b">Höher (Gesellschaft)</td></tr>
        <tr><td class="p-2 border-b">Buchhaltung</td><td class="p-2 border-b">Vereinfachung möglich</td><td class="p-2 border-b">Doppelte Buchführung</td></tr>
        <tr><td class="p-2 border-b">Besteuerung</td><td class="p-2 border-b">Einkommensteuer</td><td class="p-2 border-b">Körperschaftsteuer</td></tr>
    </tbody>
</table>

<h2>Wie wählen?</h2>
<ul>
    <li><strong>Tätigkeit mit geringem Risiko, leichter Start</strong> → das Einzelunternehmen reicht oft.</li>
    <li><strong>Finanzielles Risiko, Schutz des Vermögens, „Gesellschafts"-Image</strong> → die SARL-S ist interessant.</li>
</ul>
<p>Die Wahl hat dauerhafte rechtliche und steuerliche Folgen: Lassen Sie sich von einer <a href="/de/blog/zusammenarbeit-treuhandgesellschaft-luxemburg-leitfaden">Treuhandgesellschaft</a> oder der House of Entrepreneurship beraten, bevor Sie entscheiden.</p>
<p class="text-sm text-slate-500">Die genauen Bedingungen (Kapital, gesetzliche Rücklage, Genehmigungen) sind gesetzlich festgelegt und auf guichet.lu sowie beim Luxembourg Business Registers (LBR) beschrieben.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Welche Rechtsform auch immer - rechnen Sie konform ab</h3>
    <p class="text-primary-800 mb-4">Einzelunternehmen oder SARL-S: faktur.lu erstellt in Luxemburg konforme Rechnungen und die Exporte für Ihren Buchhalter.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML,
            ],
        ];
    }
}
