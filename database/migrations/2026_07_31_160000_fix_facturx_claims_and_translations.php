<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « factur-x-zugferd-facturation-electronique-europeenne » —
 * correction des claims produit et reconstruction des traductions.
 *
 * TROISIÈME OCCURRENCE DU MÊME MOTIF. Après le PDF/A (annoncé
 * « automatique » alors qu'il s'agit d'une action explicite) et Peppol
 * (transmission annoncée disponible alors que le go-live est au
 * backlog), l'article Factur-X décrivait à son tour une fonction réelle
 * comme automatique et systématique :
 *   - « Le PDF généré contient automatiquement les données XML Factur-X
 *      intégrées »
 *   - « Aucune action supplémentaire de votre part : c'est 100 %
 *      automatique »
 *   - CTA : « faktur.lu intègre le format Factur-X / ZUGFeRD dans TOUTES
 *      vos factures Pro »
 *
 * ÉTAT RÉEL, vérifié dans le code :
 *   - `InvoicePdfService` ne référence pas Factur-X : le PDF ordinaire
 *     ne contient aucun XML intégré ;
 *   - la génération passe par deux routes GET explicites,
 *     invoices/{invoice}/facturx et /facturx-xml, protégées par
 *     `plan.feature:facturx` ;
 *   - `FacturXService::facturx()` refuse les brouillons (abort 400) :
 *     la facture doit être finalisée.
 * C'est donc un téléchargement à la demande, pas un enrichissement
 * silencieux de chaque facture.
 *
 * CE QUI ÉTAIT EXACT et reste inchangé : le profil par défaut est bien
 * EN 16931 (`FacturXService::PROFILE_EN16931`), et la fonctionnalité est
 * bien réservée au plan Pro (`facturx` dans les features du plan à
 * 15 EUR/mois). La liste des cinq profils correspond exactement aux
 * constantes du service.
 *
 * Précision ajoutée, utile et vérifiable : la génération n'est possible
 * que sur une facture finalisée — un brouillon renvoie une erreur.
 *
 * FAITS VÉRIFIÉS et confirmés exacts, laissés tels quels : Factur-X et
 * ZUGFeRD sont le même standard sous deux noms (FNFE-MPE en France, FeRD
 * en Allemagne), tous deux fondés sur EN 16931 ; format hybride PDF/A-3
 * avec XML intégré ; calendrier français (réception pour toutes les
 * entreprises et émission ETI/grandes entreprises au 1er septembre 2026,
 * TPE/PME au 1er septembre 2027) ; calendrier allemand (réception
 * obligatoire depuis le 1er janvier 2025, émission progressive d'ici
 * 2028) ; ViDA au 1er juillet 2030 ; Peppol déjà obligatoire pour le
 * secteur public luxembourgeois.
 *
 * TRADUCTIONS RECONSTRUITES : allemand 3 768 caractères, luxembourgeois
 * 3 434 et anglais 4 477 contre 7 380 en français, avec 2 H3 seulement
 * (aucun en luxembourgeois) et 2 liens contre 11.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    private const KEY = 'factur-x-zugferd-facturation-electronique-europeenne';

    public function up(): void
    {
        // 1. Français : remplacer la section produit et le CTA.
        $fr = DB::table('blog_posts')->where('translation_key', self::KEY)->where('locale', 'fr')->first(['id', 'content']);

        if ($fr) {
            $c = preg_replace(
                '~<h2>Générer des factures Factur-X avec faktur\.lu</h2>.*?(?=<div class="bg-amber-50)~s',
                $this->productSection('fr'),
                $fr->content,
                1
            );

            $c = str_replace(
                'faktur.lu intègre le format Factur-X / ZUGFeRD dans toutes vos factures Pro. Anticipez les futures obligations européennes.',
                'Le plan Pro génère le fichier Factur-X (PDF/A-3 avec XML intégré) de vos factures finalisées, au profil EN 16931. Anticipez les obligations européennes à venir.',
                $c
            );

            DB::table('blog_posts')->where('id', $fr->id)->update(['content' => $c, 'updated_at' => now()]);
        }

        // 2. Reconstruire les quatre traductions.
        foreach (['de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure annonçait un
        // enrichissement automatique de toutes les factures.
    }

    private function productSection(string $l): string
    {
        return match ($l) {
            'fr' => <<<'HTML'
<h2>Générer une facture Factur-X avec faktur.lu</h2>

<p>La génération Factur-X est incluse dans le <strong>plan Pro</strong>. Elle fonctionne à la demande, sur une facture <strong>finalisée</strong> :</p>

<ol>
    <li>Créez et <strong>finalisez</strong> votre facture — un brouillon ne peut pas être converti</li>
    <li>Téléchargez le fichier <strong>Factur-X</strong> : un PDF/A-3 contenant le XML intégré</li>
    <li>Ou récupérez le <strong>XML seul</strong>, si votre client ne veut que les données structurées</li>
    <li>Transmettez-le à votre client, qui l'importe dans sa comptabilité sans ressaisie</li>
</ol>

<p>Le profil utilisé est <strong>EN 16931</strong> par défaut, le plus largement accepté.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Une précision qui évite une mauvaise surprise</p>
    <p>Le PDF standard que vous envoyez habituellement <strong>ne contient pas</strong> le XML Factur-X : c'est un fichier distinct, à générer explicitement. Tant que vos clients ne le réclament pas, rien ne vous oblige à changer vos habitudes — mais le jour où un client français vous le demande, c'est disponible en un clic.</p>
</div>

HTML,
            default => '',
        };
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead"><strong>Factur-X</strong> (in Deutschland <strong>ZUGFeRD</strong> genannt) ist der von Frankreich und Deutschland übernommene Standard für die elektronische Rechnungsstellung, dessen Einführung derzeit in der gesamten Europäischen Union voranschreitet. Hier ist alles Wesentliche für 2026.</p>

<h2>Was ist Factur-X?</h2>

<p>Factur-X ist ein <strong>hybrides</strong> Rechnungsformat, das zwei Elemente in einer einzigen PDF/A-3-Datei vereint:</p>

<ul>
    <li>Den <strong>sichtbaren Teil</strong>: das lesbare PDF, das Sie am Bildschirm sehen und ausdrucken können</li>
    <li>Den <strong>strukturierten Teil</strong>: eine in das PDF eingebettete XML-Datei mit den Rechnungsdaten in normiertem Format</li>
</ul>

<p>Dieses Doppelformat erlaubt es zugleich <strong>Menschen</strong>, die Rechnung zu lesen, und <strong>Software</strong>, sie automatisch in die Buchhaltung zu übernehmen.</p>

<h2>Factur-X gegen ZUGFeRD: wo ist der Unterschied?</h2>

<p>Technisch gibt es keinen. Es handelt sich um <strong>denselben Standard</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> ist der in Frankreich und im französischsprachigen Raum verwendete Name (getragen von <a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> ist der in Deutschland verwendete Name (getragen vom <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Beide beruhen auf der europäischen Norm <strong>EN 16931</strong></li>
</ul>

<h2>Die Factur-X-Profile</h2>

<p>Factur-X kennt mehrere Detailstufen:</p>

<ul>
    <li><strong>Minimum</strong>: Basisangaben (Absender, Empfänger, Gesamtbetrag)</li>
    <li><strong>Basic WL</strong>: ohne Positionszeilen, aber mit aufgeschlüsselter MwSt.</li>
    <li><strong>Basic</strong>: mit detaillierten Rechnungspositionen</li>
    <li><strong>EN 16931</strong> (früher „Comfort"): vollständiges, normkonformes Profil – <strong>empfohlen</strong></li>
    <li><strong>Extended</strong>: Zusatzdaten für besondere Anforderungen</li>
</ul>

<p>faktur.lu erzeugt Factur-X-Rechnungen im Profil <strong>EN 16931</strong>, dem am breitesten akzeptierten.</p>

<h2>Warum Factur-X wichtig ist</h2>

<h3>Für Ihr Unternehmen</h3>
<ul>
    <li><strong>Automatisierte Verarbeitung</strong>: Ihre Kunden übernehmen die Rechnung ohne Neuerfassung</li>
    <li><strong>Weniger Fehler</strong>: keine manuelle Abschrift von Beträgen und Nummern</li>
    <li><strong>Schnellere Zahlung</strong>: eine automatisch verarbeitete Rechnung durchläuft den Freigabeprozess zügiger</li>
    <li><strong>Ein einziges Dokument</strong>: kein getrenntes Versenden von PDF und Datendatei</li>
</ul>

<h3>Für die Europäische Union</h3>
<ul>
    <li>Verringerung der MwSt.-Lücke durch strukturierte, prüfbare Daten</li>
    <li>Harmonisierung der Formate zwischen den Mitgliedstaaten auf Basis von EN 16931</li>
    <li>Grundlage für die im ViDA-Paket vorgesehene digitale Meldung</li>
</ul>

<h2>Ist es in Luxemburg Pflicht?</h2>

<p>2026 ist Factur-X für inländisches B2B in Luxemburg noch <strong>nicht</strong> verpflichtend. Gleichwohl:</p>

<ul>
    <li><strong>Frankreich</strong> macht die elektronische Rechnungsstellung verpflichtend: Empfang für alle Unternehmen zum 1. September 2026, Ausstellung für größere Unternehmen zum selben Datum, für Kleinstunternehmen und KMU zum 1. September 2027</li>
    <li><strong>Deutschland</strong> verlangt den Empfang elektronischer B2B-Rechnungen bereits seit dem 1. Januar 2025; die Ausstellungspflicht greift gestaffelt bis 2028</li>
    <li>Die <strong>ViDA</strong>-Richtlinie der EU sieht die Pflicht für innergemeinschaftliches B2B zum 1. Juli 2030 vor</li>
    <li>Der luxemburgische <strong>öffentliche Sektor</strong> nutzt bereits Peppol, verpflichtend seit 2022-2023</li>
</ul>

<p>Vorausschauend handeln lohnt sich: Wer Factur-X jetzt einführt, ist für die kommenden Pflichten gerüstet und erleichtert seinen Kunden die Arbeit — besonders den französischen ab September 2026.</p>

<h2>Eine Factur-X-Rechnung mit faktur.lu erzeugen</h2>

<p>Die Factur-X-Erzeugung ist im <strong>Pro-Tarif</strong> enthalten. Sie funktioniert auf Abruf, an einer <strong>finalisierten</strong> Rechnung:</p>

<ol>
    <li>Erstellen und <strong>finalisieren</strong> Sie Ihre Rechnung — ein Entwurf lässt sich nicht konvertieren</li>
    <li>Laden Sie die <strong>Factur-X</strong>-Datei herunter: ein PDF/A-3 mit eingebettetem XML</li>
    <li>Oder holen Sie sich das <strong>XML allein</strong>, wenn Ihr Kunde nur die strukturierten Daten möchte</li>
    <li>Übermitteln Sie es Ihrem Kunden, der es ohne Neuerfassung in seine Buchhaltung übernimmt</li>
</ol>

<p>Verwendet wird standardmäßig das Profil <strong>EN 16931</strong>, das am breitesten akzeptierte.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Ein Hinweis, der Überraschungen erspart</p>
    <p>Das Standard-PDF, das Sie üblicherweise versenden, enthält <strong>kein</strong> Factur-X-XML: das ist eine eigene Datei, die ausdrücklich zu erzeugen ist. Solange Ihre Kunden sie nicht verlangen, müssen Sie nichts ändern — an dem Tag aber, an dem ein französischer Kunde danach fragt, ist sie mit einem Klick da.</p>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Die Zeitpläne von ViDA, Frankreich und Deutschland entwickeln sich. Diese Seite wird regelmäßig aktualisiert — die für Ihren Fall geltenden Daten entnehmen Sie den offiziellen Quellen unten.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäische Kommission – VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/facturation-electronique-entreprises" target="_blank" rel="noopener">Frankreich – economie.gouv.fr – Elektronische Rechnungsstellung</a></li>
    <li><a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE – Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD – ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Luxemburg →</a></li><li><a href="/de/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Rechnungssoftware wählen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Factur-X-Rechnungen erzeugen</h3>
    <p class="text-primary-800 mb-4">Der Pro-Tarif erzeugt die Factur-X-Datei (PDF/A-3 mit eingebettetem XML) Ihrer finalisierten Rechnungen im Profil EN 16931. Seien Sie den kommenden europäischen Pflichten voraus.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead"><strong>Factur-X</strong> (known as <strong>ZUGFeRD</strong> in Germany) is the e-invoicing standard adopted by France and Germany, and being rolled out across the European Union. Here is everything you need to know in 2026.</p>

<h2>What is Factur-X?</h2>

<p>Factur-X is a <strong>hybrid</strong> invoice format combining two elements in a single PDF/A-3 file:</p>

<ul>
    <li>The <strong>visual part</strong>: the readable PDF you see on screen and can print</li>
    <li>The <strong>structured part</strong>: an XML file embedded in the PDF, holding the invoice data in a standardised format</li>
</ul>

<p>This dual format lets <strong>humans</strong> read the invoice and <strong>software</strong> import it straight into the accounts.</p>

<h2>Factur-X vs ZUGFeRD: what is the difference?</h2>

<p>Technically, none. They are the <strong>same standard</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> is the name used in France and French-speaking countries (led by <a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> is the name used in Germany (led by <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Both are based on the European standard <strong>EN 16931</strong></li>
</ul>

<h2>The Factur-X profiles</h2>

<p>Factur-X offers several levels of detail:</p>

<ul>
    <li><strong>Minimum</strong>: basic information (sender, recipient, total amount)</li>
    <li><strong>Basic WL</strong>: no line items, but VAT broken down</li>
    <li><strong>Basic</strong>: with detailed invoice lines</li>
    <li><strong>EN 16931</strong> (formerly "Comfort"): the full profile compliant with the European standard – <strong>recommended</strong></li>
    <li><strong>Extended</strong>: additional data for specific needs</li>
</ul>

<p>faktur.lu generates Factur-X invoices using the <strong>EN 16931</strong> profile, the most widely accepted.</p>

<h2>Why Factur-X matters</h2>

<h3>For your business</h3>
<ul>
    <li><strong>Automated processing</strong>: your clients import the invoice without re-keying</li>
    <li><strong>Fewer errors</strong>: no manual copying of amounts and numbers</li>
    <li><strong>Faster payment</strong>: an automatically processed invoice moves through approval more quickly</li>
    <li><strong>A single document</strong>: no separate PDF and data file to send</li>
</ul>

<h3>For the European Union</h3>
<ul>
    <li>Reducing the VAT gap through structured, verifiable data</li>
    <li>Harmonising formats across Member States around EN 16931</li>
    <li>Laying the groundwork for the digital reporting planned under ViDA</li>
</ul>

<h2>Is it mandatory in Luxembourg?</h2>

<p>In 2026, Factur-X is <strong>not yet</strong> mandatory for domestic B2B in Luxembourg. However:</p>

<ul>
    <li><strong>France</strong> is making e-invoicing mandatory: receiving for all businesses on 1 September 2026, issuing for larger companies on the same date, and for micro-businesses and SMEs on 1 September 2027</li>
    <li><strong>Germany</strong> has required businesses to be able to receive B2B e-invoices since 1 January 2025; the obligation to issue phases in through 2028</li>
    <li>The EU's <strong>ViDA</strong> directive provides for mandatory intra-EU B2B e-invoicing from 1 July 2030</li>
    <li>The Luxembourg <strong>public sector</strong> already uses Peppol, mandatory since 2022-2023</li>
</ul>

<p>Getting ahead pays off: adopting Factur-X now leaves you ready for the coming obligations and makes life easier for clients who already use the format — French ones in particular from September 2026.</p>

<h2>Generating a Factur-X invoice with faktur.lu</h2>

<p>Factur-X generation is included in the <strong>Pro plan</strong>. It works on demand, on a <strong>finalised</strong> invoice:</p>

<ol>
    <li>Create and <strong>finalise</strong> your invoice — a draft cannot be converted</li>
    <li>Download the <strong>Factur-X</strong> file: a PDF/A-3 with the XML embedded</li>
    <li>Or take the <strong>XML alone</strong>, if your client only wants the structured data</li>
    <li>Send it to your client, who imports it into their accounts without re-keying</li>
</ol>

<p>The profile used is <strong>EN 16931</strong> by default, the most widely accepted.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">One detail that avoids an unpleasant surprise</p>
    <p>The standard PDF you usually send <strong>does not</strong> contain the Factur-X XML: it is a separate file, generated explicitly. As long as your clients are not asking for it, nothing forces you to change your habits — but the day a French client does ask, it is one click away.</p>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>The ViDA, French and German timetables evolve. This page is updated regularly, but check the official sources below for the dates that apply to your case.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">European Commission - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/facturation-electronique-entreprises" target="_blank" rel="noopener">France - economie.gouv.fr - Electronic invoicing</a></li>
    <li><a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE - Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD - ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Luxembourg →</a></li><li><a href="/en/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Choosing invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Generate Factur-X invoices</h3>
    <p class="text-primary-800 mb-4">The Pro plan generates the Factur-X file (PDF/A-3 with embedded XML) for your finalised invoices, using the EN 16931 profile. Stay ahead of the coming European obligations.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead"><strong>Factur-X</strong> (an Däitschland <strong>ZUGFeRD</strong> genannt) ass de Standard fir elektronesch Fakturatioun, dee vu Frankräich an Däitschland iwwerholl gouf an deem seng Aféierung an der ganzer Europäescher Unioun am Gaang ass. Hei ass alles Wesentlecht fir 2026.</p>

<h2>Wat ass Factur-X?</h2>

<p>Factur-X ass en <strong>hybrid</strong> Rechnungsformat, dat zwee Elementer an engem eenzege PDF/A-3-Fichier vereent:</p>

<ul>
    <li>De <strong>siichtbaren Deel</strong>: dat liesbaart PDF, dat Dir um Bildschierm gesitt an ausdrécke kënnt</li>
    <li>De <strong>strukturéierten Deel</strong>: en XML-Fichier, an d'PDF agebonnen, mat de Rechnungsdaten an engem normaliséierte Format</li>
</ul>

<p>Dëst Duebelformat erlaabt et gläichzäiteg de <strong>Mënschen</strong>, d'Rechnung ze liesen, an der <strong>Software</strong>, se automatesch an d'Comptabilitéit ze iwwerhuelen.</p>

<h2>Factur-X géint ZUGFeRD: wou ass den Ënnerscheed?</h2>

<p>Technesch gëtt et keen. Et handelt sech ëm <strong>dee selwechte Standard</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> ass den Numm, dee a Frankräich an am franséischsproochege Raum benotzt gëtt (gedroe vun <a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> ass den Numm, dee an Däitschland benotzt gëtt (gedroe vum <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Béid baséieren op der europäescher Norm <strong>EN 16931</strong></li>
</ul>

<h2>D'Factur-X-Profiler</h2>

<p>Factur-X kennt méi Detailstufen:</p>

<ul>
    <li><strong>Minimum</strong>: Basisinformatiounen (Ofsender, Empfänger, Gesamtbetrag)</li>
    <li><strong>Basic WL</strong>: ouni Positiounslinnen, mä mat opgedeelter TVA</li>
    <li><strong>Basic</strong>: mat detailléierte Rechnungslinnen</li>
    <li><strong>EN 16931</strong> (fréier „Comfort"): vollstännegt, normkonformt Profil – <strong>recommandéiert</strong></li>
    <li><strong>Extended</strong>: Zousazdaten fir besonnesch Ufuerderungen</li>
</ul>

<p>faktur.lu generéiert Factur-X-Rechnungen am Profil <strong>EN 16931</strong>, dat am breetsten akzeptéiert gëtt.</p>

<h2>Firwat Factur-X wichteg ass</h2>

<h3>Fir Är Entreprise</h3>
<ul>
    <li><strong>Automatiséiert Veraarbechtung</strong>: Är Clienten iwwerhuelen d'Rechnung ouni Neierfaassung</li>
    <li><strong>Manner Feeler</strong>: keng manuell Ofschrëft vu Beträg an Nummeren</li>
    <li><strong>Méi séier Bezuelung</strong>: eng automatesch veraarbecht Rechnung leeft méi séier duerch de Fräigabeprozess</li>
    <li><strong>E eenzegt Dokument</strong>: kee getrennte Versand vu PDF an Datefichier</li>
</ul>

<h3>Fir d'Europäesch Unioun</h3>
<ul>
    <li>Reduktioun vun der TVA-Lück duerch strukturéiert, iwwerpréifbar Daten</li>
    <li>Harmoniséierung vun de Formater tëscht de Member-Staaten op Basis vun EN 16931</li>
    <li>Grondlag fir déi digital Deklaratioun, déi am ViDA-Paquet virgesinn ass</li>
</ul>

<h2>Ass et zu Lëtzebuerg obligatoresch?</h2>

<p>2026 ass Factur-X fir inlännescht B2B zu Lëtzebuerg nach <strong>net</strong> obligatoresch. Trotzdem:</p>

<ul>
    <li><strong>Frankräich</strong> mécht d'elektronesch Fakturatioun obligatoresch: Empfang fir all Entreprisen den 1. September 2026, Ausstellung fir méi grouss Entreprisen zum selwechten Datum, a fir ganz kleng Entreprisen a KMU den 1. September 2027</li>
    <li><strong>Däitschland</strong> verlaangt den Empfang vun elektronesche B2B-Rechnunge scho zanter dem 1. Januar 2025; d'Ausstellungsflicht gräift gestaffelt bis 2028</li>
    <li>D'<strong>ViDA</strong>-Direktiv vun der EU gesäit d'Flicht fir innergemeinschaftlecht B2B den 1. Juli 2030 vir</li>
    <li>De Lëtzebuerger <strong>ëffentleche Secteur</strong> notzt scho Peppol, obligatoresch zanter 2022-2023</li>
</ul>

<p>Virauskucken luewt sech: wien Factur-X elo aféiert, ass fir déi kommend Flichte gerüst an erliichtert senge Clienten d'Aarbecht — besonnesch de franséischen ab September 2026.</p>

<h2>Eng Factur-X-Rechnung mat faktur.lu generéieren</h2>

<p>D'Factur-X-Generéierung ass am <strong>Pro-Plang</strong> abegraff. Si funktionéiert op Ufro, op enger <strong>finaliséierter</strong> Rechnung:</p>

<ol>
    <li>Erstellt a <strong>finaliséiert</strong> Är Rechnung — e Brouillon kann net konvertéiert ginn</li>
    <li>Luet de <strong>Factur-X</strong>-Fichier erof: e PDF/A-3 mat agebonnenem XML</li>
    <li>Oder huelt d'<strong>XML eleng</strong>, wann Äre Client nëmmen déi strukturéiert Daten wëll</li>
    <li>Iwwerdroet en Ärem Client, deen en ouni Neierfaassung a seng Comptabilitéit iwwerhëlt</li>
</ol>

<p>Benotzt gëtt standardméisseg d'Profil <strong>EN 16931</strong>, dat am breetsten akzeptéiert gëtt.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Eng Präzisioun, déi Iwwerraschungen erspuert</p>
    <p>Dat Standard-PDF, dat Dir normalerweis verschéckt, enthält <strong>kee</strong> Factur-X-XML: dat ass en eegene Fichier, deen ausdrécklech ze generéieren ass. Soulaang Är Clienten en net verlaangen, musst Dir näischt änneren — mä um Dag, wou e franséische Client dono freet, ass en mat engem Klick do.</p>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Kalennere vu ViDA, Frankräich an Däitschland entwéckele sech. Dës Säit gëtt reegelméisseg aktualiséiert — d'Datumer, déi fir Äre Fall gëllen, enthuelt Dir den offizielle Quellen hei ënnen.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Europäesch Kommissioun – VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/facturation-electronique-entreprises" target="_blank" rel="noopener">Frankräich – economie.gouv.fr – Elektronesch Fakturatioun</a></li>
    <li><a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE – Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD – ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Lëtzebuerg →</a></li><li><a href="/lb/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Factur-X-Rechnunge generéieren</h3>
    <p class="text-primary-800 mb-4">De Pro-Plang generéiert de Factur-X-Fichier (PDF/A-3 mat agebonnenem XML) vun Äre finaliséierte Rechnungen, am Profil EN 16931. Sidd de kommenden europäesche Flichte viraus.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">O <strong>Factur-X</strong> (chamado <strong>ZUGFeRD</strong> na Alemanha) é o padrão de faturação eletrónica adotado pela França e pela Alemanha, em fase de adoção em toda a União Europeia. Eis tudo o que precisa de saber em 2026.</p>

<h2>O que é o Factur-X?</h2>

<p>O Factur-X é um formato de fatura <strong>«híbrido»</strong> que combina dois elementos num único ficheiro PDF/A-3:</p>

<ul>
    <li>A <strong>parte visual</strong>: o PDF legível que vê no ecrã e pode imprimir</li>
    <li>A <strong>parte estruturada</strong>: um ficheiro XML integrado no PDF, com os dados da fatura num formato normalizado</li>
</ul>

<p>Este duplo formato permite simultaneamente que os <strong>humanos</strong> leiam a fatura e que os <strong>programas</strong> a integrem automaticamente na contabilidade.</p>

<h2>Factur-X vs ZUGFeRD: qual a diferença?</h2>

<p>Tecnicamente, nenhuma. Trata-se do <strong>mesmo padrão</strong>:</p>

<ul>
    <li><strong>Factur-X</strong> é o nome utilizado em França e nos países francófonos (promovido pela <a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE</a>)</li>
    <li><strong>ZUGFeRD</strong> é o nome utilizado na Alemanha (promovido pelo <a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD</a>, Forum elektronische Rechnung Deutschland)</li>
    <li>Ambos assentam na norma europeia <strong>EN 16931</strong></li>
</ul>

<h2>Os perfis Factur-X</h2>

<p>O Factur-X propõe vários níveis de detalhe:</p>

<ul>
    <li><strong>Minimum</strong>: informações de base (emissor, destinatário, montante total)</li>
    <li><strong>Basic WL</strong>: sem linhas de detalhe, mas com IVA repartido</li>
    <li><strong>Basic</strong>: com as linhas de fatura detalhadas</li>
    <li><strong>EN 16931</strong> (anteriormente «Comfort»): perfil completo conforme à norma europeia – <strong>recomendado</strong></li>
    <li><strong>Extended</strong>: dados suplementares para necessidades específicas</li>
</ul>

<p>O faktur.lu gera faturas Factur-X no perfil <strong>EN 16931</strong>, o mais amplamente aceite.</p>

<h2>Porque é que o Factur-X é importante</h2>

<h3>Para a sua empresa</h3>
<ul>
    <li><strong>Processamento automatizado</strong>: os seus clientes importam a fatura sem reintrodução</li>
    <li><strong>Menos erros</strong>: sem cópia manual de montantes e números</li>
    <li><strong>Pagamento mais rápido</strong>: uma fatura processada automaticamente percorre o circuito de aprovação mais depressa</li>
    <li><strong>Um único documento</strong>: sem envio separado de PDF e ficheiro de dados</li>
</ul>

<h3>Para a União Europeia</h3>
<ul>
    <li>Redução do desvio de IVA graças a dados estruturados e verificáveis</li>
    <li>Harmonização dos formatos entre Estados-Membros em torno da EN 16931</li>
    <li>Base para a declaração digital prevista no pacote ViDA</li>
</ul>

<h2>É obrigatório no Luxemburgo?</h2>

<p>Em 2026, o Factur-X <strong>ainda não</strong> é obrigatório para o B2B doméstico no Luxemburgo. No entanto:</p>

<ul>
    <li>A <strong>França</strong> torna a faturação eletrónica obrigatória: receção para todas as empresas a 1 de setembro de 2026, emissão para as maiores empresas na mesma data, e para microempresas e PME a 1 de setembro de 2027</li>
    <li>A <strong>Alemanha</strong> exige a receção de faturas eletrónicas B2B desde 1 de janeiro de 2025; a obrigação de emissão entra em vigor de forma faseada até 2028</li>
    <li>A diretiva <strong>ViDA</strong> da UE prevê a obrigação para o B2B intra-UE a 1 de julho de 2030</li>
    <li>O <strong>setor público</strong> luxemburguês já utiliza o Peppol, obrigatório desde 2022-2023</li>
</ul>

<p>Antecipar compensa: adotar o Factur-X agora deixa-o preparado para as obrigações futuras e facilita a vida aos clientes que já usam o formato — em especial os franceses a partir de setembro de 2026.</p>

<h2>Gerar uma fatura Factur-X com o faktur.lu</h2>

<p>A geração Factur-X está incluída no <strong>plano Pro</strong>. Funciona a pedido, sobre uma fatura <strong>finalizada</strong>:</p>

<ol>
    <li>Crie e <strong>finalize</strong> a sua fatura — um rascunho não pode ser convertido</li>
    <li>Descarregue o ficheiro <strong>Factur-X</strong>: um PDF/A-3 com o XML integrado</li>
    <li>Ou obtenha o <strong>XML isolado</strong>, se o seu cliente só quiser os dados estruturados</li>
    <li>Envie-o ao seu cliente, que o importa na contabilidade sem reintrodução</li>
</ol>

<p>O perfil utilizado é o <strong>EN 16931</strong> por defeito, o mais amplamente aceite.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Uma precisão que evita uma surpresa desagradável</p>
    <p>O PDF normal que envia habitualmente <strong>não contém</strong> o XML Factur-X: é um ficheiro distinto, a gerar explicitamente. Enquanto os seus clientes não o pedirem, nada o obriga a mudar de hábitos — mas no dia em que um cliente francês o pedir, está a um clique de distância.</p>
</div>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os calendários do ViDA, da França e da Alemanha evoluem. Esta página é atualizada regularmente, mas consulte as fontes oficiais abaixo para as datas exatas aplicáveis ao seu caso.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-digital-age-vida_en" target="_blank" rel="noopener">Comissão Europeia - VAT in the Digital Age (ViDA)</a></li>
    <li><a href="https://www.economie.gouv.fr/facturation-electronique-entreprises" target="_blank" rel="noopener">França - economie.gouv.fr - Faturação eletrónica</a></li>
    <li><a href="https://fnfe-mpe.org/" target="_blank" rel="noopener">FNFE-MPE - Factur-X</a></li>
    <li><a href="https://www.ferd-net.de/" target="_blank" rel="noopener">FeRD - ZUGFeRD</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol B2G Luxemburgo →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gerar faturas Factur-X</h3>
    <p class="text-primary-800 mb-4">O plano Pro gera o ficheiro Factur-X (PDF/A-3 com XML integrado) das suas faturas finalizadas, no perfil EN 16931. Antecipe-se às obrigações europeias que aí vêm.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
