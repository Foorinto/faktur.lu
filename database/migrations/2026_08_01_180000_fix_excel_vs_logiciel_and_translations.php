<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Excel vs logiciel de facturation : pourquoi changer ».
 *
 * Article solide sur le fond : la fourchette « 0 à 15 EUR/mois », le plan
 * Essentiel a 5 EUR et le quota de 5 factures par mois du plan gratuit sont
 * exacts (plans.price_monthly = 0 / 500 / 1500 centimes, limits
 * max_invoices_per_month = 5). Le multi-devises annonce dans le tableau existe
 * aussi : config/billing.php declare EUR, USD, GBP et CHF, et la devise se
 * definit par client puis se propage a la facture.
 *
 * Deux corrections :
 *
 * 1. Le RCS etait cite parmi les « mentions legales requises » sans sa
 *    condition. L'obligation vient de la legislation sur le registre de
 *    commerce, pas de la loi TVA — l'article « mentions obligatoires », deja
 *    corrige, le precise. Les deux se contredisaient.
 *
 * 2. Le tableau comparatif oppose Excel a « Logiciel » en general, mais
 *    l'article se conclut sur un CTA faktur.lu : le lecteur rapporte donc les
 *    lignes au produit. Or les relances automatiques relevent du plan Pro et
 *    le livre des recettes du plan Essentiel. Une note sous le tableau le dit.
 *
 * DE, EN, LB : 5 406 a 5 553 caracteres contre 7 305 en francais, avec deux
 * liens contre quatre et un H3 manquant.
 */
return new class extends Migration
{
    private const KEY = 'excel-vs-logiciel-facturation-pourquoi-switch';

    private const FR_FIXES = [
        [
            '    <li>Les <strong>mentions légales</strong> requises (TVA, RCS, matricule)</li>',
            "    <li>Les <strong>mentions légales</strong> requises : numéro de TVA et matricule, plus le numéro RCS si vous êtes commerçant ou société inscrite - cette dernière obligation venant du registre de commerce, non de la loi TVA</li>",
        ],
        [
            "</table>\n\n<h2>Comment migrer d'Excel à un logiciel</h2>",
            "</table>\n\n"
                .'<p class="text-sm text-slate-500"><em>Chez faktur.lu, les relances automatiques font partie du plan Pro et le livre des recettes du plan Essentiel. Les autres lignes de ce tableau sont incluses dès le plan gratuit.</em></p>'
                ."\n\n<h2>Comment migrer d'Excel à un logiciel</h2>",
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

    /** Tableau comparatif, identique partout hors libelles. */
    private function table(string $feature, string $head, array $rows): string
    {
        $body = '';

        foreach ($rows as $label) {
            $body .= '        <tr><td class="border border-gray-300 px-4 py-2">'.$label.'</td>'
                .'<td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td>'
                .'<td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>'."\n";
        }

        return <<<HTML
<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">{$feature}</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Excel</th>
            <th class="border border-gray-300 px-4 py-2 text-center">{$head}</th>
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
        $deTable = $this->table('Funktion', 'Software', [
            'Automatische Nummerierung', 'Automatische MwSt-Berechnung', 'Konforme Pflichtangaben',
            'FAIA-Export', 'Integrierter E-Mail-Versand', 'Zahlungsverfolgung',
            'Automatische Mahnungen', 'Automatisches Einnahmenbuch', 'Automatische Sicherung', 'Mehrwaehrungsfaehig',
        ]);

        $enTable = $this->table('Feature', 'Software', [
            'Automatic numbering', 'Automatic VAT calculation', 'Compliant legal mentions',
            'FAIA export', 'Built-in email sending', 'Payment tracking',
            'Automatic reminders', 'Automatic revenue book', 'Automatic backup', 'Multi-currency',
        ]);

        $lbTable = $this->table('Fonctioun', 'Software', [
            'Automatesch Nummeréierung', 'Automatesch TVA-Berechnung', 'Konform Pflichtmentiounen',
            'FAIA-Export', 'Integréierten E-Mail-Versand', 'Suivi vun de Bezuelungen',
            'Automatesch Mahnungen', 'Automatescht Akommessbuch', 'Automatesch Sécherung', 'Multi-Devisen',
        ]);

        $ptTable = $this->table('Funcionalidade', 'Software', [
            'Numeração automática', 'Cálculo automático do IVA', 'Menções legais conformes',
            'Exportação FAIA', 'Envio de email integrado', 'Acompanhamento dos pagamentos',
            'Cobranças automáticas', 'Livro de receitas automático', 'Cópia de segurança automática', 'Multimoeda',
        ]);

        $de = <<<HTML
<p class="lead">Sie schreiben Ihre Rechnungen noch in Excel? Damit sind Sie nicht allein: <strong>viele Freiberufler und Kleinstunternehmen</strong> in Luxemburg fakturieren weiterhin mit einer Tabellenkalkulation. Diese Praxis birgt allerdings ernste Risiken.</p>

<h2>Die Grenzen von Excel bei der Rechnungsstellung</h2>

<h3>1. Keine garantierte Konformitaet</h3>

<p>Excel prueft nicht, ob Ihre Rechnung den luxemburgischen Vorgaben genuegt. Sie riskieren, Folgendes zu vergessen:</p>

<ul>
    <li>Die zwingende <strong>fortlaufende Nummerierung</strong> (ohne Luecke und ohne Doppelung)</li>
    <li>Die erforderlichen <strong>Pflichtangaben</strong>: MwSt-Nummer und Matrikel, dazu die RCS-Nummer, sofern Sie als Kaufmann oder Gesellschaft eingetragen sind – Letzteres folgt aus dem Handelsregisterrecht, nicht aus dem MwSt-Gesetz</li>
    <li>Den richtigen <strong>MwSt-Hinweis</strong> je nach Fall (innergemeinschaftlich, Kleinunternehmerregelung usw.)</li>
    <li>Die korrekte MwSt-Berechnung (Rundungsfehler sind haeufig)</li>
</ul>

<h3>2. Kein FAIA-Export</h3>

<p>Bei einer Steuerpruefung kann die AED eine <strong>FAIA-Datei</strong> verlangen (informatisierte Pruefdatei). Aus Excel laesst sie sich nicht erzeugen. Sie muessten Ihre gesamte Buchhaltung von Hand rekonstruieren – ein Albtraum.</p>

<h3>3. Fehleranfaelligkeit</h3>

<p>In Excel sind Fehler haeufig und schwer zu entdecken:</p>

<ul>
    <li><strong>Zerstoerte Formeln</strong>: ein ungluecklicher Kopiervorgang verfaelscht saemtliche Berechnungen</li>
    <li><strong>Doppelte Nummern</strong>: ohne automatische Kontrolle vergeben Sie eine Nummer zweimal</li>
    <li><strong>Vergessene Zeilen</strong>: eine nicht erfasste Rechnung verfaelscht den erklaerten Umsatz</li>
    <li><strong>Keine Sicherung</strong>: eine beschaedigte oder geloeschte Datei bedeutet Datenverlust</li>
</ul>

<h3>4. Zeitverlust</h3>

<p>In Excel kostet jede Rechnung Zeit:</p>

<ul>
    <li>Die Kundendaten bei jeder Rechnung neu abtippen</li>
    <li>Die MwSt von Hand berechnen</li>
    <li>Ein PDF erzeugen, speichern und per E-Mail versenden</li>
    <li>Die Zahlungen in einer weiteren Datei verfolgen</li>
    <li>Am Monatsende das Einnahmenbuch aufbereiten</li>
</ul>

<p>Mit Excel verbringt ein Freiberufler im Schnitt <strong>mehrere Stunden im Monat</strong> mit der Verwaltung. Mit passender Software ist es <strong>weniger als eine Stunde</strong>.</p>

<h2>Was eine Rechnungssoftware bringt</h2>

{$deTable}

<p class="text-sm text-slate-500"><em>Bei faktur.lu gehoeren die automatischen Mahnungen zum Pro-Tarif und das Einnahmenbuch zum Essentiel-Tarif. Die uebrigen Zeilen dieser Tabelle sind bereits im kostenlosen Tarif enthalten.</em></p>

<h2>Wie Sie von Excel zu einer Software wechseln</h2>

<p>Der Umstieg ist einfacher, als Sie denken:</p>

<ol>
    <li><strong>Exportieren Sie Ihre Kunden</strong> aus Excel als CSV</li>
    <li><strong>Importieren Sie sie</strong> in die Software (faktur.lu unterstuetzt den Excel-/CSV-Import mit Spaltenzuordnung)</li>
    <li><strong>Richten Sie Ihr Unternehmen ein</strong> (Name, MwSt, Anschrift, Logo)</li>
    <li><strong>Erstellen Sie Ihre erste Rechnung</strong>: in zwei Minuten statt fuenfzehn</li>
</ol>

<p>Sie muessen Ihre bisherigen Kunden nicht einzeln neu erfassen. Der <strong>Excel-/CSV-Import</strong> von faktur.lu erkennt die Spalten automatisch und schlaegt eine passende Zuordnung vor.</p>

<h2>Was kostet das?</h2>

<p>Eine auf Luxemburg zugeschnittene Rechnungssoftware kostet zwischen <strong>0 und 15 EUR im Monat</strong>. Das ist der Preis eines Kaffees pro Woche fuer:</p>

<ul>
    <li>Mehr als vier gewonnene Stunden im Monat</li>
    <li>Vermiedene Konformitaetsfehler</li>
    <li>Bereitschaft im Fall einer Steuerpruefung</li>
    <li>Ruhe im Kopf</li>
</ul>

<p>faktur.lu bietet einen <strong>kostenlosen Tarif</strong> fuer den Anfang (5 Rechnungen pro Monat) und den Tarif Essentiel zu 5 EUR im Monat fuer Freiberufler.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich" class="text-primary-500 hover:text-primary-600 text-sm">die richtige Software waehlen →</a></li><li><a href="/de/blog/7-tipps-automatisierung-rechnungsstellung-zeit-sparen" class="text-primary-500 hover:text-primary-600 text-sm">Rechnungsstellung automatisieren →</a></li><li><a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Export →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">In fuenf Minuten von Excel zu faktur.lu</h3>
    <p class="text-primary-800 mb-4">Importieren Sie Ihre Kunden aus Excel, erstellen Sie Ihre erste konforme Rechnung und exportieren Sie Ihre FAIA-Datei. Kostenlos zum Start.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;

        $en = <<<HTML
<p class="lead">Still writing your invoices in Excel? You are not alone: <strong>many freelancers and micro-businesses</strong> in Luxembourg still invoice from a spreadsheet. But the practice carries serious risks.</p>

<h2>Where Excel falls short for invoicing</h2>

<h3>1. No guaranteed compliance</h3>

<p>Excel does not check that your invoice meets Luxembourg's legal requirements. You risk forgetting:</p>

<ul>
    <li>The mandatory <strong>sequential numbering</strong> (no gaps, no duplicates)</li>
    <li>The required <strong>legal information</strong>: VAT number and matricule, plus the RCS number if you are a registered trader or company — that last obligation coming from company-register law, not from the VAT law</li>
    <li>The right <strong>VAT mention</strong> for the situation (intra-EU, exemption, and so on)</li>
    <li>Correct VAT calculation (rounding errors are common)</li>
</ul>

<h3>2. No FAIA export</h3>

<p>In a tax audit, the AED can require a <strong>FAIA file</strong> (computerised audit file). It cannot be produced from Excel. You would have to rebuild your entire bookkeeping by hand — a nightmare.</p>

<h3>3. Error-prone by design</h3>

<p>In Excel, mistakes are frequent and hard to spot:</p>

<ul>
    <li><strong>Broken formulas</strong>: one careless copy-paste can throw off every calculation</li>
    <li><strong>Duplicate numbers</strong>: with no automatic check, you can assign the same number twice</li>
    <li><strong>Missed rows</strong>: an unrecorded invoice distorts the turnover you declare</li>
    <li><strong>No backup</strong>: a corrupted or deleted file means lost data</li>
</ul>

<h3>4. Time lost</h3>

<p>In Excel, every invoice costs you time:</p>

<ul>
    <li>Retyping the client's details on each invoice</li>
    <li>Working out the VAT by hand</li>
    <li>Generating a PDF, saving it, emailing it</li>
    <li>Tracking payments in yet another file</li>
    <li>Preparing the revenue book at month end</li>
</ul>

<p>With Excel a freelancer spends on average <strong>several hours a month</strong> on admin. With software built for the job, it is <strong>under an hour</strong>.</p>

<h2>What invoicing software brings</h2>

{$enTable}

<p class="text-sm text-slate-500"><em>At faktur.lu, automatic reminders belong to the Pro plan and the revenue book to the Essentiel plan. Every other row in this table is included from the free plan.</em></p>

<h2>How to move from Excel to software</h2>

<p>Migrating is simpler than you think:</p>

<ol>
    <li><strong>Export your clients</strong> from Excel as CSV</li>
    <li><strong>Import them</strong> into the software (faktur.lu supports Excel/CSV import with column mapping)</li>
    <li><strong>Set up your business</strong> (name, VAT, address, logo)</li>
    <li><strong>Create your first invoice</strong>: in two minutes, not fifteen</li>
</ol>

<p>You do not need to retype your existing clients one by one. faktur.lu's <strong>Excel/CSV import</strong> detects the columns automatically and proposes a sensible mapping.</p>

<h2>What does it cost?</h2>

<p>Invoicing software suited to Luxembourg costs between <strong>EUR 0 and 15 a month</strong>. That is the price of one coffee a week for:</p>

<ul>
    <li>Four hours or more saved every month</li>
    <li>Compliance mistakes avoided</li>
    <li>Being ready if a tax audit comes</li>
    <li>Peace of mind</li>
</ul>

<p>faktur.lu offers a <strong>free plan</strong> to get started (5 invoices a month) and an Essentiel plan at EUR 5 a month for freelancers.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/choose-invoicing-software-luxembourg-comparison" class="text-primary-500 hover:text-primary-600 text-sm">choosing your software →</a></li><li><a href="/en/blog/7-tips-automate-invoicing-save-time" class="text-primary-500 hover:text-primary-600 text-sm">automating your invoicing →</a></li><li><a href="/en/blog/faia-luxembourg-computerized-audit-file-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA export →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">From Excel to faktur.lu in five minutes</h3>
    <p class="text-primary-800 mb-4">Import your clients from Excel, create your first compliant invoice and export your FAIA file. Free to start.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;

        $lb = <<<HTML
<p class="lead">Maacht Dir Är Rechnungen nach ëmmer mat Excel? Dir sidd net eleng: <strong>vill Freelancer a Mikroentreprisen</strong> zu Lëtzebuerg fakturéieren nach ëmmer mat engem Tabelleprogramm. Dës Praxis bréngt awer eescht Risiken mat sech.</p>

<h2>D'Grenze vun Excel bei der Fakturatioun</h2>

<h3>1. Keng garantéiert Konformitéit</h3>

<p>Excel kontrolléiert net, ob Är Rechnung de Lëtzebuerger gesetzleche Verflichtungen entsprécht. Dir riskéiert ze vergiessen:</p>

<ul>
    <li>Déi obligatoresch <strong>sequentiell Nummeréierung</strong> (ouni Lach an ouni Duebel)</li>
    <li>Déi néideg <strong>gesetzlech Mentiounen</strong>: TVA-Nummer a Matricule, plus d'RCS-Nummer, wann Dir Händler oder ageschriwwe Gesellschaft sidd – dës lescht Obligatioun kënnt aus dem Handelsregisterrecht, net aus dem TVA-Gesetz</li>
    <li>Déi richteg <strong>TVA-Mentioun</strong> no der Situatioun (intracommunautär, Franchise asw.)</li>
    <li>Déi korrekt TVA-Berechnung (Rondungsfeeler si heefeg)</li>
</ul>

<h3>2. Keen FAIA-Export</h3>

<p>Bei enger Steierkontroll kann d'AED e <strong>FAIA-Fichier</strong> verlaangen (informatiséierte Prüffichier). Aus Excel léisst en sech net generéieren. Dir misst Är ganz Comptabilitéit mat der Hand rekonstruéieren – e Kaucheamar.</p>

<h3>3. Risiko vu Feeler</h3>

<p>Mat Excel sinn d'Feeler heefeg a schwéier z'entdecken:</p>

<ul>
    <li><strong>Kaputt Formelen</strong>: ee falsche Copy-Paste kann all Är Berechnunge verfälschen</li>
    <li><strong>Duebel Nummeren</strong>: ouni automatesch Kontroll gitt Dir zweemol déiselwecht Nummer</li>
    <li><strong>Vergiessen Zeilen</strong>: eng net erfaasste Rechnung verfälscht Ären deklaréierten Ëmsaz</li>
    <li><strong>Keng Sécherung</strong>: e beschiedegten oder geläschte Fichier heescht Donnéeë verluer</li>
</ul>

<h3>4. Zäitverloscht</h3>

<p>Mat Excel kascht all Rechnung Zäit:</p>

<ul>
    <li>D'Clientsinformatiounen bei all Rechnung nei ofschreiwen</li>
    <li>D'TVA mat der Hand berechnen</li>
    <li>E PDF generéieren, späicheren a per E-Mail verschécken</li>
    <li>D'Bezuelungen an engem anere Fichier verfollegen</li>
    <li>Um Enn vum Mount d'Akommessbuch virbereeden</li>
</ul>

<p>Mat Excel verbréngt e Freelancer am Duerchschnëtt <strong>e puer Stonnen am Mount</strong> mat der Administratioun. Mat enger ugepasster Software ass et <strong>manner wéi eng Stonn</strong>.</p>

<h2>Wat eng Fakturatiounssoftware bréngt</h2>

{$lbTable}

<p class="text-sm text-slate-500"><em>Bei faktur.lu gehéieren déi automatesch Mahnungen zum Plang Pro an d'Akommessbuch zum Plang Essentiel. Déi aner Zeile vun dësem Tableau sinn scho vum gratis Plang un abegraff.</em></p>

<h2>Wéi ee vun Excel op eng Software wiesselt</h2>

<p>De Wiessel ass méi einfach, wéi Dir denkt:</p>

<ol>
    <li><strong>Exportéiert Är Clienten</strong> aus Excel am CSV-Format</li>
    <li><strong>Importéiert se</strong> an d'Software (faktur.lu ënnerstëtzt den Excel-/CSV-Import mat Kolonnen-Zouuerdnung)</li>
    <li><strong>Konfiguréiert Äre Betrib</strong> (Numm, TVA, Adress, Logo)</li>
    <li><strong>Erstellt Är éischt Rechnung</strong>: an zwou Minutten, net a fofzéng</li>
</ol>

<p>Dir musst Är al Clienten net eenzel nei aginn. Den <strong>Excel-/CSV-Import</strong> vu faktur.lu erkennt d'Kolonnen automatesch a proposéiert eng passend Zouuerdnung.</p>

<h2>Wat kascht dat?</h2>

<p>Eng op Lëtzebuerg ugepasste Fakturatiounssoftware kascht tëscht <strong>0 an 15 EUR am Mount</strong>. Dat ass de Präis vun engem Kaffi d'Woch fir:</p>

<ul>
    <li>Méi wéi véier gewonnen Stonnen am Mount</li>
    <li>Vermidde Konformitéitsfeeler</li>
    <li>Prett ze si bei enger Steierkontroll</li>
    <li>Rou am Kapp</li>
</ul>

<p>faktur.lu bitt e <strong>gratis Plang</strong> fir unzefänken (5 Rechnungen am Mount) an de Plang Essentiel fir 5 EUR am Mount fir Freelancer.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach" class="text-primary-500 hover:text-primary-600 text-sm">Är Software wielen →</a></li><li><a href="/lb/blog/7-tipps-fakturatioun-automatiseieren-zait-spueren" class="text-primary-500 hover:text-primary-600 text-sm">Är Fakturatioun automatiséieren →</a></li><li><a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide" class="text-primary-500 hover:text-primary-600 text-sm">FAIA-Export →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">A fënnef Minutte vun Excel op faktur.lu</h3>
    <p class="text-primary-800 mb-4">Importéiert Är Clienten aus Excel, erstellt Är éischt konform Rechnung an exportéiert Äre FAIA-Fichier. Gratis fir unzefänken.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;

        $pt = <<<HTML
<p class="lead">Ainda usa o Excel para fazer as suas faturas? Não está sozinho: <strong>muitos freelancers e microempresas</strong> no Luxemburgo continuam a faturar com uma folha de cálculo. Mas esta prática comporta riscos sérios.</p>

<h2>Os limites do Excel para a faturação</h2>

<h3>1. Nenhuma conformidade garantida</h3>

<p>O Excel não verifica se a sua fatura respeita as obrigações legais luxemburguesas. Corre o risco de esquecer:</p>

<ul>
    <li>A <strong>numeração sequencial</strong> obrigatória (sem falhas nem duplicados)</li>
    <li>As <strong>menções legais</strong> exigidas: número de IVA e matrícula, mais o número RCS se for comerciante ou sociedade inscrita — esta última obrigação decorre do registo comercial, não da lei do IVA</li>
    <li>A menção de IVA correta consoante o caso (intracomunitário, isenção, etc.)</li>
    <li>O cálculo correto do IVA (os erros de arredondamento são frequentes)</li>
</ul>

<h3>2. Sem exportação FAIA</h3>

<p>Numa inspeção fiscal, a AED pode exigir um <strong>ficheiro FAIA</strong> (ficheiro de auditoria informatizado). É impossível gerá-lo a partir do Excel. Teria de reconstituir manualmente toda a sua contabilidade — um pesadelo.</p>

<h3>3. Risco de erros</h3>

<p>Com o Excel, os erros são frequentes e difíceis de detetar:</p>

<ul>
    <li><strong>Fórmulas partidas</strong>: uma cópia mal feita pode falsear todos os cálculos</li>
    <li><strong>Números duplicados</strong>: sem controlo automático, pode atribuir duas vezes o mesmo número</li>
    <li><strong>Linhas esquecidas</strong>: uma fatura não registada falseia o volume de negócios declarado</li>
    <li><strong>Sem cópia de segurança</strong>: um ficheiro corrompido ou eliminado significa dados perdidos</li>
</ul>

<h3>4. Perda de tempo</h3>

<p>Com o Excel, cada fatura exige tempo:</p>

<ul>
    <li>Voltar a copiar os dados do cliente em cada fatura</li>
    <li>Calcular o IVA manualmente</li>
    <li>Gerar um PDF, guardá-lo e enviá-lo por email</li>
    <li>Acompanhar os pagamentos noutro ficheiro</li>
    <li>Preparar o livro de receitas no fim do mês</li>
</ul>

<p>Com o Excel, um freelancer passa em média <strong>várias horas por mês</strong> na gestão administrativa. Com um software adequado, é <strong>menos de uma hora</strong>.</p>

<h2>O que traz um software de faturação</h2>

{$ptTable}

<p class="text-sm text-slate-500"><em>No faktur.lu, as cobranças automáticas fazem parte do plano Pro e o livro de receitas do plano Essentiel. As restantes linhas deste quadro estão incluídas desde o plano gratuito.</em></p>

<h2>Como migrar do Excel para um software</h2>

<p>A migração é mais simples do que julga:</p>

<ol>
    <li><strong>Exporte os seus clientes</strong> do Excel em formato CSV</li>
    <li><strong>Importe-os</strong> no software (o faktur.lu suporta a importação Excel/CSV com mapeamento de colunas)</li>
    <li><strong>Configure a sua empresa</strong> (nome, IVA, morada, logótipo)</li>
    <li><strong>Crie a sua primeira fatura</strong>: em 2 minutos, não em 15</li>
</ol>

<p>Não precisa de reintroduzir os seus antigos clientes um a um. A <strong>importação Excel/CSV</strong> do faktur.lu deteta automaticamente as colunas e propõe um mapeamento adequado.</p>

<h2>Quanto custa?</h2>

<p>Um software de faturação adaptado ao Luxemburgo custa entre <strong>0 e 15 EUR/mês</strong>. É o preço de um café por semana para:</p>

<ul>
    <li>Ganhar mais de 4 horas por mês</li>
    <li>Evitar erros de conformidade</li>
    <li>Estar preparado em caso de inspeção fiscal</li>
    <li>Ter o espírito tranquilo</li>
</ul>

<p>O faktur.lu propõe um <strong>plano gratuito</strong> para começar (5 faturas/mês) e um plano Essentiel a 5 EUR/mês para freelancers.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/como-escolher-o-seu-software-de-faturacao-no-luxemburgo" class="text-primary-500 hover:text-primary-600 text-sm">escolher o seu software →</a></li><li><a href="/pt/blog/7-conselhos-para-automatizar-a-sua-faturacao-e-ganhar-tempo" class="text-primary-500 hover:text-primary-600 text-sm">automatizar a sua faturação →</a></li><li><a href="/pt/blog/faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado" class="text-primary-500 hover:text-primary-600 text-sm">exportação FAIA →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Passe do Excel para o faktur.lu em 5 minutos</h3>
    <p class="text-primary-800 mb-4">Importe os seus clientes a partir do Excel, crie a sua primeira fatura conforme e exporte o seu FAIA. Gratuito para começar.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
