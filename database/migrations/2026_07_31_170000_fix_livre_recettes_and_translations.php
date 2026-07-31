<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « livre-des-recettes-luxembourg-obligations-modele » —
 * vérification factuelle et reconstruction des traductions.
 *
 * BONNE NOUVELLE SUR LES CLAIMS PRODUIT. Après trois articles où la
 * fonctionnalité était survendue, celui-ci décrit fidèlement ce qui
 * existe : RevenueBookController avec index, exportPdf et exportCsv, et
 * le chemin annoncé « Comptabilité > Livre des recettes » correspond
 * bien à AccountingNav.vue. Rien à retirer.
 *
 * UNE OMISSION TOUT DE MÊME : le plan requis. Les routes
 * /reports/revenue-book et ses exports sont protégées par
 * `plan.feature:accounting_exports`, présent dans les plans Essentiel et
 * Pro mais ABSENT du plan gratuit. L'article disait « avec faktur.lu,
 * votre livre des recettes est généré automatiquement » sans préciser le
 * plan : un utilisateur gratuit cherche la fonction et ne la trouve pas.
 *
 * Précision d'autant plus utile que l'article compare le livre au FAIA
 * en concluant « les deux sont générés automatiquement par faktur.lu » —
 * or `faia_export` figure bien dans le plan gratuit, contrairement au
 * livre. Les deux fonctions ne sont pas au même niveau d'offre, et le
 * texte le dit désormais.
 *
 * BASE LÉGALE PRÉCISÉE. L'article parlait d'« obligations comptables
 * simplifiées définies par la loi fiscale luxembourgeoise » — vague. La
 * source primaire est citable : l'article 65, paragraphe 2, de la loi
 * TVA impose de « tenir une comptabilité suffisamment détaillée pour
 * permettre l'application de la TVA et son contrôle par
 * l'administration », complété par l'article 16 du Code de commerce pour
 * la conservation.
 *
 * ENCADRÉ AED / ACD NUANCÉ. Il affirmait que « pour le livre des
 * recettes, c'est l'AED qui est compétente en contrôle ». C'est vrai
 * côté TVA, mais l'ACD peut également consulter les livres dans le cadre
 * d'un contrôle d'impôt sur le revenu. Le livre sert les deux
 * administrations ; l'encadré le dit maintenant, ce qui reste fidèle à
 * son intention de ne pas confondre les deux.
 *
 * PÉRIMÈTRE FAIA COMPLÉTÉ : « uniquement pour les entreprises au PCN
 * avec un volume transactionnel important » devient les quatre
 * conditions cumulatives, pour aligner sur les articles FAIA corrigés.
 *
 * FAIT VÉRIFIÉ ET CONFIRMÉ EXACT, laissé tel quel : la conservation du
 * livre court « à compter de la clôture de l'exercice ». C'est bien la
 * règle des LIVRES (art. 65 §4 2° : dix ans à partir de leur clôture),
 * distincte de celle des factures, qui court depuis leur date d'émission
 * — distinction établie lors de la correction de l'article archivage.
 *
 * LIEN NON VÉRIFIABLE remplacé, comme dans les articles précédents :
 * l'ELI Legilux donné pour le Code de commerce renvoie vers une
 * application JavaScript dont le contenu n'a pas pu être contrôlé.
 *
 * TRADUCTIONS RECONSTRUITES : luxembourgeois 2 934 caractères et
 * allemand 3 635 contre 7 429 en français, avec 2 liens contre 10 et
 * 5 H2 contre 7 en luxembourgeois.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    private const KEY = 'livre-des-recettes-luxembourg-obligations-modele';

    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure ne précisait pas
        // le plan requis pour la fonctionnalité.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Le <strong>livre des recettes</strong> est un document comptable simple, tenu au Luxembourg par les indépendants, freelances et petites entreprises qui ne tiennent pas une comptabilité en partie double. Il recense chronologiquement toutes les recettes encaissées. Voici tout ce qu'il faut savoir en 2026.</p>

<h2>Qu'est-ce que le livre des recettes ?</h2>

<p>Le livre des recettes est un registre chronologique qui répertorie toutes les factures émises et les paiements reçus par votre entreprise. C'est la forme la plus simple de la comptabilité exigée par la loi.</p>

<p>Le fondement est l'<strong>article 65, paragraphe 2, de la loi TVA</strong>, qui impose à tout assujetti de « tenir une comptabilité suffisamment détaillée pour permettre l'application de la TVA et son contrôle par l'administration ». La loi ne prescrit pas un modèle unique : elle fixe un résultat à atteindre. Pour un indépendant en comptabilité simplifiée, le livre des recettes est la manière habituelle d'y répondre.</p>

<p>Contrairement au grand livre comptable, il s'agit d'un document <strong>simplifié</strong>, destiné aux <strong>indépendants, freelances et petites entreprises</strong> qui ne tiennent pas une comptabilité en partie double.</p>

<h2>Qui doit tenir un livre des recettes ?</h2>

<ul>
    <li><strong>Les indépendants et professions libérales</strong> qui ne tiennent pas une comptabilité commerciale complète</li>
    <li><strong>Les petites entreprises individuelles</strong>, y compris en franchise de TVA (article 57bis LIVA, seuil 50 000 € HT depuis le 1<sup>er</sup> janvier 2025)</li>
    <li><strong>Les commerçants soumis à la comptabilité simplifiée</strong> dont le chiffre d'affaires n'excède pas le seuil de la comptabilité commerciale complète</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">À noter</p>
    <p>Les <strong>sociétés tenant une comptabilité en partie double</strong> (SARL, SA, etc.) n'ont pas à tenir un livre des recettes séparé : leur grand-livre et leurs journaux remplissent cette fonction. L'usage vise principalement les indépendants en comptabilité simplifiée.</p>
</div>

<h2>Que doit contenir le livre des recettes ?</h2>

<p>Chaque entrée doit mentionner :</p>

<ul>
    <li><strong>La date</strong> de la facture ou du paiement</li>
    <li><strong>Le numéro de facture</strong> (numérotation séquentielle, art. 63 LIVA)</li>
    <li><strong>Le nom du client</strong></li>
    <li><strong>La description</strong> de la prestation ou du bien vendu</li>
    <li><strong>Le montant hors taxes</strong></li>
    <li><strong>Le taux de TVA appliqué</strong> (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
    <li><strong>Le montant de la TVA</strong></li>
    <li><strong>Le montant toutes taxes comprises</strong></li>
</ul>

<h2>Format et conservation</h2>

<p>Le livre des recettes peut être tenu :</p>

<ul>
    <li><strong>Au format papier</strong> : dans un cahier dédié, sans ratures ni blancs</li>
    <li><strong>Au format numérique</strong> : via un logiciel de facturation, un tableur ou un PDF, avec des garanties d'intégrité</li>
</ul>

<p>Il doit être conservé <strong>dix ans à compter de sa clôture</strong> (art. 65, paragraphe 4, de la loi TVA, et article 16 du Code de commerce). Notez la nuance : pour les <strong>livres</strong>, le délai court depuis la clôture, alors que pour les <strong>factures</strong> il court depuis leur date d'émission. Voir les <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obligations comptables sur Guichet.lu</a>.</p>

<h2>Générer son livre des recettes avec faktur.lu</h2>

<p>Le livre des recettes est construit automatiquement à partir de vos factures — vous n'avez rien à ressaisir :</p>

<ol>
    <li>Allez dans <strong>Comptabilité &gt; Livre des recettes</strong></li>
    <li>Sélectionnez la période souhaitée (mois, trimestre, année)</li>
    <li>Consultez le détail de chaque facture avec ventilation de la TVA</li>
    <li>Exportez en <strong>PDF</strong> ou <strong>CSV</strong> pour votre fiduciaire</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Disponible à partir du plan Essentiel</p>
    <p>Le livre des recettes fait partie des exports comptables, inclus dans les plans <strong>Essentiel</strong> et <strong>Pro</strong>. Le plan gratuit ne le propose pas — en revanche, l'<strong>export FAIA y est bien inclus</strong>, ce qui n'est pas intuitif : les deux fonctions ne sont pas au même niveau d'offre.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">À ne pas confondre : AED et ACD</p>
    <p>L'<strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA) gère la TVA et les contrôles relatifs aux factures. L'<strong>ACD</strong> (Administration des contributions directes) gère l'impôt sur le revenu et l'impôt sur les sociétés. Le livre des recettes sert <strong>aux deux</strong> : à l'AED comme pièce TVA, à l'ACD comme base de votre résultat imposable. Un même document, deux administrations.</p>
</div>

<h2>Livre des recettes ou export FAIA ?</h2>

<p>Ne confondez pas les deux :</p>

<ul>
    <li>Le <strong>livre des recettes</strong> est un document de suivi courant, utilisé au quotidien et transmis à votre fiduciaire</li>
    <li>Le <strong>FAIA</strong> est un fichier XML structuré (norme SAF-T adaptée par l'AED), exigé uniquement lors d'un contrôle, et seulement si <strong>quatre conditions sont réunies en même temps</strong> : être soumis au plan comptable normalisé, ne pas bénéficier d'un régime simplifié, dépasser 112 000 € de chiffre d'affaires et environ 500 transactions comptables par an</li>
</ul>

<p>Autrement dit : si vous tenez un livre des recettes parce que vous êtes en comptabilité simplifiée, vous n'êtes très probablement <strong>pas</strong> soumis au FAIA. Voir notre <a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide">guide complet du FAIA</a>.</p>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Loi TVA coordonnée - article 65 (comptabilité et conservation)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obligations comptables des entreprises</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portail de la fiscalité indirecte</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 31 juillet 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Franchise TVA Luxembourg →</a></li><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">Le fichier FAIA →</a></li><li><a href="/fr/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivage des factures →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Votre livre des recettes, sans ressaisie</h3>
    <p class="text-primary-800 mb-4">faktur.lu construit votre livre des recettes à partir de vos factures et l'exporte en PDF ou CSV pour votre fiduciaire. Inclus dès le plan Essentiel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Das <strong>Einnahmenbuch</strong> ist eine einfache Buchführungsunterlage, die in Luxemburg von Selbstständigen, Freiberuflern und Kleinunternehmen geführt wird, die keine doppelte Buchführung betreiben. Es erfasst chronologisch sämtliche vereinnahmten Erlöse. Hier ist alles Wesentliche für 2026.</p>

<h2>Was ist das Einnahmenbuch?</h2>

<p>Das Einnahmenbuch ist ein chronologisches Register aller ausgestellten Rechnungen und erhaltenen Zahlungen Ihres Unternehmens. Es ist die einfachste Form der gesetzlich verlangten Buchführung.</p>

<p>Grundlage ist <strong>Artikel 65 Absatz 2 des MwSt.-Gesetzes</strong>, der jedem Steuerpflichtigen auferlegt, „eine Buchhaltung zu führen, die hinreichend detailliert ist, um die Anwendung der MwSt. und deren Kontrolle durch die Verwaltung zu ermöglichen". Das Gesetz schreibt kein einheitliches Muster vor, sondern ein zu erreichendes Ergebnis. Für einen Selbstständigen in vereinfachter Buchführung ist das Einnahmenbuch der übliche Weg dorthin.</p>

<p>Anders als das Hauptbuch handelt es sich um eine <strong>vereinfachte</strong> Unterlage für <strong>Selbstständige, Freiberufler und Kleinunternehmen</strong> ohne doppelte Buchführung.</p>

<h2>Wer muss ein Einnahmenbuch führen?</h2>

<ul>
    <li><strong>Selbstständige und freie Berufe</strong>, die keine vollständige kaufmännische Buchführung betreiben</li>
    <li><strong>Kleine Einzelunternehmen</strong>, auch in der MwSt.-Freigrenze (Artikel 57bis LIVA, Schwelle 50 000 € netto seit dem 1. Januar 2025)</li>
    <li><strong>Kaufleute in vereinfachter Buchführung</strong>, deren Umsatz die Schwelle zur vollständigen kaufmännischen Buchführung nicht überschreitet</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Hinweis</p>
    <p><strong>Gesellschaften mit doppelter Buchführung</strong> (SARL, SA usw.) müssen kein gesondertes Einnahmenbuch führen: Hauptbuch und Journale erfüllen diese Funktion. Die Praxis betrifft vor allem Selbstständige in vereinfachter Buchführung.</p>
</div>

<h2>Was muss das Einnahmenbuch enthalten?</h2>

<p>Jeder Eintrag muss ausweisen:</p>

<ul>
    <li><strong>Das Datum</strong> der Rechnung oder der Zahlung</li>
    <li><strong>Die Rechnungsnummer</strong> (fortlaufende Nummerierung, Art. 63 LIVA)</li>
    <li><strong>Den Namen des Kunden</strong></li>
    <li><strong>Die Beschreibung</strong> der Leistung oder des verkauften Gegenstands</li>
    <li><strong>Den Nettobetrag</strong></li>
    <li><strong>Den angewandten MwSt.-Satz</strong> (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
    <li><strong>Den MwSt.-Betrag</strong></li>
    <li><strong>Den Bruttobetrag</strong></li>
</ul>

<h2>Format und Aufbewahrung</h2>

<p>Das Einnahmenbuch kann geführt werden:</p>

<ul>
    <li><strong>In Papierform</strong>: in einem eigenen Heft, ohne Streichungen und Leerstellen</li>
    <li><strong>In digitaler Form</strong>: über eine Rechnungssoftware, eine Tabellenkalkulation oder ein PDF, mit Integritätsgarantien</li>
</ul>

<p>Es ist <strong>zehn Jahre ab seinem Abschluss</strong> aufzubewahren (Art. 65 Absatz 4 des MwSt.-Gesetzes und Artikel 16 des Handelsgesetzbuchs). Beachten Sie die Nuance: Bei <strong>Büchern</strong> läuft die Frist ab dem Abschluss, bei <strong>Rechnungen</strong> dagegen ab ihrem Ausstellungsdatum. Siehe die <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Buchführungspflichten auf Guichet.lu</a>.</p>

<h2>Das Einnahmenbuch mit faktur.lu erzeugen</h2>

<p>Das Einnahmenbuch entsteht automatisch aus Ihren Rechnungen — Sie müssen nichts neu erfassen:</p>

<ol>
    <li>Gehen Sie zu <strong>Buchhaltung &gt; Einnahmenbuch</strong></li>
    <li>Wählen Sie den gewünschten Zeitraum (Monat, Quartal, Jahr)</li>
    <li>Sehen Sie jede Rechnung im Detail mit MwSt.-Aufschlüsselung</li>
    <li>Exportieren Sie als <strong>PDF</strong> oder <strong>CSV</strong> für Ihren Treuhänder</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ab dem Tarif Essentiel verfügbar</p>
    <p>Das Einnahmenbuch gehört zu den Buchhaltungsexporten und ist in den Tarifen <strong>Essentiel</strong> und <strong>Pro</strong> enthalten. Der Gratis-Tarif bietet es nicht — der <strong>FAIA-Export ist dort hingegen enthalten</strong>, was nicht auf der Hand liegt: die beiden Funktionen liegen nicht auf derselben Tarifstufe.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Nicht zu verwechseln: AED und ACD</p>
    <p>Die <strong>AED</strong> (Registrierungs-, Domänen- und MwSt.-Verwaltung) betreut die MwSt. und die Rechnungsprüfungen. Die <strong>ACD</strong> (Verwaltung der direkten Steuern) betreut die Einkommen- und Körperschaftsteuer. Das Einnahmenbuch dient <strong>beiden</strong>: der AED als MwSt.-Beleg, der ACD als Grundlage Ihres steuerpflichtigen Ergebnisses. Ein Dokument, zwei Verwaltungen.</p>
</div>

<h2>Einnahmenbuch oder FAIA-Export?</h2>

<p>Verwechseln Sie die beiden nicht:</p>

<ul>
    <li>Das <strong>Einnahmenbuch</strong> ist eine laufende Übersicht, im Alltag genutzt und an den Treuhänder weitergegeben</li>
    <li>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei (von der AED angepasster SAF-T-Standard), nur bei einer Prüfung verlangt, und nur wenn <strong>vier Bedingungen gleichzeitig erfüllt</strong> sind: dem normierten Kontenplan unterliegen, keine vereinfachte Regelung in Anspruch nehmen, über 112 000 € Umsatz und etwa 500 Buchungstransaktionen jährlich liegen</li>
</ul>

<p>Anders gesagt: Wer ein Einnahmenbuch führt, weil er in vereinfachter Buchführung ist, unterliegt der FAIA höchstwahrscheinlich <strong>nicht</strong>. Siehe unseren <a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide">vollständigen FAIA-Leitfaden</a>.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordiniertes MwSt.-Gesetz – Artikel 65 (Buchführung und Aufbewahrung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Buchführungspflichten der Unternehmen</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Freigrenze Luxemburg →</a></li><li><a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">Die FAIA-Datei →</a></li><li><a href="/de/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivierung von Rechnungen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Ihr Einnahmenbuch, ohne Neuerfassung</h3>
    <p class="text-primary-800 mb-4">faktur.lu baut Ihr Einnahmenbuch aus Ihren Rechnungen auf und exportiert es als PDF oder CSV für Ihren Treuhänder. Ab dem Tarif Essentiel enthalten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">The <strong>revenue book</strong> is a simple accounting record kept in Luxembourg by freelancers, self-employed people and small businesses that do not run double-entry bookkeeping. It lists every payment received, in chronological order. Here is everything you need to know in 2026.</p>

<h2>What is the revenue book?</h2>

<p>The revenue book is a chronological register of all the invoices you issue and the payments you receive. It is the simplest form of the bookkeeping the law requires.</p>

<p>Its basis is <strong>article 65, paragraph 2, of the VAT law</strong>, which requires every taxable person to "keep accounts in sufficient detail to allow VAT to be applied and checked by the administration". The law prescribes no single template: it sets a result to achieve. For a self-employed person on simplified bookkeeping, the revenue book is the usual way to get there.</p>

<p>Unlike a general ledger, it is a <strong>simplified</strong> record, aimed at <strong>freelancers, self-employed people and small businesses</strong> without double-entry bookkeeping.</p>

<h2>Who must keep a revenue book?</h2>

<ul>
    <li><strong>Self-employed people and liberal professions</strong> who do not keep full commercial accounts</li>
    <li><strong>Small sole proprietorships</strong>, including those under the VAT exemption (article 57bis LIVA, €50,000 net threshold since 1 January 2025)</li>
    <li><strong>Traders on simplified bookkeeping</strong> whose turnover stays below the threshold for full commercial accounts</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Worth noting</p>
    <p><strong>Companies keeping double-entry accounts</strong> (SARL, SA, etc.) do not need a separate revenue book: their general ledger and journals serve that purpose. The practice mainly concerns self-employed people on simplified bookkeeping.</p>
</div>

<h2>What must the revenue book contain?</h2>

<p>Each entry must show:</p>

<ul>
    <li><strong>The date</strong> of the invoice or payment</li>
    <li><strong>The invoice number</strong> (sequential numbering, art. 63 LIVA)</li>
    <li><strong>The client's name</strong></li>
    <li><strong>A description</strong> of the service or goods sold</li>
    <li><strong>The net amount</strong></li>
    <li><strong>The VAT rate applied</strong> (17%, 14%, 8%, 3% or 0%)</li>
    <li><strong>The VAT amount</strong></li>
    <li><strong>The gross amount</strong></li>
</ul>

<h2>Format and retention</h2>

<p>The revenue book may be kept:</p>

<ul>
    <li><strong>On paper</strong>: in a dedicated notebook, with no crossings-out or blanks</li>
    <li><strong>Digitally</strong>: through invoicing software, a spreadsheet or a PDF, with integrity guarantees</li>
</ul>

<p>It must be kept for <strong>ten years from its closing</strong> (art. 65 paragraph 4 of the VAT law, and article 16 of the Commercial Code). Note the nuance: for <strong>books</strong>, the period runs from the closing, whereas for <strong>invoices</strong> it runs from the date of issue. See the <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">accounting obligations on Guichet.lu</a>.</p>

<h2>Generating your revenue book with faktur.lu</h2>

<p>The revenue book is built automatically from your invoices — nothing to re-key:</p>

<ol>
    <li>Go to <strong>Accounting &gt; Revenue book</strong></li>
    <li>Select the period you want (month, quarter, year)</li>
    <li>Review each invoice in detail with its VAT breakdown</li>
    <li>Export as <strong>PDF</strong> or <strong>CSV</strong> for your accountant</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Available from the Essentiel plan</p>
    <p>The revenue book is part of the accounting exports, included in the <strong>Essentiel</strong> and <strong>Pro</strong> plans. The free plan does not offer it — the <strong>FAIA export, however, is included</strong> there, which is not intuitive: the two features sit at different plan levels.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Not to be confused: AED and ACD</p>
    <p>The <strong>AED</strong> (Registration Duties, Estates and VAT Authority) handles VAT and invoice audits. The <strong>ACD</strong> (Direct Tax Authority) handles income tax and corporate tax. The revenue book serves <strong>both</strong>: the AED as VAT evidence, the ACD as the basis for your taxable result. One document, two authorities.</p>
</div>

<h2>Revenue book or FAIA export?</h2>

<p>Do not confuse the two:</p>

<ul>
    <li>The <strong>revenue book</strong> is a day-to-day record, used routinely and passed to your accountant</li>
    <li>The <strong>FAIA</strong> is a structured XML file (the SAF-T standard as adapted by the AED), required only during an audit, and only where <strong>four conditions are met at once</strong>: being subject to the standard chart of accounts, not benefiting from a simplified regime, exceeding €112,000 in turnover and around 500 accounting transactions a year</li>
</ul>

<p>In other words: if you keep a revenue book because you are on simplified bookkeeping, you are most likely <strong>not</strong> subject to the FAIA. See our <a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide">complete FAIA guide</a>.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Coordinated VAT law - article 65 (accounts and retention)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Accounting obligations of businesses</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Luxembourg VAT exemption →</a></li><li><a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">The FAIA file →</a></li><li><a href="/en/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archiving invoices →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Your revenue book, without re-keying</h3>
    <p class="text-primary-800 mb-4">faktur.lu builds your revenue book from your invoices and exports it as PDF or CSV for your accountant. Included from the Essentiel plan.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">D'<strong>Recettebuch</strong> ass en einfacht comptabelt Dokument, dat zu Lëtzebuerg vun Onofhängegen, Freelancer a klengen Entreprise gefouert gëtt, déi keng duebel Comptabilitéit hunn. Et erfaasst chronologesch all agenomm Recetten. Hei ass alles Wesentlecht fir 2026.</p>

<h2>Wat ass d'Recettebuch?</h2>

<p>D'Recettebuch ass e chronologescht Regëster vun alle Rechnungen, déi Dir ausstellt, a vun alle Bezuelungen, déi Dir kritt. Et ass déi einfachst Form vun der Comptabilitéit, déi d'Gesetz verlaangt.</p>

<p>D'Grondlag ass den <strong>Artikel 65, Paragraf 2, vum TVA-Gesetz</strong>, deen all Assujetti opleet, „eng Comptabilitéit ze féieren, déi genuch detailléiert ass, fir d'Applikatioun vun der TVA an hir Kontroll duerch d'Administratioun ze erlaben". D'Gesetz schreift kee eenzegt Muster vir, mä e Resultat, dat z'erreechen ass. Fir en Onofhängegen a vereinfachter Comptabilitéit ass d'Recettebuch dee gewinnte Wee dohin.</p>

<p>Am Géigesaz zum Haaptbuch handelt et sech ëm en <strong>vereinfacht</strong> Dokument, geduecht fir <strong>Onofhängeger, Freelancer a kleng Entreprisen</strong> ouni duebel Comptabilitéit.</p>

<h2>Wien muss e Recettebuch féieren?</h2>

<ul>
    <li><strong>Onofhängeger a fräi Beruffer</strong>, déi keng komplett kommerziell Comptabilitéit féieren</li>
    <li><strong>Kleng Eenzelentreprisen</strong>, och an der TVA-Franchise (Artikel 57bis LIVA, Seuil 50 000 € ouni TVA zanter dem 1. Januar 2025)</li>
    <li><strong>Händler a vereinfachter Comptabilitéit</strong>, deenen hiren Ëmsaz de Seuil fir déi komplett kommerziell Comptabilitéit net iwwerschreit</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Ze bemierken</p>
    <p><strong>Gesellschafte mat duebeler Comptabilitéit</strong> (SARL, SA asw.) mussen kee separaat Recettebuch féieren: hiert Haaptbuch an hir Journaler erfëllen dës Funktioun. D'Praxis betrëfft virun allem Onofhängeger a vereinfachter Comptabilitéit.</p>
</div>

<h2>Wat muss d'Recettebuch enthalen?</h2>

<p>All Antrag muss ausweisen:</p>

<ul>
    <li><strong>D'Datum</strong> vun der Rechnung oder vun der Bezuelung</li>
    <li><strong>D'Rechnungsnummer</strong> (fortlafend Nummeréierung, Art. 63 LIVA)</li>
    <li><strong>Den Numm vum Client</strong></li>
    <li><strong>D'Beschreiwung</strong> vun der Leeschtung oder vum verkaafte Gutt</li>
    <li><strong>De Betrag ouni TVA</strong></li>
    <li><strong>Den applizéierten TVA-Taux</strong> (17 %, 14 %, 8 %, 3 % oder 0 %)</li>
    <li><strong>Den TVA-Betrag</strong></li>
    <li><strong>De Betrag mat TVA</strong></li>
</ul>

<h2>Format an Opbewahrung</h2>

<p>D'Recettebuch kann gefouert ginn:</p>

<ul>
    <li><strong>A Pabeierform</strong>: an engem eegene Heft, ouni Duerchstreechungen a Läschten</li>
    <li><strong>An digitaler Form</strong>: iwwer eng Fakturatiounssoftware, en Tabelleblat oder e PDF, mat Integritéitsgarantien</li>
</ul>

<p>Et muss <strong>zéng Joer vu sengem Ofschloss un</strong> opbewahrt ginn (Art. 65 Paragraf 4 vum TVA-Gesetz an Artikel 16 vum Handelsgesetzbuch). Bemierkt d'Nuance: bei <strong>Bicher</strong> leeft d'Frist vum Ofschloss un, bei <strong>Rechnungen</strong> dogéint vun hirem Ausstellungsdatum un. Kuckt d'<a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">comptabel Flichten op Guichet.lu</a>.</p>

<h2>Säi Recettebuch mat faktur.lu generéieren</h2>

<p>D'Recettebuch gëtt automatesch aus Äre Rechnungen opgebaut — Dir musst näischt nei erfaassen:</p>

<ol>
    <li>Gitt op <strong>Comptabilitéit &gt; Recettebuch</strong></li>
    <li>Wielt déi gewënschte Period (Mount, Trimester, Joer)</li>
    <li>Kuckt all Rechnung am Detail mat der TVA-Opdeelung</li>
    <li>Exportéiert als <strong>PDF</strong> oder <strong>CSV</strong> fir Är Fiduciaire</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Disponibel ab dem Plang Essentiel</p>
    <p>D'Recettebuch gehéiert zu de comptabelen Exporter an ass an de Pläng <strong>Essentiel</strong> a <strong>Pro</strong> abegraff. De Gratis-Plang bitt et net — den <strong>FAIA-Export ass do hannergéint abegraff</strong>, wat net op der Hand läit: déi zwou Funktioune leien net um selwechten Niveau vun der Offer.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Net ze verwiesselen: AED an ACD</p>
    <p>D'<strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA) këmmert sech ëm d'TVA an d'Rechnungskontrollen. D'<strong>ACD</strong> (Administration des contributions directes) këmmert sech ëm d'Akommessteier an d'Gesellschaftssteier. D'Recettebuch déngt <strong>allen zwee</strong>: der AED als TVA-Beleg, der ACD als Basis vun Ärem steierflichtege Resultat. Ee Dokument, zwou Administratiounen.</p>
</div>

<h2>Recettebuch oder FAIA-Export?</h2>

<p>Verwiesselt déi zwee net:</p>

<ul>
    <li>D'<strong>Recettebuch</strong> ass en alldeeglecht Iwwersiichtsdokument, dat Dir Ärer Fiduciaire weiderginn</li>
    <li>De <strong>FAIA</strong> ass e strukturéierten XML-Fichier (SAF-T-Standard, vun der AED ugepasst), nëmme bei enger Kontroll verlaangt, an nëmme wa <strong>véier Bedingunge gläichzäiteg erfëllt</strong> sinn: dem normaliséierte Comptesplang ënnerleien, kee vereinfachte Regime a Usproch huelen, iwwer 112 000 € Ëmsaz an ongeféier 500 comptabel Transaktioune pro Joer leien</li>
</ul>

<p>Anescht gesot: wien e Recettebuch féiert, well hien a vereinfachter Comptabilitéit ass, ënnerleit dem FAIA héchstwahrscheinlech <strong>net</strong>. Kuckt eise <a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide">komplette FAIA-Guide</a>.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Koordinéiert TVA-Gesetz – Artikel 65 (Comptabilitéit an Opbewahrung)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu – Comptabel Flichte vun den Entreprisen</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Franchise Lëtzebuerg →</a></li><li><a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">De FAIA-Fichier →</a></li><li><a href="/lb/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivéierung vu Rechnungen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Äert Recettebuch, ouni Neierfaassung</h3>
    <p class="text-primary-800 mb-4">faktur.lu baut Äert Recettebuch aus Äre Rechnungen op an exportéiert et als PDF oder CSV fir Är Fiduciaire. Abegraff ab dem Plang Essentiel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">O <strong>livro de receitas</strong> é um documento contabilístico simples, mantido no Luxemburgo por independentes, freelancers e pequenas empresas que não fazem contabilidade por partidas dobradas. Regista cronologicamente todas as receitas cobradas. Eis tudo o que é preciso saber em 2026.</p>

<h2>O que é o livro de receitas?</h2>

<p>O livro de receitas é um registo cronológico de todas as faturas emitidas e dos pagamentos recebidos pela sua empresa. É a forma mais simples da contabilidade exigida por lei.</p>

<p>O fundamento é o <strong>artigo 65.º, parágrafo 2, da lei do IVA</strong>, que impõe a qualquer sujeito passivo «manter uma contabilidade suficientemente detalhada para permitir a aplicação do IVA e o seu controlo pela administração». A lei não prescreve um modelo único: fixa um resultado a atingir. Para um independente em contabilidade simplificada, o livro de receitas é a forma habitual de lá chegar.</p>

<p>Ao contrário do livro razão, trata-se de um documento <strong>simplificado</strong>, destinado a <strong>independentes, freelancers e pequenas empresas</strong> sem contabilidade por partidas dobradas.</p>

<h2>Quem deve manter um livro de receitas?</h2>

<ul>
    <li><strong>Os independentes e profissões liberais</strong> que não mantêm uma contabilidade comercial completa</li>
    <li><strong>As pequenas empresas em nome individual</strong>, incluindo em isenção de IVA (artigo 57bis LIVA, limiar de 50 000 € sem IVA desde 1 de janeiro de 2025)</li>
    <li><strong>Os comerciantes em contabilidade simplificada</strong> cujo volume de negócios não exceda o limiar da contabilidade comercial completa</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A reter</p>
    <p>As <strong>sociedades com contabilidade por partidas dobradas</strong> (SARL, SA, etc.) não têm de manter um livro de receitas separado: o razão e os diários cumprem essa função. A prática visa sobretudo os independentes em contabilidade simplificada.</p>
</div>

<h2>O que deve conter o livro de receitas?</h2>

<p>Cada registo deve indicar:</p>

<ul>
    <li><strong>A data</strong> da fatura ou do pagamento</li>
    <li><strong>O número da fatura</strong> (numeração sequencial, art. 63 LIVA)</li>
    <li><strong>O nome do cliente</strong></li>
    <li><strong>A descrição</strong> da prestação ou do bem vendido</li>
    <li><strong>O montante sem IVA</strong></li>
    <li><strong>A taxa de IVA aplicada</strong> (17 %, 14 %, 8 %, 3 % ou 0 %)</li>
    <li><strong>O montante do IVA</strong></li>
    <li><strong>O montante com IVA</strong></li>
</ul>

<h2>Formato e conservação</h2>

<p>O livro de receitas pode ser mantido:</p>

<ul>
    <li><strong>Em papel</strong>: num caderno dedicado, sem rasuras nem espaços em branco</li>
    <li><strong>Em formato digital</strong>: através de um software de faturação, uma folha de cálculo ou um PDF, com garantias de integridade</li>
</ul>

<p>Deve ser conservado <strong>dez anos a contar do seu encerramento</strong> (art. 65.º parágrafo 4 da lei do IVA e artigo 16 do Código Comercial). Repare na nuance: para os <strong>livros</strong>, o prazo corre desde o encerramento, ao passo que para as <strong>faturas</strong> corre desde a data de emissão. Ver as <a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">obrigações contabilísticas no Guichet.lu</a>.</p>

<h2>Gerar o livro de receitas com o faktur.lu</h2>

<p>O livro de receitas é construído automaticamente a partir das suas faturas — não tem nada a reintroduzir:</p>

<ol>
    <li>Vá a <strong>Contabilidade &gt; Livro de receitas</strong></li>
    <li>Selecione o período pretendido (mês, trimestre, ano)</li>
    <li>Consulte o detalhe de cada fatura com repartição do IVA</li>
    <li>Exporte em <strong>PDF</strong> ou <strong>CSV</strong> para o seu contabilista</li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Disponível a partir do plano Essentiel</p>
    <p>O livro de receitas faz parte das exportações contabilísticas, incluídas nos planos <strong>Essentiel</strong> e <strong>Pro</strong>. O plano gratuito não o propõe — em contrapartida, a <strong>exportação FAIA está aí incluída</strong>, o que não é intuitivo: as duas funcionalidades não estão ao mesmo nível de oferta.</p>
</div>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A não confundir: AED e ACD</p>
    <p>A <strong>AED</strong> (Administração do registo, dos domínios e do IVA) trata do IVA e das inspeções relativas às faturas. A <strong>ACD</strong> (Administração das contribuições diretas) trata do imposto sobre o rendimento e do imposto sobre as sociedades. O livro de receitas serve <strong>a ambas</strong>: à AED como documento de IVA, à ACD como base do seu resultado tributável. Um documento, duas administrações.</p>
</div>

<h2>Livro de receitas ou exportação FAIA?</h2>

<p>Não confunda os dois:</p>

<ul>
    <li>O <strong>livro de receitas</strong> é um documento de acompanhamento corrente, usado no dia a dia e entregue ao contabilista</li>
    <li>O <strong>FAIA</strong> é um ficheiro XML estruturado (norma SAF-T adaptada pela AED), exigido apenas numa inspeção, e apenas se <strong>quatro condições estiverem reunidas ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado, não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e cerca de 500 transações contabilísticas por ano</li>
</ul>

<p>Por outras palavras: se mantém um livro de receitas por estar em contabilidade simplificada, muito provavelmente <strong>não</strong> está sujeito ao FAIA. Ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide">guia completo do FAIA</a>.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/legislation/tva/loi/loi-tva-2023-01-01.pdf" target="_blank" rel="noopener">Lei do IVA coordenada - artigo 65.º (contabilidade e conservação)</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/gestion-juridique-comptabilite/comptable/enregistrement/obligations-comptables.html" target="_blank" rel="noopener">Guichet.lu - Obrigações contabilísticas das empresas</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portal da fiscalidade indireta</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal" class="text-primary-500 hover:text-primary-600 text-sm">Isenção de IVA no Luxemburgo →</a></li><li><a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">O ficheiro FAIA →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Arquivo das faturas →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">O seu livro de receitas, sem reintrodução</h3>
    <p class="text-primary-800 mb-4">O faktur.lu constrói o seu livro de receitas a partir das suas faturas e exporta-o em PDF ou CSV para o seu contabilista. Incluído a partir do plano Essentiel.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
