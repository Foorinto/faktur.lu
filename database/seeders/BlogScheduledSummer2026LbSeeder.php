<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traductions LUXEMBOURGEOISES des articles programmés juillet-août 2026.
 * translation_key = slug FR ; slug = slug luxembourgeois localisé ; locale='lb'.
 * Idempotent (skip si slug existe). published_at = même lundi que le FR.
 *
 * NB: le luxembourgeois mérite une relecture par un locuteur natif avant
 * publication (contenu programmé en date future => fenêtre de relecture).
 */
class BlogScheduledSummer2026LbSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip LB: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'cover_image' => $article['cover_image'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'lb',
                'translation_key' => $article['translation_key'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Created LB: {$article['slug']}");
        }
    }

    protected function articles(): array
    {
        return [
            // ===== 2026-07-06 =====
            [
                'category_id' => 1,
                'translation_key' => 'travailler-avec-fiduciaire-luxembourg-guide',
                'slug' => 'zesummenaarbecht-fiduciaire-letzebuerg-guide',
                'cover_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=630&fit=crop',
                'title' => 'Mat enger Fiduciaire zu Lëtzebuerg schaffen: de praktesche Guide',
                'meta_title' => 'Mat enger Fiduciaire zu Lëtzebuerg schaffen | faktur.lu',
                'meta_description' => 'Wéini Dir eng Fiduciaire zu Lëtzebuerg braucht, wéi Dir déi richteg auswielt an hir propper Daten liwwert, fir Zäit (a Suen) ze spueren. Praktesche Guide 2026.',
                'excerpt' => 'Eng gutt Fiduciaire spuert Iech Zäit a vermeit Feeler. Virausgesat, Dir wielt se gutt aus a liwwert hir propper Daten. Esou profitéiert Dir am meeschten.',
                'published_at' => '2026-07-06 09:00:00',
                'content' => <<<HTML
<p class="lead">Zu Lëtzebuerg vertrauen déi meescht Onofhängeger a KMU hir Comptabilitéit enger <strong>Fiduciaire</strong> (Comptablescabinet) un. Gutt gewielt a gutt beliwwert, spuert se Iech wäertvoll Zäit a verhënnert deier Feeler. Esou schafft Dir effikass mat hir zesummen.</p>

<h2>Wat ass eng Fiduciaire a wozu déngt se?</h2>
<p>Eng Fiduciaire iwwerhëlt ganz oder deelweis Är comptabel a steierlech Flichten: Comptabilitéit, TVA-Deklaratiounen, Joresbilan, Steiererklärung, Berodung. Zu Lëtzebuerg ass se fir en Onofhängege net gesetzlech obligatoresch, mä staark recommandéiert, soubal Är Aktivitéit méi wéi e puer Rechnungen am Mount ausmécht.</p>

<h2>Wéini eng Fiduciaire engagéieren?</h2>
<ul>
    <li>Dir iwwerschreit d'TVA-Befreiungsgrenz a musst regelméisseg TVA deklaréieren.</li>
    <li>Dir grënnt eng Gesellschaft (Sàrl, Sàrl-S) mat obligatorescher duebeler Comptabilitéit.</li>
    <li>Iech feelt d'Zäit oder d'Rou fir Är steierlech Flichten.</li>
    <li>Dir wëllt optimiséieren (ofzéibar Käschten, Ofschreiwungen) ouni Risiko bei enger Kontroll vun der AED.</li>
</ul>

<h2>Wéi déi richteg Fiduciaire wielen</h2>
<ul>
    <li><strong>Spezialiséierung</strong>: e Cabinet, deen un Onofhängeger a kleng Betriber gewinnt ass, versteet Iech besser wéi een, deen op grouss Gruppen ausgeriicht ass.</li>
    <li><strong>Reaktiounsfäegkeet</strong>: stellt eng Fro virum Ënnerschreiwen a kuckt d'Äntwertzäit.</li>
    <li><strong>Tools</strong>: eng Fiduciaire, déi digital Daten (Comptabilitéitsexporter, FAIA) akzeptéiert amplaz Stapele PDF, spuert béide Säiten Zäit.</li>
    <li><strong>Transparent Honoraren</strong>: Méintspauschal oder pro Akt? Wat ass genau abegraff?</li>
</ul>
<p>D'<strong>Ordre des Experts-Comptables (OEC)</strong> féiert en offiziellt Verzeechnes vun den zougelaasse Fachleit (oec.lu).</p>

<h2>Den eigentleche Liewel: propper Daten liwweren</h2>
<p>Déi gréisste Quell vu verluerener Zäit (a Käschten) op der Säit vun der Fiduciaire ass d'<strong>Neierfaassung</strong>: Är PDF- oder Excel-Rechnungen erëm an hir Software aginn. Wat Är Daten méi strukturéiert sinn, wat se manner Stonnen ofrechent a wat manner Feeler entstinn.</p>
<ul>
    <li>Stellt konform Rechnungen mat enger duerchgängeger Nummeréierung aus.</li>
    <li>Zentraliséiert alles an engem Tool amplaz verstreeter Dateien.</li>
    <li>Liwwert integratiounsfäeg Exporter: FAIA 2.01, Sage BOB 50, Sage 100 oder CSV.</li>
</ul>

<h2>faktur.lu: e gratis Portal fir Är Fiduciaire</h2>
<p>Mat faktur.lu stellt Dir ganz normal Rechnungen aus, an Är Fiduciaire greift <strong>nëmme liesend</strong> iwwer en eegene Comptablespportal op Är Daten zou: si hëlt Är Exporter (FAIA 2.01, Sage, CSV) mat engem Klick of - ouni Nofro an ouni Neierfaassung. De Portal ass <strong>gratis fir de Cabinet</strong>. Wann Är Fiduciaire faktur.lu nach net benotzt, schwätzt hir vun eisem <a href="/lb/partneren">Partnerprogramm</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Propper fakturéieren - Äre Comptabel wäert Iech merci soen</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert zu Lëtzebuerg konform Rechnungen an Exporter prett fir Är Fiduciaire (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-07-13 =====
            [
                'category_id' => 1,
                'translation_key' => 'faire-devis-professionnel-luxembourg',
                'slug' => 'professionellen-devis-letzebuerg-maachen',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
                'title' => 'E professionellen Devis zu Lëtzebuerg maachen: Mentiounen, Gültegkeet a Konversioun',
                'meta_title' => 'E professionellen Devis zu Lëtzebuerg maachen: Guide | faktur.lu',
                'meta_description' => 'Wéi Dir zu Lëtzebuerg e professionellen Devis schreift: Mentiounen, Gültegkeetsdauer, juristesche Wäert a Konversioun an eng Rechnung. Modell a gutt Praxis 2026.',
                'excerpt' => 'En Devis bënnt Äre Client esou vill wéi Iech. Hei d\'Mentiounen, de juristesche Wäert, d\'Gültegkeetsdauer a wéi Dir en propper an eng Rechnung ëmwandelt.',
                'published_at' => '2026-07-13 09:00:00',
                'content' => <<<HTML
<p class="lead">Virun der Rechnung gëtt et dacks den <strong>Devis</strong>. Gutt geschriwwen, steckt en d'Leeschtung of, berouegt de Client a vermeit Sträit. Esou maacht Dir e professionellen Devis zu Lëtzebuerg.</p>

<h2>Wozu déngt en Devis?</h2>
<p>Den Devis ass eng <strong>chiffréiert kommerziell Offer</strong>: en beschreift d'Leeschtung oder d'Wueren, de Präis an d'Konditiounen. Zu Lëtzebuerg ass en net obligatoresch, mä staark recommandéiert, soubal eng Leeschtung en zwéckt huet: en schützt béid Säiten, andeems en Ëmfang a Präis virum Ufank festleet.</p>

<h2>D'Mentiounen an engem Devis</h2>
<ul>
    <li>Är komplett Kontaktdaten (Numm, Adress, TVA-Nummer wann assujetti).</li>
    <li>D'Kontaktdaten vum Client.</li>
    <li>Eng <strong>Devisnummer</strong> an d'Ausstellungsdatum.</li>
    <li>D'Detail vun de Leeschtungen / Produiten, mat Quantitéiten an Eenheetspräisser.</li>
    <li>De Betrag <strong>ouni TVA (HT)</strong>, déi applicabel TVA an de <strong>Total mat TVA (TTC)</strong>.</li>
    <li>D'<strong>Gültegkeetsdauer</strong> vun der Offer.</li>
    <li>D'Bezuelkonditiounen an, wann néideg, den ugefroten Akont.</li>
</ul>
<p>Wann Dir am Befreiungsregime sidd, gitt déi entspriechend Mentioun amplaz vun der TVA un.</p>

<h2>Wéi e juristesche Wäert?</h2>
<p>En Devis, deen de Client <strong>ënnerschriwwen an akzeptéiert</strong> huet, huet e contractuelle Wäert: en bënnt béid Säiten un den ofgemaachten Ëmfang a Präis. Dat ass Äre beschte Schutz bei Meenungsverschiddenheeten. Loosst den Devis also datéieren an ënnerschreiwen (Mentioun „Averstanen") ier Dir ufänkt.</p>

<h2>Gültegkeetsdauer</h2>
<p>Gitt ëmmer eng Gültegkeetsdauer un (z. B. 30 Deeg). Duerno sidd Dir net méi un de genannte Präis gebonnen - nëtzlech, wann Är Käschten änneren. Et ass och e Verkafsliewel: eng zäitlech limitéiert Offer hëlleft bei der Entscheedung.</p>

<h2>Vum Devis zur Rechnung</h2>
<p>Soubal den Devis akzeptéiert an d'Leeschtung erbruecht ass, stellt Dir d'<strong>Rechnung</strong> aus. Fir Feeler an duebel Erfaassung ze vermeiden, wandelt Dir den Devis am beschten <strong>direkt an eng Rechnung</strong> ëm: Positiounen, Betréi a Kontaktdaten gi iwwerholl, an et bleift just déi sequenziell Rechnungsnummer ze vergiewen.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Devis, dann Rechnung mat engem Klick</h3>
    <p class="text-primary-800 mb-4">Mat faktur.lu erstellt Dir Är Devisen, looss se akzeptéieren a wandelt se ouni Neierfaassung an konform Rechnungen ëm.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis en Devis erstellen</a>
</div>
HTML,
            ],

            // ===== 2026-07-20 =====
            [
                'category_id' => 1,
                'translation_key' => 'facturation-projet-forfait-regie-luxembourg',
                'slug' => 'projet-fakturatioun-forfait-regie-letzebuerg',
                'cover_image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&h=630&fit=crop',
                'title' => 'Projetfakturatioun zu Lëtzebuerg: Forfait oder no Opwand?',
                'meta_title' => 'Projetfakturatioun: Forfait vs Opwand zu Lëtzebuerg | faktur.lu',
                'meta_description' => 'Forfait oder Fakturatioun no Opwand fir e Projet zu Lëtzebuerg? Virdeeler, Risiken, Akonten a gutt Praxis fir ouni béis Iwwerraschung ze fakturéieren.',
                'excerpt' => 'E Projet als Forfait oder no Opwand ze fakturéieren ännert alles: Risiko, Tresorerie, Clientsbezéiung. Esou wielt a fakturéiert Dir all Modell propper.',
                'published_at' => '2026-07-20 09:00:00',
                'content' => <<<HTML
<p class="lead">Beim Fakturéiere vun engem Projet - Entwécklung, Design, Berodung, Bauleeschtung - stinn sech zwee Modeller géigeniwwer: de <strong>Forfait</strong> an d'<strong>Fakturatioun no Opwand</strong>. Déi richteg Wiel hänkt vum Grad vun der Onsécherheet an Ärer Tresorerie of.</p>

<h2>De Forfait: e fixe Präis fir en definéierten Ëmfang</h2>
<p>Dir nennt e Gesamtpräis fir e präzist Resultat. De Client kennt säi Budget am Viraus, wat berouegt. De Risiko läit awer bei Iech: wann de Projet aus dem Rudder leeft, iwwerhuelt Dir d'Méikäschten.</p>
<p><strong>Wéini benotzen:</strong> kloere a stabilen Ëmfang, Leeschtung, déi Dir gutt beherrscht.</p>
<p><strong>Gutt Praxis:</strong> stecke den Ëmfang am Devis präzis of, gesitt eng Klausel fir Ufroen ausserhalb vum Ëmfang vir, an deelt d'Bezuelung op (Akont + Etappen + Rescht).</p>

<h2>No Opwand: Fakturatioun vun der gemaachter Zäit</h2>
<p>Dir rechent déi effektiv geschaffte Stonnen oder Deeg zu engem ofgemaachte Saz of. De Risiko vun der Iwwerschreidung wiesselt zum Client, an Dir gitt fir déi ganz investéiert Zäit bezuelt. Am Géigenzuch huet de Client kee garantéierte Budget.</p>
<p><strong>Wéini benotzen:</strong> onkloeren oder evolutiven Ëmfang, laang Missiounen, Maintenance.</p>
<p><strong>Gutt Praxis:</strong> erfaasst d'Zäit genee, liwwert e kloeren Stonnennoweis, a setzt eventuell eng berouegend Obergrenz.</p>

<h2>Verglach</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Forfait</th><th class="text-left p-2 bg-slate-100">No Opwand</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Iwwerschreidungsrisiko</td><td class="p-2 border-b">Bei Iech</td><td class="p-2 border-b">Beim Client</td></tr>
        <tr><td class="p-2 border-b">Budgetiwwersiicht vum Client</td><td class="p-2 border-b">Héich</td><td class="p-2 border-b">Niddreg</td></tr>
        <tr><td class="p-2 border-b">Ideal fir</td><td class="p-2 border-b">Stabilen Ëmfang</td><td class="p-2 border-b">Evolutiven Ëmfang</td></tr>
    </tbody>
</table>

<h2>D'Akonten: schützt Är Tresorerie</h2>
<p>Bei engem Projet iwwer e puer Wochen sollt Dir net alles um Enn fakturéieren. Frot en <strong>Akont um Ufank</strong> a fakturéiert un <strong>Etappen</strong>. Dat séchert Är Tresorerie a limitéiert de Risiko vun engem Net-Bezuelen.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Forfait, Opwand, Akonten: faktur.lu erleedegt alles</h3>
    <p class="text-primary-800 mb-4">Zäiterfaassung, Akontos- a Projetrechnungen - konform an a wéinege Klicken.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-07-27 =====
            [
                'category_id' => 2,
                'translation_key' => 'cotisations-sociales-independant-ccss-luxembourg-2026',
                'slug' => 'sozialbeitreg-onofhaengeg-ccss-letzebuerg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'title' => 'Sozialbeiträg fir Onofhängeger zu Lëtzebuerg (CCSS) am Joer 2026',
                'meta_title' => 'Sozialbeiträg Onofhängeger Lëtzebuerg (CCSS) 2026 | faktur.lu',
                'meta_description' => 'Wéivill bezilt en Onofhängegen zu Lëtzebuerg? CCSS-Saz, Berechnungsbasis, Mindestbasis a Funktionéieren. Guide 2026 fir Är Sozialcharge virauszegesinn.',
                'excerpt' => 'Als Onofhängegen zu Lëtzebuerg bezilt Dir Beiträg un d\'CCSS op Är Akommes. Esou funktionéiert et, déi indikativ Saz a wéi Dir dës Charge virausgesitt.',
                'published_at' => '2026-07-27 09:00:00',
                'content' => <<<HTML
<p class="lead">Beim Start an d'Onofhängegkeet zu Lëtzebuerg sinn d'<strong>Sozialbeiträg</strong> dacks déi béis Iwwerraschung. Si finanzéieren Är sozial Ofsécherung (Krankheet, Pensioun, Ofhängegkeet) a ginn op Är Akommes berechent. Hei dat Wesentlecht fir virauszeplangen.</p>

<h2>Wat ass d'CCSS?</h2>
<p>De <strong>Centre Commun de la Sécurité Sociale (CCSS)</strong> ass den Organismus, deen d'Umeldung an d'Erhiewung vun de Sozialbeiträg zu Lëtzebuerg zentraliséiert. Vum Ufank vun Ärer onofhängeger Aktivitéit un musst Dir Iech do umellen.</p>

<h2>Op wat bezilt een?</h2>
<p>D'Beiträg gi op Är <strong>berufflech Akommes</strong> berechent (de Gewënn, net den Ëmsaz). Um Ufank geschitt d'Umeldung dacks op enger provisorescher Basis, déi duerno no Äre richtegen Akommes ugepasst gëtt, déi vun der Steierverwaltung matgedeelt ginn.</p>

<h2>Wéi eng Saz? (Gréisstenuerdnung)</h2>
<p>Am Ganzen maachen d'Beiträg vun engem Onofhängege <strong>ongeféier 25 %</strong> vun der Akommes aus, opgedeelt op déi verschidde Branchen:</p>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Branche</th><th class="text-left p-2 bg-slate-100">Indikative Saz</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Pensiounsversécherung</td><td class="p-2 border-b">~16 %</td></tr>
        <tr><td class="p-2 border-b">Krankeversécherung (Fleeg + Geldleeschtungen)</td><td class="p-2 border-b">~6 %</td></tr>
        <tr><td class="p-2 border-b">Ofhängegkeetsversécherung</td><td class="p-2 border-b">~1,4 %</td></tr>
        <tr><td class="p-2 border-b">Accident- an Aarbechtsmedezin</td><td class="p-2 border-b">~1 %</td></tr>
    </tbody>
</table>
<p class="text-sm text-slate-500">Déi genee Saz änneren a hänke vun Ärer Situatioun of. Bezitt Iech ëmmer op d'CCSS (ccss.public.lu) oder Är Fiduciaire fir déi aktuell Zuelen.</p>

<h2>D'Mindest- a Maximalbasis</h2>
<p>Et gëtt eng <strong>Mindestbasis</strong>: och mat enger niddreger Akommes bezilt Dir op eng Mindestbasis (gekoppelt un de soziale Mindestloun). Um anern Enn sinn d'Beiträg iwwer enger gewësser Akommes gedeckelt. Konkret generéiert och eng ganz bescheiden Akommes nach ëmmer Mindestbeiträg - an Ärer Tresorerie virauszegesinn.</p>

<h2>Wéi richteg virausplangen</h2>
<ul>
    <li><strong>Setzt ~25 %</strong> vun Ärem Gewënn fir d'Beiträg op d'Säit, zousätzlech zur Steier.</li>
    <li>Rechent mat enger <strong>Regulariséierung</strong>: wann Är Akommes klëmmt, kënnt eng Beitragsnobezuelung.</li>
    <li>Féiert eng kloer Comptabilitéit, fir Äre richtege Gewënn zu all Moment ze kennen.</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ze mierken</p>
    <p>Sozialbeiträg sinn keng Steier: si finanzéieren Är Pensioun an Är Krankeversécherung. Mä si weien ~25 % vun der Akommes - vun der éischter Rechnung un anzeplangen.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Behaalt Är Akommes an Echtzäit am Bléck</h3>
    <p class="text-primary-800 mb-4">faktur.lu verfollegt Ären Ëmsaz an Är Ausgaben an hëlleft Iech, Beiträg a Steier ouni Stress op d'Säit ze leeën.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-08-03 =====
            [
                'category_id' => 3,
                'translation_key' => 'fixer-tjm-freelance-luxembourg',
                'slug' => 'dagestaux-freelancer-letzebuerg-festleeen',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'title' => 'Säin Dagestaux als Freelancer zu Lëtzebuerg festleeën',
                'meta_title' => 'Dagestaux als Freelancer zu Lëtzebuerg festleeën: Method 2026 | faktur.lu',
                'meta_description' => 'Wéi Dir als Freelancer zu Lëtzebuerg Äre Dagestaux berechent: Käschten, Beiträg, net-fakturéierbar Deeg an eng einfach Method, fir Iech net ënner Wäert ze verkafen.',
                'excerpt' => 'Vill Freelancer leeën hiren Taux no Gefill fest a schaffen um Enn mat Verloscht. Hei eng einfach Method fir en Dagestaux ze berechnen, deen Är Käschten wierklech deckt.',
                'published_at' => '2026-08-03 09:00:00',
                'content' => <<<HTML
<p class="lead">Déi klassesch Fal fir Ufänger: e fréiere Loun duerch 20 Deeg deelen an doraus den Dagestaux maachen. D'Resultat: Dir schafft mat Verloscht ouni et ze mierken. Esou berechent Dir e realisteschen <strong>Dagestaux</strong> zu Lëtzebuerg.</p>

<h2>Äre Dagestaux ass net „Loun / 20"</h2>
<p>En Employé gëtt fir ~220 Deeg/Joer bezuelt, Congé a Krankheet abegraff, ouni Charge ze geréieren. E Freelancer hannergéint rechent net all Dag of an dréit all d'Charge. Äre Taux muss vill méi wéi Äre gewënschte „Loun" decken.</p>

<h2>Déi 4 Posten, déi derbäi gehéieren</h2>
<ul>
    <li><strong>Är gewënscht Nettoakommes.</strong> Wat Dir wierklech behale wëllt.</li>
    <li><strong>D'Sozialbeiträg (~25 %).</strong> Kuckt eisen Artikel iwwer d'<a href="/lb/blog/sozialbeitreg-onofhaengeg-ccss-letzebuerg-2026">CCSS-Beiträg</a>.</li>
    <li><strong>D'Akommessteier.</strong> Progressiv, virauszegesinn.</li>
    <li><strong>Är Betribskäschten.</strong> Software, Versécherung, Comptabel, Material, Formatioun, Reesen.</li>
</ul>

<h2>Déi wierklech fakturéierbar Deeg</h2>
<p>Vu ~252 Aarbechtsdeeg zitt Dir Congé, Feierdeeg, eventuell Krankheet a virun allem déi <strong>net-fakturéierbar</strong> Zäit of: Prospektioun, Administratioun, Devisen, Formatioun. An der Praxis fakturéiere vill Freelancer <strong>180 bis 200 Deeg pro Joer</strong>, am éischte Joer dacks manner.</p>

<h2>D'Berechnungsmethod</h2>
<ol>
    <li>Addéiert Äre <strong>Joresbedarf</strong>: Nettoakommes + Beiträg + virausgeplangte Steier + Betribskäschten.</li>
    <li>Deelt duerch Är <strong>realistesch Zuel u fakturéierbaren Deeg</strong>.</li>
    <li>Dir kritt Äre <strong>Mindestdagestaux</strong> - drënner verléiert Dir Suen.</li>
</ol>
<p><strong>Beispill:</strong> wann Äre Joresbedarf 90 000 € ass an Dir 180 Deeg fakturéiert, läit Äre Mindestdagestaux bei 500 €. All méi niddrege Taux bedeit mat Verloscht ze schaffen.</p>

<h2>Iwwer de Mindestsaz eraus: de Wäert</h2>
<p>De Mindestsaz deckt Är Käschten; Äre Schlusspräis hänkt duerno vun Ärem <strong>Wäert</strong> of: Expertise, Raritéit, Resultater fir de Client. Fakturéiert net Är Zäit - fakturéiert dat, wat Dir de Client gewanne loosst.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verfollegt Är Zäit an Är Rentabilitéit</h3>
    <p class="text-primary-800 mb-4">Mat faktur.lu verfollegt Dir fakturéiert Deeg, Ausgaben an Ëmsaz a prooft, ob Äre Dagestaux hält, wat en versprécht.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-08-10 =====
            [
                'category_id' => 2,
                'translation_key' => 'tva-deductible-depenses-professionnelles-luxembourg',
                'slug' => 'tva-ofzuchsfaeheg-betribsausgaben-letzebuerg',
                'cover_image' => 'https://images.unsplash.com/photo-1599658880436-c61792e70672?w=1200&h=630&fit=crop',
                'title' => 'D\'TVA op Betribsausgaben zu Lëtzebuerg zréckhuelen',
                'meta_title' => 'Ofzuchsfäeg TVA zu Lëtzebuerg: TVA op Är Akeef zréckhuelen | faktur.lu',
                'meta_description' => 'Wéi Dir zu Lëtzebuerg d\'TVA op Är Betribsausgaben zréckhuelt: Konditiounen, ofzéibar an net-ofzéibar Ausgaben, a Funktionéiere vun der Deklaratioun. Guide 2026.',
                'excerpt' => 'D\'TVA, déi Dir op Är berufflech Akeef bezilt, ass am Allgemengen erëmkréibar. Hei d\'Konditiounen, d\'Spezialfäll a wéi et an der Praxis funktionéiert.',
                'published_at' => '2026-08-10 09:00:00',
                'content' => <<<HTML
<p class="lead">Wann Dir zu Lëtzebuerg TVA-flichteg sidd, fakturéiert Dir Äre Clienten TVA (agesammelt TVA), mä Dir kënnt och d'<strong>TVA, déi Dir op Är berufflech Akeef bezuelt huet, zréckhuelen</strong> (ofzéibar TVA). Dat ass e zentrale Mechanismus - esou funktionéiert en.</p>

<h2>D'Prinzip: agesammelt TVA - ofzéibar TVA</h2>
<p>Bei all Deklaratioun féiert Dir dem Staat den Ënnerscheed tëscht der op Äre Verkeef <strong>agesammelter</strong> TVA an der op Äre Akeef <strong>ofzéibarer</strong> TVA of. Wann Dir an enger Period vill investéiert huet, kann d'ofzéibar TVA d'agesammelt souguer iwwerschreiden: Dir kritt dann en TVA-Avoir.</p>

<h2>D'Konditiounen fir d'TVA ofzezéien</h2>
<ul>
    <li>Dir sidd TVA-<strong>flichteg</strong> (net am Befreiungsregime).</li>
    <li>D'Ausgab ass <strong>berufflech</strong> a mat Ärer steierbarer Aktivitéit verbonnen.</li>
    <li>Dir hutt eng <strong>konform Rechnung</strong> mat der TVA an der TVA-Nummer vum Fournisseur.</li>
</ul>

<h2>Am Allgemengen ofzéibar Ausgaben</h2>
<p>Material a Verbrauchsgidder, berufflech Software an Abonnementer, Honoraren (Comptabel, Affekot), Loyer vu beruffleche Raim, Telekommunikatioun, Formatioun - soulaang se Ärer Aktivitéit déngen.</p>

<h2>D'Spezialfäll (limitéierten oder ausgeschlossenen Ofzuch)</h2>
<p>Verschidde Ausgaben sinn deelweis oder ganz vum Ofzuchsrecht ausgeschloss, virun allem wann se en privaten oder representativen Undeel hunn. Gemëscht genotzt Ausgaben (privat a berufflech), verschidde Empfangs- oder Gefierkäschten kënne limitéiert sinn. Am Zweiwel préift bei der <strong>AED</strong> oder Ärer Fiduciaire.</p>
<p class="text-sm text-slate-500">Déi genee Ofzuchsreegelen stinn am lëtzebuerger TVA-Gesetz a kënnen änneren. Bezitt Iech op d'AED oder e Fachmann.</p>

<h2>Wéi et an der Praxis leeft</h2>
<p>Dir erfaasst Är Ausgaben mat hirer TVA iwwer d'Period a verschreift d'Gesamt-ofzéibar-TVA an Är <strong>TVA-Deklaratioun</strong> (monatlech, vierteljärlech oder järlech, je no Regime). Eng gutt gefouert Ausgabecomptabilitéit verhënnert, datt erëmkréibar TVA leie bleift - bar Suen, déi soss verluer ginn.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verléiert keng erëmkréibar TVA méi</h3>
    <p class="text-primary-800 mb-4">faktur.lu erfaasst Är Ausgaben an hir TVA a bereet propper Daten fir Är Deklaratioun an Är Fiduciaire vir.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-08-17 =====
            [
                'category_id' => 1,
                'translation_key' => 'facture-acompte-luxembourg-regles-mentions',
                'slug' => 'akontosrechnung-letzebuerg-reegelen',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'title' => 'Akontosrechnung zu Lëtzebuerg: Reegelen, Mentiounen an TVA',
                'meta_title' => 'Akontosrechnung zu Lëtzebuerg: Reegelen a Mentiounen | faktur.lu',
                'meta_description' => 'Wéi Dir zu Lëtzebuerg eng Akontosrechnung erstellt: obligatoresch Mentiounen, Behandlung vun der TVA a Verbindung mat der Schlussrechnung. Praktesche Guide 2026.',
                'excerpt' => 'En Akont ze froen schützt Är Tresorerie, mä et bedeit eng richteg Rechnung mat eegene TVA-Reegelen. Esou maacht Dir et richteg.',
                'published_at' => '2026-08-17 09:00:00',
                'content' => <<<HTML
<p class="lead">En <strong>Akont</strong> virum Ufank vun enger Missioun ze froen ass e gudde Schutz vun der Tresorerie. Mä en Akont ass keng einfach „Bezuelung": en féiert zu enger richteger <strong>Akontosrechnung</strong> mat eegene Reegelen. Esou maacht Dir et zu Lëtzebuerg.</p>

<h2>Firwat en Akont fakturéieren?</h2>
<p>Bei enger laanger Leeschtung oder engem Projet séchert den Akont Är Tresorerie, bënnt de Client a reduzéiert de Risiko vun engem Net-Bezuelen. Besonnesch nëtzlech bei der <a href="/lb/blog/projet-fakturatioun-forfait-regie-letzebuerg">Projetfakturatioun</a>.</p>

<h2>D'Mentiounen vun enger Akontosrechnung</h2>
<p>Eng Akontosrechnung ass eng vollwäerteg Rechnung: si enthält déi üblech obligatoresch Mentiounen (Är an d'Kontaktdaten vum Client, Nummer an Datum, TVA-Nummer), zousätzlech:</p>
<ul>
    <li>De kloere Hiweis, datt et sech ëm en <strong>Akont</strong> handelt, an op wéi eng Bestellung/wéi en Devis e sech bezitt.</li>
    <li>De <strong>Akontosbetrag</strong> ouni TVA, déi applicabel TVA an de Betrag mat TVA.</li>
    <li>Eng sequenziell Nummer, wéi all Rechnung.</li>
</ul>

<h2>D'Behandlung vun der TVA</h2>
<p>Wichtege Punkt: bei enger Déngschtleeschtung gëtt d'TVA am Prinzip <strong>beim Empfang vum Akont fälleg</strong>, fir de kruten Betrag. Anescht gesot, Dir deklaréiert a féiert d'TVA op den Akont of, soubal en agaangen ass, ouni op d'Schlussrechnung ze waarden.</p>
<p class="text-sm text-slate-500">De Moment vun der TVA-Fällegkeet hänkt vun der Aart vun der Operatioun of. Am Zweiwel bei der AED oder Ärer Fiduciaire préiwen.</p>

<h2>D'Schlussrechnung</h2>
<p>Um Enn vun der Leeschtung stellt Dir d'<strong>Schlussrechnung</strong> aus. Si weist de Gesamtbetrag vun der Leeschtung a <strong>zitt den scho fakturéierten Akont</strong> (mat senger TVA) of, sou datt nëmmen de Rescht fakturéiert gëtt. Esou bezilt de Client ni zweemol dee selwechte Betrag, an déi ganz TVA bleift korrekt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Zesummegefaasst</p>
    <p>Akont = richteg Rechnung, TVA fälleg beim Empfang. D'Schlussrechnung weist de Total a zitt den scho fakturéierten Akont of.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Akonten a Schlussbetréi automatesch geréiert</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert Är Akontos- a Schlussrechnungen konform, mat der richteger TVA-Behandlung.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-08-24 =====
            [
                'category_id' => 2,
                'translation_key' => 'impot-independant-luxembourg-2026',
                'slug' => 'akommessteier-onofhaengeg-letzebuerg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'title' => 'Akommessteier fir Onofhängeger zu Lëtzebuerg: wéi et 2026 funktionéiert',
                'meta_title' => 'Akommessteier Onofhängeger Lëtzebuerg 2026: Guide | faktur.lu',
                'meta_description' => 'Wéi gëtt den Onofhängegen zu Lëtzebuerg besteiert? Gewënn, progressiven Tarif, vierteljärlech Virausbezuelungen, ofzéibar Charge. Guide 2026 fir d\'Steier virauszeplangen.',
                'excerpt' => 'D\'Steier vum Onofhängege rechent sech net op den Ëmsaz mä op de Gewënn, no engem progressiven Tarif. Hei dat Wesentlecht fir se gutt virauszeplangen.',
                'published_at' => '2026-08-24 09:00:00',
                'content' => <<<HTML
<p class="lead">No de <a href="/lb/blog/sozialbeitreg-onofhaengeg-ccss-letzebuerg-2026">Sozialbeiträg</a> ass deen anere groussen Posten vum Onofhängege d'<strong>Akommessteier</strong>. Gutt Noriicht: Dir gitt net op alles besteiert, wat Dir kritt. Esou funktionéiert et zu Lëtzebuerg.</p>

<h2>Besteiert gëtt de Gewënn, net den Ëmsaz</h2>
<p>D'Steierbasis vum Onofhängege ass de <strong>Gewënn</strong>: Ären Ëmsaz minus Är <strong>ofzéibar Betribscharge</strong> (Ausgaben, Akeef, Ofschreiwungen, Sozialbeiträg…). Wat Är Comptabilitéit méi rigoureux ass, wat Dir méi ofzitt, wat Iech zousteet.</p>

<h2>E progressiven Tarif</h2>
<p>D'lëtzebuerger Akommessteier ass <strong>progressiv</strong>: wat déi steierbar Akommes méi héich ass, wat de Grenzsteiersaz méi héich ass, a Stäffelen. En bescheidenen Deel vun der Akommes bleift steierfräi, dann eropklammen d'Saz bis zu engem héije Spëtzegrenzsaz. Derbäi kënnt de <strong>Bäitrag zum Beschäftegungsfong</strong> (Solidaritéitssteier). Är <strong>Steierklass</strong> (je no Familljesituatioun) beaflosst d'Berechnung.</p>
<p class="text-sm text-slate-500">Déi genee Stäffelen a Saz vum Tarif si gesetzlech festgeluecht a ginn regelméisseg ugepasst. Bezitt Iech op d'Steierverwaltung (ACD, impotsdirects.public.lu) oder Är Fiduciaire fir den aktuellen Tarif.</p>

<h2>D'vierteljärlech Virausbezuelungen</h2>
<p>D'ACD freet am Allgemengen <strong>vierteljärlech Steiervirausbezuelungen</strong>, geschat op Basis vun Ären zréckléiende Akommes. Bei der Joresdeklaratioun gëtt de Saldo ausgeglach: Dir bezuelt de Komplement oder kritt eng Erstattung. Wann Är Akommes klëmmt, rechent mat méi héije Virausbezuelungen am nächste Joer.</p>

<h2>D'Joresdeklaratioun</h2>
<p>All Joer fëllt Dir Är Steiererklärung aus (online iwwer MyGuichet oder mat Ärer Fiduciaire). Do verschreift Dir Äre Gewënn an Är ofzéibar Elementer. Hei zielen all d'Belege, déi Dir iwwert d'Joer gesammelt hutt.</p>

<h2>Wéi richteg virausplangen</h2>
<ul>
    <li><strong>Plangt d'Steier</strong> zousätzlech zu de ~25 % Sozialbeiträg: gitt net alles aus, wat Dir kritt.</li>
    <li>Behaalt all Är <strong>Käschtebelege</strong>: all ofzéibar Betribsausgab reduzéiert Är Steierbasis.</li>
    <li>Haalt Är Comptabilitéit aktuell, fir Äre richtege Gewënn ze kennen a Iwwerraschungen ze vermeiden.</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Behaalt Äre Gewënn d'ganzt Joer am Bléck</h3>
    <p class="text-primary-800 mb-4">faktur.lu verfollegt Recettes an Ausgaben fir Iech e kloere Bléck op Äre Gewënn ze ginn - a Steier a Beiträg virauszegesinn.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],

            // ===== 2026-08-31 =====
            [
                'category_id' => 5,
                'translation_key' => 'sarl-s-vs-entreprise-individuelle-luxembourg',
                'slug' => 'sarl-s-vs-eenzelentreprise-letzebuerg',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=630&fit=crop',
                'title' => 'SARL-S oder Eenzelentreprise zu Lëtzebuerg: wéi ee Statut wielen?',
                'meta_title' => 'SARL-S oder Eenzelentreprise zu Lëtzebuerg? Verglach | faktur.lu',
                'meta_description' => 'SARL-S oder Eenzelentreprise zu Lëtzebuerg: Haftung, Kapital, Comptabilitéit, Fiskalitéit. Kloere Verglach fir de richtege Statut 2026 ze wielen.',
                'excerpt' => 'Als Eenzelentreprise ufänken oder eng SARL-S grënnen? Béid hunn hir Virdeeler. Hei e kloere Verglach fir no Ärer Situatioun ze wielen.',
                'published_at' => '2026-08-31 09:00:00',
                'content' => <<<HTML
<p class="lead">Beim Start zu Lëtzebuerg stellt sech ëmmer erëm d'Fro: als <strong>Eenzelentreprise</strong> bleiwen oder eng <strong>SARL-S</strong> (vereinfacht Gesellschaft mat beschränkter Haftung) grënnen? Hei déi richteg Ënnerscheeder fir ze entscheeden.</p>

<h2>D'Eenzelentreprise</h2>
<p>Déi einfachst Form: Dir handelt an Ärem eegene Numm, ouni eng eege juristesch Persoun ze schafen. Kee Kapital, wéineg Formalismus.</p>
<p><strong>De Schlëssspunkt:</strong> Är Haftung ass <strong>onbeschränkt</strong> - Äert perséinlecht Verméigen kann bei beruffleche Scholden erugezu ginn.</p>

<h2>D'SARL-S (vereinfacht Gesellschaft)</h2>
<p>D'SARL-S gouf geschaf fir d'Entrepreneuriat ze erliichteren. Si ass <strong>natierleche Persoune</strong> reservéiert, mat engem <strong>reduzéierte Startkapital</strong> (tëscht 1 € an 12 000 €), a bitt eng op d'Aplagen <strong>beschränkt Haftung</strong>: Äert perséinlecht Verméigen ass am Prinzip geschützt.</p>
<p>Am Géigenzuch bréngt si méi Formalismus mat: Grënnung, duebel Comptabilitéit, Gesellschaftsflichten, an en Deel vum Gewënn muss enger gesetzlecher Reserve zougefouert ginn, bis d'Kapital vun enger klassescher SARL erreecht ass.</p>

<h2>Verglach</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Eenzelentreprise</th><th class="text-left p-2 bg-slate-100">SARL-S</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Haftung</td><td class="p-2 border-b">Onbeschränkt (perséinlecht Verméigen)</td><td class="p-2 border-b">Op d'Aplagen beschränkt</td></tr>
        <tr><td class="p-2 border-b">Kapital</td><td class="p-2 border-b">Keent</td><td class="p-2 border-b">1 € bis 12 000 €</td></tr>
        <tr><td class="p-2 border-b">Formalismus</td><td class="p-2 border-b">Minimal</td><td class="p-2 border-b">Méi héich (Gesellschaft)</td></tr>
        <tr><td class="p-2 border-b">Comptabilitéit</td><td class="p-2 border-b">Vereinfacht méiglech</td><td class="p-2 border-b">Duebel Comptabilitéit</td></tr>
        <tr><td class="p-2 border-b">Fiskalitéit</td><td class="p-2 border-b">Akommessteier</td><td class="p-2 border-b">Gesellschaftssteier</td></tr>
    </tbody>
</table>

<h2>Wéi wielen?</h2>
<ul>
    <li><strong>Aktivitéit mat niddregem Risiko, liichte Start</strong> → d'Eenzelentreprise duergeet dacks.</li>
    <li><strong>Finanziellen Risiko, Schutz vum Verméigen, „Gesellschafts"-Image</strong> → d'SARL-S ass interessant.</li>
</ul>
<p>D'Wiel huet dauerhaft juristesch a steierlech Konsequenzen: looss Iech vun enger <a href="/lb/blog/zesummenaarbecht-fiduciaire-letzebuerg-guide">Fiduciaire</a> oder der House of Entrepreneurship beroden, ier Dir entscheet.</p>
<p class="text-sm text-slate-500">Déi genee Konditiounen (Kapital, gesetzlech Reserve, Autorisatiounen) si gesetzlech festgeluecht an op guichet.lu sou wéi beim Luxembourg Business Registers (LBR) beschriwwen.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Wéi e Statut och ëmmer - fakturéiert konform</h3>
    <p class="text-primary-800 mb-4">Eenzelentreprise oder SARL-S, faktur.lu generéiert zu Lëtzebuerg konform Rechnungen an d'Exporter fir Äre Comptabel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis ausprobéieren</a>
</div>
HTML,
            ],
        ];
    }
}
