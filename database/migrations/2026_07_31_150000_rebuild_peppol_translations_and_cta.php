<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « peppol-b2g-luxembourg-guide-complet-2026 » — fin du travail
 * entamé le 2026-07-31 : reconstruction intégrale des traductions et
 * correction d'un dernier claim produit oublié.
 *
 * 1. CLAIM RÉSIDUEL DANS LE CTA. La correction précédente avait traité
 *    la section principale et deux réponses de FAQ, mais laissé le bloc
 *    d'appel à l'action, qui répétait exactement la promesse retirée :
 *    « faktur.lu intègre nativement la transmission Peppol BIS 3.0.
 *    Créez votre compte gratuit et envoyez votre première facture
 *    électronique en quelques minutes. » Un lecteur qui saute au CTA —
 *    ce que fait une bonne partie du trafic — repartait donc avec
 *    l'information fausse. Le CTA porte désormais sur ce qui est livré :
 *    la génération du fichier BIS 3.0 conforme.
 *
 * 2. TRADUCTIONS RECONSTRUITES. C'était la pire dérive du blog :
 *    allemand 4 610 caractères et luxembourgeois 4 259 contre 12 088 en
 *    français, avec 5 H2 contre 9 et AUCUN H3. Anglais et portugais
 *    étaient à peine mieux (5 871 et 6 093, 7 H2, 3 H3). Les quatre
 *    langues sont désormais intégrales et structurellement identiques au
 *    français.
 *
 *    Ce qui manquait dans les traductions courtes, et qui n'est pas
 *    cosmétique : l'absence de seuil de montant, le calendrier échelonné
 *    2022-2023, la mise en garde sur le schéma 0184 (danois) confondu
 *    avec le 9938 luxembourgeois, le format XRechnung également accepté,
 *    l'alternative MyGuichet.lu pour qui n'est pas raccordé, et la FAQ
 *    entière.
 *
 * FAITS VÉRIFIÉS et confirmés exacts, conservés tels quels : loi du
 * 13 décembre 2021 ; calendrier du 18 mai 2022, 18 octobre 2022 et
 * 18 mars 2023 ; absence de seuil ; schéma 9938 (LU:VAT) contre 0184
 * (registre danois) ; Peppol BIS Billing 3.0 sur UBL conforme EN 16931 ;
 * XRechnung 3.0.1 accepté ; obligation B2B intracommunautaire au
 * 1er juillet 2030 au titre de ViDA. La couverture du réseau — plus de
 * 100 pays et plusieurs millions de participants — a été vérifiée et
 * tient : environ 4,4 millions de participants enregistrés en juin 2026.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    private const KEY = 'peppol-b2g-luxembourg-guide-complet-2026';

    public function up(): void
    {
        // 1. Corriger le CTA dans les cinq langues.
        $ctaFixes = [
            'fr' => [
                'faktur.lu intègre nativement la transmission Peppol BIS 3.0. Créez votre compte gratuit et envoyez votre première facture électronique en quelques minutes.',
                'faktur.lu génère le fichier Peppol BIS Billing 3.0 conforme de vos factures, prêt à être transmis au secteur public. Créez votre compte gratuit et testez en quelques minutes.',
            ],
        ];

        foreach ($ctaFixes as $locale => [$old, $new]) {
            $post = DB::table('blog_posts')->where('translation_key', self::KEY)->where('locale', $locale)->first(['id', 'content']);
            if ($post && str_contains($post->content, $old)) {
                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => str_replace($old, $new, $post->content),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Reconstruire les quatre traductions.
        foreach (['de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : restaurer des traductions amputées de
        // 60 % et un CTA promettant une transmission non livrée n'aurait
        // pas de sens.
    }

    private function de(): string
    {
        return <<<'HTML'
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

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/de/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Rechnungssoftware wählen →</a></li><li><a href="/de/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer LU-Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Bereit für die Peppol-Rechnungsstellung?</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt die konforme Peppol-BIS-Billing-3.0-Datei Ihrer Rechnungen, bereit zur Übermittlung an den öffentlichen Sektor. Legen Sie Ihr kostenloses Konto an und testen Sie es in wenigen Minuten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
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

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/en/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Choosing invoicing software →</a></li><li><a href="/en/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a LU invoice →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Ready to invoice through Peppol?</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates the compliant Peppol BIS Billing 3.0 file for your invoices, ready to submit to the public sector. Create your free account and try it in minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
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

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/lb/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Fakturatiounssoftware wielen →</a></li><li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger LU-Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Prett fir iwwer Peppol ze fakturéieren?</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert de konforme Peppol-BIS-Billing-3.0-Fichier vun Äre Rechnungen, prett fir un den ëffentleche Secteur iwwerdroen ze ginn. Leet Äre gratis Kont un an testt et a wéinege Minutten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">Desde 2022-2023, a faturação eletrónica através do <strong>Peppol</strong> é obrigatória para os fornecedores do setor público luxemburguês, <strong>qualquer que seja o montante da fatura</strong>. Este guia explica-lhe tudo o que precisa de saber para estar em conformidade em 2026.</p>

<h2>O que é o Peppol?</h2>

<p>O <strong>Peppol (Pan-European Public Procurement OnLine)</strong> é uma rede internacional de faturação eletrónica que permite trocar documentos comerciais (faturas, notas de encomenda) de forma normalizada entre empresas e administrações públicas. Gerido pela <a href="https://peppol.org" target="_blank" rel="noopener">OpenPeppol</a>, estende-se atualmente a mais de 100 países e conta com vários milhões de participantes registados.</p>

<p>No Luxemburgo, o Peppol é o canal oficial para a faturação eletrónica B2G (Business-to-Government). Qualquer empresa que fature ao Estado, aos municípios ou a estabelecimentos públicos deve utilizar este formato.</p>

<h2>Quem é abrangido?</h2>

<p>Se for fornecedor de uma das seguintes entidades, deve faturar através do Peppol:</p>

<ul>
    <li><strong>O Estado luxemburguês</strong> e os seus ministérios</li>
    <li><strong>Os municípios luxemburgueses</strong></li>
    <li><strong>Os estabelecimentos públicos</strong> (hospitais, escolas, etc.)</li>
    <li><strong>Todos os contratos públicos e contratos de concessão</strong>, independentemente do montante</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Sem limiar de montante</p>
    <p>Ao contrário de uma ideia difundida, <strong>não existe qualquer limiar mínimo</strong> (do tipo «30 000 €») para a obrigação. A <a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">lei de 13 de dezembro de 2021</a> impõe a faturação eletrónica para <strong>todas</strong> as faturas emitidas no âmbito de um contrato público ou de concessão, do mais pequeno ao maior montante.</p>
</div>

<h3>Calendário de aplicação</h3>

<p>A obrigação entrou em vigor de forma faseada, consoante a dimensão do operador económico:</p>

<ul>
    <li><strong>18 de maio de 2022</strong>: grandes operadores económicos</li>
    <li><strong>18 de outubro de 2022</strong>: operadores económicos de média dimensão</li>
    <li><strong>18 de março de 2023</strong>: pequenos operadores e empresas recém-criadas</li>
</ul>

<p>Desde 18 de março de 2023, <strong>todos</strong> os fornecedores do setor público luxemburguês estão, portanto, abrangidos.</p>

<h2>Como funciona o Peppol?</h2>

<p>A rede Peppol assenta num modelo de quatro cantos (4-corner model):</p>

<ol>
    <li><strong>O emissor</strong> (a sua empresa) cria a fatura</li>
    <li><strong>O ponto de acesso emissor</strong> (o seu software de faturação ou o respetivo Access Point) coloca a fatura na rede Peppol</li>
    <li><strong>O ponto de acesso destinatário</strong> (do lado da administração) recebe a fatura</li>
    <li><strong>O destinatário</strong> (a administração pública) processa a fatura</li>
</ol>

<p>Cada participante na rede é identificado por um <strong>Peppol Participant ID</strong> único. No Luxemburgo, o esquema padrão é o <strong>9938</strong> (LU:VAT), baseado no número de IVA. Formato: <code>9938:LU########</code>.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">⚠ Erro clássico: esquema 0184</p>
    <p>Muitas fontes confundem. O esquema <strong>0184</strong> corresponde ao registo de empresas <strong>dinamarquês</strong> (DIGSTORG/CVR), não ao Luxemburgo. O esquema LU correto é o <strong>9938</strong>. Verifique na <a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">lista de códigos EAS oficial do Peppol</a>.</p>
</div>

<h2>O formato UBL e o Peppol BIS Billing 3.0</h2>

<p>As faturas Peppol no Luxemburgo utilizam o formato <strong>Peppol BIS Billing 3.0</strong>, assente no padrão XML <strong>UBL (Universal Business Language)</strong> e conforme à norma europeia <strong>EN 16931</strong>. A sua fatura deve conter:</p>

<ul>
    <li>As informações do emissor (nome, endereço, n.º de IVA)</li>
    <li>As informações do destinatário (nome, Peppol Participant ID)</li>
    <li>As linhas de faturação (descrição, quantidade, preço unitário)</li>
    <li>Os montantes de IVA repartidos por taxa</li>
    <li>Os totais (sem IVA, IVA, com IVA)</li>
    <li>As referências de encomenda ou de contrato</li>
</ul>

<p>O formato <strong>XRechnung 3.0.1</strong> (norma alemã conforme à EN 16931) é igualmente aceite pelas administrações luxemburguesas.</p>

<h2>Alternativas se não estiver ligado ao Peppol</h2>

<p>Se ainda não tiver software ligado ao Peppol, pode utilizar o canal alternativo oficial:</p>

<ul>
    <li><strong>MyGuichet.lu</strong>: formulários em linha que permitem submeter manualmente uma fatura eletrónica conforme às administrações destinatárias</li>
</ul>

<p>Veja o procedimento completo em <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Transmissão de uma fatura eletrónica para um contrato público</a>.</p>

<h2>Peppol e faktur.lu: o que está disponível hoje</h2>

<p>Preferimos ser precisos neste ponto a assinalar uma caixa.</p>

<p><strong>Disponível desde já:</strong> o faktur.lu gera o <strong>ficheiro Peppol BIS Billing 3.0 (UBL)</strong> das suas faturas, conforme ao formato exigido pelo setor público luxemburguês. Descarrega-o a partir da fatura e transmite-o pelo canal da sua escolha — nomeadamente o ponto de acesso do <strong>CTIE</strong>, que os organismos públicos sem ponto próprio utilizam.</p>

<p><strong>Em entrada em produção:</strong> a transmissão automática através de um ponto de acesso integrado, que lhe poupará esse passo manual. O formato está tecnicamente pronto, a implementação para todas as contas ainda não. Atualizaremos esta página no dia em que estiver.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Entretanto, está em conformidade</p>
    <p>A obrigação legal incide sobre o <strong>formato</strong> da fatura e a sua transmissão eletrónica, não sobre a ferramenta usada para a entregar. Um ficheiro BIS 3.0 conforme, submetido pelo canal do seu comprador público, cumpre a obrigação.</p>
</div>

<h2>Vantagens da faturação Peppol</h2>

<ul>
    <li><strong>Processamento acelerado</strong>: as faturas Peppol são processadas automaticamente, reduzindo os prazos de pagamento</li>
    <li><strong>Menos erros</strong>: o formato estruturado elimina os erros de introdução manual</li>
    <li><strong>Rastreabilidade</strong>: cada fatura é seguida de ponta a ponta na rede</li>
    <li><strong>Conformidade</strong>: cumpre as obrigações legais luxemburguesas (lei de 13 de dezembro de 2021)</li>
    <li><strong>Alcance internacional</strong>: o Peppol está atualmente implantado em mais de <strong>100 países</strong>, com dezenas de Peppol Authorities nacionais</li>
</ul>

<h2>Perguntas frequentes</h2>

<h3>A faturação Peppol é obrigatória no B2B?</h3>

<p>Ainda não no Luxemburgo para o B2B doméstico. O pacote europeu <strong>ViDA (VAT in the Digital Age)</strong>, adotado em 2025, prevê a obrigação de faturação eletrónica para as operações <strong>B2B intracomunitárias</strong> a partir de <strong>1 de julho de 2030</strong>. Para o B2B doméstico, a harmonização completa está prevista até 2035. Ver <a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - ViDA</a>.</p>

<h3>Quanto custa o envio através do Peppol?</h3>

<p>A geração do ficheiro Peppol BIS 3.0 está incluída na sua subscrição faktur.lu, sem custo adicional por fatura. A própria rede Peppol não fatura ao emissor: o eventual custo depende do ponto de acesso utilizado para o encaminhamento.</p>

<h3>Como encontrar o Peppol ID de uma administração?</h3>

<p>Os Peppol Participant IDs das administrações luxemburguesas estão disponíveis no <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Também os pode procurar diretamente no faktur.lu ao criar o cliente.</p>

<h3>Qual é o esquema Peppol no Luxemburgo?</h3>

<p>O esquema padrão é o <strong>9938</strong> (LU:VAT, baseado no número de IVA). Formato completo: <code>9938:LU########</code>. Não confundir com 0184, o esquema dinamarquês.</p>

<h3>Quais são as sanções em caso de não conformidade?</h3>

<p>A administração pode recusar o processamento de uma fatura em papel ou PDF não conforme e exigir a sua retransmissão através do Peppol, o que atrasa o pagamento. Para o detalhe das sanções formais, consulte a lei de 13 de dezembro de 2021 ou o seu contabilista.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>O calendário ViDA, os limiares e as Peppol Authorities evoluem. Esta página é atualizada regularmente, mas para a sua situação consulte o <a href="https://guichet.public.lu/" target="_blank" rel="noopener">Guichet.lu</a> e a documentação oficial do Peppol.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/2021/12/13/a869/jo" target="_blank" rel="noopener">Lei de 13 de dezembro de 2021 sobre a faturação eletrónica B2G</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/marche-public-concession/facturation/transmission-facture-electronique-marche-public-contrat-concession.html" target="_blank" rel="noopener">Guichet.lu - Transmissão de fatura eletrónica</a></li>
    <li><a href="https://mindigital.gouvernement.lu/fr/dossiers/2021/facturation-electronique.html" target="_blank" rel="noopener">Ministério da Digitalização - Faturação eletrónica</a></li>
    <li><a href="https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/" target="_blank" rel="noopener">Peppol - Lista de códigos EAS (esquemas de identificadores)</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Escolher software de faturação →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura LU →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Pronto para faturar através do Peppol?</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera o ficheiro Peppol BIS Billing 3.0 conforme das suas faturas, pronto a ser transmitido ao setor público. Crie a sua conta gratuita e experimente em poucos minutos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
