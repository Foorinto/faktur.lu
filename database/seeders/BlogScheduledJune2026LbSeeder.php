<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Luxembourgish translations of the 5 scheduled June 2026 long-tail articles.
 */
class BlogScheduledJune2026LbSeeder extends Seeder
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
            [
                'category_id' => 2,
                'title' => "AED-Steierkontroll Lëtzebuerg: Virbereedung 2026",
                'slug' => 'aed-steierkontroll-letzebuerg-2026-virbereedung',
                'translation_key' => 'controle-fiscal-aed-luxembourg-2026-preparation',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => 'AED-Steierkontroll Lëtzebuerg 2026: Virbereedungsguide | faktur.lu',
                'meta_description' => "Alles iwwer AED-Steierkontrollen zu Lëtzebuerg: Virbereedung, FAIA, néideg Dokumenter, typeschen Oflaaf. Praxisguide 2026 fir Freelancer a KMU.",
                'excerpt' => "Eng Notifikatioun vun enger AED-Kontroll beoneit all Selbststänneg zu Lëtzebuerg. Hei wat virzebereeden ass a wéi faktur.lu Iech hëlleft, d'Kontroll an 30 Minutten anstatt 3 Deeg ze bestoen.",
                'published_at' => '2026-06-01 09:00:00',
                'content' => <<<HTML
<p class="lead">Eng Notifikatioun vun der <strong>Verwaltung fir Eintragung, Domänen an TVA (AED)</strong> beoneit all Selbststänneg a KMU-Chef zu Lëtzebuerg. Mat enger gudder Virbereedung dauert eng Steierkontroll awer just e puer Stonnen ouni Stress. Hei wéi Iech 2026 virzebereeden.</p>

<h2>Wien ass vun enger AED-Kontroll betraff?</h2>

<p>All Entreprise, déi zu Lëtzebuerg TVA-aschriwwen ass, kann vun der AED kontrolléiert ginn. An der Praxis zielt d'Verwaltung op:</p>

<ul>
    <li><strong>Séier wuesseg Entreprisen</strong> mat ongewéinleche Chiffre d'Affaires-Schwankungen</li>
    <li><strong>Risiko-Secteuren</strong> identifizéiert vun der AED (Commerce, Restauratioun, BTP, Berodung)</li>
    <li><strong>Atypesch TVA-Deklaratiounen</strong> (widderholend TVA-Krediter, grouss innergemeinschaftlech Operatiounen)</li>
    <li><strong>Zoufalls-Kontrollen</strong>: tëschent 3% an 5% vun den Entreprisen ginn all Joer no statistesche Krittären kontrolléiert</li>
</ul>

<p>Eng Kontroll kann <strong>déi lescht 5 Joer</strong> ëmfaassen (Standard-Verjährung, 10 Joer bei vermutetem Bedruch).</p>

<h2>Déi 3 Aarten vun AED-Kontroll</h2>

<h3>1. Schrëftlech Kontroll</h3>

<p>D'AED freet Iech, Dokumenter per Bréif oder elektronesch ze schécken. Keen physeschen Besuch. Et ass den heefegsten an mannst belaaschtegen Typ.</p>

<h3>2. Vu-Plaz-Kontroll</h3>

<p>En AED-Inspekter kënnt an Är Lokaler fir d'Buchhaltung, Rechnungen a Belegspréiwen ze kontrolléieren. Annoncéiert per Bréif mat minimal 8 Deeg Frist.</p>

<h3>3. Ongekënnegt Kontroll</h3>

<p>Selten, fir schwéier Bedruch-Verdäichter reservéiert. Den Inspekter erschéngt ouni Virankündegung. Dir hutt d'Recht op d'Präsenz vun Ärem Fiduciaire während der Kontroll.</p>

<h2>Dokumenter, déi d'AED ufroe kann</h2>

<p>D'Lëscht varéiert no Aktivitéit, mä folgend Dokumenter ginn systematesch kontrolléiert:</p>

<ul>
    <li><strong>All ausgestellt an empfaangen Rechnungen</strong> vun der kontrolléierter Period, mat obligatoresche LIVA-Ugaben</li>
    <li><strong>D'FAIA-Datei</strong> (Standardiséiert Auditdatei vun der AED) am Format 2.01</li>
    <li><strong>D'Akommes-/Ausgabebuch</strong> oder déi voll Buchhaltung no Ärem Regime</li>
    <li><strong>Déi monatlech oder véierjährlech TVA-Deklaratiounen</strong></li>
    <li><strong>Beruffleche Bankrelevéë</strong> vun der Period</li>
    <li><strong>Belegspréif fir ofgezunne Ausgaben</strong> (Spesennotzen, Liwwerantefakturen)</li>
    <li><strong>Wichteg Client/Liwwerant-Kontrakter</strong></li>
    <li><strong>Beweis vun der Autoliquidatioun</strong> fir innergemeinschaftlech B2B-Operatiounen (TVA-Nummeren iwwer VIES validéiert)</li>
</ul>

<h2>FAIA: de kritesche Kontrollpunkt</h2>

<p>D'<strong>FAIA</strong> ass zënter 2020 de Pivot vun all AED-Kontrolle ginn. Wann Dir keng FAIA konform mat der Versioun 2.01 produzéiere kënnt, betruecht den Inspekter Är Buchhaltung als net Standard-konform.</p>

<p>D'FAIA ass eng strukturéiert XML-Datei mat:</p>

<ul>
    <li>Entreprise-Header (Numm, TVA-Nummer, RCS, Period)</li>
    <li>All Verkafsrechnungen vun der kontrolléierter Period</li>
    <li>Zougeheereg Buchhaltungs-Eintraagen</li>
    <li>Soll/Avoir-Totaler pro Bewegung</li>
</ul>

<p>Wann Dir mat Excel oder engem System fakturéiert, dee keng FAIA generéiert, musst Dir se manuell rekonstruéieren - wat <strong>e puer Aarbechtsdeeg</strong> daueren kann a Feeler ausstellt.</p>

<h2>Wéi eng Vu-Plaz-Kontroll oflaaft</h2>

<ol>
    <li><strong>Schrëftlech Notifikatioun</strong> mat minimal 8 Deeg Frist (ausser ongekënnegt Kontroll)</li>
    <li><strong>Ukunft vum/de AED-Inspekter(en)</strong> mat enger Kontrollkommissioun</li>
    <li><strong>Dokumenter-Ufro</strong> no virgeplangter Lëscht</li>
    <li><strong>Verifikatioun vun den Eintraagen</strong> a Verglach mat TVA-Deklaratiounen</li>
    <li><strong>Gespréich</strong> iwwer net-Standard-Operatiounen oder fonnt Differenzen</li>
    <li><strong>Notifikatioun vun de Conclusiounen</strong> per Bréif bannent 60 Deeg</li>
</ol>

<p>Dir hutt dann <strong>30 Deeg fir d'Conclusiounen ze kontestéieren</strong> per Beschwerde beim AED-Direkter.</p>

<h2>Déi 5 Feeler, déi deier ginn</h2>

<ol>
    <li><strong>Net-sequentiell Rechnungsnummeréierung</strong> (Artikel 61 LIVA): Lücken oder Doubel = Vermutung vun verdéckelte Recetten</li>
    <li><strong>Fehlend LIVA-Ugaben</strong> op Rechnungen: TVA-Nummer, RCS, Matricule, Ausstellungsdatum, etc.</li>
    <li><strong>Net-dokumentéiert B2B-Autoliquidatioun</strong>: ouni VIES-Validéierung vun der Client-TVA-Nummer kann d'AED als steierflichteg Operatioun requalifizéieren</li>
    <li><strong>Fehlend oder onliesbar Ausgabe-Belege</strong> (handgeschriwwe Notiz, verbleechte Beleg)</li>
    <li><strong>Inkonsistenzen tëschent TVA-Deklaratioun a Rechnungen</strong>: och e klengen Ënnerscheed léist eng vertéift Iwwerpréiwung aus</li>
</ol>

<h2>D'Kontroll an 5 Schrëtt virzebereeden</h2>

<p>Egal ob Freelancer oder KMU, hei d'Checkleeschten ab Notifikatioun:</p>

<ol>
    <li><strong>FAIA virbereeden</strong> fir déi gefrote Period - idealerweis mat engem Klick aus Ärer Rechnungs-Software</li>
    <li><strong>Dréckt oder exportéiert</strong> all ausgestallte Rechnungen (PDF/A fir legal Archivéierung)</li>
    <li><strong>Konsistenz iwwerpréiwen</strong> mat Äre gedeposéierten TVA-Deklaratiounen</li>
    <li><strong>E Dossier pro Mount</strong>: Rechnungen + Bankrelevéë + zougehéireg TVA-Deklaratioun</li>
    <li><strong>Äre Fiduciaire informéieren</strong> a him froen, präsent ze sinn (recommandéiert)</li>
</ol>

<h2>Wéi faktur.lu Iech virbereet</h2>

<p>Mat enger Lëtzebuerg-konformer Rechnungsplattform bereeden Dir d'AED-Kontroll an <strong>30 Minutten anstatt méi Deeg</strong> vir:</p>

<ul>
    <li>Automatesch sequentiell Nummeréierung konform <strong>Artikel 61 LIVA</strong></li>
    <li>Obligatoresch LIVA-Ugaben automatesch op all Rechnung generéiert</li>
    <li>Echtzäit-VIES-Validéierung fir innergemeinschaftlech B2B-Operatiounen</li>
    <li><strong>FAIA 2.01</strong>-Export per Klick fir all Period</li>
    <li><strong>PDF/A</strong>-Archivéierung vu Rechnungen (legal 10-Joer-Norm, Art. 16 Code de commerce)</li>
    <li>Fiduciaire-Portal: Äre Fiduciaire kritt alles ouni E-Mail-Hin-an-Hier</li>
</ul>

<p><a href="/lb/register" class="text-primary-500 hover:underline font-medium">Gratis mat faktur.lu starten</a> an Är nächst Kontroll ouni Stress virbereeden.</p>

<h2>FAQ - AED-Steierkontroll</h2>

<h3>Wéi laang dauert eng AED-Kontroll?</h3>
<p>Schrëftlech Kontroll: 2 bis 4 Wochen (no Sendezäit). Vu-Plaz-Kontroll: typesch 1 bis 3 Deeg an der Entreprise + 30 bis 60 Deeg AED-intern Analyse.</p>

<h3>Kann ech eng Kontroll verweigeren?</h3>
<p>Nee. Kontrollverweigerung gëtt mat enger administrativer Strof sanktionéiert a Bösheet vermutet. Dir kënnt awer aus berechtegtem Grond eng Verréckung froen (Krankheet, lang Ofwiesenheet) an d'Präsenz vun Ärem Fiduciaire verlaangen.</p>

<h3>Wéi eng Strofen drohten bei Feeler?</h3>
<p>Variabel: 10% bis 50% vun der net-deklaréierter TVA bei gudden Glab-Versäumnesser, bis 200% bei nogewisenem Bedruch. Pauschalstrofen (50 EUR bis 25.000 EUR) kënnen och fir formell Versäumnesser ugewant ginn.</p>

<h3>Kann d'Kontroll al Joeren ëmfaassen?</h3>
<p>Jo: Verjährung 5 Joer bei gudden Glab-Versäumnesser, 10 Joer bei Bedruch-Verdacht. Drëfir ass 10-järeg Archivéierung entscheedend (Flicht, Art. 16 Code de commerce).</p>

<h3>Wat maachen wann ech net mat de Conclusiounen accord sinn?</h3>
<p>Dir hutt 30 Deeg, fir schrëftlech Beschwerde beim AED-Direkter ze maachen, mat Argumenter a Beleger. Bei Refus Recours virum Verwaltungsgeriicht bannent 3 Méint.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Bereed Är nächst Kontroll haut vir</h3>
    <p class="text-primary-800 mb-4">Automatesch Nummeréierung, FAIA 2.01 per Klick, konform LIVA-Ugaben, 10-Joer-PDF/A-Archivéierung. AED-Kontroll gëtt Routine.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis starten</a>
</div>
HTML,
            ],

            [
                'category_id' => 2,
                'title' => "FAIA Lëtzebuerg: kompletten Guide vun der Auditdatei 2026",
                'slug' => 'faia-letzebuerg-komplette-guide-auditdatei-2026',
                'translation_key' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
                'meta_title' => 'FAIA Lëtzebuerg 2026: kompletten Guide AED-Auditdatei | faktur.lu',
                'meta_description' => "FAIA fir Lëtzebuerger Freelancer a KMU erkläert: wat et ass, wien betraff ass, wéi eng konform FAIA 2.01 generéieren. Gratis-Validator inklusiv.",
                'excerpt' => "FAIA ass d'Format, dat vun der AED bei Steierkontrolle gefuerdert gëtt. Hei alles wichtegt 2026: XML-Struktur, Betraffener, Generéierung a gratis Validéierung.",
                'published_at' => '2026-06-08 09:00:00',
                'content' => <<<HTML
<p class="lead">D'<strong>FAIA</strong> (Standardiséiert Auditdatei vun der AED) ass de Standard, dee vun der lëtzebuerger Steierverwaltung bei all TVA-Kontroll gefuerdert gëtt. Trotzdem wëssen wéineg Freelancer a KMU, wat se wierklech enthält. Dëse Guide erkläert alles 2026.</p>

<h2>Wat ass d'FAIA?</h2>

<p>D'<strong>FAIA</strong> ass eng strukturéiert XML-Datei, baséiert op der internationaler <strong>SAF-T</strong>-Norm (Standard Audit File for Tax) entwéckelt vun der OECD. Si versammelt all d'Buchhaltungsdaten vun engem Entreprise iwwer eng bestëmmte Period, an engem Format, dat vun AED-Tools maschinell liesbar ass.</p>

<p>Déi aktuell gülteg Versioun ass <strong>Versioun 2.01</strong>, publizéiert vun der AED am Joer 2020. Dat offiziellt XSD-Schema ass op der Websäit vun der Verwaltung verfügbar an bei Konformitéits-Streidereien massgebend.</p>

<h2>Wien muss eng FAIA bereetstellen?</h2>

<p>All Entreprise, déi TVA-aschriwwen zu Lëtzebuerg ass, <strong>muss bei enger Steierkontroll eng konform FAIA produzéieren kënnen</strong>. Dat betrëfft:</p>

<ul>
    <li><strong>Freelancer a Selbststänneg</strong>, inklusiv ënner TVA-Franchise-Regime</li>
    <li><strong>KMU</strong> ënner allen Regimes (vereinfacht oder normal)</li>
    <li><strong>Handelsgesellschaften</strong> (SARL, SA, SAS)</li>
    <li><strong>Veräiner</strong>, déi TVA-flichteg wirtschaftlech Aktivitéit ausüben</li>
</ul>

<p>D'Flicht ass net monatlech Erstellen - et duergeet, se <strong>op AED-Ufro</strong> bannent enger reasonabler Frist (typesch 15 Deeg no Notifikatioun) produzéieren ze kënnen.</p>

<h2>Wat enthält eng FAIA?</h2>

<p>Eng FAIA 2.01 ass an méi Pflichtsektiounen strukturéiert:</p>

<h3>1. Header</h3>

<ul>
    <li>Numm, TVA-Nummer, RCS, Matricule</li>
    <li>Entreprise-Adress</li>
    <li>Ofgedeckte Period (Start- an Enddatum)</li>
    <li>Erstellungsdatum vun der Datei</li>
    <li>Währung (EUR)</li>
</ul>

<h3>2. Kontoplang (MasterFiles)</h3>

<p>Lëscht vun de benotzte Konten (Clienten, Liwweranten, allgemeng Konten). Fir Freelancer oder kleng Struktur kann dës Sektioun minimal sinn.</p>

<h3>3. Verkafsrechnungen (SalesInvoices)</h3>

<ul>
    <li>Rechnungsnummer (sequentiell, konform Artikel 61 LIVA)</li>
    <li>Ofschlossdatum</li>
    <li>Client: Numm, TVA-Nummer, Adress</li>
    <li>Rechnungs-Linne: Beschreiwung, Quantitéit, Stéckpräis, TVA</li>
    <li>Total Hors-TVA, TVA, TVA-c</li>
</ul>

<h3>4. Bewegungen (GeneralLedgerEntries)</h3>

<p>Zougehéireg Buchhaltungs-Eintraagen (Soll/Avoir pro Konto).</p>

<h3>5. Footer</h3>

<p>Kontrolltotaler: Gesamtsoll, Gesamt-Avoir, Transaktiounsunzuel.</p>

<h2>Déi 4 heefegst FAIA-Feeler</h2>

<ol>
    <li><strong>Rechnungsnummeréierung net konform</strong> mat Artikel 61 LIVA (Lücken oder Doubel). De offiziellen Validator weist d'Datei zréck.</li>
    <li><strong>Fehlend Pflichtfelder</strong>: Client-TVA-Nummer, voll Adress, Autoliquidatioun-Vermierk fir innergemeinschaftlech B2B.</li>
    <li><strong>Inkonsistent Totaler</strong> tëschent Sektiounen (z. B. Rechnungssumm ≠ deklaréiert Total Sales Invoices).</li>
    <li><strong>Falsche Datum-Format</strong>: FAIA verlaangt ISO 8601 (YYYY-MM-DD), net DD/MM/YYYY.</li>
</ol>

<h2>Wéi eng konform FAIA generéieren</h2>

<p>Dräi Optiounen no Ärer Situatioun:</p>

<h3>Optioun 1 - FAIA-native Rechnungs-Software</h3>

<p>Déi einfachst a sécherst Method. Eng Software wéi <a href="/lb" class="text-primary-500 hover:underline font-medium">faktur.lu</a> generéiert FAIA 2.01 op Ufro, fir all Period, mat engem Klick. D'Datei ass automatesch XSD-konform.</p>

<h3>Optioun 2 - Export aus Buchhaltungs-Software</h3>

<p>Sage BOB 50, Sage 100 an déi meescht professionell Buchhaltungs-Software erlaben FAIA-Export. Iwwerpréift mat Ärem Editeur, datt d'Versioun 2.01 ënnerstëtzt gëtt.</p>

<h3>Optioun 3 - Manuell Konstruktioun (ofgerot)</h3>

<p>Theoretesch mat XML-Entwéckler méiglech, awer Feeler-anfälleg. Reservéiert fir extrem Fäll (Legacy-Daten, Migratioun).</p>

<h2>FAIA gratis validéieren</h2>

<p>Ier Dir Är FAIA un d'AED schéckt, validéiert se géint dat offiziellt Schema fir Feeler virum Inspekter z'entdecken.</p>

<p>faktur.lu bitt e <a href="/lb/faia-validator" class="text-primary-500 hover:underline font-medium">gratis FAIA-Validator</a>, deen:</p>

<ul>
    <li>XML-Konformitéit mam AED-XSD-Schema 2.01 prüft</li>
    <li>Fehlend Pflichtfelder erkennt</li>
    <li>Totaler-Konsistenz prüft</li>
    <li>Sequentialitéit vun de Rechnungsnummeren prüft</li>
    <li>Keng Donnéeën späichert (d'Datei bleift op Ärer Maschin)</li>
</ul>

<h2>FAQ - FAIA</h2>

<h3>Muss ech all Joer eng FAIA schécken?</h3>
<p>Nee. FAIA gëtt nëmme <strong>op AED-Ufro</strong> bei enger Kontroll erstallt. Dir musst se awer séier (bannent 15 Deeg) generéiere kënnen.</p>

<h3>Wat passéiert wann ech keng FAIA produzéiere kann?</h3>
<p>Den Inspekter betruecht Är Buchhaltung als net Standard-konform an kann eng <strong>administrativ Strof</strong> uwennen oder Äre Chiffre d'Affaires pauschal schätzen (zu Ären Lasten). Am schlëmmsten Fall: TVA-Nopassung mat Zouschlag.</p>

<h3>Ass FAIA bei TVA-Franchise obligatoresch?</h3>
<p>Jo. Och ënner Franchise (Artikel 56 ter LIVA) musst Dir op Ufro eng FAIA produzéiere kënnen. D'FAIA enthält dann "TVA net uwennbar"-Vermierker pro Rechnung.</p>

<h3>Wéi weess ech, datt meng FAIA virun enger Kontroll konform ass?</h3>
<p>Benotzt e <a href="/lb/faia-validator" class="text-primary-500 hover:underline font-medium">FAIA-Validator</a>, dee se mam offizielle AED-XSD-Schema 2.01 vergläicht. Gratis, ouni Inskriptioun, dauert 5 Sekonnen.</p>

<h3>Kann mäi Fiduciaire d'FAIA fir mech generéieren?</h3>
<p>Jo, Äre Fiduciaire kann FAIA aus sengem eegene Tool generéieren oder Är iwwer e Fiduciaire-Portal ofruffen. Mat faktur.lu invitéiert Dir Äre Fiduciaire mat Liesberechtegung an hien zougräift mat engem Klick op d'FAIA.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 mat engem Klick, AED-konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert Är FAIA fir all Period, automatesch géint dat offiziellt XSD-Schema validéiert. Inklusiv vum Gratis-Plang un.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis testen</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artikel 21 LIVA: Autoliquidatioun B2B intra-EU fir Lëtzebuerger Freelancer",
                'slug' => 'artikel-21-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg',
                'translation_key' => 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200&h=630&fit=crop',
                'meta_title' => 'Artikel 21 LIVA: B2B-Autoliquidatioun intra-EU Lëtzebuerg | faktur.lu',
                'meta_description' => "Fakturéiert Dir e B2B-Client an Europa? Artikel 21 LIVA verlaangt d'Autoliquidatioun. Hei d'Regelen, Pflichtvermierker, VIES-Validéierung an Faller 2026.",
                'excerpt' => "Sidd Dir Lëtzebuerger Freelancer a fakturéiert e B2B-Client a Frankräich, Belsch oder Däitschland? Artikel 21 LIVA applizéiert sech: Autoliquidatioun TVA. Hei d'Regelen, Pflichtvermierker a wéi sech net z'irren.",
                'published_at' => '2026-06-15 09:00:00',
                'content' => <<<HTML
<p class="lead">Sidd Dir Lëtzebuerger Freelancer a fakturéiert e B2B-Client a Frankräich, Däitschland oder Belsch? <strong>Artikel 21 LIVA</strong> applizéiert sech: Autoliquidatioun TVA. Wann Dir de Vermierk vergiesst oder iertümlech lëtzebuerger TVA fakturéiert, riskéiert Dir eng Steier-Nopassung. Hei déi kloer Regelen.</p>

<h2>Artikel 21 LIVA an Kuerz</h2>

<p>Den <strong>Artikel 21 vum lëtzebuerger TVA-Gesetz (LIVA)</strong> setzt dee europäesche Reverse-Charge-Prinzip fir Dienstleeschtungen tëschent Steierflichtegen an zwee EU-Memberstaaten ëm.</p>

<p>Konkret, wann e Lëtzebuerger Freelancer eng Dienstleeschtung un e Betrib an engem aneren EU-Land fakturéiert:</p>

<ul>
    <li>D'Lëtzebuerger TVA ass <strong>net geschëllt</strong></li>
    <li>De auslännesche Client <strong>autoliquidéiert d'TVA</strong> a sengem eegene Land zum applikabelen Lokal-Saaz</li>
    <li>D'Rechnung vermierkt explizitt <strong>"Autoliquidation, article 21 LIVA"</strong></li>
</ul>

<h2>Wéini applizéiert sech Artikel 21 LIVA?</h2>

<p>Dräi kumulativ Konditiounen mussen erfëllt sinn:</p>

<ol>
    <li><strong>Dienstleeschtung</strong> (net Waren - aner Regelen)</li>
    <li><strong>Profi-Client (B2B)</strong> an engem aneren EU-Memberstaat</li>
    <li><strong>Gülteg innergemeinschaftlech TVA-Nummer</strong> vum Client (obligatoresch VIES-Validéierung)</li>
</ol>

<p>Wann eng Konditioun feelt, ännert d'Regel:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situatioun</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA-Regel</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B intra-EU mat gülteger TVA-Nummer</td><td class="border border-gray-300 px-4 py-2"><strong>Autoliquidatioun (Art. 21 LIVA)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C (Privatperson) intra-EU</td><td class="border border-gray-300 px-4 py-2">Lëtzebuerger TVA 17% (oder OSS bei Schwell-Iwwerschratt)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B intra-EU ouni validéiert TVA-Nummer</td><td class="border border-gray-300 px-4 py-2">Lëtzebuerger TVA 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Drëtt-Land-Client (B2B oder B2C)</td><td class="border border-gray-300 px-4 py-2">Befreiung mat ugepasstem Vermierk</td></tr>
    </tbody>
</table>

<h2>D'VIES-Validéierung, kriteschen Schrëtt</h2>

<p>VIES (VAT Information Exchange System) ass den europäesche Service fir d'Echtzäit-Iwwerpréiwung vun enger innergemeinschaftlecher TVA-Nummer.</p>

<p>Virun enger Autoliquidatioun-Rechnung <strong>musst Dir onbedingt</strong> d'Client-TVA-Nummer iwwer VIES validéieren. Ass d'Nummer um Rechnungsdatum net gülteg, betruecht d'AED d'Leeschtung als zu Lëtzebuerg steierflichteg an fuerdert net-erhuewenen TVA no.</p>

<p>De offizielle VIES-Service ass op <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a> erreechbar. An der Praxis soll Är Rechnungs-Software dat automatesch pro Rechnung maachen.</p>

<h2>Pflichtvermierker op der Rechnung</h2>

<p>Eng Autoliquidatioun-Rechnung no Artikel 21 LIVA muss zousätzlech zu üblechen Vermierker enthalen:</p>

<ul>
    <li>Är <strong>lëtzebuerger TVA-Nummer</strong> (LU + 8 Ziffern)</li>
    <li>D'<strong>Client-TVA-Nummer</strong> (Land-Préfix + Ziffern)</li>
    <li>Déi genau Erwähnung: <strong>"Autoliquidation, article 21 LIVA"</strong> (oder Äquivalent an Client-Sprooch)</li>
    <li><strong>Keng TVA bäigefügt</strong> op Total (TVA = 0 EUR)</li>
    <li>Total Hors-TVA = Total TVA-c</li>
</ul>

<p><strong>Wichteg</strong>: De Vermierk "Autoliquidatioun" muss siichtbar a verständlech sinn. D'AED huet schonn Entreprisen sanktionéiert, déi vague Formuléierungen wéi "TVA net uwennbar" ouni Präzisioun benotzt hunn.</p>

<h2>Konkret Beispill</h2>

<p>Marie ass freelance UX-Designerin zu Lëtzebuerg-Stad. Si fakturéiert <strong>2.500 EUR Hors-TVA</strong> un <strong>Acme SA</strong>, Pariser Agence (TVA-Nummer FR12345678901, iwwer VIES validéiert).</p>

<p>Hir Rechnung enthält:</p>

<ul>
    <li><strong>Aussteller</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Empfänger</strong>: Acme SA, 10 rue de Rivoli 75001 Paris, FR12345678901</li>
    <li><strong>Leeschtung</strong>: UX-Design Websäit - 2.500,00 EUR</li>
    <li><strong>TVA (0%)</strong>: 0,00 EUR</li>
    <li><strong>Total TVA-c</strong>: 2.500,00 EUR</li>
    <li><strong>Vermierk</strong>: <em>"Autoliquidation, article 21 LIVA - TVA geschëllt vum Empfänger a sengem Land."</em></li>
</ul>

<p>Acme SA deklaréiert d'franséisch TVA (20%) a senger eegener TVA-Deklaratioun, souwuel als erhuewen TVA wéi och als ofziehbar TVA (neutral Operatioun fir si).</p>

<h2>Déi 3 deier Feeler</h2>

<ol>
    <li><strong>VIES-Validéierung vergiess</strong>: Wann d'TVA-Nummer net gülteg oder suspendéiert ass, musst Dir lëtzebuerger TVA (17%) erhiewen. Ouni dass de Client bezilt, geet et aus Ärer Täsch.</li>
    <li><strong>Fehlend oder vague "Autoliquidatioun"-Vermierk</strong>: D'AED requalifizéiert als zu Lëtzebuerg steierflichteg.</li>
    <li><strong>Falsch buchhaltungstechnesch Behandlung</strong>: D'Operatioun muss an der lëtzebuerger TVA-Deklaratioun (Rubrik innergemeinschaftlech Dienstleeschtungen) an an der <strong>VIES-zesummefaassender Deklaratioun</strong> (monatlech oder véierjährlech) erscheinen.</li>
</ol>

<h2>Wéi faktur.lu Iech schützt</h2>

<p>faktur.lu erkennt automatesch Autoliquidatioun-Konditiounen an applizéiert Artikel 21 LIVA:</p>

<ul>
    <li><strong>Echtzäit-VIES-Validéierung</strong> bei Aginn vun engem B2B-intra-EU-Client</li>
    <li>Vermierk <strong>"Autoliquidation, article 21 LIVA"</strong> automatesch op der Rechnung</li>
    <li><strong>TVA op 0 forcéiert</strong> fir betraffen Leeschtungen, Berechnungen geprüft</li>
    <li><strong>VIES-zesummefaassend Deklaratioun</strong> automatesch um Period-Enn erstallt</li>
    <li>Vermierker iwwersat an Client-Sprooch (FR, DE, EN, PT)</li>
</ul>

<p>Dir vermeit Feeler a fakturéiert auslännesch Clienten voll konform.</p>

<h2>FAQ - Artikel 21 LIVA</h2>

<h3>An wann mäi Client keng TVA-Nummer huet?</h3>
<p>Ouni iwwer VIES validéiert TVA-Nummer applizéiert sech keng Autoliquidatioun. Dir musst lëtzebuerger TVA (17% Standard) fakturéieren. Datselwecht fir Privatpersounen (B2C).</p>

<h3>Wéi ass et mat Waren-Liwwerungen (net Dienstleeschtungen)?</h3>
<p>Fir innergemeinschaftlech B2B-Warenverkeef gëllt <strong>Artikel 43 LIVA</strong> (innergemeinschaftlech befreit Liwwerungen). Regelen a Vermierker ënnerscheeden sech.</p>

<h3>Muss d'Operatioun der AED gemellt ginn?</h3>
<p>Jo. D'Operatioun erschéngt an:</p>
<ul>
    <li>Ärer <strong>lëtzebuerger TVA-Deklaratioun</strong> (Feld innergemeinschaftlech Dienstleeschtungen)</li>
    <li>Der <strong>VIES-zesummefaassender Deklaratioun</strong> (monatlech bei CA > 50.000 EUR iwwer 12 Méint, soss véierjährlech)</li>
</ul>

<h3>Kann de Client d'Autoliquidatioun verweigeren?</h3>
<p>Nee, et ass eng EU-Pflichtregel. Dir kënnt awer d'Fakturatioun verweigeren, wann dem Client eng validéiert TVA-Nummer feelt (oder mat Standard-lëtzebuerger TVA fakturéieren).</p>

<h3>Wat passéiert bei enger AED-Kontroll?</h3>
<p>D'AED prüft de <strong>VIES-Validéierungs-Beweis</strong> um Rechnungsdatum (e Screenshot oder automatescht Log duergeet). Ouni dëse Beweis gëtt d'Operatioun als steierflichteg requalifizéiert an Dir schëllt TVA + Strofen.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">D'Autoliquidatioun, ouni Feeler-Risiko</h3>
    <p class="text-primary-800 mb-4">faktur.lu validéiert VIES an Echtzäit, applizéiert Artikel 21 LIVA automatesch, generéiert Är zesummefaassend Deklaratiounen. Méi keen Stress fir europäesch Clienten.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis starten</a>
</div>
HTML,
            ],

            [
                'category_id' => 1,
                'title' => "Lëtzebuerger TVA 2026: déi 4 Sätz (17%, 14%, 8%, 3%) erkläert",
                'slug' => 'letzebuerger-tva-2026-veier-satz-17-14-8-3-erklaert',
                'translation_key' => 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'cover_image' => 'https://images.unsplash.com/photo-1543286386-713bdd548da4?w=1200&h=630&fit=crop',
                'meta_title' => 'Lëtzebuerger TVA 2026: Guide vun de 4 Sätz (17%, 14%, 8%, 3%) | faktur.lu',
                'meta_description' => "Wéi en TVA-Saaz zu Lëtzebuerg 2026? Standard 17%, Zwëschen 14%, reduzéiert 8%, super-reduzéiert 3%. Praxisguide mat konkrete Beispiller pro Secteur.",
                'excerpt' => "Lëtzebuerg applizéiert 4 TVA-Sätz. Wéi ee fir wat? Hei de Praxisguide 2026 mat konkrete Beispiller fir all Saaz: Dienstleeschtungen, Liewensmëttel, Bicher, Gastronomie, Gas, Strom.",
                'published_at' => '2026-06-22 09:00:00',
                'content' => <<<HTML
<p class="lead">Lëtzebuerg applizéiert 2026 <strong>4 verschidden TVA-Sätz</strong>: 17% (Standard), 14% (Zwëschen), 8% (reduzéiert) an 3% (super-reduzéiert). De richtege Saaz ze wielen ass entscheedend fir d'Steierkonformitéit. Hei de komplette Guide 2026 mat konkrete Beispiller.</p>

<h2>Déi 4 TVA-Sätz 2026</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Saaz</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Bezeechnung</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Applikatioun</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td><td class="border border-gray-300 px-4 py-2">Standard</td><td class="border border-gray-300 px-4 py-2">Majoritéit vun de Wueren a Dienstleeschtungen</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td><td class="border border-gray-300 px-4 py-2">Zwëschen</td><td class="border border-gray-300 px-4 py-2">Wäin, bestëmmt Brennstoffer</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td><td class="border border-gray-300 px-4 py-2">Reduzéiert</td><td class="border border-gray-300 px-4 py-2">Gas, Strom, Coiffeur, bestëmmt Aarbechten</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td><td class="border border-gray-300 px-4 py-2">Super-reduzéiert</td><td class="border border-gray-300 px-4 py-2">Liewensmëttel, Bicher, Medikamenter, ëffentlechen Transport</td></tr>
    </tbody>
</table>

<p>Dës Sätz sinn zënter dem <strong>1. Januar 2024</strong> a Kraaft (de Standardsaaz koum no der temporärer Senkung 2023 vun 16% op 17% zréck).</p>

<h2>Standardsaaz 17%: par défaut</h2>

<p>De Standardsaaz applizéiert sech op <strong>all steierflichteg Operatiounen</strong>, déi net explizitt engem anere Saaz ënnerleien. Konkret:</p>

<ul>
    <li><strong>Dienstleeschtungen</strong>: Berodung, Design, Entwécklung, Marketing, Formatioun, etc.</li>
    <li><strong>Net-Liewensmëttel-Wueren</strong>: IT-Material, Mobilier, Kleeder, etc.</li>
    <li><strong>Verlounung vu Beruffsmaterial</strong></li>
    <li><strong>Hotellerie</strong> (4 Stären a méi)</li>
    <li><strong>Restauranter</strong> (alkoholesch Gedrénks)</li>
</ul>

<p>Et ass <strong>de Standardsaaz</strong>, deen Dir bei Zweifel uwennen, bis Dir d'Situatioun mat Ärem Fiduciaire kläert.</p>

<h2>Zwëschensaaz 14%: Wäin a Brennstoffer</h2>

<p>De Zwëschensaaz betrëfft haaptsächlech:</p>

<ul>
    <li><strong>Wäin</strong> (stëll an Schaumwäin, ausser Champagner)</li>
    <li><strong>Bestëmmt fest Brennstoffer</strong> (Kuel, Brennholz)</li>
    <li><strong>E puer Reklam-Publikatiounen</strong></li>
</ul>

<p>Dëse Saaz ass rar. Wann Dir net am Wäin- oder Hëtzungssecteur sidd, gebraucht Dir e wuel ni.</p>

<h2>Reduzéiert Saaz 8%: Energie a perséinlech Dienstleeschtungen</h2>

<p>De reduzéiert Saaz applizéiert sech a ville Secteuren:</p>

<ul>
    <li><strong>Gas- a Stroumliwwerung</strong></li>
    <li><strong>Coiffeur</strong></li>
    <li><strong>Restauratioun</strong> (ouni alkoholesch Gedrénks)</li>
    <li><strong>Hotellerie</strong> (1 bis 3 Stären)</li>
    <li><strong>Renovéierungsaarbechten</strong> vun ale Wunnengen (ënner Konditiounen)</li>
    <li><strong>Live-Spektakelen</strong> (Theater, Concerten)</li>
    <li><strong>Schousterei, Vëlosreparatur, Klederännerung</strong></li>
</ul>

<h2>Super-reduzéiert Saaz 3%: essentiell Produkter</h2>

<p>De niddregsten Saaz deckt als essentiell ageschwat Produkter:</p>

<ul>
    <li><strong>Grond-Liewensmëttel</strong> (Brout, Mëllech, Fleesch, Geméis, Friichten)</li>
    <li><strong>Bicher, Zeitungen, Periodika</strong> (Pabeier a digital zënter 2020)</li>
    <li><strong>Medikamenter</strong> (op Verschreiwung oder fräi)</li>
    <li><strong>Ëffentlechen Transport</strong> (Bus, Zuch, Tram)</li>
    <li><strong>Agraresch Produktioun</strong> (Som, Dünger)</li>
    <li><strong>Waasser aus ëffentleche Netzer</strong></li>
    <li><strong>Konstwierker</strong> (ënner bestëmmten Konditiounen)</li>
</ul>

<h2>Heefeg Sonderfäll</h2>

<h3>Restauratioun: 8% oder 17%?</h3>

<p>Vu-Plaz servéiert Iessen am Restaurant: <strong>8%</strong>, mä alkoholesch Gedrénks bleiwen bei <strong>17%</strong>. Eng zu engem Iessen servéiert Wäinflasch gëtt getrennt fakturéiert.</p>

<h3>Hotellerie: 8% oder 17%?</h3>

<p>Logement an 1- bis 3-Stären-Hoteler: <strong>8%</strong>. Ab 4 Stären: <strong>17%</strong>. Niewendienschter (Spa, Zëmmerservice) follegen eegene Regelen.</p>

<h3>E-Books: 3% oder 17%?</h3>

<p>Zënter 2020 gëllt fir <strong>digital Bicher</strong> deeselwechte Saaz wéi fir Pabeierbicher, also <strong>3%</strong>. Mä Streaming-Abonnementer (Netflix, Spotify) bleiwen bei 17%.</p>

<h3>Renovéierungsaarbechten: 8% oder 17%?</h3>

<p>Renovéierungen vu Wunnengen <strong>méi al wéi 2 Joer</strong> profitéieren vum reduzéierte Saaz <strong>8%</strong>, ënner Konditioun datt Material a Mainpower zesummen vum Bauunternehmen fakturéiert ginn. Fir Neibau: 17%.</p>

<h2>Wéi d'lëtzebuerger TVA berechnen</h2>

<p>Wann Dir 1.000 EUR Hors-TVA un engem Lëtzebuerger Client zum Standardsaaz 17% fakturéiert:</p>

<ul>
    <li><strong>Hors-TVA</strong>: 1.000,00 EUR</li>
    <li><strong>TVA (17%)</strong>: 170,00 EUR</li>
    <li><strong>TVA-c</strong>: 1.170,00 EUR</li>
</ul>

<p>Ëmgekéiert, wann Dir d'TVA-c kennt an d'Hors-TVA sicht:</p>

<ul>
    <li><strong>Hors-TVA</strong> = TVA-c ÷ (1 + Saaz/100)</li>
    <li><strong>Beispill</strong>: 1.170 EUR TVA-c ÷ 1,17 = 1.000 EUR Hors-TVA</li>
</ul>

<p>Zäit spueren mat eisem <a href="/lb/tools/tva-rechner" class="text-primary-500 hover:underline font-medium">gratis Lëtzebuerger TVA-Rechner</a>, dee all 4 Sätz ënnerstëtzt.</p>

<h2>Falsch Sätz: d'Folgen</h2>

<p>De falsche Saaz unzewennen ass keng Bagatell:</p>

<ul>
    <li><strong>TVA-Ënnerfakturatioun</strong> (Saaz ze nidder): AED-Nofuerderung + Zënsen + Strofen (10% bis 50%)</li>
    <li><strong>TVA-Iwwerfakturatioun</strong> (Saaz ze héich): de Client kann Erstattung verlaangen, an Dir musst mat der AED regulariséieren</li>
    <li><strong>Refus vum Vorsteierofzuch beim Client</strong>: bei offensichtlechem Feeler kann de Client de TVA-Ofzuch refuséieren</li>
</ul>

<h2>Wéi faktur.lu Feeler vermeit</h2>

<p>faktur.lu applizéiert automatesch de richtege Saaz no der Leeschtungsaart:</p>

<ul>
    <li>Saaz-Auswahl no <strong>Leeschtungstyp</strong> bei Produkt-Schafung</li>
    <li>Automatesch Detektioun vu <strong>B2B intra-EU</strong>-Fäll (Autoliquidatioun Art. 21 LIVA)</li>
    <li><strong>Saaz 0</strong> bei Franchise (Art. 56 ter)</li>
    <li>Obligatoresch LIVA-Vermierker op all Rechnung generéiert</li>
</ul>

<h2>FAQ - TVA Lëtzebuerg</h2>

<h3>Ass de Standardsaaz 2026 wierklech 17%?</h3>
<p>Jo, zënter dem 1. Januar 2024. De Saaz war 2023 temporär bei 16% fir d'Kafkraaft-Ënnerstëtzung, dann zréck zum historeschen 17%.</p>

<h3>Ass meng Aktivitéit TVA-befreit?</h3>
<p>E puer Aktivitéiten sinn befreit (Gesondheet, Erzéiung, Versécherung, Wunn-Verlounung), wat awer och bedeit, dass Dir keng <strong>Vorsteier</strong> op Är Beruffskaaf <strong>net erëmkréit</strong>. Iwwerpréift Äre Fall mat Ärem Fiduciaire.</p>

<h3>Wéini de Saaz op enger offener Rechnung wiesselen?</h3>
<p>Ännert de Saaz tëschent Devis a Liwwerung, applizéiert sech de Saaz <strong>um Rechnungsofschluss-Datum</strong> (net dem Devis-Datum).</p>

<h3>A bei grenziwwerschreitenden B2C-Verkeef?</h3>
<p>Zënter 2021 applizéiert sech de <strong>OSS (One Stop Shop)</strong>-Regime: iwwer 10.000 EUR innergemeinschaftlech B2C-Verkeef pro Joer musst Dir de Bestëmmungsland-Saaz uwennen. Soss applizéiert sech lëtzebuerger TVA.</p>

<h3>Wéi e besonnesche Saaz bei enger Kontroll rechtfäerdegen?</h3>
<p>Konservéiert <strong>Belegspréif vun der Leeschtungsaart</strong>: Liwwerschäiner, Kontrakter, technesch Datebliedeler. D'AED kann d'Nomenklatur oder d'Zesummesetzung gesinn wëllen fir den ugewanten Saaz ze validéieren.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Lëtzebuerger TVA an 5 Sekonnen berechnen</h3>
    <p class="text-primary-800 mb-4">Eise gratis TVA-Rechner ënnerstëtzt déi 4 lëtzebuerger Sätz (17%, 14%, 8%, 3%). Ouni Inskriptioun, ouni Kaart.</p>
    <a href="/lb/tools/tva-rechner" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Rechner opmaachen</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Artikel 61 LIVA: firwat Är Rechnungs-Nummeréierung sequentiell muss sinn",
                'slug' => 'artikel-61-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch',
                'translation_key' => 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'cover_image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?w=1200&h=630&fit=crop',
                'meta_title' => 'Artikel 61 LIVA Lëtzebuerg: sequentiell Nummeréierung obligatoresch | faktur.lu',
                'meta_description' => "Artikel 61 LIVA verlaangt fortlafend Nummeréierung vun Äre Rechnungen zu Lëtzebuerg. Lücken, Doubel, Formater: wat ze wëssen an wéi automatiséieren.",
                'excerpt' => "Är Rechnungen zu Lëtzebuerg mussen eng eenzegaarteg, sequentiell a fortlafend Nummer droen. Dat ass Artikel 61 LIVA. Eng Lück oder en Doubel kann eng Nopassung ausléisen. Hei wéi konform bleiwen.",
                'published_at' => '2026-06-29 09:00:00',
                'content' => <<<HTML
<p class="lead">Den <strong>Artikel 61 LIVA</strong> setzt eng einfach awer fundamental Regel: Är lëtzebuerger Rechnungen mussen eng <strong>eenzegaarteg, sequentiell a fortlafend Nummer</strong> droen. Eng Lück oder en Doubel kann eng Steier-Nopassung ausléisen. Hei d'Regel erkläert a wéi se ni méi musst denken.</p>

<h2>Wat seet Artikel 61 LIVA genau?</h2>

<p>Den Artikel 61 vum lëtzebuerger TVA-Gesetz präziséiert, datt all Rechnung, déi vun engem Steierflichtege erstellt gëtt, eng <strong>sequentiell Nummer</strong> droen muss, <strong>chronologesch a fortlafend</strong> pro Geschäftsjoer vergiewen.</p>

<p>Dräi kumulativ Regelen:</p>

<ol>
    <li><strong>Eenzegaartegkeet</strong>: zwou Rechnungen kënnen ni déiselwecht Nummer droen</li>
    <li><strong>Sequentialitéit</strong>: d'Nummeren follegen sech der Rei (1, 2, 3, 4...)</li>
    <li><strong>Fortlafongegkeet</strong>: keng Lück (kee Sprong vun 5 op 8 ouni 6 a 7)</li>
</ol>

<p>Dës Flicht applizéiert sech op <strong>all TVA-Flichteg</strong> zu Lëtzebuerg, inklusiv ënner Franchise (Artikel 56 ter), och wann d'Rechnung de Vermierk "TVA net uwennbar" dréit.</p>

<h2>Firwat dës Regel</h2>

<p>D'Zil ass einfach: <strong>Chiffre-d'Affaires-Verstoppung verhënneren</strong>. Ouni fortlafend Nummeréierung kéint en Entreprise einfach gewësse Rechnungen an der TVA-Deklaratioun "vergiessen". Sequentialitéit erlaabt der AED, op ee Bléck ze pruefen, ob all ausgestellte Rechnungen deklaréiert goufen.</p>

<p>Dofir enthält d'<strong>FAIA</strong> systematesch d'Lëscht vun de Rechnungsnummeren vun der kontrolléierter Period. Eng a FAIA fonnt Lück léist direkt eng Erklärungs-Ufro aus.</p>

<h2>Akzeptéiert Formater</h2>

<p>Artikel 61 schreift kee bestëmmt Format vir. Dir kënnt all Konventioun benotzen, soulaang Sequentialitéit respektéiert ass:</p>

<ul>
    <li><strong>Einfach Nummeréierung</strong>: 1, 2, 3, 4...</li>
    <li><strong>Mat Préfix</strong>: F-001, F-002, F-003...</li>
    <li><strong>Mat Joer</strong>: 2026-001, 2026-002, 2026-003...</li>
    <li><strong>Mat Préfix + Joer</strong>: F-2026-001, F-2026-002...</li>
    <li><strong>Mat Client</strong> (ofgerot): ACME-001, ACME-002 (brécht global Fortlafongegkeet)</li>
</ul>

<p><strong>Tipp</strong>: Eng Nummer mat <strong>Joer + sequentielle Zähler</strong> (z. B. F-2026-001) ass am liesbarsten an erlaabt jäerleche Restart. Et ass déi heefegst Praxis zu Lëtzebuerg.</p>

<h2>Jäerleche Reset: erlaabt oder net?</h2>

<p>Jo, Dir kënnt d'Nummeréierung jäerlech bei <strong>1 jäerlech</strong> nei starten. Et ass souguer gänge Praxis. Wichteg ass, dass <strong>bannent engem Joer</strong> d'Sequenz fortlafend bleift.</p>

<p>Gülteg Beispill:</p>

<ul>
    <li>F-2025-148 (lescht Rechnung 2025)</li>
    <li>F-2026-001 (éischt Rechnung 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>De Wiessel vu 148 op 001 ass erlaabt, well d'Joer wiesselt. Mä bannent 2026 kënnt Dir net vun F-2026-001 op F-2026-003 sprangen ouni F-2026-002 ausgestallt ze hunn.</p>

<h2>Wat bei Feeler?</h2>

<h3>Fall 1 - En Doubel ausgestallt</h3>

<p>D'AED betruecht eng vun den zwou Rechnungen als <strong>fiktiv oder bedréigelech</strong>. Dir musst:</p>

<ol>
    <li>En <strong>Avoir</strong> op enger vun den zwou ausstellen (Buchungs-Annulatioun)</li>
    <li>Eng <strong>nei Rechnung</strong> mat der nächster Nummer ausstellen</li>
    <li>Déi 3 Dokumenter behalen (déi 2 originell Rechnungen + den Avoir)</li>
</ol>

<h3>Fall 2 - Eng Lück an der Sequenz</h3>

<p>Méi schwéiere Fall. Dir musst d'<strong>Lück erklären</strong> kënnen. Dräi akzeptéiert Erklärungen:</p>

<ul>
    <li>D'Rechnung ass ausgestallt ginn an dann <strong>duerch Avoir annuléiert</strong> (béid Dokumenter behalen)</li>
    <li>Technesche Feeler: e Brouillon net ofgeschloss. An dësem Fall besser ofschléissen an archivéieren, och wann d'Leeschtung net stattfonnt huet (mat "Rechnung annuléiert"-Vermierk)</li>
    <li>Dokumentéierte IT-Bug (selten)</li>
</ul>

<p>Ouni glabhafte Erklärung vermutet d'AED Verstoppung a kann de feelende Chiffre d'Affaires <strong>pauschal schätzen</strong>.</p>

<h3>Fall 3 - Eng ofgeschlossen Rechnung läschen</h3>

<p>Rechtlech onméiglech. Eng ofgeschlossen Rechnung muss 10 Joer archivéiert ginn (Art. 16 Code de commerce). Fir z'annuléieren:</p>

<ul>
    <li>En <strong>Avoir</strong> iwwer deeselwechte Montant ausstellen (separater Nummeréierung typesch: AV-2026-001)</li>
    <li>Béid Dokumenter behalen (Rechnung + Avoir)</li>
</ul>

<h2>Klassesch Faller</h2>

<ol>
    <li><strong>Manuell Excel-Nummeréierung</strong>: ganz héicht Risiko op Doubel oder Lücken (vergiessen vun der leschter Rechnung, gebrach Formel, gelongene Copy-Paste)</li>
    <li><strong>Méi parallel Serien</strong> (eng pro Client oder Projet): schwéier bei enger Kontroll ze rechtfäerdegen</li>
    <li><strong>Reset matzen am Joer</strong> ouni Geschäftsjoer-Wiessel: verbueden</li>
    <li><strong>Ofgeschlossen Rechnungen läschen</strong> ouni Ersatz-Avoir: Verstouss géint Art. 16 Code de commerce + Art. 61 LIVA</li>
</ol>

<h2>Wéi automatiséieren ouni drun ze denken</h2>

<p>Eng Rechnungs-Software wéi <a href="/lb" class="text-primary-500 hover:underline font-medium">faktur.lu</a> këmmert sech ëm d'Nummeréierung:</p>

<ul>
    <li><strong>Automatesch sequentiell Nummeréierung</strong>: onméiglech en Doubel ze maachen</li>
    <li><strong>Keng Lück méiglech</strong>: bei all Ofschloss inkrementéiert de Zähler</li>
    <li><strong>Personaliséierbart Format</strong>: Préfix, Joer, Zähler-Längt, alles konfiguréierbar</li>
    <li><strong>Automatesche jäerleche Reset</strong>: Zähler jäerlech op 1 (oder net, no Präferenz)</li>
    <li><strong>Getrennt Sequenzen</strong> fir Rechnungen, Avoiren a Devisen (onofhängeg awer kontinuéierlech Zähler)</li>
    <li><strong>Eenzegaartegkeets-Iwwerpréiwung</strong> bei all Schafung</li>
</ul>

<p>Domat ass Artikel 61 LIVA keng Suerg méi: Är Nummeréierung ass per Konstruktioun konform.</p>

<h2>FAQ - Artikel 61 LIVA</h2>

<h3>An wann ech ënner TVA-Franchise (Artikel 56 ter) fakturéieren?</h3>
<p>Artikel 61 LIVA applizéiert sech <strong>trotzdem</strong>. All ausgestellte Rechnung, och ouni erhuewen TVA, muss eng sequentiell a fortlafend Nummer droen.</p>

<h3>Kann ech eng Nummeréierung pro Client hunn (ACME-001, ACME-002...)?</h3>
<p>Technesch jo - awer staark ofgerot. Bei enger Kontroll verlaangt d'AED <strong>global</strong> Sequentialitéit. Dir musst dann beweisen, dass et iwwer all Client-Serien keng Lück gëtt, wat komplex a riskant ass.</p>

<h3>Sinn d'Devisen betraff?</h3>
<p>Nee, Artikel 61 LIVA betrëfft nëmmen <strong>Rechnungen an Avoiren</strong>. Dir kënnt Är Devisen nummeréieren wéi Dir wëllt (sequentiell Nummeréierung trotzdem recommandéiert fir Äert eegent Tracking).</p>

<h3>Kann ech getrennte Serien fir Rechnungen an Avoiren hunn?</h3>
<p>Jo, souguer recommandéiert. Eng Serie fir Rechnungen (F-2026-001, F-2026-002...) an eng Serie fir Avoiren (AV-2026-001, AV-2026-002...). All eng muss fortlafend, mä si sinn onofhängeg.</p>

<h3>Wat bei Migratioun zu enger neier Software matzen am Joer?</h3>
<p>Dir musst d'<strong>Sequenz weiderféieren</strong> an der neier Software. Wann Dir bei F-2026-148 an der aler waart, muss Är éischt Rechnung an der neier F-2026-149 sinn. faktur.lu erlaabt Startzähler-Konfiguratioun bei der Aschreiwung fir Lücke-Verhënneren.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Eng konform Nummeréierung, ouni ze denken</h3>
    <p class="text-primary-800 mb-4">faktur.lu këmmert sech automatesch ëm Är sequentiell Nummeréierung, Artikel 61 LIVA per Konstruktioun respektéiert. Personaliséierbart Format, konfiguréierbare jäerleche Reset.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis starten</a>
</div>
HTML,
            ],
        ];
    }
}
