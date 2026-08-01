<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guide « Créer une entreprise individuelle en Allemagne ».
 *
 * Vérifié le 2026-08-01 contre le texte du § 19 UStG sur
 * gesetze-im-internet.de : les seuils de 25 000 EUR (année précédente) et
 * 100 000 EUR (année en cours) sont exacts.
 *
 * CORRECTION. Le tableau alignait les deux seuils sans dire ce que chacun
 * déclenche, alors que la loi les traite très différemment. Le texte dit :
 * « steuerfrei, wenn der Gesamtumsatz im vorangegangenen Kalenderjahr 25 000
 * Euro nicht überschritten hat und im laufenden Kalenderjahr 100 000 Euro
 * nicht überschreitet ». Le premier verbe est au passé composé, le second au
 * PRÉSENT : le seuil de 25 000 EUR se constate a posteriori, celui de
 * 100 000 EUR est un plafond dur qui met fin à l'exonération dès l'opération
 * qui le franchit, en cours d'année.
 *
 * C'est le piège pratique du régime, et l'article n'en disait rien.
 *
 * Non modifié faute d'élément contraire : Freibetrag Gewerbesteuer de
 * 24 500 EUR, taux de TVA 19 % et 7 %, coûts de Gewerbeanmeldung. Les
 * montants IHK (5 200 EUR, 15 340 EUR, exonération Existenzgründer) varient
 * par chambre, ce que l'encart signale déjà.
 *
 * DE, EN, LB, PT : 11 135 à 11 651 caractères contre 13 528, cinq à six liens
 * contre huit.
 */
return new class extends Migration
{
    private const KEY = 'creer-entreprise-individuelle-allemagne-guide-2025';

    private const FR_FIXES = [
        [
            '        <tr><td class="p-2 border-b">CA année précédente (N-1)</td><td class="p-2 border-b">≤ 25 000 €</td></tr>'."\n"
                .'        <tr><td class="p-2 border-b">CA année en cours (N)</td><td class="p-2 border-b">≤ 100 000 €</td></tr>'."\n"
                .'    </tbody>'."\n"
                .'</table>',
            '        <tr><td class="p-2 border-b">CA année précédente (N-1)</td><td class="p-2 border-b">≤ 25 000 €</td></tr>'."\n"
                .'        <tr><td class="p-2 border-b">CA année en cours (N)</td><td class="p-2 border-b">≤ 100 000 €</td></tr>'."\n"
                .'    </tbody>'."\n"
                .'</table>'."\n\n"
                .'<p>Les deux seuils ne jouent pas de la même façon, et le § 19 UStG le dit dans sa grammaire même : le premier est au passé, le second au présent.</p>'."\n\n"
                .'<ul>'."\n"
                .'    <li><strong>25 000 € (année précédente)</strong> : condition constatée a posteriori. La dépasser vous prive du régime <strong>pour l\'année suivante</strong>.</li>'."\n"
                .'    <li><strong>100 000 € (année en cours)</strong> : plafond dur. Le franchir met fin à l\'exonération <strong>immédiatement, dès l\'opération qui le dépasse</strong> - vous facturez la TVA à partir de là, sans attendre le 1<sup>er</sup> janvier.</li>'."\n"
                .'</ul>',
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

    /**
     * @param  array<int, string>  $heads
     * @param  array<int, array<int, string>>  $rows
     */
    private function table(array $heads, array $rows, string $extra = ''): string
    {
        $th = '';
        foreach ($heads as $h) {
            $th .= '            <th class="text-left p-2 bg-slate-100">'.$h."</th>\n";
        }

        $tb = '';
        foreach ($rows as $cells) {
            $tb .= '        <tr>';
            foreach ($cells as $c) {
                $cls = 'p-2 border-b';
                if (str_starts_with($c, '~green~')) {
                    $cls .= ' text-green-600';
                    $c = substr($c, 7);
                } elseif (str_starts_with($c, '~red~')) {
                    $cls .= ' text-red-600 font-semibold';
                    $c = substr($c, 5);
                }
                $tb .= '<td class="'.$cls.'">'.$c.'</td>';
            }
            $tb .= "</tr>\n";
        }

        return '<table class="w-full my-4'.$extra."\">\n    <thead>\n        <tr>\n{$th}        </tr>\n    </thead>\n    <tbody>\n{$tb}    </tbody>\n</table>";
    }

    /** @return array<string, string> */
    private function translations(): array
    {
        $de = implode("\n\n", [
            '<p class="lead">Deutschland bietet mehrere Möglichkeiten zur Gründung eines Einzelunternehmens mit relativ einfachen und schnellen Schritten. Dieser Leitfaden stellt die Rechtsformen und die Etappen für den Start 2026 vor.</p>',

            '<h2>Die Rechtsformen für ein Einzelunternehmen</h2>',
            '<h3>Einzelunternehmen</h3>',
            $this->table(['Merkmal', 'Detail'], [
                ['Definition', 'Von einer einzigen Person geführtes Unternehmen'],
                ['Mindestkapital', 'Keines erforderlich'],
                ['Haftung', '<strong>Unbeschränkt</strong>'],
                ['Gründung', 'Gewerbeanmeldung und Steuernummer'],
                ['Besteuerung', 'Einkommensteuer und Gewerbesteuer (Freibetrag 24 500 €)'],
            ]),
            '<p><strong>Unterkategorien:</strong></p>',
            "<ul>\n    <li><strong>Kleingewerbetreibender:</strong> kein Eintrag ins Handelsregister</li>\n    <li><strong>Eingetragener Kaufmann (e.K.):</strong> im Handelsregister eingetragen</li>\n</ul>",

            '<h3>Freiberufler</h3>',
            $this->table(['Merkmal', 'Detail'], [
                ['Definition', 'Geistige, kreative, wissenschaftliche oder erzieherische Tätigkeit'],
                ['Gewerbeanmeldung', '~green~NICHT erforderlich'],
                ['Gewerbesteuer', '~green~NICHT anwendbar'],
                ['IHK/HWK', '~green~Kein Pflichtbeitrag'],
                ['Anmeldung', 'Direkt beim Finanzamt'],
            ]),
            '<p><strong>Betroffene Berufe (Katalogberufe):</strong> Ärzte, Rechtsanwälte, Architekten, Ingenieure, Journalisten, Übersetzer, Künstler, Lehrer …</p>',

            '<h2>Voraussetzungen</h2>',
            '<h3>Für Gewerbetreibende</h3>',
            "<ul>\n    <li><strong>Mindestalter:</strong> 18 Jahre (Volljährigkeit)</li>\n    <li><strong>Wohnsitz:</strong> Anschrift in Deutschland</li>\n    <li><strong>Dokumente:</strong> Reisepass oder Personalausweis</li>\n    <li><strong>Zulässige Tätigkeit:</strong> gesetzlich erlaubte Tätigkeit</li>\n</ul>",
            '<h3>Mögliche zusätzliche Unterlagen</h3>',
            "<ul>\n    <li><strong>Führungszeugnis</strong> (Auszug aus dem Strafregister): rund 13 €</li>\n    <li><strong>Gewerbezentralregisterauszug:</strong> rund 13 €</li>\n    <li><strong>Handwerkskarte:</strong> 80-250 €</li>\n</ul>",

            '<h2>Gründungsschritte</h2>',
            '<h3>Weg A: Gewerbetreibender</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Schritt 1: Gewerbeanmeldung (Gewerbeamt)<br>\n        ↓<br>\n"
                ."        Schritt 2: Automatische Meldungen (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>\n        ↓<br>\n"
                ."        Schritt 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>\n        ↓<br>\n"
                ."        Schritt 4: Zuteilung der Steuernummer<br>\n        ↓<br>\n"
                ."        Schritt 5: Anmeldung bei der Berufsgenossenschaft (7 Tage)\n"
                .'    </p>'."\n"
                .'</div>',
            '<h4>Gewerbeanmeldung</h4>',
            "<ul>\n    <li><strong>Wo:</strong> Gewerbeamt der Sitzgemeinde</li>\n    <li><strong>Formular:</strong> GewA 1</li>\n    <li><strong>Art:</strong> online (Gewerbe-Service-Portal) oder vor Ort</li>\n    <li><strong>Dauer:</strong> 1-3 Tage</li>\n</ul>",

            '<h3>Weg B: Freiberufler</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Schritt 1: Anmeldung beim Finanzamt (innerhalb von 4 Wochen nach Beginn)<br>\n        ↓<br>\n"
                ."        Schritt 2: Fragebogen zur steuerlichen Erfassung<br>\n        ↓<br>\n"
                ."        Schritt 3: Zuteilung der Steuernummer\n"
                .'    </p>'."\n"
                .'</div>',

            '<h2>Gründungskosten</h2>',
            $this->table(['Posten', 'Betrag'], [
                ['Gewerbeanmeldung (je nach Gemeinde)', '15 - 65 €'],
                ['Großstädte (München, Stuttgart, Hamburg)', '50 - 65 €'],
                ['Kleinere Gemeinden', '15 - 30 €'],
                ['Freiberufler', '~green~<strong>0 € (kostenlos)</strong>'],
            ]),

            '<h2>Übliche Fristen</h2>',
            $this->table(['Schritt', 'Dauer'], [
                ['Bearbeitung der Gewerbeanmeldung', '<strong>1-3 Tage</strong>'],
                ['Schriftliche Bestätigung des Gewerbeamts', 'Höchstens 3 Tage'],
                ['Erhalt des Fragebogens vom Finanzamt', '4-6 Wochen'],
                ['Zuteilung der Steuernummer', '2-4 Wochen'],
                ['<strong>Gesamtdauer</strong>', '<strong>6-10 Wochen</strong>'],
            ]),

            '<h2>Pflichten nach der Gründung</h2>',
            '<h3>Umsatzsteuer</h3>',
            '<h4>Regelbesteuerung</h4>',
            "<ul>\n    <li><strong>Regelsatz:</strong> 19 %</li>\n    <li><strong>Ermäßigter Satz:</strong> 7 %</li>\n    <li>Monatliche oder vierteljährliche Umsatzsteuer-Voranmeldung</li>\n</ul>",
            '<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>',
            $this->table(['Kriterium', 'Schwelle 2026'], [
                ['Gesamtumsatz Vorjahr (N-1)', '≤ 25 000 €'],
                ['Gesamtumsatz laufendes Jahr (N)', '≤ 100 000 €'],
            ]),
            '<p>Die beiden Schwellen wirken unterschiedlich, und § 19 UStG sagt das schon in seiner Grammatik: die erste steht im Perfekt, die zweite im Präsens.</p>',
            "<ul>\n    <li><strong>25 000 € (Vorjahr)</strong>: rückblickend festgestellte Bedingung. Wird sie überschritten, entfällt die Regelung <strong>für das Folgejahr</strong>.</li>\n    <li><strong>100 000 € (laufendes Jahr)</strong>: harte Obergrenze. Ihre Überschreitung beendet die Steuerbefreiung <strong>sofort, ab dem Umsatz, der sie überschreitet</strong> – Sie weisen ab da Umsatzsteuer aus, ohne den 1. Januar abzuwarten.</li>\n</ul>",
            '<p><strong>Vorteile:</strong></p>',
            "<ul>\n    <li>Keine Umsatzsteuer in Rechnung zu stellen</li>\n    <li>Keine Umsatzsteuererklärungen</li>\n    <li>Vereinfachte Buchführung</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtangabe auf Rechnungen</p>'."\n"
                .'    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »</p>'."\n"
                .'</div>',

            '<h3>Gewerbesteuer</h3>',
            $this->table(['Situation', 'Pflicht'], [
                ['Freiberufler', '~green~Befreit'],
                ['Gewerbetreibender (natürliche Person) mit Gewerbeertrag &lt; 24 500 €/Jahr', '~green~Befreit (Freibetrag)'],
                ['Gewerbetreibender ≥ 24 500 €/Jahr', 'Steuerpflichtig auf den übersteigenden Teil'],
            ]),

            '<h3>Sozialversicherung</h3>',
            $this->table(['Art', 'Pflicht'], [
                ['Krankenversicherung', '~red~PFLICHT'],
                ['Pflegeversicherung', '~red~PFLICHT'],
                ['Rentenversicherung', 'Freiwillig*'],
                ['Arbeitslosenversicherung', 'Freiwillig'],
            ]),
            '<p><small>*Pflicht für bestimmte Berufe (Handwerker, Lehrer, Pflegekräfte, Hebammen usw.)</small></p>',

            '<h3>IHK-/HWK-Beitrag</h3>',
            '<p>Für Gewerbetreibende automatische und verpflichtende Mitgliedschaft. Der Gesamtbetrag besteht aus zwei Teilen:</p>',
            "<ul>\n    <li><strong>Grundbeitrag</strong>: Befreiung, wenn der jährliche Gewerbeertrag unter <strong>5 200 €</strong> liegt</li>\n    <li><strong>Umlage</strong> (gewinnabhängiger Anteil): für nicht im Handelsregister eingetragene natürliche Personen gilt ein <strong>Freibetrag von 15 340 €</strong> auf die Bemessungsgrundlage</li>\n</ul>",

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚡ Vorteil für Existenzgründer</p>'."\n"
                .'    <p><strong>Existenzgründer</strong> (natürliche Personen, erstes Gewerbe innerhalb der letzten fünf Jahre) sind <strong>zwei Jahre lang vollständig</strong> von Grundbeitrag und Umlage befreit, sofern ihr jährlicher Gewerbeertrag unter 25 000 € bleibt. <strong>Fragen Sie bei Ihrer örtlichen IHK nach</strong>, die genauen Bedingungen unterscheiden sich leicht je nach Kammer.</p>'."\n"
                .'</div>',

            '<h2>Vergleichstabelle</h2>',
            $this->table(['Kriterium', 'Einzelunternehmen', 'Freiberufler'], [
                ['Gewerbeanmeldung', 'Ja', '~green~Nein'],
                ['Gewerbesteuer', 'Ja (über 24 500 €)', '~green~Nein'],
                ['IHK-Mitgliedschaft', 'Pflicht', '~green~Nein'],
                ['Gründungskosten', '15-65 €', '~green~0 €'],
                ['Gründungsdauer', '1-3 Tage', '~green~Sofort'],
            ], ' text-sm'),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Jährlich zu prüfen</p>'."\n"
                .'    <p>Die deutschen Schwellen, Sätze und Steuerregeln ändern sich. Diese Seite wird regelmäßig aktualisiert; für Ihre persönliche Situation wenden Sie sich an Ihren Steuerberater oder an die offiziellen Portale.</p>'."\n"
                .'</div>',

            '<h2>Offizielle Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://www.existenzgruendungsportal.de/\" target=\"_blank\" rel=\"noopener\">Existenzgründungsportal (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.bmwk.de/\" target=\"_blank\" rel=\"noopener\">Bundesministerium für Wirtschaft (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.ihk.de/\" target=\"_blank\" rel=\"noopener\">IHK – Industrie- und Handelskammer</a></li>\n"
                ."    <li><a href=\"https://www.deutsche-rentenversicherung.de/\" target=\"_blank\" rel=\"noopener\">Deutsche Rentenversicherung</a></li>\n"
                ."    <li><a href=\"https://gruenderplattform.de/\" target=\"_blank\" rel=\"noopener\">Gründerplattform</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kurz gefasst</p>'."\n"
                .'    <p>Ein Einzelunternehmen in Deutschland zu gründen kostet je nach Status zwischen 0 und 65 €. Die Gewerbeanmeldung ist in 1-3 Tagen bearbeitet. Die Kleinunternehmerregelung befreit von der Umsatzsteuer (Vorjahr ≤ 25 000 €, laufendes Jahr ≤ 100 000 €), wobei die zweite Schwelle sofort greift. Freiberufler zahlen weder Gewerbesteuer noch IHK-Beitrag. Existenzgründer können unter Bedingungen zwei Jahre IHK-Befreiung erhalten.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/einzelunternehmen-luxemburg-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Luxemburg gründen: Leitfaden 2026 →</a></li><li><a href="/de/blog/freiberufler-luxemburg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freiberufler in Luxemburg: rechtssicher fakturieren →</a></li><li><a href="/de/blog/mwst-luxemburg-saetze-berechnung-pflichten" class="text-primary-500 hover:text-primary-600 text-sm">MwSt Luxemburg 2026: die vier Sätze erklärt →</a></li></ul></div>',
        ]);

        $en = implode("\n\n", [
            '<p class="lead">Germany offers several options for creating a sole proprietorship, with relatively simple and fast procedures. This guide sets out the legal forms and the steps to get started in 2026.</p>',

            '<h2>Legal forms for a sole proprietorship</h2>',
            '<h3>Einzelunternehmen (sole proprietorship)</h3>',
            $this->table(['Characteristic', 'Detail'], [
                ['Definition', 'A business run by a single person'],
                ['Minimum capital', 'None required'],
                ['Liability', '<strong>Unlimited</strong>'],
                ['Set-up', 'Gewerbeanmeldung plus tax number'],
                ['Taxation', 'Income tax plus Gewerbesteuer (allowance EUR 24,500)'],
            ]),
            '<p><strong>Sub-categories:</strong></p>',
            "<ul>\n    <li><strong>Kleingewerbetreibender:</strong> small trader, no entry in the commercial register</li>\n    <li><strong>Eingetragener Kaufmann (e.K.):</strong> entered in the commercial register</li>\n</ul>",

            '<h3>Freiberufler (liberal profession)</h3>',
            $this->table(['Characteristic', 'Detail'], [
                ['Definition', 'Intellectual, creative, scientific or educational activity'],
                ['Gewerbeanmeldung', '~green~NOT required'],
                ['Gewerbesteuer', '~green~NOT applicable'],
                ['IHK/HWK', '~green~No compulsory membership fee'],
                ['Registration', 'Directly with the Finanzamt'],
            ]),
            '<p><strong>Professions concerned (Katalogberufe):</strong> doctors, lawyers, architects, engineers, journalists, translators, artists, teachers…</p>',

            '<h2>Conditions and prerequisites</h2>',
            '<h3>For Gewerbetreibende</h3>',
            "<ul>\n    <li><strong>Minimum age:</strong> 18 (majority)</li>\n    <li><strong>Residence:</strong> an address in Germany</li>\n    <li><strong>Documents:</strong> passport or identity card</li>\n    <li><strong>Lawful activity:</strong> an activity permitted by law</li>\n</ul>",
            '<h3>Possible additional documents</h3>',
            "<ul>\n    <li><strong>Führungszeugnis</strong> (criminal record extract): around EUR 13</li>\n    <li><strong>Gewerbezentralregisterauszug:</strong> around EUR 13</li>\n    <li><strong>Craft card:</strong> EUR 80-250</li>\n</ul>",

            '<h2>Set-up steps</h2>',
            '<h3>Route A: Gewerbetreibender</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Step 1: Gewerbeanmeldung (Gewerbeamt)<br>\n        ↓<br>\n"
                ."        Step 2: Automatic notifications (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>\n        ↓<br>\n"
                ."        Step 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>\n        ↓<br>\n"
                ."        Step 4: Steuernummer issued<br>\n        ↓<br>\n"
                ."        Step 5: Registration with the Berufsgenossenschaft (7 days)\n"
                .'    </p>'."\n"
                .'</div>',
            '<h4>Gewerbeanmeldung</h4>',
            "<ul>\n    <li><strong>Where:</strong> the Gewerbeamt of the municipality where you are based</li>\n    <li><strong>Form:</strong> GewA 1</li>\n    <li><strong>How:</strong> online (Gewerbe-Service-Portal) or in person</li>\n    <li><strong>Time:</strong> 1-3 days</li>\n</ul>",

            '<h3>Route B: Freiberufler</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Step 1: Register with the Finanzamt (within 4 weeks of starting)<br>\n        ↓<br>\n"
                ."        Step 2: Fragebogen zur steuerlichen Erfassung<br>\n        ↓<br>\n"
                ."        Step 3: Steuernummer issued\n"
                .'    </p>'."\n"
                .'</div>',

            '<h2>Set-up costs</h2>',
            $this->table(['Item', 'Amount'], [
                ['Gewerbeanmeldung (varies by municipality)', 'EUR 15 - 65'],
                ['Large cities (Munich, Stuttgart, Hamburg)', 'EUR 50 - 65'],
                ['Smaller municipalities', 'EUR 15 - 30'],
                ['Freiberufler', '~green~<strong>EUR 0 (free)</strong>'],
            ]),

            '<h2>Typical timescales</h2>',
            $this->table(['Step', 'Time'], [
                ['Processing the Gewerbeanmeldung', '<strong>1-3 days</strong>'],
                ['Written confirmation from the Gewerbeamt', '3 days at most'],
                ['Receiving the Finanzamt questionnaire', '4-6 weeks'],
                ['Steuernummer issued', '2-4 weeks'],
                ['<strong>Total time</strong>', '<strong>6-10 weeks</strong>'],
            ]),

            '<h2>Obligations once you are set up</h2>',
            '<h3>VAT / Umsatzsteuer</h3>',
            '<h4>Normal regime</h4>',
            "<ul>\n    <li><strong>Standard rate:</strong> 19%</li>\n    <li><strong>Reduced rate:</strong> 7%</li>\n    <li>Monthly or quarterly Umsatzsteuer-Voranmeldung</li>\n</ul>",
            '<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>',
            $this->table(['Criterion', '2026 threshold'], [
                ['Turnover previous year (N-1)', '≤ EUR 25,000'],
                ['Turnover current year (N)', '≤ EUR 100,000'],
            ]),
            '<p>The two thresholds do not work the same way, and § 19 UStG says so in its very grammar: the first is in the past tense, the second in the present.</p>',
            "<ul>\n    <li><strong>EUR 25,000 (previous year)</strong>: a condition assessed after the fact. Exceeding it costs you the scheme <strong>for the following year</strong>.</li>\n    <li><strong>EUR 100,000 (current year)</strong>: a hard ceiling. Crossing it ends the exemption <strong>immediately, from the transaction that exceeds it</strong> — you charge VAT from then on, without waiting for 1 January.</li>\n</ul>",
            '<p><strong>Advantages:</strong></p>',
            "<ul>\n    <li>No VAT to charge</li>\n    <li>No VAT returns</li>\n    <li>Simplified bookkeeping</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Mandatory mention on invoices</p>'."\n"
                .'    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »<br>'."\n"
                .'    <em>(No VAT shown, small-business scheme under § 19 UStG)</em></p>'."\n"
                .'</div>',

            '<h3>Gewerbesteuer (trade tax)</h3>',
            $this->table(['Situation', 'Obligation'], [
                ['Freiberufler', '~green~Exempt'],
                ['Gewerbetreibender (natural person) with Gewerbeertrag &lt; EUR 24,500/year', '~green~Exempt (allowance)'],
                ['Gewerbetreibender ≥ EUR 24,500/year', 'Liable on the excess'],
            ]),

            '<h3>Social contributions</h3>',
            $this->table(['Type', 'Obligation'], [
                ['Krankenversicherung (health)', '~red~MANDATORY'],
                ['Pflegeversicherung (long-term care)', '~red~MANDATORY'],
                ['Rentenversicherung (pension)', 'Optional*'],
                ['Arbeitslosenversicherung (unemployment)', 'Optional'],
            ]),
            '<p><small>*Mandatory for certain professions (craftspeople, teachers, carers, midwives and others)</small></p>',

            '<h3>IHK/HWK membership fee</h3>',
            '<p>Membership is automatic and compulsory for Gewerbetreibende. The total is made up of two parts:</p>',
            "<ul>\n    <li><strong>Grundbeitrag (base fee)</strong>: waived if the annual Gewerbeertrag is below <strong>EUR 5,200</strong></li>\n    <li><strong>Umlage (profit-based share)</strong>: for natural persons not entered in the commercial register, an <strong>allowance of EUR 15,340</strong> applies to the basis of assessment</li>\n</ul>",

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚡ Existenzgründer advantage</p>'."\n"
                .'    <p><strong>New founders</strong> (natural persons whose first Gewerbe was opened within the past five years) are <strong>fully exempt for two years</strong> from both Grundbeitrag and Umlage, provided their annual Gewerbeertrag stays below EUR 25,000. <strong>Check with your local IHK</strong>: the exact conditions vary slightly by chamber.</p>'."\n"
                .'</div>',

            '<h2>Comparison table</h2>',
            $this->table(['Criterion', 'Einzelunternehmen', 'Freiberufler'], [
                ['Gewerbeanmeldung', 'Yes', '~green~No'],
                ['Gewerbesteuer', 'Yes (above EUR 24,500)', '~green~No'],
                ['IHK membership', 'Compulsory', '~green~No'],
                ['Set-up cost', 'EUR 15-65', '~green~EUR 0'],
                ['Set-up time', '1-3 days', '~green~Immediate'],
            ], ' text-sm'),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">To check every year</p>'."\n"
                .'    <p>German thresholds, rates and tax rules change. This page is updated regularly, but for your own situation consult your Steuerberater or the official portals.</p>'."\n"
                .'</div>',

            '<h2>Official sources</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://www.existenzgruendungsportal.de/\" target=\"_blank\" rel=\"noopener\">Existenzgründungsportal (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.bmwk.de/\" target=\"_blank\" rel=\"noopener\">Federal Ministry for Economic Affairs (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.ihk.de/\" target=\"_blank\" rel=\"noopener\">IHK – Chamber of Industry and Commerce</a></li>\n"
                ."    <li><a href=\"https://www.deutsche-rentenversicherung.de/\" target=\"_blank\" rel=\"noopener\">Deutsche Rentenversicherung</a></li>\n"
                ."    <li><a href=\"https://gruenderplattform.de/\" target=\"_blank\" rel=\"noopener\">Gründerplattform</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">In short</p>'."\n"
                .'    <p>Setting up a sole proprietorship in Germany costs between EUR 0 and 65 depending on status. The Gewerbeanmeldung is processed in 1-3 days. The Kleinunternehmerregelung exempts you from VAT (previous year ≤ EUR 25,000, current year ≤ EUR 100,000), the second threshold biting immediately. Freiberufler pay neither Gewerbesteuer nor IHK fees. New founders can obtain a two-year IHK exemption under conditions.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/sole-proprietorship-luxembourg-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in Luxembourg: complete guide 2026 →</a></li><li><a href="/en/blog/freelancer-luxembourg-invoice-compliance" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer in Luxembourg: how to invoice in full compliance →</a></li><li><a href="/en/blog/vat-luxembourg-rates-calculation-obligations" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT 2026: the four rates explained →</a></li></ul></div>',
        ]);

        $lb = implode("\n\n", [
            '<p class="lead">Däitschland bitt e puer Méiglechkeete fir eng Eenzelentreprise ze grënnen, mat relativ einfachen a séiere Schrëtt. Dëse Guide stellt d\'Rechtsformen an d\'Etappe vir, fir 2026 unzefänken.</p>',

            '<h2>D\'Rechtsforme fir eng Eenzelentreprise</h2>',
            '<h3>Einzelunternehmen (Eenzelentreprise)</h3>',
            $this->table(['Charakteristik', 'Detail'], [
                ['Definitioun', 'Entreprise vun enger eenzeger Persoun gefouert'],
                ['Mindestkapital', 'Keent néideg'],
                ['Verantwortung', '<strong>Onbegrenzt</strong>'],
                ['Grënnung', 'Gewerbeanmeldung a Steiernummer'],
                ['Impositioun', 'Akommessteier a Gewerbesteuer (Freibetrag 24 500 €)'],
            ]),
            '<p><strong>Ënnerkategorien:</strong></p>',
            "<ul>\n    <li><strong>Kleingewerbetreibender:</strong> klenge Commerçant, keng Aschreiwung am Handelsregister</li>\n    <li><strong>Eingetragener Kaufmann (e.K.):</strong> am Handelsregister ageschriwwen</li>\n</ul>",

            '<h3>Freiberufler (fräie Beruff)</h3>',
            $this->table(['Charakteristik', 'Detail'], [
                ['Definitioun', 'Intellektuell, kreativ, wëssenschaftlech oder erzéieresch Aktivitéit'],
                ['Gewerbeanmeldung', '~green~NET néideg'],
                ['Gewerbesteuer', '~green~NET applicabel'],
                ['IHK/HWK', '~green~Kee Pflichtbäitrag'],
                ['Aschreiwung', 'Direkt beim Finanzamt'],
            ]),
            '<p><strong>Betraffe Beruffer (Katalogberufe):</strong> Dokteren, Affekoten, Architekten, Ingenieuren, Journalisten, Iwwersetzer, Kënschtler, Enseignanten …</p>',

            '<h2>Konditiounen a Viraussetzungen</h2>',
            '<h3>Fir Gewerbetreibende</h3>',
            "<ul>\n    <li><strong>Mindestalter:</strong> 18 Joer (Volljäregkeet)</li>\n    <li><strong>Wunnsëtz:</strong> Adress an Däitschland</li>\n    <li><strong>Dokumenter:</strong> Pass oder Identitéitskaart</li>\n    <li><strong>Legal Aktivitéit:</strong> vum Gesetz erlaabt Aktivitéit</li>\n</ul>",
            '<h3>Méiglech zousätzlech Dokumenter</h3>',
            "<ul>\n    <li><strong>Führungszeugnis</strong> (Auszuch aus dem Strofregister): ronn 13 €</li>\n    <li><strong>Gewerbezentralregisterauszug:</strong> ronn 13 €</li>\n    <li><strong>Handwierkerkaart:</strong> 80-250 €</li>\n</ul>",

            '<h2>Grënnungsschrëtt</h2>',
            '<h3>Wee A: Gewerbetreibender</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Schrëtt 1: Gewerbeanmeldung (Gewerbeamt)<br>\n        ↓<br>\n"
                ."        Schrëtt 2: Automatesch Meldungen (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>\n        ↓<br>\n"
                ."        Schrëtt 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>\n        ↓<br>\n"
                ."        Schrëtt 4: Zoudeele vun der Steiernummer<br>\n        ↓<br>\n"
                ."        Schrëtt 5: Aschreiwung bei der Berufsgenossenschaft (7 Deeg)\n"
                .'    </p>'."\n"
                .'</div>',
            '<h4>Gewerbeanmeldung</h4>',
            "<ul>\n    <li><strong>Wou:</strong> Gewerbeamt vun der Sëtzgemeng</li>\n    <li><strong>Formulaire:</strong> GewA 1</li>\n    <li><strong>Modus:</strong> online (Gewerbe-Service-Portal) oder op der Plaz</li>\n    <li><strong>Delai:</strong> 1-3 Deeg</li>\n</ul>",

            '<h3>Wee B: Freiberufler</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Schrëtt 1: Aschreiwung beim Finanzamt (bannent 4 Wochen nom Ufank)<br>\n        ↓<br>\n"
                ."        Schrëtt 2: Fragebogen zur steuerlichen Erfassung<br>\n        ↓<br>\n"
                ."        Schrëtt 3: Zoudeele vun der Steiernummer\n"
                .'    </p>'."\n"
                .'</div>',

            '<h2>Grënnungskäschten</h2>',
            $this->table(['Posten', 'Montant'], [
                ['Gewerbeanmeldung (no Gemeng)', '15 - 65 €'],
                ['Groussstied (München, Stuttgart, Hamburg)', '50 - 65 €'],
                ['Méi kleng Gemengen', '15 - 30 €'],
                ['Freiberufler', '~green~<strong>0 € (gratis)</strong>'],
            ]),

            '<h2>Duerchschnëttlech Delaien</h2>',
            $this->table(['Schrëtt', 'Delai'], [
                ['Behandlung vun der Gewerbeanmeldung', '<strong>1-3 Deeg</strong>'],
                ['Schrëftlech Bestätegung vum Gewerbeamt', 'Héchstens 3 Deeg'],
                ['Kréie vum Fragebogen vum Finanzamt', '4-6 Wochen'],
                ['Zoudeele vun der Steiernummer', '2-4 Wochen'],
                ['<strong>Gesamtdelai</strong>', '<strong>6-10 Wochen</strong>'],
            ]),

            '<h2>Obligatiounen no der Grënnung</h2>',
            '<h3>TVA / Umsatzsteuer</h3>',
            '<h4>Normalregime</h4>',
            "<ul>\n    <li><strong>Standardsaz:</strong> 19 %</li>\n    <li><strong>Reduzéierte Saz:</strong> 7 %</li>\n    <li>Méintlech oder trimestriell Umsatzsteuer-Voranmeldung</li>\n</ul>",
            '<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>',
            $this->table(['Kriterium', 'Schwell 2026'], [
                ['Ëmsaz vum Joer virdrun (N-1)', '≤ 25 000 €'],
                ['Ëmsaz vum lafende Joer (N)', '≤ 100 000 €'],
            ]),
            '<p>Déi zwou Schwelle wierken net d\'selwecht, an de § 19 UStG seet dat scho mat senger Grammatik: déi éischt steet an der Vergaangenheet, déi zweet an der Géigewaart.</p>',
            "<ul>\n    <li><strong>25 000 € (Joer virdrun)</strong>: Konditioun, déi am Nohinein festgestallt gëtt. Se ze iwwerschreiden hëlt Iech de Regime <strong>fir d'Joer duerno</strong>.</li>\n    <li><strong>100 000 € (lafend Joer)</strong>: haarde Plafong. En ze iwwerschreide beendegt d'Befreiung <strong>direkt, vun der Operatioun un, déi en iwwerschreit</strong> – Dir fakturéiert d'TVA vun do un, ouni den 1. Januar ofzewaarden.</li>\n</ul>",
            '<p><strong>Virdeeler:</strong></p>',
            "<ul>\n    <li>Keng TVA ze fakturéieren</li>\n    <li>Keng TVA-Deklaratiounen</li>\n    <li>Vereinfacht Comptabilitéit</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtmentioun op de Rechnungen</p>'."\n"
                .'    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »</p>'."\n"
                .'</div>',

            '<h3>Gewerbesteuer</h3>',
            $this->table(['Situatioun', 'Obligatioun'], [
                ['Freiberufler', '~green~Befreit'],
                ['Gewerbetreibender (natierlech Persoun) mat Gewerbeertrag &lt; 24 500 €/Joer', '~green~Befreit (Freibetrag)'],
                ['Gewerbetreibender ≥ 24 500 €/Joer', 'Steierpflichteg op deem Deel doriwwer'],
            ]),

            '<h3>Sozialbäiträg</h3>',
            $this->table(['Typ', 'Obligatioun'], [
                ['Krankenversicherung (Krankheet)', '~red~OBLIGATORESCH'],
                ['Pflegeversicherung (Ofhängegkeet)', '~red~OBLIGATORESCH'],
                ['Rentenversicherung (Pensioun)', 'Fakultativ*'],
                ['Arbeitslosenversicherung (Chômage)', 'Fakultativ'],
            ]),
            '<p><small>*Obligatoresch fir gewësse Beruffer (Handwierker, Enseignanten, Fleegepersonal, Hiewamen asw.)</small></p>',

            '<h3>IHK-/HWK-Bäitrag</h3>',
            '<p>Automatesch an obligatoresch Memberschaft fir Gewerbetreibende. De Gesamtbetrag besteet aus zwee Deeler:</p>',
            "<ul>\n    <li><strong>Grundbeitrag</strong>: Befreiung, wann de jäerleche Gewerbeertrag ënner <strong>5 200 €</strong> läit</li>\n    <li><strong>Umlage</strong> (Deel proportional zum Gewënn): fir natierlech Persounen, déi net am Handelsregister ageschriwwe sinn, gëllt e <strong>Freibetrag vun 15 340 €</strong> op der Berechnungsbasis</li>\n</ul>",

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚡ Virdeel fir Existenzgründer</p>'."\n"
                .'    <p><strong>Nei Grënner</strong> (natierlech Persounen, éischt Gewerbe bannent de leschte fënnef Joer opgemaach) si <strong>zwee Joer laang komplett</strong> vum Grundbeitrag a vun der Umlage befreit, wann hire jäerleche Gewerbeertrag ënner 25 000 € bleift. <strong>Frot bei Ärer lokaler IHK no</strong>, déi genee Konditiounen ënnerscheede sech liicht no Kummer.</p>'."\n"
                .'</div>',

            '<h2>Verglachstabell</h2>',
            $this->table(['Kritär', 'Einzelunternehmen', 'Freiberufler'], [
                ['Gewerbeanmeldung', 'Jo', '~green~Nee'],
                ['Gewerbesteuer', 'Jo (iwwer 24 500 €)', '~green~Nee'],
                ['IHK-Memberschaft', 'Obligatoresch', '~green~Nee'],
                ['Grënnungskäschten', '15-65 €', '~green~0 €'],
                ['Grënnungsdelai', '1-3 Deeg', '~green~Direkt'],
            ], ' text-sm'),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">All Joer ze kontrolléieren</p>'."\n"
                .'    <p>Déi däitsch Schwellen, Sätz a Steierreegelen änneren sech. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Ären Steuerberater oder déi offiziell Portaler.</p>'."\n"
                .'</div>',

            '<h2>Offiziell Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://www.existenzgruendungsportal.de/\" target=\"_blank\" rel=\"noopener\">Existenzgründungsportal (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.bmwk.de/\" target=\"_blank\" rel=\"noopener\">Bundesministerium für Wirtschaft (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.ihk.de/\" target=\"_blank\" rel=\"noopener\">IHK – Industrie- und Handelskammer</a></li>\n"
                ."    <li><a href=\"https://www.deutsche-rentenversicherung.de/\" target=\"_blank\" rel=\"noopener\">Deutsche Rentenversicherung</a></li>\n"
                ."    <li><a href=\"https://gruenderplattform.de/\" target=\"_blank\" rel=\"noopener\">Gründerplattform</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kuerz zesummegefaasst</p>'."\n"
                .'    <p>Eng Eenzelentreprise an Däitschland ze grënnen kascht tëscht 0 an 65 € no Statut. D\'Gewerbeanmeldung gëtt an 1-3 Deeg behandelt. D\'Kleinunternehmerregelung befreit vun der TVA (Joer virdrun ≤ 25 000 €, lafend Joer ≤ 100 000 €), wouduerch déi zweet Schwell direkt gräift. Freiberufler bezuele weder Gewerbesteuer nach IHK-Bäitrag. Nei Grënner kënnen ënner Konditiounen zwee Joer IHK-Befreiung kréien.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/eenzelentreprise-letzebuerg-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise zu Lëtzebuerg grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/freelancer-letzebuerg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer zu Lëtzebuerg: konform fakturéieren →</a></li><li><a href="/lb/blog/tva-letzebuerg-tariffer-berechnung-obligatiounen" class="text-primary-500 hover:text-primary-600 text-sm">TVA Lëtzebuerg 2026: déi 4 Sätz erkläert →</a></li></ul></div>',
        ]);

        $pt = implode("\n\n", [
            '<p class="lead">A Alemanha oferece várias opções para criar uma empresa individual, com formalidades relativamente simples e rápidas. Este guia apresenta as formas jurídicas e as etapas para arrancar em 2026.</p>',

            '<h2>As formas jurídicas para empresa individual</h2>',
            '<h3>Einzelunternehmen (empresa individual)</h3>',
            $this->table(['Característica', 'Detalhe'], [
                ['Definição', 'Empresa gerida por uma única pessoa'],
                ['Capital mínimo', 'Nenhum exigido'],
                ['Responsabilidade', '<strong>Ilimitada</strong>'],
                ['Criação', 'Gewerbeanmeldung e número fiscal'],
                ['Tributação', 'Imposto sobre o rendimento e Gewerbesteuer (Freibetrag 24 500 €)'],
            ]),
            '<p><strong>Subcategorias:</strong></p>',
            "<ul>\n    <li><strong>Kleingewerbetreibender:</strong> pequeno comerciante, sem inscrição no registo comercial</li>\n    <li><strong>Eingetragener Kaufmann (e.K.):</strong> inscrito no registo comercial</li>\n</ul>",

            '<h3>Freiberufler (profissão liberal)</h3>',
            $this->table(['Característica', 'Detalhe'], [
                ['Definição', 'Atividade intelectual, criativa, científica ou educativa'],
                ['Gewerbeanmeldung', '~green~NÃO exigida'],
                ['Gewerbesteuer', '~green~NÃO aplicável'],
                ['IHK/HWK', '~green~Sem quota obrigatória'],
                ['Inscrição', 'Diretamente no Finanzamt'],
            ]),
            '<p><strong>Profissões abrangidas (Katalogberufe):</strong> médicos, advogados, arquitetos, engenheiros, jornalistas, tradutores, artistas, professores…</p>',

            '<h2>Condições e requisitos</h2>',
            '<h3>Para os Gewerbetreibende</h3>',
            "<ul>\n    <li><strong>Idade mínima:</strong> 18 anos (maioridade)</li>\n    <li><strong>Residência:</strong> morada na Alemanha</li>\n    <li><strong>Documentos:</strong> passaporte ou cartão de identificação</li>\n    <li><strong>Atividade legal:</strong> atividade autorizada por lei</li>\n</ul>",
            '<h3>Documentos adicionais possíveis</h3>',
            "<ul>\n    <li><strong>Führungszeugnis</strong> (certificado de registo criminal): cerca de 13 €</li>\n    <li><strong>Gewerbezentralregisterauszug:</strong> cerca de 13 €</li>\n    <li><strong>Cartão de artesão:</strong> 80-250 €</li>\n</ul>",

            '<h2>Etapas de criação</h2>',
            '<h3>Percurso A: Gewerbetreibender</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Etapa 1: Gewerbeanmeldung (Gewerbeamt)<br>\n        ↓<br>\n"
                ."        Etapa 2: Notificações automáticas (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>\n        ↓<br>\n"
                ."        Etapa 3: Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>\n        ↓<br>\n"
                ."        Etapa 4: Atribuição do Steuernummer<br>\n        ↓<br>\n"
                ."        Etapa 5: Inscrição na Berufsgenossenschaft (7 dias)\n"
                .'    </p>'."\n"
                .'</div>',
            '<h4>Gewerbeanmeldung</h4>',
            "<ul>\n    <li><strong>Onde:</strong> Gewerbeamt do município da sede</li>\n    <li><strong>Formulário:</strong> GewA 1</li>\n    <li><strong>Modo:</strong> em linha (Gewerbe-Service-Portal) ou presencial</li>\n    <li><strong>Prazo:</strong> 1-3 dias</li>\n</ul>",

            '<h3>Percurso B: Freiberufler</h3>',
            '<div class="bg-slate-50 p-4 rounded-lg my-4">'."\n"
                .'    <p class="font-mono text-sm">'."\n"
                ."        Etapa 1: Inscrição no Finanzamt (nas 4 semanas seguintes ao início)<br>\n        ↓<br>\n"
                ."        Etapa 2: Fragebogen zur steuerlichen Erfassung<br>\n        ↓<br>\n"
                ."        Etapa 3: Atribuição do Steuernummer\n"
                .'    </p>'."\n"
                .'</div>',

            '<h2>Custos de criação</h2>',
            $this->table(['Rubrica', 'Montante'], [
                ['Gewerbeanmeldung (varia consoante o município)', '15 - 65 €'],
                ['Grandes cidades (Munique, Estugarda, Hamburgo)', '50 - 65 €'],
                ['Municípios pequenos', '15 - 30 €'],
                ['Freiberufler', '~green~<strong>0 € (gratuito)</strong>'],
            ]),

            '<h2>Prazos médios</h2>',
            $this->table(['Etapa', 'Prazo'], [
                ['Tratamento da Gewerbeanmeldung', '<strong>1-3 dias</strong>'],
                ['Confirmação escrita do Gewerbeamt', '3 dias no máximo'],
                ['Receção do Fragebogen do Finanzamt', '4-6 semanas'],
                ['Atribuição do Steuernummer', '2-4 semanas'],
                ['<strong>Prazo total</strong>', '<strong>6-10 semanas</strong>'],
            ]),

            '<h2>Obrigações após a criação</h2>',
            '<h3>IVA / Umsatzsteuer</h3>',
            '<h4>Regime normal</h4>',
            "<ul>\n    <li><strong>Taxa normal:</strong> 19 %</li>\n    <li><strong>Taxa reduzida:</strong> 7 %</li>\n    <li>Umsatzsteuer-Voranmeldung mensal ou trimestral</li>\n</ul>",
            '<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>',
            $this->table(['Critério', 'Limiar 2026'], [
                ['Volume de negócios do ano anterior (N-1)', '≤ 25 000 €'],
                ['Volume de negócios do ano em curso (N)', '≤ 100 000 €'],
            ]),
            '<p>Os dois limiares não funcionam da mesma maneira, e o § 19 UStG di-lo na própria gramática: o primeiro está no passado, o segundo no presente.</p>',
            "<ul>\n    <li><strong>25 000 € (ano anterior)</strong>: condição verificada a posteriori. Ultrapassá-la priva-o do regime <strong>no ano seguinte</strong>.</li>\n    <li><strong>100 000 € (ano em curso)</strong>: teto rígido. Ultrapassá-lo põe fim à isenção <strong>imediatamente, a partir da operação que o excede</strong> — passa a faturar IVA a partir daí, sem esperar por 1 de janeiro.</li>\n</ul>",
            '<p><strong>Vantagens:</strong></p>',
            "<ul>\n    <li>Sem IVA a faturar</li>\n    <li>Sem declarações de IVA</li>\n    <li>Contabilidade simplificada</li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Menção obrigatória nas faturas</p>'."\n"
                .'    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »<br>'."\n"
                .'    <em>(Sem IVA indicado, regime de pequenos empresários nos termos do § 19 UStG)</em></p>'."\n"
                .'</div>',

            '<h3>Gewerbesteuer (imposto profissional)</h3>',
            $this->table(['Situação', 'Obrigação'], [
                ['Freiberufler', '~green~Isento'],
                ['Gewerbetreibender (pessoa singular) com Gewerbeertrag &lt; 24 500 €/ano', '~green~Isento (Freibetrag)'],
                ['Gewerbetreibender ≥ 24 500 €/ano', 'Tributado sobre a parte excedente'],
            ]),

            '<h3>Contribuições sociais</h3>',
            $this->table(['Tipo', 'Obrigação'], [
                ['Krankenversicherung (doença)', '~red~OBRIGATÓRIA'],
                ['Pflegeversicherung (dependência)', '~red~OBRIGATÓRIA'],
                ['Rentenversicherung (reforma)', 'Facultativa*'],
                ['Arbeitslosenversicherung (desemprego)', 'Facultativa'],
            ]),
            '<p><small>*Obrigatória para certas profissões (artesãos, professores, cuidadores, parteiras, entre outros)</small></p>',

            '<h3>Quota IHK/HWK</h3>',
            '<p>Adesão automática e obrigatória para os Gewerbetreibende. O total compõe-se de duas partes:</p>',
            "<ul>\n    <li><strong>Grundbeitrag (quota de base)</strong>: isenção se o Gewerbeertrag anual for inferior a <strong>5 200 €</strong></li>\n    <li><strong>Umlage (parte proporcional ao lucro)</strong>: para pessoas singulares não inscritas no registo comercial, aplica-se um <strong>Freibetrag de 15 340 €</strong> à base de cálculo</li>\n</ul>",

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚡ Vantagem Existenzgründer</p>'."\n"
                .'    <p>Os <strong>novos criadores</strong> (pessoas singulares cujo primeiro Gewerbe foi aberto nos últimos cinco anos) beneficiam de <strong>dois anos de isenção total</strong> de Grundbeitrag e Umlage, desde que o Gewerbeertrag anual se mantenha abaixo de 25 000 €. <strong>Confirme junto da sua IHK local</strong>: as condições exatas variam ligeiramente consoante a câmara.</p>'."\n"
                .'</div>',

            '<h2>Quadro comparativo</h2>',
            $this->table(['Critério', 'Einzelunternehmen', 'Freiberufler'], [
                ['Gewerbeanmeldung', 'Sim', '~green~Não'],
                ['Gewerbesteuer', 'Sim (acima de 24 500 €)', '~green~Não'],
                ['Adesão à IHK', 'Obrigatória', '~green~Não'],
                ['Custo de criação', '15-65 €', '~green~0 €'],
                ['Prazo de criação', '1-3 dias', '~green~Imediato'],
            ], ' text-sm'),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
                .'    <p>Os limiares, as taxas e as regras fiscais alemãs evoluem. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu Steuerberater ou os portais oficiais.</p>'."\n"
                .'</div>',

            '<h2>Fontes oficiais</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://www.existenzgruendungsportal.de/\" target=\"_blank\" rel=\"noopener\">Existenzgründungsportal (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.bmwk.de/\" target=\"_blank\" rel=\"noopener\">Ministério Federal da Economia (BMWK)</a></li>\n"
                ."    <li><a href=\"https://www.ihk.de/\" target=\"_blank\" rel=\"noopener\">IHK – Câmara de Indústria e Comércio</a></li>\n"
                ."    <li><a href=\"https://www.deutsche-rentenversicherung.de/\" target=\"_blank\" rel=\"noopener\">Deutsche Rentenversicherung</a></li>\n"
                ."    <li><a href=\"https://gruenderplattform.de/\" target=\"_blank\" rel=\"noopener\">Gründerplattform</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Em resumo</p>'."\n"
                .'    <p>Criar uma empresa individual na Alemanha custa entre 0 e 65 € consoante o estatuto. A Gewerbeanmeldung é tratada em 1-3 dias. A Kleinunternehmerregelung isenta de IVA (ano anterior ≤ 25 000 €, ano em curso ≤ 100 000 €), sendo que o segundo limiar produz efeito imediato. Os Freiberufler não pagam Gewerbesteuer nem quota IHK. Os novos criadores podem obter dois anos de isenção IHK sob condições.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual no Luxemburgo: guia completo 2026 →</a></li><li><a href="/pt/blog/freelancer-no-luxemburgo-como-faturar-em-total-conformidade" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer no Luxemburgo: como faturar em total conformidade →</a></li><li><a href="/pt/blog/iva-no-luxemburgo-taxas-calculo-e-obrigacoes-para-as-empresas" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo 2026: as 4 taxas explicadas →</a></li></ul></div>',
        ]);

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
