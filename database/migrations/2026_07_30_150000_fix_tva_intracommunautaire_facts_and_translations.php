<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « tva-intracommunautaire-guide-entreprises-luxembourgeoises »
 * (le plus consulté du blog) — vérification factuelle et traductions intégrales.
 *
 * IMPRÉCISION CORRIGÉE : le seuil de 10 000 € était présenté comme
 * s'appliquant aux seules ventes à distance de BIENS, les services
 * électroniques étant renvoyés au régime OSS sans condition. En réalité ce
 * seuil est COMMUN aux ventes à distance de biens et aux services
 * électroniques/télécoms/audiovisuels, tous États membres confondus.
 *
 * Conséquence de l'imprécision : un indépendant vendant quelques services
 * numériques à des particuliers européens pouvait croire devoir appliquer la
 * TVA étrangère dès le premier euro, et s'inscrire à l'OSS sans nécessité.
 *
 * FAITS VÉRIFIÉS et confirmés exacts :
 * - Autoliquidation B2B services : art. 196 directive 2006/112/CE désigne le
 *   preneur comme redevable ; art. 44 fixe le lieu d'imposition ; la mention
 *   obligatoire relève de l'art. 226 §11bis. L'article distingue déjà
 *   correctement ces trois articles, erreur pourtant fréquente ailleurs.
 * - Livraison intracommunautaire de biens exonérée : art. 138.
 * - Format du numéro LU + 8 chiffres, attribué par l'AED.
 * - Obligation de vérification VIES avant de facturer hors taxes.
 * - Taux cités : Allemagne 19 %, France 20 %, Belgique 21 %.
 *
 * PARITÉ : allemand, anglais et luxembourgeois ne comptaient que 5 sections
 * sur 7 (4 586 à 5 196 caractères contre 7 241). Les cas pratiques et les
 * mentions obligatoires manquaient. Traductions désormais intégrales.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'tva-intracommunautaire-guide-entreprises-luxembourgeoises')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure contenait une
        // imprécision factuelle, la restaurer n'aurait pas de sens.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Vous facturez des clients dans d'autres pays de l'Union européenne ? La <strong>TVA intracommunautaire</strong> suit des règles spécifiques que tout entrepreneur luxembourgeois doit maîtriser. Ce guide vous explique tout.</p>

<h2>Qu'est-ce que la TVA intracommunautaire ?</h2>

<p>La TVA intracommunautaire est le régime de TVA qui s'applique aux échanges de biens et services entre entreprises situées dans différents pays de l'<strong>Union européenne</strong>. Le principe fondamental est l'<strong>autoliquidation</strong> : c'est l'acheteur, et non le vendeur, qui déclare et paie la TVA dans son pays.</p>

<h2>Le numéro de TVA intracommunautaire</h2>

<p>Au Luxembourg, votre numéro de TVA intracommunautaire a le format <strong>LU + 8 chiffres</strong> (exemple : LU12345678). Ce numéro est :</p>

<ul>
    <li>Attribué par l'<strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA)</li>
    <li>Obligatoire pour toute transaction intracommunautaire</li>
    <li>Vérifiable sur le <strong>système VIES</strong> de la Commission européenne</li>
</ul>

<p><strong>Conseil :</strong> faktur.lu vérifie automatiquement les numéros TVA via VIES lorsque vous ajoutez un client intracommunautaire.</p>

<h2>Règles de facturation intracommunautaire</h2>

<h3>Vente de services B2B (cas le plus courant)</h3>

<p>Quand vous vendez un service à une entreprise située dans un autre pays UE :</p>

<ol>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Vous mentionnez sur la facture : <strong>"Autoliquidation - Article 196 de la directive 2006/112/CE"</strong> (l'article 44 directive définit le lieu d'imposition ; c'est l'article 196 qui désigne le preneur comme redevable et qui correspond à la mention obligatoire - voir art. 226 §11bis directive)</li>
    <li>Vous indiquez votre numéro TVA <strong>et</strong> celui du client</li>
    <li>Le client déclare la TVA dans son propre pays (mécanisme de "reverse charge")</li>
</ol>

<h3>Vente de biens B2B</h3>

<p>Pour les livraisons de biens à une entreprise UE :</p>

<ol>
    <li>Vous facturez <strong>hors taxes</strong> (livraison intracommunautaire exonérée)</li>
    <li>Vous mentionnez : <strong>"Livraison intracommunautaire exonérée - Article 138 directive 2006/112/CE"</strong></li>
    <li>Vous devez prouver que les biens ont quitté le Luxembourg</li>
    <li>La transaction doit figurer dans votre <strong>état récapitulatif</strong> (déclaration intracommunautaire)</li>
</ol>

<h3>Vente à un particulier (B2C)</h3>

<p>Les règles sont différentes pour les ventes aux particuliers dans l'UE. Un <strong>seuil unique de 10 000 € par an</strong> les gouverne :</p>

<ul>
    <li><strong>Sous 10 000 € par an</strong> : vous facturez la <strong>TVA luxembourgeoise</strong>, comme à un client local</li>
    <li><strong>Au-delà de 10 000 €</strong> : vous appliquez la <strong>TVA du pays du client</strong> et la déclarez via le guichet unique <strong>OSS</strong></li>
    <li><strong>Ce seuil est commun</strong> aux ventes à distance de biens <em>et</em> aux services électroniques, de télécommunications et audiovisuels — tous pays de l'UE confondus (hors Luxembourg). Ce sont donc vos ventes B2C européennes <strong>cumulées</strong> qu'il faut suivre, et non chaque catégorie séparément</li>
    <li><strong>Autres services</strong> (conseil, formation en présentiel…) : généralement TVA luxembourgeoise, avec des exceptions selon la nature du service</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Le franchissement est immédiat</p>
    <p>L'opération qui fait dépasser les 10 000 € est <strong>déjà</strong> taxable dans le pays du client. Les ventes antérieures restent taxables au Luxembourg. Surveillez donc votre cumul en cours d'année plutôt que de le découvrir en clôture.</p>
</div>

<h2>Déclarations obligatoires</h2>

<p>En tant qu'entreprise luxembourgeoise effectuant des opérations intracommunautaires, vous devez :</p>

<ul>
    <li><strong>Déclaration TVA périodique</strong> : déclarer vos opérations intracommunautaires dans les cases appropriées</li>
    <li><strong>État récapitulatif</strong> : déclaration mensuelle ou trimestrielle listant toutes vos ventes intracommunautaires par client et par pays</li>
    <li><strong>Intrastat</strong> : déclaration statistique pour les mouvements de biens dépassant certains seuils</li>
</ul>

<h2>Validation VIES : pourquoi c'est crucial</h2>

<p>Avant de facturer HT à un client UE, vous <strong>devez vérifier</strong> que son numéro de TVA est valide via le système <strong>VIES (VAT Information Exchange System)</strong>. Si le numéro est invalide :</p>

<ul>
    <li>Vous devez facturer <strong>avec TVA luxembourgeoise</strong></li>
    <li>Vous risquez un <strong>redressement fiscal</strong> si vous facturez HT sans vérification</li>
    <li>Conservez une <strong>preuve de vérification VIES</strong> (capture d'écran ou log)</li>
</ul>

<p>faktur.lu vérifie automatiquement chaque numéro TVA intracommunautaire et conserve un log de la validation.</p>

<h2>Cas pratiques courants</h2>

<h3>Consultant luxembourgeois facturant un client allemand</h3>
<p>Vous facturez HT avec mention d'autoliquidation. Le client allemand déclare la TVA allemande (19%) dans sa propre déclaration. Vous déclarez l'opération dans votre état récapitulatif.</p>

<h3>Agence web luxembourgeoise facturant un client français</h3>
<p>Même principe : facture HT, autoliquidation. Le client français déclare 20% de TVA française. Vous devez vérifier son numéro TVA sur VIES avant de facturer.</p>

<h3>E-commerce luxembourgeois vendant à un particulier belge</h3>
<p>Tant que vos ventes B2C européennes cumulées (biens à distance + services électroniques) restent sous 10 000 EUR/an, vous facturez la TVA luxembourgeoise. Au-delà, vous appliquez la TVA du pays du client (21% en Belgique) via le régime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Mentions obligatoires sur la facture</h2>

<p>Toute facture intracommunautaire doit mentionner :</p>

<ul>
    <li>Votre numéro de TVA luxembourgeois</li>
    <li>Le numéro de TVA du client</li>
    <li>La mention légale d'exonération (autoliquidation ou livraison intracommunautaire)</li>
    <li>Le montant HT et la mention "TVA 0%"</li>
</ul>

<p>faktur.lu détecte automatiquement le scénario TVA selon le pays et le type de client, et applique les mentions légales appropriées.</p>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validation des numéros de TVA (Commission européenne)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Portail de la fiscalité indirecte</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Directive 2006/112/CE (texte consolidé)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">TVA au Luxembourg →</a></li><li><a href="/fr/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">facturer à l'étranger →</a></li><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparatif : choisir son logiciel de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez dans toute l'UE en conformité</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement les scénarios TVA intracommunautaires, vérifie les numéros TVA via VIES et applique les bonnes mentions légales.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les seuils, taux et procédures fiscales luxembourgeoises peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Sie stellen Kunden in anderen EU-Ländern Rechnungen? Die <strong>innergemeinschaftliche Mehrwertsteuer</strong> folgt besonderen Regeln, die jeder luxemburgische Unternehmer beherrschen sollte. Dieser Leitfaden erklärt sie Ihnen.</p>

<h2>Was ist die innergemeinschaftliche Mehrwertsteuer?</h2>

<p>Die innergemeinschaftliche MwSt. ist die Regelung für den Waren- und Dienstleistungsverkehr zwischen Unternehmen in verschiedenen Ländern der <strong>Europäischen Union</strong>. Grundprinzip ist die <strong>Umkehrung der Steuerschuldnerschaft</strong> (Reverse Charge): Nicht der Verkäufer, sondern der Käufer meldet und zahlt die MwSt. in seinem Land.</p>

<h2>Die innergemeinschaftliche MwSt.-Nummer</h2>

<p>In Luxemburg hat Ihre innergemeinschaftliche MwSt.-Nummer das Format <strong>LU + 8 Ziffern</strong> (Beispiel: LU12345678). Diese Nummer ist:</p>

<ul>
    <li>Von der <strong>AED</strong> (Registrierungs-, Domänen- und MwSt.-Verwaltung) zugeteilt</li>
    <li>Für jede innergemeinschaftliche Transaktion zwingend erforderlich</li>
    <li>Über das <strong>VIES-System</strong> der Europäischen Kommission überprüfbar</li>
</ul>

<p><strong>Tipp:</strong> faktur.lu prüft MwSt.-Nummern automatisch über VIES, sobald Sie einen innergemeinschaftlichen Kunden anlegen.</p>

<h2>Regeln der innergemeinschaftlichen Fakturierung</h2>

<h3>Verkauf von B2B-Dienstleistungen (häufigster Fall)</h3>

<p>Wenn Sie eine Dienstleistung an ein Unternehmen in einem anderen EU-Land verkaufen:</p>

<ol>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong></li>
    <li>Sie vermerken auf der Rechnung: <strong>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</strong> (Artikel 44 der Richtlinie bestimmt den Ort der Besteuerung; Artikel 196 benennt den Leistungsempfänger als Steuerschuldner und entspricht der Pflichtangabe – siehe Art. 226 §11bis der Richtlinie)</li>
    <li>Sie geben Ihre MwSt.-Nummer <strong>und</strong> die des Kunden an</li>
    <li>Der Kunde meldet die MwSt. in seinem eigenen Land (Reverse-Charge-Verfahren)</li>
</ol>

<h3>Verkauf von B2B-Waren</h3>

<p>Für Warenlieferungen an ein EU-Unternehmen:</p>

<ol>
    <li>Sie fakturieren <strong>ohne MwSt.</strong> (steuerfreie innergemeinschaftliche Lieferung)</li>
    <li>Sie vermerken: <strong>„Steuerfreie innergemeinschaftliche Lieferung – Artikel 138 der Richtlinie 2006/112/EG"</strong></li>
    <li>Sie müssen nachweisen, dass die Waren Luxemburg verlassen haben</li>
    <li>Die Transaktion muss in Ihrer <strong>Zusammenfassenden Meldung</strong> erscheinen</li>
</ol>

<h3>Verkauf an Privatpersonen (B2C)</h3>

<p>Für Verkäufe an Privatpersonen in der EU gelten andere Regeln. Maßgeblich ist eine <strong>einheitliche Schwelle von 10 000 € pro Jahr</strong>:</p>

<ul>
    <li><strong>Unter 10 000 € pro Jahr</strong>: Sie berechnen die <strong>luxemburgische MwSt.</strong>, wie bei einem lokalen Kunden</li>
    <li><strong>Über 10 000 €</strong>: Sie wenden die <strong>MwSt. des Kundenlandes</strong> an und melden sie über den einheitlichen Schalter <strong>OSS</strong></li>
    <li><strong>Diese Schwelle gilt gemeinsam</strong> für Fernverkäufe von Waren <em>und</em> für elektronische, Telekommunikations- und Rundfunkdienstleistungen – über alle EU-Länder hinweg (ohne Luxemburg). Zu verfolgen ist also die <strong>Summe</strong> Ihrer europäischen B2C-Umsätze, nicht jede Kategorie einzeln</li>
    <li><strong>Andere Dienstleistungen</strong> (Beratung, Präsenzschulungen…): in der Regel luxemburgische MwSt., mit Ausnahmen je nach Art der Leistung</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Die Überschreitung wirkt sofort</p>
    <p>Der Umsatz, der die 10 000 € überschreitet, ist <strong>bereits</strong> im Land des Kunden steuerpflichtig. Frühere Verkäufe bleiben in Luxemburg steuerpflichtig. Verfolgen Sie Ihre Summe daher unterjährig, statt sie zum Jahresabschluss zu entdecken.</p>
</div>

<h2>Meldepflichten</h2>

<p>Als luxemburgisches Unternehmen mit innergemeinschaftlichen Umsätzen müssen Sie:</p>

<ul>
    <li><strong>Periodische MwSt.-Erklärung</strong>: Ihre innergemeinschaftlichen Umsätze in den vorgesehenen Feldern angeben</li>
    <li><strong>Zusammenfassende Meldung</strong>: monatliche oder vierteljährliche Aufstellung aller innergemeinschaftlichen Verkäufe nach Kunde und Land</li>
    <li><strong>Intrastat</strong>: statistische Meldung für Warenbewegungen oberhalb bestimmter Schwellen</li>
</ul>

<h2>VIES-Prüfung: warum sie entscheidend ist</h2>

<p>Bevor Sie einem EU-Kunden ohne MwSt. fakturieren, <strong>müssen</strong> Sie die Gültigkeit seiner MwSt.-Nummer über das <strong>VIES-System</strong> (VAT Information Exchange System) prüfen. Ist die Nummer ungültig:</p>

<ul>
    <li>Müssen Sie <strong>mit luxemburgischer MwSt.</strong> fakturieren</li>
    <li>Riskieren Sie eine <strong>Steuernachforderung</strong>, wenn Sie ohne Prüfung steuerfrei fakturieren</li>
    <li>Bewahren Sie einen <strong>Nachweis der VIES-Prüfung</strong> auf (Screenshot oder Protokoll)</li>
</ul>

<p>faktur.lu prüft jede innergemeinschaftliche MwSt.-Nummer automatisch und protokolliert die Validierung.</p>

<h2>Häufige Praxisfälle</h2>

<h3>Luxemburgischer Berater fakturiert einen deutschen Kunden</h3>
<p>Sie fakturieren ohne MwSt. mit Reverse-Charge-Vermerk. Der deutsche Kunde meldet die deutsche MwSt. (19 %) in seiner eigenen Erklärung. Sie melden den Vorgang in Ihrer Zusammenfassenden Meldung.</p>

<h3>Luxemburgische Webagentur fakturiert einen französischen Kunden</h3>
<p>Gleiches Prinzip: Rechnung ohne MwSt., Reverse Charge. Der französische Kunde meldet 20 % französische MwSt. Prüfen Sie vor der Fakturierung seine MwSt.-Nummer über VIES.</p>

<h3>Luxemburgischer Onlinehändler verkauft an eine belgische Privatperson</h3>
<p>Solange Ihre kumulierten europäischen B2C-Umsätze (Fernverkäufe + elektronische Dienstleistungen) unter 10 000 EUR/Jahr bleiben, berechnen Sie die luxemburgische MwSt. Darüber wenden Sie die MwSt. des Kundenlandes an (21 % in Belgien) über das Verfahren <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Pflichtangaben auf der Rechnung</h2>

<p>Jede innergemeinschaftliche Rechnung muss enthalten:</p>

<ul>
    <li>Ihre luxemburgische MwSt.-Nummer</li>
    <li>Die MwSt.-Nummer des Kunden</li>
    <li>Den gesetzlichen Befreiungsvermerk (Reverse Charge oder innergemeinschaftliche Lieferung)</li>
    <li>Den Nettobetrag und den Vermerk „MwSt. 0 %"</li>
</ul>

<p>faktur.lu erkennt das MwSt.-Szenario automatisch nach Land und Kundentyp und setzt die passenden gesetzlichen Vermerke.</p>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung von MwSt.-Nummern (Europäische Kommission)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal der indirekten Steuern</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Richtlinie 2006/112/EG (konsolidierte Fassung)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">MwSt. in Luxemburg →</a></li><li><a href="/de/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Ins Ausland fakturieren →</a></li><li><a href="/de/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Vergleich: Rechnungssoftware wählen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturieren Sie EU-weit rechtskonform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt innergemeinschaftliche MwSt.-Szenarien automatisch, prüft MwSt.-Nummern über VIES und setzt die richtigen gesetzlichen Vermerke.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Luxemburgische Schwellen, Sätze und Steuerverfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich jedoch an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">Do you invoice clients in other EU countries? <strong>Intra-community VAT</strong> follows specific rules that every Luxembourg business owner should master. This guide explains them.</p>

<h2>What is intra-community VAT?</h2>

<p>Intra-community VAT is the VAT regime applying to trade in goods and services between businesses located in different <strong>European Union</strong> countries. The core principle is the <strong>reverse charge</strong>: it is the buyer, not the seller, who declares and pays the VAT in their own country.</p>

<h2>The intra-community VAT number</h2>

<p>In Luxembourg, your intra-community VAT number has the format <strong>LU + 8 digits</strong> (example: LU12345678). This number is:</p>

<ul>
    <li>Issued by the <strong>AED</strong> (Registration Duties, Estates and VAT Authority)</li>
    <li>Mandatory for every intra-community transaction</li>
    <li>Verifiable through the European Commission's <strong>VIES system</strong></li>
</ul>

<p><strong>Tip:</strong> faktur.lu automatically checks VAT numbers through VIES when you add an intra-community client.</p>

<h2>Intra-community invoicing rules</h2>

<h3>Selling B2B services (the most common case)</h3>

<p>When you sell a service to a business located in another EU country:</p>

<ol>
    <li>You invoice <strong>excluding VAT (0%)</strong></li>
    <li>You state on the invoice: <strong>"Reverse charge - Article 196 of Directive 2006/112/EC"</strong> (Article 44 of the Directive determines the place of supply; it is Article 196 that designates the customer as liable and corresponds to the mandatory wording - see art. 226 §11bis of the Directive)</li>
    <li>You state your VAT number <strong>and</strong> your client's</li>
    <li>The client declares the VAT in their own country (reverse charge mechanism)</li>
</ol>

<h3>Selling B2B goods</h3>

<p>For supplies of goods to an EU business:</p>

<ol>
    <li>You invoice <strong>excluding VAT</strong> (exempt intra-community supply)</li>
    <li>You state: <strong>"Exempt intra-community supply - Article 138 of Directive 2006/112/EC"</strong></li>
    <li>You must prove that the goods have left Luxembourg</li>
    <li>The transaction must appear in your <strong>recapitulative statement</strong></li>
</ol>

<h3>Selling to a private individual (B2C)</h3>

<p>Different rules apply to sales to private individuals in the EU. A <strong>single threshold of €10,000 per year</strong> governs them:</p>

<ul>
    <li><strong>Below €10,000 per year</strong>: you charge <strong>Luxembourg VAT</strong>, as you would to a local client</li>
    <li><strong>Above €10,000</strong>: you apply the <strong>VAT of the client's country</strong> and declare it through the <strong>OSS</strong> one-stop shop</li>
    <li><strong>This threshold is shared</strong> between distance sales of goods <em>and</em> electronic, telecommunications and broadcasting services - across all EU countries combined (excluding Luxembourg). What you must track is therefore your <strong>combined</strong> European B2C sales, not each category separately</li>
    <li><strong>Other services</strong> (consulting, in-person training…): generally Luxembourg VAT, with exceptions depending on the nature of the service</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Crossing the threshold takes effect immediately</p>
    <p>The transaction that takes you past €10,000 is <strong>already</strong> taxable in the client's country. Earlier sales remain taxable in Luxembourg. Track your running total during the year rather than discovering it at year-end.</p>
</div>

<h2>Mandatory declarations</h2>

<p>As a Luxembourg business carrying out intra-community transactions, you must file:</p>

<ul>
    <li><strong>Periodic VAT return</strong>: report your intra-community transactions in the appropriate boxes</li>
    <li><strong>Recapitulative statement</strong>: a monthly or quarterly return listing all intra-community sales by client and country</li>
    <li><strong>Intrastat</strong>: a statistical declaration for movements of goods above certain thresholds</li>
</ul>

<h2>VIES validation: why it matters</h2>

<p>Before invoicing an EU client without VAT, you <strong>must verify</strong> that their VAT number is valid through the <strong>VIES</strong> (VAT Information Exchange System). If the number is invalid:</p>

<ul>
    <li>You must invoice <strong>with Luxembourg VAT</strong></li>
    <li>You risk a <strong>tax reassessment</strong> if you invoice VAT-free without checking</li>
    <li>Keep <strong>evidence of the VIES check</strong> (screenshot or log)</li>
</ul>

<p>faktur.lu automatically checks every intra-community VAT number and keeps a log of the validation.</p>

<h2>Common practical cases</h2>

<h3>A Luxembourg consultant invoicing a German client</h3>
<p>You invoice VAT-free with a reverse charge mention. The German client declares German VAT (19%) in their own return. You report the transaction in your recapitulative statement.</p>

<h3>A Luxembourg web agency invoicing a French client</h3>
<p>Same principle: VAT-free invoice, reverse charge. The French client declares 20% French VAT. You must check their VAT number on VIES before invoicing.</p>

<h3>A Luxembourg e-commerce business selling to a Belgian consumer</h3>
<p>As long as your combined European B2C sales (distance sales of goods + electronic services) stay below €10,000 per year, you charge Luxembourg VAT. Above that, you apply the VAT of the client's country (21% in Belgium) through the <strong>OSS (One-Stop Shop)</strong> scheme.</p>

<h2>Mandatory invoice details</h2>

<p>Every intra-community invoice must state:</p>

<ul>
    <li>Your Luxembourg VAT number</li>
    <li>The client's VAT number</li>
    <li>The legal exemption wording (reverse charge or intra-community supply)</li>
    <li>The net amount and the mention "VAT 0%"</li>
</ul>

<p>faktur.lu automatically detects the VAT scenario based on country and client type, and applies the correct legal wording.</p>

<h2>Official sources</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - VAT number validation (European Commission)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED - Indirect taxation portal</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Directive 2006/112/EC (consolidated text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">VAT in Luxembourg →</a></li><li><a href="/en/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Invoicing abroad →</a></li><li><a href="/en/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparison: choosing invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice across the EU in full compliance</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically detects intra-community VAT scenarios, verifies VAT numbers through VIES and applies the correct legal wording.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Luxembourg thresholds, rates and tax procedures may change. This page is updated regularly, but for your own situation, consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Fakturéiert Dir Clienten an anere Länner vun der Europäescher Unioun? D'<strong>innergemeinschaftlech TVA</strong> follegt spezifesche Reegelen, déi all Lëtzebuerger Entrepreneur beherrsche soll. Dëse Guide erkläert se Iech.</p>

<h2>Wat ass d'innergemeinschaftlech TVA?</h2>

<p>D'innergemeinschaftlech TVA ass d'Regime, dat op den Handel mat Wueren an Déngschtleeschtungen tëscht Entreprisen a verschiddene Länner vun der <strong>Europäescher Unioun</strong> applizéiert gëtt. D'Grondprinzip ass d'<strong>Autoliquidatioun</strong>: net de Verkeefer, mä de Keefer deklaréiert a bezuelt d'TVA a sengem Land.</p>

<h2>D'innergemeinschaftlech TVA-Nummer</h2>

<p>Zu Lëtzebuerg huet Är innergemeinschaftlech TVA-Nummer d'Format <strong>LU + 8 Zifferen</strong> (Beispill: LU12345678). Dës Nummer ass:</p>

<ul>
    <li>Vun der <strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA) zougedeelt</li>
    <li>Obligatoresch fir all innergemeinschaftlech Transaktioun</li>
    <li>Iwwer de <strong>VIES-System</strong> vun der Europäescher Kommissioun iwwerpréifbar</li>
</ul>

<p><strong>Tipp:</strong> faktur.lu iwwerpréift TVA-Nummeren automatesch iwwer VIES, wann Dir en innergemeinschaftleche Client uleet.</p>

<h2>Reegele vun der innergemeinschaftlecher Fakturatioun</h2>

<h3>Verkaf vu B2B-Déngschtleeschtungen (heefegste Fall)</h3>

<p>Wann Dir eng Déngschtleeschtung un eng Entreprise an engem anere EU-Land verkeeft:</p>

<ol>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong></li>
    <li>Dir vermierkt op der Rechnung: <strong>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</strong> (den Artikel 44 vun der Direktiv bestëmmt den Ort vun der Besteierung; et ass den Artikel 196, deen de Client als Schëllner bezeechent an der obligatorescher Mentioun entsprécht – kuckt Art. 226 §11bis vun der Direktiv)</li>
    <li>Dir gitt Är TVA-Nummer <strong>an</strong> déi vum Client un</li>
    <li>De Client deklaréiert d'TVA a sengem eegene Land (Reverse-Charge-Mechanismus)</li>
</ol>

<h3>Verkaf vu B2B-Wueren</h3>

<p>Fir Wuerelieferungen un eng EU-Entreprise:</p>

<ol>
    <li>Dir fakturéiert <strong>ouni TVA</strong> (befreit innergemeinschaftlech Liwwerung)</li>
    <li>Dir vermierkt: <strong>„Befreit innergemeinschaftlech Liwwerung – Artikel 138 vun der Direktiv 2006/112/EG"</strong></li>
    <li>Dir musst beweisen, datt d'Wueren Lëtzebuerg verlooss hunn</li>
    <li>D'Transaktioun muss an Ärem <strong>Récapitulatif</strong> erschéngen</li>
</ol>

<h3>Verkaf un eng Privatpersoun (B2C)</h3>

<p>Fir Verkeef un Privatpersounen an der EU gëllen aner Reegelen. Eng <strong>eenzeg Schwell vun 10 000 € pro Joer</strong> ass entscheedend:</p>

<ul>
    <li><strong>Ënner 10 000 € pro Joer</strong>: Dir fakturéiert d'<strong>Lëtzebuerger TVA</strong>, wéi bei engem lokale Client</li>
    <li><strong>Iwwer 10 000 €</strong>: Dir applizéiert d'<strong>TVA vum Land vum Client</strong> an deklaréiert se iwwer de <strong>OSS</strong>-Guichet</li>
    <li><strong>Dës Schwell ass gemeinsam</strong> fir Fernverkeef vu Wueren <em>an</em> elektronesch, Telekom- an audiovisuell Déngschtleeschtungen – iwwer all EU-Länner zesummen (ouni Lëtzebuerg). Ze verfollegen ass dofir d'<strong>Zomm</strong> vun Äre europäesche B2C-Verkeef, net all Kategorie eenzel</li>
    <li><strong>Aner Déngschtleeschtungen</strong> (Beroodung, Formatioun a Presenz…): normalerweis Lëtzebuerger TVA, mat Ausnamen no der Aart vun der Leeschtung</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">D'Iwwerschreidung wierkt direkt</p>
    <p>D'Operatioun, déi iwwer 10 000 € geet, ass <strong>scho</strong> am Land vum Client steierflichteg. Fréier Verkeef bleiwen zu Lëtzebuerg steierflichteg. Verfollegt dofir Är Zomm am Laf vum Joer, amplaz se beim Ofschloss z'entdecken.</p>
</div>

<h2>Obligatoresch Deklaratiounen</h2>

<p>Als Lëtzebuerger Entreprise mat innergemeinschaftlechen Operatioune musst Dir:</p>

<ul>
    <li><strong>Periodesch TVA-Deklaratioun</strong>: Är innergemeinschaftlech Operatiounen an de virgesinne Felder uginn</li>
    <li><strong>Récapitulatif</strong>: monatlech oder trimestriell Opstellung vun alle innergemeinschaftleche Verkeef pro Client a Land</li>
    <li><strong>Intrastat</strong>: statistesch Deklaratioun fir Wuerebeweegungen iwwer gewësse Schwellen</li>
</ul>

<h2>VIES-Validatioun: firwat dat entscheedend ass</h2>

<p>Ier Dir engem EU-Client ouni TVA fakturéiert, <strong>musst Dir iwwerpréiwen</strong>, ob seng TVA-Nummer gëlteg ass, iwwer de <strong>VIES-System</strong> (VAT Information Exchange System). Wann d'Nummer ongëlteg ass:</p>

<ul>
    <li>Musst Dir <strong>mat Lëtzebuerger TVA</strong> fakturéieren</li>
    <li>Riskéiert Dir eng <strong>Steiernofuerderung</strong>, wann Dir ouni Iwwerpréiwung ouni TVA fakturéiert</li>
    <li>Haalt e <strong>Beweis vun der VIES-Iwwerpréiwung</strong> op (Screenshot oder Log)</li>
</ul>

<p>faktur.lu iwwerpréift all innergemeinschaftlech TVA-Nummer automatesch a protokolléiert d'Validatioun.</p>

<h2>Heefeg Praxisfäll</h2>

<h3>Lëtzebuerger Beroder fakturéiert en däitsche Client</h3>
<p>Dir fakturéiert ouni TVA mat der Mentioun vun der Autoliquidatioun. Den däitsche Client deklaréiert déi däitsch TVA (19 %) a senger eegener Deklaratioun. Dir deklaréiert d'Operatioun an Ärem Récapitulatif.</p>

<h3>Lëtzebuerger Webagence fakturéiert e franséische Client</h3>
<p>Selwecht Prinzip: Rechnung ouni TVA, Autoliquidatioun. De franséische Client deklaréiert 20 % franséisch TVA. Dir musst seng TVA-Nummer op VIES iwwerpréiwen ier Dir fakturéiert.</p>

<h3>Lëtzebuerger E-Commerce verkeeft un eng belsch Privatpersoun</h3>
<p>Soulaang Är kumuléiert europäesch B2C-Verkeef (Fernverkeef + elektronesch Déngschtleeschtungen) ënner 10 000 EUR/Joer bleiwen, fakturéiert Dir d'Lëtzebuerger TVA. Doriwwer applizéiert Dir d'TVA vum Land vum Client (21 % an der Belsch) iwwer de Regime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Obligatoresch Mentiounen op der Rechnung</h2>

<p>All innergemeinschaftlech Rechnung muss enthalen:</p>

<ul>
    <li>Är Lëtzebuerger TVA-Nummer</li>
    <li>D'TVA-Nummer vum Client</li>
    <li>D'gesetzlech Mentioun vun der Befreiung (Autoliquidatioun oder innergemeinschaftlech Liwwerung)</li>
    <li>De Betrag ouni TVA an d'Mentioun „TVA 0 %"</li>
</ul>

<p>faktur.lu erkennt d'TVA-Szenario automatesch no Land an Zort vu Client, an applizéiert déi richteg gesetzlech Mentiounen.</p>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun TVA-Nummeren (Europäesch Kommissioun)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal vun der indirekter Fiskalitéit</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Direktiv 2006/112/EG (konsolidéierten Text)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">TVA zu Lëtzebuerg →</a></li><li><a href="/lb/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">An d'Ausland fakturéieren →</a></li><li><a href="/lb/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Verglach: Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert an der ganzer EU konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt innergemeinschaftlech TVA-Szenarien automatesch, iwwerpréift TVA-Nummeren iwwer VIES an applizéiert déi richteg gesetzlech Mentiounen.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>Lëtzebuerger Schwellen, Tariffer a Steierprozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot awer Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">Fatura clientes noutros países da União Europeia? O <strong>IVA intracomunitário</strong> segue regras específicas que qualquer empresário luxemburguês deve dominar. Este guia explica-lhe tudo.</p>

<h2>O que é o IVA intracomunitário?</h2>

<p>O IVA intracomunitário é o regime de IVA aplicável às trocas de bens e serviços entre empresas situadas em diferentes países da <strong>União Europeia</strong>. O princípio fundamental é a <strong>autoliquidação</strong>: é o comprador, e não o vendedor, que declara e paga o IVA no seu país.</p>

<h2>O número de IVA intracomunitário</h2>

<p>No Luxemburgo, o seu número de IVA intracomunitário tem o formato <strong>LU + 8 dígitos</strong> (exemplo: LU12345678). Este número é:</p>

<ul>
    <li>Atribuído pela <strong>AED</strong> (Administração do registo, dos domínios e do IVA)</li>
    <li>Obrigatório para qualquer transação intracomunitária</li>
    <li>Verificável no <strong>sistema VIES</strong> da Comissão Europeia</li>
</ul>

<p><strong>Conselho:</strong> o faktur.lu verifica automaticamente os números de IVA através do VIES quando adiciona um cliente intracomunitário.</p>

<h2>Regras de faturação intracomunitária</h2>

<h3>Venda de serviços B2B (o caso mais frequente)</h3>

<p>Quando vende um serviço a uma empresa situada noutro país da UE:</p>

<ol>
    <li>Fatura <strong>sem IVA (0 %)</strong></li>
    <li>Menciona na fatura: <strong>«Autoliquidação – Artigo 196.º da Diretiva 2006/112/CE»</strong> (o artigo 44.º da diretiva define o lugar de tributação; é o artigo 196.º que designa o adquirente como devedor e que corresponde à menção obrigatória – ver art. 226.º §11bis da diretiva)</li>
    <li>Indica o seu número de IVA <strong>e</strong> o do cliente</li>
    <li>O cliente declara o IVA no seu próprio país (mecanismo de <em>reverse charge</em>)</li>
</ol>

<h3>Venda de bens B2B</h3>

<p>Para entregas de bens a uma empresa da UE:</p>

<ol>
    <li>Fatura <strong>sem IVA</strong> (entrega intracomunitária isenta)</li>
    <li>Menciona: <strong>«Entrega intracomunitária isenta – Artigo 138.º da Diretiva 2006/112/CE»</strong></li>
    <li>Deve provar que os bens saíram do Luxemburgo</li>
    <li>A transação deve constar do seu <strong>mapa recapitulativo</strong></li>
</ol>

<h3>Venda a um particular (B2C)</h3>

<p>As regras são diferentes para as vendas a particulares na UE. Um <strong>limiar único de 10 000 € por ano</strong> rege-as:</p>

<ul>
    <li><strong>Abaixo de 10 000 € por ano</strong>: fatura o <strong>IVA luxemburguês</strong>, como a um cliente local</li>
    <li><strong>Acima de 10 000 €</strong>: aplica o <strong>IVA do país do cliente</strong> e declara-o através do balcão único <strong>OSS</strong></li>
    <li><strong>Este limiar é comum</strong> às vendas à distância de bens <em>e</em> aos serviços eletrónicos, de telecomunicações e audiovisuais – todos os países da UE em conjunto (excluindo o Luxemburgo). O que deve acompanhar é, portanto, o <strong>total</strong> das suas vendas B2C europeias, e não cada categoria isoladamente</li>
    <li><strong>Outros serviços</strong> (consultoria, formação presencial…): geralmente IVA luxemburguês, com exceções consoante a natureza do serviço</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A ultrapassagem produz efeitos imediatos</p>
    <p>A operação que faz ultrapassar os 10 000 € é <strong>já</strong> tributável no país do cliente. As vendas anteriores permanecem tributáveis no Luxemburgo. Acompanhe o seu acumulado ao longo do ano em vez de o descobrir no fecho.</p>
</div>

<h2>Declarações obrigatórias</h2>

<p>Enquanto empresa luxemburguesa que realiza operações intracomunitárias, deve:</p>

<ul>
    <li><strong>Declaração periódica de IVA</strong>: declarar as suas operações intracomunitárias nos campos adequados</li>
    <li><strong>Mapa recapitulativo</strong>: declaração mensal ou trimestral listando todas as vendas intracomunitárias por cliente e por país</li>
    <li><strong>Intrastat</strong>: declaração estatística para os movimentos de bens acima de determinados limiares</li>
</ul>

<h2>Validação VIES: porque é crucial</h2>

<p>Antes de faturar sem IVA a um cliente da UE, <strong>deve verificar</strong> que o seu número de IVA é válido através do sistema <strong>VIES</strong> (VAT Information Exchange System). Se o número for inválido:</p>

<ul>
    <li>Deve faturar <strong>com IVA luxemburguês</strong></li>
    <li>Arrisca uma <strong>correção fiscal</strong> se faturar sem IVA e sem verificação</li>
    <li>Conserve uma <strong>prova da verificação VIES</strong> (captura de ecrã ou registo)</li>
</ul>

<p>O faktur.lu verifica automaticamente cada número de IVA intracomunitário e conserva um registo da validação.</p>

<h2>Casos práticos frequentes</h2>

<h3>Consultor luxemburguês a faturar um cliente alemão</h3>
<p>Fatura sem IVA com menção de autoliquidação. O cliente alemão declara o IVA alemão (19 %) na sua própria declaração. Declara a operação no seu mapa recapitulativo.</p>

<h3>Agência web luxemburguesa a faturar um cliente francês</h3>
<p>Mesmo princípio: fatura sem IVA, autoliquidação. O cliente francês declara 20 % de IVA francês. Deve verificar o número de IVA no VIES antes de faturar.</p>

<h3>Comércio eletrónico luxemburguês a vender a um particular belga</h3>
<p>Enquanto as suas vendas B2C europeias acumuladas (bens à distância + serviços eletrónicos) se mantiverem abaixo de 10 000 EUR/ano, fatura o IVA luxemburguês. Acima disso, aplica o IVA do país do cliente (21 % na Bélgica) através do regime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Menções obrigatórias na fatura</h2>

<p>Qualquer fatura intracomunitária deve mencionar:</p>

<ul>
    <li>O seu número de IVA luxemburguês</li>
    <li>O número de IVA do cliente</li>
    <li>A menção legal de isenção (autoliquidação ou entrega intracomunitária)</li>
    <li>O montante sem IVA e a menção «IVA 0 %»</li>
</ul>

<p>O faktur.lu deteta automaticamente o cenário de IVA consoante o país e o tipo de cliente, e aplica as menções legais adequadas.</p>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validação de números de IVA (Comissão Europeia)</a></li>
    <li><a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED – Portal da fiscalidade indireta</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX%3A02006L0112-20240101" target="_blank" rel="noopener">Diretiva 2006/112/CE (texto consolidado)</a></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">IVA no Luxemburgo →</a></li><li><a href="/pt/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Faturar para o estrangeiro →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparativo: escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature em toda a UE em conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente os cenários de IVA intracomunitário, verifica os números de IVA através do VIES e aplica as menções legais corretas.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos fiscais luxemburgueses podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }
};
