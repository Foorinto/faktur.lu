<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Travailler avec une fiduciaire au Luxembourg ».
 *
 * REVENDICATION CORRIGÉE. Le titre annonçait « un portail GRATUIT pour votre
 * fiduciaire » et le corps « le portail est gratuit pour le cabinet ». C'est
 * vrai côté cabinet — il ne paie rien — mais trompeur côté lecteur, qui est
 * le client : inviter une fiduciaire relève de `accounting_portal`, réservé
 * à Essentiel et Pro (PlanService, et le message de CheckPlanFeature le dit
 * mot pour mot : « Le portail comptable (invitation d'une fiduciaire)
 * nécessite le plan Essentiel ou Pro »).
 *
 * De même, « elle récupère vos exports (FAIA 2.01, Sage, CSV) en un clic »
 * mélange deux niveaux : l'export FAIA est inclus dès le plan gratuit,
 * les exports Sage BOB 50, Sage 100 et CSV relèvent de `accounting_exports`,
 * donc d'Essentiel. AccountantExportController lève un 403 explicite sinon.
 *
 * Le texte distingue désormais les deux : gratuité pour le cabinet, plan
 * requis côté client.
 *
 * Vérifié et conservé : l'Ordre des Experts-Comptables tient bien un annuaire
 * public (oec.lu répond).
 *
 * DE, EN, LB, PT : 3 220 à 3 574 caractères contre 3 829, deux liens contre
 * cinq.
 */
return new class extends Migration
{
    private const KEY = 'travailler-avec-fiduciaire-luxembourg-guide';

    private const FR_FIXES = [
        [
            '<h2>faktur.lu : un portail gratuit pour votre fiduciaire</h2>',
            '<h2>faktur.lu : un portail dédié à votre fiduciaire</h2>',
        ],
        [
            'Le portail est <strong>gratuit pour le cabinet</strong>. Si votre fiduciaire n\'utilise pas encore faktur.lu, parlez-lui de notre <a href="/fr/partenaires">programme partenaire</a>.',
            'Le portail ne coûte <strong>rien au cabinet</strong> : c\'est vous qui ouvrez l\'accès depuis votre compte. Si votre fiduciaire n\'utilise pas encore faktur.lu, parlez-lui de notre <a href="/fr/partenaires">programme partenaire</a>.',
        ],
        [
            'elle récupère vos exports (FAIA 2.01, Sage, CSV) en un clic, sans vous solliciter et sans ressaisie.',
            'elle récupère vos exports en un clic, sans vous solliciter et sans ressaisie.'
                ."</p>\n\n"
                .'<p class="text-sm text-slate-500"><em>L\'export FAIA 2.01 est inclus dès le plan gratuit. L\'invitation d\'une fiduciaire et les exports Sage BOB 50, Sage 100 et CSV sont disponibles à partir du plan Essentiel.</em>',
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
<p class="lead">In Luxemburg uebertragen die meisten Selbststaendigen und KMU ihre Buchhaltung einer <strong>Treuhandgesellschaft</strong>. Gut gewaehlt und gut beliefert, spart sie Ihnen viel Zeit und erspart Ihnen teure Fehler. So arbeiten Sie wirksam mit ihr zusammen.</p>

<h2>Was ist eine Treuhandgesellschaft und wozu dient sie?</h2>
<p>Eine Treuhandgesellschaft uebernimmt Ihre buchhalterischen und steuerlichen Pflichten ganz oder teilweise: Buchfuehrung, MwSt-Erklaerungen, Jahresabschluss, Steuererklaerung, Beratung. In Luxemburg ist sie fuer Selbststaendige rechtlich nicht vorgeschrieben, aber sehr zu empfehlen, sobald Ihre Taetigkeit ueber einige Rechnungen im Monat hinausgeht.</p>

<h2>Wann sollten Sie eine Treuhandgesellschaft einschalten?</h2>
<ul>
    <li>Sie ueberschreiten die MwSt-Befreiungsschwelle und muessen regelmaessig MwSt erklaeren.</li>
    <li>Sie gruenden eine Gesellschaft (Sàrl, Sàrl-S) mit vorgeschriebener doppelter Buchfuehrung.</li>
    <li>Es fehlt Ihnen an Zeit oder an Gelassenheit gegenueber Ihren steuerlichen Pflichten.</li>
    <li>Sie wollen optimieren (abzugsfaehige Kosten, Abschreibungen), ohne bei einer <a href="/de/blog/steuerpruefung-luxemburg-vorbereiten">Pruefung der AED</a> ein Risiko einzugehen.</li>
</ul>

<h2>Die richtige Treuhandgesellschaft waehlen</h2>
<p>Einige konkrete Kriterien:</p>
<ul>
    <li><strong>Die Spezialisierung</strong>: ein Buero, das Selbststaendige und Kleinstunternehmen gewohnt ist, versteht Sie besser als eines, das auf Konzerne ausgerichtet ist.</li>
    <li><strong>Die Reaktionsfaehigkeit</strong>: stellen Sie vor der Unterschrift eine Frage und achten Sie auf die Antwortzeit.</li>
    <li><strong>Die Werkzeuge</strong>: eine Treuhandgesellschaft, die digitale Daten annimmt (Buchhaltungsexporte, FAIA) statt Stapel von PDF, spart beiden Seiten Zeit.</li>
    <li><strong>Die Transparenz der Honorare</strong>: Monatspauschale oder Einzelabrechnung? Was genau ist enthalten?</li>
</ul>
<p>Die <strong>Ordre des Experts-Comptables (OEC)</strong> fuehrt ein oeffentliches Verzeichnis der zugelassenen Fachleute (oec.lu).</p>

<h2>Der eigentliche Hebel: saubere Daten liefern</h2>
<p>Die groesste Zeit- und Kostenquelle auf Seiten der Treuhandgesellschaft ist die <strong>Neuerfassung</strong>: Ihre PDF- oder Excel-Rechnungen erneut in ihre Software eingeben. Je strukturierter Ihre Daten, desto weniger Stunden werden abgerechnet und desto weniger Fehler entstehen.</p>
<p>Gute Praktiken:</p>
<ul>
    <li>Stellen Sie <a href="/de/blog/pflichtangaben-rechnung-luxemburg">konforme Rechnungen</a> mit lueckenloser fortlaufender Nummerierung aus.</li>
    <li>Buendeln Sie alles in einem einzigen Werkzeug statt in verstreuten Dateien.</li>
    <li>Liefern Sie einbaufertige Exporte: <a href="/de/blog/faia-luxemburg-informatisierte-audit-datei-leitfaden">FAIA</a>, Sage BOB 50, Sage 100 oder CSV.</li>
</ul>

<h2>faktur.lu: ein eigenes Portal fuer Ihre Treuhandgesellschaft</h2>
<p>Mit faktur.lu fakturieren Sie ganz normal, und Ihre Treuhandgesellschaft greift <strong>nur lesend</strong> ueber ein eigenes Buchhaltungsportal auf Ihre Daten zu: sie holt sich Ihre Exporte mit einem Klick, ohne Rueckfrage und ohne Neuerfassung.</p>

<p class="text-sm text-slate-500"><em>Der FAIA-Export 2.01 ist bereits im kostenlosen Tarif enthalten. Die Einladung einer Treuhandgesellschaft sowie die Exporte nach Sage BOB 50, Sage 100 und CSV sind ab dem Essentiel-Tarif verfuegbar.</em></p>

<p>Das Portal kostet die <strong>Treuhandgesellschaft nichts</strong>: Sie selbst oeffnen den Zugang aus Ihrem Konto heraus. Nutzt Ihre Treuhandgesellschaft faktur.lu noch nicht, sprechen Sie sie auf unser <a href="/de/partner">Partnerprogramm</a> an.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Sauber fakturieren – Ihr Buchhalter wird es Ihnen danken</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt in Luxemburg konforme Rechnungen und Exporte, die fuer Ihre Treuhandgesellschaft bereitstehen (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>
HTML;

        $en = <<<'HTML'
<p class="lead">In Luxembourg, most self-employed people and SMEs hand their bookkeeping to a <strong>fiduciaire</strong> (accounting firm). Well chosen and well fed, it saves you real time and spares you costly mistakes. Here is how to work with one effectively.</p>

<h2>What is a fiduciaire and what is it for?</h2>
<p>A fiduciaire takes on all or part of your accounting and tax obligations: bookkeeping, VAT returns, annual accounts, tax return, advice. In Luxembourg it is not legally compulsory for a self-employed person, but it is strongly advisable as soon as your activity goes beyond a few invoices a month.</p>

<h2>When should you call on one?</h2>
<ul>
    <li>You exceed the VAT exemption threshold and must file VAT returns regularly.</li>
    <li>You set up a company (Sàrl, Sàrl-S) with mandatory double-entry bookkeeping.</li>
    <li>You lack the time, or the peace of mind, for your tax obligations.</li>
    <li>You want to optimise (deductible expenses, depreciation) without taking risks in an <a href="/en/blog/tax-audit-luxembourg-how-to-prepare">AED audit</a>.</li>
</ul>

<h2>Choosing the right fiduciaire</h2>
<p>A few concrete criteria:</p>
<ul>
    <li><strong>Specialisation</strong>: a firm used to freelancers and micro-businesses will understand you better than one geared to large groups.</li>
    <li><strong>Responsiveness</strong>: ask a question before signing, and watch how long the answer takes.</li>
    <li><strong>Tooling</strong>: a fiduciaire that accepts digital data (accounting exports, FAIA) rather than stacks of PDFs saves time on both sides.</li>
    <li><strong>Transparent fees</strong>: monthly retainer or per item? What exactly does it cover?</li>
</ul>
<p>The <strong>Ordre des Experts-Comptables (OEC)</strong> keeps a public directory of accredited professionals (oec.lu).</p>

<h2>The real lever: sending clean data</h2>
<p>The main source of wasted time — and cost — on the fiduciaire's side is <strong>re-keying</strong>: taking your PDF or Excel invoices and typing them back into their software. The more structured your data, the fewer hours they bill and the fewer errors creep in.</p>
<p>Good practice:</p>
<ul>
    <li>Issue <a href="/en/blog/mandatory-information-invoice-luxembourg">compliant invoices</a> with unbroken sequential numbering.</li>
    <li>Keep everything in a single tool rather than scattered files.</li>
    <li>Provide ready-to-import exports: <a href="/en/blog/faia-luxembourg-computerized-audit-file-guide">FAIA</a>, Sage BOB 50, Sage 100 or CSV.</li>
</ul>

<h2>faktur.lu: a dedicated portal for your fiduciaire</h2>
<p>With faktur.lu you invoice as usual, and your fiduciaire reaches your data <strong>read-only</strong> through a dedicated accounting portal: they pull your exports in one click, without asking you and without re-keying.</p>

<p class="text-sm text-slate-500"><em>The FAIA 2.01 export is included from the free plan. Inviting a fiduciaire, and the Sage BOB 50, Sage 100 and CSV exports, are available from the Essentiel plan.</em></p>

<p>The portal costs the <strong>firm nothing</strong>: you are the one who opens access from your own account. If your fiduciaire does not use faktur.lu yet, tell them about our <a href="/en/partners">partner programme</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice cleanly — your accountant will thank you</h3>
    <p class="text-primary-800 mb-4">faktur.lu produces Luxembourg-compliant invoices and exports ready for your fiduciaire (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML;

        $lb = <<<'HTML'
<p class="lead">Zu Lëtzebuerg iwwerloosse déi meescht Onofhängeger a KMU hir Comptabilitéit engem <strong>Fiduciaire</strong> (Comptabelbüro). Gutt gewielt a gutt beliwwert, spuert en Iech wäertvoll Zäit an erspuert Iech deier Feeler. Hei ass, wéi Dir effikass mat him zesummeschafft.</p>

<h2>Wat ass e Fiduciaire a wozou déngt en?</h2>
<p>E Fiduciaire iwwerhëlt Är comptabel a fiskalesch Obligatiounen ganz oder deelweis: Buchféierung, TVA-Deklaratiounen, Joresbilan, Steiererklärung, Berodung. Zu Lëtzebuerg ass en fir en Onofhängegen net gesetzlech obligatoresch, mä ganz recommandéiert, soubal Är Aktivitéit iwwer e puer Rechnungen am Mount erausgeet.</p>

<h2>Wéini soll ee sech un e Fiduciaire wenden?</h2>
<ul>
    <li>Dir iwwerschreit d'TVA-Franchise-Schwell a musst reegelméisseg TVA deklaréieren.</li>
    <li>Dir grënnt eng Gesellschaft (Sàrl, Sàrl-S) mat obligatorescher duebeler Buchféierung.</li>
    <li>Iech feelt d'Zäit oder d'Rou fir Är fiskalesch Obligatiounen.</li>
    <li>Dir wëllt optiméieren (ofsetzbar Käschten, Amortissementer), ouni bei enger <a href="/lb/blog/steierprefung-letzebuerg-virbereden">Kontroll vun der AED</a> e Risiko anzegoen.</li>
</ul>

<h2>De richtege Fiduciaire wielen</h2>
<p>E puer konkret Kritären:</p>
<ul>
    <li><strong>D'Spezialitéit</strong>: e Büro, dat Onofhängeger a Mikroentreprise gewinnt ass, versteet Iech besser wéi ee fir grouss Gruppen.</li>
    <li><strong>D'Reaktivitéit</strong>: stellt eng Fro ier Dir ënnerschreift, a kuckt d'Äntwertzäit.</li>
    <li><strong>D'Tools</strong>: e Fiduciaire, deen numeresch Donnéeën (comptabel Exporten, FAIA) unhëlt amplaz Stapele vu PDF, spuert béide Säiten Zäit.</li>
    <li><strong>D'Transparenz vun den Honoraire</strong>: Méintsforfait oder pro Akt? Wat ass genee abegraff?</li>
</ul>
<p>D'<strong>Ordre des Experts-Comptables (OEC)</strong> féiert en offiziellen Annuaire vun den agreéierte Fachleit (oec.lu).</p>

<h2>De richtege Hiewel: propper Donnéeën liwweren</h2>
<p>Déi gréisste Quell u verluerer Zäit (a Käschten) op der Säit vum Fiduciaire ass d'<strong>Nei-Erfassung</strong>: Är PDF- oder Excel-Rechnungen nach eng Kéier a seng Software anzeginn. Wat Är Donnéeë strukturéierter sinn, wat manner Stonne fakturéiert ginn a wat manner Feeler entstinn.</p>
<p>Gutt Praktiken:</p>
<ul>
    <li>Stellt <a href="/lb/blog/pflichtinformatiounen-rechnung-letzebuerg">konform Rechnungen</a> mat kontinuéierlecher sequentieller Nummeréierung aus.</li>
    <li>Zentraliséiert alles an engem eenzegen Tool amplaz a verstreete Fichieren.</li>
    <li>Liwwert prett-z-integréierend Exporten: <a href="/lb/blog/faia-letzebuerg-informatiseierte-audit-fichier-guide">FAIA</a>, Sage BOB 50, Sage 100 oder CSV.</li>
</ul>

<h2>faktur.lu: e spezielle Portal fir Ären Fiduciaire</h2>
<p>Mat faktur.lu fakturéiert Dir ganz normal, an Ären Fiduciaire kënnt <strong>nëmme fir ze liesen</strong> iwwer e spezielle Comptabilitéits-Portal un Är Donnéeën: hie kritt Är Exporte mat engem Klick, ouni Iech ze froen an ouni Nei-Erfassung.</p>

<p class="text-sm text-slate-500"><em>Den FAIA-Export 2.01 ass scho vum gratis Plang un abegraff. D'Invitatioun vun engem Fiduciaire an d'Exporten op Sage BOB 50, Sage 100 a CSV si vum Plang Essentiel un disponibel.</em></p>

<p>De Portal kascht dem <strong>Büro näischt</strong>: Dir sidd et, deen den Zougang aus Ärem Kont opmécht. Benotzt Ären Fiduciaire faktur.lu nach net, schwätzt him vun eisem <a href="/lb/partneren">Partnerprogramm</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert propper, Ären Comptabel wäert Iech merci soen</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert zu Lëtzebuerg konform Rechnungen an Exporten, déi fir Ären Fiduciaire prett sinn (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis probéieren</a>
</div>
HTML;

        $pt = <<<'HTML'
<p class="lead">No Luxemburgo, a maior parte dos independentes e das PME confia a contabilidade a uma <strong>fiduciária</strong> (gabinete de contabilidade). Bem escolhida e bem alimentada, faz-lhe ganhar tempo precioso e evita-lhe erros dispendiosos. Eis como colaborar eficazmente com ela.</p>

<h2>O que é uma fiduciária e para que serve?</h2>
<p>Uma fiduciária assume, no todo ou em parte, as suas obrigações contabilísticas e fiscais: escrituração, declarações de IVA, balanço anual, declaração de impostos, aconselhamento. No Luxemburgo não é legalmente obrigatória para um independente, mas é vivamente recomendada assim que a atividade ultrapassa algumas faturas por mês.</p>

<h2>Quando recorrer a uma fiduciária?</h2>
<ul>
    <li>Ultrapassa o limiar de isenção de IVA e passa a ter de declarar IVA regularmente.</li>
    <li>Cria uma sociedade (Sàrl, Sàrl-S) com contabilidade organizada obrigatória.</li>
    <li>Falta-lhe tempo ou tranquilidade quanto às obrigações fiscais.</li>
    <li>Quer otimizar (despesas dedutíveis, amortizações) sem correr riscos numa <a href="/pt/blog/controlo-fiscal-no-luxemburgo-como-se-preparar">inspeção da AED</a>.</li>
</ul>

<h2>Como escolher a fiduciária certa</h2>
<p>Alguns critérios concretos:</p>
<ul>
    <li><strong>A especialidade</strong>: um gabinete habituado a independentes e microempresas compreendê-lo-á melhor do que um orientado para grandes grupos.</li>
    <li><strong>A capacidade de resposta</strong>: faça uma pergunta antes de assinar e observe o prazo de resposta.</li>
    <li><strong>As ferramentas</strong>: uma fiduciária que aceita dados digitais (exportações contabilísticas, FAIA) em vez de pilhas de PDF poupa tempo aos dois lados.</li>
    <li><strong>A transparência dos honorários</strong>: avença mensal ou ao ato? O que inclui exatamente?</li>
</ul>
<p>A <strong>Ordre des Experts-Comptables (OEC)</strong> mantém um diretório oficial dos profissionais acreditados (oec.lu).</p>

<h2>A verdadeira alavanca: entregar dados limpos</h2>
<p>A principal fonte de tempo perdido (e de custos) do lado da fiduciária é a <strong>reintrodução</strong>: pegar nas suas faturas em PDF ou Excel e voltar a lançá-las no software dela. Quanto mais estruturados forem os seus dados, menos horas ela fatura e menos erros surgem.</p>
<p>Boas práticas:</p>
<ul>
    <li>Emita <a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa">faturas conformes</a> com numeração sequencial contínua.</li>
    <li>Centralize tudo numa só ferramenta em vez de ficheiros dispersos.</li>
    <li>Forneça exportações prontas a integrar: <a href="/pt/blog/faia-luxemburgo-tudo-sobre-o-ficheiro-de-auditoria-informatizado">FAIA</a>, Sage BOB 50, Sage 100 ou CSV.</li>
</ul>

<h2>faktur.lu: um portal dedicado à sua fiduciária</h2>
<p>Com o faktur.lu fatura normalmente, e a sua fiduciária acede aos seus dados <strong>apenas em leitura</strong> através de um portal de contabilidade dedicado: obtém as suas exportações num clique, sem o incomodar e sem reintroduzir nada.</p>

<p class="text-sm text-slate-500"><em>A exportação FAIA 2.01 está incluída desde o plano gratuito. O convite a uma fiduciária e as exportações para Sage BOB 50, Sage 100 e CSV estão disponíveis a partir do plano Essentiel.</em></p>

<p>O portal não custa <strong>nada ao gabinete</strong>: é você que abre o acesso a partir da sua conta. Se a sua fiduciária ainda não usa o faktur.lu, fale-lhe do nosso <a href="/pt/parceiros">programa de parceiros</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature bem, o seu contabilista agradece</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera faturas conformes ao Luxemburgo e exportações prontas para a sua fiduciária (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>
HTML;

        return ['de' => $de, 'en' => $en, 'lb' => $lb, 'pt' => $pt];
    }
};
