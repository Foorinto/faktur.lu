<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guide « Créer une entreprise individuelle en France ».
 *
 * Vérifications faites le 2026-08-01 contre entreprendre.service-public.gouv.fr :
 *   - seuils micro-entreprise 2026 : 203 100 EUR et 83 600 EUR — confirmés ;
 *   - franchise en base de TVA : 85 000 / 93 500 et 37 500 / 41 250 — confirmés,
 *     et le seuil unique de 25 000 EUR issu de la loi de finances 2025 a été
 *     ABANDONNÉ, donc rien à changer de ce côté ;
 *   - taux de cotisation 21,2 % pour les services BIC — confirmé par la page ACRE.
 *
 * Trois corrections.
 *
 * 1. L'ACRE était rédigée au futur. La page officielle, vérifiée le 1er juillet
 *    2026, donne l'état actuel : l'exonération est de 25 %, le taux appliqué
 *    valant 75 % du taux normal. L'encart annonçait encore « Changement au
 *    1er juillet 2026 » comme à venir, ce qui induit désormais en erreur.
 *
 * 2. Délai de demande. L'article disait « 45 jours (60 jours à partir du
 *    1er janvier 2026) ». Le délai applicable est de 60 jours, et l'Urssaf
 *    statue sous 30 jours, son silence valant accord — précision utile absente.
 *
 * 3. Tableau de la franchise en base : il alignait « seuil de base » et « seuil
 *    majoré » sans dire ce que chacun déclenche. Or les conséquences diffèrent
 *    radicalement — dépassement du seuil de base : TVA au 1er janvier suivant ;
 *    dépassement du seuil majoré : TVA dès le premier jour du dépassement.
 *    C'est le défaut récurrent de ce blog, la règle sans sa condition.
 *
 * Les taux 12,3 %, 25,6 % et 23,2 % n'ont pas pu être confirmés : le site de
 * l'Urssaf refuse les requêtes automatisées. Ils sont conservés en l'état,
 * aucun élément ne les contredisant, et l'encart de prudence renvoie déjà à
 * l'Urssaf.
 *
 * DE, EN, LB, PT : 8 906 à 9 544 caractères contre 11 708, six à sept liens
 * contre quatorze.
 */
return new class extends Migration
{
    private const KEY = 'creer-entreprise-individuelle-france-guide-2025';

    private const FR_FIXES = [
        [
            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">⚠ Changement au 1<sup>er</sup> juillet 2026</p>'."\n"
                .'    <p>Pour les créations <strong>avant le 1<sup>er</sup> juillet 2026</strong> : exonération de 50 % (taux minoré à 50 % du taux normal).<br>'."\n"
                .'    Pour les créations <strong>à partir du 1<sup>er</sup> juillet 2026</strong> : exonération réduite à <strong>25 %</strong> (taux porté à 75 % du taux normal). Voir <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public - ACRE</a>.</p>'."\n"
                .'</div>',
            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Taux en vigueur depuis le 1<sup>er</sup> juillet 2026</p>'."\n"
                .'    <p>L\'exonération est de <strong>25 %</strong> : le micro-entrepreneur acquitte 75 % du taux normal de cotisations. Un électricien relevant des prestations de services commerciales et artisanales cotise ainsi à <strong>15,9 %</strong> au lieu de 21,2 %. Les créations antérieures au 1<sup>er</sup> juillet 2026 bénéficiaient d\'une exonération de 50 %. Voir <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public - ACRE</a>.</p>'."\n"
                .'</div>',
        ],
        [
            "    <li>Demande à effectuer au moment de la création ou dans les 45 jours (60 jours à partir du 1<sup>er</sup> janvier 2026)</li>",
            "    <li>Demande à déposer auprès de l'Urssaf dans les <strong>60 jours</strong> qui suivent la date d'ouverture de l'activité</li>\n"
                ."    <li>L'Urssaf statue sous 30 jours ; <strong>son silence vaut accord</strong></li>",
        ],
        [
            '        <tr><td class="p-2 border-b">Prestations de services</td><td class="p-2 border-b">37 500 €</td><td class="p-2 border-b">41 250 €</td></tr>'."\n"
                .'    </tbody>'."\n"
                .'</table>',
            '        <tr><td class="p-2 border-b">Prestations de services</td><td class="p-2 border-b">37 500 €</td><td class="p-2 border-b">41 250 €</td></tr>'."\n"
                .'    </tbody>'."\n"
                .'</table>'."\n\n"
                .'<p>Les deux seuils ne produisent pas le même effet :</p>'."\n\n"
                .'<ul>'."\n"
                .'    <li>Dépasser le <strong>seuil de base</strong> une année vous rend redevable de la TVA <strong>au 1<sup>er</sup> janvier suivant</strong></li>'."\n"
                .'    <li>Dépasser le <strong>seuil majoré</strong> vous y rend redevable <strong>dès le premier jour du dépassement</strong></li>'."\n"
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

        // Le résumé annonçait encore les deux taux d'ACRE comme un avant/après.
        $content = str_replace(
            "L'ACRE offre une exonération partielle la 1<sup>re</sup> année (50 % avant le 1<sup>er</sup> juillet 2026, 25 % après).",
            "L'ACRE offre depuis le 1<sup>er</sup> juillet 2026 une exonération de 25 % des cotisations la 1<sup>re</sup> année.",
            $content
        );

        DB::table('blog_posts')->where('id', $post->id)->update([
            'content' => $content,
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  array<int, string>  $heads
     */
    private function table(array $heads, array $rows): string
    {
        $head = '';
        foreach ($heads as $h) {
            $head .= '            <th class="text-left p-2 bg-slate-100">'.$h."</th>\n";
        }

        $body = '';
        foreach ($rows as $cells) {
            $body .= '        <tr>';
            foreach ($cells as $c) {
                $body .= '<td class="p-2 border-b">'.$c.'</td>';
            }
            $body .= "</tr>\n";
        }

        return "<table class=\"w-full my-4\">\n    <thead>\n        <tr>\n{$head}        </tr>\n    </thead>\n    <tbody>\n{$body}    </tbody>\n</table>";
    }

    /** @return array<string, string> */
    private function translations(): array
    {
        $de = implode("\n\n", [
            '<p class="lead">Frankreich bietet einen vereinfachten Rahmen fuer die Gruendung eines Einzelunternehmens, insbesondere mit dem Regime der Micro-Entreprise. Seit 2023 laufen saemtliche Formalitaeten ueber den zentralen Schalter des INPI. Hier finden Sie Schritte, Kosten und Pflichten fuer den Start 2026.</p>',

            '<h2>Rechtsformen fuer ein Einzelunternehmen</h2>',
            '<h3>Entreprise Individuelle (EI)</h3>',
            '<p>Das Einzelunternehmen erlaubt es, eine Taetigkeit im eigenen Namen auszuueben, ohne eine juristische Person zu gruenden.</p>',
            "<ul>\n    <li>Kein Stammkapital erforderlich</li>\n    <li>Kein Gesellschaftsvertrag zu verfassen</li>\n    <li>Moegliche Taetigkeiten: gewerblich, handwerklich, landwirtschaftlich oder freiberuflich</li>\n    <li><strong>Seit dem 15. Mai 2022</strong>: Privat- und Betriebsvermoegen sind automatisch getrennt (Inkrafttreten des neuen einheitlichen EI-Status aus dem <a href=\"https://www.legifrance.gouv.fr/loda/id/JORFTEXT000045161732/\" target=\"_blank\" rel=\"noopener\">Gesetz vom 14. Februar 2022</a>)</li>\n</ul>",

            '<h3>Micro-Entreprise (Auto-Entrepreneur)</h3>',
            '<p>Die Micro-Entreprise ist eine vereinfachte Form des Einzelunternehmens mit Umsatzgrenzen:</p>',
            $this->table(['Art der Taetigkeit', 'Umsatzgrenze (2026)'], [
                ['Warenverkauf, Beherbergung', '203 100 EUR'],
                ['Dienstleistungen', '83 600 EUR'],
            ]),

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Gut zu wissen: die EIRL gibt es nicht mehr</p>'."\n"
                .'    <p>Das Gesetz vom 14. Februar 2022 (in Kraft seit dem 15. Mai 2022) hat die <strong>Moeglichkeit abgeschafft, eine EIRL zu gruenden</strong>. Der neue EI-Status enthaelt die automatische Vermoegenstrennung, ohne besondere Formalitaet.</p>'."\n"
                .'</div>',

            '<h2>Voraussetzungen</h2>',
            '<h3>Persoenliche Voraussetzungen</h3>',
            "<ul>\n    <li><strong>Volljaehrig</strong> sein (oder emanzipierter Minderjaehriger)</li>\n    <li>Eine <strong>Anschrift in Frankreich</strong> haben</li>\n    <li>Nicht unter Betreuung oder Pflegschaft stehen</li>\n    <li>Kein Geschaeftsfuehrungsverbot haben</li>\n    <li>Franzoesischer oder europaeischer Staatsangehoeriger sein oder einen Aufenthaltstitel besitzen, der die Taetigkeit erlaubt</li>\n</ul>",

            '<h3>Reglementierte Taetigkeiten</h3>',
            '<p>Bestimmte Berufe setzen besondere Abschluesse oder Qualifikationen voraus: Friseurhandwerk, Bauwesen, Gesundheitsberufe usw.</p>',

            '<h2>Gruendungsschritte ueber den zentralen INPI-Schalter</h2>',
            '<h3>Schritt 1: Unterlagen vorbereiten</h3>',
            "<ul>\n    <li>Ausweisdokument (Personalausweis oder Reisepass) im PDF-Format</li>\n    <li>Wohnsitznachweis (bei Taetigkeit von zu Hause aus)</li>\n    <li>Qualifikationsnachweise fuer reglementierte Taetigkeiten</li>\n</ul>",

            '<h3>Schritt 2: Konto anlegen</h3>',
            '<p>Auf <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> gehen und ein Konto ueber FranceConnect (empfohlen) oder eine INPI-Kennung anlegen.</p>',

            '<h3>Schritt 3: Taetigkeit anmelden</h3>',
            "<ol>\n    <li>Auf „Déclarer\" klicken</li>\n    <li>„Entrepreneur individuel\" auswaehlen</li>\n    <li>Angaben machen: Art der Taetigkeit, Anschrift, Beginn, steuerliche und soziale Optionen</li>\n</ol>",

            '<h3>Schritt 4: Bestaetigung und Nachverfolgung</h3>',
            "<ul>\n    <li>Belege beifuegen</li>\n    <li>Gegebenenfalls die Zahlung vornehmen</li>\n    <li>Den Fortschritt ueber das Dashboard verfolgen</li>\n    <li>Automatische Eintragung ins RNE (Registre National des Entreprises)</li>\n</ul>",

            '<h2>Gruendungskosten</h2>',
            $this->table(['Art der Taetigkeit', 'Kosten'], [
                ['Gewerbliche Taetigkeit', '<span class="text-green-600 font-semibold">Kostenlos</span>'],
                ['Handwerkliche Taetigkeit', '<span class="text-green-600 font-semibold">Kostenlos</span>'],
                ['Freier Beruf', '<span class="text-green-600 font-semibold">Kostenlos</span>'],
                ['Handelsvertreter', '23,86 EUR'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Achtung</p>'."\n"
                .'    <p>Huetten Sie sich vor privaten Websites, die Gebuehren fuer eine an sich kostenlose Leistung verlangen.</p>'."\n"
                .'</div>',

            '<h2>Uebliche Fristen</h2>',
            $this->table(['Schritt', 'Frist'], [
                ['Online-Anmeldung', 'Wenige Minuten'],
                ['Eingangsbestaetigung', '24 Stunden'],
                ['Erhalt der SIRET-Nummer', '<strong>1 bis 2 Wochen</strong>'],
                ['URSSAF-Mitteilung', '4 bis 10 Wochen'],
            ]),

            '<h2>Pflichten nach der Gruendung</h2>',
            '<h3>URSSAF-Beitraege (Micro-Entreprise)</h3>',
            $this->table(['Art der Taetigkeit', 'Satz 2026'], [
                ['Handel (An- und Verkauf)', '12,3 %'],
                ['Gewerbliche/handwerkliche Dienstleistungen', '21,2 %'],
                ['Sonstige Dienstleistungen', '25,6 %'],
                ['Freie Berufe (Cipav)', '23,2 %'],
            ]),
            '<p><strong>Rhythmus:</strong> monatliche oder vierteljaehrliche Erklaerung (nach Wahl). Die Erklaerung ist auch bei einem Umsatz von null abzugeben.</p>',

            '<h3>MwSt – Franchise en base</h3>',
            $this->table(['Art der Taetigkeit', 'Grundschwelle', 'Erhoehte Schwelle'], [
                ['Verkauf/Handel/Beherbergung', '85 000 EUR', '93 500 EUR'],
                ['Dienstleistungen', '37 500 EUR', '41 250 EUR'],
            ]),
            '<p>Die beiden Schwellen haben nicht dieselbe Wirkung:</p>',
            "<ul>\n    <li>Wird die <strong>Grundschwelle</strong> in einem Jahr ueberschritten, werden Sie <strong>zum 1. Januar des Folgejahres</strong> mehrwertsteuerpflichtig</li>\n    <li>Wird die <strong>erhoehte Schwelle</strong> ueberschritten, werden Sie es <strong>ab dem ersten Tag der Ueberschreitung</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtangabe bei Befreiung</p>'."\n"
                .'    <p>« TVA non applicable, art. 293 B du CGI »</p>'."\n"
                .'</div>',

            '<h3>CFE (Grundsteuer der Unternehmen)</h3>',
            "<ul>\n    <li><strong>Erstes Jahr:</strong> von der Zahlung befreit</li>\n    <li><strong>Vollstaendige Befreiung:</strong> bei einem Jahresumsatz &lt; 5 000 EUR</li>\n    <li><strong>Pflicht:</strong> die Erklaerung Nr. 1447-C-SD vor dem 31. Dezember des ersten Jahres einreichen</li>\n</ul>",

            '<h3>Buchfuehrungspflichten</h3>',
            "<ol>\n    <li><strong>Konforme Rechnungen</strong> fuer jeden Verkauf bzw. jede Leistung ausstellen</li>\n    <li>Ein chronologisches <strong>Einnahmenbuch</strong> fuehren</li>\n    <li>Ein <strong>Einkaufsregister</strong> fuehren (bei Verkaufstaetigkeit)</li>\n    <li><strong>Belege zehn Jahre aufbewahren</strong></li>\n</ol>",

            '<h2>Verfuegbare Hilfen</h2>',
            '<h3>ACRE (Hilfe fuer Gruender und Uebernehmer)</h3>',
            '<p>Die ACRE gewaehrt im ersten Taetigkeitsjahr eine <strong>teilweise Befreiung</strong> von den Sozialbeitraegen.</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Seit dem 1. Juli 2026 geltender Satz</p>'."\n"
                .'    <p>Die Befreiung betraegt <strong>25 %</strong>: der Micro-Entrepreneur zahlt 75 % des normalen Beitragssatzes. Ein Elektriker, der unter die gewerblichen und handwerklichen Dienstleistungen faellt, zahlt somit <strong>15,9 %</strong> statt 21,2 %. Gruendungen vor dem 1. Juli 2026 kamen in den Genuss einer Befreiung von 50 %. Siehe <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public – ACRE</a>.</p>'."\n"
                .'</div>',

            "<ul>\n    <li>Voraussetzungen: Arbeitsuchende, RSA-Empfaenger, Jugendliche von 18 bis 25 Jahren, uebernehmende Arbeitnehmer usw.</li>\n    <li>Antrag bei der Urssaf innerhalb von <strong>60 Tagen</strong> nach dem im Gruendungsnachweis genannten Taetigkeitsbeginn</li>\n    <li>Die Urssaf entscheidet binnen 30 Tagen; <strong>ihr Schweigen gilt als Zustimmung</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Jaehrlich zu pruefen</p>'."\n"
                .'    <p>Die franzoesischen Schwellen, Saetze und Verfahren aendern sich haeufig. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich an die <a href="https://www.urssaf.fr/" target="_blank" rel="noopener">URSSAF</a>, an <a href="https://entreprendre.service-public.gouv.fr/" target="_blank" rel="noopener">Service Public</a> oder an einen Steuerberater.</p>'."\n"
                .'</div>',

            '<h2>Offizielle Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F37396\" target=\"_blank\" rel=\"noopener\">Service Public – Entrepreneur Individuel</a></li>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F11677\" target=\"_blank\" rel=\"noopener\">Service Public – ACRE</a></li>\n"
                ."    <li><a href=\"https://formalites.entreprises.gouv.fr/\" target=\"_blank\" rel=\"noopener\">Zentraler Schalter fuer Unternehmensformalitaeten</a></li>\n"
                ."    <li><a href=\"https://www.autoentrepreneur.urssaf.fr/\" target=\"_blank\" rel=\"noopener\">URSSAF Auto-entrepreneur</a></li>\n"
                ."    <li><a href=\"https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei\" target=\"_blank\" rel=\"noopener\">INPI – Sein Einzelunternehmen gruenden</a></li>\n"
                ."    <li><a href=\"https://bpifrance-creation.fr/\" target=\"_blank\" rel=\"noopener\">Bpifrance Création</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kurz gefasst</p>'."\n"
                .'    <p>Die Gruendung einer Micro-Entreprise in Frankreich ist kostenlos und schnell (SIRET in 1-2 Wochen). Die Sozialbeitraege liegen je nach Taetigkeit zwischen 12 und 26 %. Die MwSt-Befreiung erlaubt es, unterhalb bestimmter Schwellen keine MwSt zu berechnen. Die ACRE gewaehrt seit dem 1. Juli 2026 im ersten Jahr eine Befreiung von 25 %.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/einzelunternehmen-luxemburg-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Luxemburg gruenden: Leitfaden 2026 →</a></li><li><a href="/de/blog/einzelunternehmen-deutschland-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Deutschland gruenden: Leitfaden 2026 →</a></li><li><a href="/de/blog/freiberufler-luxemburg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freiberufler in Luxemburg: rechtssicher fakturieren →</a></li></ul></div>',
        ]);

        $en = implode("\n\n", [
            '<p class="lead">France offers a simplified framework for setting up a sole proprietorship, in particular through the micro-entreprise scheme. Since 2023 every formality goes through the INPI single window. Here are the steps, costs and obligations for starting out in 2026.</p>',

            '<h2>Legal forms for a sole proprietorship</h2>',
            '<h3>Entreprise Individuelle (EI)</h3>',
            '<p>The sole proprietorship lets you carry on an activity in your own name, without creating a legal entity.</p>',
            "<ul>\n    <li>No share capital required</li>\n    <li>No articles of association to draft</li>\n    <li>Possible activities: commercial, craft, agricultural or liberal</li>\n    <li><strong>Since 15 May 2022</strong>: personal and business assets are automatically separated (entry into force of the new single EI status created by the <a href=\"https://www.legifrance.gouv.fr/loda/id/JORFTEXT000045161732/\" target=\"_blank\" rel=\"noopener\">law of 14 February 2022</a>)</li>\n</ul>",

            '<h3>Micro-entreprise (auto-entrepreneur)</h3>',
            '<p>The micro-entreprise is a simplified version of the sole proprietorship, with turnover ceilings:</p>',
            $this->table(['Type of activity', 'Turnover ceiling (2026)'], [
                ['Sale of goods, accommodation', 'EUR 203,100'],
                ['Provision of services', 'EUR 83,600'],
            ]),

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Worth knowing: the EIRL no longer exists</p>'."\n"
                .'    <p>The law of 14 February 2022 (in force since 15 May 2022) <strong>abolished the option of setting up an EIRL</strong>. The new EI status now includes automatic separation of assets, with no specific formality.</p>'."\n"
                .'</div>',

            '<h2>Conditions and prerequisites</h2>',
            '<h3>Personal conditions</h3>',
            "<ul>\n    <li>Be <strong>of age</strong> (or an emancipated minor)</li>\n    <li>Have an <strong>address in France</strong></li>\n    <li>Not be under guardianship or curatorship</li>\n    <li>Not be subject to a management ban</li>\n    <li>Be a French or European national, or hold a residence permit allowing the activity</li>\n</ul>",

            '<h3>Regulated activities</h3>',
            '<p>Some professions require specific diplomas or qualifications: hairdressing, construction, health professions, and so on.</p>',

            '<h2>Set-up steps via the INPI single window</h2>',
            '<h3>Step 1: Prepare the documents</h3>',
            "<ul>\n    <li>Identity document (ID card or passport) in PDF format</li>\n    <li>Proof of address (if you work from home)</li>\n    <li>Qualification certificates for regulated activities</li>\n</ul>",

            '<h3>Step 2: Create the account</h3>',
            '<p>Go to <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> and create an account via FranceConnect (recommended) or an INPI login.</p>',

            '<h3>Step 3: Declare the activity</h3>',
            "<ol>\n    <li>Click \"Déclarer\"</li>\n    <li>Select \"Entrepreneur individuel\"</li>\n    <li>Fill in: nature of the activity, address, start date, tax and social options</li>\n</ol>",

            '<h3>Step 4: Validation and follow-up</h3>',
            "<ul>\n    <li>Attach the supporting documents</li>\n    <li>Pay if required</li>\n    <li>Track progress from the dashboard</li>\n    <li>Automatic registration with the RNE (national business register)</li>\n</ul>",

            '<h2>Set-up costs</h2>',
            $this->table(['Type of activity', 'Cost'], [
                ['Commercial activity', '<span class="text-green-600 font-semibold">Free</span>'],
                ['Craft activity', '<span class="text-green-600 font-semibold">Free</span>'],
                ['Liberal profession', '<span class="text-green-600 font-semibold">Free</span>'],
                ['Commercial agent', 'EUR 23.86'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Careful</p>'."\n"
                .'    <p>Beware of private websites charging fees for a service that is normally free.</p>'."\n"
                .'</div>',

            '<h2>Typical timescales</h2>',
            $this->table(['Step', 'Time'], [
                ['Online declaration', 'A few minutes'],
                ['Filing receipt', '24 hours'],
                ['Obtaining the SIRET number', '<strong>1 to 2 weeks</strong>'],
                ['URSSAF notification', '4 to 10 weeks'],
            ]),

            '<h2>Obligations once you are set up</h2>',
            '<h3>URSSAF contributions (micro-entreprise)</h3>',
            $this->table(['Type of activity', '2026 rate'], [
                ['Buying and reselling', '12.3%'],
                ['Commercial/craft services', '21.2%'],
                ['Other services', '25.6%'],
                ['Liberal professions (Cipav)', '23.2%'],
            ]),
            '<p><strong>Frequency:</strong> monthly or quarterly declaration (your choice). You must declare even when turnover is nil.</p>',

            '<h3>VAT — franchise en base</h3>',
            $this->table(['Type of activity', 'Base threshold', 'Increased threshold'], [
                ['Sale/trade/accommodation', 'EUR 85,000', 'EUR 93,500'],
                ['Provision of services', 'EUR 37,500', 'EUR 41,250'],
            ]),
            '<p>The two thresholds do not have the same effect:</p>',
            "<ul>\n    <li>Exceeding the <strong>base threshold</strong> in one year makes you liable for VAT <strong>from 1 January of the following year</strong></li>\n    <li>Exceeding the <strong>increased threshold</strong> makes you liable <strong>from the first day of the overrun</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Mandatory mention under the exemption</p>'."\n"
                .'    <p>« TVA non applicable, art. 293 B du CGI »</p>'."\n"
                .'</div>',

            '<h3>CFE (business property contribution)</h3>',
            "<ul>\n    <li><strong>First year:</strong> exempt from payment</li>\n    <li><strong>Full exemption:</strong> if annual turnover is &lt; EUR 5,000</li>\n    <li><strong>Obligation:</strong> file declaration no. 1447-C-SD before 31 December of the first year</li>\n</ul>",

            '<h3>Bookkeeping obligations</h3>',
            "<ol>\n    <li>Issue <strong>compliant invoices</strong> for every sale or service</li>\n    <li>Keep a chronological <strong>revenue book</strong></li>\n    <li>Keep a <strong>purchase register</strong> (if you sell goods)</li>\n    <li><strong>Keep supporting documents</strong> for ten years</li>\n</ol>",

            '<h2>Available support</h2>',
            '<h3>ACRE (support for business founders and buyers)</h3>',
            '<p>ACRE grants a <strong>partial exemption</strong> from social contributions in the first year of activity.</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Rate in force since 1 July 2026</p>'."\n"
                .'    <p>The exemption is <strong>25%</strong>: the micro-entrepreneur pays 75% of the normal contribution rate. An electrician, falling under commercial and craft services, therefore pays <strong>15.9%</strong> instead of 21.2%. Businesses created before 1 July 2026 benefited from a 50% exemption. See <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public – ACRE</a>.</p>'."\n"
                .'</div>',

            "<ul>\n    <li>Eligibility: jobseekers, RSA recipients, 18-25 year-olds, employees buying a business, and others</li>\n    <li>Application to be filed with URSSAF within <strong>60 days</strong> of the start date shown on the proof of business creation</li>\n    <li>URSSAF rules within 30 days; <strong>silence counts as approval</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">To check every year</p>'."\n"
                .'    <p>French thresholds, rates and procedures change often. This page is updated regularly, but for your own situation consult <a href="https://www.urssaf.fr/" target="_blank" rel="noopener">URSSAF</a>, <a href="https://entreprendre.service-public.gouv.fr/" target="_blank" rel="noopener">Service Public</a> or a chartered accountant.</p>'."\n"
                .'</div>',

            '<h2>Official sources</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F37396\" target=\"_blank\" rel=\"noopener\">Service Public – Entrepreneur Individuel</a></li>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F11677\" target=\"_blank\" rel=\"noopener\">Service Public – ACRE</a></li>\n"
                ."    <li><a href=\"https://formalites.entreprises.gouv.fr/\" target=\"_blank\" rel=\"noopener\">Single window for business formalities</a></li>\n"
                ."    <li><a href=\"https://www.autoentrepreneur.urssaf.fr/\" target=\"_blank\" rel=\"noopener\">URSSAF Auto-entrepreneur</a></li>\n"
                ."    <li><a href=\"https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei\" target=\"_blank\" rel=\"noopener\">INPI – Setting up your sole proprietorship</a></li>\n"
                ."    <li><a href=\"https://bpifrance-creation.fr/\" target=\"_blank\" rel=\"noopener\">Bpifrance Création</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">In short</p>'."\n"
                .'    <p>Setting up a micro-entreprise in France is free and quick (SIRET in 1-2 weeks). Social contributions range from 12% to 26% depending on the activity. The VAT exemption lets you invoice without VAT below certain thresholds. Since 1 July 2026, ACRE grants a 25% exemption in the first year.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/sole-proprietorship-luxembourg-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in Luxembourg: complete guide 2026 →</a></li><li><a href="/en/blog/sole-proprietorship-germany-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in Germany: complete guide 2026 →</a></li><li><a href="/en/blog/freelancer-luxembourg-invoice-compliance" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer in Luxembourg: how to invoice in full compliance →</a></li></ul></div>',
        ]);

        $lb = implode("\n\n", [
            '<p class="lead">Frankräich bitt e vereinfachte Kader fir eng Eenzelentreprise ze grënnen, virun allem mam Regime vun der Micro-Entreprise. Zanter 2023 lafen all Formalitéiten iwwer de zentrale Schalter vum INPI. Hei sinn d\'Schrëtt, d\'Käschten an d\'Obligatioune fir 2026 unzefänken.</p>',

            '<h2>D\'Rechtsformen fir eng Eenzelentreprise</h2>',
            '<h3>Entreprise Individuelle (EI)</h3>',
            '<p>D\'Eenzelentreprise erlaabt et, eng Aktivitéit a sengem eegenen Numm auszeüben, ouni eng juristesch Persoun ze grënnen.</p>',
            "<ul>\n    <li>Kee Gesellschaftskapital verlaangt</li>\n    <li>Keng Statuten ze verfaassen</li>\n    <li>Méiglech Aktivitéiten: kommerziell, handwierklech, landwirtschaftlech oder fräiberuflech</li>\n    <li><strong>Zanter dem 15. Mee 2022</strong>: dat perséinlecht a dat beruflecht Verméige gi automatesch getrennt (Akraafttriede vum neien eenheetleche Statut EI aus dem <a href=\"https://www.legifrance.gouv.fr/loda/id/JORFTEXT000045161732/\" target=\"_blank\" rel=\"noopener\">Gesetz vum 14. Februar 2022</a>)</li>\n</ul>",

            '<h3>Micro-Entreprise (Auto-Entrepreneur)</h3>',
            '<p>D\'Micro-Entreprise ass e vereinfachte Regime vun der Eenzelentreprise mat Ëmsazschwellen:</p>',
            $this->table(['Typ vun Aktivitéit', 'Ëmsazschwell (2026)'], [
                ['Verkaf vu Wueren, Hébergement', '203 100 €'],
                ['Servicer', '83 600 €'],
            ]),

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Gutt ze wëssen: d\'EIRL gëtt et net méi</p>'."\n"
                .'    <p>D\'Gesetz vum 14. Februar 2022 (a Kraaft zanter dem 15. Mee 2022) huet d\'<strong>Méiglechkeet ofgeschaaft, eng EIRL ze grënnen</strong>. Den neie Statut EI enthält d\'automatesch Trennung vun de Verméigen, ouni spezifesch Formalitéit.</p>'."\n"
                .'</div>',

            '<h2>Konditiounen a Viraussetzungen</h2>',
            '<h3>Perséinlech Konditiounen</h3>',
            "<ul>\n    <li><strong>Volljäreg</strong> sinn (oder emanzipéierte Mannerjäregen)</li>\n    <li>Eng <strong>Adress a Frankräich</strong> hunn</li>\n    <li>Net ënner Tutelle oder Curatelle stoen</li>\n    <li>Kee Gestiounsverbued hunn</li>\n    <li>Franséischen oder europäesche Staatsbierger sinn, oder en Openthaltstitel hunn, deen d'Aktivitéit erlaabt</li>\n</ul>",

            '<h3>Reglementéiert Aktivitéiten</h3>',
            '<p>Gewësse Beruffer verlaange spezifesch Diplomer oder Qualifikatiounen: Coiffure, Bau, Gesondheetsberuffer asw.</p>',

            '<h2>Grënnungsschrëtt iwwer de zentrale Schalter vum INPI</h2>',
            '<h3>Schrëtt 1: Dokumenter virbereeden</h3>',
            "<ul>\n    <li>Identitéitsdokument (Identitéitskaart oder Pass) am PDF-Format</li>\n    <li>Wunnsëtznowäis (wann d'Aktivitéit doheem ausgeüübt gëtt)</li>\n    <li>Qualifikatiounsattestatioune fir reglementéiert Aktivitéiten</li>\n</ul>",

            '<h3>Schrëtt 2: Kont uleeën</h3>',
            '<p>Op <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> goen an e Kont iwwer FranceConnect (recommandéiert) oder eng INPI-Identifikatioun uleeën.</p>',

            '<h3>Schrëtt 3: Aktivitéit deklaréieren</h3>',
            "<ol>\n    <li>Op „Déclarer\" klicken</li>\n    <li>„Entrepreneur individuel\" auswielen</li>\n    <li>Uginn: Aart vun der Aktivitéit, Adress, Ufanksdatum, fiskal a sozial Optiounen</li>\n</ol>",

            '<h3>Schrëtt 4: Validatioun a Suivi</h3>',
            "<ul>\n    <li>D'Beleeger bäileeën</li>\n    <li>Wann néideg, d'Bezuelung maachen</li>\n    <li>De Fortschrëtt iwwer den Tableau de bord verfollegen</li>\n    <li>Automatesch Aschreiwung am RNE (Registre National des Entreprises)</li>\n</ul>",

            '<h2>Grënnungskäschten</h2>',
            $this->table(['Typ vun Aktivitéit', 'Käschten'], [
                ['Kommerziell Aktivitéit', '<span class="text-green-600 font-semibold">Gratis</span>'],
                ['Handwierklech Aktivitéit', '<span class="text-green-600 font-semibold">Gratis</span>'],
                ['Fräie Beruff', '<span class="text-green-600 font-semibold">Gratis</span>'],
                ['Handelsvertrieder', '23,86 €'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Opgepasst</p>'."\n"
                .'    <p>Hitt Iech viru private Säiten, déi Käschte fir e Service verlaangen, dee normalerweis gratis ass.</p>'."\n"
                .'</div>',

            '<h2>Duerchschnëttlech Delaien</h2>',
            $this->table(['Schrëtt', 'Delai'], [
                ['Online-Deklaratioun', 'E puer Minutten'],
                ['Depotsbescheinegung', '24 Stonnen'],
                ['Kréien vun der SIRET-Nummer', '<strong>1 bis 2 Wochen</strong>'],
                ['URSSAF-Notifikatioun', '4 bis 10 Wochen'],
            ]),

            '<h2>Obligatiounen no der Grënnung</h2>',
            '<h3>URSSAF-Bäiträg (Micro-Entreprise)</h3>',
            $this->table(['Typ vun Aktivitéit', 'Saz 2026'], [
                ['Akaf-Weiderverkaf', '12,3 %'],
                ['Kommerziell/handwierklech Servicer', '21,2 %'],
                ['Aner Servicer', '25,6 %'],
                ['Fräi Beruffer (Cipav)', '23,2 %'],
            ]),
            '<p><strong>Frequenz:</strong> méintlech oder trimestriell Deklaratioun (no Wiel). D\'Deklaratioun ass och bei engem Ëmsaz vun null obligatoresch.</p>',

            '<h3>TVA – Franchise en base</h3>',
            $this->table(['Typ vun Aktivitéit', 'Basisschwell', 'Erhéichte Schwell'], [
                ['Verkaf/Handel/Hébergement', '85 000 €', '93 500 €'],
                ['Servicer', '37 500 €', '41 250 €'],
            ]),
            '<p>Déi zwou Schwelle wierken net d\'selwecht:</p>',
            "<ul>\n    <li>Gëtt d'<strong>Basisschwell</strong> an engem Joer iwwerschratt, gitt Dir <strong>den 1. Januar duerno</strong> TVA-pflichteg</li>\n    <li>Gëtt d'<strong>erhéicht Schwell</strong> iwwerschratt, gitt Dir et <strong>vum éischten Dag vun der Iwwerschreidung un</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtmentioun an der Franchise</p>'."\n"
                .'    <p>« TVA non applicable, art. 293 B du CGI »</p>'."\n"
                .'</div>',

            '<h3>CFE (Cotisation Foncière des Entreprises)</h3>',
            "<ul>\n    <li><strong>1. Joer:</strong> vun der Bezuelung befreit</li>\n    <li><strong>Total Befreiung:</strong> wann de jäerlechen Ëmsaz &lt; 5 000 € ass</li>\n    <li><strong>Obligatioun:</strong> d'Deklaratioun Nr. 1447-C-SD virum 31. Dezember vum 1. Joer aginn</li>\n</ul>",

            '<h3>Comptabel Obligatiounen</h3>',
            "<ol>\n    <li><strong>Konform Rechnungen</strong> fir all Verkaf/Leeschtung erstellen</li>\n    <li>E chronologescht <strong>Akommessbuch</strong> féieren</li>\n    <li>E <strong>Akafsregister</strong> féieren (bei Verkafsaktivitéit)</li>\n    <li>D'<strong>Beleeger 10 Joer opbewaren</strong></li>\n</ol>",

            '<h2>Disponibel Hëllefen</h2>',
            '<h3>ACRE (Hëllef fir Grënner an Iwwerhuelter)</h3>',
            '<p>D\'ACRE erlaabt eng <strong>deelweis Befreiung</strong> vun de Sozialbäiträg am éischte Joer vun der Aktivitéit.</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Saz a Kraaft zanter dem 1. Juli 2026</p>'."\n"
                .'    <p>D\'Befreiung ass <strong>25 %</strong>: de Micro-Entrepreneur bezilt 75 % vum normale Bäitragssaz. En Elektriker, dee bei de kommerziellen an handwierkleche Servicer läit, bezilt also <strong>15,9 %</strong> amplaz 21,2 %. Grënnunge virum 1. Juli 2026 haten eng Befreiung vu 50 %. Kuckt <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public – ACRE</a>.</p>'."\n"
                .'</div>',

            "<ul>\n    <li>Konditiounen: Aarbechtsichender, RSA-Empfänger, Jonker vun 18-25 Joer, Salariéen déi iwwerhuelen asw.</li>\n    <li>Ufro bei der Urssaf bannent <strong>60 Deeg</strong> no dem Ufanksdatum vun der Aktivitéit, dat um Grënnungsnowäis steet</li>\n    <li>D'Urssaf entscheet bannent 30 Deeg; <strong>hire Schweigen gëllt als Zoustëmmung</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">All Joer ze kontrolléieren</p>'."\n"
                .'    <p>Déi franséisch Schwellen, Sätz a Prozedure änneren sech dacks. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot d\'<a href="https://www.urssaf.fr/" target="_blank" rel="noopener">URSSAF</a>, de <a href="https://entreprendre.service-public.gouv.fr/" target="_blank" rel="noopener">Service Public</a> oder en Expert-comptable.</p>'."\n"
                .'</div>',

            '<h2>Offiziell Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F37396\" target=\"_blank\" rel=\"noopener\">Service Public – Entrepreneur Individuel</a></li>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F11677\" target=\"_blank\" rel=\"noopener\">Service Public – ACRE</a></li>\n"
                ."    <li><a href=\"https://formalites.entreprises.gouv.fr/\" target=\"_blank\" rel=\"noopener\">Guichet Unique des Formalités d'Entreprises</a></li>\n"
                ."    <li><a href=\"https://www.autoentrepreneur.urssaf.fr/\" target=\"_blank\" rel=\"noopener\">URSSAF Auto-entrepreneur</a></li>\n"
                ."    <li><a href=\"https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei\" target=\"_blank\" rel=\"noopener\">INPI – Seng Eenzelentreprise grënnen</a></li>\n"
                ."    <li><a href=\"https://bpifrance-creation.fr/\" target=\"_blank\" rel=\"noopener\">Bpifrance Création</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kuerz zesummegefaasst</p>'."\n"
                .'    <p>Eng Micro-Entreprise a Frankräich ze grënnen ass gratis a séier (SIRET an 1-2 Wochen). D\'Sozialbäiträg leien tëscht 12 an 26 % no Aktivitéit. D\'TVA-Franchise erlaabt et, ënner gewësse Schwellen keng TVA ze fakturéieren. D\'ACRE gëtt zanter dem 1. Juli 2026 eng Befreiung vu 25 % am éischte Joer.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/eenzelentreprise-letzebuerg-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise zu Lëtzebuerg grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/eenzelentreprise-deutschland-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise an Däitschland grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/freelancer-letzebuerg-konform-fakturieren" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer zu Lëtzebuerg: konform fakturéieren →</a></li></ul></div>',
        ]);

        $pt = implode("\n\n", [
            '<p class="lead">A França oferece um quadro simplificado para criar a sua empresa individual, sobretudo com o regime da micro-entreprise. Desde 2023, todas as formalidades passam pelo balcão único do INPI. Eis as etapas, os custos e as obrigações para arrancar em 2026.</p>',

            '<h2>As formas jurídicas para empresa individual</h2>',
            '<h3>Entreprise Individuelle (EI)</h3>',
            '<p>A empresa individual permite exercer uma atividade em nome próprio, sem criar uma pessoa coletiva.</p>',
            "<ul>\n    <li>Não é exigido capital social</li>\n    <li>Não há estatutos a redigir</li>\n    <li>Atividades possíveis: comerciais, artesanais, agrícolas ou liberais</li>\n    <li><strong>Desde 15 de maio de 2022</strong>: o património pessoal e o profissional estão automaticamente separados (entrada em vigor do novo estatuto único EI, decorrente da <a href=\"https://www.legifrance.gouv.fr/loda/id/JORFTEXT000045161732/\" target=\"_blank\" rel=\"noopener\">lei de 14 de fevereiro de 2022</a>)</li>\n</ul>",

            '<h3>Micro-entreprise (auto-entrepreneur)</h3>',
            '<p>A micro-entreprise é um regime simplificado da empresa individual, com limiares de volume de negócios:</p>',
            $this->table(['Tipo de atividade', 'Limiar de VN (2026)'], [
                ['Venda de mercadorias, alojamento', '203 100 €'],
                ['Prestações de serviços', '83 600 €'],
            ]),

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A saber: a EIRL já não existe</p>'."\n"
                .'    <p>A lei de 14 de fevereiro de 2022 (em vigor desde 15 de maio de 2022) <strong>suprimiu a possibilidade de criar uma EIRL</strong>. O novo estatuto EI integra a separação automática dos patrimónios, sem formalidade específica.</p>'."\n"
                .'</div>',

            '<h2>Condições e requisitos</h2>',
            '<h3>Condições pessoais</h3>',
            "<ul>\n    <li>Ser <strong>maior de idade</strong> (ou menor emancipado)</li>\n    <li>Ter <strong>morada em França</strong></li>\n    <li>Não estar sob tutela ou curatela</li>\n    <li>Não estar impedido de gerir</li>\n    <li>Ser de nacionalidade francesa ou europeia, ou ter título de residência que autorize o exercício</li>\n</ul>",

            '<h3>Atividades regulamentadas</h3>',
            '<p>Certas profissões exigem diplomas ou qualificações específicas: cabeleireiro, construção, profissões de saúde, etc.</p>',

            '<h2>Etapas de criação através do balcão único do INPI</h2>',
            '<h3>Etapa 1: Preparação dos documentos</h3>',
            "<ul>\n    <li>Documento de identificação (cartão de cidadão ou passaporte) em PDF</li>\n    <li>Comprovativo de morada (se a atividade for exercida em casa)</li>\n    <li>Certificados de qualificação para as atividades regulamentadas</li>\n</ul>",

            '<h3>Etapa 2: Criação da conta</h3>',
            '<p>Aceder a <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> e criar uma conta através do FranceConnect (recomendado) ou de um identificador INPI.</p>',

            '<h3>Etapa 3: Declaração de atividade</h3>',
            "<ol>\n    <li>Clicar em «Déclarer»</li>\n    <li>Selecionar «Entrepreneur individuel»</li>\n    <li>Indicar: natureza da atividade, morada, data de início, opções fiscais e sociais</li>\n</ol>",

            '<h3>Etapa 4: Validação e acompanhamento</h3>',
            "<ul>\n    <li>Juntar os documentos comprovativos</li>\n    <li>Efetuar o pagamento, se necessário</li>\n    <li>Acompanhar o progresso no painel</li>\n    <li>Inscrição automática no RNE (Registre National des Entreprises)</li>\n</ul>",

            '<h2>Custos de criação</h2>',
            $this->table(['Tipo de atividade', 'Custo'], [
                ['Atividade comercial', '<span class="text-green-600 font-semibold">Gratuito</span>'],
                ['Atividade artesanal', '<span class="text-green-600 font-semibold">Gratuito</span>'],
                ['Profissão liberal', '<span class="text-green-600 font-semibold">Gratuito</span>'],
                ['Agente comercial', '23,86 €'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Atenção</p>'."\n"
                .'    <p>Desconfie de sites privados que cobram por um serviço normalmente gratuito.</p>'."\n"
                .'</div>',

            '<h2>Prazos médios</h2>',
            $this->table(['Etapa', 'Prazo'], [
                ['Declaração em linha', 'Alguns minutos'],
                ['Recibo de depósito', '24 horas'],
                ['Obtenção do número SIRET', '<strong>1 a 2 semanas</strong>'],
                ['Notificação URSSAF', '4 a 10 semanas'],
            ]),

            '<h2>Obrigações após a criação</h2>',
            '<h3>Contribuições URSSAF (micro-entreprise)</h3>',
            $this->table(['Tipo de atividade', 'Taxa 2026'], [
                ['Compra e revenda', '12,3 %'],
                ['Serviços comerciais/artesanais', '21,2 %'],
                ['Outros serviços', '25,6 %'],
                ['Profissões liberais (Cipav)', '23,2 %'],
            ]),
            '<p><strong>Periodicidade:</strong> declaração mensal ou trimestral (à escolha). É obrigatório declarar mesmo com volume de negócios nulo.</p>',

            '<h3>IVA – franchise en base</h3>',
            $this->table(['Tipo de atividade', 'Limiar de base', 'Limiar majorado'], [
                ['Venda/comércio/alojamento', '85 000 €', '93 500 €'],
                ['Prestações de serviços', '37 500 €', '41 250 €'],
            ]),
            '<p>Os dois limiares não produzem o mesmo efeito:</p>',
            "<ul>\n    <li>Ultrapassar o <strong>limiar de base</strong> num ano torna-o sujeito a IVA <strong>a 1 de janeiro do ano seguinte</strong></li>\n    <li>Ultrapassar o <strong>limiar majorado</strong> torna-o sujeito <strong>logo no primeiro dia da ultrapassagem</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Menção obrigatória em isenção</p>'."\n"
                .'    <p>« TVA non applicable, art. 293 B du CGI »</p>'."\n"
                .'</div>',

            '<h3>CFE (Cotisation Foncière des Entreprises)</h3>',
            "<ul>\n    <li><strong>1.º ano:</strong> isento de pagamento</li>\n    <li><strong>Isenção total:</strong> se o volume de negócios anual for &lt; 5 000 €</li>\n    <li><strong>Obrigação:</strong> entregar a declaração n.º 1447-C-SD antes de 31 de dezembro do 1.º ano</li>\n</ul>",

            '<h3>Obrigações contabilísticas</h3>',
            "<ol>\n    <li>Emitir <strong>faturas conformes</strong> para cada venda ou prestação</li>\n    <li>Manter um <strong>livro de receitas</strong> cronológico</li>\n    <li>Manter um <strong>registo de compras</strong> (se houver atividade de venda)</li>\n    <li><strong>Conservar os comprovativos</strong> durante 10 anos</li>\n</ol>",

            '<h2>Apoios disponíveis</h2>',
            '<h3>ACRE (apoio a criadores e adquirentes de empresa)</h3>',
            '<p>A ACRE permite uma <strong>isenção parcial</strong> das contribuições sociais no 1.º ano de atividade.</p>',

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Taxa em vigor desde 1 de julho de 2026</p>'."\n"
                .'    <p>A isenção é de <strong>25 %</strong>: o micro-empresário paga 75 % da taxa normal de contribuições. Um eletricista, abrangido pelas prestações de serviços comerciais e artesanais, paga assim <strong>15,9 %</strong> em vez de 21,2 %. As criações anteriores a 1 de julho de 2026 beneficiavam de uma isenção de 50 %. Ver <a href="https://entreprendre.service-public.gouv.fr/vosdroits/F11677" target="_blank" rel="noopener">Service Public – ACRE</a>.</p>'."\n"
                .'</div>',

            "<ul>\n    <li>Condições: desempregados, beneficiários do RSA, jovens dos 18 aos 25 anos, trabalhadores que adquirem uma empresa, etc.</li>\n    <li>Pedido a apresentar à Urssaf nos <strong>60 dias</strong> seguintes à data de início da atividade indicada no comprovativo de criação</li>\n    <li>A Urssaf decide em 30 dias; <strong>o seu silêncio vale como aceitação</strong></li>\n</ul>",

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
                .'    <p>Os limiares, as taxas e os procedimentos franceses mudam com frequência. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte a <a href="https://www.urssaf.fr/" target="_blank" rel="noopener">URSSAF</a>, o <a href="https://entreprendre.service-public.gouv.fr/" target="_blank" rel="noopener">Service Public</a> ou um contabilista certificado.</p>'."\n"
                .'</div>',

            '<h2>Fontes oficiais</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F37396\" target=\"_blank\" rel=\"noopener\">Service Public – Entrepreneur Individuel</a></li>\n"
                ."    <li><a href=\"https://entreprendre.service-public.gouv.fr/vosdroits/F11677\" target=\"_blank\" rel=\"noopener\">Service Public – ACRE</a></li>\n"
                ."    <li><a href=\"https://formalites.entreprises.gouv.fr/\" target=\"_blank\" rel=\"noopener\">Balcão único das formalidades de empresa</a></li>\n"
                ."    <li><a href=\"https://www.autoentrepreneur.urssaf.fr/\" target=\"_blank\" rel=\"noopener\">URSSAF Auto-entrepreneur</a></li>\n"
                ."    <li><a href=\"https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei\" target=\"_blank\" rel=\"noopener\">INPI – Criar a sua empresa individual</a></li>\n"
                ."    <li><a href=\"https://bpifrance-creation.fr/\" target=\"_blank\" rel=\"noopener\">Bpifrance Création</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Em resumo</p>'."\n"
                .'    <p>Criar uma micro-entreprise em França é gratuito e rápido (SIRET em 1-2 semanas). As contribuições sociais variam entre 12 e 26 % consoante a atividade. A isenção de IVA permite não faturar IVA abaixo de certos limiares. Desde 1 de julho de 2026, a ACRE concede uma isenção de 25 % no 1.º ano.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/criar-uma-empresa-individual-no-luxemburgo-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual no Luxemburgo: guia completo 2026 →</a></li><li><a href="/pt/blog/criar-uma-empresa-individual-na-alemanha-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual na Alemanha: guia completo 2026 →</a></li><li><a href="/pt/blog/freelancer-no-luxemburgo-como-faturar-em-total-conformidade" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer no Luxemburgo: como faturar em total conformidade →</a></li></ul></div>',
        ]);

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
