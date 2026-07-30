<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « faia-luxembourg-fichier-audit-informatise-guide » —
 * vérification factuelle et traductions intégrales.
 *
 * ERREUR PRINCIPALE — périmètre de l'obligation très surévalué.
 * L'article annonçait que le FAIA concerne « toute entreprise ou personne »
 * assujettie à la TVA, utilisant un système informatique et contrôlée par
 * l'AED. La FAQ officielle de l'AED (pfi.public.lu, FAIA-FAQ.pdf) énonce
 * au contraire QUATRE conditions cumulatives, et exclut nommément :
 *   - les assujettis non soumis au plan comptable normalisé (PCN) ;
 *   - les assujettis bénéficiant d'un régime simplifié ;
 *   - ceux dont le chiffre d'affaires annuel ne dépasse pas 112 000 EUR ;
 *   - ceux dont le volume reste « dans les limites du raisonnable
 *     (+/- 500 transactions) ».
 *
 * L'AED donne elle-même le contre-exemple : « Je réalise un chiffre
 * d'affaires de 1.000.000 EUR et je n'ai que 400 transactions. Suis-je
 * obligé de fournir un fichier FAIA ? — Non. »
 *
 * Enjeu particulier : cet article vend la fonction FAIA de faktur.lu.
 * Exagérer l'obligation revient à vendre par la peur, et se retourne
 * contre nous dès qu'un lecteur vérifie auprès de sa fiduciaire.
 *
 * BASE LÉGALE ERRONÉE — « règlement grand-ducal du 28 janvier 2009 ».
 * La base réelle est la loi du 19 décembre 2008 (Mémorial A-206 du
 * 24 décembre 2008), qui a modifié l'article 70, paragraphe 3, de la loi
 * TVA. Aucun règlement grand-ducal du 28 janvier 2009 n'institue le FAIA.
 *
 * VERSION DU SCHÉMA — « FAIA_v2.01.xsd (version 2.01 publiée en 2020) ».
 * Le 2.01 est antérieur : la FAQ de mars 2013 discute déjà du passage
 * 1.0 → 2.0. Juillet 2020 est la date de dernière mise à jour de la page
 * de l'AED, pas la date de publication du schéma.
 *
 * FAITS VÉRIFIÉS et confirmés exacts :
 * - Le FAIA se produit sur demande, jamais avec la déclaration TVA.
 * - L'AED ne met AUCUN outil de validation à disposition : « Seul le
 *   schéma publié au site Internet de l'AED peut servir de mécanisme de
 *   contrôle. » L'article le disait déjà, c'est confirmé.
 * - Trois schémas XSD : full, reduced version A, reduced version B.
 * - Aucun délai légal fixe : le contrôleur l'apprécie au cas par cas.
 *
 * PRÉCISIONS AJOUTÉES, toutes issues de la FAQ AED :
 * - Définition d'une « transaction » : une chaîne entière de
 *   comptabilisation, pas une facture. Un achat en compte quatre
 *   écritures liées et vaut UNE transaction. Sans cette définition, un
 *   lecteur comptant ses factures se croit au-dessus du seuil à tort.
 * - Non soumis au PCN : pas d'obligation FAIA, mais l'article 70 permet
 *   à l'AED d'exiger malgré tout un export structuré.
 * - Le fichier porte sur un exercice entier aligné sur l'année civile,
 *   sans exercice tronqué ni fichier multi-années.
 * - Documents sources à joindre systématiquement si la facturation est
 *   intégrée à la comptabilité.
 * - Support de transmission libre (clé USB, disque externe, e-mail…).
 *
 * PARITÉ : allemand, anglais et luxembourgeois faisaient 4 378 à
 * 4 780 caractères contre 8 145, sans tableau technique ni articles
 * connexes ; le portugais était plus complet mais incomplet.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'faia-luxembourg-fichier-audit-informatise-guide')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure surévaluait
        // largement le périmètre de l'obligation FAIA.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Le FAIA (Fichier d'Audit Informatisé) est un fichier que l'AED peut exiger lors d'un contrôle fiscal. Contrairement à une idée répandue, il ne concerne pas toutes les entreprises luxembourgeoises : quatre conditions cumulatives déterminent qui doit être capable de le produire.</p>

<h2>Qu'est-ce que le FAIA ?</h2>

<p>Le <strong>FAIA (Fichier d'Audit Informatisé)</strong>, aussi appelé <strong>SAF-T Luxembourg</strong>, est un fichier au format XML standardisé qui contient l'ensemble des données comptables et fiscales d'une entreprise pour une période donnée.</p>

<p>Sa base légale est la <strong>loi du 19 décembre 2008</strong> (Mémorial A-206 du 24 décembre 2008), qui a modifié l'<strong>article 70, paragraphe 3, de la loi TVA</strong>. Ce texte prévoit que les livres et documents existant sous forme électronique doivent, sur demande de l'administration, être communiqués « dans une forme lisible et directement intelligible » ou selon toutes autres modalités techniques que l'administration détermine. Le FAIA est la modalité retenue par l'AED.</p>

<h2>Qui doit produire un fichier FAIA ?</h2>

<p>C'est le point le plus souvent déformé. Selon la <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">FAQ officielle de l'AED</a>, l'obligation suppose que <strong>quatre conditions soient réunies en même temps</strong>.</p>

<h3>Les quatre conditions cumulatives</h3>

<ol>
    <li>Être <strong>soumis au plan comptable normalisé (PCN)</strong></li>
    <li><strong>Ne pas bénéficier d'un régime simplifié</strong></li>
    <li>Réaliser un <strong>chiffre d'affaires annuel supérieur à 112 000 €</strong></li>
    <li>Dépasser un <strong>volume d'environ 500 transactions comptables</strong> par an</li>
</ol>

<p>Si l'une seulement de ces conditions manque, vous n'êtes pas visé par le FAIA. L'AED le formule elle-même sans détour dans sa FAQ :</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>« Je réalise un chiffre d'affaires de 1.000.000 € et je n'ai que 400 transactions dans ma comptabilité. Est-ce que je suis obligé à fournir un fichier FAIA ? — <strong>Non.</strong> Bien que votre chiffre d'affaires dépasse les 112.000 €, votre volume de transaction reste dans les limites d'où un contrôle manuel est plus rationnel. »</p>
</blockquote>

<h3>Ce qu'est réellement une « transaction »</h3>

<p>Attention au comptage : une transaction <strong>n'est pas une facture</strong>. L'AED définit une transaction comme une <strong>chaîne entière de comptabilisation</strong>. Un achat, par exemple, se décompose en quatre écritures liées — compte d'achat, TVA en amont, compte fournisseur, paiement — qui forment ensemble <strong>une seule</strong> transaction.</p>

<p>Un indépendant qui compte ses 600 factures et en conclut qu'il dépasse le seuil se trompe donc probablement de mesure.</p>

<h3>Si vous n'êtes pas soumis au PCN</h3>

<p>Vous échappez à l'obligation FAIA proprement dite, même avec un chiffre d'affaires élevé et plus de 500 transactions. Mais l'article 70 continue de s'appliquer : l'AED peut vous demander d'exporter vos données électroniques <strong>dans un format délimité et structuré</strong>. Être hors FAIA ne dispense pas d'être capable de sortir sa comptabilité proprement.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Important</p>
    <p class="text-amber-700">Le FAIA n'est jamais à transmettre spontanément, et notamment <strong>pas avec votre déclaration de TVA</strong>. Il est produit <strong>uniquement sur demande</strong> d'un agent de l'AED en charge du contrôle de votre entreprise.</p>
</div>

<h2>Que contient le fichier FAIA ?</h2>

<p>Le fichier FAIA est structuré en plusieurs sections contenant :</p>

<h3>1. Informations générales (Header)</h3>

<ul>
    <li>Identification de l'entreprise (nom, adresse, numéro TVA)</li>
    <li>Période couverte par le fichier</li>
    <li>Informations sur le logiciel utilisé</li>
    <li>Date et heure de génération</li>
</ul>

<h3>2. Plan comptable (GeneralLedger)</h3>

<ul>
    <li>Liste de tous les comptes comptables utilisés</li>
    <li>Hiérarchie des comptes</li>
    <li>Soldes d'ouverture et de clôture</li>
</ul>

<h3>3. Clients et fournisseurs (MasterFiles)</h3>

<ul>
    <li>Fichier clients avec coordonnées complètes</li>
    <li>Fichier fournisseurs</li>
    <li>Numéros de TVA intracommunautaires</li>
</ul>

<h3>4. Écritures comptables (GeneralLedgerEntries)</h3>

<ul>
    <li>Toutes les écritures de la période, y compris celles sans lien direct avec la TVA — l'export doit porter sur l'entièreté de la comptabilité</li>
    <li>Journaux comptables</li>
    <li>Pièces justificatives référencées</li>
</ul>

<h3>5. Factures (SourceDocuments)</h3>

<ul>
    <li>Factures de vente émises</li>
    <li>Factures d'achat reçues</li>
    <li>Avoirs et notes de crédit</li>
    <li>Détail ligne par ligne avec TVA</li>
</ul>

<p>Si votre système de facturation est <strong>intégré à votre comptabilité</strong>, les documents sources sont à remettre systématiquement. S'il ne l'est pas, l'agent de l'AED peut demander des documents sources spécifiques.</p>

<h2>Format technique du FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Encodage</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Schéma XSD</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, dernière mise à jour publiée par l'AED en juillet 2020. Trois schémas coexistent : <em>full</em>, <em>reduced version A</em> et <em>reduced version B</em>, selon le régime comptable</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Période</td>
            <td class="border border-gray-300 px-4 py-2">Un exercice entier, aligné sur l'année civile. Les exercices tronqués sont refusés, et un fichier ne peut couvrir qu'une seule période : un contrôle portant sur trois ans exige trois fichiers</td>
        </tr>
    </tbody>
</table>

<h2>Comment générer un fichier FAIA conforme ?</h2>

<h3>Option 1 : Logiciel de facturation compatible</h3>

<p>C'est la solution la plus simple. Un logiciel comme <strong>faktur.lu</strong> génère automatiquement un fichier FAIA conforme à partir de vos données de facturation.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Export FAIA en un clic avec faktur.lu</p>
    <p class="text-green-700">Notre logiciel génère un fichier FAIA validé selon le schéma XSD officiel, prêt à être transmis à l'AED — que vous y soyez tenu aujourd'hui ou que vous franchissiez les seuils demain.</p>
</div>

<h3>Option 2 : Logiciel comptable</h3>

<p>Les logiciels de comptabilité professionnels (Sage, BOB, etc.) proposent généralement un module d'export FAIA.</p>

<h3>Option 3 : Développement sur mesure</h3>

<p>Pour les grandes entreprises avec des systèmes propriétaires, un développement spécifique peut être nécessaire pour extraire et formater les données selon le schéma FAIA.</p>

<h2>Validation du fichier FAIA</h2>

<p>Avant de transmettre votre fichier, validez-le :</p>

<ol>
    <li><strong>Validation XSD</strong> : vérifier que le fichier respecte le schéma XML officiel</li>
    <li><strong>Contrôle des totaux</strong> : s'assurer que les sommes sont cohérentes</li>
    <li><strong>Vérification des références</strong> : tous les identifiants (clients, comptes) doivent être présents</li>
</ol>

<p>L'AED est explicite sur ce point : <strong>aucun outil de validation n'est mis à disposition</strong>, et « seul le schéma publié au site Internet de l'AED peut servir de mécanisme de contrôle ». Vous pouvez donc utiliser n'importe quel validateur XML tiers (par exemple le <a href="/fr/validateur-faia">validateur faktur.lu</a>) pour vérifier la conformité avant transmission.</p>

<h2>Délais, transmission et sanctions</h2>

<h3>Délai de production</h3>

<p>Aucun délai légal fixe n'est publié par l'AED. Lorsqu'un fichier FAIA est demandé dans le cadre d'un contrôle, le délai est fixé <strong>au cas par cas par le contrôleur</strong>, selon la complexité de la demande.</p>

<h3>Support de transmission</h3>

<p>L'AED se montre flexible : tout support électronique standard disponible sur le marché est accepté — clé USB, disque dur externe, CD-R ou DVD-R, e-mail.</p>

<h3>Sanctions en cas de non-conformité</h3>

<p>Pour les entreprises effectivement soumises à l'obligation, le refus ou l'incapacité de fournir les données peut entraîner :</p>

<ul>
    <li>Des <strong>amendes administratives</strong></li>
    <li>Une <strong>taxation d'office</strong> par l'administration</li>
    <li>Le <strong>rejet de la comptabilité</strong> comme preuve</li>
</ul>

<h2>Bonnes pratiques</h2>

<ol>
    <li><strong>Vérifiez d'abord si vous êtes concerné</strong> — les quatre conditions doivent être réunies</li>
    <li><strong>Testez régulièrement</strong> votre export FAIA, pas seulement lors d'un contrôle</li>
    <li><strong>Archivez</strong> les fichiers FAIA générés pour chaque exercice</li>
    <li><strong>Vérifiez la cohérence</strong> entre vos factures et vos écritures comptables</li>
    <li><strong>Utilisez un logiciel certifié</strong> ou testé pour l'export FAIA</li>
</ol>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED - Page officielle FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED - Schémas XSD FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (champ d'application et exclusions)</a></li>
</ul>

<h2>Conclusion</h2>

<p>Le FAIA est une obligation réelle, mais ciblée : elle vise les entreprises soumises au plan comptable normalisé, au régime normal, au-delà de 112 000 € de chiffre d'affaires et d'environ 500 transactions annuelles. Beaucoup d'indépendants et de petites structures n'y sont pas tenus.</p>

<p>Si vous êtes concerné — ou si votre croissance vous y amène — un logiciel de facturation capable de produire le fichier vous évite de découvrir le problème le jour du contrôle. faktur.lu intègre nativement l'export FAIA, validé selon le schéma officiel de l'AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">contrôle fiscal →</a></li><li><a href="/fr/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">archivage des factures →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les seuils et procédures peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Die FAIA (informatisierte Audit-Datei) ist eine Datei, die die AED bei einer Steuerprüfung verlangen kann. Entgegen einer verbreiteten Vorstellung betrifft sie nicht alle luxemburgischen Unternehmen: Vier kumulative Bedingungen bestimmen, wer sie erzeugen können muss.</p>

<h2>Was ist die FAIA?</h2>

<p>Die <strong>FAIA (Fichier d'Audit Informatisé)</strong>, auch <strong>SAF-T Luxemburg</strong> genannt, ist eine Datei im standardisierten XML-Format, die sämtliche Buchhaltungs- und Steuerdaten eines Unternehmens für einen bestimmten Zeitraum enthält.</p>

<p>Ihre Rechtsgrundlage ist das <strong>Gesetz vom 19. Dezember 2008</strong> (Mémorial A-206 vom 24. Dezember 2008), das <strong>Artikel 70 Absatz 3 des MwSt.-Gesetzes</strong> geändert hat. Danach müssen Bücher und Unterlagen, die in elektronischer Form vorliegen, auf Verlangen der Verwaltung „in lesbarer und unmittelbar verständlicher Form" oder nach anderen von der Verwaltung bestimmten technischen Modalitäten übermittelt werden. Die FAIA ist die von der AED gewählte Modalität.</p>

<h2>Wer muss eine FAIA-Datei erzeugen?</h2>

<p>Das ist der am häufigsten verzerrte Punkt. Nach der <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offiziellen FAQ der AED</a> setzt die Pflicht voraus, dass <strong>vier Bedingungen gleichzeitig erfüllt</strong> sind.</p>

<h3>Die vier kumulativen Bedingungen</h3>

<ol>
    <li>Dem <strong>normierten Kontenplan (PCN)</strong> unterliegen</li>
    <li><strong>Keine vereinfachte Regelung</strong> in Anspruch nehmen</li>
    <li>Einen <strong>Jahresumsatz von über 112 000 €</strong> erzielen</li>
    <li>Ein Volumen von <strong>etwa 500 Buchungstransaktionen</strong> jährlich überschreiten</li>
</ol>

<p>Fehlt auch nur eine dieser Bedingungen, sind Sie von der FAIA nicht betroffen. Die AED formuliert es in ihrer FAQ unmissverständlich:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ich erziele einen Umsatz von 1.000.000 € und habe nur 400 Transaktionen in meiner Buchhaltung. Bin ich verpflichtet, eine FAIA-Datei zu liefern? — <strong>Nein.</strong> Obwohl Ihr Umsatz die 112.000 € übersteigt, bleibt Ihr Transaktionsvolumen in Grenzen, in denen eine manuelle Prüfung rationeller ist."</p>
</blockquote>

<h3>Was eine „Transaktion" wirklich ist</h3>

<p>Vorsicht beim Zählen: Eine Transaktion <strong>ist keine Rechnung</strong>. Die AED definiert sie als eine <strong>vollständige Buchungskette</strong>. Ein Einkauf zerfällt beispielsweise in vier verbundene Buchungen — Aufwandskonto, Vorsteuer, Lieferantenkonto, Zahlung —, die zusammen <strong>eine einzige</strong> Transaktion bilden.</p>

<p>Wer seine 600 Rechnungen zählt und daraus schließt, die Schwelle zu überschreiten, misst also wahrscheinlich das Falsche.</p>

<h3>Wenn Sie dem PCN nicht unterliegen</h3>

<p>Dann entgehen Sie der eigentlichen FAIA-Pflicht, selbst bei hohem Umsatz und mehr als 500 Transaktionen. Artikel 70 gilt jedoch weiter: Die AED kann verlangen, dass Sie Ihre elektronischen Daten <strong>in einem abgegrenzten und strukturierten Format</strong> exportieren. Außerhalb der FAIA zu stehen entbindet nicht davon, seine Buchhaltung sauber ausgeben zu können.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Wichtig</p>
    <p class="text-amber-700">Die FAIA ist niemals unaufgefordert zu übermitteln, insbesondere <strong>nicht zusammen mit Ihrer MwSt.-Erklärung</strong>. Sie wird <strong>ausschließlich auf Verlangen</strong> eines mit der Prüfung Ihres Unternehmens betrauten AED-Bediensteten erzeugt.</p>
</div>

<h2>Was enthält die FAIA-Datei?</h2>

<p>Die FAIA-Datei ist in mehrere Abschnitte gegliedert:</p>

<h3>1. Allgemeine Angaben (Header)</h3>

<ul>
    <li>Identifikation des Unternehmens (Name, Anschrift, MwSt.-Nummer)</li>
    <li>Von der Datei abgedeckter Zeitraum</li>
    <li>Angaben zur verwendeten Software</li>
    <li>Datum und Uhrzeit der Erzeugung</li>
</ul>

<h3>2. Kontenplan (GeneralLedger)</h3>

<ul>
    <li>Liste aller verwendeten Buchhaltungskonten</li>
    <li>Kontenhierarchie</li>
    <li>Eröffnungs- und Schlusssalden</li>
</ul>

<h3>3. Kunden und Lieferanten (MasterFiles)</h3>

<ul>
    <li>Kundenstammdaten mit vollständigen Kontaktangaben</li>
    <li>Lieferantenstammdaten</li>
    <li>Innergemeinschaftliche MwSt.-Nummern</li>
</ul>

<h3>4. Buchungssätze (GeneralLedgerEntries)</h3>

<ul>
    <li>Alle Buchungen des Zeitraums, auch ohne unmittelbaren MwSt.-Bezug — der Export muss die gesamte Buchhaltung umfassen</li>
    <li>Buchungsjournale</li>
    <li>Referenzierte Belege</li>
</ul>

<h3>5. Rechnungen (SourceDocuments)</h3>

<ul>
    <li>Ausgestellte Ausgangsrechnungen</li>
    <li>Erhaltene Eingangsrechnungen</li>
    <li>Gutschriften und Stornorechnungen</li>
    <li>Positionsweise Aufschlüsselung mit MwSt.</li>
</ul>

<p>Ist Ihr Fakturierungssystem <strong>in die Buchhaltung integriert</strong>, sind die Quelldokumente systematisch mitzuliefern. Ist es das nicht, kann der AED-Bedienstete gezielt einzelne Quelldokumente anfordern.</p>

<h2>Technisches Format der FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Kodierung</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD-Schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, letzte von der AED veröffentlichte Aktualisierung im Juli 2020. Drei Schemata bestehen nebeneinander: <em>full</em>, <em>reduced version A</em> und <em>reduced version B</em>, je nach Buchführungsregime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Zeitraum</td>
            <td class="border border-gray-300 px-4 py-2">Ein vollständiges Geschäftsjahr, ausgerichtet am Kalenderjahr. Verkürzte Geschäftsjahre werden abgelehnt, und eine Datei darf nur einen Zeitraum abdecken: Eine Prüfung über drei Jahre erfordert drei Dateien</td>
        </tr>
    </tbody>
</table>

<h2>Wie erzeugt man eine konforme FAIA-Datei?</h2>

<h3>Option 1: Kompatible Rechnungssoftware</h3>

<p>Das ist die einfachste Lösung. Eine Software wie <strong>faktur.lu</strong> erzeugt automatisch eine konforme FAIA-Datei aus Ihren Fakturierungsdaten.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ FAIA-Export mit einem Klick bei faktur.lu</p>
    <p class="text-green-700">Unsere Software erzeugt eine nach dem offiziellen XSD-Schema validierte FAIA-Datei, bereit zur Übermittlung an die AED — ob Sie heute dazu verpflichtet sind oder die Schwellen morgen überschreiten.</p>
</div>

<h3>Option 2: Buchhaltungssoftware</h3>

<p>Professionelle Buchhaltungsprogramme (Sage, BOB usw.) bieten in der Regel ein FAIA-Exportmodul.</p>

<h3>Option 3: Individualentwicklung</h3>

<p>Für Großunternehmen mit proprietären Systemen kann eine spezifische Entwicklung nötig sein, um die Daten nach dem FAIA-Schema zu extrahieren und zu formatieren.</p>

<h2>Validierung der FAIA-Datei</h2>

<p>Validieren Sie Ihre Datei vor der Übermittlung:</p>

<ol>
    <li><strong>XSD-Validierung</strong>: prüfen, ob die Datei dem offiziellen XML-Schema entspricht</li>
    <li><strong>Kontrolle der Summen</strong>: sicherstellen, dass die Beträge stimmig sind</li>
    <li><strong>Prüfung der Referenzen</strong>: alle Kennungen (Kunden, Konten) müssen vorhanden sein</li>
</ol>

<p>Die AED ist hier eindeutig: <strong>Es wird kein Validierungswerkzeug bereitgestellt</strong>, und „nur das auf der Website der AED veröffentlichte Schema kann als Kontrollmechanismus dienen". Sie können also jeden beliebigen XML-Validator eines Drittanbieters nutzen (etwa den <a href="/de/validateur-faia">faktur.lu-Validator</a>), um die Konformität vorab zu prüfen.</p>

<h2>Fristen, Übermittlung und Sanktionen</h2>

<h3>Frist zur Erstellung</h3>

<p>Die AED veröffentlicht keine feste gesetzliche Frist. Wird eine FAIA-Datei im Rahmen einer Prüfung verlangt, legt <strong>der Prüfer die Frist im Einzelfall</strong> fest, je nach Komplexität der Anfrage.</p>

<h3>Übermittlungsmedium</h3>

<p>Die AED zeigt sich flexibel: Jeder marktübliche elektronische Datenträger wird akzeptiert — USB-Stick, externe Festplatte, CD-R oder DVD-R, E-Mail.</p>

<h3>Sanktionen bei Nichterfüllung</h3>

<p>Für tatsächlich verpflichtete Unternehmen kann die Weigerung oder Unfähigkeit, die Daten zu liefern, Folgendes nach sich ziehen:</p>

<ul>
    <li><strong>Verwaltungsbußgelder</strong></li>
    <li>Eine <strong>Schätzung von Amts wegen</strong> durch die Verwaltung</li>
    <li>Die <strong>Verwerfung der Buchhaltung</strong> als Beweismittel</li>
</ul>

<h2>Bewährte Praxis</h2>

<ol>
    <li><strong>Prüfen Sie zuerst, ob Sie betroffen sind</strong> — alle vier Bedingungen müssen erfüllt sein</li>
    <li><strong>Testen Sie Ihren FAIA-Export regelmäßig</strong>, nicht erst bei einer Prüfung</li>
    <li><strong>Archivieren Sie</strong> die erzeugten FAIA-Dateien für jedes Geschäftsjahr</li>
    <li><strong>Prüfen Sie die Stimmigkeit</strong> zwischen Rechnungen und Buchungen</li>
    <li><strong>Nutzen Sie zertifizierte</strong> oder getestete Software für den FAIA-Export</li>
</ol>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Offizielle FAIA-Seite</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – XSD-Schemata FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Anwendungsbereich und Ausschlüsse)</a></li>
</ul>

<h2>Fazit</h2>

<p>Die FAIA ist eine echte, aber gezielte Pflicht: Sie betrifft Unternehmen, die dem normierten Kontenplan und dem normalen Regime unterliegen, über 112 000 € Umsatz und etwa 500 Jahrestransaktionen hinaus. Viele Selbstständige und kleine Strukturen sind nicht dazu verpflichtet.</p>

<p>Sind Sie betroffen — oder führt Ihr Wachstum Sie dorthin —, erspart Ihnen eine Rechnungssoftware, die die Datei erzeugen kann, die Entdeckung des Problems am Prüfungstag. faktur.lu integriert den FAIA-Export nativ, validiert nach dem offiziellen Schema der AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Steuerprüfung →</a></li><li><a href="/de/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivierung von Rechnungen →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen und Verfahren können sich ändern. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">The FAIA (computerised audit file) is a file the AED can request during a tax audit. Contrary to a widespread belief, it does not concern every Luxembourg business: four cumulative conditions determine who must be able to produce it.</p>

<h2>What is the FAIA?</h2>

<p>The <strong>FAIA (Fichier d'Audit Informatisé)</strong>, also known as <strong>SAF-T Luxembourg</strong>, is a standardised XML file containing all of a company's accounting and tax data for a given period.</p>

<p>Its legal basis is the <strong>law of 19 December 2008</strong> (Mémorial A-206 of 24 December 2008), which amended <strong>article 70, paragraph 3, of the VAT law</strong>. That text provides that books and documents held in electronic form must, at the administration's request, be communicated "in a legible and directly intelligible form" or according to any other technical arrangements the administration determines. The FAIA is the arrangement chosen by the AED.</p>

<h2>Who must produce a FAIA file?</h2>

<p>This is the most frequently distorted point. According to the <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED's official FAQ</a>, the obligation requires <strong>four conditions to be met at the same time</strong>.</p>

<h3>The four cumulative conditions</h3>

<ol>
    <li>Being <strong>subject to the standard chart of accounts (PCN)</strong></li>
    <li><strong>Not benefiting from a simplified regime</strong></li>
    <li>Achieving <strong>annual turnover above €112,000</strong></li>
    <li>Exceeding a volume of roughly <strong>500 accounting transactions</strong> per year</li>
</ol>

<p>If even one of these conditions is missing, the FAIA does not apply to you. The AED puts it plainly in its own FAQ:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>"I have turnover of €1,000,000 and only 400 transactions in my accounts. Am I required to provide a FAIA file? — <strong>No.</strong> Although your turnover exceeds €112,000, your transaction volume remains within limits where a manual audit is more rational."</p>
</blockquote>

<h3>What a "transaction" actually is</h3>

<p>Be careful how you count: a transaction <strong>is not an invoice</strong>. The AED defines it as an <strong>entire posting chain</strong>. A purchase, for instance, breaks down into four linked entries — expense account, input VAT, supplier account, payment — which together form <strong>a single</strong> transaction.</p>

<p>A freelancer counting 600 invoices and concluding they are over the threshold is therefore probably measuring the wrong thing.</p>

<h3>If you are not subject to the PCN</h3>

<p>You escape the FAIA obligation as such, even with high turnover and more than 500 transactions. But article 70 still applies: the AED may require you to export your electronic data <strong>in a delimited, structured format</strong>. Being outside the FAIA does not excuse you from being able to output your accounts cleanly.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Important</p>
    <p class="text-amber-700">The FAIA is never submitted spontaneously, and in particular <strong>not with your VAT return</strong>. It is produced <strong>solely on request</strong> from an AED officer in charge of auditing your business.</p>
</div>

<h2>What does the FAIA file contain?</h2>

<p>The FAIA file is structured into several sections:</p>

<h3>1. General information (Header)</h3>

<ul>
    <li>Company identification (name, address, VAT number)</li>
    <li>Period covered by the file</li>
    <li>Information on the software used</li>
    <li>Date and time of generation</li>
</ul>

<h3>2. Chart of accounts (GeneralLedger)</h3>

<ul>
    <li>List of all accounting accounts used</li>
    <li>Account hierarchy</li>
    <li>Opening and closing balances</li>
</ul>

<h3>3. Customers and suppliers (MasterFiles)</h3>

<ul>
    <li>Customer file with full contact details</li>
    <li>Supplier file</li>
    <li>Intra-community VAT numbers</li>
</ul>

<h3>4. Accounting entries (GeneralLedgerEntries)</h3>

<ul>
    <li>All entries for the period, including those with no direct VAT relevance — the export must cover the accounts in their entirety</li>
    <li>Accounting journals</li>
    <li>Referenced supporting documents</li>
</ul>

<h3>5. Invoices (SourceDocuments)</h3>

<ul>
    <li>Sales invoices issued</li>
    <li>Purchase invoices received</li>
    <li>Credit notes</li>
    <li>Line-by-line detail with VAT</li>
</ul>

<p>If your invoicing system is <strong>integrated with your accounts</strong>, source documents must be supplied systematically. If it is not, the AED officer may request specific source documents.</p>

<h2>FAIA technical format</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Encoding</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, last update published by the AED in July 2020. Three schemas coexist: <em>full</em>, <em>reduced version A</em> and <em>reduced version B</em>, depending on the accounting regime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Period</td>
            <td class="border border-gray-300 px-4 py-2">A full financial year, aligned on the calendar year. Truncated years are refused, and one file may cover only one period: an audit spanning three years requires three files</td>
        </tr>
    </tbody>
</table>

<h2>How to generate a compliant FAIA file</h2>

<h3>Option 1: compatible invoicing software</h3>

<p>This is the simplest route. Software such as <strong>faktur.lu</strong> automatically generates a compliant FAIA file from your invoicing data.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ One-click FAIA export with faktur.lu</p>
    <p class="text-green-700">Our software generates a FAIA file validated against the official XSD schema, ready to be sent to the AED — whether you are required to today or cross the thresholds tomorrow.</p>
</div>

<h3>Option 2: accounting software</h3>

<p>Professional accounting packages (Sage, BOB, etc.) generally offer a FAIA export module.</p>

<h3>Option 3: custom development</h3>

<p>For large companies with proprietary systems, specific development may be needed to extract and format the data according to the FAIA schema.</p>

<h2>Validating the FAIA file</h2>

<p>Before submitting your file, validate it:</p>

<ol>
    <li><strong>XSD validation</strong>: check that the file complies with the official XML schema</li>
    <li><strong>Totals check</strong>: make sure the sums are consistent</li>
    <li><strong>Reference check</strong>: every identifier (customers, accounts) must be present</li>
</ol>

<p>The AED is explicit on this point: <strong>no validation tool is provided</strong>, and "only the schema published on the AED website may serve as a control mechanism". You may therefore use any third-party XML validator (such as the <a href="/en/validateur-faia">faktur.lu validator</a>) to check compliance before submitting.</p>

<h2>Deadlines, submission and penalties</h2>

<h3>Production deadline</h3>

<p>The AED publishes no fixed statutory deadline. Where a FAIA file is requested during an audit, the deadline is set <strong>case by case by the auditor</strong>, depending on the complexity of the request.</p>

<h3>Submission medium</h3>

<p>The AED is flexible: any standard electronic medium available on the market is accepted — USB stick, external hard drive, CD-R or DVD-R, e-mail.</p>

<h3>Penalties for non-compliance</h3>

<p>For businesses that genuinely fall under the obligation, refusing or being unable to supply the data may lead to:</p>

<ul>
    <li><strong>Administrative fines</strong></li>
    <li><strong>Assessment on the administration's own estimate</strong></li>
    <li><strong>Rejection of the accounts</strong> as evidence</li>
</ul>

<h2>Best practice</h2>

<ol>
    <li><strong>First check whether you are concerned</strong> — all four conditions must be met</li>
    <li><strong>Test your FAIA export regularly</strong>, not only during an audit</li>
    <li><strong>Archive</strong> the FAIA files generated for each financial year</li>
    <li><strong>Check consistency</strong> between your invoices and your accounting entries</li>
    <li><strong>Use certified</strong> or tested software for the FAIA export</li>
</ol>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED - Official FAIA page</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED - FAIA 2.01 XSD schemas</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAIA FAQ (scope and exclusions)</a></li>
</ul>

<h2>Conclusion</h2>

<p>The FAIA is a real obligation, but a targeted one: it applies to businesses subject to the standard chart of accounts and the normal regime, above €112,000 in turnover and around 500 annual transactions. Many freelancers and small structures are simply not covered.</p>

<p>If you are concerned — or if growth takes you there — invoicing software able to produce the file spares you discovering the problem on audit day. faktur.lu includes FAIA export natively, validated against the AED's official schema.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Tax audits →</a></li><li><a href="/en/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archiving invoices →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Thresholds and procedures may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">De FAIA (informatiséierten Audit-Fichier) ass e Fichier, deen d'AED bei enger Steierkontroll ka verlaangen. Am Géigesaz zu enger verbreeter Virstellung betrëfft en net all Lëtzebuerger Entreprise: véier kumulativ Bedingunge bestëmmen, wien en muss kënne produzéieren.</p>

<h2>Wat ass de FAIA?</h2>

<p>De <strong>FAIA (Fichier d'Audit Informatisé)</strong>, och <strong>SAF-T Lëtzebuerg</strong> genannt, ass e Fichier am standardiséierten XML-Format, deen all d'Comptabilitéits- a Steierdate vun enger Entreprise fir eng bestëmmte Period enthält.</p>

<p>Seng gesetzlech Basis ass d'<strong>Gesetz vum 19. Dezember 2008</strong> (Mémorial A-206 vum 24. Dezember 2008), dat den <strong>Artikel 70, Paragraf 3, vum TVA-Gesetz</strong> geännert huet. Deen Text gesäit vir, datt Bicher an Dokumenter, déi an elektronescher Form existéieren, op Ufro vun der Administratioun „an enger liesbarer an direkt verständlecher Form" oder no anere technesche Modalitéiten, déi d'Administratioun bestëmmt, mussen iwwerdroe ginn. De FAIA ass d'Modalitéit, déi d'AED gewielt huet.</p>

<h2>Wien muss e FAIA-Fichier produzéieren?</h2>

<p>Dat ass de Punkt, deen am dackste verzerrt gëtt. No der <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offizieller FAQ vun der AED</a> setzt d'Flicht viraus, datt <strong>véier Bedingungen zur selwechter Zäit erfëllt</strong> sinn.</p>

<h3>Déi véier kumulativ Bedingungen</h3>

<ol>
    <li>Dem <strong>normaliséierte Comptesplang (PCN)</strong> ënnerleien</li>
    <li><strong>Kee vereinfachte Regime</strong> a Usproch huelen</li>
    <li>E <strong>Joresëmsaz vun iwwer 112 000 €</strong> realiséieren</li>
    <li>E Volume vun ongeféier <strong>500 comptabel Transaktiounen</strong> pro Joer iwwerschreiden</li>
</ol>

<p>Wann nëmmen eng vun dëse Bedingunge feelt, sidd Dir vum FAIA net betraff. D'AED formuléiert et a hirer FAQ ouni Ëmschwëff:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ech realiséieren en Ëmsaz vun 1.000.000 € an hunn nëmmen 400 Transaktiounen a menger Comptabilitéit. Sinn ech verflicht, e FAIA-Fichier ze liwweren? — <strong>Neen.</strong> Och wann Ären Ëmsaz déi 112.000 € iwwerschreit, bleift Äre Transaktiounsvolume a Grenzen, wou eng manuell Kontroll méi rationell ass."</p>
</blockquote>

<h3>Wat eng „Transaktioun" wierklech ass</h3>

<p>Oppassen beim Zielen: eng Transaktioun <strong>ass keng Rechnung</strong>. D'AED definéiert se als eng <strong>ganz Comptabiliséierungskette</strong>. En Akaf zerfält zum Beispill a véier verbonnen Écritureën — Akafskont, TVA en amont, Fournisseurskont, Bezuelung —, déi zesummen <strong>eng eenzeg</strong> Transaktioun bilden.</p>

<p>Wien seng 600 Rechnungen zielt an doraus schléisst, de Seuil ze iwwerschreiden, miesst also wahrscheinlech dat Falscht.</p>

<h3>Wann Dir dem PCN net ënnerleit</h3>

<p>Da entkommt Dir der eigentlecher FAIA-Flicht, souguer mat engem héijen Ëmsaz a méi wéi 500 Transaktiounen. Mä den Artikel 70 gëllt weider: d'AED ka verlaangen, datt Dir Är elektronesch Daten <strong>an engem ofgegrenzten a strukturéierte Format</strong> exportéiert. Ausserhalb vum FAIA ze stoen entbënnt net dovunner, seng Comptabilitéit propper erausginn ze kënnen.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Wichteg</p>
    <p class="text-amber-700">De FAIA gëtt ni spontan iwwerdroen, a besonnesch <strong>net mat Ärer TVA-Deklaratioun</strong>. En gëtt <strong>eleng op Ufro</strong> vun engem AED-Agent produzéiert, deen d'Kontroll vun Ärer Entreprise mécht.</p>
</div>

<h2>Wat enthält de FAIA-Fichier?</h2>

<p>De FAIA-Fichier ass a méi Sektiounen ënnerdeelt:</p>

<h3>1. Allgemeng Informatiounen (Header)</h3>

<ul>
    <li>Identifikatioun vun der Entreprise (Numm, Adress, TVA-Nummer)</li>
    <li>Period, déi de Fichier ofdeckt</li>
    <li>Informatiounen iwwer déi benotzte Software</li>
    <li>Datum an Auerzäit vun der Generéierung</li>
</ul>

<h3>2. Comptesplang (GeneralLedger)</h3>

<ul>
    <li>Lëscht vun alle benotzte Comptabilitéitskonten</li>
    <li>Hierarchie vun de Konten</li>
    <li>Ufanks- a Schlusssoldeën</li>
</ul>

<h3>3. Clienten a Fournisseuren (MasterFiles)</h3>

<ul>
    <li>Clientsfichier mat vollstännege Kontaktdaten</li>
    <li>Fournisseursfichier</li>
    <li>Innergemeinschaftlech TVA-Nummeren</li>
</ul>

<h3>4. Comptabel Écritureën (GeneralLedgerEntries)</h3>

<ul>
    <li>All Écritureë vun der Period, och déi ouni direkte Bezuch zur TVA — den Export muss d'ganz Comptabilitéit ëmfaassen</li>
    <li>Comptabilitéitsjournaler</li>
    <li>Referenzéiert Beleeger</li>
</ul>

<h3>5. Rechnungen (SourceDocuments)</h3>

<ul>
    <li>Ausgestallt Verkafsrechnungen</li>
    <li>Erhalen Akafsrechnungen</li>
    <li>Avoiren a Kreditnoten</li>
    <li>Detail Linn fir Linn mat TVA</li>
</ul>

<p>Wann Äre Fakturatiounssystem <strong>an Är Comptabilitéit integréiert</strong> ass, sinn d'Quelldokumenter systematesch matzeliwweren. Wann en et net ass, kann den AED-Agent gezielt eenzel Quelldokumenter ufroen.</p>

<h2>Technescht Format vum FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Format</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Kodéierung</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">XSD-Schema</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, lescht Aktualiséierung, déi d'AED am Juli 2020 publizéiert huet. Dräi Schemae bestinn niewenteneen: <em>full</em>, <em>reduced version A</em> an <em>reduced version B</em>, no dem Comptabilitéitsregime</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Period</td>
            <td class="border border-gray-300 px-4 py-2">E ganzt Geschäftsjoer, um Kalennerjoer ausgeriicht. Verkierzte Geschäftsjoere ginn refuséiert, an e Fichier däerf nëmmen eng Period ofdecken: eng Kontroll iwwer dräi Joer verlaangt dräi Fichieren</td>
        </tr>
    </tbody>
</table>

<h2>Wéi generéiert een e konforme FAIA-Fichier?</h2>

<h3>Optioun 1: Kompatibel Fakturatiounssoftware</h3>

<p>Dat ass déi einfachst Léisung. Eng Software wéi <strong>faktur.lu</strong> generéiert automatesch e konforme FAIA-Fichier aus Äre Fakturatiounsdaten.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ FAIA-Export mat engem Klick bei faktur.lu</p>
    <p class="text-green-700">Eis Software generéiert e FAIA-Fichier, validéiert no dem offizielle XSD-Schema, prett fir un d'AED ze schécken — egal ob Dir haut dozou verflicht sidd oder d'Seuiler muer iwwerschreit.</p>
</div>

<h3>Optioun 2: Comptabilitéitssoftware</h3>

<p>Professionell Comptabilitéitsprogrammer (Sage, BOB asw.) bidden allgemeng e FAIA-Exportmodul un.</p>

<h3>Optioun 3: Entwécklung op Mooss</h3>

<p>Fir grouss Entreprise mat proprietäre Systemer kann eng spezifesch Entwécklung néideg sinn, fir d'Daten no dem FAIA-Schema z'extrahéieren an ze formatéieren.</p>

<h2>Validatioun vum FAIA-Fichier</h2>

<p>Ier Dir Äre Fichier iwwerdroet, validéiert en:</p>

<ol>
    <li><strong>XSD-Validatioun</strong>: préiwen, ob de Fichier dem offizielle XML-Schema entsprécht</li>
    <li><strong>Kontroll vun de Summen</strong>: sécherstellen, datt d'Zomme kohärent sinn</li>
    <li><strong>Iwwerpréiwung vun de Referenzen</strong>: all Identifianten (Clienten, Konten) mussen do sinn</li>
</ol>

<p>D'AED ass op dësem Punkt kloer: <strong>et gëtt keen Validatiounsinstrument zur Verfügung gestallt</strong>, an „nëmmen d'Schema, dat op der Internetsäit vun der AED publizéiert ass, ka als Kontrollmechanismus déngen". Dir kënnt also all beliebege XML-Validator vun engem Drëtten notzen (zum Beispill de <a href="/lb/validateur-faia">faktur.lu-Validator</a>), fir d'Konformitéit virdrun ze préiwen.</p>

<h2>Fristen, Iwwerdroung a Sanktiounen</h2>

<h3>Frist fir d'Erstellung</h3>

<p>D'AED publizéiert keng fest gesetzlech Frist. Wann e FAIA-Fichier am Kader vun enger Kontroll gefrot gëtt, gëtt d'Frist <strong>vum Kontrolleur vun Fall zu Fall</strong> festgeluecht, no der Komplexitéit vun der Ufro.</p>

<h3>Iwwerdroungsmedium</h3>

<p>D'AED weist sech flexibel: all Standard-elektronesche Support, dee fräi um Marché disponibel ass, gëtt akzeptéiert — USB-Stick, extern Festplack, CD-R oder DVD-R, E-Mail.</p>

<h3>Sanktioune bei Netkonformitéit</h3>

<p>Fir Entreprisen, déi tatsächlech ënner d'Flicht falen, kann d'Verweigerung oder d'Onfäegkeet, d'Daten ze liwweren, Folgendes no sech zéien:</p>

<ul>
    <li><strong>Administrativ Geldstrofen</strong></li>
    <li>Eng <strong>Taxatioun vun Amts wéinst</strong> duerch d'Administratioun</li>
    <li>D'<strong>Refuséiere vun der Comptabilitéit</strong> als Beweis</li>
</ul>

<h2>Bewäert Praxis</h2>

<ol>
    <li><strong>Préift als éischt, ob Dir betraff sidd</strong> — all véier Bedingunge musse erfëllt sinn</li>
    <li><strong>Testt Äre FAIA-Export reegelméisseg</strong>, net eréischt bei enger Kontroll</li>
    <li><strong>Archivéiert</strong> déi generéiert FAIA-Fichieren fir all Geschäftsjoer</li>
    <li><strong>Préift d'Kohärenz</strong> tëscht Äre Rechnungen an Äre comptabelen Écritureën</li>
    <li><strong>Notzt zertifizéiert</strong> oder gestest Software fir de FAIA-Export</li>
</ol>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Offiziell FAIA-Säit</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – XSD-Schemae FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Applikatiounsberäich an Ausschlëss)</a></li>
</ul>

<h2>Conclusioun</h2>

<p>De FAIA ass eng richteg, mä geziilte Flicht: se betrëfft Entreprisen, déi dem normaliséierte Comptesplang an dem normale Regime ënnerleien, iwwer 112 000 € Ëmsaz an ongeféier 500 Joerestransaktiounen eraus. Vill Onofhängeger a kleng Strukture sinn net dozou verflicht.</p>

<p>Wann Dir betraff sidd — oder wann Äre Wuesstem Iech dohinner féiert —, erspuert Iech eng Fakturatiounssoftware, déi de Fichier ka produzéieren, d'Entdeckung vum Problem um Dag vun der Kontroll. faktur.lu integréiert den FAIA-Export nativ, validéiert no dem offizielle Schema vun der AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">Steierkontroll →</a></li><li><a href="/lb/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">Archivéierung vu Rechnungen →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen a Prozedure kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">O FAIA (ficheiro de auditoria informatizado) é um ficheiro que a AED pode exigir durante uma inspeção fiscal. Ao contrário de uma ideia difundida, não diz respeito a todas as empresas luxemburguesas: quatro condições cumulativas determinam quem tem de ser capaz de o produzir.</p>

<h2>O que é o FAIA?</h2>

<p>O <strong>FAIA (Fichier d'Audit Informatisé)</strong>, também chamado <strong>SAF-T Luxemburgo</strong>, é um ficheiro em formato XML normalizado que contém a totalidade dos dados contabilísticos e fiscais de uma empresa para um dado período.</p>

<p>A sua base legal é a <strong>lei de 19 de dezembro de 2008</strong> (Mémorial A-206 de 24 de dezembro de 2008), que alterou o <strong>artigo 70.º, parágrafo 3, da lei do IVA</strong>. Este texto prevê que os livros e documentos existentes sob forma eletrónica devem, a pedido da administração, ser comunicados «numa forma legível e diretamente inteligível» ou segundo quaisquer outras modalidades técnicas que a administração determine. O FAIA é a modalidade escolhida pela AED.</p>

<h2>Quem deve produzir um ficheiro FAIA?</h2>

<p>É o ponto mais frequentemente deturpado. Segundo as <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">FAQ oficiais da AED</a>, a obrigação pressupõe que <strong>quatro condições estejam reunidas ao mesmo tempo</strong>.</p>

<h3>As quatro condições cumulativas</h3>

<ol>
    <li>Estar <strong>sujeito ao plano de contas normalizado (PCN)</strong></li>
    <li><strong>Não beneficiar de um regime simplificado</strong></li>
    <li>Realizar um <strong>volume de negócios anual superior a 112 000 €</strong></li>
    <li>Ultrapassar um volume de cerca de <strong>500 transações contabilísticas</strong> por ano</li>
</ol>

<p>Se faltar apenas uma destas condições, o FAIA não lhe é aplicável. A própria AED o formula sem rodeios nas suas FAQ:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>«Realizo um volume de negócios de 1.000.000 € e tenho apenas 400 transações na minha contabilidade. Sou obrigado a fornecer um ficheiro FAIA? — <strong>Não.</strong> Embora o seu volume de negócios ultrapasse os 112.000 €, o seu volume de transações mantém-se em limites em que um controlo manual é mais racional.»</p>
</blockquote>

<h3>O que é realmente uma «transação»</h3>

<p>Atenção à contagem: uma transação <strong>não é uma fatura</strong>. A AED define-a como uma <strong>cadeia inteira de lançamentos</strong>. Uma compra, por exemplo, decompõe-se em quatro lançamentos ligados — conta de compra, IVA a montante, conta de fornecedor, pagamento — que formam em conjunto <strong>uma única</strong> transação.</p>

<p>Quem conta as suas 600 faturas e conclui que ultrapassa o limiar está, portanto, provavelmente a medir a coisa errada.</p>

<h3>Se não estiver sujeito ao PCN</h3>

<p>Escapa à obrigação FAIA propriamente dita, mesmo com um volume de negócios elevado e mais de 500 transações. Mas o artigo 70.º continua a aplicar-se: a AED pode exigir que exporte os seus dados eletrónicos <strong>num formato delimitado e estruturado</strong>. Estar fora do FAIA não dispensa de ser capaz de extrair a contabilidade corretamente.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Importante</p>
    <p class="text-amber-700">O FAIA nunca se transmite espontaneamente e, em particular, <strong>não com a sua declaração de IVA</strong>. É produzido <strong>unicamente a pedido</strong> de um agente da AED encarregado da inspeção da sua empresa.</p>
</div>

<h2>O que contém o ficheiro FAIA?</h2>

<p>O ficheiro FAIA está estruturado em várias secções:</p>

<h3>1. Informações gerais (Header)</h3>

<ul>
    <li>Identificação da empresa (nome, endereço, número de IVA)</li>
    <li>Período abrangido pelo ficheiro</li>
    <li>Informações sobre o software utilizado</li>
    <li>Data e hora de geração</li>
</ul>

<h3>2. Plano de contas (GeneralLedger)</h3>

<ul>
    <li>Lista de todas as contas contabilísticas utilizadas</li>
    <li>Hierarquia das contas</li>
    <li>Saldos de abertura e de encerramento</li>
</ul>

<h3>3. Clientes e fornecedores (MasterFiles)</h3>

<ul>
    <li>Ficheiro de clientes com dados completos</li>
    <li>Ficheiro de fornecedores</li>
    <li>Números de IVA intracomunitários</li>
</ul>

<h3>4. Lançamentos contabilísticos (GeneralLedgerEntries)</h3>

<ul>
    <li>Todos os lançamentos do período, incluindo os sem ligação direta ao IVA — a exportação deve abranger a totalidade da contabilidade</li>
    <li>Diários contabilísticos</li>
    <li>Documentos de suporte referenciados</li>
</ul>

<h3>5. Faturas (SourceDocuments)</h3>

<ul>
    <li>Faturas de venda emitidas</li>
    <li>Faturas de compra recebidas</li>
    <li>Notas de crédito</li>
    <li>Detalhe linha a linha com IVA</li>
</ul>

<p>Se o seu sistema de faturação estiver <strong>integrado na contabilidade</strong>, os documentos de origem devem ser entregues sistematicamente. Se não estiver, o agente da AED pode pedir documentos de origem específicos.</p>

<h2>Formato técnico do FAIA</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Formato</td>
            <td class="border border-gray-300 px-4 py-2">XML (Extensible Markup Language)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Codificação</td>
            <td class="border border-gray-300 px-4 py-2">UTF-8</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Esquema XSD</td>
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01.xsd, última atualização publicada pela AED em julho de 2020. Coexistem três esquemas: <em>full</em>, <em>reduced version A</em> e <em>reduced version B</em>, consoante o regime contabilístico</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Período</td>
            <td class="border border-gray-300 px-4 py-2">Um exercício inteiro, alinhado pelo ano civil. Os exercícios truncados são recusados e um ficheiro só pode cobrir um período: uma inspeção a três anos exige três ficheiros</td>
        </tr>
    </tbody>
</table>

<h2>Como gerar um ficheiro FAIA conforme?</h2>

<h3>Opção 1: software de faturação compatível</h3>

<p>É a solução mais simples. Um software como o <strong>faktur.lu</strong> gera automaticamente um ficheiro FAIA conforme a partir dos seus dados de faturação.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Exportação FAIA num clique com o faktur.lu</p>
    <p class="text-green-700">O nosso software gera um ficheiro FAIA validado segundo o esquema XSD oficial, pronto a ser transmitido à AED — quer esteja obrigado hoje, quer ultrapasse os limiares amanhã.</p>
</div>

<h3>Opção 2: software de contabilidade</h3>

<p>Os programas de contabilidade profissionais (Sage, BOB, etc.) propõem geralmente um módulo de exportação FAIA.</p>

<h3>Opção 3: desenvolvimento à medida</h3>

<p>Para grandes empresas com sistemas proprietários, pode ser necessário um desenvolvimento específico para extrair e formatar os dados segundo o esquema FAIA.</p>

<h2>Validação do ficheiro FAIA</h2>

<p>Antes de transmitir o ficheiro, valide-o:</p>

<ol>
    <li><strong>Validação XSD</strong>: verificar que o ficheiro respeita o esquema XML oficial</li>
    <li><strong>Controlo dos totais</strong>: assegurar que as somas são coerentes</li>
    <li><strong>Verificação das referências</strong>: todos os identificadores (clientes, contas) devem estar presentes</li>
</ol>

<p>A AED é explícita neste ponto: <strong>não é disponibilizada qualquer ferramenta de validação</strong> e «apenas o esquema publicado no sítio da AED pode servir de mecanismo de controlo». Pode, portanto, utilizar qualquer validador XML de terceiros (por exemplo o <a href="/pt/validateur-faia">validador faktur.lu</a>) para verificar a conformidade antes da transmissão.</p>

<h2>Prazos, transmissão e sanções</h2>

<h3>Prazo de produção</h3>

<p>A AED não publica qualquer prazo legal fixo. Quando um ficheiro FAIA é pedido no âmbito de uma inspeção, o prazo é fixado <strong>caso a caso pelo inspetor</strong>, consoante a complexidade do pedido.</p>

<h3>Suporte de transmissão</h3>

<p>A AED mostra-se flexível: qualquer suporte eletrónico corrente disponível no mercado é aceite — pen USB, disco externo, CD-R ou DVD-R, e-mail.</p>

<h3>Sanções em caso de não conformidade</h3>

<p>Para as empresas efetivamente abrangidas pela obrigação, a recusa ou incapacidade de fornecer os dados pode acarretar:</p>

<ul>
    <li><strong>Coimas administrativas</strong></li>
    <li>Uma <strong>tributação oficiosa</strong> pela administração</li>
    <li>A <strong>rejeição da contabilidade</strong> como prova</li>
</ul>

<h2>Boas práticas</h2>

<ol>
    <li><strong>Verifique primeiro se está abrangido</strong> — as quatro condições devem estar reunidas</li>
    <li><strong>Teste regularmente</strong> a sua exportação FAIA, e não apenas durante uma inspeção</li>
    <li><strong>Arquive</strong> os ficheiros FAIA gerados para cada exercício</li>
    <li><strong>Verifique a coerência</strong> entre as suas faturas e os seus lançamentos contabilísticos</li>
    <li><strong>Utilize software certificado</strong> ou testado para a exportação FAIA</li>
</ol>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED - Página oficial do FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED - Esquemas XSD FAIA 2.01</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED - FAQ FAIA (âmbito de aplicação e exclusões)</a></li>
</ul>

<h2>Conclusão</h2>

<p>O FAIA é uma obrigação real, mas dirigida: visa as empresas sujeitas ao plano de contas normalizado, ao regime normal, acima de 112 000 € de volume de negócios e de cerca de 500 transações anuais. Muitos independentes e pequenas estruturas não estão abrangidos.</p>

<p>Se estiver abrangido — ou se o crescimento o levar até lá —, um software de faturação capaz de produzir o ficheiro poupa-lhe a descoberta do problema no dia da inspeção. O faktur.lu integra nativamente a exportação FAIA, validada segundo o esquema oficial da AED.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">inspeção fiscal →</a></li><li><a href="/pt/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">arquivo de faturas →</a></li></ul></div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares e procedimentos podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>
HTML;
    }
};
