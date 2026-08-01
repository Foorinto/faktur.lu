<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Guide « Créer une entreprise individuelle au Luxembourg ».
 *
 * Vérifications faites contre les sources officielles :
 *   - autorisation tacite au bout de 3 mois : confirmée mot pour mot par
 *     guichet.public.lu (« L'absence de réponse avant la fin de cette période
 *     de 3 mois vaut autorisation tacite ») ;
 *   - seuil de franchise 50 000 EUR et tolérance de 10 % : confirmés par la
 *     FAQ SME de l'AED ;
 *   - référence article 57bis : confirmée par cette même FAQ, qui prescrit la
 *     mention exacte « TVA non applicable – Article 57bis de la loi modifiée
 *     du 12 février 1979 ».
 *
 * Deux corrections.
 *
 * 1. La tolérance était énoncée sans sa conséquence. La FAQ de l'AED précise
 *    que le dépassement du seuil, même contenu dans les 10 %, fait perdre la
 *    franchise l'année civile suivante, et qu'au-delà de 55 000 EUR elle cesse
 *    dès le jour suivant le dépassement. L'article laissait croire à une
 *    simple zone tampon. C'est le défaut récurrent de ce blog : la règle
 *    énoncée sans sa condition d'application.
 *
 * 2. Arithmétique des cotisations. L'article annonçait « environ 24,2 % » puis
 *    listait 16,00 + 5,60 + 0,25 + 1,40 = 23,25 %, la mutualité étant citée
 *    sans taux. L'article dédié aux cotisations CCSS, lui, retient « environ
 *    25 % » comme ordre de grandeur. Les deux se contredisaient et aucun des
 *    deux ne tombait juste. Le total affiché est désormais cohérent avec sa
 *    propre ventilation et avec l'article dédié.
 *
 * DE, EN, LB, PT : 9 477 à 9 964 caractères contre 12 845, avec cinq à six
 * liens contre dix-huit.
 */
return new class extends Migration
{
    private const KEY = 'creer-entreprise-individuelle-luxembourg-guide-2025';

    private const FR_FIXES = [
        [
            '        <tr><td class="p-2 border-b">CA annuel HT ≤ 50 000 € (avec tolérance 10 % jusqu\'à 55 000 €)</td><td class="p-2 border-b">Franchise de TVA (article 57bis LIVA) - pas d\'inscription obligatoire</td></tr>'
                ."\n"
                .'        <tr><td class="p-2 border-b">CA annuel HT &gt; 55 000 €</td><td class="p-2 border-b">Inscription TVA obligatoire + déclarations périodiques</td></tr>',
            '        <tr><td class="p-2 border-b">CA annuel HT ≤ 50 000 €</td><td class="p-2 border-b">Franchise de TVA (article 57bis LIVA) - pas d\'inscription obligatoire</td></tr>'
                ."\n"
                .'        <tr><td class="p-2 border-b">Dépassement dans la limite de 10 % (jusqu\'à 55 000 €)</td><td class="p-2 border-b">Franchise maintenue jusqu\'à la fin de l\'année civile, <strong>mais perdue l\'année suivante</strong></td></tr>'
                ."\n"
                .'        <tr><td class="p-2 border-b">Dépassement au-delà de 55 000 €</td><td class="p-2 border-b">La franchise cesse <strong>dès le jour suivant le dépassement</strong> - inscription TVA et déclarations périodiques</td></tr>',
        ],
        [
            "<p>Pour un indépendant au Luxembourg, les cotisations sociales totales représentent environ <strong>24,2 %</strong> du revenu professionnel, réparties comme suit :</p>",
            "<p>Pour un indépendant au Luxembourg, les cotisations obligatoires listées ci-dessous représentent environ <strong>23 %</strong> du revenu professionnel. En y ajoutant la mutualité des employeurs, dont le taux dépend de la classe de risque, l'ordre de grandeur retenu est d'<strong>environ 25 %</strong> :</p>",
        ],
        [
            "    <li>Inscription à la <strong>TVA (AED)</strong> obligatoire si CA &gt; 50 000 € HT (sinon : franchise possible, voir ci-dessous)</li>",
            "    <li>Inscription à la <strong>TVA (AED)</strong> obligatoire dès que vous dépassez le seuil de franchise de 50 000 € HT (voir les règles de dépassement ci-dessous)</li>",
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
     * Tableau à deux colonnes, structure identique dans toutes les langues.
     *
     * @param  array<int, array{0:string,1:string}>  $rows
     */
    private function table(string $h1, string $h2, array $rows): string
    {
        $body = '';

        foreach ($rows as [$left, $right]) {
            $body .= '        <tr><td class="p-2 border-b">'.$left.'</td><td class="p-2 border-b">'.$right."</td></tr>\n";
        }

        return <<<HTML
<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">{$h1}</th>
            <th class="text-left p-2 bg-slate-100">{$h2}</th>
        </tr>
    </thead>
    <tbody>
{$body}    </tbody>
</table>
HTML;
    }

    /** @return array<string, string> */
    private function translations(): array
    {
        // ---------------------------------------------------------------- DE
        $de = implode("\n\n", [
            '<p class="lead">Luxemburg bietet Unternehmern ein guenstiges Umfeld: die Verwaltungsschritte sind vergleichsweise einfach und die Gruendungskosten moderat (rund 70-120 EUR). Dieser Leitfaden begleitet Sie 2026 Schritt fuer Schritt bei der Gruendung Ihres Einzelunternehmens im Grossherzogtum.</p>',

            '<h2>Rechtsformen fuer ein Einzelunternehmen</h2>',
            '<p>In Luxemburg uebt der selbststaendige Unternehmer seinen Beruf im eigenen Namen aus, als:</p>',
            "<ul>\n    <li><strong>Kaufmann</strong>: fuer gewerbliche Taetigkeiten</li>\n    <li><strong>Handwerker</strong>: fuer handwerkliche Taetigkeiten</li>\n    <li><strong>Selbststaendig Geistig Taetiger</strong>: fuer die freien Berufe</li>\n</ul>",

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Zu beachten</p>'."\n"
                .'    <p>Ein genaues Gegenstueck zum franzoesischen Status des „auto-entrepreneur“ gibt es in Luxemburg nicht. Das Einzelunternehmen ist die naechstliegende und einfachste Form, ohne eigene Rechtspersoenlichkeit neben dem Unternehmer.</p>'."\n"
                .'</div>',

            '<h3>Wesentliche Merkmale</h3>',
            $this->table('Aspekt', 'Detail', [
                ['Rechtspersoenlichkeit', 'Keine – der Unternehmer handelt im eigenen Namen'],
                ['Mindestkapital', 'Kein Mindestkapital erforderlich'],
                ['Haftung', '<strong>Unbeschraenkt</strong> – auch mit dem Privatvermoegen'],
                ['Formalitaeten', 'Minimal – kein Gesellschaftsvertrag noetig'],
            ]),

            '<h2>Voraussetzungen</h2>',
            '<h3>Niederlassungsgenehmigung (verpflichtend)</h3>',
            '<p>Jede gewerbsmaessig ausgeuebte wirtschaftliche Taetigkeit setzt eine <a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener"><strong>vorherige Niederlassungsgenehmigung</strong></a> voraus.</p>',
            '<p><strong>Zu erfuellende Bedingungen:</strong></p>',
            "<ul>\n    <li><strong>Physische Niederlassung</strong>: angemessene materielle Einrichtung in Luxemburg</li>\n    <li><strong>Tatsaechliche Leitung</strong>: persoenliche Anwesenheit und taegliche Fuehrung durch den Inhaber</li>\n    <li><strong>Berufliche Zuverlaessigkeit</strong>: einwandfreies Fuehrungszeugnis, Erfuellung frueherer steuerlicher und sozialer Pflichten</li>\n    <li><strong>Berufliche Qualifikation</strong>: je nach angestrebter Taetigkeit</li>\n</ul>",

            '<h3>Erforderliche berufliche Qualifikationen</h3>',
            $this->table('Art der Taetigkeit', 'Erforderliche Qualifikation', [
                ['Gewerbliche Taetigkeiten', 'In der Regel kein besonderer Abschluss erforderlich'],
                ['Handwerkliche Taetigkeiten', 'DAP, CATP oder Meisterbrief'],
                ['Freie Berufe', 'Fachspezifische Abschluesse je nach Beruf (Gesundheit, Recht, Buchhaltung …)'],
            ]),

            '<h2>Die Gruendungsschritte im Einzelnen</h2>',
            '<h3>Schritt 1: Das Vorhaben ausarbeiten</h3>',
            "<ul>\n    <li>Einen Businessplan erstellen</li>\n    <li>Beratungsstellen kontaktieren: <a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a>, Handelskammer, Handwerkskammer</li>\n</ul>",

            '<h3>Schritt 2: Voraussetzungen pruefen</h3>',
            "<ul>\n    <li>Die Verfuegbarkeit des Handelsnamens pruefen</li>\n    <li>Sicherstellen, dass Sie die erforderlichen Qualifikationen besitzen</li>\n    <li>Bei Bedarf die Anerkennung auslaendischer Abschluesse beantragen</li>\n</ul>",

            '<h3>Schritt 3: Antrag auf Niederlassungsgenehmigung</h3>',
            '<p><strong>Wo:</strong> online ueber <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> (mit LuxTrust-Zertifikat) oder per Post</p>',
            '<p><strong>Erforderliche Unterlagen:</strong></p>',
            "<ul>\n    <li>Antragsformular</li>\n    <li>Nachweise der beruflichen Qualifikation</li>\n    <li>Auszug aus dem Strafregister (Bulletin Nr. 3)</li>\n    <li>Kopie des Personalausweises</li>\n    <li>Zahlungsnachweis der Kanzleigebuehr (<strong>50 EUR</strong>)</li>\n</ul>",

            '<h3>Schritt 4: Eintragung ins RCS</h3>',
            '<p><strong>Wo:</strong> elektronische Hinterlegung auf der Website der <a href="https://www.lbr.lu/" target="_blank" rel="noopener">LBR (Luxembourg Business Registers)</a></p>',
            '<p><strong>Erforderliche Unterlagen:</strong></p>',
            "<ul>\n    <li>Antragsformular</li>\n    <li>Niederlassungsgenehmigung</li>\n    <li>Ausweisdokument</li>\n    <li>Heiratsurkunde / Ehevertrag (falls zutreffend)</li>\n</ul>",

            '<h3>Schritt 5: Anmeldung bei der Sozialversicherung</h3>',
            '<p>Anmeldung als Selbststaendiger beim <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS (Centre Commun de la Sécurité Sociale)</a>. Innerhalb von acht Tagen nach Aufnahme der Taetigkeit vorzunehmen.</p>',

            '<h3>Schritt 6: Steuerliche Anmeldung</h3>',
            "<ul>\n    <li>Anmeldung bei der <strong>Administration des Contributions Directes (ACD)</strong> fuer die Einkommensteuer</li>\n    <li>Anmeldung zur <strong>MwSt (AED)</strong>, sobald Sie die Kleinunternehmerschwelle von 50 000 EUR netto ueberschreiten (siehe die Regeln zur Ueberschreitung weiter unten)</li>\n</ul>",

            '<h2>Gruendungskosten</h2>',
            $this->table('Posten', 'Richtwert', [
                ['Niederlassungsgenehmigung (Kanzleigebuehr)', '50 EUR'],
                ['RCS-Eintragung (elektronische Hinterlegung LBR)', '~19 EUR (+20 EUR mit Unterstuetzung im LBR-Buero)'],
                ['Anerkennung eines Abschlusses (falls noetig)', 'Je nach Art der Anerkennung unterschiedlich'],
                ['<strong>Geschaetzte Summe</strong>', '<strong>~70-120 EUR</strong>'],
            ]),

            '<h2>Uebliche Fristen</h2>',
            $this->table('Schritt', 'Frist', [
                ['Niederlassungsgenehmigung', 'Bis zu 3 Monate (danach gilt die Genehmigung als stillschweigend erteilt)'],
                ['Anerkennung eines Abschlusses', '2 bis 6 Wochen'],
                ['RCS-Eintragung', 'Wenige Tage'],
                ['<strong>Geschaetzte Gesamtdauer</strong>', '<strong>1 bis 3 Monate</strong>'],
            ]),

            '<h2>Pflichten nach der Gruendung</h2>',
            '<h3>MwSt</h3>',
            $this->table('Situation', 'Pflicht', [
                ['Jahresumsatz netto ≤ 50 000 EUR', 'MwSt-Befreiung (Artikel 57bis LIVA) – keine Anmeldepflicht'],
                ['Ueberschreitung innerhalb von 10 % (bis 55 000 EUR)', 'Befreiung bleibt bis zum Jahresende bestehen, <strong>entfaellt aber im Folgejahr</strong>'],
                ['Ueberschreitung ueber 55 000 EUR hinaus', 'Die Befreiung endet <strong>am Tag nach der Ueberschreitung</strong> – MwSt-Anmeldung und regelmaessige Erklaerungen'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtangabe bei Befreiung (Artikel 57bis LIVA)</p>'."\n"
                .'    <p>« TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 »</p>'."\n"
                .'</div>',

            '<p>Hinweis: Die Befreiungsschwelle wurde zum 1. Januar 2025 von 35 000 EUR auf <strong>50 000 EUR</strong> angehoben (Umsetzung der EU-Richtlinie 2020/285). Mehr dazu in unserem <a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime">vollstaendigen Leitfaden zur MwSt-Befreiung</a>.</p>',

            '<h3>Sozialbeitraege (CCSS)</h3>',
            '<p>Die nachstehend aufgefuehrten Pflichtbeitraege eines Selbststaendigen in Luxemburg machen rund <strong>23 %</strong> des Berufseinkommens aus. Rechnet man die Mutualité des employeurs hinzu, deren Satz von der Risikoklasse abhaengt, liegt die Groessenordnung bei <strong>rund 25 %</strong>:</p>',
            "<ul>\n    <li>Rentenversicherung: <strong>16,00 %</strong> (8 % Versichertenanteil + 8 % „Arbeitgeberanteil“, vom Selbststaendigen getragen)</li>\n    <li>Krankenversicherung (Sachleistungen): 5,60 %</li>\n    <li>Krankenversicherung (Geldleistungen): 0,25 % (Satz fuer Selbststaendige)</li>\n    <li>Pflegeversicherung: 1,40 %</li>\n    <li>Mutualité des employeurs (Lohnfortzahlung im Krankheitsfall): je nach Risikoklasse</li>\n</ul>",
            '<p>Quelle: <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS – Selbststaendige</a>.</p>',

            '<h3>Buchfuehrung</h3>',
            $this->table('Jahresumsatz', 'Pflicht', [
                ['&lt; 100 000 EUR netto', 'Vereinfachte Buchfuehrung (Einnahmenbuch)'],
                ['≥ 100 000 EUR netto', 'Regelbuchfuehrung (PCN)'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Jaehrlich zu pruefen</p>'."\n"
                .'    <p>Schwellen, Beitragssaetze und Verfahren koennen sich aendern. Diese Seite wird regelmaessig aktualisiert; fuer Ihre persoenliche Situation wenden Sie sich an Ihren Treuhaender oder direkt an die <a href="https://ccss.public.lu/" target="_blank" rel="noopener">CCSS</a> und die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>'."\n"
                .'</div>',

            '<h2>Offizielle Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Einzelunternehmen (Selbststaendiger)</a></li>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Niederlassungsgenehmigung</a></li>\n"
                ."    <li><a href=\"https://lbr.lu/\" target=\"_blank\" rel=\"noopener\">Luxembourg Business Registers (LBR)</a></li>\n"
                ."    <li><a href=\"https://ccss.public.lu/fr/independants.html\" target=\"_blank\" rel=\"noopener\">CCSS – Selbststaendige</a></li>\n"
                ."    <li><a href=\"https://pfi.public.lu/fr/professionnel/tva/sme.html\" target=\"_blank\" rel=\"noopener\">AED – Befreiungsregelung (Artikel 57bis)</a></li>\n"
                ."    <li><a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 4. Juni 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kurz gefasst</p>'."\n"
                .'    <p>Die Gruendung eines Einzelunternehmens in Luxemburg ist vergleichsweise einfach und guenstig (rund 70-120 EUR). Der Vorgang dauert 1 bis 3 Monate und umfasst die Niederlassungsgenehmigung sowie die Eintragung ins RCS. Die Sozialbeitraege liegen bei rund 25 % des Einkommens, und die MwSt-Befreiung gilt bis 50 000 EUR Jahresumsatz netto.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/einzelunternehmen-frankreich-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Frankreich gruenden: Leitfaden 2026 →</a></li><li><a href="/de/blog/einzelunternehmen-belgien-gruenden-leitfaden-2026" class="text-primary-500 hover:text-primary-600 text-sm">Einzelunternehmen in Belgien gruenden: Leitfaden 2026 →</a></li><li><a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">MwSt-Befreiung Luxemburg: Schwelle 50 000 EUR →</a></li></ul></div>',
        ]);

        // ---------------------------------------------------------------- EN
        $en = implode("\n\n", [
            '<p class="lead">Luxembourg offers entrepreneurs a favourable environment, with relatively simple formalities and moderate set-up costs (around EUR 70-120). This guide walks you step by step through creating your sole proprietorship in the Grand Duchy in 2026.</p>',

            '<h2>Legal forms for a sole proprietorship</h2>',
            '<p>In Luxembourg, the self-employed entrepreneur works in their own name, as a:</p>',
            "<ul>\n    <li><strong>Trader</strong>: for commercial activities</li>\n    <li><strong>Craftsperson</strong>: for craft activities</li>\n    <li><strong>Self-employed intellectual worker</strong>: for the liberal professions</li>\n</ul>",

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Worth noting</p>'."\n"
                .'    <p>There is no exact equivalent of the French "auto-entrepreneur" status in Luxembourg. The sole proprietorship is the closest and simplest form, with no legal personality separate from the entrepreneur.</p>'."\n"
                .'</div>',

            '<h3>Key characteristics</h3>',
            $this->table('Aspect', 'Detail', [
                ['Legal personality', 'None — the entrepreneur acts in their own name'],
                ['Minimum capital', 'No minimum capital required'],
                ['Liability', '<strong>Unlimited</strong> — personal assets are exposed'],
                ['Formalities', 'Minimal — no articles of association to draft'],
            ]),

            '<h2>Conditions and prerequisites</h2>',
            '<h3>Business permit (mandatory)</h3>',
            '<p>Any economic activity carried on as a regular occupation requires a <a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener"><strong>prior business permit</strong></a>.</p>',
            '<p><strong>Conditions to meet:</strong></p>',
            "<ul>\n    <li><strong>Physical establishment</strong>: suitable material premises in Luxembourg</li>\n    <li><strong>Effective management</strong>: physical presence and day-to-day management by the holder</li>\n    <li><strong>Professional integrity</strong>: clean criminal record, past tax and social obligations met</li>\n    <li><strong>Professional qualification</strong>: depending on the activity</li>\n</ul>",

            '<h3>Professional qualifications required</h3>',
            $this->table('Type of activity', 'Qualification required', [
                ['Commercial activities', 'Generally no specific diploma required'],
                ['Craft activities', 'DAP, CATP or Master Craftsman certificate'],
                ['Liberal professions', 'Profession-specific diplomas (health, law, accountancy…)'],
            ]),

            '<h2>The creation steps in detail</h2>',
            '<h3>Step 1: Shape the project</h3>',
            "<ul>\n    <li>Write a business plan</li>\n    <li>Contact the support bodies: <a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a>, Chamber of Commerce, Chamber of Skilled Trades</li>\n</ul>",

            '<h3>Step 2: Check the prerequisites</h3>',
            "<ul>\n    <li>Check that your trade name is available</li>\n    <li>Make sure you hold the required qualifications</li>\n    <li>Apply for recognition of foreign diplomas if needed</li>\n</ul>",

            '<h3>Step 3: Apply for the business permit</h3>',
            '<p><strong>Where:</strong> online via <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> (with a LuxTrust certificate) or by post</p>',
            '<p><strong>Documents required:</strong></p>',
            "<ul>\n    <li>Application form</li>\n    <li>Evidence of professional qualification</li>\n    <li>Criminal record extract (bulletin no. 3)</li>\n    <li>Copy of your identity card</li>\n    <li>Proof of payment of the chancery fee (<strong>EUR 50</strong>)</li>\n</ul>",

            '<h3>Step 4: Register with the RCS</h3>',
            '<p><strong>Where:</strong> electronic filing on the <a href="https://www.lbr.lu/" target="_blank" rel="noopener">LBR (Luxembourg Business Registers)</a> website</p>',
            '<p><strong>Documents required:</strong></p>',
            "<ul>\n    <li>Requisition form</li>\n    <li>Business permit</li>\n    <li>Identity document</li>\n    <li>Marriage certificate / marriage contract (if applicable)</li>\n</ul>",

            '<h3>Step 5: Register with social security</h3>',
            '<p>Register as self-employed with the <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS (Centre Commun de la Sécurité Sociale)</a>. To be done within eight days of starting your activity.</p>',

            '<h3>Step 6: Tax registration</h3>',
            "<ul>\n    <li>Register with the <strong>Administration des Contributions Directes (ACD)</strong> for income tax</li>\n    <li>Register for <strong>VAT (AED)</strong> as soon as you exceed the EUR 50,000 exemption threshold (see the rules on exceeding it below)</li>\n</ul>",

            '<h2>Set-up costs</h2>',
            $this->table('Item', 'Indicative amount', [
                ['Business permit (chancery fee)', 'EUR 50'],
                ['RCS registration (electronic filing with LBR)', '~EUR 19 (+EUR 20 with LBR desk assistance)'],
                ['Diploma recognition (if needed)', 'Varies with the type of recognition'],
                ['<strong>Estimated total</strong>', '<strong>~EUR 70-120</strong>'],
            ]),

            '<h2>Typical timescales</h2>',
            $this->table('Formality', 'Time', [
                ['Business permit', 'Up to 3 months (tacit approval once that period has passed)'],
                ['Diploma recognition', '2 to 6 weeks'],
                ['RCS registration', 'A few days'],
                ['<strong>Estimated total time</strong>', '<strong>1 to 3 months</strong>'],
            ]),

            '<h2>Obligations once you are set up</h2>',
            '<h3>VAT</h3>',
            $this->table('Situation', 'Obligation', [
                ['Annual turnover excl. VAT ≤ EUR 50,000', 'VAT exemption (article 57bis LIVA) — no registration required'],
                ['Exceeded by up to 10% (up to EUR 55,000)', 'Exemption kept until the end of the calendar year, <strong>but lost the following year</strong>'],
                ['Exceeded beyond EUR 55,000', 'The exemption ends <strong>the day after the threshold is passed</strong> — VAT registration and periodic returns'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Mandatory mention under the exemption (article 57bis LIVA)</p>'."\n"
                .'    <p>« TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 »</p>'."\n"
                .'</div>',

            '<p>Note: the exemption threshold rose from EUR 35,000 to <strong>EUR 50,000</strong> on 1 January 2025 (transposing EU directive 2020/285). For more detail, see our <a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime">complete guide to the VAT exemption</a>.</p>',

            '<h3>Social contributions (CCSS)</h3>',
            '<p>For a self-employed person in Luxembourg, the mandatory contributions listed below come to roughly <strong>23%</strong> of professional income. Adding the employers\' mutual insurance, whose rate depends on the risk class, the working figure is <strong>around 25%</strong>:</p>',
            "<ul>\n    <li>Pension insurance: <strong>16.00%</strong> (8% insured share + 8% \"employer\" share, borne by the self-employed person)</li>\n    <li>Health insurance (care in kind): 5.60%</li>\n    <li>Health insurance (cash benefits): 0.25% (self-employed rate)</li>\n    <li>Long-term care insurance: 1.40%</li>\n    <li>Employers' mutual insurance (continued sick pay): depends on the risk class</li>\n</ul>",
            '<p>Reference: <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS – Self-employed</a>.</p>',

            '<h3>Bookkeeping</h3>',
            $this->table('Annual turnover', 'Obligation', [
                ['&lt; EUR 100,000 excl. VAT', 'Simplified bookkeeping (revenue book)'],
                ['≥ EUR 100,000 excl. VAT', 'Standard bookkeeping (PCN)'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">To check every year</p>'."\n"
                .'    <p>Thresholds, contribution rates and procedures can change. This page is updated regularly, but for your own situation consult your fiduciaire or the <a href="https://ccss.public.lu/" target="_blank" rel="noopener">CCSS</a> and the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>'."\n"
                .'</div>',

            '<h2>Official sources</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Sole proprietorship (self-employed)</a></li>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Business permit</a></li>\n"
                ."    <li><a href=\"https://lbr.lu/\" target=\"_blank\" rel=\"noopener\">Luxembourg Business Registers (LBR)</a></li>\n"
                ."    <li><a href=\"https://ccss.public.lu/fr/independants.html\" target=\"_blank\" rel=\"noopener\">CCSS – Self-employed</a></li>\n"
                ."    <li><a href=\"https://pfi.public.lu/fr/professionnel/tva/sme.html\" target=\"_blank\" rel=\"noopener\">AED – Exemption scheme (article 57bis)</a></li>\n"
                ."    <li><a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Article updated on 4 June 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">In short</p>'."\n"
                .'    <p>Setting up a sole proprietorship in Luxembourg is relatively simple and inexpensive (around EUR 70-120). The process takes 1 to 3 months and involves obtaining the business permit and registering with the RCS. Social contributions come to around 25% of income, and the VAT exemption applies up to EUR 50,000 of annual turnover excl. VAT.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/sole-proprietorship-france-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in France: complete guide 2026 →</a></li><li><a href="/en/blog/sole-proprietorship-belgium-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Setting up a sole proprietorship in Belgium: complete guide 2026 →</a></li><li><a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption: EUR 50,000 threshold →</a></li></ul></div>',
        ]);

        // ---------------------------------------------------------------- LB
        $lb = implode("\n\n", [
            '<p class="lead">Lëtzebuerg bitt den Entrepreneuren e gënschtegt Ëmfeld: d\'administrativ Schrëtt si relativ einfach an d\'Grënnungskäschte moderat (ronn 70-120 €). Dëse Guide begleet Iech 2026 Schrëtt fir Schrëtt bei der Grënnung vun Ärer Eenzelentreprise am Grand-Duché.</p>',

            '<h2>D\'Rechtsformen fir eng Eenzelentreprise</h2>',
            '<p>Zu Lëtzebuerg iwwt den onofhängegen Entrepreneur säi Beruff a sengem eegenen Numm aus, als:</p>',
            "<ul>\n    <li><strong>Händler</strong>: fir kommerziell Aktivitéiten</li>\n    <li><strong>Handwierker</strong>: fir handwierklech Aktivitéiten</li>\n    <li><strong>Onofhängege geeschtege Schaffenden</strong>: fir déi fräi Beruffer</li>\n</ul>",

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Ze bemierken</p>'."\n"
                .'    <p>Et gëtt zu Lëtzebuerg keen exakten Äquivalent zum franséische Statut vum „auto-entrepreneur". D\'Eenzelentreprise ass déi nootste an einfachst Form, ouni eege Rechtsperséinlechkeet nieft dem Entrepreneur.</p>'."\n"
                .'</div>',

            '<h3>Haaptcharakteristiken</h3>',
            $this->table('Aspekt', 'Detail', [
                ['Rechtsperséinlechkeet', 'Keng – den Entrepreneur handelt a sengem eegenen Numm'],
                ['Mindestkapital', 'Kee Mindestkapital verlaangt'],
                ['Verantwortung', '<strong>Onbegrenzt</strong> – och mat de perséinleche Verméigen'],
                ['Formalismus', 'Minimal – keng Statuten ze verfaassen'],
            ]),

            '<h2>Konditiounen a Viraussetzungen</h2>',
            '<h3>Etablissementserlaabnis (obligatoresch)</h3>',
            '<p>All wirtschaftlech Aktivitéit, déi gewinnt ausgeüübt gëtt, verlaangt eng <a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener"><strong>virgängeg Etablissementserlaabnis</strong></a>.</p>',
            '<p><strong>Konditiounen, déi z\'erfëllen sinn:</strong></p>',
            "<ul>\n    <li><strong>Physescht Etablissement</strong>: ugepasste materiell Installatioun zu Lëtzebuerg</li>\n    <li><strong>Effektiv Gestioun</strong>: physesch Präsenz an deeglech Gestioun duerch den Inhaber</li>\n    <li><strong>Beruflech Éierbarkeet</strong>: propert Strofregister, Respekt vun de fréiere fiskalen a sozialen Obligatiounen</li>\n    <li><strong>Beruflech Qualifikatioun</strong>: no der viséierter Aktivitéit</li>\n</ul>",

            '<h3>Verlaangte beruflech Qualifikatiounen</h3>',
            $this->table('Typ vun Aktivitéit', 'Verlaangte Qualifikatioun', [
                ['Kommerziell Aktivitéiten', 'Allgemeng kee spezifeschen Diplom verlaangt'],
                ['Handwierklech Aktivitéiten', 'DAP, CATP oder Meeschterbréif'],
                ['Fräi Beruffer', 'Spezifesch Diplomer no Beruff (Gesondheet, Recht, Comptabilitéit …)'],
            ]),

            '<h2>Detailléiert Grënnungsschrëtt</h2>',
            '<h3>Schrëtt 1: Ausschaffe vum Projet</h3>',
            "<ul>\n    <li>E Businessplang verfaassen</li>\n    <li>D'Begleedungsorganismen kontaktéieren: <a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a>, Handelskummer, Handwierkskummer</li>\n</ul>",

            '<h3>Schrëtt 2: Iwwerpréiwe vun de Viraussetzungen</h3>',
            "<ul>\n    <li>D'Disponibilitéit vum Handelsnumm iwwerpréiwen</li>\n    <li>Sécherstellen, datt Dir déi verlaangte Qualifikatiounen hutt</li>\n    <li>Wann néideg, d'Unerkennung vun auslännesche Diplomer ufroen</li>\n</ul>",

            '<h3>Schrëtt 3: Ufro vun der Etablissementserlaabnis</h3>',
            '<p><strong>Wou:</strong> online iwwer <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> (mat LuxTrust-Zertifikat) oder per Post</p>',
            '<p><strong>Verlaangten Dokumenter:</strong></p>',
            "<ul>\n    <li>Ufroformulaire</li>\n    <li>Beleeger vun der beruflecher Qualifikatioun</li>\n    <li>Auszuch aus dem Strofregister (Bulletin Nr. 3)</li>\n    <li>Kopie vun der Identitéitskaart</li>\n    <li>Bezuelungsbeleeg vum Chancellerie-Droit (<strong>50 €</strong>)</li>\n</ul>",

            '<h3>Schrëtt 4: Aschreiwung am RCS</h3>',
            '<p><strong>Wou:</strong> elektronesch Hannerleeung op der Säit vun der <a href="https://www.lbr.lu/" target="_blank" rel="noopener">LBR (Luxembourg Business Registers)</a></p>',
            '<p><strong>Verlaangten Dokumenter:</strong></p>',
            "<ul>\n    <li>Requisitiounsformulaire</li>\n    <li>Etablissementserlaabnis</li>\n    <li>Identitéitsdokument</li>\n    <li>Bestietnesakt / Éiekontrakt (wann applicabel)</li>\n</ul>",

            '<h3>Schrëtt 5: Affiliatioun un d\'Sozialversécherung</h3>',
            '<p>Aschreiwung beim <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS (Centre Commun de la Sécurité Sociale)</a> als Onofhängegen. Bannent aacht Deeg no der Ophuele vun der Aktivitéit ze maachen.</p>',

            '<h3>Schrëtt 6: Fiskalesch Aschreiwung</h3>',
            "<ul>\n    <li>Aschreiwung bei der <strong>Administration des Contributions Directes (ACD)</strong> fir d'Akommessteier</li>\n    <li>Aschreiwung bei der <strong>TVA (AED)</strong>, soubal Dir d'Franchise-Schwell vu 50 000 € HT iwwerschreit (kuckt d'Reegele fir d'Iwwerschreidung méi ënnen)</li>\n</ul>",

            '<h2>Grënnungskäschten</h2>',
            $this->table('Posten', 'Indikative Montant', [
                ['Etablissementserlaabnis (Chancellerie-Droit)', '50 €'],
                ['RCS-Aschreiwung (elektronesch Hannerleeung LBR)', '~19 € (+20 € mat Hëllef um LBR-Büro)'],
                ['Unerkennung vun engem Diplom (wann néideg)', 'Variabel no Typ vun der Unerkennung'],
                ['<strong>Geschate Gesamtsumme</strong>', '<strong>~70-120 €</strong>'],
            ]),

            '<h2>Duerchschnëttlech Delaien</h2>',
            $this->table('Schrëtt', 'Delai', [
                ['Etablissementserlaabnis', 'Bis zu 3 Méint (no dësem Delai gëllt se als stëllschweigend accordéiert)'],
                ['Unerkennung vun engem Diplom', '2 bis 6 Wochen'],
                ['RCS-Aschreiwung', 'E puer Deeg'],
                ['<strong>Geschaten Gesamtdelai</strong>', '<strong>1 bis 3 Méint</strong>'],
            ]),

            '<h2>Obligatiounen no der Grënnung</h2>',
            '<h3>TVA</h3>',
            $this->table('Situatioun', 'Obligatioun', [
                ['Jäerlechen Ëmsaz HT ≤ 50 000 €', 'TVA-Franchise (Artikel 57bis LIVA) – keng obligatoresch Aschreiwung'],
                ['Iwwerschreidung bannent 10 % (bis 55 000 €)', 'Franchise bleift bis zum Enn vum Kalennerjoer, <strong>mä geet d\'Joer duerno verluer</strong>'],
                ['Iwwerschreidung iwwer 55 000 € eraus', 'D\'Franchise hält <strong>vum Dag no der Iwwerschreidung un</strong> op – TVA-Aschreiwung a periodesch Deklaratiounen'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Pflichtmentioun an der Franchise (Artikel 57bis LIVA)</p>'."\n"
                .'    <p>« TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 »</p>'."\n"
                .'</div>',

            '<p>Notiz: D\'Franchise-Schwell ass den 1. Januar 2025 vu 35 000 € op <strong>50 000 €</strong> eropgesat ginn (Ëmsetzung vun der EU-Richtlinn 2020/285). Fir méi Detailer kuckt eise <a href="/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime">kompletten Guide zur TVA-Franchise</a>.</p>',

            '<h3>Sozialbäiträg (CCSS)</h3>',
            '<p>Fir en Onofhängegen zu Lëtzebuerg maachen déi hei ënnendrënner opgezielte Pflichtbäiträg ronn <strong>23 %</strong> vum beruflechen Akommes aus. Rechent een d\'Mutualité des employeurs derbäi, deem säi Saz vun der Risikoklass ofhänkt, läit d\'Gréisstenuerdnung bei <strong>ronn 25 %</strong>:</p>',
            "<ul>\n    <li>Pensiounsversécherung: <strong>16,00 %</strong> (8 % Deel vum Versécherten + 8 % „Patrons\"-Deel, vum Onofhängege gedroen)</li>\n    <li>Krankeversécherung (Gesondheetsleeschtungen): 5,60 %</li>\n    <li>Krankeversécherung (Geldleeschtungen): 0,25 % (Saz fir Onofhängeger)</li>\n    <li>Ofhängegkeetsversécherung: 1,40 %</li>\n    <li>Mutualité des employeurs (weiderbezuelte Krankheet): no Risikoklass</li>\n</ul>",
            '<p>Referenz: <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS – Onofhängeger</a>.</p>',

            '<h3>Comptabilitéit</h3>',
            $this->table('Jäerlechen Ëmsaz', 'Obligatioun', [
                ['&lt; 100 000 € HT', 'Vereinfacht Comptabilitéit (Akommessbuch)'],
                ['≥ 100 000 € HT', 'Normaliséiert Comptabilitéit (PCN)'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">All Joer ze kontrolléieren</p>'."\n"
                .'    <p>D\'Schwellen, d\'Bäitragssätz an d\'Prozedure kënne sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun frot Ären Fiduciaire oder direkt de <a href="https://ccss.public.lu/" target="_blank" rel="noopener">CCSS</a> an d\'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>'."\n"
                .'</div>',

            '<h2>Offiziell Quellen</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Eenzelentreprise (Onofhängegen)</a></li>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Etablissementserlaabnis</a></li>\n"
                ."    <li><a href=\"https://lbr.lu/\" target=\"_blank\" rel=\"noopener\">Luxembourg Business Registers (LBR)</a></li>\n"
                ."    <li><a href=\"https://ccss.public.lu/fr/independants.html\" target=\"_blank\" rel=\"noopener\">CCSS – Onofhängeger</a></li>\n"
                ."    <li><a href=\"https://pfi.public.lu/fr/professionnel/tva/sme.html\" target=\"_blank\" rel=\"noopener\">AED – Franchise-Regime (Artikel 57bis)</a></li>\n"
                ."    <li><a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artikel den 4. Juni 2026 aktualiséiert.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Kuerz zesummegefaasst</p>'."\n"
                .'    <p>D\'Grënnung vun enger Eenzelentreprise zu Lëtzebuerg ass relativ einfach a bëlleg (ronn 70-120 €). De Prozess dauert 1 bis 3 Méint an ëmfaasst d\'Etablissementserlaabnis an d\'Aschreiwung am RCS. D\'Sozialbäiträg maachen ronn 25 % vum Akommes aus, an d\'TVA-Franchise gëllt bis 50 000 € jäerlechen Ëmsaz HT.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/eenzelentreprise-frankreich-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise a Frankräich grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/eenzelentreprise-belgien-grenden-guide-2026" class="text-primary-500 hover:text-primary-600 text-sm">Eng Eenzelentreprise a Belsch grënnen: Guide 2026 →</a></li><li><a href="/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg: Schwell 50 000 € →</a></li></ul></div>',
        ]);

        // ---------------------------------------------------------------- PT
        $pt = implode("\n\n", [
            '<p class="lead">O Luxemburgo oferece um ambiente favorável aos empreendedores, com formalidades administrativas relativamente simples e custos de criação moderados (cerca de 70-120 €). Este guia acompanha-o passo a passo na criação da sua empresa individual no Grão-Ducado em 2026.</p>',

            '<h2>As formas jurídicas para empresa individual</h2>',
            '<p>No Luxemburgo, o empresário independente exerce a sua profissão em nome próprio, na qualidade de:</p>',
            "<ul>\n    <li><strong>Comerciante</strong>: para as atividades comerciais</li>\n    <li><strong>Artesão</strong>: para as atividades artesanais</li>\n    <li><strong>Trabalhador intelectual independente</strong>: para as profissões liberais</li>\n</ul>",

            '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A ter em conta</p>'."\n"
                .'    <p>Não existe no Luxemburgo um equivalente exato ao estatuto francês de «auto-entrepreneur». A empresa individual é a forma mais próxima e mais simples, sem personalidade jurídica distinta da do empresário.</p>'."\n"
                .'</div>',

            '<h3>Características principais</h3>',
            $this->table('Aspeto', 'Detalhe', [
                ['Personalidade jurídica', 'Nenhuma — o empresário age em nome próprio'],
                ['Capital mínimo', 'Não é exigido capital mínimo'],
                ['Responsabilidade', '<strong>Ilimitada</strong> — responde com os bens pessoais'],
                ['Formalismo', 'Mínimo — sem estatutos a redigir'],
            ]),

            '<h2>Condições e requisitos</h2>',
            '<h3>Autorização de estabelecimento (obrigatória)</h3>',
            '<p>Qualquer atividade económica exercida de forma habitual exige uma <a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener"><strong>autorização de estabelecimento prévia</strong></a>.</p>',
            '<p><strong>Condições a preencher:</strong></p>',
            "<ul>\n    <li><strong>Estabelecimento físico</strong>: instalação material adequada no Luxemburgo</li>\n    <li><strong>Gestão efetiva</strong>: presença física e gestão diária pelo titular</li>\n    <li><strong>Idoneidade profissional</strong>: registo criminal limpo, cumprimento das obrigações fiscais e sociais anteriores</li>\n    <li><strong>Qualificação profissional</strong>: consoante a atividade pretendida</li>\n</ul>",

            '<h3>Qualificações profissionais exigidas</h3>',
            $this->table('Tipo de atividade', 'Qualificação exigida', [
                ['Atividades comerciais', 'Em geral, não é exigido diploma específico'],
                ['Atividades artesanais', 'DAP, CATP ou Carta de Mestre'],
                ['Profissões liberais', 'Diplomas específicos consoante a profissão (saúde, direito, contabilidade…)'],
            ]),

            '<h2>Etapas de criação detalhadas</h2>',
            '<h3>Etapa 1: Elaboração do projeto</h3>',
            "<ul>\n    <li>Redigir um plano de negócios</li>\n    <li>Contactar os organismos de apoio: <a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a>, Câmara de Comércio, Câmara dos Ofícios</li>\n</ul>",

            '<h3>Etapa 2: Verificação dos requisitos</h3>',
            "<ul>\n    <li>Verificar a disponibilidade do nome comercial</li>\n    <li>Assegurar que possui as qualificações exigidas</li>\n    <li>Pedir o reconhecimento de diplomas estrangeiros, se necessário</li>\n</ul>",

            '<h3>Etapa 3: Pedido de autorização de estabelecimento</h3>',
            '<p><strong>Onde:</strong> em linha através do <a href="https://www.myguichet.lu/" target="_blank" rel="noopener">MyGuichet.lu</a> (com certificado LuxTrust) ou por correio</p>',
            '<p><strong>Documentos exigidos:</strong></p>',
            "<ul>\n    <li>Formulário de pedido</li>\n    <li>Comprovativos de qualificação profissional</li>\n    <li>Certificado de registo criminal (boletim n.º 3)</li>\n    <li>Cópia do documento de identificação</li>\n    <li>Comprovativo de pagamento do emolumento de chancelaria (<strong>50 €</strong>)</li>\n</ul>",

            '<h3>Etapa 4: Inscrição no RCS</h3>',
            '<p><strong>Onde:</strong> depósito eletrónico no sítio da <a href="https://www.lbr.lu/" target="_blank" rel="noopener">LBR (Luxembourg Business Registers)</a></p>',
            '<p><strong>Documentos exigidos:</strong></p>',
            "<ul>\n    <li>Formulário de requisição</li>\n    <li>Autorização de estabelecimento</li>\n    <li>Documento de identificação</li>\n    <li>Certidão de casamento / contrato de casamento (se aplicável)</li>\n</ul>",

            '<h3>Etapa 5: Inscrição na segurança social</h3>',
            '<p>Inscrição no <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS (Centre Commun de la Sécurité Sociale)</a> como independente. A efetuar nos 8 dias seguintes ao início da atividade.</p>',

            '<h3>Etapa 6: Inscrição fiscal</h3>',
            "<ul>\n    <li>Inscrição junto da <strong>Administration des Contributions Directes (ACD)</strong> para o imposto sobre o rendimento</li>\n    <li>Inscrição no <strong>IVA (AED)</strong> logo que ultrapasse o limiar de isenção de 50 000 € sem IVA (ver as regras de ultrapassagem abaixo)</li>\n</ul>",

            '<h2>Custos de criação</h2>',
            $this->table('Rubrica', 'Montante indicativo', [
                ['Autorização de estabelecimento (emolumento de chancelaria)', '50 €'],
                ['Inscrição no RCS (depósito eletrónico LBR)', '~19 € (+20 € com assistência no balcão da LBR)'],
                ['Reconhecimento de diploma (se necessário)', 'Variável consoante o tipo de reconhecimento'],
                ['<strong>Total estimado</strong>', '<strong>~70-120 €</strong>'],
            ]),

            '<h2>Prazos médios</h2>',
            $this->table('Diligência', 'Prazo', [
                ['Autorização de estabelecimento', 'Até 3 meses (findo esse prazo, vale como autorização tácita)'],
                ['Reconhecimento de diploma', '2 a 6 semanas'],
                ['Inscrição no RCS', 'Alguns dias'],
                ['<strong>Prazo total estimado</strong>', '<strong>1 a 3 meses</strong>'],
            ]),

            '<h2>Obrigações após a criação</h2>',
            '<h3>IVA</h3>',
            $this->table('Situação', 'Obrigação', [
                ['Volume de negócios anual sem IVA ≤ 50 000 €', 'Isenção de IVA (artigo 57bis LIVA) — sem inscrição obrigatória'],
                ['Ultrapassagem até 10 % (até 55 000 €)', 'Isenção mantida até ao fim do ano civil, <strong>mas perdida no ano seguinte</strong>'],
                ['Ultrapassagem acima de 55 000 €', 'A isenção cessa <strong>no dia seguinte à ultrapassagem</strong> — inscrição no IVA e declarações periódicas'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Menção obrigatória em isenção (artigo 57bis LIVA)</p>'."\n"
                .'    <p>« TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 »</p>'."\n"
                .'</div>',

            '<p>Nota: o limiar de isenção passou de 35 000 € para <strong>50 000 €</strong> em 1 de janeiro de 2025 (transposição da diretiva UE 2020/285). Para mais detalhes, veja o nosso <a href="/pt/blog/isencao-de-iva-no-luxemburgo-limiar-obrigacoes-e-passagem-ao-regime-normal">guia completo da isenção de IVA</a>.</p>',

            '<h3>Contribuições sociais (CCSS)</h3>',
            '<p>Para um independente no Luxemburgo, as contribuições obrigatórias listadas abaixo representam cerca de <strong>23 %</strong> do rendimento profissional. Somando a mutualidade dos empregadores, cuja taxa depende da classe de risco, a ordem de grandeza é de <strong>cerca de 25 %</strong>:</p>',
            "<ul>\n    <li>Seguro de pensão: <strong>16,00 %</strong> (8 % parte do segurado + 8 % parte «empregador», a cargo do independente)</li>\n    <li>Seguro de doença (cuidados de saúde): 5,60 %</li>\n    <li>Seguro de doença (prestações pecuniárias): 0,25 % (taxa dos independentes)</li>\n    <li>Seguro de dependência: 1,40 %</li>\n    <li>Mutualidade dos empregadores (continuação da remuneração na doença): consoante a classe de risco</li>\n</ul>",
            '<p>Referência: <a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS – Independentes</a>.</p>',

            '<h3>Contabilidade</h3>',
            $this->table('Volume de negócios anual', 'Obrigação', [
                ['&lt; 100 000 € sem IVA', 'Contabilidade simplificada (livro de receitas)'],
                ['≥ 100 000 € sem IVA', 'Contabilidade normalizada (PCN)'],
            ]),

            '<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">A verificar todos os anos</p>'."\n"
                .'    <p>Os limiares, as taxas de contribuição e os procedimentos podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal consulte o seu <em>fiduciaire</em> ou diretamente o <a href="https://ccss.public.lu/" target="_blank" rel="noopener">CCSS</a> e a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>'."\n"
                .'</div>',

            '<h2>Fontes oficiais</h2>',
            "<ul>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle-societe-personnes.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Empresa individual (independente)</a></li>\n"
                ."    <li><a href=\"https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html\" target=\"_blank\" rel=\"noopener\">Guichet.lu – Autorização de estabelecimento</a></li>\n"
                ."    <li><a href=\"https://lbr.lu/\" target=\"_blank\" rel=\"noopener\">Luxembourg Business Registers (LBR)</a></li>\n"
                ."    <li><a href=\"https://ccss.public.lu/fr/independants.html\" target=\"_blank\" rel=\"noopener\">CCSS – Independentes</a></li>\n"
                ."    <li><a href=\"https://pfi.public.lu/fr/professionnel/tva/sme.html\" target=\"_blank\" rel=\"noopener\">AED – Regime de isenção (artigo 57bis)</a></li>\n"
                ."    <li><a href=\"https://www.houseofentrepreneurship.lu/\" target=\"_blank\" rel=\"noopener\">House of Entrepreneurship</a></li>\n"
                .'</ul>',

            '<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 4 de junho de 2026.</em></p>',

            '<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">'."\n"
                .'    <p class="font-semibold">Em resumo</p>'."\n"
                .'    <p>Criar uma empresa individual no Luxemburgo é relativamente simples e pouco dispendioso (cerca de 70-120 €). O processo demora 1 a 3 meses e inclui a obtenção da autorização de estabelecimento e a inscrição no RCS. As contribuições sociais representam cerca de 25 % do rendimento, e a isenção de IVA aplica-se até 50 000 € de volume de negócios anual sem IVA.</p>'."\n"
                .'</div>',

            '<div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/criar-uma-empresa-individual-em-franca-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual em França: guia completo 2026 →</a></li><li><a href="/pt/blog/criar-uma-empresa-individual-na-belgica-guia-completo-2026" class="text-primary-500 hover:text-primary-600 text-sm">Criar uma empresa individual na Bélgica: guia completo 2026 →</a></li><li><a href="/pt/blog/isencao-de-iva-no-luxemburgo-limiar-obrigacoes-e-passagem-ao-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo: limiar de 50 000 € →</a></li></ul></div>',
        ]);

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
