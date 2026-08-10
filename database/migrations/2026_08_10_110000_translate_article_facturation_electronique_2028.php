<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Traductions EN / DE / LB / PT de l'article sur le calendrier luxembourgeois
 * de la facturation électronique B2B.
 *
 * Les quatre versions partagent le `translation_key` de l'original français,
 * ce qui les relie entre elles pour le sélecteur de langue et les hreflang.
 *
 * ⚠ Le luxembourgeois demande une relecture native : il s'agit ici de
 * vocabulaire réglementaire, et la traduction faite ici est une base de
 * travail, pas une version définitive.
 *
 * Comme la migration d'origine, celle-ci **insère seulement**. Un slug déjà
 * présent est laissé intact : les articles se retouchent depuis
 * l'administration, et une migration ne doit jamais écraser ce travail.
 */
return new class extends Migration
{
    private const CLE = 'facturation-electronique-obligatoire-luxembourg-2028';

    private const COUVERTURE = 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop';

    /** Même horodatage que l'original : volontairement dans le passé, sinon scopePublished les écarte. */
    private const PUBLIE_LE = '2026-08-10 06:00:00';

    public function up(): void
    {
        // Voir la migration d'origine : ni auteur ni catégorie ne sont codés en
        // dur, sans quoi une base vide (les tests) tombe sur une violation de
        // clé étrangère.
        $auteur = DB::table('users')->min('id');
        $categorie = DB::table('blog_categories')->where('slug', 'reglementation')->value('id');

        if ($auteur === null || $categorie === null) {
            echo '  → auteur ou catégorie « reglementation » introuvable, traductions non créées'.PHP_EOL;

            return;
        }

        $crees = 0;

        foreach ($this->articles() as $locale => $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                echo '  → '.$locale.' déjà présent, laissé intact'.PHP_EOL;

                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $auteur,
                'category_id' => $categorie,
                'locale' => $locale,
                'translation_key' => self::CLE,
                'slug' => $article['slug'],
                'title' => $article['title'],
                'meta_title' => $article['meta_title'],
                'excerpt' => $article['excerpt'],
                'meta_description' => $article['meta_description'],
                'cover_image' => self::COUVERTURE,
                'content' => $article['content'],
                'status' => 'published',
                'published_at' => self::PUBLIE_LE,
                'views_count' => 0,
                'created_at' => self::PUBLIE_LE,
                'updated_at' => self::PUBLIE_LE,
            ]);

            $crees++;
        }

        echo '  → '.$crees.' traduction(s) créée(s) sur 4'.PHP_EOL;
    }

    public function down(): void
    {
        DB::table('blog_posts')
            ->where('translation_key', self::CLE)
            ->whereIn('locale', ['en', 'de', 'lb', 'pt'])
            ->delete();
    }

    /** @return array<string, array<string, string>> */
    private function articles(): array
    {
        return [
            'en' => [
                'slug' => 'mandatory-e-invoicing-luxembourg-2028',
                'title' => 'Mandatory e-invoicing in Luxembourg: the 2028-2029 timeline',
                'meta_title' => 'Mandatory e-invoicing Luxembourg 2028 | faktur.lu',
                'excerpt' => 'A draft law released on 30 July 2026 makes e-invoicing mandatory between Luxembourg businesses: reception from 1 January 2028, issuance during 2028 for the largest and on 1 January 2029 for everyone else. What to remember, and what is still uncertain.',
                'meta_description' => 'Mandatory e-invoicing in Luxembourg: reception from 1 January 2028, issuance during 2028 then 1 January 2029 for all other businesses.',
                'content' => $this->contenuEn(),
            ],
            'de' => [
                'slug' => 'elektronische-rechnungspflicht-luxemburg-2028',
                'title' => 'Elektronische Rechnungspflicht in Luxemburg: der Zeitplan 2028-2029',
                'meta_title' => 'Elektronische Rechnungspflicht Luxemburg 2028 | faktur.lu',
                'excerpt' => 'Ein am 30. Juli 2026 veröffentlichter Gesetzentwurf macht die elektronische Rechnungsstellung zwischen luxemburgischen Unternehmen verpflichtend: Empfang ab dem 1. Januar 2028, Ausstellung im Laufe des Jahres 2028 für die größten und am 1. Januar 2029 für alle anderen.',
                'meta_description' => 'Elektronische Rechnungspflicht in Luxemburg: Empfang ab 1. Januar 2028, Ausstellung 2028 und ab 1. Januar 2029 für alle übrigen Unternehmen.',
                'content' => $this->contenuDe(),
            ],
            'lb' => [
                'slug' => 'elektronesch-rechnungsflicht-letzebuerg-2028',
                'title' => 'Elektronesch Rechnungsflicht zu Lëtzebuerg: den Zäitplang 2028-2029',
                'meta_title' => 'Elektronesch Rechnungsflicht Lëtzebuerg 2028 | faktur.lu',
                'excerpt' => 'E Gesetzprojet vum 30. Juli 2026 mécht d\'elektronesch Rechnungsstellung tëscht Lëtzebuerger Entreprisen obligatoresch: Empfang ab dem 1. Januar 2028, Ausstellung am Laf vun 2028 fir déi gréissten an den 1. Januar 2029 fir all déi aner.',
                'meta_description' => 'Elektronesch Rechnungsflicht zu Lëtzebuerg: Empfang ab dem 1. Januar 2028, Ausstellung 2028 an ab dem 1. Januar 2029 fir all déi aner Entreprisen.',
                'content' => $this->contenuLb(),
            ],
            'pt' => [
                'slug' => 'faturacao-eletronica-obrigatoria-luxemburgo-2028',
                'title' => 'Faturação eletrónica obrigatória no Luxemburgo: o calendário 2028-2029',
                'meta_title' => 'Faturação eletrónica obrigatória Luxemburgo 2028 | faktur.lu',
                'excerpt' => 'Um projeto de lei divulgado a 30 de julho de 2026 torna a faturação eletrónica obrigatória entre empresas luxemburguesas: receção a partir de 1 de janeiro de 2028, emissão durante 2028 para as maiores e a 1 de janeiro de 2029 para todas as outras.',
                'meta_description' => 'Faturação eletrónica obrigatória no Luxemburgo: receção a partir de 1 de janeiro de 2028, emissão em 2028 e a 1 de janeiro de 2029 para as restantes.',
                'content' => $this->contenuPt(),
            ],
        ];
    }

    private function contenuEn(): string
    {
        return <<<'HTML'
<p class="lead">Luxembourg is preparing to make <strong>e-invoicing mandatory between businesses</strong>. A draft law released on 30 July 2026 requires every business to be able to <strong>receive</strong> an electronic invoice from 1 January 2028. The obligation to <strong>issue</strong> follows: first for businesses above certain size thresholds, during 2028, then for everyone else on 1 January 2029.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In short</p>
    <ul>
        <li><strong>1 January 2028</strong>: every business established in Luxembourg must be able to <strong>receive</strong> a structured electronic invoice.</li>
        <li><strong>During 2028</strong>: obligation to <strong>issue</strong> for businesses exceeding at least two of the three thresholds (EUR 7.5m balance sheet, EUR 15m turnover, 50 employees), measured on the 2026 accounts.</li>
        <li><strong>1 January 2029</strong>: obligation to <strong>issue</strong> for everyone else, which covers the vast majority of freelancers and small businesses.</li>
        <li><strong>Peppol</strong> network, European <strong>EN 16931</strong> standard.</li>
        <li>Sales to consumers (B2C) are not covered.</li>
        <li>This is a <strong>draft law</strong>: the dates are not final, and the thresholds will be set by Grand-Ducal regulation.</li>
    </ul>
</div>

<h2>What the draft law provides</h2>

<p>The government approved a draft law in July 2026 extending the e-invoicing obligation to transactions between businesses established in Luxembourg. The text was made public on <strong>30 July 2026</strong> and circulates under the number 8815.</p>

<p>It does not build a framework from scratch: it <strong>amends two existing laws</strong>, the <a href="http://journalofficiel.lu/eli/etat/leg/loi/2019/05/16/a345/jo" target="_blank" rel="noopener">law of 16 May 2019 on electronic invoicing in public procurement</a>, and the VAT law of 12 February 1979. In other words, Luxembourg is extending to the private sector a system already proven in the public one.</p>

<p>A draft Grand-Ducal regulation accompanies it. Its purpose is to prevent every business from deploying its own technical solution: it sets a common network, so that sender and recipient do not have to agree case by case.</p>

<h2>The timeline in detail</h2>

<p>The first step applies to <strong>everyone at the same time</strong>. The following ones depend on company size.</p>

<table class="w-full my-6">
    <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Date</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Obligation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Who</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1 January 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Receive and process a compliant electronic invoice</td>
            <td class="border border-gray-300 px-4 py-2">Every business established in Luxembourg</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>During 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Issue invoices in electronic format</td>
            <td class="border border-gray-300 px-4 py-2">Businesses exceeding at least two of the three thresholds below</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1 January 2029</strong></td>
            <td class="border border-gray-300 px-4 py-2">Issue invoices in electronic format</td>
            <td class="border border-gray-300 px-4 py-2">All other businesses</td>
        </tr>
    </tbody>
</table>

<h3>Which size thresholds?</h3>

<p>A business joins the first issuance wave if it exceeds <strong>at least two</strong> of the following three criteria, measured at the close of the <strong>2026</strong> financial year:</p>

<ul>
    <li><strong>EUR 7.5 million</strong> balance sheet total;</li>
    <li><strong>EUR 15 million</strong> net turnover;</li>
    <li><strong>50 employees</strong> on average, full-time equivalent.</li>
</ul>

<p>These are the size criteria already used in Luxembourg accounting law. For the vast majority of freelancers and small businesses, none of the three is reached: the issuance deadline is therefore <strong>1 January 2029</strong>.</p>

<p>The final thresholds and part of the technical arrangements are to be set by <strong>Grand-Ducal regulation</strong>. They may still change.</p>

<p>This gap between receiving and issuing matters. A small business has nothing to issue before 2029, but it will have to <strong>take in electronic invoices from January 2028</strong>, including from suppliers who are themselves only obliged later. In practice many will issue sooner: nothing prevents you from moving early.</p>

<h2>Who is covered, and who is not</h2>

<p>The scheme targets transactions where <strong>both sender and recipient are established in Luxembourg</strong>. It is a domestic mandate.</p>

<p>Outside its scope:</p>

<ul>
    <li><strong>sales to consumers</strong> (B2C), explicitly excluded;</li>
    <li><strong>cross-border transactions</strong>, which follow a separate, European timeline (see below);</li>
    <li>transactions whose recipient is not a taxable person.</li>
</ul>

<p>If you mainly invoice consumers, this text concerns you little on the issuing side. But as soon as you buy from a Luxembourg supplier, the reception obligation applies.</p>

<h2>How it will work technically</h2>

<p>Luxembourg is reusing <strong>Peppol</strong>, the network already in place for public-sector invoicing. Invoices will travel through the so-called "four-corner" model: you hand your invoice to your access point, which delivers it to your customer's access point, which passes it on to them.</p>

<p>You therefore do not connect to each of your customers. You connect once to the network, and the network handles the rest. It is the same principle as email: your mail provider talks to your correspondent's without you configuring anything.</p>

<p>The format must comply with the European <strong>EN 16931</strong> standard, in an authorised syntax. In practice, a structured invoice a machine can read, not a PDF sent by email. An ordinary PDF, even signed, does not meet that condition.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A PDF is not an electronic invoice</p>
    <p>This is the most common confusion. In regulatory terms, an electronic invoice is a <strong>structured</strong> file that the recipient's software can read and post without re-keying. A PDF is a picture of an invoice: readable by a human, opaque to a machine. Hybrid formats such as <a href="/en/blog/factur-x-zugferd-european-electronic-invoicing-explained">Factur-X</a> answer both needs by embedding the structured file inside the PDF.</p>
</div>

<h2>Do not confuse this with ViDA</h2>

<p>Two timelines coexist, and they are constantly mixed up.</p>

<ul>
    <li><strong>The Luxembourg timeline</strong>, the subject of this article: <strong>domestic</strong> transactions, 2028 and 2029.</li>
    <li><strong>The European ViDA timeline</strong> ("VAT in the Digital Age"): <strong>intra-EU</strong> transactions, mandatory e-invoicing and digital reporting on <strong>1 July 2030</strong>, with harmonisation of national systems targeted around 2035.</li>
</ul>

<p>The practical consequence is simple: <strong>the national deadline comes first</strong>. A Luxembourg business invoicing only Luxembourg customers is concerned in 2028, not 2030. Many articles cite only ViDA and suggest nothing is urgent before the end of the decade. That has been inaccurate since July 2026.</p>

<h2>What is already mandatory today</h2>

<p>E-invoicing is nothing new in Luxembourg for anyone working with the public sector. Since 2022-2023, every invoice addressed to the State, a municipality or a public body must go through Peppol, <strong>with no minimum amount</strong>. We cover that in our <a href="/en/blog/peppol-b2g-luxembourg-complete-guide-2026">complete Peppol B2G guide</a>.</p>

<p>It is precisely because this infrastructure exists and works that the government chose to extend it rather than build another.</p>

<h2>How to get ready</h2>

<p>There is more than a year before the first deadline. Three useful things, in order of priority.</p>

<h3>1. Check that your software can produce structured invoices</h3>

<p>The question to ask your vendor is not "do you do e-invoicing", to which everyone says yes. It is: <strong>"do you generate Peppol BIS 3.0 or EN 16931, and by when will you be able to transmit over the network?"</strong> The difference between producing the file and being able to deliver it is real, and that is the part that takes time.</p>

<h3>2. Plan for reception, not just issuance</h3>

<p>This is the most overlooked point, even though it is the first obligation in time. Receiving means being identifiable on the network and having a channel to collect incoming invoices. If you are not there, your suppliers cannot invoice you.</p>

<h3>3. Clean up your customer data</h3>

<p>The network works on identifiers. In Luxembourg, the identifier is based on the VAT number. A customer file with missing, mistyped or outdated VAT numbers will become a direct obstacle. Better to clean it now, calmly.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ This is not law yet</p>
    <p>The text described here is a <strong>draft law</strong>, released but not voted. The exact scope, the sequence of dates and the technical arrangements may change during parliamentary debate. The deadlines above reflect the government's intention, not settled law.</p>
    <p class="mt-2">Published analyses <strong>already diverge</strong> on the exact phasing of the issuance obligation: most place the first wave on 1 July 2028, others mention 1 January 2028 for the largest businesses. We have used the more cautious wording. The reception date, by contrast, is agreed by all.</p>
    <p class="mt-2">Before spending money or changing tools on this basis, <strong>talk to your accountant or adviser</strong>. This article informs you; it does not replace professional advice on your situation.</p>
</div>

<h2>Frequently asked questions</h2>

<h3>Must I issue electronic invoices from 2028?</h3>

<p>Only if you exceed at least two of the three thresholds (EUR 7.5m balance sheet, EUR 15m turnover, 50 employees) on your 2026 accounts. Otherwise your issuance deadline is <strong>1 January 2029</strong>. However, <strong>the obligation to receive applies to everyone from 1 January 2028</strong>.</p>

<h3>What about my invoices to consumers?</h3>

<p>They are not covered. The scheme targets transactions between taxable persons.</p>

<h3>Will a PDF sent by email be enough?</h3>

<p>No. A structured file compliant with EN 16931 will be required, transmitted over the network. A PDF alone does not meet the definition.</p>

<h3>Do I need to change invoicing software?</h3>

<p>Not necessarily, but your software will have to produce the structured format and transmit it. Ask your vendor now: it is the best way to find out whether you will need to switch, and you have time to do it calmly. Our selection criteria are set out in our <a href="/en/blog/choose-invoicing-software-luxembourg-comparison">invoicing software comparison</a>.</p>

<h3>What happens if I am not ready?</h3>

<p>Penalties will fall under the VAT regime amended by the text. As the draft has not been voted, their exact nature is not settled. The immediate risk is mostly commercial: a customer who cannot send you their invoice, or cannot process yours, will look for a supplier who can.</p>

<h2>Further reading</h2>

<ul>
    <li><a href="/en/blog/peppol-b2g-luxembourg-complete-guide-2026">Peppol B2G in Luxembourg: complete guide</a></li>
    <li><a href="/en/blog/factur-x-zugferd-european-electronic-invoicing-explained">Factur-X and ZUGFeRD: European e-invoicing explained</a></li>
    <li><a href="https://www.cc.lu/en/all-information/news/detail/facturation-electronique-obligations-et-echeances-au-luxembourg-et-en-europe" target="_blank" rel="noopener">Chamber of Commerce: obligations and deadlines in Luxembourg and Europe</a></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <p class="font-semibold">faktur.lu already generates the format</p>
    <p>Your invoices can be produced in Peppol BIS 3.0 (UBL 2.1) and Factur-X, compliant with the EN 16931 standard. Automatic transmission via a certified access point is being connected, ahead of the deadlines described here.</p>
    <p class="mt-3"><a href="/register" class="font-semibold">Try faktur.lu for free</a></p>
</div>
HTML;
    }

    private function contenuDe(): string
    {
        return <<<'HTML'
<p class="lead">Luxemburg schickt sich an, die <strong>elektronische Rechnungsstellung zwischen Unternehmen verpflichtend</strong> zu machen. Ein am 30. Juli 2026 veröffentlichter Gesetzentwurf verlangt, dass jedes Unternehmen ab dem 1. Januar 2028 eine elektronische Rechnung <strong>empfangen</strong> kann. Die Pflicht zur <strong>Ausstellung</strong> folgt: zunächst für Unternehmen oberhalb bestimmter Größenschwellen im Laufe des Jahres 2028, dann für alle übrigen am 1. Januar 2029.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Kurz gefasst</p>
    <ul>
        <li><strong>1. Januar 2028</strong>: Jedes in Luxemburg ansässige Unternehmen muss eine strukturierte elektronische Rechnung <strong>empfangen</strong> können.</li>
        <li><strong>Im Laufe des Jahres 2028</strong>: Pflicht zur <strong>Ausstellung</strong> für Unternehmen, die mindestens zwei der drei Schwellen überschreiten (7,5 Mio. EUR Bilanzsumme, 15 Mio. EUR Umsatz, 50 Beschäftigte), gemessen am Abschluss 2026.</li>
        <li><strong>1. Januar 2029</strong>: Pflicht zur <strong>Ausstellung</strong> für alle übrigen, also für die große Mehrheit der Selbstständigen und Kleinunternehmen.</li>
        <li><strong>Peppol</strong>-Netz, europäische Norm <strong>EN 16931</strong>.</li>
        <li>Verkäufe an Privatpersonen (B2C) sind nicht betroffen.</li>
        <li>Es handelt sich um einen <strong>Gesetzentwurf</strong>: Die Daten sind nicht endgültig, und die Schwellen werden durch großherzogliche Verordnung festgelegt.</li>
    </ul>
</div>

<h2>Was der Gesetzentwurf vorsieht</h2>

<p>Die Regierung hat im Juli 2026 einen Gesetzentwurf gebilligt, der die Pflicht zur elektronischen Rechnungsstellung auf Geschäfte zwischen in Luxemburg ansässigen Unternehmen ausweitet. Der Text wurde am <strong>30. Juli 2026</strong> veröffentlicht und trägt die Nummer 8815.</p>

<p>Er schafft keinen Rahmen aus dem Nichts, sondern <strong>ändert zwei bestehende Gesetze</strong>: das <a href="http://journalofficiel.lu/eli/etat/leg/loi/2019/05/16/a345/jo" target="_blank" rel="noopener">Gesetz vom 16. Mai 2019 über die elektronische Rechnungsstellung im öffentlichen Auftragswesen</a> und das Mehrwertsteuergesetz vom 12. Februar 1979. Luxemburg überträgt also auf die Privatwirtschaft, was sich im öffentlichen Sektor bereits bewährt hat.</p>

<p>Begleitet wird er von einem Entwurf einer großherzoglichen Verordnung. Sie soll verhindern, dass jedes Unternehmen eine eigene technische Lösung aufbaut: Sie legt ein gemeinsames Netz fest, damit sich Absender und Empfänger nicht im Einzelfall abstimmen müssen.</p>

<h2>Der Zeitplan im Einzelnen</h2>

<p>Die erste Stufe betrifft <strong>alle gleichzeitig</strong>. Die folgenden hängen von der Unternehmensgröße ab.</p>

<table class="w-full my-6">
    <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Datum</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Pflicht</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wer</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1. Januar 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Konforme elektronische Rechnungen empfangen und verarbeiten</td>
            <td class="border border-gray-300 px-4 py-2">Alle in Luxemburg ansässigen Unternehmen</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>Im Laufe des Jahres 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Rechnungen im elektronischen Format ausstellen</td>
            <td class="border border-gray-300 px-4 py-2">Unternehmen, die mindestens zwei der drei Schwellen überschreiten</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1. Januar 2029</strong></td>
            <td class="border border-gray-300 px-4 py-2">Rechnungen im elektronischen Format ausstellen</td>
            <td class="border border-gray-300 px-4 py-2">Alle übrigen Unternehmen</td>
        </tr>
    </tbody>
</table>

<h3>Welche Größenschwellen?</h3>

<p>Ein Unternehmen fällt in die erste Ausstellungswelle, wenn es <strong>mindestens zwei</strong> der folgenden drei Kriterien überschreitet, gemessen zum Abschluss des Geschäftsjahres <strong>2026</strong>:</p>

<ul>
    <li><strong>7,5 Millionen Euro</strong> Bilanzsumme;</li>
    <li><strong>15 Millionen Euro</strong> Nettoumsatz;</li>
    <li><strong>50 Beschäftigte</strong> im Durchschnitt, in Vollzeitäquivalenten.</li>
</ul>

<p>Es sind die Größenkriterien, die im luxemburgischen Bilanzrecht bereits verwendet werden. Für die große Mehrheit der Selbstständigen und Kleinunternehmen wird keines der drei erreicht: Die Ausstellungsfrist ist damit der <strong>1. Januar 2029</strong>.</p>

<p>Die endgültigen Schwellen und ein Teil der technischen Modalitäten sollen durch <strong>großherzogliche Verordnung</strong> festgelegt werden. Sie können sich also noch ändern.</p>

<p>Der Abstand zwischen Empfang und Ausstellung ist kein Detail. Ein kleines Unternehmen muss vor 2029 nichts ausstellen, aber <strong>ab Januar 2028 elektronische Rechnungen entgegennehmen</strong>, auch von Lieferanten, die selbst erst später verpflichtet sind. In der Praxis werden viele früher ausstellen: Vorziehen ist jederzeit erlaubt.</p>

<h2>Wer betroffen ist und wer nicht</h2>

<p>Das Verfahren betrifft Geschäfte, bei denen <strong>Absender und Empfänger beide in Luxemburg ansässig</strong> sind. Es handelt sich um ein inländisches Mandat.</p>

<p>Nicht erfasst sind:</p>

<ul>
    <li><strong>Verkäufe an Privatpersonen</strong> (B2C), ausdrücklich ausgenommen;</li>
    <li><strong>grenzüberschreitende Geschäfte</strong>, für die ein eigener, europäischer Zeitplan gilt (siehe unten);</li>
    <li>Geschäfte, deren Empfänger nicht mehrwertsteuerpflichtig ist.</li>
</ul>

<p>Wer überwiegend an Privatpersonen fakturiert, ist auf der Ausstellungsseite kaum betroffen. Sobald Sie jedoch bei einem luxemburgischen Lieferanten einkaufen, greift die Empfangspflicht.</p>

<h2>Wie es technisch funktionieren wird</h2>

<p>Luxemburg nutzt <strong>Peppol</strong> weiter, das Netz, das für die Rechnungsstellung an den öffentlichen Sektor bereits besteht. Die Rechnungen laufen über das sogenannte Vier-Ecken-Modell: Sie übergeben Ihre Rechnung Ihrem Zugangspunkt, dieser übergibt sie dem Zugangspunkt Ihres Kunden, der sie dort zustellt.</p>

<p>Sie müssen sich also nicht mit jedem Ihrer Kunden verbinden. Sie schließen sich einmal an das Netz an, den Rest übernimmt das Netz. Es ist dasselbe Prinzip wie bei der E-Mail: Ihr Anbieter spricht mit dem Ihres Gegenübers, ohne dass Sie etwas einrichten müssten.</p>

<p>Das Format muss der europäischen Norm <strong>EN 16931</strong> in einer zugelassenen Syntax entsprechen. Konkret: eine strukturierte, maschinenlesbare Rechnung, kein per E-Mail versandtes PDF. Ein gewöhnliches PDF erfüllt diese Bedingung auch signiert nicht.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Ein PDF ist keine elektronische Rechnung</p>
    <p>Das ist die häufigste Verwechslung. Im Sinne der Regelung ist eine elektronische Rechnung eine <strong>strukturierte</strong> Datei, die die Software des Empfängers ohne erneute Erfassung lesen und verbuchen kann. Ein PDF ist ein Abbild einer Rechnung: für Menschen lesbar, für Maschinen undurchsichtig. Hybridformate wie <a href="/de/blog/factur-x-zugferd-europaeische-elektronische-rechnungsstellung">Factur-X</a> bedienen beides, indem sie die strukturierte Datei in das PDF einbetten.</p>
</div>

<h2>Nicht mit ViDA verwechseln</h2>

<p>Zwei Zeitpläne bestehen nebeneinander und werden ständig vermischt.</p>

<ul>
    <li><strong>Der luxemburgische Zeitplan</strong>, Gegenstand dieses Artikels: <strong>inländische</strong> Geschäfte, 2028 und 2029.</li>
    <li><strong>Der europäische ViDA-Zeitplan</strong> („Mehrwertsteuer im digitalen Zeitalter"): <strong>innergemeinschaftliche</strong> Geschäfte, verpflichtende elektronische Rechnungsstellung und digitale Meldung zum <strong>1. Juli 2030</strong>, mit einer angestrebten Harmonisierung der nationalen Systeme bis etwa 2035.</li>
</ul>

<p>Die praktische Folge ist einfach: <strong>Die nationale Frist kommt zuerst</strong>. Ein luxemburgisches Unternehmen, das nur luxemburgische Kunden fakturiert, ist 2028 betroffen, nicht 2030. Viele Artikel nennen nur ViDA und erwecken den Eindruck, vor Ende des Jahrzehnts eile nichts. Das ist seit Juli 2026 unzutreffend.</p>

<h2>Was heute bereits Pflicht ist</h2>

<p>Für alle, die mit dem öffentlichen Sektor arbeiten, ist die elektronische Rechnungsstellung in Luxemburg nichts Neues. Seit 2022-2023 muss jede Rechnung an den Staat, eine Gemeinde oder eine öffentliche Einrichtung über Peppol laufen, <strong>ohne Mindestbetrag</strong>. Wir behandeln das in unserem <a href="/de/blog/peppol-b2g-luxemburg-vollstaendiger-leitfaden-2026">vollständigen Peppol-B2G-Leitfaden</a>.</p>

<p>Gerade weil diese Infrastruktur besteht und funktioniert, hat die Regierung sie ausgeweitet, statt eine neue zu errichten.</p>

<h2>Wie Sie sich vorbereiten</h2>

<p>Bis zur ersten Frist bleibt mehr als ein Jahr. Drei sinnvolle Schritte, nach Priorität geordnet.</p>

<h3>1. Prüfen Sie, ob Ihre Software strukturierte Rechnungen erzeugt</h3>

<p>Die Frage an Ihren Anbieter lautet nicht „machen Sie elektronische Rechnungsstellung", was alle bejahen. Sie lautet: <strong>„Erzeugen Sie Peppol BIS 3.0 oder EN 16931, und ab wann können Sie über das Netz übertragen?"</strong> Der Unterschied zwischen dem Erzeugen der Datei und dem Zustellen ist real, und er kostet Zeit.</p>

<h3>2. Denken Sie an den Empfang, nicht nur an die Ausstellung</h3>

<p>Dieser Punkt wird am häufigsten übersehen, obwohl er zeitlich die erste Pflicht ist. Empfangen setzt voraus, im Netz identifizierbar zu sein und einen Kanal für eingehende Rechnungen zu haben. Wer nicht dort ist, kann von seinen Lieferanten nicht fakturiert werden.</p>

<h3>3. Bringen Sie Ihre Kundendaten in Ordnung</h3>

<p>Das Netz arbeitet mit Kennungen. In Luxemburg beruht die Kennung auf der Mehrwertsteuernummer. Eine Kundendatei mit fehlenden, falsch erfassten oder veralteten Nummern wird unmittelbar zum Hindernis. Besser jetzt in Ruhe bereinigen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Noch ist es kein Gesetz</p>
    <p>Der hier beschriebene Text ist ein <strong>Gesetzentwurf</strong>, eingebracht, aber nicht verabschiedet. Genauer Anwendungsbereich, Abfolge der Daten und technische Modalitäten können sich in der parlamentarischen Beratung ändern. Die obigen Fristen geben die Absicht der Regierung wieder, kein geltendes Recht.</p>
    <p class="mt-2">Die veröffentlichten Analysen <strong>weichen bereits voneinander ab</strong>, was die genaue Staffelung der Ausstellungspflicht betrifft: Die meisten setzen die erste Welle auf den 1. Juli 2028, andere nennen den 1. Januar 2028 für die größten Unternehmen. Wir haben die vorsichtigere Formulierung gewählt. Über das Empfangsdatum besteht dagegen Einigkeit.</p>
    <p class="mt-2">Bevor Sie auf dieser Grundlage Geld ausgeben oder das Werkzeug wechseln, <strong>sprechen Sie mit Ihrem Treuhänder oder Berater</strong>. Dieser Artikel informiert Sie, er ersetzt keine fachliche Beratung zu Ihrer Lage.</p>
</div>

<h2>Häufige Fragen</h2>

<h3>Muss ich ab 2028 elektronisch fakturieren?</h3>

<p>Nur wenn Sie im Abschluss 2026 mindestens zwei der drei Schwellen überschreiten (7,5 Mio. EUR Bilanzsumme, 15 Mio. EUR Umsatz, 50 Beschäftigte). Andernfalls ist Ihre Ausstellungsfrist der <strong>1. Januar 2029</strong>. Die <strong>Empfangspflicht gilt dagegen für alle ab dem 1. Januar 2028</strong>.</p>

<h3>Und meine Rechnungen an Privatpersonen?</h3>

<p>Sie sind nicht betroffen. Das Verfahren betrifft Geschäfte zwischen Steuerpflichtigen.</p>

<h3>Genügt ein per E-Mail versandtes PDF?</h3>

<p>Nein. Erforderlich ist eine strukturierte, EN-16931-konforme Datei, übertragen über das Netz. Ein PDF allein erfüllt die Definition nicht.</p>

<h3>Muss ich meine Rechnungssoftware wechseln?</h3>

<p>Nicht zwingend, aber Ihre Software muss das strukturierte Format erzeugen und übertragen können. Fragen Sie Ihren Anbieter jetzt: So erfahren Sie am besten, ob ein Wechsel nötig wird, und Sie haben Zeit, ihn in Ruhe zu vollziehen. Unsere Auswahlkriterien stehen in unserem <a href="/de/blog/rechnungssoftware-luxemburg-richtige-waehlen-vergleich">Vergleich der Rechnungssoftware</a>.</p>

<h3>Was passiert, wenn ich nicht bereit bin?</h3>

<p>Sanktionen richten sich nach dem durch den Text geänderten Mehrwertsteuerrecht. Da der Entwurf nicht verabschiedet ist, steht ihre genaue Ausgestaltung nicht fest. Das unmittelbare Risiko ist vor allem geschäftlich: Ein Kunde, der Ihnen seine Rechnung nicht senden oder Ihre nicht verarbeiten kann, sucht sich einen Lieferanten, der es kann.</p>

<h2>Weiterlesen</h2>

<ul>
    <li><a href="/de/blog/peppol-b2g-luxemburg-vollstaendiger-leitfaden-2026">Peppol B2G in Luxemburg: vollständiger Leitfaden</a></li>
    <li><a href="/de/blog/factur-x-zugferd-europaeische-elektronische-rechnungsstellung">Factur-X und ZUGFeRD: die europäische elektronische Rechnungsstellung erklärt</a></li>
    <li><a href="https://www.cc.lu/en/all-information/news/detail/facturation-electronique-obligations-et-echeances-au-luxembourg-et-en-europe" target="_blank" rel="noopener">Handelskammer: Pflichten und Fristen in Luxemburg und Europa</a></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <p class="font-semibold">faktur.lu erzeugt das Format bereits</p>
    <p>Ihre Rechnungen können im Format Peppol BIS 3.0 (UBL 2.1) und Factur-X erzeugt werden, konform zur Norm EN 16931. Die automatische Übertragung über einen zertifizierten Zugangspunkt wird angebunden, im Hinblick auf die hier beschriebenen Fristen.</p>
    <p class="mt-3"><a href="/register" class="font-semibold">faktur.lu kostenlos testen</a></p>
</div>
HTML;
    }

    private function contenuLb(): string
    {
        return <<<'HTML'
<p class="lead">Lëtzebuerg mécht sech drun, d'<strong>elektronesch Rechnungsstellung tëscht Entreprisen obligatoresch</strong> ze maachen. E Gesetzprojet, deen den 30. Juli 2026 verëffentlecht gouf, verlaangt, datt all Entreprise ab dem 1. Januar 2028 eng elektronesch Rechnung <strong>empfänke</strong> kann. D'Flicht fir <strong>auszestellen</strong> kënnt duerno: fir d'éischt fir Entreprisen iwwer gewësse Gréisstschwellen am Laf vun 2028, dann fir all déi aner den 1. Januar 2029.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Kuerz zesummegefaasst</p>
    <ul>
        <li><strong>1. Januar 2028</strong>: All Entreprise, déi zu Lëtzebuerg néiergelooss ass, muss eng strukturéiert elektronesch Rechnung <strong>empfänke</strong> kënnen.</li>
        <li><strong>Am Laf vun 2028</strong>: Flicht fir <strong>auszestellen</strong> fir Entreprisen, déi op mannst zwou vun den dräi Schwellen iwwerschreiden (7,5 Mio. EUR Bilanzsumm, 15 Mio. EUR Ëmsaz, 50 Beschäftegter), gemooss um Ofschloss 2026.</li>
        <li><strong>1. Januar 2029</strong>: Flicht fir <strong>auszestellen</strong> fir all déi aner, also fir déi grouss Majoritéit vun de Selbststännegen a Klengentreprisen.</li>
        <li><strong>Peppol</strong>-Netz, europäesch Norm <strong>EN 16931</strong>.</li>
        <li>Verkeef u Privatpersounen (B2C) sinn net betraff.</li>
        <li>Et handelt sech ëm e <strong>Gesetzprojet</strong>: D'Datumer sinn net definitiv, an d'Schwelle ginn duerch groussherzoglech Reglement festgeluecht.</li>
    </ul>
</div>

<h2>Wat de Gesetzprojet virgesäit</h2>

<p>D'Regierung huet am Juli 2026 e Gesetzprojet ugeholl, deen d'Flicht zur elektronescher Rechnungsstellung op Geschäfter tëscht Entreprisen ausweidert, déi zu Lëtzebuerg néiergelooss sinn. Den Text gouf den <strong>30. Juli 2026</strong> ëffentlech gemaach an dréit d'Nummer 8815.</p>

<p>Hie baut kee Kader vun Null op, mee <strong>ännert zwee bestoend Gesetzer</strong>: d'<a href="http://journalofficiel.lu/eli/etat/leg/loi/2019/05/16/a345/jo" target="_blank" rel="noopener">Gesetz vum 16. Mee 2019 iwwer d'elektronesch Rechnungsstellung am ëffentleche Marchéswiesen</a> an d'TVA-Gesetz vum 12. Februar 1979. Lëtzebuerg iwwerdréit also op de private Secteur, wat sech am ëffentleche scho bewäert huet.</p>

<p>E Projet vun engem groussherzogleche Reglement begleet en. Säin Zweck ass ze verhënneren, datt all Entreprise hir eege technesch Léisung opbaut: Et leet e gemeinsamt Netz fest, sou datt Ofsender an Empfänger sech net vu Fall zu Fall ofstëmme mussen.</p>

<h2>Den Zäitplang am Detail</h2>

<p>Déi éischt Etapp betrëfft <strong>jiddereen zur selwechter Zäit</strong>. Déi weider hänken vun der Gréisst vun der Entreprise of.</p>

<table class="w-full my-6">
    <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Datum</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Flicht</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wien</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1. Januar 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Konform elektronesch Rechnungen empfänken a veraarbechten</td>
            <td class="border border-gray-300 px-4 py-2">All Entreprisen, déi zu Lëtzebuerg néiergelooss sinn</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>Am Laf vun 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Rechnungen am elektronesche Format ausstellen</td>
            <td class="border border-gray-300 px-4 py-2">Entreprisen, déi op mannst zwou vun den dräi Schwellen iwwerschreiden</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1. Januar 2029</strong></td>
            <td class="border border-gray-300 px-4 py-2">Rechnungen am elektronesche Format ausstellen</td>
            <td class="border border-gray-300 px-4 py-2">All déi aner Entreprisen</td>
        </tr>
    </tbody>
</table>

<h3>Wéi eng Gréisstschwellen?</h3>

<p>Eng Entreprise kënnt an déi éischt Ausstellungswell, wa se op mannst <strong>zwou</strong> vun de folgenden dräi Krittäre iwwerschreit, gemooss um Ofschloss vum Geschäftsjoer <strong>2026</strong>:</p>

<ul>
    <li><strong>7,5 Milliounen Euro</strong> Bilanzsumm;</li>
    <li><strong>15 Milliounen Euro</strong> Nettoëmsaz;</li>
    <li><strong>50 Beschäftegter</strong> am Duerchschnëtt, a Vollzäitequivalenter.</li>
</ul>

<p>Et sinn d'Gréisstkrittären, déi am Lëtzebuerger Bilanzrecht scho benotzt ginn. Fir déi grouss Majoritéit vun de Selbststännegen a Klengentreprisen gëtt keng vun den dräi erreecht: D'Ausstellungsfrist ass domat den <strong>1. Januar 2029</strong>.</p>

<p>Déi definitiv Schwellen an en Deel vun den techneschen Modalitéite solle per <strong>groussherzoglech Reglement</strong> festgeluecht ginn. Si kënne sech also nach änneren.</p>

<p>Den Ofstand tëscht Empfang an Ausstellung ass keen Detail. Eng kleng Entreprise muss viru 2029 näischt ausstellen, mee si muss <strong>ab Januar 2028 elektronesch Rechnungen unhuelen</strong>, och vu Liwweranten, déi selwer eréischt méi spéit verflicht sinn. An der Praxis wäerte vill méi fréi ausstellen: Virzéien ass ëmmer erlaabt.</p>

<h2>Wien betraff ass a wien net</h2>

<p>D'Verfahre betrëfft Geschäfter, bei deenen <strong>Ofsender an Empfänger béid zu Lëtzebuerg néiergelooss</strong> sinn. Et handelt sech ëm en inlännescht Mandat.</p>

<p>Net erfaasst sinn:</p>

<ul>
    <li><strong>Verkeef u Privatpersounen</strong> (B2C), ausdrécklech ausgeholl;</li>
    <li><strong>grenziwwerschreidend Geschäfter</strong>, fir déi en eegenen, europäeschen Zäitplang gëllt (kuckt hei ënnen);</li>
    <li>Geschäfter, deenen hiren Empfänger net TVA-flichteg ass.</li>
</ul>

<p>Wien haaptsächlech u Privatpersoune fakturéiert, ass op der Ausstellungssäit kaum betraff. Mee soubal Dir bei engem Lëtzebuerger Liwwerant akaaft, gräift d'Empfangsflicht.</p>

<h2>Wéi et technesch fonctionéiere wäert</h2>

<p>Lëtzebuerg notzt <strong>Peppol</strong> weider, d'Netz, dat fir d'Rechnungsstellung un den ëffentleche Secteur scho besteet. D'Rechnunge lafen iwwer dat sougenannt Véier-Ecken-Modell: Dir gitt Är Rechnung Ärem Access Point, deen se dem Access Point vun Ärem Client weiderginn, deen se do zoustellt.</p>

<p>Dir musst Iech also net mat jidderengem vun Äre Clienten verbannen. Dir schléisst Iech eemol un d'Netz un, de Rescht mécht d'Netz. Et ass datselwecht Prinzip wéi bei der E-Mail: Äre Provider schwätzt mat deem vun Ärem Korrespondent, ouni datt Dir eppes ariichte musst.</p>

<p>D'Format muss der europäescher Norm <strong>EN 16931</strong> an enger zougelooss Syntax entspriechen. Konkret: eng strukturéiert, maschinnliesbar Rechnung, kee PDF per E-Mail. E gewéinlecht PDF erfëllt dës Bedingung och ënnerschriwwen net.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">E PDF ass keng elektronesch Rechnung</p>
    <p>Dat ass déi heefegst Verwiesslung. Am Sënn vun der Reglementatioun ass eng elektronesch Rechnung eng <strong>strukturéiert</strong> Datei, déi d'Software vum Empfänger ouni nei Erfaassung liese a verbuche kann. E PDF ass e Bild vun enger Rechnung: fir Mënsche liesbar, fir Maschinnen ondurchsiichteg. Hybridformater wéi <a href="/lb/blog/factur-x-zugferd-europaesch-elektronesch-rechnungsstellung">Factur-X</a> bedéngen déi zwee, andeem se déi strukturéiert Datei an d'PDF aboden.</p>
</div>

<h2>Net mat ViDA verwiesselen</h2>

<p>Zwee Zäitplang bestinn niewenteneen a ginn dauernd vermëscht.</p>

<ul>
    <li><strong>De Lëtzebuerger Zäitplang</strong>, Sujet vun dësem Artikel: <strong>inlännesch</strong> Geschäfter, 2028 an 2029.</li>
    <li><strong>Den europäesche ViDA-Zäitplang</strong> („TVA am digitalen Zäitalter"): <strong>innergemeinschaftlech</strong> Geschäfter, obligatoresch elektronesch Rechnungsstellung an digital Meldung den <strong>1. Juli 2030</strong>, mat enger ugestriewter Harmoniséierung vun den nationale Systemer bis ronn 2035.</li>
</ul>

<p>D'praktesch Konsequenz ass einfach: <strong>Déi national Frist kënnt fir d'éischt</strong>. Eng Lëtzebuerger Entreprise, déi nëmme Lëtzebuerger Clienten fakturéiert, ass 2028 betraff, net 2030. Vill Artikelen nenne nëmmen ViDA a maachen de Androck, virum Enn vum Joerzéngt géif näischt presséieren. Dat ass zënter Juli 2026 net méi richteg.</p>

<h2>Wat haut scho Flicht ass</h2>

<p>Fir jiddereen, dee mam ëffentleche Secteur schafft, ass d'elektronesch Rechnungsstellung zu Lëtzebuerg näischt Neies. Zënter 2022-2023 muss all Rechnung un de Staat, eng Gemeng oder en ëffentlechen Etablissement iwwer Peppol lafen, <strong>ouni Mindestbetrag</strong>. Mir behandelen dat an eisem <a href="/lb/blog/peppol-b2g-letzebuerg-komplette-guide-2026">komplette Peppol-B2G-Guide</a>.</p>

<p>Grad well dës Infrastruktur besteet a fonctionéiert, huet d'Regierung se ausgeweidert, amplaz eng nei opzebauen.</p>

<h2>Wéi Dir Iech virbereet</h2>

<p>Bis zur éischter Frist bleift méi wéi e Joer. Dräi sënnvoll Schrëtt, no Prioritéit geuerdent.</p>

<h3>1. Kuckt no, ob Är Software strukturéiert Rechnungen erstellt</h3>

<p>D'Fro un Äre Fournisseur ass net „maacht Dir elektronesch Rechnungsstellung", wat jiddereen bejot. Si ass: <strong>„Erstellt Dir Peppol BIS 3.0 oder EN 16931, an ab wéini kënnt Dir iwwer d'Netz iwwerdroen?"</strong> Den Ënnerscheed tëscht der Datei erstellen a se zoustellen ass real, an dat ass den Deel, dee Zäit kascht.</p>

<h3>2. Denkt un den Empfang, net nëmmen un d'Ausstellung</h3>

<p>Dëse Punkt gëtt am heefegsten iwwersinn, obwuel en zäitlech déi éischt Flicht ass. Empfänke setzt viraus, am Netz identifizéierbar ze sinn an e Kanal fir erakommend Rechnungen ze hunn. Wien net do ass, kann vu senge Liwweranten net fakturéiert ginn.</p>

<h3>3. Brengt Är Clientsdaten an d'Rei</h3>

<p>D'Netz schafft mat Identifianten. Zu Lëtzebuerg baséiert den Identifiant op der TVA-Nummer. Eng Clientsdatei mat feelenden, falsch erfaassten oder verhalene Nummere gëtt direkt zum Hindernis. Besser elo a Rou botzen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Et ass nach kee Gesetz</p>
    <p>Den hei beschriwwenen Text ass e <strong>Gesetzprojet</strong>, deposéiert, mee net gestëmmt. Deen exakte Beräich, d'Reiefolleg vun den Datumer an d'technesch Modalitéite kënne sech an der parlamentarescher Berodung änneren. Déi uewe genannte Fristen ginn d'Absicht vun der Regierung erëm, kee geltend Recht.</p>
    <p class="mt-2">Déi verëffentlechten Analysen <strong>ënnerscheede sech scho</strong>, wat déi genee Staffelung vun der Ausstellungsflicht ugeet: Déi meescht setzen déi éischt Well op den 1. Juli 2028, anerer nennen den 1. Januar 2028 fir déi gréissten Entreprisen. Mir hunn déi virsiichteg Formuléierung gewielt. Iwwer den Empfangsdatum besteet dogéint Eenegkeet.</p>
    <p class="mt-2">Ier Dir op dëser Basis Suen ausgitt oder d'Tool wiesselt, <strong>schwätzt mat Ärer Fiduciaire oder Ärem Beroder</strong>. Dësen Artikel informéiert Iech, hien ersetzt keng fachlech Berodung zu Ärer Situatioun.</p>
</div>

<h2>Heefeg Froen</h2>

<h3>Muss ech ab 2028 elektronesch fakturéieren?</h3>

<p>Nëmme wann Dir am Ofschloss 2026 op mannst zwou vun den dräi Schwellen iwwerschreit (7,5 Mio. EUR Bilanzsumm, 15 Mio. EUR Ëmsaz, 50 Beschäftegter). Soss ass Är Ausstellungsfrist den <strong>1. Januar 2029</strong>. D'<strong>Empfangsflicht gëllt dogéint fir jiddereen ab dem 1. Januar 2028</strong>.</p>

<h3>A meng Rechnungen u Privatpersounen?</h3>

<p>Si sinn net betraff. D'Verfahre betrëfft Geschäfter tëscht Steierflichtegen.</p>

<h3>Duergeet e PDF per E-Mail?</h3>

<p>Nee. Néideg ass eng strukturéiert, EN-16931-konform Datei, iwwer d'Netz iwwerdroen. E PDF eleng erfëllt d'Definitioun net.</p>

<h3>Muss ech meng Rechnungssoftware wiesselen?</h3>

<p>Net onbedéngt, mee Är Software muss dat strukturéiert Format erstelle an iwwerdroe kënnen. Frot Äre Fournisseur elo: Sou erfaart Dir am beschten, ob e Wiessel néideg gëtt, an Dir hutt Zäit, en a Rou ze maachen. Eis Auswielkrittäre stinn an eisem <a href="/lb/blog/rechnungssoftware-letzebuerg-richteg-wielen-verglach">Verglach vun de Rechnungssoftwaren</a>.</p>

<h3>Wat geschitt, wann ech net prett sinn?</h3>

<p>Sanktioune riichte sech no dem TVA-Recht, dat vum Text geännert gëtt. Well de Projet net gestëmmt ass, steet hir genee Ausgestaltung net fest. De direkte Risiko ass virun allem kommerziell: E Client, deen Iech seng Rechnung net schécke kann oder Är net veraarbechte kann, sicht sech e Liwwerant, deen et kann.</p>

<h2>Fir méi wäit ze liesen</h2>

<ul>
    <li><a href="/lb/blog/peppol-b2g-letzebuerg-komplette-guide-2026">Peppol B2G zu Lëtzebuerg: komplette Guide</a></li>
    <li><a href="/lb/blog/factur-x-zugferd-europaesch-elektronesch-rechnungsstellung">Factur-X a ZUGFeRD: déi europäesch elektronesch Rechnungsstellung erkläert</a></li>
    <li><a href="https://www.cc.lu/en/all-information/news/detail/facturation-electronique-obligations-et-echeances-au-luxembourg-et-en-europe" target="_blank" rel="noopener">Handelskummer: Flichten a Fristen zu Lëtzebuerg an an Europa</a></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <p class="font-semibold">faktur.lu generéiert d'Format schonn</p>
    <p>Är Rechnunge kënnen am Format Peppol BIS 3.0 (UBL 2.1) a Factur-X erstallt ginn, konform mat der Norm EN 16931. Déi automatesch Iwwerdroung iwwer en zertifizéierten Access Point gëtt ugebonnen, am Hibléck op déi hei beschriwwe Fristen.</p>
    <p class="mt-3"><a href="/register" class="font-semibold">faktur.lu gratis testen</a></p>
</div>
HTML;
    }

    private function contenuPt(): string
    {
        return <<<'HTML'
<p class="lead">O Luxemburgo prepara-se para tornar a <strong>faturação eletrónica obrigatória entre empresas</strong>. Um projeto de lei divulgado a 30 de julho de 2026 exige que qualquer empresa consiga <strong>receber</strong> uma fatura eletrónica a partir de 1 de janeiro de 2028. A obrigação de <strong>emitir</strong> vem a seguir: primeiro para as empresas acima de certos limiares de dimensão, durante 2028, depois para todas as outras a 1 de janeiro de 2029.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Em resumo</p>
    <ul>
        <li><strong>1 de janeiro de 2028</strong>: todas as empresas estabelecidas no Luxemburgo devem conseguir <strong>receber</strong> uma fatura eletrónica estruturada.</li>
        <li><strong>Durante 2028</strong>: obrigação de <strong>emitir</strong> para as empresas que ultrapassem pelo menos dois dos três limiares (7,5 M€ de balanço, 15 M€ de volume de negócios, 50 trabalhadores), apurados nas contas de 2026.</li>
        <li><strong>1 de janeiro de 2029</strong>: obrigação de <strong>emitir</strong> para todas as restantes, o que abrange a grande maioria dos independentes e das pequenas empresas.</li>
        <li>Rede <strong>Peppol</strong>, norma europeia <strong>EN 16931</strong>.</li>
        <li>As vendas a particulares (B2C) não estão abrangidas.</li>
        <li>Trata-se de um <strong>projeto de lei</strong>: as datas não são definitivas e os limiares serão fixados por regulamento grão-ducal.</li>
    </ul>
</div>

<h2>O que prevê o projeto de lei</h2>

<p>O governo aprovou em julho de 2026 um projeto de lei que alarga a obrigação de faturação eletrónica às operações entre empresas estabelecidas no Luxemburgo. O texto foi tornado público a <strong>30 de julho de 2026</strong> e circula com o número 8815.</p>

<p>Não cria um quadro a partir do zero: <strong>altera duas leis existentes</strong>, a <a href="http://journalofficiel.lu/eli/etat/leg/loi/2019/05/16/a345/jo" target="_blank" rel="noopener">lei de 16 de maio de 2019 relativa à faturação eletrónica na contratação pública</a> e a lei do IVA de 12 de fevereiro de 1979. Por outras palavras, o Luxemburgo alarga ao setor privado um dispositivo já rodado no setor público.</p>

<p>Um projeto de regulamento grão-ducal acompanha-o. O seu objetivo é evitar que cada empresa desenvolva a sua própria solução técnica: fixa uma rede comum, para que emitente e destinatário não tenham de se entender caso a caso.</p>

<h2>O calendário em detalhe</h2>

<p>O primeiro passo aplica-se a <strong>todos ao mesmo tempo</strong>. Os seguintes dependem da dimensão da empresa.</p>

<table class="w-full my-6">
    <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Data</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Obrigação</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Quem</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1 de janeiro de 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Receber e processar uma fatura eletrónica conforme</td>
            <td class="border border-gray-300 px-4 py-2">Todas as empresas estabelecidas no Luxemburgo</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>Durante 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Emitir as faturas em formato eletrónico</td>
            <td class="border border-gray-300 px-4 py-2">Empresas que ultrapassem pelo menos dois dos três limiares abaixo</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1 de janeiro de 2029</strong></td>
            <td class="border border-gray-300 px-4 py-2">Emitir as faturas em formato eletrónico</td>
            <td class="border border-gray-300 px-4 py-2">Todas as restantes empresas</td>
        </tr>
    </tbody>
</table>

<h3>Que limiares de dimensão?</h3>

<p>Uma empresa entra na primeira vaga de emissão se ultrapassar <strong>pelo menos dois</strong> dos três critérios seguintes, apurados no fecho do exercício de <strong>2026</strong>:</p>

<ul>
    <li><strong>7,5 milhões de euros</strong> de total do balanço;</li>
    <li><strong>15 milhões de euros</strong> de volume de negócios líquido;</li>
    <li><strong>50 trabalhadores</strong> em média, em equivalente a tempo inteiro.</li>
</ul>

<p>São os critérios de dimensão já utilizados no direito contabilístico luxemburguês. Para a grande maioria dos independentes e das pequenas empresas, nenhum dos três é atingido: o prazo de emissão é, portanto, <strong>1 de janeiro de 2029</strong>.</p>

<p>Os limiares definitivos e parte das modalidades técnicas devem ser fixados por <strong>regulamento grão-ducal</strong>. Podem, por isso, ainda mudar.</p>

<p>Esta diferença entre receber e emitir não é um pormenor. Uma pequena empresa nada tem de emitir antes de 2029, mas terá de <strong>aceitar faturas eletrónicas já em janeiro de 2028</strong>, incluindo de fornecedores que só ficam obrigados mais tarde. Na prática, muitas emitirão antes: nada impede antecipar.</p>

<h2>Quem está abrangido e quem não está</h2>

<p>O dispositivo visa as operações em que <strong>emitente e destinatário estão ambos estabelecidos no Luxemburgo</strong>. Trata-se de um mandato doméstico.</p>

<p>Ficam de fora:</p>

<ul>
    <li>as <strong>vendas a particulares</strong> (B2C), explicitamente excluídas;</li>
    <li>as <strong>operações transfronteiriças</strong>, sujeitas a um calendário próprio, europeu (ver abaixo);</li>
    <li>as operações cujo destinatário não é sujeito passivo de IVA.</li>
</ul>

<p>Se fatura sobretudo a particulares, este texto pouco lhe diz respeito do lado da emissão. Mas assim que compra a um fornecedor luxemburguês, a obrigação de receção aplica-se.</p>

<h2>Como vai funcionar tecnicamente</h2>

<p>O Luxemburgo reutiliza a <strong>Peppol</strong>, a rede já em funcionamento para a faturação ao setor público. As faturas circularão segundo o modelo dito «de quatro cantos»: entrega a sua fatura ao seu ponto de acesso, que a entrega ao ponto de acesso do seu cliente, que lha faz chegar.</p>

<p>Não tem, portanto, de se ligar a cada um dos seus clientes. Liga-se uma vez à rede, e a rede trata do resto. É o mesmo princípio do correio eletrónico: o seu fornecedor de email fala com o do seu correspondente sem que tenha de configurar seja o que for.</p>

<p>O formato terá de respeitar a norma europeia <strong>EN 16931</strong>, numa sintaxe autorizada. Em concreto, uma fatura estruturada legível por uma máquina, e não um PDF enviado por email. Um PDF comum, mesmo assinado, não cumpre esta condição.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Um PDF não é uma fatura eletrónica</p>
    <p>É a confusão mais frequente. Do ponto de vista regulamentar, uma fatura eletrónica é um ficheiro <strong>estruturado</strong> que o software do destinatário consegue ler e contabilizar sem nova introdução de dados. Um PDF é uma imagem de fatura: legível por um humano, opaca para uma máquina. Os formatos híbridos como o <a href="/pt/blog/factur-x-zugferd-a-faturacao-eletronica-europeia-explicada">Factur-X</a> respondem às duas necessidades, colocando o ficheiro estruturado dentro do PDF.</p>
</div>

<h2>Não confundir com o ViDA</h2>

<p>Coexistem dois calendários, e são constantemente misturados.</p>

<ul>
    <li><strong>O calendário luxemburguês</strong>, tema deste artigo: operações <strong>domésticas</strong>, 2028 e 2029.</li>
    <li><strong>O calendário europeu ViDA</strong> («IVA na era digital»): operações <strong>intracomunitárias</strong>, faturação eletrónica e comunicação digital obrigatórias a <strong>1 de julho de 2030</strong>, com uma harmonização dos sistemas nacionais prevista para cerca de 2035.</li>
</ul>

<p>A consequência prática é simples: <strong>o prazo nacional chega primeiro</strong>. Uma empresa luxemburguesa que fature apenas clientes luxemburgueses é abrangida em 2028, não em 2030. Muitos artigos citam apenas o ViDA e dão a entender que nada urge antes do fim da década. Isso é inexato desde julho de 2026.</p>

<h2>O que já é obrigatório hoje</h2>

<p>A faturação eletrónica nada tem de novo no Luxemburgo para quem trabalha com o setor público. Desde 2022-2023, qualquer fatura dirigida ao Estado, a um município ou a um organismo público tem de passar pela Peppol, <strong>sem qualquer limiar de valor</strong>. Detalhamo-lo no nosso <a href="/pt/blog/peppol-b2g-no-luxemburgo-guia-completo-2026">guia completo Peppol B2G</a>.</p>

<p>É precisamente por esta infraestrutura existir e funcionar que o governo optou por alargá-la em vez de construir outra.</p>

<h2>Como se preparar</h2>

<p>Falta mais de um ano para o primeiro prazo. Três coisas úteis, por ordem de prioridade.</p>

<h3>1. Verifique se o seu software produz ficheiros estruturados</h3>

<p>A pergunta a fazer ao seu fornecedor não é «fazem faturação eletrónica?», a que todos respondem que sim. É: <strong>«geram Peppol BIS 3.0 ou EN 16931, e a partir de quando conseguem transmitir pela rede?»</strong> A diferença entre produzir o ficheiro e conseguir entregá-lo é real, e é essa que consome tempo.</p>

<h3>2. Antecipe a receção, não apenas a emissão</h3>

<p>É o ponto mais esquecido, apesar de ser a primeira obrigação no tempo. Receber pressupõe ser identificável na rede e dispor de um canal para recolher as faturas recebidas. Se não estiver lá, os seus fornecedores não conseguem faturar-lhe.</p>

<h3>3. Atualize os dados dos seus clientes</h3>

<p>A rede funciona por identificadores. No Luxemburgo, o identificador assenta no número de IVA. Um ficheiro de clientes com números de IVA em falta, mal introduzidos ou desatualizados torna-se um obstáculo direto. Mais vale limpá-lo agora, com calma.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Ainda não é lei</p>
    <p>O texto aqui descrito é um <strong>projeto de lei</strong>, apresentado mas não votado. O âmbito exato, a sequência das datas e as modalidades técnicas podem evoluir durante o debate parlamentar. Os prazos acima traduzem a intenção do governo, não um direito adquirido.</p>
    <p class="mt-2">As análises publicadas <strong>já divergem</strong> quanto ao faseamento exato da obrigação de emissão: a maioria situa a primeira vaga a 1 de julho de 2028, outras apontam 1 de janeiro de 2028 para as maiores empresas. Optámos pela formulação mais prudente. Quanto à data de receção, há consenso.</p>
    <p class="mt-2">Antes de fazer despesa ou mudar de ferramenta com esta base, <strong>fale com o seu contabilista ou consultor</strong>. Este artigo informa-o, não substitui um parecer profissional sobre a sua situação.</p>
</div>

<h2>Perguntas frequentes</h2>

<h3>Sou obrigado a emitir faturas eletrónicas já em 2028?</h3>

<p>Apenas se ultrapassar pelo menos dois dos três limiares (7,5 M€ de balanço, 15 M€ de volume de negócios, 50 trabalhadores) nas contas de 2026. Caso contrário, o seu prazo de emissão é <strong>1 de janeiro de 2029</strong>. Já a <strong>obrigação de receber aplica-se a todos a partir de 1 de janeiro de 2028</strong>.</p>

<h3>E as minhas faturas a particulares?</h3>

<p>Não estão abrangidas. O dispositivo visa as operações entre sujeitos passivos de IVA.</p>

<h3>Um PDF enviado por email será suficiente?</h3>

<p>Não. Será necessário um ficheiro estruturado conforme à norma EN 16931, transmitido pela rede. Um PDF por si só não cumpre a definição.</p>

<h3>Tenho de mudar de software de faturação?</h3>

<p>Não necessariamente, mas o seu software terá de produzir o formato estruturado e transmiti-lo. Pergunte já ao seu fornecedor: é a melhor forma de saber se terá de mudar, e tem tempo para o fazer com calma. Os nossos critérios de escolha estão no nosso <a href="/pt/blog/como-escolher-o-seu-software-de-faturacao-no-luxemburgo">comparativo de software de faturação</a>.</p>

<h3>O que acontece se não estiver preparado?</h3>

<p>As sanções decorrerão do regime do IVA alterado pelo texto. Como o projeto não foi votado, a sua natureza exata não está fixada. O risco imediato é sobretudo comercial: um cliente que não lhe consiga enviar a fatura, ou que não consiga processar a sua, procurará um fornecedor que o consiga.</p>

<h2>Para saber mais</h2>

<ul>
    <li><a href="/pt/blog/peppol-b2g-no-luxemburgo-guia-completo-2026">Peppol B2G no Luxemburgo: guia completo</a></li>
    <li><a href="/pt/blog/factur-x-zugferd-a-faturacao-eletronica-europeia-explicada">Factur-X e ZUGFeRD: a faturação eletrónica europeia explicada</a></li>
    <li><a href="https://www.cc.lu/en/all-information/news/detail/facturation-electronique-obligations-et-echeances-au-luxembourg-et-en-europe" target="_blank" rel="noopener">Câmara de Comércio: obrigações e prazos no Luxemburgo e na Europa</a></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <p class="font-semibold">O faktur.lu já gera o formato</p>
    <p>As suas faturas podem ser produzidas em formato Peppol BIS 3.0 (UBL 2.1) e Factur-X, conformes à norma EN 16931. A transmissão automática através de um ponto de acesso certificado está em fase de ligação, tendo em vista os prazos aqui descritos.</p>
    <p class="mt-3"><a href="/register" class="font-semibold">Experimentar o faktur.lu gratuitamente</a></p>
</div>
HTML;
    }
};
