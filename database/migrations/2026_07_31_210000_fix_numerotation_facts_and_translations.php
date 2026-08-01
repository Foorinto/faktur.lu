<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article pivot « Article 63 LIVA : numérotation séquentielle des factures ».
 *
 * 1. Français — deux corrections.
 *
 *    a) Référence légale imprécise. L'article citait « le point 3° du paragraphe
 *       sur les mentions obligatoires ». La liste des mentions figure au
 *       paragraphe 8 de l'article 63, et le numéro séquentiel y est le
 *       point 2°, pas le 3° (le point 3° désigne le numéro d'identification TVA
 *       du fournisseur). La citation entre guillemets, elle, est exacte au mot
 *       près : « un numéro séquentiel, basé sur une ou plusieurs séries, qui
 *       identifie la facture de façon unique ».
 *
 *    b) Revendication produit fausse : « compteur remis à 1 chaque 1er janvier
 *       (ou non, selon votre préférence) ». Le reset annuel est inconditionnel.
 *       GenerateInvoiceNumberAction::nextSequence() calcule toujours le maximum
 *       sur whereYear('issued_at', $year) ; aucun réglage ne permet une
 *       séquence continue sur plusieurs années.
 *
 * 2. DE, EN, LB, PT — traductions reconstruites à 100 %. Elles plafonnaient à
 *    8 663–9 126 caractères contre 11 190 en français, avec une section et
 *    cinq liens manquants.
 *
 * Liens internes construits à partir du slug réel de chaque langue. Les URL
 * externes (legilux, pfi.public.lu) ont été testées : les cinq répondent 200.
 */
return new class extends Migration
{
    private const KEY = 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire';

    /** Corrections ciblées du français : [avant, après] */
    private const FR_FIXES = [
        [
            "L'<strong>article 63 LIVA</strong> (point 3° du paragraphe sur les mentions obligatoires) impose",
            "L'<strong>article 63 LIVA</strong> (paragraphe 8, point 2°) impose",
        ],
        [
            "précise, à son point 3°, que toute facture émise par un assujetti doit comporter :",
            "précise, à son paragraphe 8, point 2°, que toute facture émise par un assujetti doit comporter :",
        ],
        [
            "<li><strong>Reset annuel automatique</strong> : compteur remis à 1 chaque 1<sup>er</sup> janvier (ou non, selon votre préférence)</li>",
            "<li><strong>Reset annuel automatique</strong> : le compteur repart à 1 à chaque année civile, sur la base de la date de facture</li>",
        ],
        [
            "la référence correcte est l'article 63 LIVA (point 3°).",
            "la référence correcte est l'article 63 LIVA (paragraphe 8, point 2°).",
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
        // Les traductions d'origine étaient incomplètes : rien à restaurer
        // utilement. Seul le français est réversible.
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
<p class="lead"><strong>Artikel 63 LIVA</strong> (Absatz 8, Nummer 2) stellt eine einfache, aber grundlegende Regel auf: Ihre luxemburgischen Rechnungen muessen eine <strong>eindeutige, fortlaufende und lueckenlose Nummer</strong> tragen. Eine Luecke in der Reihenfolge oder eine doppelte Nummer kann eine Steuernachforderung ausloesen. Hier die Regel im Klartext – und wie Sie nie mehr daran denken muessen.</p>

<h2>Was steht genau in Artikel 63 LIVA?</h2>

<p><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 des luxemburgischen MwSt-Gesetzes</a> bestimmt in Absatz 8, Nummer 2, dass jede von einem Steuerpflichtigen ausgestellte Rechnung enthalten muss:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „Eine fortlaufende Nummer, die auf einer oder mehreren Serien beruht und die Rechnung eindeutig identifiziert."
</blockquote>

<p>Im Klartext drei kumulative Regeln:</p>

<ol>
    <li><strong>Eindeutigkeit</strong>: Zwei Rechnungen duerfen nie dieselbe Nummer tragen</li>
    <li><strong>Fortlaufende Vergabe</strong>: Die Nummern folgen aufeinander (1, 2, 3, 4 …)</li>
    <li><strong>Lueckenlosigkeit</strong>: keine Luecke (kein Sprung von 5 auf 8 ohne 6 und 7)</li>
</ol>

<p>Diese Pflicht gilt fuer <strong>alle</strong> der luxemburgischen MwSt unterliegenden Steuerpflichtigen, auch im Rahmen der Kleinunternehmerregelung (Artikel 57bis LIVA seit dem 1. Januar 2025), selbst wenn die Rechnung den Hinweis „MwSt nicht anwendbar" traegt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Nicht zu verwechseln</p>
    <p>Artikel <strong>61</strong> LIVA behandelt dagegen die <strong>Person des Steuerschuldners</strong> bei bestimmten Umsaetzen (etwa beim inlaendischen Reverse-Charge). Mit der Rechnungsnummerierung hat er nichts zu tun, entgegen dem, was viele Quellen faelschlich wiedergeben. Die zutreffende Rechtsgrundlage ist Artikel <strong>63</strong>.</p>
</div>

<h2>Warum es diese Regel gibt</h2>

<p>Das Ziel ist schlicht: <strong>Umsatzverschleierung verhindern</strong>. Ohne lueckenlose Nummerierung koennte ein Unternehmen einzelne Rechnungen in seiner MwSt-Erklaerung leicht „vergessen". Die Fortlaufendkeit erlaubt es der AED, auf einen Blick zu pruefen, ob alle ausgestellten Rechnungen erklaert wurden.</p>

<p>Aus demselben Grund enthaelt die <strong>FAIA</strong> (die informatisierte Pruefdatei der AED) die Liste der Rechnungsnummern des geprueften Zeitraums. Eine in der FAIA entdeckte Luecke loest sofort eine Aufforderung zur Stellungnahme aus.</p>

<h2>Zulaessige Formate</h2>

<p>Artikel 63 schreibt kein bestimmtes Format vor. Sie koennen jede Konvention verwenden, solange die Fortlaufendkeit gewahrt bleibt:</p>

<ul>
    <li><strong>Einfache Nummerierung</strong>: 1, 2, 3, 4 …</li>
    <li><strong>Mit Praefix</strong>: F-001, F-002, F-003 …</li>
    <li><strong>Mit Jahr</strong>: 2026-001, 2026-002, 2026-003 …</li>
    <li><strong>Mit Praefix und Jahr</strong>: F-2026-001, F-2026-002 …</li>
    <li><strong>Nach Kunde</strong> (nicht empfohlen): ACME-001, ACME-002 (bricht die globale Fortlaufendkeit)</li>
</ul>

<p><strong>Tipp</strong>: Eine Nummer aus <strong>Jahr und fortlaufendem Zaehler</strong> (z. B. F-2026-001) ist am besten lesbar und erlaubt es, jedes Jahr wieder bei 1 zu beginnen. Das ist in Luxemburg die verbreitetste Praxis.</p>

<h2>Jaehrlicher Neustart: erlaubt oder nicht?</h2>

<p>Ja, Sie duerfen die Nummerierung in <strong>jedem Steuerjahr wieder bei 1</strong> beginnen. Das ist sogar die gaengigste Praxis. Entscheidend ist, dass die Reihenfolge <strong>innerhalb desselben Jahres</strong> lueckenlos bleibt.</p>

<p>Gueltiges Beispiel:</p>

<ul>
    <li>F-2025-148 (letzte Rechnung aus 2025)</li>
    <li>F-2026-001 (erste Rechnung aus 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>Der Sprung von 148 auf 001 ist zulaessig, weil das Jahr wechselt. Innerhalb von 2026 duerfen Sie dagegen nicht von F-2026-001 auf F-2026-003 springen, ohne F-2026-002 ausgestellt zu haben.</p>

<h2>Was passiert bei einem Fehler?</h2>

<h3>Fall 1 – Sie haben eine Nummer doppelt vergeben</h3>

<p>Die AED geht davon aus, dass eine der beiden Rechnungen <strong>fingiert oder betruegerisch</strong> ist. Sie muessen:</p>

<ol>
    <li>Eine <strong>Gutschrift</strong> auf eine der beiden Rechnungen ausstellen (buchhalterische Stornierung)</li>
    <li>Eine <strong>neue Rechnung</strong> mit der naechsten freien Nummer ausstellen</li>
    <li>Alle drei Belege aufbewahren (die zwei urspruenglichen Rechnungen und die Gutschrift)</li>
</ol>

<h3>Fall 2 – Sie haben eine Luecke in der Reihenfolge (Nummer 5 fehlt zwischen 4 und 6)</h3>

<p>Der schwerere Fall. Sie muessen die <strong>Luecke erklaeren</strong> koennen. Drei zulaessige Erklaerungen:</p>

<ul>
    <li>Die Rechnung wurde ausgestellt und dann <strong>per Gutschrift storniert</strong> (Sie bewahren beide Belege auf)</li>
    <li>Technischer Fehler: Sie haben einen nicht finalisierten Entwurf angelegt. Dann finalisieren und archivieren Sie ihn besser, auch wenn die Leistung nicht erbracht wurde (mit dem Hinweis „stornierte Rechnung")</li>
    <li>Dokumentierter Softwarefehler (selten)</li>
</ul>

<p>Ohne glaubhafte Erklaerung unterstellt die AED eine Verschleierung und kann den fehlenden Umsatz <strong>schaetzen</strong> (Schaetzungsveranlagung). Moegliche Verwaltungsgeldbussen: 250 bis 10.000 EUR je Verstoss (Artikel 77 LIVA).</p>

<h3>Fall 3 – Sie wollen eine bereits finalisierte Rechnung loeschen</h3>

<p>Rechtlich unmoeglich. Eine finalisierte Rechnung muss zehn Jahre archiviert bleiben (<a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Artikel 16 des Handelsgesetzbuchs</a> und Artikel 65 LIVA). Zur Stornierung:</p>

<ul>
    <li>Eine <strong>Gutschrift</strong> ueber denselben Betrag ausstellen (in der Regel mit eigener Nummernserie: AV-2026-001)</li>
    <li>Beide Belege aufbewahren (Rechnung und Gutschrift)</li>
</ul>

<h2>Die klassischen Fallen</h2>

<ol>
    <li><strong>Manuelle Nummerierung in Excel</strong>: sehr hohes Risiko doppelter Nummern oder Luecken (vergessene letzte Rechnung, zerstoerte Formel, misslungenes Kopieren)</li>
    <li><strong>Mehrere parallele Serien</strong> (eine je Kunde oder Projekt): bei einer Pruefung schwer zu rechtfertigen</li>
    <li><strong>Neustart der Nummerierung mitten im Jahr</strong> ohne Wechsel des Geschaeftsjahres: unzulaessig</li>
    <li><strong>Loeschen finalisierter Rechnungen</strong> ohne Ersatzgutschrift: Verstoss gegen Artikel 16 des Handelsgesetzbuchs und Artikel 63 LIVA</li>
</ol>

<h2>Wie Sie das automatisieren</h2>

<p>Eine Rechnungssoftware wie <a href="/de" class="text-primary-500 hover:underline font-medium">faktur.lu</a> uebernimmt die Nummerierung fuer Sie:</p>

<ul>
    <li><strong>Automatische fortlaufende Nummerierung</strong>: eine doppelte Nummer ist nicht moeglich</li>
    <li><strong>Keine Luecken</strong>: bei jeder Finalisierung erhoeht sich der Zaehler</li>
    <li><strong>Anpassbares Format</strong>: Praefix, Jahr, Laenge des Zaehlers – alles konfigurierbar</li>
    <li><strong>Automatischer Jahresneustart</strong>: der Zaehler beginnt in jedem Kalenderjahr wieder bei 1, massgeblich ist das Rechnungsdatum</li>
    <li><strong>Getrennte Serien</strong> fuer Rechnungen, Gutschriften und Angebote (unabhaengige Zaehler, jeder fuer sich lueckenlos)</li>
    <li><strong>Eindeutigkeitspruefung</strong> bei jeder Erstellung</li>
</ul>

<p>Damit ist Artikel 63 LIVA kein Thema mehr: Ihre Nummerierung ist bauartbedingt konform.</p>

<h2>FAQ – Artikel 63 LIVA und Nummerierung</h2>

<h3>Und wenn ich unter der Kleinunternehmerregelung fakturiere (Artikel 57bis LIVA)?</h3>
<p>Artikel 63 LIVA gilt <strong>trotzdem</strong>. Jede ausgestellte Rechnung muss eine fortlaufende, lueckenlose Nummer tragen, auch ohne erhobene MwSt.</p>

<h3>Darf ich je Kunde nummerieren (ACME-001, ACME-002 …)?</h3>
<p>Technisch ja – aber es ist dringend abzuraten. Bei einer Pruefung verlangt die AED die <strong>globale</strong> Fortlaufendkeit. Sie muessten dann ueber alle Kundenserien hinweg nachweisen, dass keine Luecke besteht: aufwendig und riskant.</p>

<h3>Gilt das auch fuer Angebote?</h3>
<p>Nein, Artikel 63 LIVA betrifft nur <strong>Rechnungen und Gutschriften</strong>. Ihre Angebote koennen Sie nummerieren, wie Sie wollen (eine fortlaufende Nummerierung bleibt fuer die eigene Nachverfolgung dennoch zu empfehlen).</p>

<h3>Darf ich getrennte Serien fuer Rechnungen und Gutschriften fuehren?</h3>
<p>Ja, das ist sogar empfehlenswert. Eine Serie fuer Rechnungen (F-2026-001, F-2026-002 …) und eine fuer Gutschriften (AV-2026-001, AV-2026-002 …). Jede muss fuer sich lueckenlos sein, beide sind voneinander unabhaengig.</p>

<h3>Was tun, wenn ich mitten im Jahr die Software wechsle?</h3>
<p>Sie muessen die <strong>Reihenfolge fortsetzen</strong>. Standen Sie im alten System bei F-2026-148, muss Ihre erste Rechnung im neuen F-2026-149 lauten. Bei faktur.lu laesst sich der Startzaehler bei der Einrichtung setzen, damit keine Luecke entsteht.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jaehrlich zu pruefen</p>
    <p>Artikelverweise und Rechnungspflichten koennen sich aendern. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich jedoch an Ihren Treuhaender oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geaendertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 63</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtangaben auf Rechnungen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Luxemburgisches Handelsgesetzbuch – Artikel 16 (Aufbewahrung zehn Jahre)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026. Zuvor unter dem Titel „Artikel 61 LIVA" veroeffentlicht – die zutreffende Referenz ist Artikel 63 LIVA (Absatz 8, Nummer 2).</em></p>

<h2>Weiterfuehrende Artikel</h2>
<ul>
    <li><a href="/de/blog/steuerpruefung-luxemburg-vorbereiten">Steuerpruefung der AED in Luxemburg: Leitfaden 2026</a></li>
    <li><a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden">FAIA Luxemburg: vollstaendiger Leitfaden zur Pruefdatei</a></li>
    <li><a href="/de/blog/pflichtangaben-rechnung-luxemburg">Pflichtangaben auf einer Rechnung in Luxemburg</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Konforme Nummerierung, ohne daran zu denken</h3>
    <p class="text-primary-800 mb-4">faktur.lu verwaltet Ihre fortlaufende Nummerierung automatisch, Artikel 63 LIVA ist bauartbedingt eingehalten. Anpassbares Format, Jahresneustart inbegriffen.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos starten</a>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead"><strong>Article 63 LIVA</strong> (paragraph 8, point 2) sets out a simple but fundamental rule: your Luxembourg invoices must carry a <strong>unique, sequential and unbroken number</strong>. A gap in the sequence or a duplicate can trigger a tax reassessment. Here is the rule explained – and how to stop thinking about it.</p>

<h2>What does article 63 LIVA actually say?</h2>

<p><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Article 63 of the Luxembourg VAT law</a> states, at paragraph 8, point 2, that every invoice issued by a taxable person must carry:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "A sequential number, based on one or more series, which uniquely identifies the invoice."
</blockquote>

<p>In plain terms, three cumulative rules:</p>

<ol>
    <li><strong>Uniqueness</strong>: two invoices can never carry the same number</li>
    <li><strong>Sequentiality</strong>: numbers follow one another in order (1, 2, 3, 4…)</li>
    <li><strong>Continuity</strong>: no gaps (no jump from 5 to 8 without 6 and 7)</li>
</ol>

<p>The obligation applies to <strong>every person</strong> subject to Luxembourg VAT, including under the small-business exemption (article 57bis LIVA since 1 January 2025), even where the invoice carries the mention "VAT not applicable".</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Not to be confused</p>
    <p>Article <strong>61</strong> LIVA, by contrast, deals with <strong>who is liable for the VAT</strong> in certain transactions (domestic reverse charge, for instance). It has nothing to do with invoice numbering, contrary to what many sources report in error. The correct legal basis is article <strong>63</strong>.</p>
</div>

<h2>Why the rule exists</h2>

<p>The purpose is straightforward: <strong>to prevent concealed turnover</strong>. Without continuous numbering, a business could easily "forget" certain invoices in its VAT return. Sequentiality lets the AED check at a glance whether every invoice issued has been declared.</p>

<p>That is also why the <strong>FAIA</strong> (the AED's computerised audit file) includes the list of invoice numbers for the period under review. A gap detected in the FAIA immediately triggers a request for explanation.</p>

<h2>Accepted formats</h2>

<p>Article 63 does not prescribe a single format. You may use any convention, provided sequentiality is preserved:</p>

<ul>
    <li><strong>Plain numbering</strong>: 1, 2, 3, 4…</li>
    <li><strong>With a prefix</strong>: F-001, F-002, F-003…</li>
    <li><strong>With the year</strong>: 2026-001, 2026-002, 2026-003…</li>
    <li><strong>With prefix and year</strong>: F-2026-001, F-2026-002…</li>
    <li><strong>Per client</strong> (not advisable): ACME-001, ACME-002 (breaks overall continuity)</li>
</ul>

<p><strong>Tip</strong>: a number combining <strong>year and sequential counter</strong> (e.g. F-2026-001) is the most readable and lets you restart at 1 each year. It is the most widespread practice in Luxembourg.</p>

<h2>Annual reset: allowed or not?</h2>

<p>Yes, you may restart numbering at <strong>1 each tax year</strong>. It is in fact the most common practice. What matters is that the sequence is unbroken <strong>within a given year</strong>.</p>

<p>A valid example:</p>

<ul>
    <li>F-2025-148 (last invoice of 2025)</li>
    <li>F-2026-001 (first invoice of 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>Going from 148 to 001 is allowed because the year changes. But within 2026, you cannot go from F-2026-001 to F-2026-003 without having issued F-2026-002.</p>

<h2>What happens if you get it wrong?</h2>

<h3>Case 1 – You issued a duplicate (two invoices with the same number)</h3>

<p>The AED treats one of the two invoices as <strong>fictitious or fraudulent</strong>. You must:</p>

<ol>
    <li>Issue a <strong>credit note</strong> against one of the two invoices (accounting cancellation)</li>
    <li>Issue a <strong>new invoice</strong> with the next available number</li>
    <li>Keep all three documents (the two original invoices and the credit note)</li>
</ol>

<h3>Case 2 – You have a gap in the sequence (number 5 missing between 4 and 6)</h3>

<p>A more serious case. You must be able to <strong>explain the gap</strong> to the AED. Three acceptable explanations:</p>

<ul>
    <li>The invoice was issued and then <strong>cancelled by credit note</strong> (you keep both documents)</li>
    <li>Technical error: you created a draft that was never finalised. In that case it is better to finalise and archive it, even if the service was never delivered (marking it "cancelled invoice")</li>
    <li>A documented software bug (rare)</li>
</ul>

<p>Without a credible explanation, the AED assumes concealment and may <strong>estimate</strong> the missing turnover (assessment by estimation). Possible administrative fines: EUR 250 to EUR 10,000 per breach (article 77 LIVA).</p>

<h3>Case 3 – You want to delete an invoice that is already finalised</h3>

<p>Legally impossible. A finalised invoice must remain archived for ten years (<a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">article 16 of the Commercial Code</a> and article 65 LIVA). To cancel it:</p>

<ul>
    <li>Issue a <strong>credit note</strong> for the same amount (usually on a separate series: AV-2026-001)</li>
    <li>Keep both documents (invoice and credit note)</li>
</ul>

<h2>The classic traps to avoid</h2>

<ol>
    <li><strong>Manual numbering in Excel</strong>: very high risk of duplicates or gaps (a forgotten last invoice, a broken formula, a botched copy-paste)</li>
    <li><strong>Several parallel series</strong> (one per client or project): hard to justify in an audit</li>
    <li><strong>Resetting numbering mid-year</strong> without a change of financial year: not allowed</li>
    <li><strong>Deleting finalised invoices</strong> with no replacement credit note: a breach of article 16 of the Commercial Code and article 63 LIVA</li>
</ol>

<h2>How to automate it and forget about it</h2>

<p>Invoicing software such as <a href="/en" class="text-primary-500 hover:underline font-medium">faktur.lu</a> handles numbering for you:</p>

<ul>
    <li><strong>Automatic sequential numbering</strong>: a duplicate cannot be created</li>
    <li><strong>No gaps possible</strong>: the counter increments on every finalisation</li>
    <li><strong>Customisable format</strong>: prefix, year, counter length – all configurable</li>
    <li><strong>Automatic annual reset</strong>: the counter restarts at 1 each calendar year, based on the invoice date</li>
    <li><strong>Separate series</strong> for invoices, credit notes and quotes (independent counters, each one unbroken)</li>
    <li><strong>Uniqueness check</strong> on every creation</li>
</ul>

<p>With that, article 63 LIVA is no longer a concern: your numbering is compliant by construction.</p>

<h2>FAQ – Article 63 LIVA and numbering</h2>

<h3>What if I invoice under the VAT exemption (article 57bis LIVA)?</h3>
<p>Article 63 LIVA applies <strong>all the same</strong>. Every invoice issued, even with no VAT charged, must carry a sequential and unbroken number.</p>

<h3>Can I number per client (ACME-001, ACME-002…)?</h3>
<p>Technically yes – but it is strongly discouraged. In an audit, the AED asks for <strong>overall</strong> sequentiality. You would then have to prove there is no gap across all client series, which is complex and risky.</p>

<h3>Do quotes fall under this rule?</h3>
<p>No, article 63 LIVA covers only <strong>invoices and credit notes</strong>. You may number your quotes however you like (though sequential numbering remains advisable for your own tracking).</p>

<h3>Can I keep separate series for invoices and credit notes?</h3>
<p>Yes, and it is even recommended. One series for invoices (F-2026-001, F-2026-002…) and one for credit notes (AV-2026-001, AV-2026-002…). Each must be unbroken, but they are independent of one another.</p>

<h3>What if I switch software mid-year?</h3>
<p>You must <strong>continue the sequence</strong> in the new software. If you were at F-2026-148 in the old one, your first invoice in the new one must be F-2026-149. faktur.lu lets you set the starting counter during setup so that no gap appears.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Article references and invoicing obligations can change. This page is updated regularly, but for your own situation, consult your fiduciaire or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) – article 63</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Mandatory information on invoices</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Luxembourg Commercial Code – article 16 (ten-year retention)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026. Previously published under the title "Article 61 LIVA" – the correct reference is article 63 LIVA (paragraph 8, point 2).</em></p>

<h2>Further reading</h2>
<ul>
    <li><a href="/en/blog/tax-audit-luxembourg-how-to-prepare">AED tax audit in Luxembourg: 2026 guide</a></li>
    <li><a href="/en/blog/faia-luxembourg-computerized-audit-file-guide">FAIA Luxembourg: complete guide to the audit file</a></li>
    <li><a href="/en/blog/mandatory-information-invoice-luxembourg">Mandatory information on a Luxembourg invoice</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Compliant numbering, without thinking about it</h3>
    <p class="text-primary-800 mb-4">faktur.lu manages your sequential numbering automatically, so article 63 LIVA is met by construction. Customisable format, annual reset included.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Start for free</a>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">Den <strong>Artikel 63 LIVA</strong> (Paragraf 8, Punkt 2°) stellt eng einfach mä fundamental Reegel op: Är Lëtzebuerger Rechnunge mussen eng <strong>eenzegaarteg, sequentiell a kontinuéierlech Nummer</strong> droen. E Lach an der Sequenz oder eng duebel Nummer kann eng Steiernofuerderung ausléisen. Hei ass d'Reegel erkläert – a wéi Dir ni méi drun denke musst.</p>

<h2>Wat seet den Artikel 63 LIVA genee?</h2>

<p>Den <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Artikel 63 vum Lëtzebuerger TVA-Gesetz</a> preziséiert a sengem Paragraf 8, Punkt 2°, datt all Rechnung, déi vun engem Steierpflichtegen erausgi gëtt, muss enthalen:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „Eng sequentiell Nummer, baséiert op enger oder méi Serien, déi d'Rechnung eendeiteg identifizéiert."
</blockquote>

<p>Kloer gesot, dräi kumulativ Reegelen:</p>

<ol>
    <li><strong>Eenzegaartegkeet</strong>: Zwou Rechnunge kënnen ni déiselwecht Nummer droen</li>
    <li><strong>Sequentialitéit</strong>: D'Nummere folgen openeen an der Rei (1, 2, 3, 4 …)</li>
    <li><strong>Kontinuitéit</strong>: kee Lach (kee Sprong vun 5 op 8 ouni 6 an 7)</li>
</ol>

<p>Dës Obligatioun gëllt fir <strong>all</strong> Steierpflichteg vun der Lëtzebuerger TVA, och an der Franchise (Artikel 57bis LIVA zanter dem 1. Januar 2025), och wann d'Rechnung d'Mentioun „TVA net applicabel" dréit.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Net ze verwiesselen</p>
    <p>Den Artikel <strong>61</strong> LIVA behandelt dogéint d'<strong>Persoun, déi d'TVA schëlleg ass</strong> bei gewëssen Operatiounen (bannenlännesch Autoliquidatioun zum Beispill). Mat der Rechnungsnummeréierung huet en näischt ze dinn, am Géigesaz zu deem, wat vill Quelle falsch berichten. Déi richteg Rechtsgrondlag ass den Artikel <strong>63</strong>.</p>
</div>

<h2>Firwat dës Reegel existéiert</h2>

<p>D'Zil ass einfach: <strong>d'Verstoppe vum Ëmsaz verhënneren</strong>. Ouni kontinuéierlech Nummeréierung kéint en Entreprise liicht gewësse Rechnungen a senger TVA-Deklaratioun „vergiessen". D'Sequentialitéit erlaabt et der AED, mat engem Bléck ze kontrolléieren, ob all erausgestallte Rechnung deklaréiert gouf.</p>

<p>Dofir enthält och de <strong>FAIA</strong> (den informatiséierte Prüffichier vun der AED) d'Lëscht vun de Rechnungsnummeren iwwer déi kontrolléiert Period. E Lach, dat am FAIA entdeckt gëtt, léist direkt eng Ufro no Erklärung aus.</p>

<h2>Déi akzeptéiert Formater</h2>

<p>Den Artikel 63 schreift kee bestëmmte Format vir. Dir kënnt all Konventioun benotzen, soulaang d'Sequentialitéit respektéiert gëtt:</p>

<ul>
    <li><strong>Einfach Nummeréierung</strong>: 1, 2, 3, 4 …</li>
    <li><strong>Mat Präfix</strong>: F-001, F-002, F-003 …</li>
    <li><strong>Mat Joer</strong>: 2026-001, 2026-002, 2026-003 …</li>
    <li><strong>Mat Präfix a Joer</strong>: F-2026-001, F-2026-002 …</li>
    <li><strong>No Client</strong> (net recommandéiert): ACME-001, ACME-002 (brécht déi global Kontinuitéit)</li>
</ul>

<p><strong>Tipp</strong>: Eng Nummer mat <strong>Joer a sequentiellem Compteur</strong> (z. B. F-2026-001) ass am liesbarsten an erlaabt et, all Joer bei 1 nei unzefänken. Dat ass d'Praxis, déi zu Lëtzebuerg am verbreetsten ass.</p>

<h2>Jäerleche Reset: erlaabt oder net?</h2>

<p>Jo, Dir kënnt d'Nummeréierung <strong>all Steierjoer bei 1</strong> nei ufänken. Dat ass souguer déi geleefegst Praxis. Wichteg ass, datt d'Sequenz <strong>bannent engem selwechte Joer</strong> kontinuéierlech bleift.</p>

<p>Gültegt Beispill:</p>

<ul>
    <li>F-2025-148 (lescht Rechnung vun 2025)</li>
    <li>F-2026-001 (éischt Rechnung vun 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>De Sprong vun 148 op 001 ass erlaabt, well d'Joer wiesselt. Bannent 2026 kënnt Dir dogéint net vu F-2026-001 op F-2026-003 sprangen, ouni F-2026-002 erausgestallt ze hunn.</p>

<h2>Wat geschitt bei engem Feeler?</h2>

<h3>Fall 1 – Dir hutt eng duebel Nummer erausgestallt</h3>

<p>D'AED geet dovun aus, datt eng vun deenen zwou Rechnungen <strong>fiktiv oder bedrügeresch</strong> ass. Dir musst:</p>

<ol>
    <li>En <strong>Avoir</strong> op eng vun deenen zwou Rechnungen erausstellen (comptabel Annulatioun)</li>
    <li>Eng <strong>nei Rechnung</strong> mat der nächster fräier Nummer erausstellen</li>
    <li>All dräi Dokumenter opbewaren (déi 2 ursprénglech Rechnungen an den Avoir)</li>
</ol>

<h3>Fall 2 – Dir hutt e Lach an der Sequenz (d'Nummer 5 feelt tëscht 4 a 6)</h3>

<p>E méi schwéiere Fall. Dir musst d'<strong>Lach erklären</strong> kënnen. Dräi akzeptabel Erklärungen:</p>

<ul>
    <li>D'Rechnung gouf erausgestallt an dann <strong>duerch en Avoir annuléiert</strong> (Dir behalt béid Dokumenter)</li>
    <li>Technesche Feeler: Dir hutt en net finaliséierten Entworf ugeluecht. An deem Fall ass et besser en ze finaliséieren an ze archivéieren, och wann d'Leeschtung net stattfonnt huet (mat der Mentioun „annuléiert Rechnung")</li>
    <li>Dokumentéierte Software-Bug (seelen)</li>
</ul>

<p>Ouni glafbar Erklärung ënnerstellt d'AED e Verstoppen a kann de feelenden Ëmsaz <strong>forfaitaresch schätzen</strong> (Taxatioun vun Amts wéinst). Méiglech administrativ Geldstrofen: 250 € bis 10 000 € pro Infraktioun (Artikel 77 LIVA).</p>

<h3>Fall 3 – Dir wëllt eng scho finaliséiert Rechnung läschen</h3>

<p>Rechtlech onméiglech. Eng finaliséiert Rechnung muss zéng Joer archivéiert bleiwen (<a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Artikel 16 vum Handelsgesetzbuch</a> an Artikel 65 LIVA). Fir ze annuléieren:</p>

<ul>
    <li>En <strong>Avoir</strong> iwwer dee selwechte Montant erausstellen (allgemeng mat enger eegener Serie: AV-2026-001)</li>
    <li>Béid Dokumenter opbewaren (Rechnung an Avoir)</li>
</ul>

<h2>Déi klassesch Fallen ze vermeiden</h2>

<ol>
    <li><strong>Manuell Nummeréierung an Excel</strong>: ganz héicht Risiko vun Duebelen oder Lächer (déi lescht Rechnung vergiess, kaputt Formel, verpatzte Copy-Paste)</li>
    <li><strong>Méi parallel Serien</strong> (eng pro Client oder Projet): schwéier ze rechtfäerdegen bei enger Kontroll</li>
    <li><strong>Nummeréierung mëttendran am Joer zrécksetzen</strong> ouni Wiessel vum Geschäftsjoer: verbueden</li>
    <li><strong>Finaliséiert Rechnunge läschen</strong> ouni Ersatz-Avoir: Verstouss géint den Artikel 16 vum Handelsgesetzbuch an den Artikel 63 LIVA</li>
</ol>

<h2>Wéi een dat automatiséiert</h2>

<p>Eng Fakturatiounssoftware wéi <a href="/lb" class="text-primary-500 hover:underline font-medium">faktur.lu</a> iwwerhëlt d'Nummeréierung fir Iech:</p>

<ul>
    <li><strong>Automatesch sequentiell Nummeréierung</strong>: eng duebel Nummer ass onméiglech</li>
    <li><strong>Kee Lach méiglech</strong>: bei all Finaliséierung geet de Compteur erop</li>
    <li><strong>Personaliséierbare Format</strong>: Präfix, Joer, Längt vum Compteur – alles konfiguréierbar</li>
    <li><strong>Automatesche jäerleche Reset</strong>: de Compteur fänkt all Kalennerjoer nees bei 1 un, op Basis vum Rechnungsdatum</li>
    <li><strong>Getrennte Serien</strong> fir Rechnungen, Avoiren an Devisen (onofhängeg Compteuren, jidderee fir sech kontinuéierlech)</li>
    <li><strong>Eenzegaartegkeetskontroll</strong> bei all Erstellung</li>
</ul>

<p>Domat ass den Artikel 63 LIVA kee Suergen méi: Är Nummeréierung ass konstruktiounsbedéngt konform.</p>

<h2>FAQ – Artikel 63 LIVA an Nummeréierung</h2>

<h3>A wann ech an der TVA-Franchise fakturéieren (Artikel 57bis LIVA)?</h3>
<p>Den Artikel 63 LIVA gëllt <strong>trotzdem</strong>. All erausgestallte Rechnung, och ouni agesammelt TVA, muss eng sequentiell a kontinuéierlech Nummer droen.</p>

<h3>Kann ech pro Client nummeréieren (ACME-001, ACME-002 …)?</h3>
<p>Technesch jo – mä et ass ganz staark ofzeroden. Bei enger Kontroll freet d'AED d'<strong>global</strong> Sequentialitéit. Dir misst dann iwwer all Client-Serien hinweg beweisen, datt et kee Lach gëtt, wat komplex a risqué ass.</p>

<h3>Sinn d'Devise betraff?</h3>
<p>Nee, den Artikel 63 LIVA betrëfft nëmmen <strong>Rechnungen an Avoiren</strong>. Dir kënnt Är Devisen nummeréieren, wéi Dir wëllt (eng sequentiell Nummeréierung bleift awer fir Är eege Suivi ze recommandéieren).</p>

<h3>Kann ech getrennte Serie fir Rechnungen an Avoiren hunn?</h3>
<p>Jo, dat ass souguer recommandéiert. Eng Serie fir d'Rechnungen (F-2026-001, F-2026-002 …) an eng Serie fir d'Avoiren (AV-2026-001, AV-2026-002 …). Jidderee muss kontinuéierlech sinn, mä si sinn onofhängeg vunenee.</p>

<h3>Wat maachen, wann ech mëttendran am Joer d'Software wiesselen?</h3>
<p>Dir musst d'<strong>Sequenz weiderféieren</strong>. Waart Dir am ale System bei F-2026-148, muss Är éischt Rechnung am neie F-2026-149 sinn. Bei faktur.lu kann de Startcompteur bei der Ariichtung gesat ginn, fir datt kee Lach entsteet.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze kontrolléieren</p>
    <p>Artikelreferenzen a Fakturatiounsobligatioune kënne sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Ären Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikel 63</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Pflichtmentiounen op de Rechnungen</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Lëtzebuerger Handelsgesetzbuch – Artikel 16 (Opbewahrung 10 Joer)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert. Virdru ënner dem Titel „Artikel 61 LIVA" publizéiert – déi richteg Referenz ass den Artikel 63 LIVA (Paragraf 8, Punkt 2°).</em></p>

<h2>Fir méi wäit ze goen</h2>
<ul>
    <li><a href="/lb/blog/steierprefung-letzebuerg-virbereden">Steierkontroll vun der AED zu Lëtzebuerg: Guide 2026</a></li>
    <li><a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide">FAIA Lëtzebuerg: komplette Guide zum Prüffichier</a></li>
    <li><a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg">Pflichtmentiounen op enger Rechnung zu Lëtzebuerg</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Eng konform Nummeréierung, ouni drun ze denken</h3>
    <p class="text-primary-800 mb-4">faktur.lu geréiert Är sequentiell Nummeréierung automatesch, den Artikel 63 LIVA ass konstruktiounsbedéngt respektéiert. Personaliséierbare Format, jäerleche Reset abegraff.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis ufänken</a>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">O <strong>artigo 63 LIVA</strong> (n.º 8, ponto 2.º) impõe uma regra simples mas fundamental: as suas faturas luxemburguesas têm de ter um <strong>número único, sequencial e contínuo</strong>. Uma falha na sequência ou um número duplicado pode desencadear uma correção fiscal. Eis a regra explicada – e como deixar de pensar nela.</p>

<h2>O que diz exatamente o artigo 63 LIVA?</h2>

<p>O <a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">artigo 63 da lei luxemburguesa do IVA</a> precisa, no seu n.º 8, ponto 2.º, que toda a fatura emitida por um sujeito passivo deve conter:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    «Um número sequencial, baseado numa ou em várias séries, que identifique a fatura de forma única.»
</blockquote>

<p>Em termos claros, três regras cumulativas:</p>

<ol>
    <li><strong>Unicidade</strong>: duas faturas nunca podem ter o mesmo número</li>
    <li><strong>Sequencialidade</strong>: os números sucedem-se por ordem (1, 2, 3, 4…)</li>
    <li><strong>Continuidade</strong>: sem falhas (nada de passar de 5 para 8 sem o 6 e o 7)</li>
</ol>

<p>Esta obrigação aplica-se a <strong>todos os sujeitos passivos</strong> do IVA luxemburguês, incluindo em regime de isenção (artigo 57bis LIVA desde 1 de janeiro de 2025), mesmo que a fatura tenha a menção «IVA não aplicável».</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A não confundir</p>
    <p>O artigo <strong>61</strong> LIVA, esse, trata da <strong>pessoa devedora do IVA</strong> em certas operações (autoliquidação interna, por exemplo). Nada tem que ver com a numeração das faturas, ao contrário do que muitas fontes referem por erro. A base legal correta é mesmo o artigo <strong>63</strong>.</p>
</div>

<h2>Porque existe esta regra</h2>

<p>O objetivo é simples: <strong>impedir a dissimulação de volume de negócios</strong>. Sem numeração contínua, uma empresa poderia facilmente «esquecer» certas faturas na sua declaração de IVA. A sequencialidade permite à AED verificar de relance se todas as faturas emitidas foram declaradas.</p>

<p>É também por isso que o <strong>FAIA</strong> (o ficheiro de auditoria informatizado da AED) inclui a lista dos números de fatura do período controlado. Uma falha detetada no FAIA desencadeia imediatamente um pedido de explicação.</p>

<h2>Os formatos aceites</h2>

<p>O artigo 63 não impõe um formato único. Pode usar qualquer convenção, desde que a sequencialidade seja respeitada:</p>

<ul>
    <li><strong>Numeração simples</strong>: 1, 2, 3, 4…</li>
    <li><strong>Com prefixo</strong>: F-001, F-002, F-003…</li>
    <li><strong>Com o ano</strong>: 2026-001, 2026-002, 2026-003…</li>
    <li><strong>Com prefixo e ano</strong>: F-2026-001, F-2026-002…</li>
    <li><strong>Por cliente</strong> (desaconselhado): ACME-001, ACME-002 (quebra a continuidade global)</li>
</ul>

<p><strong>Dica</strong>: um número com <strong>ano e contador sequencial</strong> (ex.: F-2026-001) é o mais legível e permite recomeçar em 1 todos os anos. É a prática mais comum no Luxemburgo.</p>

<h2>Reinício anual: permitido ou não?</h2>

<p>Sim, pode recomeçar a numeração em <strong>1 em cada ano fiscal</strong>. É mesmo a prática mais corrente. O que importa é que, <strong>dentro do mesmo ano</strong>, a sequência seja contínua.</p>

<p>Exemplo válido:</p>

<ul>
    <li>F-2025-148 (última fatura de 2025)</li>
    <li>F-2026-001 (primeira fatura de 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>A passagem de 148 para 001 é permitida porque se muda de ano. Mas dentro de 2026 não pode passar de F-2026-001 para F-2026-003 sem ter emitido a F-2026-002.</p>

<h2>O que acontece em caso de erro?</h2>

<h3>Caso 1 – Emitiu um duplicado (duas faturas com o mesmo número)</h3>

<p>A AED considera que uma das duas faturas é <strong>fictícia ou fraudulenta</strong>. Tem de:</p>

<ol>
    <li>Emitir uma <strong>nota de crédito</strong> sobre uma das duas faturas (anulação contabilística)</li>
    <li>Emitir uma <strong>nova fatura</strong> com o número seguinte disponível</li>
    <li>Conservar os 3 documentos (as 2 faturas iniciais e a nota de crédito)</li>
</ol>

<h3>Caso 2 – Tem uma falha na sequência (falta o número 5 entre o 4 e o 6)</h3>

<p>Caso mais grave. Tem de conseguir <strong>explicar a falha</strong> à AED. Três explicações admissíveis:</p>

<ul>
    <li>A fatura foi emitida e depois <strong>anulada por nota de crédito</strong> (guarda ambos os documentos)</li>
    <li>Erro técnico: criou um rascunho não finalizado. Nesse caso, mais vale finalizá-lo e arquivá-lo, mesmo que a prestação não tenha ocorrido (indicando «fatura anulada»)</li>
    <li>Erro informático documentado (raro)</li>
</ul>

<p>Sem explicação credível, a AED presume dissimulação e pode <strong>estimar por métodos indiretos</strong> o volume de negócios em falta (tributação oficiosa). Coimas administrativas possíveis: 250 € a 10 000 € por infração (artigo 77 LIVA).</p>

<h3>Caso 3 – Quer eliminar uma fatura já finalizada</h3>

<p>Legalmente impossível. Uma fatura finalizada tem de permanecer arquivada dez anos (<a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">artigo 16 do Código Comercial</a> e artigo 65 LIVA). Para anular:</p>

<ul>
    <li>Emitir uma <strong>nota de crédito</strong> do mesmo montante (geralmente com numeração separada: AV-2026-001)</li>
    <li>Conservar os 2 documentos (fatura e nota de crédito)</li>
</ul>

<h2>As armadilhas clássicas a evitar</h2>

<ol>
    <li><strong>Numeração manual em Excel</strong>: risco muito elevado de duplicados ou falhas (esquecimento da última fatura, fórmula partida, cópia mal feita)</li>
    <li><strong>Várias séries em paralelo</strong> (uma por cliente ou por projeto): difícil de justificar numa inspeção</li>
    <li><strong>Reinício da numeração a meio do ano</strong> sem mudança de exercício fiscal: proibido</li>
    <li><strong>Eliminação de faturas finalizadas</strong> sem nota de crédito de substituição: violação do artigo 16 do Código Comercial e do artigo 63 LIVA</li>
</ol>

<h2>Como automatizar sem pensar nisso</h2>

<p>Um software de faturação como o <a href="/pt" class="text-primary-500 hover:underline font-medium">faktur.lu</a> trata da numeração por si:</p>

<ul>
    <li><strong>Numeração sequencial automática</strong>: é impossível criar um duplicado</li>
    <li><strong>Nenhuma falha possível</strong>: a cada finalização, o contador incrementa</li>
    <li><strong>Formato personalizável</strong>: prefixo, ano, comprimento do contador – tudo configurável</li>
    <li><strong>Reinício anual automático</strong>: o contador volta a 1 em cada ano civil, com base na data da fatura</li>
    <li><strong>Séries separadas</strong> para faturas, notas de crédito e orçamentos (contadores independentes, cada um contínuo)</li>
    <li><strong>Verificação de unicidade</strong> em cada criação</li>
</ul>

<p>Com isto, o artigo 63 LIVA deixa de ser uma preocupação: a sua numeração é conforme por construção.</p>

<h2>FAQ – Artigo 63 LIVA e numeração</h2>

<h3>E se eu faturar em isenção de IVA (artigo 57bis LIVA)?</h3>
<p>O artigo 63 LIVA aplica-se <strong>na mesma</strong>. Toda a fatura emitida, mesmo sem IVA liquidado, tem de ter um número sequencial e contínuo.</p>

<h3>Posso numerar por cliente (ACME-001, ACME-002…)?</h3>
<p>Tecnicamente, sim – mas é fortemente desaconselhado. Numa inspeção, a AED exige a sequencialidade <strong>global</strong>. Teria então de provar que não há falhas somando todas as séries de clientes, o que é complexo e arriscado.</p>

<h3>Os orçamentos estão abrangidos?</h3>
<p>Não, o artigo 63 LIVA abrange apenas <strong>faturas e notas de crédito</strong>. Pode numerar os seus orçamentos como quiser (embora uma numeração sequencial continue a ser recomendável para o seu próprio acompanhamento).</p>

<h3>Posso ter séries separadas para faturas e notas de crédito?</h3>
<p>Sim, é mesmo recomendável. Uma série para as faturas (F-2026-001, F-2026-002…) e uma série para as notas de crédito (AV-2026-001, AV-2026-002…). Cada uma tem de ser contínua, mas são independentes entre si.</p>

<h3>O que fazer se mudar de software a meio do ano?</h3>
<p>Tem de <strong>continuar a sequência</strong> no novo software. Se estava em F-2026-148 no antigo, a sua primeira fatura no novo tem de ser F-2026-149. O faktur.lu permite configurar o contador inicial na instalação para evitar a falha.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As referências de artigos e as obrigações de faturação podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu <em>fiduciaire</em> ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) – artigo 63</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/en-cours-activite-economique/que-doivent-contenir-factures.html" target="_blank" rel="noopener">AED – Menções obrigatórias nas faturas</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1931/05/22/n1/jo" target="_blank" rel="noopener">Código Comercial luxemburguês – artigo 16 (conservação 10 anos)</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026. Anteriormente publicado com o título «Artigo 61 LIVA» – a referência correta é o artigo 63 LIVA (n.º 8, ponto 2.º).</em></p>

<h2>Para saber mais</h2>
<ul>
    <li><a href="/pt/blog/controlo-fiscal-no-luxemburgo-como-se-preparar">Inspeção fiscal da AED no Luxemburgo: guia 2026</a></li>
    <li><a href="/pt/blog/faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado">FAIA Luxemburgo: guia completo do ficheiro de auditoria</a></li>
    <li><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa">Menções obrigatórias numa fatura no Luxemburgo</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Uma numeração conforme, sem pensar nisso</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gere automaticamente a sua numeração sequencial, cumprindo o artigo 63 LIVA por construção. Formato personalizável, reinício anual incluído.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Começar gratuitamente</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
