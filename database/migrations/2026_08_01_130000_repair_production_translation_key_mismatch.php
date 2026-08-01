<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repare 27 articles dont la production avait perdu l'appariement.
 *
 * Constat : sur ces lignes, `translation_key` designait un AUTRE article que
 * celui du slug — une permutation circulaire touchant l'allemand, l'anglais et
 * le luxembourgeois. Le francais et le portugais etaient intacts.
 *
 * Consequence : toutes les migrations editoriales ciblent
 * `translation_key` + `locale`. Sur ces lignes, elles ecrivaient donc dans la
 * mauvaise ligne. Des corrections destinees aux articles piliers ont atterri
 * sur les guides « creer une entreprise individuelle », et reciproquement ces
 * guides servaient un contenu hors sujet sous un titre correct :
 * /de/blog/einzelunternehmen-deutschland-gruenden-leitfaden-2026 affichait
 * l'article « Pflichtangaben nach Artikel 63 LIVA ».
 *
 * Le `slug`, lui, etait correct partout : c'est donc la cle de reparation.
 * Titre, extrait et metadonnees etaient corrects et ne sont pas touches.
 *
 * Les contenus embarques ci-dessous proviennent de la base de reference, ou
 * l'appariement n'a jamais ete rompu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repaired = 0;

        foreach ($this->articles() as $article) {
            $updated = DB::table('blog_posts')
                ->where('slug', $article['slug'])
                ->where('locale', $article['locale'])
                ->update([
                    'translation_key' => $article['translation_key'],
                    'content' => $article['content'],
                    'updated_at' => now(),
                ]);

            $repaired += $updated;
        }

        echo "  {$repaired} article(s) reapparie(s)\n";
    }

    public function down(): void
    {
        // Restaurer un appariement casse n'aurait pas de sens.
    }

    /** @return array<int, array{slug: string, locale: string, translation_key: string, content: string}> */
    private function articles(): array
    {
        return [
            [
                'slug' => 'einzelunternehmen-belgien-gruenden-leitfaden-2026',
                'locale' => 'de',
                'translation_key' => 'creer-entreprise-individuelle-belgique-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Belgien bietet einen günstigen Rahmen für Selbständige mit vereinfachten Verfahren seit der Abschaffung der Grundkenntnisse in Unternehmensführung. Dieser Leitfaden begleitet Sie bei der Gründung Ihres Unternehmens als natürliche Person.</p>

<h2>Rechtsform: Unternehmen als natürliche Person</h2>

<p>Das Unternehmen als natürliche Person (Selbständiger) ist die einfachste Form, um allein eine wirtschaftliche Tätigkeit in Belgien auszuüben.</p>

<h3>Hauptmerkmale</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspekt</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gründungsakt</td><td class="p-2 border-b">Nicht erforderlich</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Nicht erforderlich</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Unbeschränkt</strong> - privates und geschäftliches Vermögen vermengt</td></tr>
        <tr><td class="p-2 border-b">Statistik</td><td class="p-2 border-b">43% der belgischen KMU (510.346 Unternehmen)</td></tr>
    </tbody>
</table>

<h2>Voraussetzungen</h2>

<h3>Allgemeine Voraussetzungen</h3>

<ul>
    <li>Mindestens <strong>18 Jahre</strong> alt sein</li>
    <li>Bürgerliche und politische Rechte genießen</li>
    <li>Rechtlich handlungsfähig sein</li>
</ul>

<h3>Grundkenntnisse in Unternehmensführung: ABGESCHAFFT</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Gute Nachricht!</p>
    <p>Die Grundkenntnisse in Unternehmensführung wurden in allen Regionen abgeschafft:</p>
    <ul class="mt-2">
        <li><strong>Flandern:</strong> seit 2018</li>
        <li><strong>Brüssel:</strong> seit 15. Januar 2024</li>
        <li><strong>Wallonien:</strong> seit 1. Oktober 2025</li>
    </ul>
</div>

<h3>Berufszugang</h3>

<p>Bestimmte regulierte Berufe erfordern weiterhin <strong>spezifische Berufskompetenzen</strong>: Friseur, Bäcker, Konditor, Automechaniker, Dachdecker, Heizungsbauer, Gastronom usw.</p>

<h2>Gründungsschritte</h2>

<h3>Schritt 1: Geschäftskonto eröffnen</h3>
<p>Pflicht zur Trennung von geschäftlichen und privaten Transaktionen.</p>

<h3>Schritt 2: Eintragung in die Zentrale Datenbank der Unternehmen (ZDU)</h3>
<ul>
    <li>Über einen <strong>zugelassenen Unternehmensschalter</strong></li>
    <li>Erhalt der <strong>Unternehmensnummer</strong> (eindeutige Kennung)</li>
    <li>Prüfung der Berufskompetenzen falls erforderlich</li>
</ul>

<h3>Schritt 3: MwSt.-Nummer aktivieren</h3>
<ul>
    <li>Bei der Allgemeinen Steuerverwaltung</li>
    <li>Kann über den Unternehmensschalter erfolgen</li>
    <li>Möglichkeit, das MwSt.-Franchiseregime zu beantragen (bei Umsatz < 25.000 €)</li>
</ul>

<h3>Schritt 4: Anmeldung bei einer Sozialversicherungskasse</h3>
<p><strong>Pflicht VOR Beginn der Tätigkeit</strong>. Anmeldung bis zu 6 Monate vorher möglich.</p>

<h3>Schritt 5: Anmeldung bei einer Krankenkasse</h3>
<p>Pflicht für die Kranken- und Invaliditätsversicherung.</p>

<h3>Schritt 6: Notwendige Versicherungen abschließen</h3>
<p>Berufshaftpflichtversicherung und andere je nach Tätigkeit.</p>

<h2>Die 8 zugelassenen Unternehmensschalter</h2>

<ol>
    <li>Liantis (der größte)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Eunomia</li>
    <li>Formalis</li>
</ol>

<h2>Gründungskosten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">ZDU-Eintragung über Schalter</td><td class="p-2 border-b">109 - 111,50 € (MwSt.-befreit)</td></tr>
        <tr><td class="p-2 border-b">Diverse Kosten</td><td class="p-2 border-b">Variabel</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschätztes Gesamtbudget</td><td class="p-2 border-b font-semibold">200 - 500 €</td></tr>
    </tbody>
</table>

<h2>Durchschnittliche Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Verfahren</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">ZDU-Eintragung über Schalter</td><td class="p-2 border-b">Sofort bis einige Tage</td></tr>
        <tr><td class="p-2 border-b">MwSt.-Aktivierung</td><td class="p-2 border-b">Einige Tage</td></tr>
        <tr><td class="p-2 border-b">Anmeldung Sozialkasse</td><td class="p-2 border-b">Sofort</td></tr>
        <tr><td class="p-2 border-b font-semibold">Gesamtprozess</td><td class="p-2 border-b font-semibold">1 bis 2 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten nach der Gründung</h2>

<h3>MwSt.</h3>

<h4>Normalregime</h4>
<ul>
    <li>Periodische MwSt.-Erklärung (monatlich oder vierteljährlich)</li>
    <li>Rechnungsstellung mit MwSt.</li>
    <li>Jährliche Kundenliste</li>
</ul>

<h4>Franchiseregime (bei Umsatz < 25.000 €)</h4>
<ul>
    <li>Keine periodische Erklärung</li>
    <li>Keine MwSt. zu berechnen oder abzuführen</li>
    <li>Mitteilung des Jahresumsatzes vor dem 31. März</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Pflichthinweis bei Franchise</p>
    <p>„Kleinunternehmen im Rahmen der Steuerfreistellung - MwSt. nicht anwendbar (Art. 56bis des MwSt.-Gesetzes)"</p>
</div>

<h3>Sozialversicherungsbeiträge (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Einkommensstufe</th>
            <th class="text-left p-2 bg-slate-100">Satz 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">0 bis 73.447,52 €</td><td class="p-2 border-b font-semibold">20,50%</td></tr>
        <tr><td class="p-2 border-b">73.447,52 bis 108.238,40 €</td><td class="p-2 border-b">14,16%</td></tr>
        <tr><td class="p-2 border-b">Über 108.238,40 €</td><td class="p-2 border-b">Befreit</td></tr>
    </tbody>
</table>

<p><strong>Mindestbeitrag 2026:</strong> 450,15 €/Quartal (hauptberuflich Selbständiger)</p>

<p><strong>Funktionsweise:</strong></p>
<ul>
    <li><strong>Vierteljährliche</strong> Zahlung</li>
    <li>Zunächst <strong>vorläufige</strong> Beiträge (basierend auf Einkommen N-3)</li>
    <li>Regulierung nach Bekanntwerden der endgültigen Einkünfte</li>
</ul>

<h3>Buchhaltungspflichten</h3>

<h4>Vereinfachte Buchhaltung (Umsatz < 500.000 €)</h4>
<p>3 Pflichtjournale:</p>
<ol>
    <li><strong>Einkaufsjournal:</strong> Auflistung der Ausgaben</li>
    <li><strong>Verkaufsjournal:</strong> Chronologische Übersicht der Rechnungen</li>
    <li><strong>Finanzjournal:</strong> Kassenbuch + Bankbuch</li>
</ol>

<p><strong>Aufbewahrung der Dokumente:</strong> 10 Jahre</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">FÖD Wirtschaft - Schritte für einen Selbständigen</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub für Unternehmer</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Sozialversicherungsbeiträge</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">FÖD Finanzen - MwSt.-Franchiseregime</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zusammenfassung</p>
    <p>Selbständig in Belgien zu werden kostet zwischen 200 und 500 € und dauert 1 bis 2 Wochen. Die Sozialversicherungsbeiträge betragen 20,5% des Einkommens. Die MwSt.-Franchise ist möglich, wenn der Umsatz unter 25.000 €/Jahr bleibt.</p>
</div>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'einzelunternehmen-deutschland-gruenden-leitfaden-2026',
                'locale' => 'de',
                'translation_key' => 'creer-entreprise-individuelle-allemagne-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Deutschland bietet mehrere Möglichkeiten zur Gründung eines Einzelunternehmens mit relativ einfachen und schnellen Verfahren. Dieser Leitfaden stellt die verschiedenen Rechtsformen und die Schritte für Ihren Start vor.</p>

<h2>Rechtsformen für Einzelunternehmen</h2>

<h3>Einzelunternehmen</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Merkmal</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definition</td><td class="p-2 border-b">Von einer Person geführtes Unternehmen</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Nicht erforderlich</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Unbeschränkt</strong></td></tr>
        <tr><td class="p-2 border-b">Gründung</td><td class="p-2 border-b">Gewerbeanmeldung + Steuernummer</td></tr>
        <tr><td class="p-2 border-b">Besteuerung</td><td class="p-2 border-b">Einkommensteuer + Gewerbesteuer (wenn > 24.500 €/Jahr)</td></tr>
    </tbody>
</table>

<p><strong>Unterkategorien:</strong></p>
<ul>
    <li><strong>Kleingewerbetreibender:</strong> Keine Eintragung ins Handelsregister</li>
    <li><strong>Eingetragener Kaufmann (e.K.):</strong> Im Handelsregister eingetragen</li>
</ul>

<h3>Freiberufler</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Merkmal</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definition</td><td class="p-2 border-b">Intellektuelle, kreative, wissenschaftliche oder pädagogische Tätigkeit</td></tr>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b text-green-600">NICHT erforderlich</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b text-green-600">NICHT anwendbar</td></tr>
        <tr><td class="p-2 border-b">IHK/HWK</td><td class="p-2 border-b text-green-600">Kein Pflichtbeitrag</td></tr>
        <tr><td class="p-2 border-b">Anmeldung</td><td class="p-2 border-b">Direkt beim Finanzamt</td></tr>
    </tbody>
</table>

<p><strong>Betroffene Berufe (Katalogberufe):</strong> Ärzte, Anwälte, Architekten, Ingenieure, Journalisten, Übersetzer, Künstler, Lehrer...</p>

<h2>Voraussetzungen</h2>

<h3>Für Gewerbetreibende</h3>

<ul>
    <li><strong>Mindestalter:</strong> 18 Jahre (Volljährigkeit)</li>
    <li><strong>Wohnsitz:</strong> Adresse in Deutschland</li>
    <li><strong>Dokumente:</strong> Reisepass oder Personalausweis</li>
    <li><strong>Legale Tätigkeit:</strong> Gesetzlich erlaubte Tätigkeit</li>
</ul>

<h3>Mögliche zusätzliche Dokumente</h3>

<ul>
    <li><strong>Führungszeugnis:</strong> ~13 €</li>
    <li><strong>Gewerbezentralregisterauszug:</strong> ~13 €</li>
    <li><strong>Handwerkskarte:</strong> 80-250 €</li>
</ul>

<h2>Gründungsschritte</h2>

<h3>Weg A: Gewerbetreibender</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Schritt 1: Gewerbeanmeldung (Gewerbeamt)<br>
        ↓<br>
        Schritt 2: Automatische Benachrichtigungen (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>
        ↓<br>
        Schritt 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>
        ↓<br>
        Schritt 4: Erteilung der Steuernummer<br>
        ↓<br>
        Schritt 5: Anmeldung Berufsgenossenschaft (7 Tage)
    </p>
</div>

<h4>Gewerbeanmeldung</h4>
<ul>
    <li><strong>Wo:</strong> Gewerbeamt der Gemeinde des Sitzes</li>
    <li><strong>Formular:</strong> GewA 1</li>
    <li><strong>Art:</strong> Online (Gewerbe-Service-Portal) oder vor Ort</li>
    <li><strong>Frist:</strong> 1-3 Tage</li>
</ul>

<h3>Weg B: Freiberufler</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Schritt 1: Anmeldung beim Finanzamt (innerhalb von 4 Wochen nach Beginn)<br>
        ↓<br>
        Schritt 2: Fragebogen zur steuerlichen Erfassung<br>
        ↓<br>
        Schritt 3: Erteilung der Steuernummer
    </p>
</div>

<h2>Gründungskosten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung (Basis)</td><td class="p-2 border-b">12,50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Großstädte (München, Stuttgart)</td><td class="p-2 border-b">50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Kleine Gemeinden</td><td class="p-2 border-b">15 - 30 €</td></tr>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600 font-semibold">0 € (kostenlos)</td></tr>
    </tbody>
</table>

<h2>Durchschnittliche Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Schritt</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Bearbeitung Gewerbeanmeldung</td><td class="p-2 border-b font-semibold">1-3 Tage</td></tr>
        <tr><td class="p-2 border-b">Schriftliche Bestätigung Gewerbeamt</td><td class="p-2 border-b">Maximal 3 Tage</td></tr>
        <tr><td class="p-2 border-b">Erhalt Fragebogen Finanzamt</td><td class="p-2 border-b">4-6 Wochen</td></tr>
        <tr><td class="p-2 border-b">Erteilung Steuernummer</td><td class="p-2 border-b">2-4 Wochen</td></tr>
        <tr><td class="p-2 border-b font-semibold">Gesamtfrist</td><td class="p-2 border-b font-semibold">6-10 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten nach der Gründung</h2>

<h3>Umsatzsteuer</h3>

<h4>Normalregime</h4>
<ul>
    <li><strong>Normalsatz:</strong> 19%</li>
    <li><strong>Ermäßigter Satz:</strong> 7%</li>
    <li>Monatliche oder vierteljährliche Erklärung (Umsatzsteuer-Voranmeldung)</li>
</ul>

<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Kriterium</th>
            <th class="text-left p-2 bg-slate-100">Schwellenwert 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Umsatz Vorjahr</td><td class="p-2 border-b">≤ 25.000 €</td></tr>
        <tr><td class="p-2 border-b">Umsatz laufendes Jahr</td><td class="p-2 border-b">≤ 100.000 €</td></tr>
    </tbody>
</table>

<p><strong>Vorteile:</strong></p>
<ul>
    <li>Keine Umsatzsteuerberechnung</li>
    <li>Keine Umsatzsteuererklärungen</li>
    <li>Vereinfachte Buchhaltung</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Pflichthinweis auf Rechnungen</p>
    <p>„Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG"</p>
</div>

<h3>Gewerbesteuer</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Pflicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600">Befreit</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender < 24.500 €/Jahr</td><td class="p-2 border-b text-green-600">Befreit (Freibetrag)</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender ≥ 24.500 €/Jahr</td><td class="p-2 border-b">Steuerpflichtig</td></tr>
    </tbody>
</table>

<h3>Sozialversicherung</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Art</th>
            <th class="text-left p-2 bg-slate-100">Pflicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Krankenversicherung</td><td class="p-2 border-b text-red-600 font-semibold">PFLICHT</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung</td><td class="p-2 border-b text-red-600 font-semibold">PFLICHT</td></tr>
        <tr><td class="p-2 border-b">Rentenversicherung</td><td class="p-2 border-b">Freiwillig*</td></tr>
        <tr><td class="p-2 border-b">Arbeitslosenversicherung</td><td class="p-2 border-b">Freiwillig</td></tr>
    </tbody>
</table>

<p><small>*Pflicht für bestimmte Berufe (Handwerker, Lehrer, Pflegekräfte)</small></p>

<h3>IHK/HWK-Beitrag</h3>

<ul>
    <li>Automatische und obligatorische Mitgliedschaft für Gewerbetreibende</li>
    <li>Befreiung wenn Gewerbeertrag < 5.200 €/Jahr</li>
    <li>Progressive Beiträge darüber hinaus</li>
</ul>

<h2>Vergleichstabelle</h2>

<table class="w-full my-4 text-sm">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Kriterium</th>
            <th class="text-left p-2 bg-slate-100">Einzelunternehmen</th>
            <th class="text-left p-2 bg-slate-100">Freiberufler</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b">Ja</td><td class="p-2 border-b text-green-600">Nein</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b">Ja (> 24.500 €)</td><td class="p-2 border-b text-green-600">Nein</td></tr>
        <tr><td class="p-2 border-b">IHK-Mitgliedschaft</td><td class="p-2 border-b">Pflicht</td><td class="p-2 border-b text-green-600">Nein</td></tr>
        <tr><td class="p-2 border-b">Gründungskosten</td><td class="p-2 border-b">12,50-60 €</td><td class="p-2 border-b text-green-600">0 €</td></tr>
        <tr><td class="p-2 border-b">Gründungsdauer</td><td class="p-2 border-b">1-3 Tage</td><td class="p-2 border-b text-green-600">Sofort</td></tr>
    </tbody>
</table>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://www.existenzgruendungsportal.de/" target="_blank" rel="noopener">Existenzgründungsportal (BMWK)</a></li>
    <li><a href="https://www.bmwk.de/" target="_blank" rel="noopener">Bundesministerium für Wirtschaft (BMWK)</a></li>
    <li><a href="https://www.ihk.de/" target="_blank" rel="noopener">IHK - Industrie- und Handelskammer</a></li>
    <li><a href="https://www.deutsche-rentenversicherung.de/" target="_blank" rel="noopener">Deutsche Rentenversicherung</a></li>
    <li><a href="https://gruenderplattform.de/" target="_blank" rel="noopener">Gründerplattform</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zusammenfassung</p>
    <p>Die Gründung eines Einzelunternehmens in Deutschland kostet je nach Status zwischen 0 und 60 €. Die Gewerbeanmeldung wird in 1-3 Tagen bearbeitet. Die Kleinunternehmerregelung ermöglicht die Umsatzsteuerbefreiung unter bestimmten Schwellen. Freiberufler profitieren von einem vereinfachten Regime ohne Gewerbesteuer und IHK-Beitrag.</p>
</div>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'einzelunternehmen-frankreich-gruenden-leitfaden-2026',
                'locale' => 'de',
                'translation_key' => 'creer-entreprise-individuelle-france-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Frankreich bietet einen vereinfachten Rahmen für die Gründung eines Einzelunternehmens, insbesondere mit dem Mikro-Unternehmensregime. Seit 2023 erfolgen alle Formalitäten über den einheitlichen Schalter des INPI. Entdecken Sie die Schritte, Kosten und Pflichten für Ihren Start.</p>

<h2>Rechtsformen für Einzelunternehmen</h2>

<h3>Einzelunternehmen (EI)</h3>

<p>Das Einzelunternehmen ermöglicht die Ausübung einer Tätigkeit im eigenen Namen ohne Gründung einer juristischen Person.</p>

<ul>
    <li>Kein Stammkapital erforderlich</li>
    <li>Keine Satzung zu erstellen</li>
    <li>Mögliche Aktivitäten: Handel, Handwerk, Landwirtschaft oder freie Berufe</li>
    <li><strong>Seit Februar 2022</strong>: Private und geschäftliche Vermögen sind automatisch getrennt</li>
</ul>

<h3>Mikro-Unternehmen (Auto-entrepreneur)</h3>

<p>Das Mikro-Unternehmen ist ein vereinfachtes Regime des Einzelunternehmens mit Umsatzschwellen:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tätigkeitsart</th>
            <th class="text-left p-2 bg-slate-100">Umsatzschwelle (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Warenverkauf, Beherbergung</td><td class="p-2 border-b">203.100 €</td></tr>
        <tr><td class="p-2 border-b">Dienstleistungen</td><td class="p-2 border-b">83.600 €</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Gut zu wissen</p>
    <p>Die EIRL existiert seit dem 15. Mai 2022 nicht mehr. Der neue EI-Status integriert nun automatisch die Vermögenstrennung.</p>
</div>

<h2>Voraussetzungen</h2>

<h3>Persönliche Voraussetzungen</h3>

<ul>
    <li><strong>Volljährig</strong> sein (oder emanzipierter Minderjähriger)</li>
    <li>Eine <strong>Adresse in Frankreich</strong> haben</li>
    <li>Nicht unter Vormundschaft oder Betreuung stehen</li>
    <li>Kein Geschäftsführungsverbot haben</li>
    <li>Französische, europäische Staatsangehörigkeit oder Aufenthaltstitel zur Berufsausübung</li>
</ul>

<h3>Regulierte Tätigkeiten</h3>

<p>Bestimmte Berufe erfordern spezifische Diplome oder Qualifikationen: Friseur, Bau, Gesundheitsberufe usw.</p>

<h2>Gründungsschritte über den Einheitlichen Schalter INPI</h2>

<h3>Schritt 1: Dokumentenvorbereitung</h3>
<ul>
    <li>Ausweis (Personalausweis oder Reisepass) im PDF-Format</li>
    <li>Wohnsitznachweis (bei Tätigkeit zu Hause)</li>
    <li>Qualifikationsnachweise für regulierte Tätigkeiten</li>
</ul>

<h3>Schritt 2: Kontoerstellung</h3>
<p>Besuchen Sie <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> und erstellen Sie ein Konto über France Connect (empfohlen) oder eine INPI-Kennung.</p>

<h3>Schritt 3: Tätigkeitsanmeldung</h3>
<ol>
    <li>Klicken Sie auf „Anmelden"</li>
    <li>Wählen Sie „Einzelunternehmer"</li>
    <li>Geben Sie ein: Art der Tätigkeit, Adresse, Startdatum, Steuer- und Sozialoptionen</li>
</ol>

<h3>Schritt 4: Validierung und Nachverfolgung</h3>
<ul>
    <li>Belege anhängen</li>
    <li>Bei Bedarf Zahlung vornehmen</li>
    <li>Fortschritt über das Dashboard verfolgen</li>
    <li>Automatische Eintragung im RNE (Nationales Unternehmensregister)</li>
</ul>

<h2>Gründungskosten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tätigkeitsart</th>
            <th class="text-left p-2 bg-slate-100">Kosten</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Handelstätigkeit</td><td class="p-2 border-b text-green-600 font-semibold">Kostenlos</td></tr>
        <tr><td class="p-2 border-b">Handwerkstätigkeit</td><td class="p-2 border-b text-green-600 font-semibold">Kostenlos</td></tr>
        <tr><td class="p-2 border-b">Freier Beruf</td><td class="p-2 border-b text-green-600 font-semibold">Kostenlos</td></tr>
        <tr><td class="p-2 border-b">Handelsvertreter</td><td class="p-2 border-b">23,86 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Achtung</p>
    <p>Vorsicht vor privaten Websites, die Gebühren für einen normalerweise kostenlosen Service erheben.</p>
</div>

<h2>Durchschnittliche Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Schritt</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Online-Anmeldung</td><td class="p-2 border-b">Einige Minuten</td></tr>
        <tr><td class="p-2 border-b">Eingangsbestätigung</td><td class="p-2 border-b">24 Stunden</td></tr>
        <tr><td class="p-2 border-b">Erhalt der SIRET-Nummer</td><td class="p-2 border-b font-semibold">1 bis 2 Wochen</td></tr>
        <tr><td class="p-2 border-b">URSSAF-Benachrichtigung</td><td class="p-2 border-b">4 bis 10 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten nach der Gründung</h2>

<h3>URSSAF-Beiträge</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tätigkeitsart</th>
            <th class="text-left p-2 bg-slate-100">Satz 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Kauf-Wiederverkauf</td><td class="p-2 border-b">12,3 %</td></tr>
        <tr><td class="p-2 border-b">Handels-/Handwerksdienstleistungen</td><td class="p-2 border-b">21,2 %</td></tr>
        <tr><td class="p-2 border-b">Sonstige Dienstleistungen</td><td class="p-2 border-b">25,6 %</td></tr>
        <tr><td class="p-2 border-b">Freie Berufe (Cipav)</td><td class="p-2 border-b">23,2 %</td></tr>
    </tbody>
</table>

<p><strong>Häufigkeit:</strong> Monatliche oder vierteljährliche Erklärung (nach Wahl). Erklärungspflicht auch bei Nullumsatz.</p>

<h3>MwSt. - Basisfranchise</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tätigkeitsart</th>
            <th class="text-left p-2 bg-slate-100">Basisschwelle</th>
            <th class="text-left p-2 bg-slate-100">Erhöhte Schwelle</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Verkauf/Handel/Beherbergung</td><td class="p-2 border-b">85.000 €</td><td class="p-2 border-b">93.500 €</td></tr>
        <tr><td class="p-2 border-b">Dienstleistungen</td><td class="p-2 border-b">37.500 €</td><td class="p-2 border-b">41.250 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Pflichthinweis bei Franchise</p>
    <p>„MwSt. nicht anwendbar, Art. 293 B des CGI"</p>
</div>

<h3>CFE (Grundsteuer für Unternehmen)</h3>

<ul>
    <li><strong>1. Jahr:</strong> Von der Zahlung befreit</li>
    <li><strong>Vollständige Befreiung:</strong> Bei Jahresumsatz < 5.000 €</li>
    <li><strong>Pflicht:</strong> Erklärung Nr. 1447-C vor dem 31. Dezember des 1. Jahres einreichen</li>
</ul>

<h3>Buchhaltungspflichten</h3>

<ol>
    <li><strong>Konforme Rechnungen</strong> für jeden Verkauf/jede Leistung erstellen</li>
    <li>Ein chronologisches <strong>Einnahmenbuch</strong> führen</li>
    <li>Ein <strong>Einkaufsregister</strong> führen (bei Verkaufstätigkeit)</li>
    <li><strong>Belege</strong> 10 Jahre aufbewahren</li>
</ol>

<h2>Verfügbare Hilfen</h2>

<h3>ACRE (Hilfe für Unternehmensgründer)</h3>

<ul>
    <li><strong>Teilweise Befreiung</strong> von Sozialabgaben im 1. Jahr (bis zu 50%)</li>
    <li>Bedingungen: Arbeitssuchende, RSA-Empfänger, Jugendliche 18-25 Jahre usw.</li>
    <li>Antrag bei Gründung oder innerhalb von 45 Tagen zu stellen</li>
</ul>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Einzelunternehmer</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">Einheitlicher Schalter für Unternehmensformalitäten</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-entrepreneur</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Einzelunternehmen gründen</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zusammenfassung</p>
    <p>Die Gründung eines Mikro-Unternehmens in Frankreich ist kostenlos und schnell (SIRET in 1-2 Wochen). Die Sozialabgaben variieren je nach Tätigkeit zwischen 12 und 26%. Die MwSt.-Franchise ermöglicht es, unter bestimmten Schwellen keine MwSt. zu berechnen.</p>
</div>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'einzelunternehmen-luxemburg-gruenden-leitfaden-2026',
                'locale' => 'de',
                'translation_key' => 'creer-entreprise-individuelle-luxembourg-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Luxemburg bietet ein günstiges Umfeld für Unternehmer mit relativ einfachen Verwaltungsverfahren und moderaten Gründungskosten. Dieser Leitfaden begleitet Sie Schritt für Schritt bei der Gründung Ihres Einzelunternehmens im Großherzogtum.</p>

<h2>Rechtsformen für Einzelunternehmen</h2>

<p>In Luxemburg übt ein selbständiger Unternehmer seinen Beruf in eigenem Namen aus als:</p>

<ul>
    <li><strong>Händler</strong>: für kommerzielle Tätigkeiten</li>
    <li><strong>Handwerker</strong>: für handwerkliche Tätigkeiten</li>
    <li><strong>Selbständiger Geistesarbeiter</strong>: für freie Berufe</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Hinweis</p>
    <p>Es gibt kein genaues Äquivalent zum französischen Auto-Entrepreneur-Status in Luxemburg. Das Einzelunternehmen ist die nächstliegende und einfachste Form.</p>
</div>

<h3>Hauptmerkmale</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspekt</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Rechtspersönlichkeit</td><td class="p-2 border-b">Keine - der Unternehmer handelt in eigenem Namen</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Kein Mindestkapital erforderlich</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Unbeschränkt</strong> - haftet mit seinem Privatvermögen</td></tr>
        <tr><td class="p-2 border-b">Formalismus</td><td class="p-2 border-b">Minimal - keine Satzung erforderlich</td></tr>
    </tbody>
</table>

<h2>Voraussetzungen</h2>

<h3>Niederlassungsgenehmigung (Pflicht)</h3>

<p>Jede regelmäßig ausgeübte wirtschaftliche Tätigkeit erfordert eine <strong>vorherige Niederlassungsgenehmigung</strong>.</p>

<p><strong>Zu erfüllende Bedingungen:</strong></p>

<ul>
    <li><strong>Physische Niederlassung</strong>: angemessene materielle Einrichtung in Luxemburg</li>
    <li><strong>Effektive Geschäftsführung</strong>: physische Präsenz und tägliche Verwaltung durch den Inhaber</li>
    <li><strong>Berufliche Ehrenhaftigkeit</strong>: einwandfreies Führungszeugnis, Einhaltung früherer Steuer- und Sozialpflichten</li>
    <li><strong>Berufsqualifikation</strong>: je nach angestrebter Tätigkeit</li>
</ul>

<h3>Erforderliche Berufsqualifikationen</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tätigkeitsart</th>
            <th class="text-left p-2 bg-slate-100">Erforderliche Qualifikation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Kommerzielle Tätigkeiten</td><td class="p-2 border-b">Im Allgemeinen kein spezifischer Abschluss erforderlich</td></tr>
        <tr><td class="p-2 border-b">Handwerkliche Tätigkeiten</td><td class="p-2 border-b">DAP, CATP oder Meisterbrief</td></tr>
        <tr><td class="p-2 border-b">Freie Berufe</td><td class="p-2 border-b">Spezifische Abschlüsse je nach Beruf</td></tr>
    </tbody>
</table>

<h2>Detaillierte Gründungsschritte</h2>

<h3>Schritt 1: Projekterarbeitung</h3>
<ul>
    <li>Businessplan erstellen</li>
    <li>Begleitende Organisationen kontaktieren (House of Entrepreneurship, Handelskammer, Handwerkskammer)</li>
</ul>

<h3>Schritt 2: Prüfung der Voraussetzungen</h3>
<ul>
    <li>Verfügbarkeit des Handelsnamens prüfen</li>
    <li>Sicherstellen, dass die erforderlichen Qualifikationen vorhanden sind</li>
    <li>Bei Bedarf Diplomanerkennung beantragen</li>
</ul>

<h3>Schritt 3: Antrag auf Niederlassungsgenehmigung</h3>
<p><strong>Wo:</strong> Online über MyGuichet.lu (mit LuxTrust-Zertifikat) oder per Post</p>
<p><strong>Erforderliche Unterlagen:</strong></p>
<ul>
    <li>Antragsformular</li>
    <li>Nachweise der Berufsqualifikation</li>
    <li>Auszug aus dem Strafregister (Bulletin Nr. 3)</li>
    <li>Kopie des Personalausweises</li>
    <li>Zahlungsnachweis der Kanzleigebühr (50 EUR)</li>
</ul>

<h3>Schritt 4: Eintragung im RCS</h3>
<p><strong>Wo:</strong> Elektronische Einreichung auf der LBR-Website (Luxembourg Business Registers)</p>
<p><strong>Erforderliche Unterlagen:</strong></p>
<ul>
    <li>Anmeldeformular</li>
    <li>Niederlassungsgenehmigung</li>
    <li>Ausweis</li>
    <li>Heiratsurkunde / Ehevertrag (falls zutreffend)</li>
</ul>

<h3>Schritt 5: Anmeldung zur Sozialversicherung</h3>
<p>Anmeldung beim CCSS (Centre Commun de la Sécurité Sociale) als Selbständiger.</p>

<h3>Schritt 6: Steuerliche Anmeldung</h3>
<ul>
    <li>Anmeldung bei der Administration des Contributions Directes</li>
    <li>MwSt.-Anmeldung, falls Umsatz > 50.000 EUR</li>
</ul>

<h2>Gründungskosten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Niederlassungsgenehmigung</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">RCS-Eintragung</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Diplomanerkennung</td><td class="p-2 border-b">75 EUR (falls erforderlich)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschätzte Gesamtkosten</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Durchschnittliche Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Verfahren</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Niederlassungsgenehmigung</td><td class="p-2 border-b">Bis zu 3 Monate</td></tr>
        <tr><td class="p-2 border-b">Diplomanerkennung</td><td class="p-2 border-b">2 bis 6 Wochen</td></tr>
        <tr><td class="p-2 border-b">RCS-Eintragung</td><td class="p-2 border-b">Einige Tage</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschätzte Gesamtdauer</td><td class="p-2 border-b font-semibold">1 bis 3 Monate</td></tr>
    </tbody>
</table>

<h2>Pflichten nach der Gründung</h2>

<h3>MwSt. (Mehrwertsteuer)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Pflicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Jahresumsatz ≤ 50.000 EUR</td><td class="p-2 border-b">MwSt.-Befreiung (keine Anmeldepflicht)</td></tr>
        <tr><td class="p-2 border-b">Jahresumsatz > 50.000 EUR</td><td class="p-2 border-b">Pflichtanmeldung + periodische Erklärungen</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Pflichthinweis bei Franchise</p>
    <p>„MwSt. nicht anwendbar, Art. 57 des luxemburgischen MwSt.-Gesetzes (Steuerbefreiungsregelung)"</p>
</div>

<h3>Sozialversicherungsbeiträge (CCSS)</h3>

<p>Die Beiträge betragen etwa <strong>25,3%</strong> des Einkommens, aufgeteilt wie folgt:</p>

<ul>
    <li>Krankenversicherung (Sachleistungen): 5,60%</li>
    <li>Krankenversicherung (Geldleistungen): 0,50%</li>
    <li>Pflegeversicherung: 1,40%</li>
    <li>Rentenversicherung: 17,00%</li>
    <li>Unfallversicherung: 0,65%</li>
    <li>Arbeitsschutz: 0,14%</li>
</ul>

<h3>Buchhaltung</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Jahresumsatz</th>
            <th class="text-left p-2 bg-slate-100">Pflicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100.000 EUR netto</td><td class="p-2 border-b">Vereinfachte Buchhaltung</td></tr>
        <tr><td class="p-2 border-b">≥ 100.000 EUR netto</td><td class="p-2 border-b">Standardisierte Buchhaltung Pflicht</td></tr>
    </tbody>
</table>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html" target="_blank" rel="noopener">Guichet.lu - Einzelunternehmen</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Niederlassungsgenehmigung</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Selbständige</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zusammenfassung</p>
    <p>Die Gründung eines Einzelunternehmens in Luxemburg ist relativ einfach und kostengünstig (etwa 100-150 EUR). Das Verfahren dauert in der Regel 1 bis 3 Monate und umfasst die Erlangung der Niederlassungsgenehmigung und die RCS-Eintragung. Die Sozialversicherungsbeiträge betragen etwa 25% des Einkommens.</p>
</div>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'faia-luxemburg-informatisierte-audit-datei-leitfaden',
                'locale' => 'de',
                'translation_key' => 'faia-luxembourg-fichier-audit-informatise-guide',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Die FAIA (informatisierte Audit-Datei) ist eine Datei, die die AED bei einer Steuerprüfung verlangen kann. Entgegen einer verbreiteten Vorstellung betrifft sie nicht alle luxemburgischen Unternehmen: Vier kumulative Bedingungen bestimmen, wer sie erzeugen können muss.</p>

<h2>Was ist die FAIA?</h2>

<p>Die <strong>FAIA (Fichier d'Audit Informatisé)</strong>, auch <strong>SAF-T Luxemburg</strong> genannt, ist eine Datei im standardisierten XML-Format, die sämtliche Buchhaltungs- und Steuerdaten eines Unternehmens für einen bestimmten Zeitraum enthält.</p>

<p>Ihre Rechtsgrundlage ist das <strong>Gesetz vom 19. Dezember 2008</strong> (Mémorial A-206 vom 24. Dezember 2008), das <strong>Artikel 70 Absatz 3 des MwSt.-Gesetzes</strong> geändert hat. Danach müssen Bücher und Unterlagen, die in elektronischer Form vorliegen, auf Verlangen der Verwaltung „in lesbarer und unmittelbar verständlicher Form" oder nach anderen von der Verwaltung bestimmten technischen Modalitäten übermittelt werden. Die FAIA ist die von der AED gewählte Modalität.</p>

<h2>Wer muss eine FAIA-Datei erzeugen?</h2>

<p>Das ist der am häufigsten verzerrte Punkt. Nach der <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offiziellen FAQ der AED</a> setzt die Pflicht voraus, dass <strong>vier Bedingungen gleichzeitig erfüllt</strong> sind.</p>

<h3>Die vier kumulativen Bedingungen</h3>

<ol>
    <li>Dem <strong>normierten Kontenplan (PCN)</strong> unterliegen</li>
    <li><strong>Keine vereinfachte Regelung</strong> in Anspruch nehmen</li>
    <li>Einen <strong>Jahresumsatz von über 112 000 €</strong> erzielen</li>
    <li>Ein Volumen von <strong>etwa 500 Buchungstransaktionen</strong> jährlich überschreiten</li>
</ol>

<p>Fehlt auch nur eine dieser Bedingungen, sind Sie von der FAIA nicht betroffen. Die AED formuliert es in ihrer FAQ unmissverständlich:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ich erziele einen Umsatz von 1.000.000 € und habe nur 400 Transaktionen in meiner Buchhaltung. Bin ich verpflichtet, eine FAIA-Datei zu liefern? — <strong>Nein.</strong> Obwohl Ihr Umsatz die 112.000 € übersteigt, bleibt Ihr Transaktionsvolumen in Grenzen, in denen eine manuelle Prüfung rationeller ist."</p>
</blockquote>

<h3>Was eine „Transaktion" wirklich ist</h3>

<p>Vorsicht beim Zählen: Eine Transaktion <strong>ist keine Rechnung</strong>. Die AED definiert sie als eine <strong>vollständige Buchungskette</strong>. Ein Einkauf zerfällt beispielsweise in vier verbundene Buchungen — Aufwandskonto, Vorsteuer, Lieferantenkonto, Zahlung —, die zusammen <strong>eine einzige</strong> Transaktion bilden.</p>

<p>Wer seine 600 Rechnungen zählt und daraus schließt, die Schwelle zu überschreiten, misst also wahrscheinlich das Falsche.</p>

<h3>Wenn Sie dem PCN nicht unterliegen</h3>

<p>Dann entgehen Sie der eigentlichen FAIA-Pflicht, selbst bei hohem Umsatz und mehr als 500 Transaktionen. Artikel 70 gilt jedoch weiter: Die AED kann verlangen, dass Sie Ihre elektronischen Daten <strong>in einem abgegrenzten und strukturierten Format</strong> exportieren. Außerhalb der FAIA zu stehen entbindet nicht davon, seine Buchhaltung sauber ausgeben zu können.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Wichtig</p>
    <p class="text-amber-700">Die FAIA ist niemals unaufgefordert zu übermitteln, insbesondere <strong>nicht zusammen mit Ihrer MwSt.-Erklärung</strong>. Sie wird <strong>ausschließlich auf Verlangen</strong> eines mit der Prüfung Ihres Unternehmens betrauten AED-Bediensteten erzeugt.</p>
</div>

<h2>Was enthält die FAIA-Datei?</h2>

<p>Die FAIA-Datei ist in mehrere Abschnitte gegliedert:</p>

<h3>1. Allgemeine Angaben (Header)</h3>

<ul>
    <li>Identifikation des Unternehmens (Name, Anschrift, MwSt.-Nummer)</li>
    <li>Von der Datei abgedeckter Zeitraum</li>
    <li>Angaben zur verwendeten Software</li>
    <li>Datum und Uhrzeit der Erzeugung</li>
</ul>

<h3>2. Kontenplan (GeneralLedger)</h3>

<ul>
    <li>Liste aller verwendeten Buchhaltungskonten</li>
    <li>Kontenhierarchie</li>
    <li>Eröffnungs- und Schlusssalden</li>
</ul>

<h3>3. Kunden und Lieferanten (MasterFiles)</h3>

<ul>
    <li>Kundenstammdaten mit vollständigen Kontaktangaben</li>
    <li>Lieferantenstammdaten</li>
    <li>Innergemeinschaftliche MwSt.-Nummern</li>
</ul>

<h3>4. Buchungssätze (GeneralLedgerEntries)</h3>

<ul>
    <li>Alle Buchungen des Zeitraums, auch ohne unmittelbaren MwSt.-Bezug — der Export muss die gesamte Buchhaltung umfassen</li>
    <li>Buchungsjournale</li>
    <li>Referenzierte Belege</li>
</ul>

<h3>5. Rechnungen (SourceDocuments)</h3>

<ul>
    <li>Ausgestellte Ausgangsrechnungen</li>
    <li>Erhaltene Eingangsrechnungen</li>
    <li>Gutschriften und Stornorechnungen</li>
    <li>Positionsweise Aufschlüsselung mit MwSt.</li>
</ul>

<p>Ist Ihr Fakturierungssystem <strong>in die Buchhaltung integriert</strong>, sind die Quelldokumente systematisch mitzuliefern. Ist es das nicht, kann der AED-Bedienstete gezielt einzelne Quelldokumente anfordern.</p>

<h2>Technisches Format der FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Kodierung</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD-Schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, letzte von der AED veröffentlichte Aktualisierung im Juli 2020. Drei Schemata bestehen nebeneinander: <em>full</em>, <em>reduced version A</em> und <em>reduced version B</em>, je nach Buchführungsregime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Zeitraum</td>
            <td class="border border-gray-300 px-4 py-2">Ein vollständiges Geschäftsjahr, ausgerichtet am Kalenderjahr. Verkürzte Geschäftsjahre werden abgelehnt, und eine Datei darf nur einen Zeitraum abdecken: Eine Prüfung über drei Jahre erfordert drei Dateien</td>
        </tr>
    </tbody>
</table>

<h2>Wie erzeugt man eine konforme FAIA-Datei?</h2>

<h3>Option 1: Kompatible Rechnungssoftware</h3>

<p>Das ist die einfachste Lösung. Eine Software wie <strong>faktur.lu</strong> erzeugt automatisch eine konforme FAIA-Datei aus Ihren Fakturierungsdaten.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ FAIA-Export mit einem Klick bei faktur.lu</p>
    <p class="text-green-700">Unsere Software erzeugt eine nach dem offiziellen XSD-Schema validierte FAIA-Datei, bereit zur Übermittlung an die AED — ob Sie heute dazu verpflichtet sind oder die Schwellen morgen überschreiten.</p>
</div>

<h3>Option 2: Buchhaltungssoftware</h3>

<p>Professionelle Buchhaltungsprogramme (Sage, BOB usw.) bieten in der Regel ein FAIA-Exportmodul.</p>

<h3>Option 3: Individualentwicklung</h3>

<p>Für Großunternehmen mit proprietären Systemen kann eine spezifische Entwicklung nötig sein, um die Daten nach dem FAIA-Schema zu extrahieren und zu formatieren.</p>

<h2>Validierung der FAIA-Datei</h2>

<p>Validieren Sie Ihre Datei vor der Übermittlung:</p>

<ol>
    <li><strong>XSD-Validierung</strong>: prüfen, ob die Datei dem offiziellen XML-Schema entspricht</li>
    <li><strong>Kontrolle der Summen</strong>: sicherstellen, dass die Beträge stimmig sind</li>
    <li><strong>Prüfung der Referenzen</strong>: alle Kennungen (Kunden, Konten) müssen vorhanden sein</li>
</ol>

<p>Die AED ist hier eindeutig: <strong>Es wird kein Validierungswerkzeug bereitgestellt</strong>, und „nur das auf der Website der AED veröffentlichte Schema kann als Kontrollmechanismus dienen". Sie können also jeden beliebigen XML-Validator eines Drittanbieters nutzen (etwa den <a href="/de/validateur-faia">faktur.lu-Validator</a>), um die Konformität vorab zu prüfen.</p>

<h2>Fristen, Übermittlung und Sanktionen</h2>

<h3>Frist zur Erstellung</h3>

<p>Die AED veröffentlicht keine feste gesetzliche Frist. Wird eine FAIA-Datei im Rahmen einer Prüfung verlangt, legt <strong>der Prüfer die Frist im Einzelfall</strong> fest, je nach Komplexität der Anfrage.</p>

<h3>Übermittlungsmedium</h3>

<p>Die AED zeigt sich flexibel: Jeder marktübliche elektronische Datenträger wird akzeptiert — USB-Stick, externe Festplatte, CD-R oder DVD-R, E-Mail.</p>

<h3>Sanktionen bei Nichterfüllung</h3>

<p>Für tatsächlich verpflichtete Unternehmen kann die Weigerung oder Unfähigkeit, die Daten zu liefern, Folgendes nach sich ziehen:</p>

<ul>
    <li><strong>Verwaltungsbußgelder</strong></li>
    <li>Eine <strong>Schätzung von Amts wegen</strong> durch die Verwaltung</li>
    <li>Die <strong>Verwerfung der Buchhaltung</strong> als Beweismittel</li>
</ul>

<h2>Bewährte Praxis</h2>

<ol>
    <li><strong>Prüfen Sie zuerst, ob Sie betroffen sind</strong> — alle vier Bedingungen müssen erfüllt sein</li>
    <li><strong>Testen Sie Ihren FAIA-Export regelmäßig</strong>, nicht erst bei einer Prüfung</li>
    <li><strong>Archivieren Sie</strong> die erzeugten FAIA-Dateien für jedes Geschäftsjahr</li>
    <li><strong>Prüfen Sie die Stimmigkeit</strong> zwischen Rechnungen und Buchungen</li>
    <li><strong>Nutzen Sie zertifizierte</strong> oder getestete Software für den FAIA-Export</li>
</ol>

<h2>Die 4 häufigsten FAIA-Fehler</h2>

<ol>
    <li><strong>Nicht konforme Rechnungsnummerierung</strong> nach Artikel 63 LIVA Ziffer 3° (Lücken in der Sequenz oder Dubletten). Die Datei kann bei der Validierung zurückgewiesen werden.</li>
    <li><strong>Fehlende Pflichtfelder</strong>: MwSt.-Nummer des Kunden, vollständige Anschrift, Reverse-Charge-Vermerk beim innergemeinschaftlichen B2B.</li>
    <li><strong>Unstimmige Summen</strong> zwischen den Abschnitten — etwa eine Rechnungssumme, die vom ausgewiesenen Gesamtbetrag abweicht.</li>
    <li><strong>Falsches Datumsformat</strong>: Die FAIA verlangt die Norm ISO 8601 (JJJJ-MM-TT), nicht TT/MM/JJJJ.</li>
</ol>

<h2>Häufige Fragen</h2>

<h3>Muss man jedes Jahr eine FAIA einsenden?</h3>
<p>Nein. Die FAIA wird ausschließlich auf Verlangen der AED bei einer Prüfung erzeugt, und nur von den betroffenen Unternehmen. Sie wird nie zusammen mit der MwSt.-Erklärung eingereicht.</p>

<h3>Was passiert, wenn ich sie nicht liefern kann, obwohl ich dazu verpflichtet bin?</h3>
<p>Der Prüfer geht davon aus, dass Ihre Buchhaltung nicht standardgemäß geführt wird. Sie riskieren ein Verwaltungsbußgeld von 250 € bis 10 000 € je Verstoß (Art. 77 LIVA), eine Schätzung von Amts wegen und — bei Weigerung, die Unterlagen vorzulegen — eine Geldbuße von bis zu 25 000 € pro Verzugstag nach Verwarnung.</p>

<h3>Ist die FAIA bei MwSt.-Freigrenze Pflicht?</h3>
<p>In der Regel nein. Die Freigrenze (Art. 57bis LIVA, Umsatz ≤ 50 000 €) hält den Steuerpflichtigen unter der Schwelle von 112 000 €, und die vereinfachte Regelung ist ohnehin für sich genommen ein Ausschlussgrund.</p>

<h3>Wie erfahre ich vor einer Prüfung, ob meine Datei konform ist?</h3>
<p>Validieren Sie sie gegen das offizielle XSD-Schema. Der <a href="/de/validateur-faia">FAIA-Validator von faktur.lu</a> ist kostenlos und ohne Anmeldung: Er prüft die Konformität mit dem Schema 2.01, erkennt fehlende Felder, kontrolliert die Stimmigkeit der Summen und die Fortlaufendheit der Rechnungsnummern, ohne Ihre Daten zu speichern.</p>

<h3>Kann mein Buchhalter die FAIA für mich erzeugen?</h3>
<p>Ja. Ihr Treuhänder kann sie aus seinem eigenen Werkzeug erzeugen oder Ihre über ein Buchhalterportal mit Lesezugriff abrufen.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Offizielle FAIA-Seite</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – XSD-Schemata FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Anwendungsbereich und Ausschlüsse)</a></li>
</ul>

<h2>Fazit</h2>

<p>Die FAIA ist eine echte, aber gezielte Pflicht: Sie betrifft Unternehmen, die dem normierten Kontenplan und dem normalen Regime unterliegen, über 112 000 € Umsatz und etwa 500 Jahrestransaktionen hinaus. Viele Selbstständige und kleine Strukturen sind nicht dazu verpflichtet.</p>

<p>Sind Sie betroffen — oder führt Ihr Wachstum Sie dorthin —, erspart Ihnen eine Rechnungssoftware, die die Datei erzeugen kann, die Entdeckung des Problems am Prüfungstag. faktur.lu integriert den FAIA-Export nativ, validiert nach dem offiziellen Schema der AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/steuerpruefung-luxemburg-vorbereiten" class="text-primary-500 hover:text-primary-600 text-sm">Steuerprüfung →</a></li><li><a href="/de/blog/rechnungsarchivierung-luxemburg-gesetzliche-dauer-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivierung von Rechnungen →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen und Verfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'freiberufler-luxemburg-konform-fakturieren',
                'locale' => 'de',
                'translation_key' => 'freelance-luxembourg-facturer-conformite',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Sie starten als Freiberufler in Luxemburg? Die Rechnungsstellung ist ein entscheidender Teil Ihrer Tätigkeit. Dieser Leitfaden zeigt Ihnen, wie Sie 2026 konforme Rechnungen erstellen und Ihre steuerlichen Pflichten erfüllen (aktualisiert nach der Anhebung der MwSt.-Freigrenze auf 50 000 €).</p>

<h2>Der Freiberufler-Status in Luxemburg</h2>

<p>In Luxemburg übt der Freiberufler (oder Selbstständige) seine Tätigkeit in der Regel unter einem dieser Status aus:</p>

<ul>
    <li><strong>Einzelunternehmen</strong>: der gängigste Status für den Start</li>
    <li><strong>Einpersonengesellschaft (SARL-S)</strong>: eine vereinfachte Gesellschaft mit beschränkter Haftung</li>
    <li><strong>Freier Beruf</strong>: für bestimmte reglementierte Tätigkeiten</li>
</ul>

<p>Unabhängig vom Status gelten für Sie dieselben Regeln der Rechnungsstellung.</p>

<h2>Sich für die MwSt. registrieren</h2>

<p>Bevor Sie mit dem Fakturieren beginnen (oberhalb der Freigrenze, siehe unten), müssen Sie eine <strong>innergemeinschaftliche MwSt.-Nummer</strong> bei der <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Registrierungs-, Domänen- und MwSt.-Verwaltung)</a> beantragen.</p>

<h3>Registrierungsverfahren</h3>

<ol>
    <li>Eine <strong>Niederlassungsgenehmigung</strong> beim Wirtschaftsministerium einholen</li>
    <li>Sich gegebenenfalls im <strong>Handelsregister (RCS)</strong> eintragen</li>
    <li>Die <strong>MwSt.-Registrierung</strong> über <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> beantragen</li>
    <li>Ihre Nummer im Format <strong>LU + 8 Ziffern</strong> erhalten</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Achtung</p>
    <p class="text-amber-700">Unterhalb der Freigrenze (Umsatz ≤ 50 000 € netto, Art. 57bis LIVA seit 2025) ist die MwSt.-Registrierung nicht verpflichtend. Sie fakturieren dann ohne MwSt. mit dem Vermerk „MwSt. nicht anwendbar – Artikel 57bis". <strong>Kehrseite:</strong> Sie können die MwSt. auf Ihre betrieblichen Einkäufe (Ausrüstung, Software, Unteraufträge) nicht abziehen. Wer investiert, für den kann die Freigrenze teurer sein als sie einbringt.</p>
</div>

<h2>Pflichtangaben auf Ihren Rechnungen</h2>

<p>Nach <strong>Artikel 63 LIVA</strong> müssen Ihre Rechnungen als Freiberufler enthalten:</p>

<h3>Ihre Angaben</h3>

<ul>
    <li><strong>Vollständiger Name</strong> oder Firmenbezeichnung</li>
    <li><strong>Geschäftsadresse</strong> in Luxemburg</li>
    <li><strong>MwSt.-Nummer</strong> (LU12345678)</li>
    <li>Gegebenenfalls Ihre RCS-Nummer (verpflichtend für eingetragene Kaufleute/Gesellschaften)</li>
</ul>

<h3>Angaben zum Kunden</h3>

<ul>
    <li>Name oder Firmenbezeichnung</li>
    <li>Vollständige Anschrift</li>
    <li>MwSt.-Nummer (verpflichtend bei gewerblichen Kunden innerhalb der EU)</li>
</ul>

<h3>Einzelheiten der Leistung</h3>

<ul>
    <li><strong>Eindeutige, fortlaufende Rechnungsnummer</strong> (Artikel 63 LIVA, Ziffer 3°)</li>
    <li><strong>Ausstellungsdatum</strong></li>
    <li><strong>Leistungsdatum</strong> (Steuertatbestand)</li>
    <li><strong>Ausführliche Beschreibung</strong> der erbrachten Leistungen</li>
    <li><strong>Anzahl der Stunden oder Tage</strong> (empfohlen)</li>
    <li><strong>Einzelpreis netto</strong></li>
    <li><strong>Nettobetrag, MwSt. und Bruttobetrag</strong></li>
    <li><strong>Anwendbarer MwSt.-Satz</strong></li>
</ul>

<h2>Welchen MwSt.-Satz anwenden?</h2>

<p>Als Freiberufler wenden Sie für die meisten Dienstleistungen in der Regel den <strong>Normalsatz von 17 %</strong> an.</p>

<h3>Sonderfälle</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anwendbare MwSt.</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Gewerblicher Kunde in Luxemburg</td><td class="border border-gray-300 px-4 py-2">MwSt. 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Gewerblicher Kunde in der EU (B2B innergemeinschaftlich)</td><td class="border border-gray-300 px-4 py-2">0 % (Reverse Charge, Art. 17 LIVA + Art. 196 Richtlinie)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Kunde außerhalb der EU</td><td class="border border-gray-300 px-4 py-2">0 % (bei den meisten geistigen Leistungen außerhalb des luxemburgischen MwSt.-Bereichs)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Privatkunde in Luxemburg</td><td class="border border-gray-300 px-4 py-2">MwSt. 17 %</td></tr>
    </tbody>
</table>

<h2>Einem Kunden im Ausland fakturieren</h2>

<h3>Gewerblicher Kunde in der EU</h3>

<p>Ist Ihr Kunde ein Unternehmen in einem anderen EU-Land:</p>

<ol>
    <li><strong>Prüfen Sie seine MwSt.-Nummer</strong> im <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>-System</li>
    <li><strong>Berechnen Sie keine MwSt.</strong> auf Ihrer Rechnung (Ort der Besteuerung beim Leistungsempfänger, Art. 17 LIVA)</li>
    <li><strong>Fügen Sie den Vermerk hinzu</strong>: <em>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</em></li>
    <li><strong>Geben Sie die MwSt.-Nummer</strong> des Kunden auf der Rechnung an</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Pflichtangabe – korrekte Rechtsgrundlage</p>
    <p>Der kanonische Vermerk für den innergemeinschaftlichen B2B-Reverse-Charge lautet <strong>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</strong>. Artikel 196 benennt den Leistungsempfänger als Steuerschuldner (Art. 226 §11bis der Richtlinie). Nicht zu verwechseln mit Artikel 44 der Richtlinie (Ort der Besteuerung) noch mit Artikel 44 des luxemburgischen Gesetzes von 1979 (sektorielle Befreiungen, ohne Zusammenhang).</p>
</div>

<h3>Kunde außerhalb der EU</h3>

<p>Bei Kunden außerhalb der Europäischen Union fallen die gängigen geistigen Leistungen eines Freiberuflers (Beratung, IT, Werbung, Übersetzung) nicht in den luxemburgischen MwSt.-Bereich. Vermerken Sie „MwSt. nicht anwendbar – Leistungsort außerhalb der EU".</p>

<h2>Die Nummerierung Ihrer Rechnungen (Artikel 63 LIVA, Ziffer 3°)</h2>

<p>Ihre Rechnungen müssen einer <strong>chronologischen und lückenlosen Nummerierung</strong> folgen:</p>

<ul>
    <li>Keine Lücke in der Reihenfolge</li>
    <li>Freies, aber einheitliches Format (z. B. 2026-001, 2026-002…)</li>
    <li>Jährlicher Neustart zulässig</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Nutzen Sie eine Rechnungssoftware wie faktur.lu, um automatisch konforme Nummern zu erzeugen und Fehler zu vermeiden.</p>
</div>

<h2>Ihre MwSt.-Erklärungen verwalten</h2>

<p>Nach der Registrierung reichen Sie Erklärungen ein, deren Rhythmus sich nach Ihrem Nettoumsatz richtet. <strong>Die AED bestimmt Ihr Regime</strong> – Sie wählen es nicht selbst.</p>

<ul>
    <li><strong>Umsatz &lt; 112 000 €/Jahr</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €/Jahr</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz &gt; 620 000 €/Jahr</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Die Jahreserklärung ersetzt die periodischen Erklärungen nicht</p>
    <p class="text-amber-700">Das ist der teuerste Irrtum auf dieser Seite. Wer vierteljährlich oder monatlich erklärt, muss <strong>zusätzlich</strong> eine <strong>zusammenfassende Jahreserklärung</strong> einreichen, und zwar vor dem <strong>1. Mai des Folgejahres</strong>. Sie zu vergessen löst Verspätungszuschläge aus, obwohl Sie Ihre MwSt. unterjährig vollständig gezahlt haben.</p>
</div>

<p>Die periodischen Erklärungen sind vor dem 15. des auf den Zeitraum folgenden Monats einzureichen. Alles läuft online über <strong>eCDF (eTVA)</strong>.</p>

<h2>Die FAIA-Datei für Freiberufler</h2>

<p>Wenn Sie eine Rechnungssoftware nutzen, müssen Sie auf Anfrage der AED eine <strong>FAIA-Datei</strong> erzeugen können – unter Bedingungen. Die AED-FAQ nimmt Steuerpflichtige mit einem Nettoumsatz ≤ 112 000 € aus, sodass sehr kleine Freiberufler in der Freigrenzenregelung in der Regel keine FAIA-Datei vorlegen müssen. Mit Ihrem Treuhänder je nach Regime zu bestätigen.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu erzeugt Ihre FAIA-Datei</p>
    <p class="text-green-700">Unsere Software erstellt automatisch eine konforme FAIA-2.01-Datei, bereit für jede Steuerprüfung der AED.</p>
</div>

<h2>Tipps für Freiberufler am Anfang</h2>

<ol>
    <li><strong>Nutzen Sie von Anfang an eine geeignete Software</strong>, um Fehler zu vermeiden</li>
    <li><strong>Bewahren Sie alle Rechnungen</strong> (ausgestellte und erhaltene) 10 Jahre auf (Art. 16 Handelsgesetzbuch + Art. 65 LIVA)</li>
    <li><strong>Trennen Sie</strong> private und geschäftliche Konten</li>
    <li><strong>Fakturieren Sie zügig</strong> (spätestens am 15. des auf die Leistung folgenden Monats, Art. 63 LIVA)</li>
    <li><strong>Prüfen Sie die MwSt.-Nummern</strong> Ihrer EU-Kunden vor der Fakturierung (VIES)</li>
    <li><strong>Notieren Sie beide Fristen</strong>: die periodischen Erklärungen und die Jahreserklärung</li>
    <li><strong>Ziehen Sie einen Buchhalter hinzu</strong> bei komplexen Fragen</li>
</ol>

<h2>Häufige Fehler, die es zu vermeiden gilt</h2>

<ul>
    <li>❌ Ohne MwSt.-Nummer fakturieren (sofern Sie steuerpflichtig sind)</li>
    <li>❌ Pflichtangaben vergessen (Art. 63 LIVA)</li>
    <li>❌ Einen falschen MwSt.-Satz anwenden</li>
    <li>❌ Nicht fortlaufende Nummerierung</li>
    <li>❌ Nach dem 15. des auf die Leistung folgenden Monats fakturieren</li>
    <li>❌ Die MwSt.-Nummern von EU-Kunden nicht prüfen (VIES)</li>
    <li>❌ <strong>Glauben, Vierteljahreserklärungen ersparten die Jahreserklärung</strong></li>
    <li>❌ „Artikel 44 der Richtlinie" (Ort der Besteuerung) mit „Artikel 196 der Richtlinie" (Reverse-Charge-Vermerk) verwechseln</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen, Sätze und MwSt.-Verfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – MwSt.-Erklärungen und Periodizität</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Freigrenzenregelung (Art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtangaben auf Rechnungen</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung innergemeinschaftlicher MwSt.-Nummern</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<h2>Fazit</h2>

<p>Die Rechnungsstellung als Freiberufler in Luxemburg ist nicht kompliziert, wenn Sie die Regeln kennen. Mit einer geeigneten Rechnungssoftware wie faktur.lu erstellen Sie konforme Rechnungen in wenigen Klicks, mit allen Pflichtangaben und automatisch korrekten MwSt.-Sätzen. Konzentrieren Sie sich auf Ihr Handwerk – um die Konformität kümmern wir uns!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/5-haeufige-fehler-freelancer-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">5 häufige Fehler auf einer Freiberufler-Rechnung →</a></li><li><a href="/de/blog/pflichtangaben-rechnung-luxemburg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li><li><a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg (50 000 €) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'mwst-luxemburg-saetze-berechnung-pflichten',
                'locale' => 'de',
                'translation_key' => 'tva-luxembourg-taux-calcul-obligations',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Die MwSt. (Mehrwertsteuer) ist ein zentrales Element der luxemburgischen Besteuerung. Das Verständnis der verschiedenen Sätze, deren korrekte Anwendung und die Einhaltung der Erklärungspflichten ist für jedes Unternehmen unerlässlich.</p>

<h2>Die MwSt.-Sätze in Luxemburg 2026</h2>

<p>Luxemburg wendet <strong>vier MwSt.-Sätze</strong> an, die zu den niedrigsten in der Europäischen Union gehören:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Satz</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Bezeichnung</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anwendung</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">17%</td>
            <td class="border border-gray-300 px-4 py-2">Normalsatz</td>
            <td class="border border-gray-300 px-4 py-2">Mehrheit der Waren und Dienstleistungen</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">14%</td>
            <td class="border border-gray-300 px-4 py-2">Ermäßigter Satz</td>
            <td class="border border-gray-300 px-4 py-2">Weine, feste Brennstoffe, Werbedrucke</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">8%</td>
            <td class="border border-gray-300 px-4 py-2">Reduzierter Satz</td>
            <td class="border border-gray-300 px-4 py-2">Gas, Strom, Friseur</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">3%</td>
            <td class="border border-gray-300 px-4 py-2">Stark ermäßigter Satz</td>
            <td class="border border-gray-300 px-4 py-2">Lebensmittel, Bücher, Medikamente, Verkehr</td>
        </tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold text-blue-800">ℹ️ Wussten Sie?</p>
    <p class="text-blue-700">Der Normalsatz von 17% in Luxemburg ist der niedrigste in der Europäischen Union, wo der Durchschnitt bei etwa 21% liegt.</p>
</div>

<h2>Detail der Sätze nach Kategorie</h2>

<h3>Stark ermäßigter Satz von 3%</h3>

<ul>
    <li>Lebensmittel (außer Alkohol und Gastronomie)</li>
    <li>Bücher, Zeitungen und Zeitschriften</li>
    <li>Medikamente</li>
    <li>Personenbeförderung</li>
    <li>Hotelunterbringung</li>
    <li>Eintritte zu kulturellen und sportlichen Veranstaltungen</li>
    <li>Medizinische und zahnärztliche Behandlungen (nicht befreit)</li>
</ul>

<h3>Reduzierter Satz von 8%</h3>

<ul>
    <li>Lieferung von Erdgas und Strom</li>
    <li>Friseurdienstleistungen</li>
    <li>Fensterreinigung</li>
    <li>Kleine Reparaturdienste (Fahrräder, Schuhe, Kleidung)</li>
</ul>

<h3>Ermäßigter Satz von 14%</h3>

<ul>
    <li>Weine (weniger als 13% Alkohol)</li>
    <li>Feste mineralische Brennstoffe</li>
    <li>Heizöl</li>
    <li>Bestimmte Werbedrucke</li>
</ul>

<h3>Normalsatz von 17%</h3>

<p>Alle Waren und Dienstleistungen, die keinen ermäßigten Satz genießen, unterliegen dem Normalsatz von 17%.</p>

<h2>Von der MwSt. befreite Umsätze</h2>

<p>Bestimmte Umsätze sind in Luxemburg <strong>von der MwSt. befreit</strong>:</p>

<ul>
    <li>Medizinische und paramedizinische Dienstleistungen</li>
    <li>Bildungsdienstleistungen</li>
    <li>Bank- und Finanzgeschäfte</li>
    <li>Versicherungsgeschäfte</li>
    <li>Immobilienvermietung (außer bei Option)</li>
    <li>Innergemeinschaftliche Lieferungen (unter Bedingungen)</li>
    <li>Exporte außerhalb der EU</li>
</ul>

<h2>MwSt.-Berechnung</h2>

<h3>MwSt. aus Nettobetrag berechnen</h3>

<p>Um den Bruttobetrag aus dem Nettobetrag zu berechnen:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Bruttobetrag = Nettobetrag × (1 + MwSt.-Satz)</p>
    <p class="mt-2 text-sm text-gray-600">Beispiel: 100€ netto × 1,17 = 117€ brutto</p>
</div>

<h3>Nettobetrag aus Bruttobetrag berechnen</h3>

<p>Um den Nettobetrag aus dem Bruttobetrag zu ermitteln:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Nettobetrag = Bruttobetrag ÷ (1 + MwSt.-Satz)</p>
    <p class="mt-2 text-sm text-gray-600">Beispiel: 117€ brutto ÷ 1,17 = 100€ netto</p>
</div>

<h2>Erklärungspflichten</h2>

<p>Die Periodizität richtet sich nach Ihrem Nettoumsatz. <strong>Die AED bestimmt Ihr Regime</strong> — Sie wählen es nicht selbst.</p>

<ul>
    <li><strong>Umsatz unter 112 000 €</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz über 620 000 €</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Die Jahreserklärung ersetzt die periodischen Erklärungen nicht</p>
    <p class="text-amber-700">Wer vierteljährlich oder monatlich erklärt, muss <strong>zusätzlich</strong> eine zusammenfassende Jahreserklärung einreichen, und zwar vor dem <strong>1. Mai</strong> des Folgejahres. Sie zu vergessen löst Verspätungszuschläge aus, obwohl Sie Ihre MwSt. unterjährig vollständig gezahlt haben.</p>
</div>

<p>Die periodischen Erklärungen sind vor dem 15. des Folgemonats online über <strong>eCDF</strong> einzureichen. Die Zahlung begleitet die Erklärung; bei einem MwSt.-Guthaben kann eine Erstattung beantragt werden.</p>
<h2>Innergemeinschaftliche MwSt.</h2>

<h3>Verkäufe an EU-Unternehmen (B2B)</h3>

<p>Lieferungen von Waren und Erbringung von Dienstleistungen an Steuerpflichtige in anderen EU-Ländern sind <strong>von der luxemburgischen MwSt. befreit</strong>. Der Kunde führt die MwSt. in seinem Land ab (Reverse Charge).</p>

<p><strong>Bedingungen:</strong></p>
<ul>
    <li>Der Kunde muss eine gültige innergemeinschaftliche USt-IdNr. haben</li>
    <li>Diese Nummer muss auf der Rechnung erscheinen</li>
    <li>Der Hinweis "MwSt.-Befreiung - Artikel 43 Absatz 1 k) des MwSt.-Gesetzes" muss erscheinen</li>
</ul>

<h3>Verkäufe an EU-Privatpersonen (B2C)</h3>

<p>Eine <strong>einheitliche Schwelle von 10 000 € pro Jahr</strong> regelt diese Verkäufe. Darunter berechnen Sie die luxemburgische MwSt.; darüber die MwSt. des Kundenlandes, gemeldet über den einheitlichen Schalter <strong>OSS</strong> oder durch Registrierung in jedem Land.</p>

<p><strong>Achtung:</strong> Diese Schwelle gilt <strong>gemeinsam</strong> für Fernverkäufe von Waren <em>und</em> für elektronische, Telekommunikations- und Rundfunkdienstleistungen — über alle EU-Länder hinweg, ohne Luxemburg. Zu verfolgen ist also die <strong>Summe</strong> Ihrer europäischen B2C-Umsätze, nicht jede Kategorie einzeln. Der Umsatz, der die Schwelle überschreitet, ist bereits im Land des Kunden steuerpflichtig.</p>
<h2>Die innergemeinschaftliche USt-IdNr.</h2>

<p>Die luxemburgische USt-IdNr. hat das Format <strong>LU + 8 Ziffern</strong> (z.B.: LU12345678).</p>

<p>Diese Nummer muss erscheinen auf:</p>
<ul>
    <li>Allen Ihren Rechnungen</li>
    <li>Ihren MwSt.-Erklärungen</li>
    <li>Ihren Intrastat-Meldungen (DEB)</li>
</ul>

<h2>Vorsteuerabzug</h2>

<p>Als Steuerpflichtiger können Sie die <strong>MwSt. abziehen</strong>, die Sie auf Ihre geschäftlichen Einkäufe gezahlt haben. Dafür:</p>

<ul>
    <li>Sie müssen eine <strong>konforme Rechnung</strong> besitzen</li>
    <li>Der Kauf muss mit Ihrer <strong>beruflichen Tätigkeit</strong> verbunden sein</li>
    <li>Die MwSt. muss <strong>korrekt ausgewiesen</strong> sein auf der Rechnung</li>
</ul>

<h2>Praktische Tipps</h2>

<ol>
    <li><strong>Prüfen Sie immer den anwendbaren Satz</strong> vor der Rechnungsstellung</li>
    <li><strong>Validieren Sie die USt-IdNr.</strong> Ihrer EU-Kunden auf der VIES-Website</li>
    <li><strong>Bewahren Sie Ihre Rechnungen 10 Jahre</strong> auf, um Ihre Abzüge zu rechtfertigen</li>
    <li><strong>Verwenden Sie geeignete Software</strong>, um Rechenfehler zu vermeiden</li>
    <li><strong>Planen Sie Ihre Erklärungen voraus</strong>, um Verspätungsstrafen zu vermeiden</li>
</ol>

<h2>Häufige Sonderfälle</h2>

<h3>Gastronomie: 3 % und 17 % auf derselben Rechnung</h3>
<p>Vor Ort servierte Speisen unterliegen <strong>3 %</strong>, <strong>alkoholische Getränke</strong> jedoch <strong>17 %</strong>. Beide Sätze stehen also auf derselben Rechnung, und die Aufteilung muss ersichtlich sein.</p>

<h3>Hotellerie: 3 % unabhängig von der Kategorie</h3>
<p>Anders als in anderen Ländern wendet Luxemburg einheitlich <strong>3 %</strong> auf alle Übernachtungen an, vom Gasthof bis zum Luxushotel. Nebenleistungen (Spa, hauseigenes Restaurant) folgen ihrer eigenen Regelung.</p>

<h3>E-Books: 3 %</h3>
<p>Digitale Bücher genießen denselben Satz wie gedruckte, also <strong>3 %</strong>. Streaming-Abonnements für Video oder Musik bleiben dagegen bei <strong>17 %</strong>: das sind digitale Dienstleistungen, keine Bücher.</p>

<h3>Renovierungsarbeiten: die Bedingung, die alle vergessen</h3>
<p>Renovierungsarbeiten an einer als Hauptwohnsitz genutzten Wohnung können den stark ermäßigten Satz von <strong>3 %</strong> genießen, innerhalb der durch großherzogliche Verordnung festgelegten Grenzen und Bedingungen (Alter des Gebäudes, steuerlicher Höchstbetrag je Wohnung).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Die Genehmigung ist VOR den Arbeiten zu beantragen</p>
    <p class="text-red-700"><strong>Artikel 65bis des MwSt.-Gesetzes</strong> ist eindeutig: Wer solche Arbeiten ausführt, „muss bei der Verwaltung die Genehmigung für die Anwendung des stark ermäßigten Satzes beantragen", und dieser Antrag „ist […] <strong>vor der Ausführung der Arbeiten</strong> zu stellen". Erst bauen und dann die 3 % verlangen funktioniert nicht. Bei einer Renovierung geht der Unterschied zwischen 3 % und 17 % in die Tausende.</p>
</div>

<h2>Den falschen Satz anwenden: die Folgen</h2>

<ul>
    <li><strong>Zu niedriger Satz</strong>: Nachforderung der AED, Verzugszinsen und Verwaltungsbußgeld von <strong>250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
    <li><strong>Verstoß, der dem Fiskus Einnahmen entzogen hat</strong>: Geldbuße von <strong>10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze</li>
    <li><strong>Zu hoher Satz</strong>: Der Kunde kann Erstattung verlangen, und Sie müssen gegenüber der AED berichtigen</li>
    <li><strong>Ablehnung des Vorsteuerabzugs beim Kunden</strong>: Ist der Satz offensichtlich falsch, kann dem Kunden der Abzug versagt werden</li>
    <li><strong>Vorsätzlicher Betrug</strong>: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt. und Freiheitsstrafe von einem Monat bis fünf Jahren (Art. 80 LIVA)</li>
</ul>

<h2>Häufige Fragen</h2>

<h3>Liegt der Normalsatz 2026 wirklich bei 17 %?</h3>
<p>Ja. Der Satz war 2023 vorübergehend auf 16 % gesenkt worden; zum 1. Januar 2024 kehrte er auf <strong>17 %</strong> zurück und ist seither unverändert.</p>

<h3>Welcher Satz gilt, wenn meine Rechnung einen Satzwechsel überspannt?</h3>
<p>Maßgeblich ist das Datum des <strong>Steuertatbestands</strong> — also der Lieferung oder Leistungserbringung, nicht das Ausstellungs- oder Zahlungsdatum.</p>

<h3>Wie belege ich einen Sondersatz bei einer Prüfung?</h3>
<p>Bewahren Sie auf, was den Umsatz der Kategorie zuordnet: die genaue Art des Gegenstands oder der Leistung und, bei Renovierungen, <strong>die vor Baubeginn erteilte Genehmigung</strong>. Ohne sie werden die 3 % verweigert, selbst wenn die Arbeiten inhaltlich begünstigt wären.</p>

<h3>Ist meine Tätigkeit befreit?</h3>
<p>Die Befreiungen sind im Gesetz abschließend aufgezählt und werden nicht vermutet. Klären Sie es im Zweifel mit Ihrem Treuhänder, bevor Sie die erste Rechnung stellen — eine zu Unrecht angewandte Befreiung lässt sich schlecht nachträglich heilen.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Sätze, Anhänge A und B, Artikel 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – MwSt.-Erklärungen und Periodizität</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
</ul>
<h2>Fazit</h2>

<p>Die MwSt.-Verwaltung in Luxemburg erfordert gute Kenntnisse der anwendbaren Sätze und Erklärungspflichten. Mit einer Rechnungssoftware wie faktur.lu profitieren Sie von der automatischen Anwendung der richtigen Sätze und gesetzeskonformen Rechnungen.</p>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'pflichtangaben-rechnung-luxemburg',
                'locale' => 'de',
                'translation_key' => 'mentions-obligatoires-facture-luxembourg',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurz gefasst</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Die Pflichtangaben ergeben sich aus <strong>Artikel 63 LIVA</strong>: Identität und Anschrift der Parteien, MwSt.-Nummern, Datum, <strong>eindeutige fortlaufende Nummer</strong>, Bezeichnung, Nettobasis, Satz, MwSt.-Betrag, Bruttobetrag.</li><li>Eine <strong>nicht konforme</strong> Rechnung kann vom Kunden zurückgewiesen werden und ein <strong>AED-Bußgeld von 250 € bis 10 000 €</strong> nach sich ziehen (Art. 77 LIVA) — oder <strong>10 % bis 50 % der betroffenen MwSt.</strong>, wenn dem Fiskus Einnahmen entgangen sind.</li><li>Bedingte Angaben: <strong>Reverse Charge</strong> (Art. 196 Richtlinie), Freigrenze, Befreiungen.</li><li><strong>Aufbewahrung 10 Jahre</strong> für Rechnungen und Buchhaltungsunterlagen.</li></ul></div>
<p class="lead">Eine nicht konforme Rechnung kann von Ihrem Kunden zurückgewiesen werden und Sie AED-Bußgeldern aussetzen. Hier ist die vollständige Checkliste der Pflichtangaben für einwandfreie luxemburgische Rechnungen — auf Grundlage von <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 LIVA</a> und der von der AED veröffentlichten Liste.</p>

<h2>Checkliste der Pflichtangaben</h2>

<p><strong>Artikel 63 LIVA</strong> (geändertes Gesetz vom 12. Februar 1979) und die Liste der AED verlangen Folgendes:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zum Aussteller</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollständiger Name oder Firmenbezeichnung</strong></li>
        <li>☐ <strong>Vollständige Anschrift</strong> des Sitzes</li>
        <li>☐ <strong>Innergemeinschaftliche MwSt.-Nummer</strong> (Format LU + 8 Ziffern)</li>
        <li>☐ <strong>RCS-Nummer</strong> für eingetragene Kaufleute und Gesellschaften — eine Pflicht aus dem <em>Recht des Handels- und Gesellschaftsregisters</em>, nicht aus dem MwSt.-Gesetz</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zum Kunden</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollständiger Name oder Firmenbezeichnung</strong></li>
        <li>☐ <strong>Vollständige Anschrift</strong></li>
        <li>☐ <strong>MwSt.-Nummer</strong> (verpflichtend bei innergemeinschaftlichem B2B - über <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> geprüft)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Angaben zur Rechnung</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Eindeutige, fortlaufende Rechnungsnummer</strong> (Art. 63 LIVA, Ziffer 3°)</li>
        <li>☐ <strong>Ausstellungsdatum</strong> der Rechnung</li>
        <li>☐ <strong>Liefer- oder Leistungsdatum</strong> — gesetzlich gefordert, <em>wenn es abweicht</em> vom Ausstellungsdatum (Art. 226 §7 der Richtlinie). Es systematisch anzugeben bleibt gute Praxis: es beseitigt bei einer Prüfung jede Unklarheit über den Steuertatbestand</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Einzelheiten zu Waren und Leistungen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Klare, ausführliche Beschreibung</strong></li>
        <li>☐ <strong>Gelieferte oder erbrachte Menge</strong></li>
        <li>☐ <strong>Nettoeinzelpreis</strong></li>
        <li>☐ <strong>Etwaige Nachlässe oder Rabatte</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ MwSt.-Angaben</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Anwendbarer MwSt.-Satz</strong> je Position (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
        <li>☐ <strong>MwSt.-Betrag</strong> je Satz</li>
        <li>☐ <strong>Bemessungsgrundlage</strong> (Nettobetrag) je MwSt.-Satz</li>
        <li>☐ <strong>Befreiungs- oder Reverse-Charge-Vermerk</strong>, falls einschlägig (siehe Tabelle unten)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Summen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nettosumme</strong></li>
        <li>☐ <strong>MwSt.-Summe</strong></li>
        <li>☐ <strong>Bruttosumme</strong></li>
    </ul>
</div>

<h2>Bedingte Angaben je nach Fall</h2>

<p>Je nach Art des Umsatzes muss ein bestimmter Vermerk auf der Rechnung stehen. Hier die kodifizierten Formulierungen:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Anzubringender Vermerk</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Rechtsgrundlage</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">B2B-Dienstleistung innergemeinschaftlich (Empfänger in einem anderen Mitgliedstaat)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (Ort) + Art. 196 Richtlinie (Steuerschuldner) + Art. 226 §11bis (Vermerk)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Innergemeinschaftliche B2B-Warenlieferung</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt.-Befreiung – Artikel 138 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Ausfuhr außerhalb der EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt.-Befreiung – Artikel 146 der Richtlinie 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">MwSt.-Freigrenze (Kleinunternehmen)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (Schwelle 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Nicht zu verwechseln</p>
    <p>Für den innergemeinschaftlichen B2B-Reverse-Charge übernehmen viele Vorlagen den Verweis auf „Artikel 44 der Richtlinie 2006/112/EG". Artikel 44 bestimmt den <strong>Ort der Besteuerung</strong> (beim Empfänger), der kodifizierte Pflichtvermerk verweist jedoch auf <strong>Artikel 196</strong> (der den Empfänger als Steuerschuldner benennt). Bevorzugen Sie den Vermerk zu Artikel 196.</p>
</div>

<h3>Sonderfälle der Fakturierung</h3>

<p><strong>Anzahlungsrechnung</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Anzahlung auf Bestellung Nr. [Referenz] vom [Datum]"</p>
</div>

<p><strong>Gutschrift (Stornorechnung)</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Gutschrift zu Rechnung Nr. [Nummer] vom [Datum]"</p>
</div>

<h2>Die Nummerierung der Rechnungen (Art. 63 LIVA, Ziffer 3°)</h2>

<p>Die Nummerierung muss diese Regeln einhalten:</p>

<ul>
    <li><strong>Eindeutig</strong>: jede Rechnung hat eine eigene Nummer</li>
    <li><strong>Chronologisch</strong>: die Nummern folgen der Reihenfolge der Ausstellung</li>
    <li><strong>Lückenlos</strong>: keine Lücke in der Sequenz</li>
    <li><strong>Nicht wiederverwendbar</strong>: eine Nummer darf nur einmal vergeben werden</li>
</ul>

<h3>Beispiele zulässiger Formate</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Beispiel</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Jahr + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Präfix + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Einfache Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Zahlungsbedingungen</h2>

<p>Auch wenn Artikel 63 LIVA sie nicht verlangt, sind diese Angaben <strong>dringend zu empfehlen</strong>:</p>

<ul>
    <li><strong>Zahlungsfrist</strong> (standardmäßig: 30 Tage nach Eingang, geändertes Gesetz vom 18. April 2004)</li>
    <li><strong>Fälligkeitsdatum</strong></li>
    <li><strong>Bankverbindung</strong> (IBAN, BIC)</li>
    <li><strong>Verzugszinsen</strong> zwischen Unternehmern — EZB-Referenzsatz + 8 Punkte, also <strong>10,15 % im 1. Halbjahr 2026</strong>, halbjährlich angepasst — und <strong>Pauschale von 40 €</strong></li>
</ul>

<h2>Aufbewahrung der Rechnungen</h2>

<p>Sie müssen Ihre Rechnungen (ausgestellte und erhaltene) <strong>10 Jahre</strong> ab Abschluss des Geschäftsjahres aufbewahren — Artikel 16 des Handelsgesetzbuchs und Artikel 65 LIVA. In Papierform oder elektronisch (PDF/A mit Integritätsgarantien). Siehe die <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Buchführungspflichten auf Guichet.lu</a>.</p>

<h2>Folgen einer nicht konformen Rechnung</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Bestehende Risiken</p>
    <ul class="text-red-700 mt-2">
        <li>Ablehnung des Vorsteuerabzugs durch Ihren Kunden</li>
        <li><strong>Verwaltungsbußgeld von 250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA)</li>
        <li>Hat der Verstoß zu einer Nichtzahlung von MwSt. oder einer unrechtmäßigen Erstattung geführt: <strong>Geldbuße von 10 % bis 50 % der betroffenen MwSt.</strong> — anteilig, also ohne Obergrenze</li>
        <li>Bei Weigerung, Rechnungen und Buchhaltungsunterlagen bei einer Prüfung vorzulegen: <strong>bis zu 25 000 € pro Verzugstag</strong>, nach Verwarnung</li>
        <li>Bei schwerer Steuerhinterziehung oder Steuerbetrug: Geldstrafe von 25 000 € bis zum Zehnfachen der MwSt., Freiheitsstrafe von einem Monat bis fünf Jahren und Aberkennung der bürgerlichen Rechte für 5 bis 10 Jahre (Art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Die Obergrenze von 10 000 € ist nicht das größte Risiko</p>
    <p>Wehtun tut die anteilige Geldbuße. Bei einem Streit über 80 000 € MwSt. bedeuten 10 bis 50 % zwischen 8 000 und 40 000 € — unabhängig davon, wie viele formale Verstöße festgestellt werden.</p>
</div>

<h2>Beispiel einer konformen Rechnung</h2>

<p>Hier die wesentlichen Bestandteile einer konformen Rechnung:</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">Ihre Gesellschaft SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">RECHNUNG</p>
            <p>N° 2026-0042</p>
            <p>Datum: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Rechnung an:</p>
        <p>Kunde Unternehmen SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Beschreibung</th>
                <th class="text-right py-2">Menge</th>
                <th class="text-right py-2">EP netto</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">Netto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">Beratungsleistung</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>Nettobetrag: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Bruttobetrag: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Machen Sie es sich einfach mit faktur.lu</h2>

<p>Konforme Rechnungen von Hand zu erstellen ist fehleranfällig. <strong>faktur.lu</strong> automatisiert die Konformität:</p>

<ul>
    <li>✅ Alle Pflichtangaben vorausgefüllt</li>
    <li>✅ Automatische, fortlaufende Nummerierung (Art. 63 LIVA)</li>
    <li>✅ Automatische MwSt.-Berechnung je nach Fall</li>
    <li>✅ Passende Rechtsvermerke (Reverse Charge, Ausfuhr, Freigrenze 57bis…)</li>
    <li>✅ Integrierter FAIA-Export für Steuerprüfungen der AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Pflichtangaben, Artikelverweise und Schwellen können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtangaben auf Rechnungen</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">MwSt.-Sanktionen und Rechtsbehelfe</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinie 2006/112/EG – Artikel 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/gutschrift-luxemburg-korrekt-erstellen" class="text-primary-500 hover:text-primary-600 text-sm">Gutschrift Luxemburg →</a></li><li><a href="/de/blog/artikel-63-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht" class="text-primary-500 hover:text-primary-600 text-sm">Fortlaufende Nummerierung (Artikel 63 LIVA) →</a></li><li><a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg (50 000 €) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'vollstaendiger-leitfaden-rechnungsstellung-luxemburg-2026',
                'locale' => 'de',
                'translation_key' => 'guide-complet-facturation-luxembourg-2026',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurz gefasst</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Jede Rechnung muss die <strong>Pflichtangaben nach Artikel 63 LIVA</strong> enthalten (Identit&auml;t von Verk&auml;ufer/K&auml;ufer, USt-IdNr., fortlaufende eindeutige Nummer, S&auml;tze und Betr&auml;ge).</li><li><strong>4 MwSt-S&auml;tze</strong> in Luxemburg: 17 % (Normalsatz), 14 % (Zwischensatz), 8 % (erm&auml;&szlig;igt), 3 % (stark erm&auml;&szlig;igt).</li><li><strong>Kontinuierliche fortlaufende Nummerierung</strong>, ohne L&uuml;cken oder Duplikate.</li><li><strong>10 Jahre Aufbewahrung</strong>; die <strong>FAIA-2.01</strong>-Datei kann von der AED bei einer Pr&uuml;fung verlangt werden.</li><li><strong>MwSt-Befreiung</strong> m&ouml;glich unter <strong>50 000 &euro; netto/Jahr</strong> (Schwelle seit 2025 in Kraft).</li></ul></div>
<p class="lead">Die Rechnungsstellung in Luxemburg unterliegt präzisen Regeln, die durch die Steuergesetzgebung festgelegt sind. Ob Sie ein KMU, Freiberufler oder ein großes Unternehmen sind, dieser Leitfaden erklärt alles, was Sie für eine konforme Rechnungsstellung wissen müssen.</p>

<h2>Warum die Konformität Ihrer Rechnungen unerlässlich ist</h2>

<p>In Luxemburg ist eine Rechnung nicht nur ein einfaches Geschäftsdokument. Es ist ein <strong>offizielles Buchhaltungsdokument</strong>, das als Grundlage dient für:</p>

<ul>
    <li>Die Berechnung und Rückerstattung der Mehrwertsteuer</li>
    <li>Steuerprüfungen der Administration des Contributions Directes (ACD)</li>
    <li>Die Erstellung der FAIA-Datei für die Administration de l'Enregistrement et des Domaines (AED)</li>
    <li>Den Nachweis Ihrer geschäftlichen Transaktionen</li>
</ul>

<p>Eine nicht konforme Rechnung kann zur <strong>Ablehnung des Vorsteuerabzugs</strong> für Ihren Kunden und zu <strong>Geldstrafen</strong> für Ihr Unternehmen führen.</p>

<h2>Pflichtangaben auf einer luxemburgischen Rechnung</h2>

<p>Gemäß Artikel 63 des luxemburgischen Mehrwertsteuergesetzes muss jede Rechnung folgende Informationen enthalten:</p>

<h3>Angaben zum Rechnungssteller</h3>

<ul>
    <li><strong>Name oder Firmenbezeichnung</strong> Ihres Unternehmens</li>
    <li><strong>Vollständige Adresse</strong> des Firmensitzes</li>
    <li><strong>Innergemeinschaftliche USt-IdNr.</strong> (Format LU + 8 Ziffern)</li>
    <li><strong>Niederlassungsgenehmigungsnummer</strong> (falls zutreffend)</li>
</ul>

<h3>Angaben zum Kunden</h3>

<ul>
    <li><strong>Name oder Firmenbezeichnung</strong> des Kunden</li>
    <li><strong>Vollständige Adresse</strong></li>
    <li><strong>USt-IdNr.</strong> (Pflicht bei innergemeinschaftlichen B2B-Transaktionen)</li>
</ul>

<h3>Rechnungsangaben</h3>

<ul>
    <li><strong>Eindeutige Rechnungsnummer</strong> in chronologischer Reihenfolge</li>
    <li><strong>Ausstellungsdatum</strong> der Rechnung</li>
    <li><strong>Liefer- oder Leistungsdatum</strong> (falls abweichend)</li>
</ul>

<h3>Leistungsdetails</h3>

<ul>
    <li><strong>Eindeutige Beschreibung</strong> der Waren oder Dienstleistungen</li>
    <li><strong>Menge</strong> und <strong>Nettoeinzelpreis</strong></li>
    <li><strong>Anzuwendender MwSt.-Satz</strong> pro Position</li>
    <li><strong>MwSt.-Betrag</strong> pro Steuersatz</li>
    <li><strong>Netto-, MwSt.- und Bruttobetrag</strong></li>
</ul>

<h2>Rechnungsnummerierung</h2>

<p>Die Nummerierung Ihrer Rechnungen muss strenge Regeln befolgen:</p>

<ul>
    <li><strong>Eindeutige chronologische Sequenz</strong>: keine Lücken in der Nummerierung</li>
    <li><strong>Freies aber konsistentes Format</strong> (z.B.: 2026-0001, RE-2026-001)</li>
    <li><strong>Eine Serie</strong> pro Geschäftsjahr (außer in Sonderfällen)</li>
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
            <td class="border border-gray-300 px-4 py-2">Ermäßigter Satz (Weine, bestimmte Brennstoffe)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Reduzierter Satz (Gas, Strom, Friseur)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Stark ermäßigter Satz (Lebensmittel, Bücher, Medikamente)</td>
        </tr>
    </tbody>
</table>

<h2>Ausstellungs- und Aufbewahrungsfristen</h2>

<h3>Ausstellungsfrist</h3>

<p>Eine Rechnung muss <strong>spätestens bis zum 15. des Folgemonats</strong> nach Lieferung der Ware oder Erbringung der Dienstleistung ausgestellt werden.</p>

<h3>Aufbewahrungsdauer</h3>

<p>Sie müssen Ihre Rechnungen <strong>10 Jahre</strong> ab Ende des betreffenden Geschäftsjahres aufbewahren. Diese Pflicht gilt für ausgestellte UND erhaltene Rechnungen.</p>

<h2>Die FAIA-Datei: Eine luxemburgische Pflicht</h2>

<p>Die <strong>FAIA (Fichier d'Audit Informatisé)</strong> ist eine standardisierte XML-Datei, die jedes Unternehmen, das Buchhaltungs- oder Rechnungssoftware verwendet, auf Anfrage der Steuerbehörde vorlegen können muss.</p>

<p>Diese Datei enthält:</p>

<ul>
    <li>Alle Ihre Buchungen</li>
    <li>Ihre ausgestellten und erhaltenen Rechnungen</li>
    <li>Ihre Kunden und Lieferanten</li>
    <li>Ihre Zahlungen</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu erstellt automatisch Ihre FAIA-Datei</p>
    <p class="text-green-700">Unsere Software erzeugt mit einem Klick eine konforme FAIA-Datei, bereit zur Übermittlung an die AED bei einer Prüfung.</p>
</div>

<h2>Fehler, die Sie vermeiden sollten</h2>

<ol>
    <li><strong>Vergessen der USt-IdNr.</strong> bei innergemeinschaftlichen B2B-Rechnungen</li>
    <li><strong>Nicht-sequentielle Nummerierung</strong> (Lücken in der Serie)</li>
    <li><strong>Keine Unterscheidung der MwSt.-Sätze</strong> wenn mehrere gelten</li>
    <li><strong>Verspätete Rechnungsausstellung</strong> (nach dem 15. des Folgemonats)</li>
    <li><strong>Rechnungen nicht 10 Jahre aufbewahren</strong></li>
</ol>

<h2>Fazit</h2>

<p>Die Rechnungsstellung in Luxemburg erfordert Sorgfalt und Konformität. Mit einer <strong>geeigneten Rechnungssoftware</strong> wie faktur.lu stellen Sie sicher, dass alle gesetzlichen Anforderungen erfüllt werden und sparen dabei wertvolle Zeit.</p>

<p>Unsere Lösung erstellt automatisch konforme Rechnungen mit allen Pflichtangaben, korrekter Nummerierung und integriertem FAIA-Export.</p>
<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'complete-guide-invoicing-luxembourg-2026',
                'locale' => 'en',
                'translation_key' => 'guide-complet-facturation-luxembourg-2026',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Key points</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Every invoice must carry the mandatory particulars of <strong>Article 63 LIVA</strong> (seller/buyer identity, VAT numbers, unique sequential number, rates and amounts).</li><li><strong>4 VAT rates</strong> in Luxembourg: 17% (standard), 14% (intermediate), 8% (reduced), 3% (super-reduced).</li><li><strong>Continuous sequential numbering</strong>, with no gaps or duplicates.</li><li><strong>10-year retention</strong>; the <strong>FAIA 2.01</strong> file can be requested by the AED during an audit.</li><li><strong>VAT exemption (franchise)</strong> available below <strong>&euro;50,000 net/year</strong> (threshold in force since 2025).</li></ul></div>
<p class="lead">Invoicing in Luxembourg follows precise rules defined by tax legislation. Whether you are an SME, freelancer, or large company, this guide explains everything you need to know to invoice in compliance.</p>

<h2>Why Invoice Compliance is Essential</h2>

<p>In Luxembourg, an invoice is not just a simple commercial document. It is an <strong>official accounting document</strong> that serves as the basis for:</p>

<ul>
    <li>VAT calculation and recovery</li>
    <li>Tax audits by the Administration des Contributions Directes (ACD)</li>
    <li>Generating the FAIA file for the Administration de l'Enregistrement et des Domaines (AED)</li>
    <li>Proof of your business transactions</li>
</ul>

<p>A non-compliant invoice can result in <strong>rejection of VAT deduction</strong> for your client and <strong>financial penalties</strong> for your business.</p>

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
    <li><strong>VAT number</strong> (mandatory for intra-community B2B transactions)</li>
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
    <li><strong>Unique chronological sequence</strong>: no gaps in numbering</li>
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
            <td class="border border-gray-300 px-4 py-2">Super-reduced rate (food, books, medicines)</td>
        </tr>
    </tbody>
</table>

<h2>Issue and Retention Deadlines</h2>

<h3>Issue Deadline</h3>

<p>An invoice must be issued <strong>no later than the 15th of the following month</strong> after delivery of goods or completion of services.</p>

<h3>Retention Period</h3>

<p>You must retain your invoices for <strong>10 years</strong> from the end of the relevant fiscal year. This obligation applies to both issued AND received invoices.</p>

<h2>The FAIA File: A Luxembourg Obligation</h2>

<p>The <strong>FAIA (Fichier d'Audit Informatisé)</strong> is a standardized XML file that any business using accounting or invoicing software must be able to produce upon request from the tax authorities.</p>

<p>This file contains:</p>

<ul>
    <li>All your accounting entries</li>
    <li>Your issued and received invoices</li>
    <li>Your clients and suppliers</li>
    <li>Your payments</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu automatically generates your FAIA file</p>
    <p class="text-green-700">Our software produces a compliant FAIA file with one click, ready to submit to the AED during an audit.</p>
</div>

<h2>Mistakes to Avoid</h2>

<ol>
    <li><strong>Forgetting the VAT number</strong> on intra-community B2B invoices</li>
    <li><strong>Using non-sequential numbering</strong> (gaps in the series)</li>
    <li><strong>Not distinguishing VAT rates</strong> when multiple rates apply</li>
    <li><strong>Issuing invoices late</strong> (after the 15th of the following month)</li>
    <li><strong>Not retaining invoices for 10 years</strong></li>
</ol>

<h2>Conclusion</h2>

<p>Invoicing in Luxembourg requires rigor and compliance. By using <strong>suitable invoicing software</strong> like faktur.lu, you ensure all legal requirements are met while saving valuable time.</p>

<p>Our solution automatically generates compliant invoices with all mandatory information, correct numbering, and integrated FAIA export.</p>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'faia-luxembourg-computerized-audit-file-guide',
                'locale' => 'en',
                'translation_key' => 'faia-luxembourg-fichier-audit-informatise-guide',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">The FAIA (computerised audit file) is a file the AED can request during a tax audit. Contrary to a widespread belief, it does not concern every Luxembourg business: four cumulative conditions determine who must be able to produce it.</p>

<h2>What is the FAIA?</h2>

<p>The <strong>FAIA (Fichier d'Audit Informatisé)</strong>, also known as <strong>SAF-T Luxembourg</strong>, is a standardised XML file containing all of a company's accounting and tax data for a given period.</p>

<p>Its legal basis is the <strong>law of 19 December 2008</strong> (Mémorial A-206 of 24 December 2008), which amended <strong>article 70, paragraph 3, of the VAT law</strong>. That text provides that books and documents held in electronic form must, at the administration's request, be communicated "in a legible and directly intelligible form" or according to any other technical arrangements the administration determines. The FAIA is the arrangement chosen by the AED.</p>

<h2>Who must produce a FAIA file?</h2>

<p>This is the most frequently distorted point. According to the <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED's official FAQ</a>, the obligation requires <strong>four conditions to be met at the same time</strong>.</p>

<h3>The four cumulative conditions</h3>

<ol>
    <li>Being <strong>subject to the standard chart of accounts (PCN)</strong></li>
    <li><strong>Not benefiting from a simplified regime</strong></li>
    <li>Achieving <strong>annual turnover above €112,000</strong></li>
    <li>Exceeding a volume of roughly <strong>500 accounting transactions</strong> per year</li>
</ol>

<p>If even one of these conditions is missing, the FAIA does not apply to you. The AED puts it plainly in its own FAQ:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>"I have turnover of €1,000,000 and only 400 transactions in my accounts. Am I required to provide a FAIA file? — <strong>No.</strong> Although your turnover exceeds €112,000, your transaction volume remains within limits where a manual audit is more rational."</p>
</blockquote>

<h3>What a "transaction" actually is</h3>

<p>Be careful how you count: a transaction <strong>is not an invoice</strong>. The AED defines it as an <strong>entire posting chain</strong>. A purchase, for instance, breaks down into four linked entries — expense account, input VAT, supplier account, payment — which together form <strong>a single</strong> transaction.</p>

<p>A freelancer counting 600 invoices and concluding they are over the threshold is therefore probably measuring the wrong thing.</p>

<h3>If you are not subject to the PCN</h3>

<p>You escape the FAIA obligation as such, even with high turnover and more than 500 transactions. But article 70 still applies: the AED may require you to export your electronic data <strong>in a delimited, structured format</strong>. Being outside the FAIA does not excuse you from being able to output your accounts cleanly.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Important</p>
    <p class="text-amber-700">The FAIA is never submitted spontaneously, and in particular <strong>not with your VAT return</strong>. It is produced <strong>solely on request</strong> from an AED officer in charge of auditing your business.</p>
</div>

<h2>What does the FAIA file contain?</h2>

<p>The FAIA file is structured into several sections:</p>

<h3>1. General information (Header)</h3>

<ul>
    <li>Company identification (name, address, VAT number)</li>
    <li>Period covered by the file</li>
    <li>Information on the software used</li>
    <li>Date and time of generation</li>
</ul>

<h3>2. Chart of accounts (GeneralLedger)</h3>

<ul>
    <li>List of all accounting accounts used</li>
    <li>Account hierarchy</li>
    <li>Opening and closing balances</li>
</ul>

<h3>3. Customers and suppliers (MasterFiles)</h3>

<ul>
    <li>Customer file with full contact details</li>
    <li>Supplier file</li>
    <li>Intra-community VAT numbers</li>
</ul>

<h3>4. Accounting entries (GeneralLedgerEntries)</h3>

<ul>
    <li>All entries for the period, including those with no direct VAT relevance — the export must cover the accounts in their entirety</li>
    <li>Accounting journals</li>
    <li>Referenced supporting documents</li>
</ul>

<h3>5. Invoices (SourceDocuments)</h3>

<ul>
    <li>Sales invoices issued</li>
    <li>Purchase invoices received</li>
    <li>Credit notes</li>
    <li>Line-by-line detail with VAT</li>
</ul>

<p>If your invoicing system is <strong>integrated with your accounts</strong>, source documents must be supplied systematically. If it is not, the AED officer may request specific source documents.</p>

<h2>FAIA technical format</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Encoding</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, last update published by the AED in July 2020. Three schemas coexist: <em>full</em>, <em>reduced version A</em> and <em>reduced version B</em>, depending on the accounting regime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Period</td>
            <td class="border border-gray-300 px-4 py-2">A full financial year, aligned on the calendar year. Truncated years are refused, and one file may cover only one period: an audit spanning three years requires three files</td>
        </tr>
    </tbody>
</table>

<h2>How to generate a compliant FAIA file</h2>

<h3>Option 1: compatible invoicing software</h3>

<p>This is the simplest route. Software such as <strong>faktur.lu</strong> automatically generates a compliant FAIA file from your invoicing data.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ One-click FAIA export with faktur.lu</p>
    <p class="text-green-700">Our software generates a FAIA file validated against the official XSD schema, ready to be sent to the AED — whether you are required to today or cross the thresholds tomorrow.</p>
</div>

<h3>Option 2: accounting software</h3>

<p>Professional accounting packages (Sage, BOB, etc.) generally offer a FAIA export module.</p>

<h3>Option 3: custom development</h3>

<p>For large companies with proprietary systems, specific development may be needed to extract and format the data according to the FAIA schema.</p>

<h2>Validating the FAIA file</h2>

<p>Before submitting your file, validate it:</p>

<ol>
    <li><strong>XSD validation</strong>: check that the file complies with the official XML schema</li>
    <li><strong>Totals check</strong>: make sure the sums are consistent</li>
    <li><strong>Reference check</strong>: every identifier (customers, accounts) must be present</li>
</ol>

<p>The AED is explicit on this point: <strong>no validation tool is provided</strong>, and "only the schema published on the AED website may serve as a control mechanism". You may therefore use any third-party XML validator (such as the <a href="/en/validateur-faia">faktur.lu validator</a>) to check compliance before submitting.</p>

<h2>Deadlines, submission and penalties</h2>

<h3>Production deadline</h3>

<p>The AED publishes no fixed statutory deadline. Where a FAIA file is requested during an audit, the deadline is set <strong>case by case by the auditor</strong>, depending on the complexity of the request.</p>

<h3>Submission medium</h3>

<p>The AED is flexible: any standard electronic medium available on the market is accepted — USB stick, external hard drive, CD-R or DVD-R, e-mail.</p>

<h3>Penalties for non-compliance</h3>

<p>For businesses that genuinely fall under the obligation, refusing or being unable to supply the data may lead to:</p>

<ul>
    <li><strong>Administrative fines</strong></li>
    <li><strong>Assessment on the administration's own estimate</strong></li>
    <li><strong>Rejection of the accounts</strong> as evidence</li>
</ul>

<h2>Best practice</h2>

<ol>
    <li><strong>First check whether you are concerned</strong> — all four conditions must be met</li>
    <li><strong>Test your FAIA export regularly</strong>, not only during an audit</li>
    <li><strong>Archive</strong> the FAIA files generated for each financial year</li>
    <li><strong>Check consistency</strong> between your invoices and your accounting entries</li>
    <li><strong>Use certified</strong> or tested software for the FAIA export</li>
</ol>

<h2>The 4 most common FAIA mistakes</h2>

<ol>
    <li><strong>Invoice numbering not compliant</strong> with article 63 LIVA point 3° (gaps in the sequence or duplicates). The file can be rejected at validation.</li>
    <li><strong>Missing mandatory fields</strong>: customer VAT number, full address, reverse charge wording for intra-EU B2B.</li>
    <li><strong>Inconsistent totals</strong> between sections — for instance a sum of invoices that differs from the declared total.</li>
    <li><strong>Incorrect date format</strong>: the FAIA requires ISO 8601 (YYYY-MM-DD), not DD/MM/YYYY.</li>
</ol>

<h2>Frequently asked questions</h2>

<h3>Do you have to send a FAIA every year?</h3>
<p>No. The FAIA is produced solely at the AED's request during an audit, and only by the businesses covered. It is never filed alongside the VAT return.</p>

<h3>What happens if I cannot provide it when required?</h3>
<p>The auditor concludes that your accounts are not kept to standard. You risk an administrative fine of €250 to €10,000 per infringement (art. 77 LIVA), an assessment on the administration's own estimate, and — where you refuse to produce records — a fine of up to €25,000 per day of delay after a warning.</p>

<h3>Is the FAIA mandatory under the VAT exemption regime?</h3>
<p>Generally no. The exemption (art. 57bis LIVA, turnover ≤ €50,000) keeps the taxable person below the €112,000 threshold, and a simplified regime is in any case a ground for exclusion on its own.</p>

<h3>How do I know my file is compliant before an audit?</h3>
<p>Validate it against the official XSD schema. The <a href="/en/faia-validator">faktur.lu FAIA validator</a> is free and needs no sign-up: it checks compliance with the 2.01 schema, detects missing fields, verifies the consistency of totals and the sequentiality of invoice numbers, and stores none of your data.</p>

<h3>Can my accountant generate the FAIA for me?</h3>
<p>Yes. Your accountant can produce it from their own tool, or retrieve yours through a read-only accountant portal.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED - Official FAIA page</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED - FAIA 2.01 XSD schemas</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAIA FAQ (scope and exclusions)</a></li>
</ul>

<h2>Conclusion</h2>

<p>The FAIA is a real obligation, but a targeted one: it applies to businesses subject to the standard chart of accounts and the normal regime, above €112,000 in turnover and around 500 annual transactions. Many freelancers and small structures are simply not covered.</p>

<p>If you are concerned — or if growth takes you there — invoicing software able to produce the file spares you discovering the problem on audit day. faktur.lu includes FAIA export natively, validated against the AED's official schema.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/tax-audit-luxembourg-how-to-prepare" class="text-primary-500 hover:text-primary-600 text-sm">Tax audits →</a></li><li><a href="/en/blog/invoice-archiving-luxembourg-legal-duration-format" class="text-primary-500 hover:text-primary-600 text-sm">Archiving invoices →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Thresholds and procedures may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'freelancer-luxembourg-invoice-compliance',
                'locale' => 'en',
                'translation_key' => 'freelance-luxembourg-facturer-conformite',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Starting out as a freelancer in Luxembourg? Invoicing is a crucial part of your business. This guide explains how to create compliant invoices in 2026 and manage your tax obligations (updated following the increase of the VAT exemption threshold to €50,000).</p>

<h2>Freelance status in Luxembourg</h2>

<p>In Luxembourg, a freelancer (or self-employed person) generally operates under one of these statuses:</p>

<ul>
    <li><strong>Sole proprietorship</strong>: the most common status to start with</li>
    <li><strong>Single-member company (SARL-S)</strong>: a simplified limited liability company</li>
    <li><strong>Liberal profession</strong>: for certain regulated activities</li>
</ul>

<p>Whatever your status, the same invoicing rules apply.</p>

<h2>Registering for VAT</h2>

<p>Before you start invoicing (above the exemption threshold, see below), you must obtain an <strong>intra-community VAT number</strong> from the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Registration Duties, Estates and VAT Authority)</a>.</p>

<h3>Registration procedure</h3>

<ol>
    <li>Obtain a <strong>business permit</strong> from the Ministry of the Economy</li>
    <li>Register with the <strong>Trade and Companies Register (RCS)</strong> where applicable</li>
    <li>Apply for <strong>VAT registration</strong> via <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a></li>
    <li>Receive your number in the format <strong>LU + 8 digits</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Note</p>
    <p class="text-amber-700">Below the exemption threshold (turnover ≤ €50,000 excl. VAT, art. 57bis LIVA since 2025), VAT registration is not mandatory. You then invoice without VAT, stating "VAT not applicable - Article 57bis". <strong>The trade-off:</strong> you cannot reclaim VAT on your business purchases (equipment, software, subcontracting). If you invest, the exemption may cost you more than it saves.</p>
</div>

<h2>Mandatory details on your invoices</h2>

<p>Under <strong>article 63 LIVA</strong>, as a freelancer your invoices must contain:</p>

<h3>Your details</h3>

<ul>
    <li><strong>Full name</strong> or business name</li>
    <li><strong>Business address</strong> in Luxembourg</li>
    <li><strong>VAT number</strong> (LU12345678)</li>
    <li>Your RCS number where applicable (mandatory for registered traders/companies)</li>
</ul>

<h3>Client details</h3>

<ul>
    <li>Name or business name</li>
    <li>Full address</li>
    <li>VAT number (mandatory for intra-EU business clients)</li>
</ul>

<h3>Details of the service</h3>

<ul>
    <li><strong>Unique, sequential invoice number</strong> (article 63 LIVA, point 3°)</li>
    <li><strong>Date of issue</strong></li>
    <li><strong>Date of supply</strong> (chargeable event for VAT)</li>
    <li><strong>Detailed description</strong> of the services rendered</li>
    <li><strong>Number of hours or days</strong> (recommended)</li>
    <li><strong>Unit price excl. VAT</strong></li>
    <li><strong>Net amount, VAT and gross amount</strong></li>
    <li><strong>Applicable VAT rate</strong></li>
</ul>

<h2>Which VAT rate applies?</h2>

<p>As a freelancer you generally apply the <strong>standard rate of 17%</strong> to most services.</p>

<h3>Special cases</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applicable VAT</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Business client in Luxembourg</td><td class="border border-gray-300 px-4 py-2">VAT 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Business client in the EU (intra-EU B2B)</td><td class="border border-gray-300 px-4 py-2">0% (reverse charge, art. 17 LIVA + art. 196 Directive)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (outside the scope of Luxembourg VAT for most intellectual services)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Private client in Luxembourg</td><td class="border border-gray-300 px-4 py-2">VAT 17%</td></tr>
    </tbody>
</table>

<h2>Invoicing a client abroad</h2>

<h3>Business client in the EU</h3>

<p>If your client is a business in another EU country:</p>

<ol>
    <li><strong>Check their VAT number</strong> on the <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> system</li>
    <li><strong>Do not charge VAT</strong> on your invoice (place of supply is the customer's, art. 17 LIVA)</li>
    <li><strong>Add the wording</strong>: <em>"Reverse charge - Article 196 of Directive 2006/112/EC"</em></li>
    <li><strong>State the client's VAT number</strong> on the invoice</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mandatory wording - correct legal basis</p>
    <p>The canonical wording for intra-EU B2B reverse charge is <strong>"Reverse charge - Article 196 of Directive 2006/112/EC"</strong>. Article 196 designates the customer as liable (art. 226 §11bis of the Directive). Not to be confused with Article 44 of the Directive (place of supply) nor with Article 44 of the Luxembourg 1979 law (sectoral exemptions, unrelated).</p>
</div>

<h3>Client outside the EU</h3>

<p>For clients located outside the European Union, the intellectual services a freelancer typically provides (consulting, IT, advertising, translation) fall outside the scope of Luxembourg VAT. State "VAT not applicable - service supplied outside the EU".</p>

<h2>Numbering your invoices (article 63 LIVA, point 3°)</h2>

<p>Your invoices must follow <strong>chronological, unbroken numbering</strong>:</p>

<ul>
    <li>No gaps in the sequence</li>
    <li>Free but consistent format (e.g. 2026-001, 2026-002…)</li>
    <li>Annual reset permitted</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tip</p>
    <p class="text-purple-700">Use invoicing software such as faktur.lu to generate compliant numbers automatically and avoid mistakes.</p>
</div>

<h2>Managing your VAT returns</h2>

<p>Once registered, you file returns whose frequency depends on your net turnover. <strong>The AED determines your regime</strong> — you do not choose it.</p>

<ul>
    <li><strong>Turnover &lt; €112,000/year</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000/year</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover &gt; €620,000/year</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ The annual return does not replace the periodic ones</p>
    <p class="text-amber-700">This is the costliest misunderstanding on this page. If you file quarterly or monthly returns, you must <strong>also</strong> file a <strong>recapitulative annual return</strong>, due before <strong>1 May of the following year</strong>. Forgetting it triggers late-filing penalties even though you paid all your VAT during the year.</p>
</div>

<p>Periodic returns are due before the 15th of the month following the period concerned. Everything is filed online through <strong>eCDF (eTVA)</strong>.</p>

<h2>The FAIA file for freelancers</h2>

<p>If you use invoicing software, you must be able to produce a <strong>FAIA file</strong> on request from the AED - subject to conditions. The AED FAQ excludes taxable persons with turnover ≤ €112,000 excl. VAT, so very small freelancers under the exemption regime are generally not required to produce a FAIA file. Confirm with your accountant based on your regime.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generates your FAIA file</p>
    <p class="text-green-700">Our software automatically produces a compliant FAIA 2.01 file, ready for any AED tax audit.</p>
</div>

<h2>Tips for freelancers starting out</h2>

<ol>
    <li><strong>Use suitable software</strong> from day one to avoid mistakes</li>
    <li><strong>Keep every invoice</strong> (issued and received) for 10 years (art. 16 Commercial Code + art. 65 LIVA)</li>
    <li><strong>Separate</strong> your personal and business accounts</li>
    <li><strong>Invoice promptly</strong> (by the 15th of the month following the supply, art. 63 LIVA)</li>
    <li><strong>Check the VAT numbers</strong> of your EU clients before invoicing (VIES)</li>
    <li><strong>Diarise both deadlines</strong>: the periodic returns and the annual return</li>
    <li><strong>Consult an accountant</strong> for complex questions</li>
</ol>

<h2>Common mistakes to avoid</h2>

<ul>
    <li>❌ Invoicing without a VAT number (where you are liable for VAT)</li>
    <li>❌ Omitting mandatory details (art. 63 LIVA)</li>
    <li>❌ Applying the wrong VAT rate</li>
    <li>❌ Non-sequential numbering</li>
    <li>❌ Invoicing after the 15th of the month following the supply</li>
    <li>❌ Failing to check EU VAT numbers (VIES)</li>
    <li>❌ <strong>Believing that quarterly returns remove the need for the annual return</strong></li>
    <li>❌ Confusing "Article 44 of the Directive" (place of supply) with "Article 196 of the Directive" (reverse charge wording)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>VAT thresholds, rates and procedures may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) - articles 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - VAT returns and filing frequencies</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED - Exemption regime (art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mandatory invoice details</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Intra-community VAT validation</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<h2>Conclusion</h2>

<p>Invoicing as a freelancer in Luxembourg is not complicated once you know the rules. Using suitable invoicing software such as faktur.lu lets you create compliant invoices in a few clicks, with every mandatory detail and the right VAT rates applied automatically. Focus on your craft — we will handle your compliance!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/5-common-mistakes-freelance-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">5 common mistakes on a freelance invoice →</a></li><li><a href="/en/blog/mandatory-information-invoice-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li><li><a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption (€50,000 threshold) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'mandatory-information-invoice-luxembourg',
                'locale' => 'en',
                'translation_key' => 'mentions-obligatoires-facture-luxembourg',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">In brief</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>Mandatory details come from <strong>article 63 LIVA</strong>: identity and address of the parties, VAT numbers, date, <strong>unique sequential number</strong>, description, net base, rate, VAT amount, gross total.</li><li>A <strong>non-compliant</strong> invoice can be rejected by the client and lead to an <strong>AED fine of €250 to €10,000</strong> (art. 77 LIVA) — or <strong>10% to 50% of the VAT at stake</strong> where the Treasury lost revenue.</li><li>Conditional wording: <strong>reverse charge</strong> (art. 196 Directive), exemption regime, exemptions.</li><li><strong>10-year retention</strong> of invoices and accounting records.</li></ul></div>
<p class="lead">A non-compliant invoice can be rejected by your client and expose you to AED fines. Here is the complete checklist of mandatory details for flawless Luxembourg invoices — based on <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">article 63 LIVA</a> and the list published by the AED.</p>

<h2>Checklist of mandatory details</h2>

<p><strong>Article 63 LIVA</strong> (amended law of 12 February 1979) and the AED's list require the following:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Issuer details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Full name or business name</strong></li>
        <li>☐ <strong>Full address</strong> of the registered office</li>
        <li>☐ <strong>Intra-community VAT number</strong> (format LU + 8 digits)</li>
        <li>☐ <strong>RCS number</strong> for registered traders and companies — an obligation arising from <em>trade and companies register legislation</em>, not from VAT law</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Client details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Full name or business name</strong></li>
        <li>☐ <strong>Full address</strong></li>
        <li>☐ <strong>VAT number</strong> (mandatory for intra-community B2B - validated through <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Invoice details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Unique, sequential invoice number</strong> (art. 63 LIVA, point 3°)</li>
        <li>☐ <strong>Date of issue</strong> of the invoice</li>
        <li>☐ <strong>Date of delivery or supply</strong> — legally required <em>where it differs</em> from the date of issue (art. 226 §7 of the Directive). Stating it systematically remains good practice: it removes any ambiguity about the chargeable event during an audit</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Details of goods or services</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Clear, detailed description</strong></li>
        <li>☐ <strong>Quantity</strong> delivered or supplied</li>
        <li>☐ <strong>Unit price excluding VAT</strong></li>
        <li>☐ <strong>Any discounts or rebates</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ VAT details</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Applicable VAT rate</strong> per line (17%, 14%, 8%, 3% or 0%)</li>
        <li>☐ <strong>VAT amount</strong> per rate</li>
        <li>☐ <strong>Taxable base</strong> (net amount) per VAT rate</li>
        <li>☐ <strong>Exemption or reverse charge wording</strong> where applicable (see table below)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totals</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Net total</strong></li>
        <li>☐ <strong>Total VAT</strong></li>
        <li>☐ <strong>Gross total</strong></li>
    </ul>
</div>

<h2>Conditional wording by situation</h2>

<p>Depending on the nature of the transaction, specific wording must appear on the invoice. Here are the codified formulations:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wording to use</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Legal basis</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Intra-EU B2B service (customer in another Member State)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"Reverse charge - Article 196 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (place) + art. 196 Directive (liability) + art. 226 §11bis (wording)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Intra-EU B2B supply of goods</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT exemption - Article 138 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Export outside the EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT exemption - Article 146 of Directive 2006/112/EC"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">VAT exemption regime (small businesses)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">"VAT not applicable - Article 57bis of the amended law of 12 February 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (€50,000 threshold)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Not to be confused</p>
    <p>For intra-EU B2B reverse charge, many templates copy the reference to "article 44 of Directive 2006/112/EC". Article 44 defines the <strong>place of supply</strong> (the customer's), but the codified mandatory wording refers to <strong>article 196</strong> (which designates the customer as liable). Prefer the article 196 wording.</p>
</div>

<h3>Special invoicing cases</h3>

<p><strong>Deposit invoice</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Deposit on order no. [reference] dated [date]"</p>
</div>

<p><strong>Credit note</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Credit note on invoice no. [number] dated [date]"</p>
</div>

<h2>Invoice numbering (art. 63 LIVA, point 3°)</h2>

<p>Numbering must follow these rules:</p>

<ul>
    <li><strong>Unique</strong>: each invoice has a distinct number</li>
    <li><strong>Chronological</strong>: numbers follow the order of issue</li>
    <li><strong>Unbroken</strong>: no gap in the sequence</li>
    <li><strong>Non-reusable</strong>: a number may be assigned only once</li>
</ul>

<h3>Examples of accepted formats</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Example</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Year + number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Prefix + number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Simple number</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Payment terms</h2>

<p>Although article 63 LIVA does not require them, these details are <strong>strongly recommended</strong>:</p>

<ul>
    <li><strong>Payment period</strong> (default: 30 days after receipt, amended law of 18 April 2004)</li>
    <li><strong>Due date</strong></li>
    <li><strong>Bank details</strong> (IBAN, BIC)</li>
    <li><strong>Late payment interest</strong> applicable between businesses — ECB reference rate + 8 points, i.e. <strong>10.15% in the first half of 2026</strong>, revised every six months — and the <strong>€40 fixed compensation</strong></li>
</ul>

<h2>Retaining invoices</h2>

<p>You must keep your invoices (issued and received) for <strong>10 years</strong> from the close of the financial year — article 16 of the Commercial Code and article 65 LIVA. Paper or electronic format (PDF/A with integrity guarantees). See the <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">accounting obligations on Guichet.lu</a>.</p>

<h2>Consequences of a non-compliant invoice</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risks incurred</p>
    <ul class="text-red-700 mt-2">
        <li>Your client's VAT deduction refused</li>
        <li><strong>Administrative fine of €250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
        <li>Where the breach led to unpaid VAT or an irregular refund: <strong>a fine of 10% to 50% of the VAT at stake</strong> — proportional, hence uncapped</li>
        <li>Where invoices and accounting records are not produced during an audit: <strong>up to €25,000 per day of delay</strong>, after a warning</li>
        <li>In cases of aggravated tax fraud or tax swindling: criminal fine of €25,000 to ten times the VAT amount, imprisonment from one month to five years, and loss of civic rights for 5 to 10 years (art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">The €10,000 cap is not the worst case</p>
    <p>It is the proportional fine that hurts. On a dispute involving €80,000 of VAT, a 10–50% penalty means €8,000 to €40,000 — regardless of how many formal breaches are recorded.</p>
</div>

<h2>Example of a compliant invoice</h2>

<p>Here are the essential elements of a compliant invoice:</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">Your Company SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">INVOICE</p>
            <p>N° 2026-0042</p>
            <p>Date: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Billed to:</p>
        <p>Client Company SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Description</th>
                <th class="text-right py-2">Qty</th>
                <th class="text-right py-2">Unit price</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">Net total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">Consulting services</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>Net total: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Gross total: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Make life easier with faktur.lu</h2>

<p>Creating compliant invoices manually invites mistakes. <strong>faktur.lu</strong> automates compliance:</p>

<ul>
    <li>✅ All mandatory details pre-filled</li>
    <li>✅ Automatic sequential numbering (art. 63 LIVA)</li>
    <li>✅ Automatic VAT calculation for each case</li>
    <li>✅ Appropriate legal wording (reverse charge, export, 57bis exemption…)</li>
    <li>✅ Built-in FAIA export for AED tax audits</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Mandatory details, article references and thresholds may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) - articles 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED - Mandatory invoice details</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">VAT sanctions and remedies</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/EC - articles 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/credit-note-luxembourg-how-to-issue-correctly" class="text-primary-500 hover:text-primary-600 text-sm">Credit notes in Luxembourg →</a></li><li><a href="/en/blog/article-63-liva-sequential-invoice-numbering-luxembourg-mandatory" class="text-primary-500 hover:text-primary-600 text-sm">Sequential numbering (article 63 LIVA) →</a></li><li><a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption (€50,000 threshold) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'sole-proprietorship-belgium-guide-2026',
                'locale' => 'en',
                'translation_key' => 'creer-entreprise-individuelle-belgique-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Belgium offers a favorable framework for self-employed workers with simplified procedures since the removal of basic management knowledge requirements. This guide accompanies you in creating your business as a natural person.</p>

<h2>Legal Form: Business as Natural Person</h2>

<p>The business as a natural person (self-employed) is the simplest form to carry out an economic activity alone in Belgium.</p>

<h3>Main Characteristics</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspect</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Founding act</td><td class="p-2 border-b">Not required</td></tr>
        <tr><td class="p-2 border-b">Minimum capital</td><td class="p-2 border-b">Not required</td></tr>
        <tr><td class="p-2 border-b">Liability</td><td class="p-2 border-b"><strong>Unlimited</strong> - personal and professional assets merged</td></tr>
        <tr><td class="p-2 border-b">Statistics</td><td class="p-2 border-b">43% of Belgian SMEs (510,346 businesses)</td></tr>
    </tbody>
</table>

<h2>Requirements and Prerequisites</h2>

<h3>General Requirements</h3>

<ul>
    <li>Be at least <strong>18 years old</strong></li>
    <li>Enjoy civil and political rights</li>
    <li>Be legally capable</li>
</ul>

<h3>Basic Management Knowledge: ABOLISHED</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Good news!</p>
    <p>Basic management knowledge has been abolished in all regions:</p>
    <ul class="mt-2">
        <li><strong>Flanders:</strong> since 2018</li>
        <li><strong>Brussels:</strong> since January 15, 2024</li>
        <li><strong>Wallonia:</strong> since October 1, 2025</li>
    </ul>
</div>

<h3>Professional Access</h3>

<p>Certain regulated professions still require <strong>specific professional competences</strong>: hairdresser, baker, pastry chef, mechanic, roofer, heating engineer, restaurateur, etc.</p>

<h2>Creation Steps</h2>

<h3>Step 1: Open a Business Bank Account</h3>
<p>Mandatory to separate professional and private transactions.</p>

<h3>Step 2: Register with the Crossroads Bank for Enterprises (CBE)</h3>
<ul>
    <li>Through an <strong>approved business counter</strong></li>
    <li>Obtain the <strong>enterprise number</strong> (unique identifier)</li>
    <li>Verification of professional competences if necessary</li>
</ul>

<h3>Step 3: Activate VAT Number</h3>
<ul>
    <li>With the General Tax Administration</li>
    <li>Can be done through the business counter</li>
    <li>Possibility to request the VAT franchise regime (if turnover < €25,000)</li>
</ul>

<h3>Step 4: Join a Social Insurance Fund</h3>
<p><strong>Mandatory BEFORE starting activity</strong>. Affiliation possible up to 6 months in advance.</p>

<h3>Step 5: Join a Health Insurance Fund</h3>
<p>Mandatory to benefit from health and disability insurance.</p>

<h3>Step 6: Take Out Necessary Insurance</h3>
<p>Professional liability insurance and others depending on activity.</p>

<h2>The 8 Approved Business Counters</h2>

<ol>
    <li>Liantis (the largest)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Eunomia</li>
    <li>Formalis</li>
</ol>

<h2>Creation Costs</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Item</th>
            <th class="text-left p-2 bg-slate-100">Amount (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">CBE registration via counter</td><td class="p-2 border-b">€109 - 111.50 (VAT exempt)</td></tr>
        <tr><td class="p-2 border-b">Miscellaneous costs</td><td class="p-2 border-b">Variable</td></tr>
        <tr><td class="p-2 border-b font-semibold">Estimated total budget</td><td class="p-2 border-b font-semibold">€200 - 500</td></tr>
    </tbody>
</table>

<h2>Average Timelines</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Procedure</th>
            <th class="text-left p-2 bg-slate-100">Timeline</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">CBE registration via counter</td><td class="p-2 border-b">Immediate to a few days</td></tr>
        <tr><td class="p-2 border-b">VAT activation</td><td class="p-2 border-b">A few days</td></tr>
        <tr><td class="p-2 border-b">Social fund affiliation</td><td class="p-2 border-b">Immediate</td></tr>
        <tr><td class="p-2 border-b font-semibold">Complete process</td><td class="p-2 border-b font-semibold">1 to 2 weeks</td></tr>
    </tbody>
</table>

<h2>Obligations After Creation</h2>

<h3>VAT</h3>

<h4>Normal Regime</h4>
<ul>
    <li>Periodic VAT declaration (monthly or quarterly)</li>
    <li>Invoicing with VAT</li>
    <li>Annual customer listing</li>
</ul>

<h4>Franchise Regime (if turnover < €25,000)</h4>
<ul>
    <li>No periodic declaration</li>
    <li>No VAT to charge or pay</li>
    <li>Communication of annual turnover before March 31</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mandatory mention under franchise</p>
    <p>"Small business subject to the tax exemption regime - VAT not applicable (Art. 56bis of the VAT Code)"</p>
</div>

<h3>Social Security Contributions (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Income Bracket</th>
            <th class="text-left p-2 bg-slate-100">Rate 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">€0 to €75,024.54</td><td class="p-2 border-b font-semibold">20.50%</td></tr>
        <tr><td class="p-2 border-b">€75,024.54 to €110,562.42</td><td class="p-2 border-b">14.16%</td></tr>
        <tr><td class="p-2 border-b">Above €110,562.42</td><td class="p-2 border-b">Exempt</td></tr>
    </tbody>
</table>

<p><strong>Minimum contribution 2026:</strong> €450.15/quarter (full-time self-employed)</p>

<p><strong>How it works:</strong></p>
<ul>
    <li><strong>Quarterly</strong> payment</li>
    <li>Initially <strong>provisional</strong> contributions (based on N-3 income)</li>
    <li>Regularization once final income is known</li>
</ul>

<h3>Accounting Obligations</h3>

<h4>Simplified Accounting (turnover < €500,000)</h4>
<p>3 mandatory journals:</p>
<ol>
    <li><strong>Purchase journal:</strong> list of expenses</li>
    <li><strong>Sales journal:</strong> chronological overview of invoices</li>
    <li><strong>Cash journal:</strong> cash book + bank book</li>
</ol>

<p><strong>Document retention:</strong> 10 years</p>

<h2>Official Sources</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">FPS Economy - Steps for a self-employed worker</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub for entrepreneurs</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Social security contributions</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">FPS Finance - VAT franchise regime</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Summary</p>
    <p>Becoming self-employed in Belgium costs between €200 and €500 and takes 1 to 2 weeks. Social security contributions represent 20.5% of income. The VAT franchise is possible if turnover stays below €25,000/year.</p>
</div>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'sole-proprietorship-france-guide-2026',
                'locale' => 'en',
                'translation_key' => 'creer-entreprise-individuelle-france-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">France offers a simplified framework for creating your sole proprietorship, particularly with the micro-enterprise regime. Since 2023, all formalities are done via the INPI one-stop shop. Discover the steps, costs and obligations to get started.</p>

<h2>Legal Forms for Sole Proprietorship</h2>

<h3>Sole Proprietorship (EI)</h3>

<p>The sole proprietorship allows you to carry out an activity in your own name, without creating a legal entity.</p>

<ul>
    <li>No share capital required</li>
    <li>No articles of association to draft</li>
    <li>Possible activities: commercial, artisanal, agricultural or liberal</li>
    <li><strong>Since February 2022</strong>: personal and professional assets are automatically separated</li>
</ul>

<h3>Micro-enterprise (Auto-entrepreneur)</h3>

<p>The micro-enterprise is a simplified regime of the sole proprietorship with turnover thresholds:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Activity Type</th>
            <th class="text-left p-2 bg-slate-100">Turnover Threshold (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Sale of goods, accommodation</td><td class="p-2 border-b">€203,100</td></tr>
        <tr><td class="p-2 border-b">Services</td><td class="p-2 border-b">€83,600</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Good to know</p>
    <p>The EIRL no longer exists since May 15, 2022. The new EI status now automatically includes asset separation.</p>
</div>

<h2>Requirements and Prerequisites</h2>

<h3>Personal Requirements</h3>

<ul>
    <li>Be of <strong>legal age</strong> (or emancipated minor)</li>
    <li>Have an <strong>address in France</strong></li>
    <li>Not be under guardianship or curatorship</li>
    <li>Not be subject to a management ban</li>
    <li>Be of French, European nationality, or have a residence permit authorizing work</li>
</ul>

<h3>Regulated Activities</h3>

<p>Certain professions require specific diplomas or qualifications: hairdressing, construction, healthcare professions, etc.</p>

<h2>Creation Steps via the INPI One-Stop Shop</h2>

<h3>Step 1: Document Preparation</h3>
<ul>
    <li>ID document (identity card or passport) in PDF format</li>
    <li>Proof of address (if activity carried out at home)</li>
    <li>Qualification certificates for regulated activities</li>
</ul>

<h3>Step 2: Account Creation</h3>
<p>Go to <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> and create an account via France Connect (recommended) or an INPI identifier.</p>

<h3>Step 3: Activity Declaration</h3>
<ol>
    <li>Click on "Declare"</li>
    <li>Select "Sole proprietor"</li>
    <li>Enter: nature of activity, address, start date, tax and social options</li>
</ol>

<h3>Step 4: Validation and Tracking</h3>
<ul>
    <li>Attach supporting documents</li>
    <li>Make payment if necessary</li>
    <li>Track progress from the dashboard</li>
    <li>Automatic registration in the RNE (National Business Register)</li>
</ul>

<h2>Creation Costs</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Activity Type</th>
            <th class="text-left p-2 bg-slate-100">Cost</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Commercial activity</td><td class="p-2 border-b text-green-600 font-semibold">Free</td></tr>
        <tr><td class="p-2 border-b">Artisanal activity</td><td class="p-2 border-b text-green-600 font-semibold">Free</td></tr>
        <tr><td class="p-2 border-b">Liberal profession</td><td class="p-2 border-b text-green-600 font-semibold">Free</td></tr>
        <tr><td class="p-2 border-b">Commercial agent</td><td class="p-2 border-b">€23.86</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Warning</p>
    <p>Beware of private websites charging fees for a normally free service.</p>
</div>

<h2>Average Timelines</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Step</th>
            <th class="text-left p-2 bg-slate-100">Timeline</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Online declaration</td><td class="p-2 border-b">A few minutes</td></tr>
        <tr><td class="p-2 border-b">Receipt of filing</td><td class="p-2 border-b">24 hours</td></tr>
        <tr><td class="p-2 border-b">Obtaining SIRET number</td><td class="p-2 border-b font-semibold">1 to 2 weeks</td></tr>
        <tr><td class="p-2 border-b">URSSAF notification</td><td class="p-2 border-b">4 to 10 weeks</td></tr>
    </tbody>
</table>

<h2>Obligations After Creation</h2>

<h3>URSSAF Contributions</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Activity Type</th>
            <th class="text-left p-2 bg-slate-100">Rate 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Buy-resell</td><td class="p-2 border-b">12.3%</td></tr>
        <tr><td class="p-2 border-b">Commercial/artisanal services</td><td class="p-2 border-b">21.2%</td></tr>
        <tr><td class="p-2 border-b">Other services</td><td class="p-2 border-b">25.6%</td></tr>
        <tr><td class="p-2 border-b">Liberal professions (Cipav)</td><td class="p-2 border-b">23.2%</td></tr>
    </tbody>
</table>

<p><strong>Frequency:</strong> Monthly or quarterly declaration (choice). Obligation to declare even if turnover is zero.</p>

<h3>VAT - Basic Franchise</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Activity Type</th>
            <th class="text-left p-2 bg-slate-100">Basic Threshold</th>
            <th class="text-left p-2 bg-slate-100">Increased Threshold</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Sale/Trading/Accommodation</td><td class="p-2 border-b">€85,000</td><td class="p-2 border-b">€93,500</td></tr>
        <tr><td class="p-2 border-b">Services</td><td class="p-2 border-b">€37,500</td><td class="p-2 border-b">€41,250</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mandatory mention under franchise</p>
    <p>"VAT not applicable, Art. 293 B of the CGI"</p>
</div>

<h3>CFE (Business Property Tax)</h3>

<ul>
    <li><strong>1st year:</strong> Exempt from payment</li>
    <li><strong>Total exemption:</strong> If annual turnover < €5,000</li>
    <li><strong>Obligation:</strong> File declaration no. 1447-C before December 31 of the 1st year</li>
</ul>

<h3>Accounting Obligations</h3>

<ol>
    <li>Issue <strong>compliant invoices</strong> for each sale/service</li>
    <li>Keep a chronological <strong>receipts book</strong></li>
    <li>Keep a <strong>purchase register</strong> (if sales activity)</li>
    <li><strong>Retain supporting documents</strong> for 10 years</li>
</ol>

<h2>Available Aid</h2>

<h3>ACRE (Aid for Business Creators)</h3>

<ul>
    <li><strong>Partial exemption</strong> from social contributions in the 1st year (up to 50%)</li>
    <li>Conditions: job seekers, RSA recipients, young people aged 18-25, etc.</li>
    <li>Application to be made at creation or within 45 days</li>
</ul>

<h2>Official Sources</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Sole Proprietor</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">One-Stop Shop for Business Formalities</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-entrepreneur</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Create your sole proprietorship</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Summary</p>
    <p>Creating a micro-enterprise in France is free and fast (SIRET in 1-2 weeks). Social contributions vary from 12 to 26% depending on activity. The VAT franchise allows you not to charge VAT under certain thresholds.</p>
</div>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'sole-proprietorship-germany-guide-2026',
                'locale' => 'en',
                'translation_key' => 'creer-entreprise-individuelle-allemagne-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Germany offers several options for creating a sole proprietorship, with relatively simple and fast procedures. This guide presents the different legal forms and the steps to get started.</p>

<h2>Legal Forms for Sole Proprietorship</h2>

<h3>Einzelunternehmen (Sole Proprietorship)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Characteristic</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definition</td><td class="p-2 border-b">Business managed by one person</td></tr>
        <tr><td class="p-2 border-b">Minimum capital</td><td class="p-2 border-b">Not required</td></tr>
        <tr><td class="p-2 border-b">Liability</td><td class="p-2 border-b"><strong>Unlimited</strong></td></tr>
        <tr><td class="p-2 border-b">Creation</td><td class="p-2 border-b">Gewerbeanmeldung + tax number</td></tr>
        <tr><td class="p-2 border-b">Taxation</td><td class="p-2 border-b">Income tax + Gewerbesteuer (if > €24,500/year)</td></tr>
    </tbody>
</table>

<p><strong>Subcategories:</strong></p>
<ul>
    <li><strong>Kleingewerbetreibender:</strong> Small trader, no commercial register entry</li>
    <li><strong>Eingetragener Kaufmann (e.K.):</strong> Registered in the commercial register</li>
</ul>

<h3>Freiberufler (Liberal Profession)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Characteristic</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definition</td><td class="p-2 border-b">Intellectual, creative, scientific or educational activity</td></tr>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b text-green-600">NOT required</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b text-green-600">NOT applicable</td></tr>
        <tr><td class="p-2 border-b">IHK/HWK</td><td class="p-2 border-b text-green-600">No mandatory contribution</td></tr>
        <tr><td class="p-2 border-b">Registration</td><td class="p-2 border-b">Directly at the Finanzamt</td></tr>
    </tbody>
</table>

<p><strong>Professions concerned (Katalogberufe):</strong> doctors, lawyers, architects, engineers, journalists, translators, artists, teachers...</p>

<h2>Requirements and Prerequisites</h2>

<h3>For Gewerbetreibende</h3>

<ul>
    <li><strong>Minimum age:</strong> 18 years (majority)</li>
    <li><strong>Residence:</strong> Address in Germany</li>
    <li><strong>Documents:</strong> Passport or identity card</li>
    <li><strong>Legal activity:</strong> Activity authorized by law</li>
</ul>

<h3>Possible Additional Documents</h3>

<ul>
    <li><strong>Führungszeugnis</strong> (criminal record extract): ~€13</li>
    <li><strong>Gewerbezentralregisterauszug:</strong> ~€13</li>
    <li><strong>Craftsman's card:</strong> €80-250</li>
</ul>

<h2>Creation Steps</h2>

<h3>Path A: Gewerbetreibender</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Step 1: Gewerbeanmeldung (Gewerbeamt)<br>
        ↓<br>
        Step 2: Automatic notifications (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>
        ↓<br>
        Step 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>
        ↓<br>
        Step 4: Tax number assignment<br>
        ↓<br>
        Step 5: Berufsgenossenschaft registration (7 days)
    </p>
</div>

<h4>Gewerbeanmeldung</h4>
<ul>
    <li><strong>Where:</strong> Gewerbeamt of the municipality of the headquarters</li>
    <li><strong>Form:</strong> GewA 1</li>
    <li><strong>Method:</strong> Online (Gewerbe-Service-Portal) or in person</li>
    <li><strong>Timeline:</strong> 1-3 days</li>
</ul>

<h3>Path B: Freiberufler</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Step 1: Registration at Finanzamt (within 4 weeks of starting)<br>
        ↓<br>
        Step 2: Fragebogen zur steuerlichen Erfassung<br>
        ↓<br>
        Step 3: Tax number assignment
    </p>
</div>

<h2>Creation Costs</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Item</th>
            <th class="text-left p-2 bg-slate-100">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung (base)</td><td class="p-2 border-b">€12.50 - 60</td></tr>
        <tr><td class="p-2 border-b">Large cities (Munich, Stuttgart)</td><td class="p-2 border-b">€50 - 60</td></tr>
        <tr><td class="p-2 border-b">Small municipalities</td><td class="p-2 border-b">€15 - 30</td></tr>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600 font-semibold">€0 (free)</td></tr>
    </tbody>
</table>

<h2>Average Timelines</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Step</th>
            <th class="text-left p-2 bg-slate-100">Timeline</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung processing</td><td class="p-2 border-b font-semibold">1-3 days</td></tr>
        <tr><td class="p-2 border-b">Written confirmation from Gewerbeamt</td><td class="p-2 border-b">Maximum 3 days</td></tr>
        <tr><td class="p-2 border-b">Receiving Fragebogen from Finanzamt</td><td class="p-2 border-b">4-6 weeks</td></tr>
        <tr><td class="p-2 border-b">Tax number assignment</td><td class="p-2 border-b">2-4 weeks</td></tr>
        <tr><td class="p-2 border-b font-semibold">Total timeline</td><td class="p-2 border-b font-semibold">6-10 weeks</td></tr>
    </tbody>
</table>

<h2>Obligations After Creation</h2>

<h3>VAT / Umsatzsteuer</h3>

<h4>Normal Regime</h4>
<ul>
    <li><strong>Standard rate:</strong> 19%</li>
    <li><strong>Reduced rate:</strong> 7%</li>
    <li>Monthly or quarterly declaration (Umsatzsteuer-Voranmeldung)</li>
</ul>

<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Criterion</th>
            <th class="text-left p-2 bg-slate-100">Threshold 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Previous year turnover</td><td class="p-2 border-b">≤ €25,000</td></tr>
        <tr><td class="p-2 border-b">Current year turnover</td><td class="p-2 border-b">≤ €100,000</td></tr>
    </tbody>
</table>

<p><strong>Advantages:</strong></p>
<ul>
    <li>No VAT billing</li>
    <li>No VAT declarations</li>
    <li>Simplified accounting</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mandatory mention on invoices</p>
    <p>"Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG"<br>
    <em>(No VAT indicated, small business regime according to § 19 UStG)</em></p>
</div>

<h3>Gewerbesteuer (Trade Tax)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600">Exempt</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender < €24,500/year</td><td class="p-2 border-b text-green-600">Exempt (Freibetrag)</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender ≥ €24,500/year</td><td class="p-2 border-b">Subject to tax</td></tr>
    </tbody>
</table>

<h3>Social Security Contributions</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Krankenversicherung (health)</td><td class="p-2 border-b text-red-600 font-semibold">MANDATORY</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung (long-term care)</td><td class="p-2 border-b text-red-600 font-semibold">MANDATORY</td></tr>
        <tr><td class="p-2 border-b">Rentenversicherung (pension)</td><td class="p-2 border-b">Optional*</td></tr>
        <tr><td class="p-2 border-b">Arbeitslosenversicherung (unemployment)</td><td class="p-2 border-b">Optional</td></tr>
    </tbody>
</table>

<p><small>*Mandatory for certain professions (craftsmen, teachers, carers)</small></p>

<h3>IHK/HWK Contribution</h3>

<ul>
    <li>Automatic and mandatory membership for Gewerbetreibende</li>
    <li>Exemption if Gewerbeertrag < €5,200/year</li>
    <li>Progressive contribution beyond</li>
</ul>

<h2>Comparison Table</h2>

<table class="w-full my-4 text-sm">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Criterion</th>
            <th class="text-left p-2 bg-slate-100">Einzelunternehmen</th>
            <th class="text-left p-2 bg-slate-100">Freiberufler</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b">Yes</td><td class="p-2 border-b text-green-600">No</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b">Yes (> €24,500)</td><td class="p-2 border-b text-green-600">No</td></tr>
        <tr><td class="p-2 border-b">IHK membership</td><td class="p-2 border-b">Mandatory</td><td class="p-2 border-b text-green-600">No</td></tr>
        <tr><td class="p-2 border-b">Creation cost</td><td class="p-2 border-b">€12.50-60</td><td class="p-2 border-b text-green-600">€0</td></tr>
        <tr><td class="p-2 border-b">Creation time</td><td class="p-2 border-b">1-3 days</td><td class="p-2 border-b text-green-600">Immediate</td></tr>
    </tbody>
</table>

<h2>Official Sources</h2>

<ul>
    <li><a href="https://www.existenzgruendungsportal.de/" target="_blank" rel="noopener">Existenzgründungsportal (BMWK)</a></li>
    <li><a href="https://www.bmwk.de/" target="_blank" rel="noopener">Federal Ministry for Economic Affairs (BMWK)</a></li>
    <li><a href="https://www.ihk.de/" target="_blank" rel="noopener">IHK - Chamber of Industry and Commerce</a></li>
    <li><a href="https://www.deutsche-rentenversicherung.de/" target="_blank" rel="noopener">German Pension Insurance</a></li>
    <li><a href="https://gruenderplattform.de/" target="_blank" rel="noopener">Gründerplattform</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Summary</p>
    <p>Creating a sole proprietorship in Germany costs between €0 and €60 depending on status. The Gewerbeanmeldung is processed in 1-3 days. The Kleinunternehmerregelung allows VAT exemption under certain thresholds. Freiberufler benefit from a simplified regime without Gewerbesteuer or IHK contribution.</p>
</div>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'sole-proprietorship-luxembourg-guide-2026',
                'locale' => 'en',
                'translation_key' => 'creer-entreprise-individuelle-luxembourg-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Luxembourg offers a favorable environment for entrepreneurs with relatively simple administrative procedures and moderate creation costs. This guide accompanies you step by step in creating your sole proprietorship in the Grand Duchy.</p>

<h2>Legal Forms for Sole Proprietorship</h2>

<p>In Luxembourg, the independent entrepreneur exercises their profession in their own name as:</p>

<ul>
    <li><strong>Trader</strong>: for commercial activities</li>
    <li><strong>Craftsperson</strong>: for artisanal activities</li>
    <li><strong>Independent intellectual worker</strong>: for liberal professions</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Note</p>
    <p>There is no exact equivalent to the French auto-entrepreneur status in Luxembourg. The sole proprietorship is the closest and simplest form.</p>
</div>

<h3>Main Characteristics</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspect</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Legal personality</td><td class="p-2 border-b">None - the entrepreneur acts in their own name</td></tr>
        <tr><td class="p-2 border-b">Minimum capital</td><td class="p-2 border-b">No minimum capital required</td></tr>
        <tr><td class="p-2 border-b">Liability</td><td class="p-2 border-b"><strong>Unlimited</strong> - liable with personal assets</td></tr>
        <tr><td class="p-2 border-b">Formalism</td><td class="p-2 border-b">Minimal - no articles of association required</td></tr>
    </tbody>
</table>

<h2>Requirements and Prerequisites</h2>

<h3>Establishment Authorization (Mandatory)</h3>

<p>Any economic activity carried out on a regular basis requires a <strong>prior establishment authorization</strong>.</p>

<p><strong>Conditions to meet:</strong></p>

<ul>
    <li><strong>Physical establishment</strong>: appropriate material installation in Luxembourg</li>
    <li><strong>Effective management</strong>: physical presence and daily management by the holder</li>
    <li><strong>Professional honorability</strong>: clean criminal record, compliance with previous tax and social obligations</li>
    <li><strong>Professional qualification</strong>: depending on the targeted activity</li>
</ul>

<h3>Required Professional Qualifications</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Activity Type</th>
            <th class="text-left p-2 bg-slate-100">Required Qualification</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Commercial activities</td><td class="p-2 border-b">Generally no specific diploma required</td></tr>
        <tr><td class="p-2 border-b">Artisanal activities</td><td class="p-2 border-b">DAP, CATP or Master's Certificate</td></tr>
        <tr><td class="p-2 border-b">Liberal professions</td><td class="p-2 border-b">Specific diplomas depending on profession</td></tr>
    </tbody>
</table>

<h2>Detailed Creation Steps</h2>

<h3>Step 1: Project Development</h3>
<ul>
    <li>Draft a business plan</li>
    <li>Contact support organizations (House of Entrepreneurship, Chamber of Commerce, Chamber of Trades)</li>
</ul>

<h3>Step 2: Verify Prerequisites</h3>
<ul>
    <li>Check availability of trade name</li>
    <li>Ensure you have the required qualifications</li>
    <li>Apply for diploma recognition if necessary</li>
</ul>

<h3>Step 3: Establishment Authorization Application</h3>
<p><strong>Where:</strong> Online via MyGuichet.lu (with LuxTrust certificate) or by mail</p>
<p><strong>Required documents:</strong></p>
<ul>
    <li>Application form</li>
    <li>Professional qualification certificates</li>
    <li>Criminal record extract (bulletin no. 3)</li>
    <li>Copy of ID card</li>
    <li>Proof of payment of chancery fee (50 EUR)</li>
</ul>

<h3>Step 4: RCS Registration</h3>
<p><strong>Where:</strong> Electronic filing on the LBR website (Luxembourg Business Registers)</p>
<p><strong>Required documents:</strong></p>
<ul>
    <li>Registration form</li>
    <li>Establishment authorization</li>
    <li>ID document</li>
    <li>Marriage certificate / marriage contract (if applicable)</li>
</ul>

<h3>Step 5: Social Security Affiliation</h3>
<p>Registration with CCSS (Centre Commun de la Sécurité Sociale) as self-employed.</p>

<h3>Step 6: Tax Registration</h3>
<ul>
    <li>Registration with the Administration des Contributions Directes</li>
    <li>VAT registration if turnover > 50,000 EUR</li>
</ul>

<h2>Creation Costs</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Item</th>
            <th class="text-left p-2 bg-slate-100">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Establishment authorization</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">RCS registration</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Diploma recognition</td><td class="p-2 border-b">75 EUR (if required)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Estimated total</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Average Timelines</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Procedure</th>
            <th class="text-left p-2 bg-slate-100">Timeline</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Establishment authorization</td><td class="p-2 border-b">Up to 3 months</td></tr>
        <tr><td class="p-2 border-b">Diploma recognition</td><td class="p-2 border-b">2 to 6 weeks</td></tr>
        <tr><td class="p-2 border-b">RCS registration</td><td class="p-2 border-b">A few days</td></tr>
        <tr><td class="p-2 border-b font-semibold">Estimated total timeline</td><td class="p-2 border-b font-semibold">1 to 3 months</td></tr>
    </tbody>
</table>

<h2>Obligations After Creation</h2>

<h3>VAT (Value Added Tax)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Annual turnover ≤ 50,000 EUR</td><td class="p-2 border-b">VAT exemption (no registration required)</td></tr>
        <tr><td class="p-2 border-b">Annual turnover > 50,000 EUR</td><td class="p-2 border-b">Mandatory registration + periodic declarations</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mandatory mention under franchise</p>
    <p>"VAT not applicable, Art. 57 of the Luxembourg VAT Code (Tax exemption regime)"</p>
</div>

<h3>Social Security Contributions (CCSS)</h3>

<p>Contributions represent approximately <strong>25.3%</strong> of income, broken down as follows:</p>

<ul>
    <li>Health insurance (benefits in kind): 5.60%</li>
    <li>Health insurance (cash benefits): 0.50%</li>
    <li>Long-term care insurance: 1.40%</li>
    <li>Pension insurance: 17.00%</li>
    <li>Accident insurance: 0.65%</li>
    <li>Occupational health: 0.14%</li>
</ul>

<h3>Accounting</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Annual Turnover</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100,000 EUR net</td><td class="p-2 border-b">Simplified accounting</td></tr>
        <tr><td class="p-2 border-b">≥ 100,000 EUR net</td><td class="p-2 border-b">Standardized accounting mandatory</td></tr>
    </tbody>
</table>

<h2>Official Sources</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html" target="_blank" rel="noopener">Guichet.lu - Sole proprietorship</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Establishment authorization</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Self-employed</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Summary</p>
    <p>Creating a sole proprietorship in Luxembourg is relatively simple and inexpensive (approximately 100-150 EUR). The process typically takes 1 to 3 months and includes obtaining the establishment authorization and RCS registration. Social contributions represent about 25% of income.</p>
</div>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'vat-luxembourg-rates-calculation-obligations',
                'locale' => 'en',
                'translation_key' => 'tva-luxembourg-taux-calcul-obligations',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">VAT (Value Added Tax) is a central element of Luxembourg taxation. Understanding the different rates, knowing how to apply them correctly, and respecting declaration obligations is essential for any business.</p>

<h2>VAT Rates in Luxembourg 2026</h2>

<p>Luxembourg applies <strong>four VAT rates</strong>, among the lowest in the European Union:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Rate</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Name</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">17%</td>
            <td class="border border-gray-300 px-4 py-2">Standard rate</td>
            <td class="border border-gray-300 px-4 py-2">Majority of goods and services</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">14%</td>
            <td class="border border-gray-300 px-4 py-2">Intermediate rate</td>
            <td class="border border-gray-300 px-4 py-2">Wines, solid fuels, advertising prints</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">8%</td>
            <td class="border border-gray-300 px-4 py-2">Reduced rate</td>
            <td class="border border-gray-300 px-4 py-2">Gas, electricity, hairdressing</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">3%</td>
            <td class="border border-gray-300 px-4 py-2">Super-reduced rate</td>
            <td class="border border-gray-300 px-4 py-2">Food, books, medicines, transport</td>
        </tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold text-blue-800">ℹ️ Did you know?</p>
    <p class="text-blue-700">The standard rate of 17% in Luxembourg is the lowest in the European Union, where the average is around 21%.</p>
</div>

<h2>Rate Details by Category</h2>

<h3>Super-reduced rate of 3%</h3>

<ul>
    <li>Food products (excluding alcohol and restaurants)</li>
    <li>Books, newspapers, and periodicals</li>
    <li>Medicines</li>
    <li>Passenger transport</li>
    <li>Hotel accommodation</li>
    <li>Admission to cultural and sporting events</li>
    <li>Medical and dental care (non-exempt)</li>
</ul>

<h3>Reduced rate of 8%</h3>

<ul>
    <li>Supply of natural gas and electricity</li>
    <li>Hairdressing services</li>
    <li>Window cleaning</li>
    <li>Small repair services (bicycles, shoes, clothing)</li>
</ul>

<h3>Intermediate rate of 14%</h3>

<ul>
    <li>Wines (less than 13% alcohol)</li>
    <li>Solid mineral fuels</li>
    <li>Heating oil</li>
    <li>Certain advertising prints</li>
</ul>

<h3>Standard rate of 17%</h3>

<p>All goods and services that do not benefit from a reduced rate are subject to the standard rate of 17%.</p>

<h2>VAT-Exempt Transactions</h2>

<p>Certain transactions are <strong>exempt from VAT</strong> in Luxembourg:</p>

<ul>
    <li>Medical and paramedical services</li>
    <li>Educational services</li>
    <li>Banking and financial operations</li>
    <li>Insurance operations</li>
    <li>Real estate rental (except with option)</li>
    <li>Intra-community deliveries (under conditions)</li>
    <li>Exports outside the EU</li>
</ul>

<h2>VAT Calculation</h2>

<h3>Calculating VAT from Net Amount</h3>

<p>To calculate the gross amount from the net price:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Gross Amount = Net Amount × (1 + VAT rate)</p>
    <p class="mt-2 text-sm text-gray-600">Example: €100 net × 1.17 = €117 gross</p>
</div>

<h3>Calculating Net from Gross Amount</h3>

<p>To find the net amount from gross:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Net Amount = Gross Amount ÷ (1 + VAT rate)</p>
    <p class="mt-2 text-sm text-gray-600">Example: €117 gross ÷ 1.17 = €100 net</p>
</div>

<h2>Declaration Obligations</h2>

<p>The filing frequency depends on your net turnover. <strong>The AED determines your regime</strong> — you do not choose it.</p>

<ul>
    <li><strong>Turnover below €112,000</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover above €620,000</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ The annual return does not replace the periodic ones</p>
    <p class="text-amber-700">If you file quarterly or monthly returns, you must <strong>also</strong> file a recapitulative annual return, due before <strong>1 May</strong> of the following year. Forgetting it triggers late-filing penalties even though you paid all your VAT during the year.</p>
</div>

<p>Periodic returns are due before the 15th of the following month, filed online through <strong>eCDF</strong>. Payment accompanies the return; where you have a VAT credit, a refund may be requested.</p>
<h2>Intra-Community VAT</h2>

<h3>Sales to EU Businesses (B2B)</h3>

<p>Deliveries of goods and services to taxable persons in other EU countries are <strong>exempt from Luxembourg VAT</strong>. The customer self-assesses the VAT in their country (reverse charge).</p>

<p><strong>Conditions:</strong></p>
<ul>
    <li>The customer must have a valid intra-community VAT number</li>
    <li>This number must appear on the invoice</li>
    <li>The mention "VAT exemption - Article 43 paragraph 1 k) of the VAT Law" must appear</li>
</ul>

<h3>Sales to EU Individuals (B2C)</h3>

<p>A <strong>single threshold of €10,000 per year</strong> governs these sales. Below it you charge Luxembourg VAT; above it, the VAT of the client's country, declared through the <strong>OSS</strong> one-stop shop or by registering in each country.</p>

<p><strong>Careful:</strong> this threshold is <strong>shared</strong> between distance sales of goods <em>and</em> electronic, telecommunications and broadcasting services — across all EU countries combined, excluding Luxembourg. What you must track is therefore the <strong>combined</strong> total of your European B2C sales, not each category separately. The transaction that crosses the threshold is already taxable in the client's country.</p>
<h2>The Intra-Community VAT Number</h2>

<p>The Luxembourg VAT number has the format <strong>LU + 8 digits</strong> (e.g.: LU12345678).</p>

<p>This number must appear on:</p>
<ul>
    <li>All your invoices</li>
    <li>Your VAT declarations</li>
    <li>Your intra-community trade declarations (DEB)</li>
</ul>

<h2>VAT Recovery</h2>

<p>As a taxable person, you can <strong>deduct the VAT</strong> paid on your business purchases. For this:</p>

<ul>
    <li>You must have a <strong>compliant invoice</strong></li>
    <li>The purchase must be related to your <strong>professional activity</strong></li>
    <li>The VAT must be <strong>correctly stated</strong> on the invoice</li>
</ul>

<h2>Practical Tips</h2>

<ol>
    <li><strong>Always check the applicable rate</strong> before invoicing</li>
    <li><strong>Validate VAT numbers</strong> of your EU customers on the VIES website</li>
    <li><strong>Keep your invoices for 10 years</strong> to justify your deductions</li>
    <li><strong>Use appropriate software</strong> to avoid calculation errors</li>
    <li><strong>Plan your declarations ahead</strong> to avoid late penalties</li>
</ol>

<h2>Common special cases</h2>

<h3>Restaurants: 3% and 17% on the same bill</h3>
<p>Meals served on the premises are taxed at <strong>3%</strong>, but <strong>alcoholic drinks</strong> remain at <strong>17%</strong>. Both rates therefore appear on the same bill, and the split must be shown.</p>

<h3>Hotels: 3% whatever the rating</h3>
<p>Unlike other countries, Luxembourg applies a uniform <strong>3%</strong> to every night's stay, from guesthouse to luxury hotel. Ancillary services (spa, in-house restaurant) follow their own regime.</p>

<h3>E-books: 3%</h3>
<p>Digital books enjoy the same rate as printed ones, namely <strong>3%</strong>. Video or music streaming subscriptions, by contrast, stay at <strong>17%</strong>: those are digital services, not books.</p>

<h3>Renovation work: the condition everyone forgets</h3>
<p>Renovation of a dwelling used as a main residence can qualify for the super-reduced rate of <strong>3%</strong>, within limits and conditions set by grand-ducal regulation (age of the building, tax-relief ceiling per dwelling).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Authorisation must be requested BEFORE the works</p>
    <p class="text-red-700"><strong>Article 65bis of the VAT law</strong> is explicit: a taxable person carrying out such work "must request authorisation from the administration for the application of the super-reduced rate", and that request "must be submitted […] <strong>before the works are carried out</strong>". Doing the job first and claiming 3% afterwards does not work. On a renovation, the gap between 3% and 17% runs into thousands of euros.</p>
</div>

<h2>Getting the rate wrong: the consequences</h2>

<ul>
    <li><strong>Rate too low</strong>: reassessment by the AED, late-payment interest, and an administrative fine of <strong>€250 to €10,000 per infringement</strong> (art. 77 LIVA)</li>
    <li><strong>Breach that deprived the Treasury of revenue</strong>: fine of <strong>10% to 50% of the VAT at stake</strong> — proportional, hence uncapped</li>
    <li><strong>Rate too high</strong>: the customer can claim a refund, and you must correct it with the AED</li>
    <li><strong>Deduction refused on the customer's side</strong>: if the rate is manifestly wrong, your customer may be denied recovery</li>
    <li><strong>Deliberate fraud</strong>: criminal fine of €25,000 to ten times the VAT amount, and imprisonment from one month to five years (art. 80 LIVA)</li>
</ul>

<h2>Frequently asked questions</h2>

<h3>Is the standard rate really 17% in 2026?</h3>
<p>Yes. The rate was temporarily lowered to 16% in 2023; it returned to <strong>17%</strong> on 1 January 2024 and has not changed since.</p>

<h3>Which rate applies if my invoice straddles a rate change?</h3>
<p>The date of the <strong>chargeable event</strong> governs — that is, the date of delivery or performance, not the invoice date nor the payment date.</p>

<h3>How do I justify a special rate during an audit?</h3>
<p>Keep whatever ties the transaction to the category: the exact nature of the goods or service and, for renovation work, <strong>the authorisation obtained before the job started</strong>. Without it, the 3% is refused even if the work would otherwise have qualified.</p>

<h3>Is my activity exempt?</h3>
<p>Exemptions are exhaustively listed in the law and are never presumed. When in doubt, settle it with your accountant before issuing the first invoice — an exemption wrongly applied is hard to undo.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - rates, annexes A and B, articles 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu - VAT returns and filing frequencies</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
</ul>
<h2>Conclusion</h2>

<p>Managing VAT in Luxembourg requires good knowledge of applicable rates and declaration obligations. By using invoicing software like faktur.lu, you benefit from automatic application of correct rates and legally compliant invoices.</p>
<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'eenzelentreprise-belgien-grenden-guide-2026',
                'locale' => 'lb',
                'translation_key' => 'creer-entreprise-individuelle-belgique-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Belsch bitt e favorable Kader fir Selbstänneger mat vereinfachte Prozeduren zënter der Ofschafung vun de Grondkenntnisser an der Entrepriseverwaltung. Dëse Guide begleet Iech bei der Grëndung vun Ärer Entreprise als natierlech Persoun.</p>

<h2>Rechtsform: Entreprise als natierlech Persoun</h2>

<p>D'Entreprise als natierlech Persoun (Selbstännegen) ass déi einfachst Form fir eleng eng wirtschaftlech Aktivitéit a Belsch auszeüben.</p>

<h3>Haaptcharakteristiken</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspekt</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Grëndungsakt</td><td class="p-2 border-b">Net erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Net erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Onbegrenzt</strong> - privat a berufflecht Verméige vermëscht</td></tr>
        <tr><td class="p-2 border-b">Statistik</td><td class="p-2 border-b">43% vun de belsche KMU (510.346 Entreprisen)</td></tr>
    </tbody>
</table>

<h2>Viraussetzungen</h2>

<h3>Allgemeng Viraussetzungen</h3>

<ul>
    <li>Mindestens <strong>18 Joer</strong> al sinn</li>
    <li>Bierger- a politesch Rechter genéissen</li>
    <li>Rechtlech handlungsfäeg sinn</li>
</ul>

<h3>Grondkenntnisser an der Entrepriseverwaltung: OFGESCHAAFT</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Gutt Neiegkeet!</p>
    <p>D'Grondkenntnisser an der Entrepriseverwaltung goufen an alle Regioune ofgeschaaft:</p>
    <ul class="mt-2">
        <li><strong>Flandern:</strong> zënter 2018</li>
        <li><strong>Bréissel:</strong> zënter 15. Januar 2024</li>
        <li><strong>Wallonie:</strong> zënter 1. Oktober 2025</li>
    </ul>
</div>

<h3>Beruffszougang</h3>

<p>Bestëmmt reguléiert Beruffer erfuerderen nach ëmmer <strong>spezifesch Beruffskompetenze</strong>: Coiffer, Bäcker, Pâtissier, Automechaniker, Daachdecken, Heizungsbauer, Gastronom, asw.</p>

<h2>Grëndungsschrëtt</h2>

<h3>Schrëtt 1: Geschäftskont opmaachen</h3>
<p>Flicht fir geschäftlech a privat Transaktiounen ze trennen.</p>

<h3>Schrëtt 2: Aschreiwung an der Banque-Carrefour des Entreprises (BCE)</h3>
<ul>
    <li>Iwwer en <strong>zougeloossenen Entrepriseschalter</strong></li>
    <li>Erhalen vun der <strong>Entreprisesnummer</strong> (eenzegaarteg Identifiant)</li>
    <li>Préifung vun de Beruffskompetenze wann néideg</li>
</ul>

<h3>Schrëtt 3: TVA-Nummer aktivéieren</h3>
<ul>
    <li>Bei der Allgemenger Steierverwaltung</li>
    <li>Kann iwwer den Entrepriseschalter gemaach ginn</li>
    <li>Méiglechkeet d'TVA-Franchiseregime unzefroen (bei Ëmsaz < 25.000 €)</li>
</ul>

<h3>Schrëtt 4: Umelle bei enger Sozialversécherungskees</h3>
<p><strong>Flicht VIRUM Ufank vun der Aktivitéit</strong>. Umeldung bis zu 6 Méint am viraus méiglech.</p>

<h3>Schrëtt 5: Umelle bei enger Krankekees</h3>
<p>Flicht fir vun der Kranke- a Invaliditéitsversécherung ze profitéieren.</p>

<h3>Schrëtt 6: Néideg Versécherungen ofschléissen</h3>
<p>Beruffshaftpflichtversécherung an aner jee no Aktivitéit.</p>

<h2>Déi 8 zougeloossen Entrepriseschalter</h2>

<ol>
    <li>Liantis (de gréissten)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Eunomia</li>
    <li>Formalis</li>
</ol>

<h2>Grëndungskäschten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">BCE-Aschreiwung iwwer Schalter</td><td class="p-2 border-b">109 - 111,50 € (TVA-befreit)</td></tr>
        <tr><td class="p-2 border-b">Divers Käschten</td><td class="p-2 border-b">Variabel</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschate Gesamtbudget</td><td class="p-2 border-b font-semibold">200 - 500 €</td></tr>
    </tbody>
</table>

<h2>Duerchschnëttlech Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Prozedur</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">BCE-Aschreiwung iwwer Schalter</td><td class="p-2 border-b">Direkt bis e puer Deeg</td></tr>
        <tr><td class="p-2 border-b">TVA-Aktivéierung</td><td class="p-2 border-b">E puer Deeg</td></tr>
        <tr><td class="p-2 border-b">Sozialkeesumeldung</td><td class="p-2 border-b">Direkt</td></tr>
        <tr><td class="p-2 border-b font-semibold">Komplette Prozess</td><td class="p-2 border-b font-semibold">1 bis 2 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten no der Grëndung</h2>

<h3>TVA</h3>

<h4>Normalregime</h4>
<ul>
    <li>Periodesch TVA-Erklärung (méintlech oder trimesteriell)</li>
    <li>Fakturéiere mat TVA</li>
    <li>Jäerlech Clientelëscht</li>
</ul>

<h4>Franchiseregime (bei Ëmsaz < 25.000 €)</h4>
<ul>
    <li>Keng periodesch Erklärung</li>
    <li>Keng TVA ze berechnen oder ze bezuelen</li>
    <li>Mëtteelung vum Joresëmsaz virum 31. Mäerz</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Flichtmentioun bei Franchise</p>
    <p>„Kleng Entreprise ënnerworf dem Steierbefreiungsregime - TVA net applicabel (Art. 56bis vum TVA-Gesetz)"</p>
</div>

<h3>Sozialversécherungsbeiträg (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Akommesstufe</th>
            <th class="text-left p-2 bg-slate-100">Taux 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">0 bis 73.447,52 €</td><td class="p-2 border-b font-semibold">20,50%</td></tr>
        <tr><td class="p-2 border-b">73.447,52 bis 108.238,40 €</td><td class="p-2 border-b">14,16%</td></tr>
        <tr><td class="p-2 border-b">Iwwer 108.238,40 €</td><td class="p-2 border-b">Befreit</td></tr>
    </tbody>
</table>

<p><strong>Mindestbeitrag 2026:</strong> 450,15 €/Quartal (Hauptberufflech Selbstännegen)</p>

<p><strong>Funktionéierungsart:</strong></p>
<ul>
    <li><strong>Trimesteriell</strong> Bezuelung</li>
    <li>Éischt <strong>provisoresch</strong> Beiträg (baséiert op Akommes N-3)</li>
    <li>Regulariséierung wann d'definitiv Akommes bekannt ass</li>
</ul>

<h3>Comptabilitéitspflichten</h3>

<h4>Vereinfacht Comptabilitéit (Ëmsaz < 500.000 €)</h4>
<p>3 Flichtjournaler:</p>
<ol>
    <li><strong>Akafjournal:</strong> Lëscht vun den Ausgaben</li>
    <li><strong>Verkafjournal:</strong> Chronologesch Iwwersiicht vun de Rechnungen</li>
    <li><strong>Finanzjournal:</strong> Kassebuch + Bankbuch</li>
</ol>

<p><strong>Opbewahrung vun den Dokumenter:</strong> 10 Joer</p>

<h2>Offiziell Quelle</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">FÖD Ekonomie - Schrëtt fir e Selbstännegen</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub fir Entrepreneur</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Sozialversécherungsbeiträg</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">FÖD Finanzen - TVA-Franchiseregime</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zesummefaassung</p>
    <p>Selbstänneg a Belsch ze ginn kascht tëscht 200 an 500 € an dauert 1 bis 2 Wochen. D'Sozialversécherungsbeiträg representéieren 20,5% vum Akommes. D'TVA-Franchise ass méiglech wann den Ëmsaz ënner 25.000 €/Joer bleift.</p>
</div>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'eenzelentreprise-deutschland-grenden-guide-2026',
                'locale' => 'lb',
                'translation_key' => 'creer-entreprise-individuelle-allemagne-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Däitschland bitt méi Méiglechkeete fir eng Eenzelentreprise ze grënnen, mat relativ einfachen a schnelle Prozeduren. Dëse Guide stellt déi verschidde Rechtsformen an d'Schrëtt fir unzefänken vir.</p>

<h2>Rechtsformen fir Eenzelentreprise</h2>

<h3>Einzelunternehmen (Eenzelentreprise)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Charakteristik</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definitioun</td><td class="p-2 border-b">Entreprise geréiert vun enger Persoun</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Net erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Onbegrenzt</strong></td></tr>
        <tr><td class="p-2 border-b">Grëndung</td><td class="p-2 border-b">Gewerbeanmeldung + Steiernummer</td></tr>
        <tr><td class="p-2 border-b">Besteierung</td><td class="p-2 border-b">Akommessteier + Gewerbesteuer (wann > 24.500 €/Joer)</td></tr>
    </tbody>
</table>

<p><strong>Ënnerkategorien:</strong></p>
<ul>
    <li><strong>Kleingewerbetreibender:</strong> Kleng Händler, keng Aschreiwung am Handelsregister</li>
    <li><strong>Eingetragener Kaufmann (e.K.):</strong> Am Handelsregister ageschriwwen</li>
</ul>

<h3>Freiberufler (Fräie Beruff)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Charakteristik</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Definitioun</td><td class="p-2 border-b">Intellektuell, kreativ, wëssenschaftlech oder pädagogesch Aktivitéit</td></tr>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b text-green-600">NET erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b text-green-600">NET applicabel</td></tr>
        <tr><td class="p-2 border-b">IHK/HWK</td><td class="p-2 border-b text-green-600">Keen Flichtbeitrag</td></tr>
        <tr><td class="p-2 border-b">Umeldung</td><td class="p-2 border-b">Direkt beim Finanzamt</td></tr>
    </tbody>
</table>

<p><strong>Betraff Beruffer (Katalogberufe):</strong> Dokteren, Affekoten, Architekten, Ingenieuren, Journalisten, Iwwersetzer, Kënschtler, Léierpersonal...</p>

<h2>Viraussetzungen</h2>

<h3>Fir Gewerbetreibende</h3>

<ul>
    <li><strong>Mindestalter:</strong> 18 Joer (Volljäregkeet)</li>
    <li><strong>Wunnsëtz:</strong> Adress an Däitschland</li>
    <li><strong>Dokumenter:</strong> Passport oder Personalauswäis</li>
    <li><strong>Legal Aktivitéit:</strong> Gesetzlech erlaabt Aktivitéit</li>
</ul>

<h3>Méiglech zousätzlech Dokumenter</h3>

<ul>
    <li><strong>Führungszeugnis</strong> (Strofregisterauszuch): ~13 €</li>
    <li><strong>Gewerbezentralregisterauszug:</strong> ~13 €</li>
    <li><strong>Handwierkskart:</strong> 80-250 €</li>
</ul>

<h2>Grëndungsschrëtt</h2>

<h3>Wee A: Gewerbetreibender</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Schrëtt 1: Gewerbeanmeldung (Gewerbeamt)<br>
        ↓<br>
        Schrëtt 2: Automatesch Benoriichtegungen (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>
        ↓<br>
        Schrëtt 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>
        ↓<br>
        Schrëtt 4: Steiernummer-Erdeellung<br>
        ↓<br>
        Schrëtt 5: Berufsgenossenschaft-Umeldung (7 Deeg)
    </p>
</div>

<h4>Gewerbeanmeldung</h4>
<ul>
    <li><strong>Wou:</strong> Gewerbeamt vun der Gemeng vum Sëtz</li>
    <li><strong>Formulaire:</strong> GewA 1</li>
    <li><strong>Aart:</strong> Online (Gewerbe-Service-Portal) oder virun Uert</li>
    <li><strong>Frist:</strong> 1-3 Deeg</li>
</ul>

<h3>Wee B: Freiberufler</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Schrëtt 1: Umeldung beim Finanzamt (bannent 4 Wochen nom Ufank)<br>
        ↓<br>
        Schrëtt 2: Fragebogen zur steuerlichen Erfassung<br>
        ↓<br>
        Schrëtt 3: Steiernummer-Erdeellung
    </p>
</div>

<h2>Grëndungskäschten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung (Basis)</td><td class="p-2 border-b">12,50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Grouss Stied (München, Stuttgart)</td><td class="p-2 border-b">50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Kleng Gemengen</td><td class="p-2 border-b">15 - 30 €</td></tr>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600 font-semibold">0 € (gratis)</td></tr>
    </tbody>
</table>

<h2>Duerchschnëttlech Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Schrëtt</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung-Veraarbechtung</td><td class="p-2 border-b font-semibold">1-3 Deeg</td></tr>
        <tr><td class="p-2 border-b">Schrëftlech Bestätegung Gewerbeamt</td><td class="p-2 border-b">Maximal 3 Deeg</td></tr>
        <tr><td class="p-2 border-b">Erhalen Fragebogen Finanzamt</td><td class="p-2 border-b">4-6 Wochen</td></tr>
        <tr><td class="p-2 border-b">Steiernummer-Erdeellung</td><td class="p-2 border-b">2-4 Wochen</td></tr>
        <tr><td class="p-2 border-b font-semibold">Gesamtfrist</td><td class="p-2 border-b font-semibold">6-10 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten no der Grëndung</h2>

<h3>Ëmsazsteier</h3>

<h4>Normalregime</h4>
<ul>
    <li><strong>Normaltaux:</strong> 19%</li>
    <li><strong>Ermäßegten Taux:</strong> 7%</li>
    <li>Méintlech oder trimesteriell Erklärung (Umsatzsteuer-Voranmeldung)</li>
</ul>

<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Kritär</th>
            <th class="text-left p-2 bg-slate-100">Schwellwäert 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Ëmsaz Virjoer</td><td class="p-2 border-b">≤ 25.000 €</td></tr>
        <tr><td class="p-2 border-b">Ëmsaz lafend Joer</td><td class="p-2 border-b">≤ 100.000 €</td></tr>
    </tbody>
</table>

<p><strong>Virdeeler:</strong></p>
<ul>
    <li>Keng Ëmsazsteierberechnung</li>
    <li>Keng Ëmsazstiererklärungen</li>
    <li>Vereinfacht Comptabilitéit</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Flichthinwäis op Rechnungen</p>
    <p>„Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG"<br>
    <em>(Keng Ëmsazsteier ugewisen, Klengentrepriseregelung no § 19 UStG)</em></p>
</div>

<h3>Gewerbesteuer (Gewerbesteier)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situatioun</th>
            <th class="text-left p-2 bg-slate-100">Flicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600">Befreit</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender < 24.500 €/Joer</td><td class="p-2 border-b text-green-600">Befreit (Freibetrag)</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender ≥ 24.500 €/Joer</td><td class="p-2 border-b">Steierpflichteg</td></tr>
    </tbody>
</table>

<h3>Sozialversécherung</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Typ</th>
            <th class="text-left p-2 bg-slate-100">Flicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Krankenversicherung</td><td class="p-2 border-b text-red-600 font-semibold">FLICHT</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung</td><td class="p-2 border-b text-red-600 font-semibold">FLICHT</td></tr>
        <tr><td class="p-2 border-b">Rentenversicherung</td><td class="p-2 border-b">Fräiwëlleg*</td></tr>
        <tr><td class="p-2 border-b">Arbeitslosenversicherung</td><td class="p-2 border-b">Fräiwëlleg</td></tr>
    </tbody>
</table>

<p><small>*Flicht fir bestëmmte Beruffer (Handwierker, Léierpersonal, Fleegeberuffen)</small></p>

<h3>IHK/HWK-Beitrag</h3>

<ul>
    <li>Automatesch an obligatoresch Memberschaft fir Gewerbetreibende</li>
    <li>Befreiung wann Gewerbeertrag < 5.200 €/Joer</li>
    <li>Progressiv Beiträg doriwwer eraus</li>
</ul>

<h2>Vergläichstabell</h2>

<table class="w-full my-4 text-sm">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Kritär</th>
            <th class="text-left p-2 bg-slate-100">Einzelunternehmen</th>
            <th class="text-left p-2 bg-slate-100">Freiberufler</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b">Jo</td><td class="p-2 border-b text-green-600">Nee</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b">Jo (> 24.500 €)</td><td class="p-2 border-b text-green-600">Nee</td></tr>
        <tr><td class="p-2 border-b">IHK-Memberschaft</td><td class="p-2 border-b">Flicht</td><td class="p-2 border-b text-green-600">Nee</td></tr>
        <tr><td class="p-2 border-b">Grëndungskäschten</td><td class="p-2 border-b">12,50-60 €</td><td class="p-2 border-b text-green-600">0 €</td></tr>
        <tr><td class="p-2 border-b">Grëndungsdauer</td><td class="p-2 border-b">1-3 Deeg</td><td class="p-2 border-b text-green-600">Direkt</td></tr>
    </tbody>
</table>

<h2>Offiziell Quelle</h2>

<ul>
    <li><a href="https://www.existenzgruendungsportal.de/" target="_blank" rel="noopener">Existenzgründungsportal (BMWK)</a></li>
    <li><a href="https://www.bmwk.de/" target="_blank" rel="noopener">Bundesministerium für Wirtschaft (BMWK)</a></li>
    <li><a href="https://www.ihk.de/" target="_blank" rel="noopener">IHK - Industrie- und Handelskammer</a></li>
    <li><a href="https://www.deutsche-rentenversicherung.de/" target="_blank" rel="noopener">Deutsche Rentenversicherung</a></li>
    <li><a href="https://gruenderplattform.de/" target="_blank" rel="noopener">Gründerplattform</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zesummefaassung</p>
    <p>Eng Eenzelentreprise an Däitschland grënnen kascht tëscht 0 an 60 € jee no Status. D'Gewerbeanmeldung gëtt an 1-3 Deeg veraarbecht. D'Kleinunternehmerregelung erlaabt d'Ëmsazsteierbefreiung ënner bestëmmte Schwellen. Freiberufler profitéieren vun engem vereinfachte Regime ouni Gewerbesteuer an IHK-Beitrag.</p>
</div>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'eenzelentreprise-frankreich-grenden-guide-2026',
                'locale' => 'lb',
                'translation_key' => 'creer-entreprise-individuelle-france-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Frankräich bitt e vereinfachte Kader fir Är Eenzelentreprise ze grënnen, besonnesch mat dem Mikro-Entreprise-Regime. Zënter 2023 gi all Formalitéiten iwwer den eenheetleche Schalter vum INPI gemaach. Entdeckt d'Schrëtt, Käschten an Obligatiounen fir unzefänken.</p>

<h2>Rechtsformen fir Eenzelentreprise</h2>

<h3>Eenzelentreprise (EI)</h3>

<p>D'Eenzelentreprise erlaabt et eng Aktivitéit am eegenen Numm auszeüben, ouni eng juristesch Persoun ze grënnen.</p>

<ul>
    <li>Kee Stammkapital erfuerderlech</li>
    <li>Keng Statuten ze erstellen</li>
    <li>Méiglech Aktivitéiten: Handel, Handwierk, Landwirtschaft oder fräi Beruffer</li>
    <li><strong>Zënter Februar 2022</strong>: Privat a berufflecht Verméige sinn automatesch getrennt</li>
</ul>

<h3>Mikro-Entreprise (Auto-entrepreneur)</h3>

<p>D'Mikro-Entreprise ass e vereinfacht Regime vun der Eenzelentreprise mat Ëmsazschwellen:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aktivitéitstyp</th>
            <th class="text-left p-2 bg-slate-100">Ëmsazschwelle (2026)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Wuerenverkaf, Ënnerbréngung</td><td class="p-2 border-b">203.100 €</td></tr>
        <tr><td class="p-2 border-b">Servicer</td><td class="p-2 border-b">83.600 €</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Gutt ze wëssen</p>
    <p>D'EIRL existéiert net méi zënter dem 15. Mee 2022. Den neien EI-Status integréiert elo automatesch d'Verméigenstrennung.</p>
</div>

<h2>Viraussetzungen</h2>

<h3>Perséinlech Viraussetzungen</h3>

<ul>
    <li><strong>Volljäreg</strong> sinn (oder emanzipéierte Mannerjäregen)</li>
    <li>Eng <strong>Adress a Frankräich</strong> hunn</li>
    <li>Net ënner Vormundschaft oder Betreiung stoen</li>
    <li>Keen Geschäftsféierungsverbuet hunn</li>
    <li>Franséisch, europäesch Nationalitéit oder Openthaltsnowäis zur Berufsausübung</li>
</ul>

<h3>Reguléiert Aktivitéiten</h3>

<p>Bestëmmt Beruffer erfuerderen spezifesch Diplomer oder Qualifikatiounen: Coiffer, Bau, Gesondheetsprofessiounen, asw.</p>

<h2>Grëndungsschrëtt iwwer den Eenheetleche Schalter INPI</h2>

<h3>Schrëtt 1: Dokumentervirbereedung</h3>
<ul>
    <li>Ausweisdokument (Personalauswäis oder Passport) am PDF-Format</li>
    <li>Wunnsëtznowäis (wann Aktivitéit doheem ausgeüübt gëtt)</li>
    <li>Qualifikatiounsnowiiser fir reguléiert Aktivitéiten</li>
</ul>

<h3>Schrëtt 2: Kontoerstellung</h3>
<p>Gitt op <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> an erstellt e Kont iwwer France Connect (recommandéiert) oder eng INPI-Kennung.</p>

<h3>Schrëtt 3: Aktivitéitsumeldung</h3>
<ol>
    <li>Klickt op „Umellen"</li>
    <li>Wielt „Eenzelentrepreneur"</li>
    <li>Gitt an: Aart vun der Aktivitéit, Adress, Startdatum, Steier- an Sozialoptiounen</li>
</ol>

<h3>Schrëtt 4: Validéierung a Suivi</h3>
<ul>
    <li>Beleger bäileeën</li>
    <li>Wann néideg Bezuelung maachen</li>
    <li>Fortschrëtt iwwer den Dashboard verfollegen</li>
    <li>Automatesch Aschreiwung am RNE (Nationalt Entrepriseregister)</li>
</ul>

<h2>Grëndungskäschten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aktivitéitstyp</th>
            <th class="text-left p-2 bg-slate-100">Käschten</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Handelsaktivitéit</td><td class="p-2 border-b text-green-600 font-semibold">Gratis</td></tr>
        <tr><td class="p-2 border-b">Handwierksaktivitéit</td><td class="p-2 border-b text-green-600 font-semibold">Gratis</td></tr>
        <tr><td class="p-2 border-b">Fräie Beruff</td><td class="p-2 border-b text-green-600 font-semibold">Gratis</td></tr>
        <tr><td class="p-2 border-b">Handelsvertrieder</td><td class="p-2 border-b">23,86 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Opgepasst</p>
    <p>Virsicht virun private Websäiten déi Gebühre fir en normalerweis gratis Service verlaangen.</p>
</div>

<h2>Duerchschnëttlech Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Schrëtt</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Online-Umeldung</td><td class="p-2 border-b">E puer Minutten</td></tr>
        <tr><td class="p-2 border-b">Empfangsbestätegung</td><td class="p-2 border-b">24 Stonnen</td></tr>
        <tr><td class="p-2 border-b">Erhalen vun der SIRET-Nummer</td><td class="p-2 border-b font-semibold">1 bis 2 Wochen</td></tr>
        <tr><td class="p-2 border-b">URSSAF-Benoriichtegung</td><td class="p-2 border-b">4 bis 10 Wochen</td></tr>
    </tbody>
</table>

<h2>Pflichten no der Grëndung</h2>

<h3>URSSAF-Beiträg</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aktivitéitstyp</th>
            <th class="text-left p-2 bg-slate-100">Taux 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Kaf-Widerverkaf</td><td class="p-2 border-b">12,3 %</td></tr>
        <tr><td class="p-2 border-b">Handels-/Handwierksservicer</td><td class="p-2 border-b">21,2 %</td></tr>
        <tr><td class="p-2 border-b">Aner Servicer</td><td class="p-2 border-b">25,6 %</td></tr>
        <tr><td class="p-2 border-b">Fräi Beruffer (Cipav)</td><td class="p-2 border-b">23,2 %</td></tr>
    </tbody>
</table>

<p><strong>Heefegkeet:</strong> Méintlech oder trimesteriell Erklärung (no Wiel). Erklärungspflicht och wann den Ëmsaz null ass.</p>

<h3>TVA - Basisfranchise</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aktivitéitstyp</th>
            <th class="text-left p-2 bg-slate-100">Basisschwelle</th>
            <th class="text-left p-2 bg-slate-100">Erhéicht Schwelle</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Verkaf/Handel/Ënnerbréngung</td><td class="p-2 border-b">85.000 €</td><td class="p-2 border-b">93.500 €</td></tr>
        <tr><td class="p-2 border-b">Servicer</td><td class="p-2 border-b">37.500 €</td><td class="p-2 border-b">41.250 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Flichtmentioun bei Franchise</p>
    <p>„TVA net applicabel, Art. 293 B vum CGI"</p>
</div>

<h3>CFE (Entreprisesteier)</h3>

<ul>
    <li><strong>1. Joer:</strong> Vun der Bezuelung befreit</li>
    <li><strong>Voll Befreiung:</strong> Wann Joresëmsaz < 5.000 €</li>
    <li><strong>Flicht:</strong> Erklärung Nr. 1447-C virum 31. Dezember vum 1. Joer areechen</li>
</ul>

<h3>Comptabilitéitspflichten</h3>

<ol>
    <li><strong>Konform Rechnungen</strong> fir all Verkaf/Leeschtung ausstellen</li>
    <li>E chronologescht <strong>Akeefersbuch</strong> féieren</li>
    <li>E <strong>Akafregister</strong> féieren (bei Verkaufsaktivitéit)</li>
    <li><strong>Beleger</strong> 10 Joer opbewahren</li>
</ol>

<h2>Verfügbar Hëllefen</h2>

<h3>ACRE (Hëllef fir Entreprisegrënner)</h3>

<ul>
    <li><strong>Deelweis Befreiung</strong> vu Sozialbeiträg am 1. Joer (bis zu 50%)</li>
    <li>Bedéngungen: Aarbechtsuchend, RSA-Empfänger, Jonker 18-25 Joer, asw.</li>
    <li>Ufro bei der Grëndung oder bannent 45 Deeg ze maachen</li>
</ul>

<h2>Offiziell Quelle</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Eenzelentrepreneur</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">Eenheetleche Schalter fir Entrepriseformalitéiten</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-entrepreneur</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Eenzelentreprise grënnen</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zesummefaassung</p>
    <p>Eng Mikro-Entreprise a Frankräich grënnen ass gratis a séier (SIRET an 1-2 Wochen). D'Sozialbeiträg variéieren tëscht 12 an 26% jee no Aktivitéit. D'TVA-Franchise erlaabt et, ënner bestëmmte Schwellen keng TVA ze berechnen.</p>
</div>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'eenzelentreprise-letzebuerg-grenden-guide-2026',
                'locale' => 'lb',
                'translation_key' => 'creer-entreprise-individuelle-luxembourg-guide-2025',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Lëtzebuerg bitt en favorabelt Ëmfeld fir Entrepreneur mat relativ einfachen administrativen Prozeduren a moderaten Grëndungskäschten. Dëse Guide begleet Iech Schrëtt fir Schrëtt bei der Grëndung vun Ärer Eenzelentreprise am Groussherzogtum.</p>

<h2>Rechtsformen fir Eenzelentreprise</h2>

<p>Zu Lëtzebuerg üübt e selbstännegen Entrepreneur säi Beruf a sengem eegenen Numm aus als:</p>

<ul>
    <li><strong>Händler</strong>: fir kommerziell Aktivitéiten</li>
    <li><strong>Handwierker</strong>: fir handwierklech Aktivitéiten</li>
    <li><strong>Selbstännegen intellektuellen Aarbechter</strong>: fir fräi Beruffer</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Notiz</p>
    <p>Et gëtt keen exakten Equivalent zum franséischen Auto-Entrepreneur-Status zu Lëtzebuerg. D'Eenzelentreprise ass déi nächst a einfachst Form.</p>
</div>

<h3>Haaptcharakteristiken</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspekt</th>
            <th class="text-left p-2 bg-slate-100">Detail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Rechtspersounlechkeet</td><td class="p-2 border-b">Keng - den Entrepreneur handelt a sengem eegenen Numm</td></tr>
        <tr><td class="p-2 border-b">Mindestkapital</td><td class="p-2 border-b">Kee Mindestkapital erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b"><strong>Onbegrenzt</strong> - haft mat sengem perséinleche Verméigen</td></tr>
        <tr><td class="p-2 border-b">Formalismus</td><td class="p-2 border-b">Minimal - keng Statuten erfuerderlech</td></tr>
    </tbody>
</table>

<h2>Viraussetzungen</h2>

<h3>Etabléierungsautoriséierung (Flicht)</h3>

<p>All wirtschaftlech Aktivitéit déi regelméisseg ausgeüübt gëtt erfuerdert eng <strong>virausgehend Etabléierungsautoriséierung</strong>.</p>

<p><strong>Bedéngungen déi erfëllt musse ginn:</strong></p>

<ul>
    <li><strong>Physesch Etablissement</strong>: entspriechend materiell Ariichtung zu Lëtzebuerg</li>
    <li><strong>Effektiv Gestioun</strong>: physesch Präsenz an deeglech Verwaltung duerch den Inhaber</li>
    <li><strong>Berufflech Ehrenhaftegkeet</strong>: propperen Casier, Anhale vu fréiere Steier- a Sozialpflichten</li>
    <li><strong>Beruffsqualifikatioun</strong>: jee no der ugezielter Aktivitéit</li>
</ul>

<h3>Erfuerderlech Beruffsqualifikatiounen</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aktivitéitstyp</th>
            <th class="text-left p-2 bg-slate-100">Erfuerderlech Qualifikatioun</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Kommerziell Aktivitéiten</td><td class="p-2 border-b">Am Allgemengen keen spezifesche Diplom erfuerderlech</td></tr>
        <tr><td class="p-2 border-b">Handwierklech Aktivitéiten</td><td class="p-2 border-b">DAP, CATP oder Meeschterbréif</td></tr>
        <tr><td class="p-2 border-b">Fräi Beruffer</td><td class="p-2 border-b">Spezifesch Diplomer jee no Beruf</td></tr>
    </tbody>
</table>

<h2>Detailléiert Grëndungsschrëtt</h2>

<h3>Schrëtt 1: Projeterausgaang</h3>
<ul>
    <li>Businessplan erstellen</li>
    <li>Begleedend Organisatiounen kontaktéieren (House of Entrepreneurship, Chambre de Commerce, Chambre des Métiers)</li>
</ul>

<h3>Schrëtt 2: Viraussetzunge préiwen</h3>
<ul>
    <li>Disponibilitéit vum Handelsnumm préiwen</li>
    <li>Sécherstellen datt Dir déi erfuerderlech Qualifikatiounen hutt</li>
    <li>Diplomunerkennung ufroen wann néideg</li>
</ul>

<h3>Schrëtt 3: Antrag op Etabléierungsautoriséierung</h3>
<p><strong>Wou:</strong> Online iwwer MyGuichet.lu (mat LuxTrust-Zertifikat) oder per Post</p>
<p><strong>Erfuerderlech Dokumenter:</strong></p>
<ul>
    <li>Ufroformular</li>
    <li>Nowiiser vun der Beruffsqualifikatioun</li>
    <li>Auszuch aus dem Strofregister (Bulletin Nr. 3)</li>
    <li>Kopie vum Auswäis</li>
    <li>Bezuelungsbewäis vun der Kanzleigebühr (50 EUR)</li>
</ul>

<h3>Schrëtt 4: RCS-Aschreiwung</h3>
<p><strong>Wou:</strong> Elektronesch Areechung op der LBR-Websäit (Luxembourg Business Registers)</p>
<p><strong>Erfuerderlech Dokumenter:</strong></p>
<ul>
    <li>Umeldungsformular</li>
    <li>Etabléierungsautoriséierung</li>
    <li>Ausweisdokument</li>
    <li>Bestietnessurkunde / Bestietnesvertrag (wann zoutreffend)</li>
</ul>

<h3>Schrëtt 5: Affiliatioun zur Sozialversécherung</h3>
<p>Aschreiwen beim CCSS (Centre Commun de la Sécurité Sociale) als Selbstännegen.</p>

<h3>Schrëtt 6: Steierleg Umeldung</h3>
<ul>
    <li>Umeldung bei der Administration des Contributions Directes</li>
    <li>TVA-Umeldung wann Ëmsaz > 50.000 EUR</li>
</ul>

<h2>Grëndungskäschten</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Posten</th>
            <th class="text-left p-2 bg-slate-100">Betrag</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Etabléierungsautoriséierung</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">RCS-Aschreiwung</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Diplomunerkennung</td><td class="p-2 border-b">75 EUR (wann erfuerderlech)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschaten Total</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Duerchschnëttlech Fristen</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Prozedur</th>
            <th class="text-left p-2 bg-slate-100">Frist</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Etabléierungsautoriséierung</td><td class="p-2 border-b">Bis zu 3 Méint</td></tr>
        <tr><td class="p-2 border-b">Diplomunerkennung</td><td class="p-2 border-b">2 bis 6 Wochen</td></tr>
        <tr><td class="p-2 border-b">RCS-Aschreiwung</td><td class="p-2 border-b">E puer Deeg</td></tr>
        <tr><td class="p-2 border-b font-semibold">Geschate Gesamtdauer</td><td class="p-2 border-b font-semibold">1 bis 3 Méint</td></tr>
    </tbody>
</table>

<h2>Pflichten no der Grëndung</h2>

<h3>TVA (Méiwertsteier)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situatioun</th>
            <th class="text-left p-2 bg-slate-100">Flicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Joresëmsaz ≤ 50.000 EUR</td><td class="p-2 border-b">TVA-Befreiung (keng Umeldepflicht)</td></tr>
        <tr><td class="p-2 border-b">Joresëmsaz > 50.000 EUR</td><td class="p-2 border-b">Flichtumeldung + periodesch Erklärungen</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Flichtmentioun bei Franchise</p>
    <p>„TVA net applicabel, Art. 57 vum Lëtzebuerger TVA-Gesetz (Steierbefreiungsregime)"</p>
</div>

<h3>Sozialversécherungsbeiträg (CCSS)</h3>

<p>D'Beiträg representéieren ongeféier <strong>25,3%</strong> vum Akommes, opgedeelt wéi follegt:</p>

<ul>
    <li>Krankeverséscherung (Sachleeschtungen): 5,60%</li>
    <li>Krankeverséscherung (Geldleeschtungen): 0,50%</li>
    <li>Fleegeversécherung: 1,40%</li>
    <li>Rentenversécherung: 17,00%</li>
    <li>Onfallversécherung: 0,65%</li>
    <li>Aarbechtsschutz: 0,14%</li>
</ul>

<h3>Comptabilitéit</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Joresëmsaz</th>
            <th class="text-left p-2 bg-slate-100">Flicht</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100.000 EUR netto</td><td class="p-2 border-b">Vereinfacht Comptabilitéit</td></tr>
        <tr><td class="p-2 border-b">≥ 100.000 EUR netto</td><td class="p-2 border-b">Standardiséiert Comptabilitéit Flicht</td></tr>
    </tbody>
</table>

<h2>Offiziell Quelle</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html" target="_blank" rel="noopener">Guichet.lu - Eenzelentreprise</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Etabléierungsautoriséierung</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Selbstänneger</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Zesummefaassung</p>
    <p>D'Grëndung vun enger Eenzelentreprise zu Lëtzebuerg ass relativ einfach a bëlleg (ongeféier 100-150 EUR). De Prozess dauert normalerweis 1 bis 3 Méint an ëmfaasst d'Erlaangen vun der Etabléierungsautoriséierung an der RCS-Aschreiwung. D'Sozialbeiträg representéieren ongeféier 25% vum Akommes.</p>
</div>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'faia-letzebuerg-informatiseierte-audit-fichier-guide',
                'locale' => 'lb',
                'translation_key' => 'faia-luxembourg-fichier-audit-informatise-guide',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">De FAIA (informatiséierten Audit-Fichier) ass e Fichier, deen d'AED bei enger Steierkontroll ka verlaangen. Am Géigesaz zu enger verbreeter Virstellung betrëfft en net all Lëtzebuerger Entreprise: véier kumulativ Bedingunge bestëmmen, wien en muss kënne produzéieren.</p>

<h2>Wat ass de FAIA?</h2>

<p>De <strong>FAIA (Fichier d'Audit Informatisé)</strong>, och <strong>SAF-T Lëtzebuerg</strong> genannt, ass e Fichier am standardiséierten XML-Format, deen all d'Comptabilitéits- a Steierdate vun enger Entreprise fir eng bestëmmte Period enthält.</p>

<p>Seng gesetzlech Basis ass d'<strong>Gesetz vum 19. Dezember 2008</strong> (Mémorial A-206 vum 24. Dezember 2008), dat den <strong>Artikel 70, Paragraf 3, vum TVA-Gesetz</strong> geännert huet. Deen Text gesäit vir, datt Bicher an Dokumenter, déi an elektronescher Form existéieren, op Ufro vun der Administratioun „an enger liesbarer an direkt verständlecher Form" oder no anere technesche Modalitéiten, déi d'Administratioun bestëmmt, mussen iwwerdroe ginn. De FAIA ass d'Modalitéit, déi d'AED gewielt huet.</p>

<h2>Wien muss e FAIA-Fichier produzéieren?</h2>

<p>Dat ass de Punkt, deen am dackste verzerrt gëtt. No der <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offizieller FAQ vun der AED</a> setzt d'Flicht viraus, datt <strong>véier Bedingungen zur selwechter Zäit erfëllt</strong> sinn.</p>

<h3>Déi véier kumulativ Bedingungen</h3>

<ol>
    <li>Dem <strong>normaliséierte Comptesplang (PCN)</strong> ënnerleien</li>
    <li><strong>Kee vereinfachte Regime</strong> a Usproch huelen</li>
    <li>E <strong>Joresëmsaz vun iwwer 112 000 €</strong> realiséieren</li>
    <li>E Volume vun ongeféier <strong>500 comptabel Transaktiounen</strong> pro Joer iwwerschreiden</li>
</ol>

<p>Wann nëmmen eng vun dëse Bedingunge feelt, sidd Dir vum FAIA net betraff. D'AED formuléiert et a hirer FAQ ouni Ëmschwëff:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ech realiséieren en Ëmsaz vun 1.000.000 € an hunn nëmmen 400 Transaktiounen a menger Comptabilitéit. Sinn ech verflicht, e FAIA-Fichier ze liwweren? — <strong>Neen.</strong> Och wann Ären Ëmsaz déi 112.000 € iwwerschreit, bleift Äre Transaktiounsvolume a Grenzen, wou eng manuell Kontroll méi rationell ass."</p>
</blockquote>

<h3>Wat eng „Transaktioun" wierklech ass</h3>

<p>Oppassen beim Zielen: eng Transaktioun <strong>ass keng Rechnung</strong>. D'AED definéiert se als eng <strong>ganz Comptabiliséierungskette</strong>. En Akaf zerfält zum Beispill a véier verbonnen Écritureën — Akafskont, TVA en amont, Fournisseurskont, Bezuelung —, déi zesummen <strong>eng eenzeg</strong> Transaktioun bilden.</p>

<p>Wien seng 600 Rechnungen zielt an doraus schléisst, de Seuil ze iwwerschreiden, miesst also wahrscheinlech dat Falscht.</p>

<h3>Wann Dir dem PCN net ënnerleit</h3>

<p>Da entkommt Dir der eigentlecher FAIA-Flicht, souguer mat engem héijen Ëmsaz a méi wéi 500 Transaktiounen. Mä den Artikel 70 gëllt weider: d'AED ka verlaangen, datt Dir Är elektronesch Daten <strong>an engem ofgegrenzten a strukturéierte Format</strong> exportéiert. Ausserhalb vum FAIA ze stoen entbënnt net dovunner, seng Comptabilitéit propper erausginn ze kënnen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Wichteg</p>
    <p class="text-amber-700">De FAIA gëtt ni spontan iwwerdroen, a besonnesch <strong>net mat Ärer TVA-Deklaratioun</strong>. En gëtt <strong>eleng op Ufro</strong> vun engem AED-Agent produzéiert, deen d'Kontroll vun Ärer Entreprise mécht.</p>
</div>

<h2>Wat enthält de FAIA-Fichier?</h2>

<p>De FAIA-Fichier ass a méi Sektiounen ënnerdeelt:</p>

<h3>1. Allgemeng Informatiounen (Header)</h3>

<ul>
    <li>Identifikatioun vun der Entreprise (Numm, Adress, TVA-Nummer)</li>
    <li>Period, déi de Fichier ofdeckt</li>
    <li>Informatiounen iwwer déi benotzte Software</li>
    <li>Datum an Auerzäit vun der Generéierung</li>
</ul>

<h3>2. Comptesplang (GeneralLedger)</h3>

<ul>
    <li>Lëscht vun alle benotzte Comptabilitéitskonten</li>
    <li>Hierarchie vun de Konten</li>
    <li>Ufanks- a Schlusssoldeën</li>
</ul>

<h3>3. Clienten a Fournisseuren (MasterFiles)</h3>

<ul>
    <li>Clientsfichier mat vollstännege Kontaktdaten</li>
    <li>Fournisseursfichier</li>
    <li>Innergemeinschaftlech TVA-Nummeren</li>
</ul>

<h3>4. Comptabel Écritureën (GeneralLedgerEntries)</h3>

<ul>
    <li>All Écritureë vun der Period, och déi ouni direkte Bezuch zur TVA — den Export muss d'ganz Comptabilitéit ëmfaassen</li>
    <li>Comptabilitéitsjournaler</li>
    <li>Referenzéiert Beleeger</li>
</ul>

<h3>5. Rechnungen (SourceDocuments)</h3>

<ul>
    <li>Ausgestallt Verkafsrechnungen</li>
    <li>Erhalen Akafsrechnungen</li>
    <li>Avoiren a Kreditnoten</li>
    <li>Detail Linn fir Linn mat TVA</li>
</ul>

<p>Wann Äre Fakturatiounssystem <strong>an Är Comptabilitéit integréiert</strong> ass, sinn d'Quelldokumenter systematesch matzeliwweren. Wann en et net ass, kann den AED-Agent gezielt eenzel Quelldokumenter ufroen.</p>

<h2>Technescht Format vum FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Kodéierung</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD-Schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, lescht Aktualiséierung, déi d'AED am Juli 2020 publizéiert huet. Dräi Schemae bestinn niewenteneen: <em>full</em>, <em>reduced version A</em> an <em>reduced version B</em>, no dem Comptabilitéitsregime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Period</td>
            <td class="border border-gray-300 px-4 py-2">E ganzt Geschäftsjoer, um Kalennerjoer ausgeriicht. Verkierzte Geschäftsjoere ginn refuséiert, an e Fichier däerf nëmmen eng Period ofdecken: eng Kontroll iwwer dräi Joer verlaangt dräi Fichieren</td>
        </tr>
    </tbody>
</table>

<h2>Wéi generéiert een e konforme FAIA-Fichier?</h2>

<h3>Optioun 1: Kompatibel Fakturatiounssoftware</h3>

<p>Dat ass déi einfachst Léisung. Eng Software wéi <strong>faktur.lu</strong> generéiert automatesch e konforme FAIA-Fichier aus Äre Fakturatiounsdaten.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ FAIA-Export mat engem Klick bei faktur.lu</p>
    <p class="text-green-700">Eis Software generéiert e FAIA-Fichier, validéiert no dem offizielle XSD-Schema, prett fir un d'AED ze schécken — egal ob Dir haut dozou verflicht sidd oder d'Seuiler muer iwwerschreit.</p>
</div>

<h3>Optioun 2: Comptabilitéitssoftware</h3>

<p>Professionell Comptabilitéitsprogrammer (Sage, BOB asw.) bidden allgemeng e FAIA-Exportmodul un.</p>

<h3>Optioun 3: Entwécklung op Mooss</h3>

<p>Fir grouss Entreprise mat proprietäre Systemer kann eng spezifesch Entwécklung néideg sinn, fir d'Daten no dem FAIA-Schema z'extrahéieren an ze formatéieren.</p>

<h2>Validatioun vum FAIA-Fichier</h2>

<p>Ier Dir Äre Fichier iwwerdroet, validéiert en:</p>

<ol>
    <li><strong>XSD-Validatioun</strong>: préiwen, ob de Fichier dem offizielle XML-Schema entsprécht</li>
    <li><strong>Kontroll vun de Summen</strong>: sécherstellen, datt d'Zomme kohärent sinn</li>
    <li><strong>Iwwerpréiwung vun de Referenzen</strong>: all Identifianten (Clienten, Konten) mussen do sinn</li>
</ol>

<p>D'AED ass op dësem Punkt kloer: <strong>et gëtt keen Validatiounsinstrument zur Verfügung gestallt</strong>, an „nëmmen d'Schema, dat op der Internetsäit vun der AED publizéiert ass, ka als Kontrollmechanismus déngen". Dir kënnt also all beliebege XML-Validator vun engem Drëtten notzen (zum Beispill de <a href="/lb/validateur-faia">faktur.lu-Validator</a>), fir d'Konformitéit virdrun ze préiwen.</p>

<h2>Fristen, Iwwerdroung a Sanktiounen</h2>

<h3>Frist fir d'Erstellung</h3>

<p>D'AED publizéiert keng fest gesetzlech Frist. Wann e FAIA-Fichier am Kader vun enger Kontroll gefrot gëtt, gëtt d'Frist <strong>vum Kontrolleur vun Fall zu Fall</strong> festgeluecht, no der Komplexitéit vun der Ufro.</p>

<h3>Iwwerdroungsmedium</h3>

<p>D'AED weist sech flexibel: all Standard-elektronesche Support, dee fräi um Marché disponibel ass, gëtt akzeptéiert — USB-Stick, extern Festplack, CD-R oder DVD-R, E-Mail.</p>

<h3>Sanktioune bei Netkonformitéit</h3>

<p>Fir Entreprisen, déi tatsächlech ënner d'Flicht falen, kann d'Verweigerung oder d'Onfäegkeet, d'Daten ze liwweren, Folgendes no sech zéien:</p>

<ul>
    <li><strong>Administrativ Geldstrofen</strong></li>
    <li>Eng <strong>Taxatioun vun Amts wéinst</strong> duerch d'Administratioun</li>
    <li>D'<strong>Refuséiere vun der Comptabilitéit</strong> als Beweis</li>
</ul>

<h2>Bewäert Praxis</h2>

<ol>
    <li><strong>Préift als éischt, ob Dir betraff sidd</strong> — all véier Bedingunge musse erfëllt sinn</li>
    <li><strong>Testt Äre FAIA-Export reegelméisseg</strong>, net eréischt bei enger Kontroll</li>
    <li><strong>Archivéiert</strong> déi generéiert FAIA-Fichieren fir all Geschäftsjoer</li>
    <li><strong>Préift d'Kohärenz</strong> tëscht Äre Rechnungen an Äre comptabelen Écritureën</li>
    <li><strong>Notzt zertifizéiert</strong> oder gestest Software fir de FAIA-Export</li>
</ol>

<h2>Déi 4 heefegst FAIA-Feeler</h2>

<ol>
    <li><strong>Net konform Rechnungsnummeréierung</strong> no dem Artikel 63 LIVA Punkt 3° (Lacken an der Sequenz oder Duebelen). De Fichier kann bei der Validatioun zréckgewise ginn.</li>
    <li><strong>Feelend obligatoresch Felder</strong>: TVA-Nummer vum Client, vollstänneg Adress, Mentioun vun der Autoliquidatioun beim innergemeinschaftleche B2B.</li>
    <li><strong>Inkohärent Totaler</strong> tëscht de Sektiounen — zum Beispill eng Rechnungszomm, déi vum deklaréierten Total ofwäicht.</li>
    <li><strong>Falscht Datumsformat</strong>: de FAIA verlaangt d'Norm ISO 8601 (JJJJ-MM-DD), net DD/MM/JJJJ.</li>
</ol>

<h2>Heefeg Froen</h2>

<h3>Muss ee all Joer e FAIA schécken?</h3>
<p>Neen. De FAIA gëtt eleng op Ufro vun der AED bei enger Kontroll produzéiert, an nëmme vun de betraffenen Entreprisen. En gëtt ni mat der TVA-Deklaratioun ofginn.</p>

<h3>Wat geschitt, wann ech en net liwwere kann, obwuel ech dozou verflicht sinn?</h3>
<p>De Kontrolleur geet dovun aus, datt Är Comptabilitéit net no de Standarde gefouert gëtt. Dir riskéiert eng administrativ Geldstrof vun 250 € bis 10 000 € pro Infraktioun (Art. 77 LIVA), eng Taxatioun vun Amts wéinst, an — bei Refus, d'Stécker ze weisen — eng Geldstrof bis zu 25 000 € pro Dag Verspéidung no engem Avertissement.</p>

<h3>Ass de FAIA bei der TVA-Franchise obligatoresch?</h3>
<p>Allgemeng neen. D'Franchise (Art. 57bis LIVA, Ëmsaz ≤ 50 000 €) hält den Assujetti ënner dem Seuil vun 112 000 €, an de vereinfachte Regime ass sowisou fir sech eleng e Grond fir den Ausschloss.</p>

<h3>Wéi weess ech virun enger Kontroll, ob mäi Fichier konform ass?</h3>
<p>Validéiert en géint dat offiziellt XSD-Schema. De <a href="/lb/faia-validator">FAIA-Validator vu faktur.lu</a> ass gratis an ouni Aschreiwung: en iwwerpréift d'Konformitéit mam Schema 2.01, erkennt feelend Felder, kontrolléiert d'Kohärenz vun den Totaler an d'Sequenzialitéit vun de Rechnungsnummeren, ouni Är Daten ze späicheren.</p>

<h3>Kann mäi Comptabel de FAIA fir mech generéieren?</h3>
<p>Jo. Är Fiduciaire kann en aus hirem eegenen Tool produzéieren, oder Ären iwwer e Comptabelsportal mat Lieszougrëff ofruffen.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Offiziell FAIA-Säit</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – XSD-Schemae FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Applikatiounsberäich an Ausschlëss)</a></li>
</ul>

<h2>Conclusioun</h2>

<p>De FAIA ass eng richteg, mä geziilte Flicht: se betrëfft Entreprisen, déi dem normaliséierte Comptesplang an dem normale Regime ënnerleien, iwwer 112 000 € Ëmsaz an ongeféier 500 Joerestransaktiounen eraus. Vill Onofhängeger a kleng Strukture sinn net dozou verflicht.</p>

<p>Wann Dir betraff sidd — oder wann Äre Wuesstem Iech dohinner féiert —, erspuert Iech eng Fakturatiounssoftware, déi de Fichier ka produzéieren, d'Entdeckung vum Problem um Dag vun der Kontroll. faktur.lu integréiert den FAIA-Export nativ, validéiert no dem offizielle Schema vun der AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/steierprefung-letzebuerg-virbereden" class="text-primary-500 hover:text-primary-600 text-sm">Steierkontroll →</a></li><li><a href="/lb/blog/rechnungsarchiveierung-letzebuerg-gesetzlech-dauer-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivéierung vu Rechnungen →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen a Prozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'freelancer-letzebuerg-konform-fakturieren',
                'locale' => 'lb',
                'translation_key' => 'freelance-luxembourg-facturer-conformite',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">Fänkt Dir als Freelancer zu Lëtzebuerg un? D'Fakturatioun ass e wesentlechen Deel vun Ärer Aktivitéit. Dëse Guide erkläert Iech, wéi Dir 2026 konform Rechnunge erstellt an Är steierlech Flichte verwalt (aktualiséiert no der Erhéijung vum TVA-Franchisesseuil op 50 000 €).</p>

<h2>De Freelancer-Statut zu Lëtzebuerg</h2>

<p>Zu Lëtzebuerg schafft de Freelancer (oder Onofhängegen) allgemeng ënner engem vun dëse Statuten:</p>

<ul>
    <li><strong>Eenzelentreprise</strong>: de gängegste Statut fir unzefänken</li>
    <li><strong>Eepersounegesellschaft (SARL-S)</strong>: eng vereinfacht Gesellschaft mat limitéierter Haftung</li>
    <li><strong>Fräie Beruff</strong>: fir gewësse reglementéiert Aktivitéiten</li>
</ul>

<p>Egal wéi Äre Statut ass, et gëllen déi selwecht Reegele fir d'Fakturatioun.</p>

<h2>Sech fir d'TVA aschreiwen</h2>

<p>Ier Dir ufänkt ze fakturéieren (iwwer de Franchisesseuil, kuckt hei ënnen), musst Dir eng <strong>innergemeinschaftlech TVA-Nummer</strong> bei der <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a> ufroen.</p>

<h3>Aschreiwungsprozedur</h3>

<ol>
    <li>Eng <strong>Etablissementsautorisatioun</strong> beim Wirtschaftsministère kréien</li>
    <li>Sech am <strong>Handelsregëster (RCS)</strong> aschreiwen, wa relevant</li>
    <li>D'<strong>TVA-Aschreiwung</strong> iwwer <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> ufroen</li>
    <li>Är Nummer am Format <strong>LU + 8 Zifferen</strong> kréien</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Opgepasst</p>
    <p class="text-amber-700">Ënner dem Franchisesseuil (Ëmsaz ≤ 50 000 € ouni TVA, Art. 57bis LIVA zanter 2025) ass d'TVA-Aschreiwung net obligatoresch. Dir fakturéiert dann ouni TVA mat der Mentioun „TVA net applicabel – Artikel 57bis". <strong>D'Kéiersäit:</strong> Dir kritt d'TVA op Är berufflech Akeef (Material, Software, Ënneropträg) net zréck. Wien investéiert, fir deen ka d'Franchise méi deier ginn wéi se abréngt.</p>
</div>

<h2>D'obligatoresch Mentiounen op Äre Rechnungen</h2>

<p>No <strong>Artikel 63 LIVA</strong> mussen Är Rechnungen als Freelancer enthalen:</p>

<h3>Är Informatiounen</h3>

<ul>
    <li><strong>Vollstännege Numm</strong> oder Firmennumm</li>
    <li><strong>Beruffsadress</strong> zu Lëtzebuerg</li>
    <li><strong>TVA-Nummer</strong> (LU12345678)</li>
    <li>Eventuell Är RCS-Nummer (obligatoresch fir agedroe Händler/Gesellschaften)</li>
</ul>

<h3>Informatioune vum Client</h3>

<ul>
    <li>Numm oder Firmennumm</li>
    <li>Vollstänneg Adress</li>
    <li>TVA-Nummer (obligatoresch fir professionell Clienten an der EU)</li>
</ul>

<h3>Detailer vun der Leeschtung</h3>

<ul>
    <li><strong>Eenzeg a fortlafend Rechnungsnummer</strong> (Artikel 63 LIVA, Punkt 3°)</li>
    <li><strong>Ausstellungsdatum</strong></li>
    <li><strong>Leeschtungsdatum</strong> (Steiertatbestand)</li>
    <li><strong>Detailléiert Beschreiwung</strong> vun de geleeschten Déngschter</li>
    <li><strong>Unzuel vu Stonnen oder Deeg</strong> (recommandéiert)</li>
    <li><strong>Eenheetspräis ouni TVA</strong></li>
    <li><strong>Betrag ouni TVA, TVA a Gesamtbetrag</strong></li>
    <li><strong>Applikabelen TVA-Taux</strong></li>
</ul>

<h2>Wéi enged TVA-Taux applizéieren?</h2>

<p>Als Freelancer applizéiert Dir allgemeng den <strong>Normaltaux vun 17 %</strong> fir déi meeschten Déngschtleeschtungen.</p>

<h3>Besonnesch Fäll</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applikabel TVA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Professionelle Client zu Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Professionelle Client an der EU (B2B innergemeinschaftlech)</td><td class="border border-gray-300 px-4 py-2">0 % (Autoliquidatioun, Art. 17 LIVA + Art. 196 Direktiv)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (fir déi meescht intellektuell Leeschtungen ausserhalb vum Lëtzebuerger TVA-Beräich)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Privatclient zu Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">TVA 17 %</td></tr>
    </tbody>
</table>

<h2>Engem Client am Ausland fakturéieren</h2>

<h3>Professionelle Client an der EU</h3>

<p>Wann Äre Client eng Entreprise an engem anere EU-Land ass:</p>

<ol>
    <li><strong>Iwwerpréift seng TVA-Nummer</strong> am <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a>-System</li>
    <li><strong>Applizéiert keng TVA</strong> op Ärer Rechnung (Ort vun der Besteierung beim Client, Art. 17 LIVA)</li>
    <li><strong>Setzt d'Mentioun derbäi</strong>: <em>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</em></li>
    <li><strong>Gitt d'TVA-Nummer</strong> vum Client op der Rechnung un</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Obligatoresch Mentioun – korrekt gesetzlech Basis</p>
    <p>Déi kanonesch Mentioun fir d'innergemeinschaftlech B2B-Autoliquidatioun ass <strong>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</strong>. Den Artikel 196 bezeechent de Client als Schëllner (Art. 226 §11bis Direktiv). Net ze verwiesselen mam Artikel 44 vun der Direktiv (Ort vun der Besteierung) nach mam Artikel 44 vum Lëtzebuerger Gesetz vun 1979 (sektoriell Befreiungen, ouni Zesummenhang).</p>
</div>

<h3>Client ausserhalb vun der EU</h3>

<p>Fir Clienten ausserhalb vun der Europäescher Unioun falen déi gängeg intellektuell Leeschtunge vun engem Freelancer (Beroodung, Informatik, Reklamm, Iwwersetzung) net an de Lëtzebuerger TVA-Beräich. Vermierkt „TVA net applicabel – Déngscht ausserhalb vun der EU lokaliséiert".</p>

<h2>D'Nummeréierung vun Äre Rechnungen (Artikel 63 LIVA, Punkt 3°)</h2>

<p>Är Rechnunge mussen enger <strong>chronologescher a kontinuéierlecher Nummeréierung</strong> follegen:</p>

<ul>
    <li>Keng Lack an der Sequenz</li>
    <li>Fräit awer kohärent Format (z. B. 2026-001, 2026-002…)</li>
    <li>Jäerlechen Neistart erlaabt</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Notzt eng Fakturatiounssoftware wéi faktur.lu, fir automatesch konform Nummeren ze generéieren a Feeler ze vermeiden.</p>
</div>

<h2>Är TVA-Deklaratioune verwalten</h2>

<p>Eemol ageschriwwen, gitt Dir Deklaratiounen of, deenen hir Periodizitéit vun Ärem Ëmsaz ouni TVA ofhänkt. <strong>Et ass d'AED, déi Äert Regime bestëmmt</strong> — Dir wielt et net.</p>

<ul>
    <li><strong>Ëmsaz &lt; 112 000 €/Joer</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €/Joer</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz &gt; 620 000 €/Joer</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ D'Jooresdeklaratioun ersetzt déi periodesch Deklaratiounen net</p>
    <p class="text-amber-700">Dat ass de deiersten Ierrtum op dëser Säit. Wann Dir Trimester- oder Méintdeklaratiounen ofgitt, musst Dir <strong>zousätzlech</strong> eng <strong>récapitulativ Jooresdeklaratioun</strong> ofginn, virum <strong>1. Mee vum nächste Joer</strong>. Se ze vergiessen léist Verspéidungsstrofen aus, obwuel Dir Är TVA am Laf vum Joer komplett bezuelt hutt.</p>
</div>

<p>Déi periodesch Deklaratioune ginn ier den 15. vum Mount no der betreffender Period ofginn. Alles leeft online iwwer <strong>eCDF (eTVA)</strong>.</p>

<h2>D'FAIA-Datei fir Freelancer</h2>

<p>Wann Dir eng Fakturatiounssoftware notzt, musst Dir op Ufro vun der AED eng <strong>FAIA-Datei</strong> produzéiere kënnen – ënner Bedingungen. D'AED-FAQ schléisst Assujettien mat engem Ëmsaz ≤ 112 000 € ouni TVA aus, sou datt ganz kleng Freelancer an der Franchise allgemeng keng FAIA-Datei musse virleeën. Mat Ärer Fiduciaire no Ärem Regime ze bestätegen.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generéiert Är FAIA-Datei</p>
    <p class="text-green-700">Eis Software produzéiert automatesch eng konform FAIA-2.01-Datei, prett fir all Steierkontroll vun der AED.</p>
</div>

<h2>Rotschléi fir Freelancer um Ufank</h2>

<ol>
    <li><strong>Notzt vun Ufank un eng ugepasste Software</strong>, fir Feeler ze vermeiden</li>
    <li><strong>Haalt all Är Rechnungen op</strong> (ausgestallt an erhalen) fir 10 Joer (Art. 16 Handelsgesetzbuch + Art. 65 LIVA)</li>
    <li><strong>Trennt</strong> Är privat a berufflech Konten</li>
    <li><strong>Fakturéiert séier</strong> (spéitstens den 15. vum Mount no der Leeschtung, Art. 63 LIVA)</li>
    <li><strong>Iwwerpréift d'TVA-Nummeren</strong> vun Äre EU-Clienten ier Dir fakturéiert (VIES)</li>
    <li><strong>Notéiert Är zwee Termine</strong>: déi periodesch Deklaratiounen an d'Jooresdeklaratioun</li>
    <li><strong>Frot e Comptabel</strong> bei komplexe Froen</li>
</ol>

<h2>Heefeg Feeler ze vermeiden</h2>

<ul>
    <li>❌ Ouni TVA-Nummer fakturéieren (wann Dir assujetti sidd)</li>
    <li>❌ Obligatoresch Mentioune vergiessen (Art. 63 LIVA)</li>
    <li>❌ E falschen TVA-Taux applizéieren</li>
    <li>❌ Net-sequenziell Nummeréierung</li>
    <li>❌ No dem 15. vum Mount no der Leeschtung fakturéieren</li>
    <li>❌ D'EU-TVA-Nummeren net iwwerpréiwen (VIES)</li>
    <li>❌ <strong>Mengen, d'Trimesterdeklaratioune géifen d'Jooresdeklaratioun erspueren</strong></li>
    <li>❌ „Artikel 44 Direktiv" (Ort vun der Besteierung) mam „Artikel 196 Direktiv" (Mentioun Autoliquidatioun) verwiesselen</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen, Tariffer an TVA-Prozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikelen 17, 57bis, 63, 65</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – TVA-Deklaratiounen a Periodizitéiten</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Franchisereegelung (Art. 57bis)</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Obligatoresch Mentiounen op de Rechnungen</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun innergemeinschaftlechen TVA-Nummeren</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<h2>Conclusioun</h2>

<p>D'Fakturatioun als Freelancer zu Lëtzebuerg ass net komplizéiert, wann Dir d'Reegele befollegt. Mat enger ugepasster Fakturatiounssoftware wéi faktur.lu erstellt Dir konform Rechnungen a wéinege Klicken, mat alle obligatoresche Mentiounen an den automatesch richtegen TVA-Tariffer. Konzentréiert Iech op Äre Beruff – ëm Är Konformitéit këmmere mir eis!</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/5-heefeg-feeler-freelancer-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">5 heefeg Feeler op enger Freelancer-Rechnung →</a></li><li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li><li><a href="/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg (Seuil 50 000 €) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'komplette-guide-rechnungsstellung-letzebuerg-2026',
                'locale' => 'lb',
                'translation_key' => 'guide-complet-facturation-luxembourg-2026',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">D'Fakturéierung zu Lëtzebuerg ënnerläit präzise Reegelen, déi duerch d'Steiergesetzgebung festgeluecht sinn. Ob Dir e KMU, Freelancer oder eng grouss Entreprise sidd, dëse Guide erkläert alles wat Dir wësse musst fir konform ze fakturéieren.</p>

<h2>Firwat d'Konformitéit vun Äre Rechnungen essentiell ass</h2>

<p>Zu Lëtzebuerg ass eng Rechnung net nëmmen en einfacht kommerziellt Dokument. Et ass en <strong>offiziellt Comptabilitéitsdokument</strong> dat als Basis déngt fir:</p>

<ul>
    <li>D'Berechnung an d'Récupératioun vun der TVA</li>
    <li>Steierkontrollen vun der Administration des Contributions Directes (ACD)</li>
    <li>D'Generéierung vum FAIA-Fichier fir d'Administration de l'Enregistrement et des Domaines (AED)</li>
    <li>De Beweis vun Äre geschäftlechen Transaktiounen</li>
</ul>

<p>Eng net-konform Rechnung kann zum <strong>Refus vum TVA-Ofzuch</strong> fir Äre Client an zu <strong>finanziellen Strofen</strong> fir Är Entreprise féieren.</p>

<h2>Pflichtangaben op enger Lëtzebuerger Rechnung</h2>

<p>Laut Artikel 63 vum Lëtzebuerger TVA-Gesetz muss all Rechnung déi folgend Informatiounen enthalen:</p>

<h3>Informatiounen iwwer den Aussteller</h3>

<ul>
    <li><strong>Numm oder Firmenbezeechnung</strong> vun Ärer Entreprise</li>
    <li><strong>Komplett Adress</strong> vum Firmesëtz</li>
    <li><strong>Intracommunautär TVA-Nummer</strong> (Format LU + 8 Zifferen)</li>
    <li><strong>Etabléierungsautoriséierungsnummer</strong> (wann zoutreffend)</li>
</ul>

<h3>Informatiounen iwwer de Client</h3>

<ul>
    <li><strong>Numm oder Firmenbezeechnung</strong> vum Client</li>
    <li><strong>Komplett Adress</strong></li>
    <li><strong>TVA-Nummer</strong> (Flicht bei intracommunautären B2B-Transaktiounen)</li>
</ul>

<h3>Rechnungsinformatiounen</h3>

<ul>
    <li><strong>Eenzegaarteg Rechnungsnummer</strong> an chronologescher Reiefolleg</li>
    <li><strong>Ausstellungsdatum</strong> vun der Rechnung</li>
    <li><strong>Liwwer- oder Leeschtungsdatum</strong> (wann anescht)</li>
</ul>

<h3>Leeschtungsdetailer</h3>

<ul>
    <li><strong>Kloer Beschreiwung</strong> vun de Wueren oder Servicer</li>
    <li><strong>Quantitéit</strong> an <strong>Nettoeenzelpräis</strong></li>
    <li><strong>Applicabelen TVA-Taux</strong> pro Linn</li>
    <li><strong>TVA-Betrag</strong> pro Taux</li>
    <li><strong>Netto-, TVA- a Bruttosumm</strong></li>
</ul>

<h2>Rechnungsnummeréierung</h2>

<p>D'Nummeréierung vun Äre Rechnungen muss streng Reegelen respektéieren:</p>

<ul>
    <li><strong>Eenzegaarteg chronologesch Sequenz</strong>: keng Lächer an der Nummeréierung</li>
    <li><strong>Fräit awer konsistent Format</strong> (z.B.: 2026-0001, RE-2026-001)</li>
    <li><strong>Eng Serie</strong> pro Geschäftsjoer (ausser a Spezialfäll)</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Tipp</p>
    <p class="text-purple-700">Benotzt eng Fakturéierungssoftware wéi faktur.lu fir automatesch eng konform Nummeréierung ze garantéieren an Feeler ze vermeiden.</p>
</div>

<h2>Déi verschidden TVA-Tariffer zu Lëtzebuerg</h2>

<p>Lëtzebuerg applizéiert véier TVA-Tariffer:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taux</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applikatioun</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Normaltaux (Majoritéit vun de Wueren a Servicer)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Mëttleren Taux (Wäiner, verschidden Brennstoffer)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Reduzéierten Taux (Gas, Stroum, Coiffer)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Super-reduzéierten Taux (Liewensmëttel, Bicher, Medikamenter)</td>
        </tr>
    </tbody>
</table>

<h2>Ausstellungs- an Opbewahrungsfristen</h2>

<h3>Ausstellungsfrist</h3>

<p>Eng Rechnung muss <strong>spéitstens den 15. vum nächste Mount</strong> no der Liwwerung vun de Wueren oder der Leeschtung ausgestallt ginn.</p>

<h3>Opbewahrungsdauer</h3>

<p>Dir musst Är Rechnungen <strong>10 Joer</strong> opbewahren, vum Enn vum concernéierte Geschäftsjoer un. Dës Flicht gëllt fir ausgestallten AN kritt Rechnungen.</p>

<h2>De FAIA-Fichier: Eng Lëtzebuerger Flicht</h2>

<p>De <strong>FAIA (Fichier d'Audit Informatisé)</strong> ass e standardiséierten XML-Fichier deen all Entreprise, déi Comptabilitéits- oder Fakturéierungssoftware benotzt, op Ufro vun der Steierverwaltung muss virweisen.</p>

<p>Dëse Fichier enthält:</p>

<ul>
    <li>All Är Buchungen</li>
    <li>Är ausgestallten a kritt Rechnungen</li>
    <li>Är Clienten a Fournisseuren</li>
    <li>Är Bezuelungen</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu generéiert automatesch Äre FAIA-Fichier</p>
    <p class="text-green-700">Eis Software produzéiert mat engem Klick e konforme FAIA-Fichier, prett fir un d'AED bei enger Kontroll ze iwwerginn.</p>
</div>

<h2>Feeler déi Dir vermeiden sollt</h2>

<ol>
    <li><strong>D'TVA-Nummer vergiessen</strong> op intracommunautären B2B-Rechnungen</li>
    <li><strong>Net-sequentiell Nummeréierung benotzen</strong> (Lächer an der Serie)</li>
    <li><strong>TVA-Tariffer net ënnerscheeden</strong> wann méi Tariffer gëllen</li>
    <li><strong>Rechnungen ze spéit ausstellen</strong> (no dem 15. vum nächste Mount)</li>
    <li><strong>Rechnungen net 10 Joer opbewahren</strong></li>
</ol>

<h2>Conclusioun</h2>

<p>D'Fakturéierung zu Lëtzebuerg erfuerdert Suergfalt a Konformitéit. Mat enger <strong>passender Fakturéierungssoftware</strong> wéi faktur.lu garantéiert Dir datt all legal Ufuerderungen erfëllt ginn a Dir wäertvoll Zäit spuert.</p>

<p>Eis Léisung generéiert automatesch konform Rechnungen mat alle Pflichtangaben, korrekter Nummeréierung an integréiertem FAIA-Export.</p>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'pflichtinformatiounen-rechnung-letzebuerg',
                'locale' => 'lb',
                'translation_key' => 'mentions-obligatoires-facture-luxembourg',
                'content' => <<<'ARTICLE_HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">A kuerz</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li>D'obligatoresch Mentiounen ergi sech aus dem <strong>Artikel 63 LIVA</strong>: Identitéit an Adress vun de Parteien, TVA-Nummeren, Datum, <strong>eenzeg fortlafend Nummer</strong>, Bezeechnung, Basis ouni TVA, Taux, TVA-Betrag, Gesamtbetrag.</li><li>Eng <strong>net konform</strong> Rechnung kann vum Client zréckgewise ginn an eng <strong>AED-Geldstrof vun 250 € bis 10 000 €</strong> no sech zéien (Art. 77 LIVA) — oder <strong>10 % bis 50 % vun der betraffener TVA</strong>, wann de Staat Recetten verluer huet.</li><li>Bedingt Mentiounen: <strong>Autoliquidatioun</strong> (Art. 196 Direktiv), Franchise, Befreiungen.</li><li><strong>Opbewahrung 10 Joer</strong> vun de Rechnungen a comptabele Stécker.</li></ul></div>
<p class="lead">Eng net konform Rechnung kann vun Ärem Client zréckgewise ginn an Iech AED-Geldstrofen aussetzen. Hei ass déi komplett Checklëscht vun den obligatoresche Mentiounen fir onbeanstandbar Lëtzebuerger Rechnungen — op Basis vum <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 LIVA</a> an der Lëscht, déi d'AED publizéiert huet.</p>

<h2>Checklëscht vun den obligatoresche Mentiounen</h2>

<p>Den <strong>Artikel 63 LIVA</strong> (geännert Gesetz vum 12. Februar 1979) an d'Lëscht vun der AED verlaange Folgendes:</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer den Emetteur</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollstännege Numm oder Firmennumm</strong></li>
        <li>☐ <strong>Vollstänneg Adress</strong> vum Sëtz</li>
        <li>☐ <strong>Innergemeinschaftlech TVA-Nummer</strong> (Format LU + 8 Zifferen)</li>
        <li>☐ <strong>RCS-Nummer</strong> fir agedroen Händler a Gesellschaften — eng Flicht aus der <em>Legislatioun iwwer d'Handels- a Gesellschaftsregëster</em>, net aus dem TVA-Gesetz</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer de Client</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Vollstännege Numm oder Firmennumm</strong></li>
        <li>☐ <strong>Vollstänneg Adress</strong></li>
        <li>☐ <strong>TVA-Nummer</strong> (obligatoresch fir innergemeinschaftlecht B2B - iwwer <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> validéiert)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informatiounen iwwer d'Rechnung</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Eenzeg a fortlafend Rechnungsnummer</strong> (Art. 63 LIVA, Punkt 3°)</li>
        <li>☐ <strong>Ausstellungsdatum</strong> vun der Rechnung</li>
        <li>☐ <strong>Liwwer- oder Leeschtungsdatum</strong> — gesetzlech verlaangt, <em>wann et ofwäicht</em> vum Ausstellungsdatum (Art. 226 §7 vun der Direktiv). Et systematesch unzeginn bleift gutt Praxis: et hieft bei enger Kontroll all Ondäitlechkeet iwwer de Steiertatbestand op</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Detail vun de Wueren oder Déngschter</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Kloer an detailléiert Beschreiwung</strong></li>
        <li>☐ <strong>Geliwwert oder geleescht Quantitéit</strong></li>
        <li>☐ <strong>Eenheetspräis ouni TVA</strong></li>
        <li>☐ <strong>Eventuell Reduktiounen oder Rabatter</strong></li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ TVA-Informatiounen</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Applikabelen TVA-Taux</strong> pro Linn (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
        <li>☐ <strong>TVA-Betrag</strong> pro Taux</li>
        <li>☐ <strong>Steierbasis</strong> (Betrag ouni TVA) pro TVA-Taux</li>
        <li>☐ <strong>Mentioun vun der Befreiung oder Autoliquidatioun</strong>, wa relevant (kuckt d'Tabell hei ënnen)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totaler</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total ouni TVA</strong></li>
        <li>☐ <strong>Total TVA</strong></li>
        <li>☐ <strong>Total mat TVA</strong></li>
    </ul>
</div>

<h2>Bedingt Mentiounen no de Fäll</h2>

<p>No der Aart vun der Operatioun muss eng bestëmmte Mentioun op der Rechnung stoen. Hei sinn déi kodifizéiert Formuléierungen:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mentioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Gesetzlech Basis</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">B2B-Déngscht innergemeinschaftlech (Client an engem anere Member-Staat)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 17 LIVA (Ort) + Art. 196 Direktiv (Schëllner) + Art. 226 §11bis (Mentioun)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Innergemeinschaftlech B2B-Wuerelieferung</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA-Befreiung – Artikel 138 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 d) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Export ausserhalb vun der EU</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA-Befreiung – Artikel 146 vun der Direktiv 2006/112/EG"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 43 §1 a) LIVA</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">TVA-Franchise (kleng Entreprisen)</td>
            <td class="border border-gray-300 px-4 py-2 font-mono text-sm">„TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979"</td>
            <td class="border border-gray-300 px-4 py-2 text-sm">Art. 57bis LIVA (Seuil 50 000 €)</td>
        </tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Net ze verwiesselen</p>
    <p>Fir d'innergemeinschaftlech B2B-Autoliquidatioun iwwerhuele vill Virlagen d'Referenz op den „Artikel 44 vun der Direktiv 2006/112/EG". Den Artikel 44 definéiert den <strong>Ort vun der Besteierung</strong> (beim Client), mä déi kodifizéiert obligatoresch Mentioun verweist op den <strong>Artikel 196</strong> (deen de Client als Schëllner bezeechent). Bevirzegt d'Mentioun Artikel 196.</p>
</div>

<h3>Besonnesch Fäll vun der Fakturatioun</h3>

<p><strong>Akontsrechnung</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Akont op d'Bestellung Nr. [Referenz] vum [Datum]"</p>
</div>

<p><strong>Avoir (Kreditnot)</strong>:</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">„Avoir op d'Rechnung Nr. [Nummer] vum [Datum]"</p>
</div>

<h2>D'Nummeréierung vun de Rechnungen (Art. 63 LIVA, Punkt 3°)</h2>

<p>D'Nummeréierung muss dës Reegele respektéieren:</p>

<ul>
    <li><strong>Eenzeg</strong>: all Rechnung huet eng eege Nummer</li>
    <li><strong>Chronologesch</strong>: d'Nummere follegen der Uerdnung vun der Ausstellung</li>
    <li><strong>Ouni Ënnerbriechung</strong>: keng Lack an der Sequenz</li>
    <li><strong>Net erëmbenotzbar</strong>: eng Nummer däerf nëmmen eemol vergi ginn</li>
</ul>

<h3>Beispiller vun akzeptéierte Formater</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Beispill</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Joer + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Präfix + Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Einfach Nummer</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Bezuelungsbedingungen</h2>

<p>Och wann den Artikel 63 LIVA se net verlaangt, sinn dës Uginne <strong>staark ze recommandéieren</strong>:</p>

<ul>
    <li><strong>Bezuelungsfrist</strong> (standardméisseg: 30 Deeg nom Empfang, geännert Gesetz vum 18. Abrëll 2004)</li>
    <li><strong>Fälegkeetsdatum</strong></li>
    <li><strong>Bankkoordinaten</strong> (IBAN, BIC)</li>
    <li><strong>Verzuchszënsen</strong> tëscht Professioneller — BCE-Referenztaux + 8 Punkten, also <strong>10,15 % am 1. Semester 2026</strong>, all Semester ugepasst — an d'<strong>Pauschal vun 40 €</strong></li>
</ul>

<h2>Opbewahrung vun de Rechnungen</h2>

<p>Dir musst Är Rechnungen (ausgestallt an erhalen) <strong>10 Joer</strong> laang vum Ofschloss vum Exercice un opbewahren — Artikel 16 vum Handelsgesetzbuch an Artikel 65 LIVA. A Pabeierform oder elektronesch (PDF/A mat Integritéitsgarantien). Kuckt d'<a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">comptabel Flichten op Guichet.lu</a>.</p>

<h2>Konsequenze vun enger net konformer Rechnung</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risiken</p>
    <ul class="text-red-700 mt-2">
        <li>Refus vum TVA-Ofzuch duerch Äre Client</li>
        <li><strong>Administrativ Geldstrof vun 250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
        <li>Wann de Manktem zu enger Netbezuelung vun TVA oder zu enger onreegelméisseger Réckerstattung gefouert huet: <strong>Geldstrof vun 10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong</li>
        <li>Bei Refus, Rechnungen a comptabel Stécker bei enger Kontroll ze weisen: <strong>bis zu 25 000 € pro Dag Verspéidung</strong>, no engem Avertissement</li>
        <li>Bei schwéierer Steierhannerzéiung oder Steierbedruch: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, Prisong vun engem Mount bis fënnef Joer a Verloscht vun de biergerleche Rechter fir 5 bis 10 Joer (Art. 80 LIVA)</li>
    </ul>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">De Plafong vun 10 000 € ass net dee gréisste Risiko</p>
    <p>Wat weh deet, ass déi proportional Geldstrof. Bei engem Sträit iwwer 80 000 € TVA bedeit eng Sanktioun vun 10 bis 50 % tëscht 8 000 an 40 000 € — onofhängeg dovunner, wéi vill formell Infraktioune festgestallt ginn.</p>
</div>

<h2>Beispill vun enger konformer Rechnung</h2>

<p>Hei sinn déi wesentlech Elementer vun enger konformer Rechnung:</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">Är Gesellschaft SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">RECHNUNG</p>
            <p>N° 2026-0042</p>
            <p>Datum: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Rechnung un:</p>
        <p>Client Entreprise SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Beschreiwung</th>
                <th class="text-right py-2">Quantitéit</th>
                <th class="text-right py-2">Eenheetspräis</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">Total ouni TVA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">Beroodungsleeschtung</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>Total ouni TVA: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Total mat TVA: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Maacht et Iech einfach mat faktur.lu</h2>

<p>Konform Rechnungen mat der Hand ze erstellen ass feeleranfälleg. <strong>faktur.lu</strong> automatiséiert d'Konformitéit:</p>

<ul>
    <li>✅ All obligatoresch Mentiounen virausgefëllt</li>
    <li>✅ Automatesch a fortlafend Nummeréierung (Art. 63 LIVA)</li>
    <li>✅ Automatesch TVA-Berechnung no dem Fall</li>
    <li>✅ Ugepasst gesetzlech Mentiounen (Autoliquidatioun, Export, Franchise 57bis…)</li>
    <li>✅ Integréierten FAIA-Export fir d'Steierkontrolle vun der AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'obligatoresch Mentiounen, Artikelreferenzen a Seuiler kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikelen 17, 43, 57bis, 63, 65, 77, 80</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Obligatoresch Mentiounen op de Rechnungen</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/VAT-sanctions-remedies.html" target="_blank" rel="noopener">TVA-Sanktiounen a Rechtsmëttelen</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Direktiv 2006/112/EG – Artikelen 138, 146, 196, 226</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/gutschrift-letzebuerg-richteg-erstellen" class="text-primary-500 hover:text-primary-600 text-sm">Kreditnot Lëtzebuerg →</a></li><li><a href="/lb/blog/artikel-63-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch" class="text-primary-500 hover:text-primary-600 text-sm">Fortlafend Nummeréierung (Artikel 63 LIVA) →</a></li><li><a href="/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg (Seuil 50 000 €) →</a></li></ul></div>
ARTICLE_HTML,
            ],
            [
                'slug' => 'tva-letzebuerg-tariffer-berechnung-obligatiounen',
                'locale' => 'lb',
                'translation_key' => 'tva-luxembourg-taux-calcul-obligations',
                'content' => <<<'ARTICLE_HTML'
<p class="lead">D'TVA (Taxe sur la Valeur Ajoutée) ass en zentralt Element vun der Lëtzebuerger Besteierung. D'Verständnis vun de verschiddenen Tariffer, hir korrekt Uwendung a d'Anhale vun den Erklärungspflichten ass essentiell fir all Entreprise.</p>

<h2>D'TVA-Tariffer zu Lëtzebuerg 2026</h2>

<p>Lëtzebuerg applizéiert <strong>véier TVA-Tariffer</strong>, déi zu den niddregsten an der Europäescher Unioun gehéieren:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taux</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Numm</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applikatioun</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">17%</td>
            <td class="border border-gray-300 px-4 py-2">Normaltaux</td>
            <td class="border border-gray-300 px-4 py-2">Majoritéit vun de Wueren a Servicer</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">14%</td>
            <td class="border border-gray-300 px-4 py-2">Mëttleren Taux</td>
            <td class="border border-gray-300 px-4 py-2">Wäiner, fest Brennstoffer, Werbedrécksaachen</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">8%</td>
            <td class="border border-gray-300 px-4 py-2">Reduzéierten Taux</td>
            <td class="border border-gray-300 px-4 py-2">Gas, Stroum, Coiffer, Renovatiounsaarbechten</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">3%</td>
            <td class="border border-gray-300 px-4 py-2">Super-reduzéierten Taux</td>
            <td class="border border-gray-300 px-4 py-2">Liewensmëttel, Bicher, Medikamenter, Transport</td>
        </tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold text-blue-800">ℹ️ Wousst Dir?</p>
    <p class="text-blue-700">Den Normaltaux vun 17% zu Lëtzebuerg ass den niddregsten an der Europäescher Unioun, wou den Duerchschnëtt bei ongeféier 21% läit.</p>
</div>

<h2>Detail vun den Tariffer pro Kategorie</h2>

<h3>Super-reduzéierten Taux vun 3%</h3>

<ul>
    <li>Liewensmëttel (ouni Alkohol a Gastronomie)</li>
    <li>Bicher, Zeitungen an Zäitschrëften</li>
    <li>Medikamenter</li>
    <li>Personentransport</li>
    <li>Hotelaccommodatioun</li>
    <li>Entréen zu kulturellen a sportlechen Evenementer</li>
    <li>Medizinesch an zäntlech Behandlungen (net befreit)</li>
</ul>

<h3>Reduzéierten Taux vun 8%</h3>

<ul>
    <li>Liwwerung vun Äerdgas a Stroum</li>
    <li>Coifferservicer</li>
    <li>Fënsterputzen</li>
    <li>Kleng Reparaturservicer (Vëloen, Schong, Kleedung)</li>
</ul>

<h3>Mëttleren Taux vun 14%</h3>

<ul>
    <li>Wäiner (manner wéi 13% Alkohol)</li>
    <li>Fest mineral Brennstoffer</li>
    <li>Heizungsmasutt</li>
    <li>Verschidde Werbedrécksaachen</li>
</ul>

<h3>Normaltaux vun 17%</h3>

<p>All Wueren a Servicer déi keen reduzéierten Taux kréien, ënnerleeën dem Normaltaux vun 17%.</p>

<h2>TVA-befreit Ëmsätz</h2>

<p>Verschidden Ëmsätz sinn zu Lëtzebuerg <strong>vun der TVA befreit</strong>:</p>

<ul>
    <li>Medizinesch a paramedizinesch Servicer</li>
    <li>Bildungsservicer</li>
    <li>Bank- a Finanzoperatiounen</li>
    <li>Versécherungsoperatiounen</li>
    <li>Immobilienvermietung (ausser bei Optioun)</li>
    <li>Intracommunautär Liwwerungen (ënner Bedéngungen)</li>
    <li>Exporter ausserhalb der EU</li>
</ul>

<h2>TVA-Berechnung</h2>

<h3>TVA aus Nettobetrag berechnen</h3>

<p>Fir de Bruttobetrag aus dem Nettopräis ze berechnen:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Bruttobetrag = Nettobetrag × (1 + TVA-Taux)</p>
    <p class="mt-2 text-sm text-gray-600">Beispill: 100€ netto × 1,17 = 117€ brutto</p>
</div>

<h3>Nettobetrag aus Bruttobetrag berechnen</h3>

<p>Fir den Nettobetrag aus dem Brutto ze fannen:</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Nettobetrag = Bruttobetrag ÷ (1 + TVA-Taux)</p>
    <p class="mt-2 text-sm text-gray-600">Beispill: 117€ brutto ÷ 1,17 = 100€ netto</p>
</div>

<h2>Erklärungspflichten</h2>

<p>D'Periodizitéit hänkt vun Ärem Ëmsaz ouni TVA of. <strong>Et ass d'AED, déi Äert Regime bestëmmt</strong> — Dir wielt et net.</p>

<ul>
    <li><strong>Ëmsaz ënner 112 000 €</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz iwwer 620 000 €</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ D'Jooresdeklaratioun ersetzt déi periodesch Deklaratiounen net</p>
    <p class="text-amber-700">Wann Dir Trimester- oder Méintdeklaratiounen ofgitt, musst Dir <strong>zousätzlech</strong> eng récapitulativ Jooresdeklaratioun ofginn, virum <strong>1. Mee</strong> vum nächste Joer. Se ze vergiessen léist Verspéidungsstrofen aus, obwuel Dir Är TVA am Laf vum Joer komplett bezuelt hutt.</p>
</div>

<p>Déi periodesch Deklaratioune ginn ier den 15. vum nächste Mount online iwwer <strong>eCDF</strong> ofginn. D'Bezuelung begleet d'Deklaratioun; bei engem TVA-Kredit kann eng Réckerstattung gefrot ginn.</p>
<h2>Intracommunautär TVA</h2>

<h3>Verkaf un EU-Entreprisen (B2B)</h3>

<p>Liwwerunge vu Wueren a Servicer un Steierpflichteg an aneren EU-Länner sinn <strong>vun der Lëtzebuerger TVA befreit</strong>. De Client rechent d'TVA a sengem Land selwer of (Reverse Charge).</p>

<p><strong>Bedéngungen:</strong></p>
<ul>
    <li>De Client muss eng gëlteg intracommunautär TVA-Nummer hunn</li>
    <li>Dës Nummer muss op der Rechnung stoen</li>
    <li>Den Hiwäis "TVA-Befreiung - Artikel 43 Paragraf 1 k) vum TVA-Gesetz" muss erschéngen</li>
</ul>

<h3>Verkaf un EU-Privatpersounen (B2C)</h3>

<p>Eng <strong>eenzeg Schwell vun 10 000 € pro Joer</strong> reegelt dës Verkeef. Dorënner applizéiert Dir d'Lëtzebuerger TVA; doriwwer d'TVA vum Land vum Client, deklaréiert iwwer de <strong>OSS</strong>-Guichet oder duerch eng Aschreiwung an all Land.</p>

<p><strong>Opgepasst:</strong> dës Schwell ass <strong>gemeinsam</strong> fir Fernverkeef vu Wueren <em>an</em> elektronesch, Telekom- an audiovisuell Déngschtleeschtungen — iwwer all EU-Länner, ouni Lëtzebuerg. Ze verfollegen ass also d'<strong>Zomm</strong> vun Äre europäesche B2C-Verkeef, net all Kategorie eenzel. D'Operatioun, déi d'Schwell iwwerschreit, ass scho am Land vum Client steierflichteg.</p>
<h2>D'Intracommunautär TVA-Nummer</h2>

<p>D'Lëtzebuerger TVA-Nummer huet de Format <strong>LU + 8 Zifferen</strong> (z.B.: LU12345678).</p>

<p>Dës Nummer muss erschéngen op:</p>
<ul>
    <li>All Äre Rechnungen</li>
    <li>Ären TVA-Erklärungen</li>
    <li>Ären Intrastat-Meldungen (DEB)</li>
</ul>

<h2>TVA-Récupératioun</h2>

<p>Als Steierpflichtegen kënnt Dir d'<strong>TVA ofzéien</strong> déi Dir op Är geschäftlech Akafe bezuelt hutt. Dofir:</p>

<ul>
    <li>Dir musst eng <strong>konform Rechnung</strong> hunn</li>
    <li>Den Akaf muss mat Ärer <strong>berufflecher Aktivitéit</strong> verbonne sinn</li>
    <li>D'TVA muss <strong>korrekt ausgewisen</strong> sinn op der Rechnung</li>
</ul>

<h2>Praktesch Tipps</h2>

<ol>
    <li><strong>Préift ëmmer den applicabelen Taux</strong> virum Fakturéieren</li>
    <li><strong>Validéiert d'TVA-Nummeren</strong> vun Ären EU-Clienten op der VIES-Websäit</li>
    <li><strong>Bewaart Är Rechnungen 10 Joer</strong> fir Är Ofzich ze justifiéieren</li>
    <li><strong>Benotzt passend Software</strong> fir Rechenfeler ze vermeiden</li>
    <li><strong>Plant Är Erklärungen am Viraus</strong> fir Verspéidungsstrofen ze vermeiden</li>
</ol>

<h2>Heefeg Spezialfäll</h2>

<h3>Restauratioun: 3 % an 17 % op der selwechter Rechnung</h3>
<p>Iessen, dat op der Plaz servéiert gëtt, ass bei <strong>3 %</strong>, mä <strong>alkoholesch Gedrénks</strong> bleiwen bei <strong>17 %</strong>. Béid Tariffer stinn also op der selwechter Rechnung, an d'Opdeelung muss ersiichtlech sinn.</p>

<h3>Hotellerie: 3 % onofhängeg vun der Klassifikatioun</h3>
<p>Am Géigesaz zu anere Länner applizéiert Lëtzebuerg eenheetlech <strong>3 %</strong> op all Iwwernuechtung, vun der Auberge bis zum Luxushotel. Niewenleeschtungen (Spa, haus-eegent Restaurant) follegen hirem eegene Regime.</p>

<h3>E-Bicher: 3 %</h3>
<p>Digital Bicher profitéiere vum selwechten Taux wéi gedréckte, also <strong>3 %</strong>. Streaming-Abonnementer fir Video oder Musek bleiwen dogéint bei <strong>17 %</strong>: dat sinn digital Déngschtleeschtungen, keng Bicher.</p>

<h3>Renovatiounsaarbechten: d'Bedingung, déi jiddereen vergësst</h3>
<p>Renovatiounsaarbechten un enger Wunneng, déi als Haaptwunnsëtz genotzt gëtt, kënne vum superreduzéierten Taux vun <strong>3 %</strong> profitéieren, an de Grenzen a Bedingungen, déi duerch groussherzoglecht Reglement festgeluecht sinn (Alter vum Gebai, steierleche Plafong pro Wunneng).</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ D'Autorisatioun muss VIRUN den Aarbechte gefrot ginn</p>
    <p class="text-red-700">Den <strong>Artikel 65bis vum TVA-Gesetz</strong> ass kloer: den Assujetti, deen esou Aarbechte mécht, „muss bei der Administratioun d'Autorisatioun fir d'Applikatioun vum superreduzéierten Taux ufroen", an dës Ufro „muss […] <strong>virun der Realisatioun vun den Aarbechten</strong> agereecht ginn". Eréischt bauen an dann d'3 % verlaangen funktionéiert net. Bei enger Renovatioun geet den Ënnerscheed tëscht 3 % an 17 % an d'Dausenden.</p>
</div>

<h2>Sech am Taux ieren: d'Konsequenzen</h2>

<ul>
    <li><strong>Ze nidderegen Taux</strong>: Nofuerderung vun der AED, Verzuchszënsen, an administrativ Geldstrof vun <strong>250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA)</li>
    <li><strong>Manktem, dat de Staat ëm Recetten bruecht huet</strong>: Geldstrof vun <strong>10 % bis 50 % vun der betraffener TVA</strong> — proportional, also ouni Plafong</li>
    <li><strong>Ze héijen Taux</strong>: de Client kann d'Réckerstattung verlaangen, an Dir musst bei der AED regulariséieren</li>
    <li><strong>Refus vum Ofzuch op der Client-Säit</strong>: ass den Taux offensichtlech falsch, kann dem Client d'Récupératioun verweigert ginn</li>
    <li><strong>Virsätzleche Bedruch</strong>: Geldstrof vun 25 000 € bis zum Zéngfache vum TVA-Betrag, a Prisong vun engem Mount bis fënnef Joer (Art. 80 LIVA)</li>
</ul>

<h2>Heefeg Froen</h2>

<h3>Ass den Normaltaux 2026 wierklech bei 17 %?</h3>
<p>Jo. Den Taux war 2023 provisoresch op 16 % erofgesat ginn; den 1. Januar 2024 ass en op <strong>17 %</strong> zréckkomm an ass zanterhier onverännert.</p>

<h3>Wéi ee Taux gëllt, wann meng Rechnung iwwer e Tauxwiessel geet?</h3>
<p>Entscheedend ass d'Datum vum <strong>Steiertatbestand</strong> — also vun der Liwwerung oder vun der Leeschtung, net d'Ausstellungs- oder Bezuelungsdatum.</p>

<h3>Wéi beleeën ech e Spezialtaux bei enger Kontroll?</h3>
<p>Haalt op, wat d'Operatioun der Kategorie zouuerdnet: déi genee Aart vum Gutt oder vun der Leeschtung an, bei Renovatiounen, <strong>d'Autorisatioun, déi virum Chantier kritt gouf</strong>. Ouni si ginn d'3 % refuséiert, och wann d'Aarbechten inhaltlech beguinstegt gewiescht wieren.</p>

<h3>Ass meng Aktivitéit befreit?</h3>
<p>D'Befreiunge sinn am Gesetz ofschléissend opgezielt a gi net vermutt. Am Zweiwel klärt et mat Ärer Fiduciaire, ier Dir déi éischt Rechnung ausstellt — eng zu Onrecht applizéiert Befreiung léist sech schlecht nohuelen.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Tariffer, Annexen A a B, Artikelen 65bis, 77, 80</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/declaration-tva.html" target="_blank" rel="noopener">Guichet.lu – TVA-Deklaratiounen a Periodizitéiten</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
</ul>
<h2>Conclusioun</h2>

<p>D'TVA-Gestioun zu Lëtzebuerg erfuerdert gutt Kenntnisser vun den applicabelen Tariffer an Erklärungspflichten. Mat enger Fakturéierungssoftware wéi faktur.lu profitéiert Dir vun der automatescher Uwendung vun de richtegen Tariffer an gesetzes-konformen Rechnungen.</p>
<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
ARTICLE_HTML,
            ],
        ];
    }
};
