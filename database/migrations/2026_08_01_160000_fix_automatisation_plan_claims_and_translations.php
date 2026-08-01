<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Automatiser sa facturation : 7 conseils ».
 *
 * Cet article est presque entierement compose de revendications produit. Quatre
 * des sept conseils portent sur des fonctionnalites reservees a un plan payant,
 * ce que le texte ne disait nulle part.
 *
 *   - Relances automatiques : `email_reminders` est reserve au plan PRO. C'etait
 *     le conseil le plus mis en avant (« Gain : 1-2 heures par mois »).
 *   - Suivi du temps et projets : `time_tracking` et `projects` sont Essentiel+.
 *   - Portail comptable : l'article affirmait « des le plan gratuit ». Faux.
 *     PlanService : « 'accounting_portal' reserve a Essentiel+ (grandfathering
 *     pour l'existant) ». Seuls les comptes anterieurs le conservent en gratuit.
 *   - Exports Sage BOB / Sage 100 / CSV : `accounting_exports`, Essentiel+.
 *     AccountantExportController leve un 403 explicite sinon.
 *
 * Restent non gates, donc annonces sans reserve : catalogue produits, factures
 * recurrentes, conversion devis en facture et import de clients.
 *
 * Le chapo avancait par ailleurs une moyenne de « 5 a 8 heures par mois »
 * presentee comme mesuree, sans source. Reformulee en ordre de grandeur, comme
 * la statistique de 90 % retiree de l'article comparatif.
 *
 * Le CTA affirmait « tout est inclus » : c'est precisement ce qui est faux.
 *
 * DE, EN, LB : 3 801 a 4 026 caracteres contre 6 264 en francais.
 */
return new class extends Migration
{
    private const KEY = 'automatiser-facturation-7-conseils-gagner-temps';

    private const FR_FIXES = [
        [
            'La facturation prend en moyenne <strong>5 à 8 heures par mois</strong> pour un freelance ou une petite entreprise. Bonne nouvelle : la plupart de ces tâches peuvent être automatisées.',
            'Entre la saisie, les relances et la préparation des documents pour la fiduciaire, la facturation peut facilement occuper <strong>plusieurs heures par mois</strong> chez un freelance ou une petite entreprise. Bonne nouvelle : la plupart de ces tâches se laissent automatiser.',
        ],
        [
            '<p>Avec faktur.lu, les relances sont envoyées automatiquement. Vous pouvez personnaliser les délais et le contenu des emails. <strong>Gain : 1-2 heures par mois.</strong></p>',
            '<p>Avec faktur.lu, les relances sont envoyées automatiquement et vous en personnalisez les délais et le contenu. <strong>Gain : 1-2 heures par mois.</strong></p>'
                ."\n\n"
                .'<p class="text-sm text-slate-500"><em>Les relances automatiques par email font partie du plan Pro.</em></p>',
        ],
        [
            "<p><strong>Gain : 30-45 minutes par mois</strong> et zéro oubli d'heures facturables.</p>",
            "<p><strong>Gain : 30-45 minutes par mois</strong> et zéro oubli d'heures facturables.</p>"
                ."\n\n"
                .'<p class="text-sm text-slate-500"><em>Le suivi du temps et les projets sont disponibles à partir du plan Essentiel.</em></p>',
        ],
        [
            '<p>faktur.lu propose un portail comptable dédié dès le plan gratuit. <strong>Gain : 1-2 heures par mois.</strong></p>',
            '<p>faktur.lu propose un portail comptable dédié à votre fiduciaire. <strong>Gain : 1-2 heures par mois.</strong></p>'
                ."\n\n"
                .'<p class="text-sm text-slate-500"><em>L\'invitation d\'une fiduciaire et les exports Sage BOB, Sage 100 et CSV sont disponibles à partir du plan Essentiel.</em></p>',
        ],
        [
            'Relances automatiques, suivi du temps, conversion devis, export comptable : tout est inclus. Gagnez 5 heures par mois.',
            'Catalogue de prestations, factures récurrentes, conversion des devis et import de vos clients sont inclus dès le plan gratuit. Le suivi du temps et le portail comptable arrivent avec Essentiel, les relances automatiques avec Pro.',
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

    /** @return array<string, string> */
    private function translations(): array
    {
        $de = <<<'HTML'
<p class="lead">Zwischen Erfassung, Mahnungen und der Aufbereitung der Unterlagen fuer den Treuhaender kann die Rechnungsstellung bei Freiberuflern und kleinen Unternehmen leicht <strong>mehrere Stunden im Monat</strong> beanspruchen. Die gute Nachricht: Die meisten dieser Aufgaben lassen sich automatisieren. Hier sind sieben Ansaetze.</p>

<h2>1. Hinterlegen Sie Ihre Produkte und Leistungen</h2>

<p>Statt Beschreibung und Preis bei jeder Rechnung neu zu tippen, <strong>legen Sie einen Katalog</strong> Ihrer Leistungen an:</p>

<ul>
    <li>Bezeichnung der Leistung (z. B. „Consulting – Tagessatz")</li>
    <li>Einzelpreis</li>
    <li>Standard-MwSt-Satz</li>
    <li>Einheit (Stunde, Tag, Pauschale)</li>
</ul>

<p>Beim Erstellen einer Rechnung waehlen Sie die Leistung einfach aus der Liste. <strong>Ersparnis: 2-3 Minuten pro Rechnung.</strong></p>

<h2>2. Automatisieren Sie die Mahnungen</h2>

<p>Pruefen Sie nicht mehr von Hand, welche Rechnungen ueberfaellig sind. Richten Sie <strong>automatische Mahnungen</strong> ein:</p>

<ul>
    <li><strong>T+7</strong>: erste freundliche Erinnerung per E-Mail</li>
    <li><strong>T+15</strong>: foermliche Mahnung</li>
    <li><strong>T+30</strong>: letzte Mahnung vor Inverzugsetzung</li>
</ul>

<p>Mit faktur.lu werden die Mahnungen automatisch versandt, und Sie bestimmen Fristen und Inhalt der E-Mails selbst. <strong>Ersparnis: 1-2 Stunden im Monat.</strong></p>

<p class="text-sm text-slate-500"><em>Automatische E-Mail-Mahnungen gehoeren zum Pro-Tarif.</em></p>

<h2>3. Nutzen Sie die integrierte Zeiterfassung</h2>

<p>Wenn Sie nach Aufwand abrechnen, notieren Sie Ihre Stunden nicht laenger auf Papier oder in Excel:</p>

<ul>
    <li>Starten Sie einen <strong>Timer mit einem Klick</strong>, sobald Sie zu arbeiten beginnen</li>
    <li>Ordnen Sie jeden Eintrag einem <strong>Projekt und einem Kunden</strong> zu</li>
    <li>Am Monatsende <strong>wandeln Sie Ihre Stunden automatisch in eine Rechnung</strong> um</li>
</ul>

<p><strong>Ersparnis: 30-45 Minuten im Monat</strong> – und keine vergessenen abrechenbaren Stunden mehr.</p>

<p class="text-sm text-slate-500"><em>Zeiterfassung und Projekte sind ab dem Essentiel-Tarif verfuegbar.</em></p>

<h2>4. Wandeln Sie Angebote mit einem Klick in Rechnungen um</h2>

<p>Ist ein Angebot angenommen, erstellen Sie die Rechnung nicht neu. Klicken Sie auf <strong>„In Rechnung umwandeln"</strong>, und alle Angaben werden uebernommen:</p>

<ul>
    <li>Kunde, Anschrift, MwSt</li>
    <li>Positionen, Mengen, Preise</li>
    <li>Anmerkungen und Bedingungen</li>
</ul>

<p><strong>Ersparnis: 5-10 Minuten je Umwandlung.</strong></p>

<h2>5. Planen Sie wiederkehrende Rechnungen</h2>

<p>Sie stellen jeden Monat denselben Betrag in Rechnung (Abonnement, Wartungspauschale, Miete)? Legen Sie eine <strong>wiederkehrende Rechnung</strong> an:</p>

<ul>
    <li>Legen Sie den Rhythmus fest (monatlich, vierteljaehrlich, jaehrlich)</li>
    <li>Die Rechnung wird automatisch erzeugt und versandt</li>
    <li>Die fortlaufende Nummer wird automatisch vergeben</li>
</ul>

<p><strong>Ersparnis: vollstaendig.</strong> Sie muessen nicht mehr daran denken.</p>

<h2>6. Uebermitteln Sie Ihre Daten automatisch an den Treuhaender</h2>

<p>Kein Excel-Ordner und kein PDF-Zip mehr fuer Ihre Treuhandgesellschaft:</p>

<ul>
    <li>Geben Sie ihr einen <strong>Buchhaltungszugang</strong> mit Leserechten auf Ihre Software</li>
    <li>Sie sieht in Echtzeit Ihre Rechnungen, Ausgaben und das Einnahmenbuch</li>
    <li>Exportieren Sie mit einem Klick nach <strong>Sage BOB 50, Sage 100 oder CSV</strong></li>
</ul>

<p>faktur.lu bietet ein eigenes Portal fuer Ihre Treuhandgesellschaft. <strong>Ersparnis: 1-2 Stunden im Monat.</strong></p>

<p class="text-sm text-slate-500"><em>Die Einladung einer Treuhandgesellschaft sowie die Exporte nach Sage BOB, Sage 100 und CSV sind ab dem Essentiel-Tarif verfuegbar.</em></p>

<h2>7. Importieren Sie Ihre Kunden als Liste</h2>

<p>Sie haben eine Kundenliste in einer Excel-Datei? Tippen Sie sie nicht einzeln ab:</p>

<ul>
    <li>Exportieren Sie Ihre Datei als CSV oder Excel</li>
    <li>Importieren Sie sie in faktur.lu mit <strong>automatischer Spaltenzuordnung</strong></li>
    <li>Pruefen Sie die Vorschau und bestaetigen Sie</li>
</ul>

<p><strong>Ersparnis: mehrere Stunden</strong>, wenn Sie mehr als 20 Kunden haben.</p>

<h2>Uebersicht der Zeitersparnis</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Automatisierte Aufgabe</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Geschaetzte Ersparnis</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Hinterlegte Produkte</td><td class="border border-gray-300 px-4 py-2">30 Min./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Automatische Mahnungen</td><td class="border border-gray-300 px-4 py-2">1-2 Std./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Zeiterfassung</td><td class="border border-gray-300 px-4 py-2">45 Min./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Angebot → Rechnung</td><td class="border border-gray-300 px-4 py-2">30 Min./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Wiederkehrende Rechnungen</td><td class="border border-gray-300 px-4 py-2">30 Min./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Treuhaender-Portal</td><td class="border border-gray-300 px-4 py-2">1-2 Std./Monat</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Kundenimport</td><td class="border border-gray-300 px-4 py-2">einmalig</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Gesamt</td><td class="border border-gray-300 px-4 py-2 font-bold">~5 Std./Monat</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">Rechnungssoftware waehlen →</a></li><li><a href="/de/blog/excel-vs-rechnungssoftware-warum-wechseln" class="text-primary-500 hover:text-primary-600 text-sm">Excel verlassen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisieren Sie Ihre Rechnungsstellung mit faktur.lu</h3>
    <p class="text-primary-800 mb-4">Leistungskatalog, wiederkehrende Rechnungen, Umwandlung von Angeboten und Kundenimport sind bereits im kostenlosen Tarif enthalten. Zeiterfassung und Treuhaender-Portal kommen mit Essentiel, die automatischen Mahnungen mit Pro.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead">Between data entry, chasing payments and preparing documents for the accountant, invoicing can easily take up <strong>several hours a month</strong> for a freelancer or a small business. The good news: most of those tasks can be automated. Here are seven ways to do it.</p>

<h2>1. Pre-register your products and services</h2>

<p>Rather than retyping the description and price on every invoice, <strong>build a catalogue</strong> of what you sell:</p>

<ul>
    <li>Service name (e.g. "Consulting – day rate")</li>
    <li>Unit price</li>
    <li>Default VAT rate</li>
    <li>Unit (hour, day, fixed fee)</li>
</ul>

<p>When creating an invoice, simply pick the service from the list. <strong>Saving: 2-3 minutes per invoice.</strong></p>

<h2>2. Automate your payment reminders</h2>

<p>Stop checking by hand which invoices are overdue. Set up <strong>automatic reminders</strong>:</p>

<ul>
    <li><strong>D+7</strong>: a first friendly email reminder</li>
    <li><strong>D+15</strong>: a formal reminder</li>
    <li><strong>D+30</strong>: a final reminder before formal notice</li>
</ul>

<p>With faktur.lu the reminders go out automatically, and you set the timing and wording of the emails yourself. <strong>Saving: 1-2 hours a month.</strong></p>

<p class="text-sm text-slate-500"><em>Automatic email reminders are part of the Pro plan.</em></p>

<h2>3. Use the built-in time tracking</h2>

<p>If you bill by the hour, stop noting your time on paper or in Excel:</p>

<ul>
    <li>Start a <strong>timer in one click</strong> when you begin working</li>
    <li>Attach each entry to a <strong>project and a client</strong></li>
    <li>At month end, <strong>turn your hours into an invoice</strong> automatically</li>
</ul>

<p><strong>Saving: 30-45 minutes a month</strong> — and no more forgotten billable hours.</p>

<p class="text-sm text-slate-500"><em>Time tracking and projects are available from the Essentiel plan.</em></p>

<h2>4. Convert quotes into invoices in one click</h2>

<p>When a quote is accepted, do not rebuild the invoice from scratch. Click <strong>"Convert to invoice"</strong> and everything carries over:</p>

<ul>
    <li>Client, address, VAT</li>
    <li>Line items, quantities, prices</li>
    <li>Notes and terms</li>
</ul>

<p><strong>Saving: 5-10 minutes per conversion.</strong></p>

<h2>5. Schedule your recurring invoices</h2>

<p>Do you bill the same amount every month (subscription, maintenance retainer, rent)? Create a <strong>recurring invoice</strong>:</p>

<ul>
    <li>Set the frequency (monthly, quarterly, yearly)</li>
    <li>The invoice is generated and sent automatically</li>
    <li>The sequential number is assigned automatically</li>
</ul>

<p><strong>Saving: total.</strong> You never have to think about it again.</p>

<h2>6. Send your data to your accountant automatically</h2>

<p>No more emailing an Excel workbook or a zip of PDFs to your fiduciaire:</p>

<ul>
    <li>Give them <strong>read-only accounting access</strong> to your software</li>
    <li>They see your invoices, expenses and revenue book in real time</li>
    <li>Export to <strong>Sage BOB 50, Sage 100 or CSV</strong> in one click</li>
</ul>

<p>faktur.lu offers a dedicated portal for your fiduciaire. <strong>Saving: 1-2 hours a month.</strong></p>

<p class="text-sm text-slate-500"><em>Inviting a fiduciaire, and the Sage BOB, Sage 100 and CSV exports, are available from the Essentiel plan.</em></p>

<h2>7. Import your clients in bulk</h2>

<p>Do you have a client list sitting in an Excel file? Do not retype it one by one:</p>

<ul>
    <li>Export your file as CSV or Excel</li>
    <li>Import it into faktur.lu with <strong>automatic column mapping</strong></li>
    <li>Check the preview and confirm</li>
</ul>

<p><strong>Saving: several hours</strong> if you have more than 20 clients.</p>

<h2>Summary of the time saved</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Automated task</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Estimated saving</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Pre-registered products</td><td class="border border-gray-300 px-4 py-2">30 min/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Automatic reminders</td><td class="border border-gray-300 px-4 py-2">1-2 h/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Time tracking</td><td class="border border-gray-300 px-4 py-2">45 min/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Quote → invoice</td><td class="border border-gray-300 px-4 py-2">30 min/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Recurring invoices</td><td class="border border-gray-300 px-4 py-2">30 min/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Accountant portal</td><td class="border border-gray-300 px-4 py-2">1-2 h/month</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client import</td><td class="border border-gray-300 px-4 py-2">one-off</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5 h/month</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">choosing invoicing software →</a></li><li><a href="/en/blog/excel-vs-invoicing-software-why-switch" class="text-primary-500 hover:text-primary-600 text-sm">leaving Excel →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automate your invoicing with faktur.lu</h3>
    <p class="text-primary-800 mb-4">Service catalogue, recurring invoices, quote conversion and client import are included from the free plan. Time tracking and the accountant portal come with Essentiel, automatic reminders with Pro.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">Tëscht der Erfassung, de Mahnungen an der Virbereedung vun den Dokumenter fir de Fiduciaire kann d'Fakturatioun bei engem Freelancer oder engem klengen Betrib liicht <strong>e puer Stonnen am Mount</strong> huelen. Déi gutt Noriicht: déi meescht vun dësen Aufgabe loosse sech automatiséieren. Hei sinn 7 Usätz.</p>

<h2>1. Späichert Är Produkter a Servicer am Viraus</h2>

<p>Amplaz d'Beschreiwung an de Präis bei all Rechnung nei anzetippen, <strong>leet e Katalog</strong> vun Äre Leeschtungen un:</p>

<ul>
    <li>Numm vum Service (z. B. „Consulting – Dagestarif")</li>
    <li>Eenheetspräis</li>
    <li>Standard-TVA-Saz</li>
    <li>Eenheet (Stonn, Dag, Forfait)</li>
</ul>

<p>Beim Erstelle vun enger Rechnung wielt Dir de Service einfach aus der Lëscht. <strong>Gewënn: 2-3 Minutten pro Rechnung.</strong></p>

<h2>2. Automatiséiert d'Mahnunge vun onbezuelte Rechnungen</h2>

<p>Verléiert keng Zäit méi domat, manuell ze kontrolléieren, wéi eng Rechnungen am Réckstand sinn. Konfiguréiert <strong>automatesch Mahnungen</strong>:</p>

<ul>
    <li><strong>D+7</strong>: éischt frëndlech Erënnerung per E-Mail</li>
    <li><strong>D+15</strong>: formell Mahnung</li>
    <li><strong>D+30</strong>: lescht Mahnung virun der Mise en demeure</li>
</ul>

<p>Mat faktur.lu ginn d'Mahnungen automatesch verschéckt, an Dir bestëmmt d'Fristen an den Inhalt vun den E-Maile selwer. <strong>Gewënn: 1-2 Stonnen am Mount.</strong></p>

<p class="text-sm text-slate-500"><em>Automatesch E-Mail-Mahnunge gehéieren zum Pro-Plang.</em></p>

<h2>3. Benotzt den integréierten Zäitsuivi</h2>

<p>Wann Dir no Zäit fakturéiert, hält op, Är Stonnen op Pabeier oder an Excel ze notéieren:</p>

<ul>
    <li>Start en <strong>Timer mat engem Klick</strong>, wann Dir mat der Aarbecht ufänkt</li>
    <li>Uerdnet all Entrée engem <strong>Projet an engem Client</strong> zou</li>
    <li>Um Enn vum Mount <strong>wandelt Dir Är Stonnen automatesch an eng Rechnung</strong> ëm</li>
</ul>

<p><strong>Gewënn: 30-45 Minutten am Mount</strong> – a keng vergiessen fakturéierbar Stonne méi.</p>

<p class="text-sm text-slate-500"><em>De Zäitsuivi an d'Projete si vum Plang Essentiel un disponibel.</em></p>

<h2>4. Wandelt Är Devisen mat engem Klick an Rechnungen ëm</h2>

<p>Wann en Devis ugeholl gëtt, erstellt d'Rechnung net vun Null. Klickt op <strong>„An eng Rechnung ëmwandelen"</strong>, an all Informatioune ginn iwwerholl:</p>

<ul>
    <li>Client, Adress, TVA</li>
    <li>Detailzeilen, Quantitéiten, Präisser</li>
    <li>Bemierkungen a Konditiounen</li>
</ul>

<p><strong>Gewënn: 5-10 Minutten pro Ëmwandlung.</strong></p>

<h2>5. Plangt Är widderhuelend Rechnungen</h2>

<p>Fakturéiert Dir all Mount dee selwechte Montant (Abonnement, Ënnerhaltsforfait, Loyer)? Erstellt eng <strong>widderhuelend Rechnung</strong>:</p>

<ul>
    <li>Definéiert de Rhythmus (méintlech, trimestriell, jäerlech)</li>
    <li>D'Rechnung gëtt automatesch generéiert a verschéckt</li>
    <li>D'sequentiell Nummer gëtt automatesch zougedeelt</li>
</ul>

<p><strong>Gewënn: total.</strong> Dir musst net méi drun denken.</p>

<h2>6. Iwwermëttelt Är Donnéeën automatesch un Ären Comptabel</h2>

<p>Keen Excel-Classeur a kee Zip vu PDFe méi fir Ären Fiduciaire:</p>

<ul>
    <li>Gitt him en <strong>Comptabilitéits-Zougang</strong> nëmme fir ze liesen op Är Software</li>
    <li>Hie gesäit an Echtzäit Är Rechnungen, Ausgaben an d'Akommessbuch</li>
    <li>Exportéiert mat engem Klick op <strong>Sage BOB 50, Sage 100 oder CSV</strong></li>
</ul>

<p>faktur.lu bitt e spezielle Portal fir Ären Fiduciaire. <strong>Gewënn: 1-2 Stonnen am Mount.</strong></p>

<p class="text-sm text-slate-500"><em>D'Invitatioun vun engem Fiduciaire an d'Exporten op Sage BOB, Sage 100 a CSV si vum Plang Essentiel un disponibel.</em></p>

<h2>7. Importéiert Är Clienten a Masse</h2>

<p>Hutt Dir eng Clientelëscht an engem Excel-Fichier? Tippt se net eenzel nei an:</p>

<ul>
    <li>Exportéiert Äre Fichier als CSV oder Excel</li>
    <li>Importéiert en an faktur.lu mat der <strong>automatescher Zouuerdnung vun de Kolonnen</strong></li>
    <li>Kontrolléiert d'Virschau a validéiert</li>
</ul>

<p><strong>Gewënn: e puer Stonnen</strong>, wann Dir méi wéi 20 Clienten hutt.</p>

<h2>Iwwersiicht vun de Gewënner un Zäit</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Automatiséiert Aufgab</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Geschatzte Gewënn</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Virgespäichert Produkter</td><td class="border border-gray-300 px-4 py-2">30 Min./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Automatesch Mahnungen</td><td class="border border-gray-300 px-4 py-2">1-2 Std./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Zäitsuivi</td><td class="border border-gray-300 px-4 py-2">45 Min./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Devis → Rechnung</td><td class="border border-gray-300 px-4 py-2">30 Min./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Widderhuelend Rechnungen</td><td class="border border-gray-300 px-4 py-2">30 Min./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Comptabilitéits-Portal</td><td class="border border-gray-300 px-4 py-2">1-2 Std./Mount</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client-Import</td><td class="border border-gray-300 px-4 py-2">punktuell</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5 Std./Mount</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Fakturatiounssoftware wielen →</a></li><li><a href="/lb/blog/excel-vs-rechnungssoftware-firwat-wiesselen" class="text-primary-500 hover:text-primary-600 text-sm">Excel verloossen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatiséiert Är Fakturatioun mat faktur.lu</h3>
    <p class="text-primary-800 mb-4">Leeschtungskatalog, widderhuelend Rechnungen, Ëmwandlung vun Devisen an Import vun Äre Clienten si scho vum gratis Plang un abegraff. De Zäitsuivi an de Comptabilitéits-Portal kommen mat Essentiel, déi automatesch Mahnunge mat Pro.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">Entre o lançamento, as cobranças e a preparação dos documentos para o contabilista, a faturação pode facilmente ocupar <strong>várias horas por mês</strong> a um freelancer ou a uma pequena empresa. A boa notícia: a maior parte destas tarefas pode ser automatizada. Eis 7 conselhos para lá chegar.</p>

<h2>1. Pré-registe os seus produtos e serviços</h2>

<p>Em vez de reescrever a descrição e o preço em cada fatura, <strong>crie um catálogo</strong> das suas prestações:</p>

<ul>
    <li>Nome do serviço (ex.: «Consultoria – valor diário»)</li>
    <li>Preço unitário</li>
    <li>Taxa de IVA por omissão</li>
    <li>Unidade (hora, dia, avença)</li>
</ul>

<p>Ao criar uma fatura, basta escolher o serviço na lista. <strong>Ganho: 2-3 minutos por fatura.</strong></p>

<h2>2. Automatize as cobranças de faturas em atraso</h2>

<p>Deixe de verificar manualmente que faturas estão vencidas. Configure <strong>cobranças automáticas</strong>:</p>

<ul>
    <li><strong>D+7</strong>: primeiro lembrete amigável por email</li>
    <li><strong>D+15</strong>: cobrança formal</li>
    <li><strong>D+30</strong>: última cobrança antes da interpelação</li>
</ul>

<p>Com o faktur.lu as cobranças são enviadas automaticamente, e é você que define os prazos e o conteúdo dos emails. <strong>Ganho: 1-2 horas por mês.</strong></p>

<p class="text-sm text-slate-500"><em>As cobranças automáticas por email fazem parte do plano Pro.</em></p>

<h2>3. Use o registo de tempo integrado</h2>

<p>Se fatura ao tempo, deixe de apontar as horas em papel ou no Excel:</p>

<ul>
    <li>Inicie um <strong>cronómetro num clique</strong> quando começa a trabalhar</li>
    <li>Associe cada entrada a um <strong>projeto e a um cliente</strong></li>
    <li>No fim do mês, <strong>converta as suas horas em fatura</strong> automaticamente</li>
</ul>

<p><strong>Ganho: 30-45 minutos por mês</strong> e nenhuma hora faturável esquecida.</p>

<p class="text-sm text-slate-500"><em>O registo de tempo e os projetos estão disponíveis a partir do plano Essentiel.</em></p>

<h2>4. Converta os seus orçamentos em faturas num clique</h2>

<p>Quando um orçamento é aceite, não recrie a fatura do zero. Clique em <strong>«Converter em fatura»</strong> e toda a informação é retomada:</p>

<ul>
    <li>Cliente, morada, IVA</li>
    <li>Linhas de detalhe, quantidades, preços</li>
    <li>Notas e condições</li>
</ul>

<p><strong>Ganho: 5-10 minutos por conversão.</strong></p>

<h2>5. Programe as suas faturas recorrentes</h2>

<p>Fatura o mesmo montante todos os meses (subscrição, avença de manutenção, renda)? Crie uma <strong>fatura recorrente</strong>:</p>

<ul>
    <li>Defina a frequência (mensal, trimestral, anual)</li>
    <li>A fatura é gerada e enviada automaticamente</li>
    <li>O número sequencial é atribuído automaticamente</li>
</ul>

<p><strong>Ganho: total.</strong> Deixa de ter de pensar nisso.</p>

<h2>6. Envie os seus dados ao contabilista automaticamente</h2>

<p>Deixa de precisar de enviar um ficheiro Excel ou um zip de PDF ao seu <em>fiduciaire</em>:</p>

<ul>
    <li>Dê-lhe um <strong>acesso de contabilidade</strong> apenas de leitura ao seu software</li>
    <li>Ele acede em tempo real às suas faturas, despesas e livro de receitas</li>
    <li>Exporte para <strong>Sage BOB 50, Sage 100 ou CSV</strong> num clique</li>
</ul>

<p>O faktur.lu oferece um portal dedicado ao seu <em>fiduciaire</em>. <strong>Ganho: 1-2 horas por mês.</strong></p>

<p class="text-sm text-slate-500"><em>O convite a um <em>fiduciaire</em> e as exportações para Sage BOB, Sage 100 e CSV estão disponíveis a partir do plano Essentiel.</em></p>

<h2>7. Importe os seus clientes em massa</h2>

<p>Tem uma lista de clientes num ficheiro Excel? Não os volte a introduzir um a um:</p>

<ul>
    <li>Exporte o seu ficheiro em CSV ou Excel</li>
    <li>Importe-o no faktur.lu com o <strong>mapeamento automático das colunas</strong></li>
    <li>Verifique a pré-visualização e confirme</li>
</ul>

<p><strong>Ganho: várias horas</strong> se tiver mais de 20 clientes.</p>

<h2>Resumo dos ganhos de tempo</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Tarefa automatizada</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Ganho estimado</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Produtos pré-registados</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Cobranças automáticas</td><td class="border border-gray-300 px-4 py-2">1-2 h/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Registo de tempo</td><td class="border border-gray-300 px-4 py-2">45 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Orçamento → fatura</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Faturas recorrentes</td><td class="border border-gray-300 px-4 py-2">30 min/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Portal do contabilista</td><td class="border border-gray-300 px-4 py-2">1-2 h/mês</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Importação de clientes</td><td class="border border-gray-300 px-4 py-2">pontual</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5 h/mês</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/como-escolher-o-seu-software-de-faturacao-no-luxemburgo" class="text-primary-500 hover:text-primary-600 text-sm">escolher o software →</a></li><li><a href="/pt/blog/excel-vs-software-de-faturacao-porque-fazer-a-mudanca" class="text-primary-500 hover:text-primary-600 text-sm">sair do Excel →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatize a sua faturação com o faktur.lu</h3>
    <p class="text-primary-800 mb-4">Catálogo de prestações, faturas recorrentes, conversão de orçamentos e importação de clientes estão incluídos desde o plano gratuito. O registo de tempo e o portal do contabilista chegam com o Essentiel, as cobranças automáticas com o Pro.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
