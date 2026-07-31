<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « choisir-logiciel-facturation-luxembourg-comparatif » —
 * vérification factuelle et traductions intégrales.
 *
 * Article de nature différente : il vend notre produit. Les affirmations
 * y ont donc été confrontées au CODE, pas seulement aux textes légaux.
 *
 * 1. CONTRADICTION AVEC NOTRE PROPRE ARTICLE FAIA. Le résumé annonçait
 *    « Le filtre n°1 : l'export FAIA 2.01 [...] un outil qui ne le génère
 *    pas est éliminatoire », et le corps « un logiciel qui ne le génère
 *    pas vous expose à un rejet en contrôle ». Or la FAQ de l'AED, citée
 *    dans notre article FAIA corrigé le 2026-07-30, pose QUATRE conditions
 *    cumulatives (plan comptable normalisé, régime normal, CA supérieur à
 *    112 000 EUR, environ 500 transactions). La majorité des indépendants
 *    qui lisent cette page n'y sont pas soumis. Présenter le FAIA comme
 *    éliminatoire était la même vente par la peur que celle corrigée dans
 *    l'article FAIA — et laissait deux pages du même site se contredire.
 *    Reformulé en critère d'anticipation, avec renvoi aux conditions.
 *
 * 2. STATISTIQUE INVENTÉE : « C'est le critère qui élimine 90 % des
 *    outils ». Aucune source, aucun panel, aucune méthode. Supprimée.
 *
 * 3. CONTRADICTION AVEC NOTRE ARTICLE ARCHIVAGE : « Archivage légal
 *    10 ans en PDF/A (valeur probante) ». Le PDF/A assure la lisibilité
 *    à long terme, il ne confère pas à lui seul la valeur probante — la
 *    loi du 25 juillet 2015 fait reposer cette présomption sur le recours
 *    à un PSDC certifié. Corrigé.
 *
 * 4. FONCTIONNALITÉ ANNONCÉE MAIS NON LIVRÉE : « Peppol » figurait dans
 *    la liste des fonctions de faktur.lu, au même rang que les
 *    fonctionnalités en production. Vérification dans le code :
 *    config/peppol.php a `enabled => env('PEPPOL_ENABLED', false)` et
 *    `provider => env('PEPPOL_PROVIDER', 'simulation')`, et les tickets
 *    FEAT-096 (go-live émission multi-tenant) et FEAT-097 (réception)
 *    sont toujours au backlog. Annoncer Peppol comme disponible était
 *    inexact. Reformulé en travaux en cours, avec le calendrier ViDA réel.
 *
 * 5. CALENDRIER ViDA aligné sur l'article « facturer à l'étranger »
 *    corrigé : les échéances commencent en 2027 et 2028, pas en 2030.
 *
 * CLAIMS PRODUIT VÉRIFIÉS ET CONFIRMÉS EXACTS :
 * - Export FAIA 2.01 : présent.
 * - Exports comptables sage_bob, sage_100, generic, fec : présents
 *   (AccountingExportController, validation du format).
 * - Alerte de seuil de franchise : FranchiseAlertService, seuil 50 000 €.
 * - Portail fiduciaire, cinq langues, offre gratuite, isolation des
 *   données par compte (trait BelongsToUser) : confirmés.
 * - Archivage PDF/A avec empreinte SHA-256 et échéance dix ans : confirmé.
 *
 * PARITÉ : le luxembourgeois faisait 4 358 caractères contre 9 186, avec
 * 5 H2 contre 11. La FAQ (5 questions) manquait dans les quatre
 * traductions — aucune n'avait le moindre H3.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'choisir-logiciel-facturation-luxembourg-comparatif')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure annonçait Peppol
        // comme disponible et présentait le FAIA comme éliminatoire.
    }

    private function fr(): string
    {
        return <<<'HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">En bref</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 critères</strong> pour choisir : conformité LU (mentions art. 63, numérotation, 4 taux de TVA, franchise 50 000 €), cycle complet (devis, avoirs, récurrentes), simplicité, prix tout compris, sécurité (hébergement UE, 2FA), intégration fiduciaire.</li><li>Le <strong>FAIA</strong> n'est pas exigible de tout le monde : quatre conditions cumulatives s'appliquent. C'est néanmoins le critère qui vous protège <strong>quand vous grandirez</strong>.</li><li>Attention aux prix d'appel : comparez ce qui est <strong>réellement inclus</strong>, pas seulement le tarif affiché.</li></ul></div>
<p class="lead">Choisir un logiciel de facturation quand on est indépendant ou PME au Luxembourg, ce n'est pas choisir « un outil de factures » de plus. C'est choisir un outil qui vous met <strong>en conformité avec des règles bien spécifiques au Grand-Duché</strong> (mentions obligatoires, TVA, numérotation, archivage). Voici les 6 critères qui comptent vraiment, et comment les évaluer.</p>

<h2>Pourquoi le Luxembourg change la donne</h2>
<p>Un logiciel de facturation « générique » (français, belge, international) coche souvent les cases de base mais ignore les obligations luxembourgeoises. Or l'Administration de l'enregistrement, des domaines et de la TVA (AED) peut exiger, lors d'un contrôle, des mentions légales exactes, une numérotation irréprochable et — sous certaines conditions — un fichier d'audit à un format précis. Le bon logiciel doit donc être pensé <strong>pour</strong> le Luxembourg, pas seulement « disponible » au Luxembourg.</p>

<h2>Critère 1 : la conformité luxembourgeoise</h2>
<p>C'est le critère central. Vérifiez précisément :</p>
<ul>
    <li><strong>Mentions obligatoires (article 63 LIVA)</strong> : coordonnées, numéro de TVA, numéro séquentiel unique, taux et montants — automatiquement et correctement apposés.</li>
    <li><strong>Numérotation séquentielle continue</strong> : sans trou ni doublon, verrouillée une fois la facture finalisée.</li>
    <li><strong>Les 4 taux de TVA luxembourgeois</strong> : 17 % (normal), 14 % (intermédiaire), 8 % (réduit) et 3 % (super-réduit), appliqués selon le bon scénario.</li>
    <li><strong>Régime de franchise</strong> : gestion du seuil de <strong>50 000 €</strong> (art. 57bis LIVA) avec la mention adéquate et une alerte à l'approche du seuil.</li>
    <li><strong>Scénarios TVA internationaux</strong> : autoliquidation intra-UE, exportation, franchise — avec la bonne mention légale posée automatiquement.</li>
    <li><strong>Archivage 10 ans</strong>, avec un format pensé pour la lisibilité longue durée (PDF/A) et une empreinte d'intégrité.</li>
    <li><strong>Export FAIA 2.01</strong> : voir ci-dessous, le critère est plus nuancé qu'on ne le dit souvent.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Le FAIA ne concerne pas tout le monde</p>
    <p>Beaucoup de comparatifs — y compris, auparavant, cette page — présentent l'export FAIA comme éliminatoire. C'est excessif. Selon la FAQ de l'AED, l'obligation suppose <strong>quatre conditions réunies en même temps</strong> : être soumis au plan comptable normalisé, ne pas bénéficier d'un régime simplifié, dépasser 112 000 € de chiffre d'affaires, et dépasser environ 500 transactions comptables par an. Beaucoup d'indépendants n'y sont pas soumis aujourd'hui.</p>
    <p class="mt-2">Cela ne rend pas le critère inutile : il devient déterminant <strong>le jour où votre activité franchit ces seuils</strong>, et changer d'outil à ce moment-là coûte bien plus cher que de l'avoir prévu. Voir notre <a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">guide complet du FAIA</a>.</p>
</div>

<h2>Critère 2 : les fonctionnalités essentielles</h2>
<p>Au-delà de la facture, votre activité a besoin d'un cycle complet : <strong>devis</strong> (convertibles en facture), <strong>notes de crédit / avoirs</strong>, <strong>factures d'acompte</strong> (avec la bonne exigibilité de TVA), <strong>factures récurrentes</strong>, suivi des <strong>dépenses</strong> et de la <strong>TVA déductible</strong>, gestion multi-devise, et <strong>relances de paiement</strong>. Plus le logiciel couvre le cycle, moins vous jonglez entre outils.</p>

<h2>Critère 3 : la simplicité d'utilisation</h2>
<p>Vous n'êtes pas comptable : le logiciel doit vous faire gagner du temps, pas vous en coûter. Testez le <strong>temps pour émettre votre première facture</strong> conforme, la clarté de l'interface et la disponibilité en français (et idéalement dans la langue de vos clients). Un bon outil s'utilise sans formation.</p>

<h2>Critère 4 : un prix adapté à votre activité</h2>
<p>Méfiez-vous des prix d'appel : regardez ce qui est <strong>réellement inclus</strong>. Un forfait « pas cher » qui facture en supplément les exports comptables ou le nombre de factures peut coûter plus cher qu'un forfait tout compris. Vérifiez la présence d'une offre gratuite pour démarrer, et la transparence des paliers.</p>

<h2>Critère 5 : sécurité et conformité RGPD</h2>
<p>Vos données clients et financières sont sensibles. Exigez un <strong>hébergement européen</strong> (RGPD), le chiffrement, l'authentification à deux facteurs, des <strong>sauvegardes régulières</strong> et une isolation stricte des données entre comptes. Un hébergement hors UE est un signal d'alerte — et, s'agissant de vos archives de factures, il vous impose de déclarer le lieu de stockage à l'administration.</p>

<h2>Critère 6 : l'intégration avec votre fiduciaire</h2>
<p>Le vrai gain de temps (et d'argent) se joue avec votre comptable. Un logiciel qui exporte des données propres et intégrables (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — voire un <strong>portail dédié</strong> où la fiduciaire récupère vos données en lecture seule — lui évite la ressaisie, donc réduit vos honoraires.</p>

<h2>Et la facturation électronique ?</h2>
<p>Deux échéances distinctes, souvent confondues :</p>
<ul>
    <li><strong>B2G (secteur public luxembourgeois)</strong> : la facturation électronique est déjà obligatoire pour facturer l'État et les communes. Si vous travaillez avec le secteur public, c'est un besoin immédiat.</li>
    <li><strong>ViDA (réforme européenne)</strong> : le calendrier est échelonné — extension du guichet OSS en janvier 2027, autoliquidation obligatoire et règles plateformes en juillet 2028, puis facturation électronique et déclaration numérique obligatoires pour les opérations intra-UE B2B au 1<sup>er</sup> juillet 2030. Ce sont <strong>2027 et 2028</strong> qui arrivent en premier, pas 2030.</li>
</ul>
<p>Demandez donc à votre éditeur non pas « faites-vous du Peppol ? » mais « <strong>à quelle échéance, et pour quel périmètre</strong> ? ». Un projet daté vaut mieux qu'une case cochée.</p>

<h2>Grille de comparaison rapide</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Ce qu'il faut exiger</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Conformité LU</td><td class="p-2 border-b">Mentions art. 63, numérotation, 4 taux TVA, franchise 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Cycle complet</td><td class="p-2 border-b">Devis, avoirs, acomptes, récurrentes, dépenses, relances</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Disponible le jour où vous franchissez les seuils</td></tr>
        <tr><td class="p-2 border-b">e-facturation</td><td class="p-2 border-b">B2G aujourd'hui ; feuille de route datée pour ViDA</td></tr>
        <tr><td class="p-2 border-b">Archivage</td><td class="p-2 border-b">PDF/A, 10 ans, empreinte d'intégrité</td></tr>
        <tr><td class="p-2 border-b">Sécurité</td><td class="p-2 border-b">Hébergement UE, 2FA, sauvegardes</td></tr>
        <tr><td class="p-2 border-b">Fiduciaire</td><td class="p-2 border-b">Exports FAIA/Sage/CSV, portail comptable</td></tr>
        <tr><td class="p-2 border-b">Prix</td><td class="p-2 border-b">Tout compris, offre gratuite, transparence</td></tr>
    </tbody>
</table>

<h2>Excel ou logiciel : faut-il vraiment franchir le pas ?</h2>
<p>Excel semble gratuit, mais il ne garantit ni la numérotation continue ni les mentions légales, et il ne produit pas de FAIA le jour où celui-ci devient exigible. Dès que vous émettez plus de quelques factures par mois, un logiciel conforme fait gagner du temps et sécurise. Nous détaillons ce comparatif dans notre article <a href="/fr/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel vs logiciel de facturation</a>.</p>

<h2>Où en est faktur.lu sur ces critères</h2>
<p>faktur.lu a été conçu <strong>spécifiquement pour le Luxembourg</strong>. Ce qui est disponible aujourd'hui :</p>
<ul>
    <li>Mentions de l'article 63 et numérotation séquentielle automatiques, les 4 taux de TVA, scénarios intra-UE et export</li>
    <li>Gestion de la franchise à 50 000 € avec alerte à l'approche du seuil</li>
    <li>Export <strong>FAIA 2.01</strong>, et exports comptables <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Portail fiduciaire</strong> en lecture seule</li>
    <li>Archivage <strong>PDF/A</strong> avec empreinte SHA-256 et échéance à dix ans</li>
    <li>Hébergement européen, authentification à deux facteurs, isolation des données par compte</li>
    <li>Interface en <strong>5 langues</strong> (FR, DE, EN, LB, PT) et <strong>offre gratuite pour démarrer</strong></li>
</ul>
<p>Ce qui est <strong>en cours</strong> : la mise en production de l'émission Peppol pour tous les comptes, puis la réception des factures fournisseurs. Le format est prêt côté technique, le déploiement ne l'est pas encore — nous préférons l'écrire plutôt que de cocher une case.</p>

<h2>FAQ — choisir son logiciel de facturation au Luxembourg</h2>
<h3>Un logiciel de facturation est-il obligatoire au Luxembourg ?</h3>
<p>Non, mais vos factures doivent respecter les obligations légales (mentions, numérotation, TVA, archivage). Un logiciel conforme est le moyen le plus simple de garantir tout cela.</p>
<h3>Le fichier FAIA est-il exigible de tous ?</h3>
<p>Non. La FAQ de l'AED pose quatre conditions cumulatives : plan comptable normalisé, absence de régime simplifié, chiffre d'affaires supérieur à 112 000 €, et environ 500 transactions comptables par an. Si l'une manque, vous n'êtes pas visé — mais l'outil doit pouvoir suivre votre croissance.</p>
<h3>Peut-on facturer avec Excel au Luxembourg ?</h3>
<p>Rien ne l'interdit formellement, mais Excel ne garantit ni la numérotation continue ni les mentions obligatoires. Le risque en contrôle est réel dès que l'activité se développe.</p>
<h3>Combien coûte un logiciel de facturation ?</h3>
<p>De gratuit (offres limitées) à environ 15-30 €/mois pour une solution complète. Le bon réflexe : comparer ce qui est <strong>inclus</strong> plutôt que le seul prix d'appel.</p>
<h3>Quel logiciel choisir si je suis en franchise de TVA ?</h3>
<p>Choisissez un outil qui gère le régime de franchise (seuil 50 000 €), ajoute automatiquement la mention adéquate et vous <strong>alerte à l'approche du seuil</strong>. Vérifiez aussi qu'il gère les <strong>obligations résiduelles</strong> : dès que vous facturez des services à des professionnels de l'UE, une déclaration annuelle simplifiée et des états récapitulatifs restent obligatoires.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Un logiciel pensé pour le Luxembourg, gratuit pour démarrer</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère des factures conformes (mentions, TVA, numérotation) et les exports pour votre fiduciaire — en quelques minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 31 juillet 2026.</em></p>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurz gefasst</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 Kriterien</strong> für die Wahl: luxemburgische Konformität (Pflichtangaben Art. 63, Nummerierung, 4 MwSt.-Sätze, Freigrenze 50 000 €), vollständiger Zyklus (Angebote, Gutschriften, wiederkehrende Rechnungen), Einfachheit, Komplettpreis, Sicherheit (EU-Hosting, 2FA), Anbindung an den Treuhänder.</li><li>Die <strong>FAIA</strong> wird nicht von allen verlangt: vier kumulative Bedingungen gelten. Sie bleibt gleichwohl das Kriterium, das Sie schützt, <strong>wenn Sie wachsen</strong>.</li><li>Vorsicht bei Lockpreisen: Vergleichen Sie, was <strong>tatsächlich enthalten</strong> ist, nicht nur den angezeigten Tarif.</li></ul></div>
<p class="lead">Eine Rechnungssoftware als Selbstständiger oder KMU in Luxemburg zu wählen heißt nicht, ein weiteres „Rechnungstool" zu wählen. Es heißt, ein Werkzeug zu wählen, das Sie <strong>mit den ganz spezifischen Regeln des Großherzogtums konform macht</strong> (Pflichtangaben, MwSt., Nummerierung, Archivierung). Hier sind die 6 Kriterien, die wirklich zählen, und wie Sie sie prüfen.</p>

<h2>Warum Luxemburg den Unterschied macht</h2>
<p>Eine „generische" Rechnungssoftware (französisch, belgisch, international) erfüllt oft die Grundanforderungen, ignoriert aber die luxemburgischen Pflichten. Die Registrierungs-, Domänen- und MwSt.-Verwaltung (AED) kann bei einer Prüfung exakte Rechtsvermerke, eine einwandfreie Nummerierung und — unter bestimmten Bedingungen — eine Audit-Datei in genauem Format verlangen. Die richtige Software muss also <strong>für</strong> Luxemburg gedacht sein, nicht bloß „in" Luxemburg verfügbar.</p>

<h2>Kriterium 1: die luxemburgische Konformität</h2>
<p>Das ist das zentrale Kriterium. Prüfen Sie genau:</p>
<ul>
    <li><strong>Pflichtangaben (Artikel 63 LIVA)</strong>: Kontaktdaten, MwSt.-Nummer, eindeutige fortlaufende Nummer, Sätze und Beträge — automatisch und korrekt angebracht.</li>
    <li><strong>Lückenlose fortlaufende Nummerierung</strong>: ohne Lücke und ohne Dublette, gesperrt sobald die Rechnung finalisiert ist.</li>
    <li><strong>Die 4 luxemburgischen MwSt.-Sätze</strong>: 17 % (normal), 14 % (mittel), 8 % (ermäßigt) und 3 % (stark ermäßigt), je nach Szenario angewandt.</li>
    <li><strong>Freigrenzenregelung</strong>: Handhabung der Schwelle von <strong>50 000 €</strong> (Art. 57bis LIVA) mit dem passenden Vermerk und einer Warnung bei Annäherung.</li>
    <li><strong>Internationale MwSt.-Szenarien</strong>: innergemeinschaftlicher Reverse Charge, Ausfuhr, Freigrenze — mit automatisch gesetztem Rechtsvermerk.</li>
    <li><strong>Zehnjährige Archivierung</strong> in einem auf Langzeitlesbarkeit ausgelegten Format (PDF/A) mit Integritätsnachweis.</li>
    <li><strong>FAIA-2.01-Export</strong>: siehe unten, das Kriterium ist differenzierter, als oft behauptet.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Die FAIA betrifft nicht jeden</p>
    <p>Viele Vergleiche — bisher auch diese Seite — stellen den FAIA-Export als K.-o.-Kriterium dar. Das ist übertrieben. Nach der FAQ der AED setzt die Pflicht <strong>vier gleichzeitig erfüllte Bedingungen</strong> voraus: dem normierten Kontenplan unterliegen, keine vereinfachte Regelung in Anspruch nehmen, über 112 000 € Umsatz erzielen und etwa 500 Buchungstransaktionen jährlich überschreiten. Viele Selbstständige fallen heute nicht darunter.</p>
    <p class="mt-2">Das macht das Kriterium nicht wertlos: Es wird entscheidend <strong>an dem Tag, an dem Ihr Geschäft diese Schwellen überschreitet</strong>, und ein Werkzeugwechsel zu diesem Zeitpunkt kostet weit mehr, als ihn vorausgesehen zu haben. Siehe unseren <a href="/de/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">vollständigen FAIA-Leitfaden</a>.</p>
</div>

<h2>Kriterium 2: die wesentlichen Funktionen</h2>
<p>Über die Rechnung hinaus braucht Ihr Geschäft einen vollständigen Zyklus: <strong>Angebote</strong> (in Rechnungen umwandelbar), <strong>Gutschriften</strong>, <strong>Anzahlungsrechnungen</strong> (mit korrekter MwSt.-Entstehung), <strong>wiederkehrende Rechnungen</strong>, Verfolgung der <strong>Ausgaben</strong> und der <strong>Vorsteuer</strong>, Mehrwährungsfähigkeit und <strong>Zahlungserinnerungen</strong>. Je mehr der Zyklus abgedeckt ist, desto weniger jonglieren Sie zwischen Werkzeugen.</p>

<h2>Kriterium 3: die Einfachheit</h2>
<p>Sie sind kein Buchhalter: Die Software soll Ihnen Zeit sparen, nicht kosten. Testen Sie die <strong>Zeit bis zur ersten konformen Rechnung</strong>, die Klarheit der Oberfläche und die Verfügbarkeit auf Deutsch (und idealerweise in der Sprache Ihrer Kunden). Ein gutes Werkzeug nutzt man ohne Schulung.</p>

<h2>Kriterium 4: ein zu Ihrem Geschäft passender Preis</h2>
<p>Misstrauen Sie Lockpreisen: Schauen Sie, was <strong>tatsächlich enthalten</strong> ist. Ein „günstiger" Tarif, der Buchhaltungsexporte oder die Rechnungsanzahl extra berechnet, kann teurer sein als ein Komplettpaket. Prüfen Sie ein kostenloses Einstiegsangebot und die Transparenz der Stufen.</p>

<h2>Kriterium 5: Sicherheit und DSGVO-Konformität</h2>
<p>Ihre Kunden- und Finanzdaten sind sensibel. Verlangen Sie <strong>europäisches Hosting</strong> (DSGVO), Verschlüsselung, Zwei-Faktor-Authentifizierung, <strong>regelmäßige Sicherungen</strong> und eine strikte Datentrennung zwischen Konten. Hosting außerhalb der EU ist ein Warnsignal — und bei Ihren Rechnungsarchiven verpflichtet es Sie, den Speicherort der Verwaltung zu melden.</p>

<h2>Kriterium 6: die Anbindung an Ihren Treuhänder</h2>
<p>Der eigentliche Zeit- und Geldgewinn entsteht bei Ihrem Buchhalter. Eine Software, die saubere, integrierbare Daten exportiert (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — oder ein <strong>eigenes Portal</strong>, über das der Treuhänder Ihre Daten lesend abruft — erspart ihm die Neuerfassung und senkt damit Ihr Honorar.</p>

<h2>Und die elektronische Rechnungsstellung?</h2>
<p>Zwei getrennte Termine, die oft verwechselt werden:</p>
<ul>
    <li><strong>B2G (luxemburgischer öffentlicher Sektor)</strong>: Die elektronische Rechnungsstellung ist bereits Pflicht, um Staat und Gemeinden zu fakturieren. Wer mit dem öffentlichen Sektor arbeitet, braucht sie sofort.</li>
    <li><strong>ViDA (europäische Reform)</strong>: Der Zeitplan ist gestaffelt — Erweiterung des OSS-Schalters im Januar 2027, verpflichtender Reverse Charge und Plattformregeln im Juli 2028, dann verpflichtende elektronische Rechnungsstellung und digitale Meldung für innergemeinschaftliche B2B-Umsätze zum 1. Juli 2030. Zuerst kommen <strong>2027 und 2028</strong>, nicht 2030.</li>
</ul>
<p>Fragen Sie Ihren Anbieter also nicht „machen Sie Peppol?", sondern „<strong>zu welchem Termin und für welchen Umfang</strong>?". Ein datiertes Vorhaben ist mehr wert als ein Häkchen.</p>

<h2>Schnelle Vergleichstabelle</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Kriterium</th><th class="text-left p-2 bg-slate-100">Was zu verlangen ist</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU-Konformität</td><td class="p-2 border-b">Pflichtangaben Art. 63, Nummerierung, 4 MwSt.-Sätze, Freigrenze 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Vollständiger Zyklus</td><td class="p-2 border-b">Angebote, Gutschriften, Anzahlungen, wiederkehrende Rechnungen, Ausgaben, Mahnungen</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Verfügbar an dem Tag, an dem Sie die Schwellen überschreiten</td></tr>
        <tr><td class="p-2 border-b">E-Rechnung</td><td class="p-2 border-b">B2G heute; datierte Roadmap für ViDA</td></tr>
        <tr><td class="p-2 border-b">Archivierung</td><td class="p-2 border-b">PDF/A, 10 Jahre, Integritätsnachweis</td></tr>
        <tr><td class="p-2 border-b">Sicherheit</td><td class="p-2 border-b">EU-Hosting, 2FA, Sicherungen</td></tr>
        <tr><td class="p-2 border-b">Treuhänder</td><td class="p-2 border-b">Exporte FAIA/Sage/CSV, Buchhalterportal</td></tr>
        <tr><td class="p-2 border-b">Preis</td><td class="p-2 border-b">Alles inklusive, kostenloses Angebot, Transparenz</td></tr>
    </tbody>
</table>

<h2>Excel oder Software: lohnt sich der Schritt?</h2>
<p>Excel wirkt kostenlos, garantiert aber weder die lückenlose Nummerierung noch die Pflichtangaben, und es erzeugt keine FAIA an dem Tag, an dem sie verlangt wird. Sobald Sie mehr als ein paar Rechnungen im Monat stellen, spart eine konforme Software Zeit und schafft Sicherheit. Wir vertiefen den Vergleich in unserem Artikel <a href="/de/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel gegen Rechnungssoftware</a>.</p>

<h2>Wo faktur.lu bei diesen Kriterien steht</h2>
<p>faktur.lu wurde <strong>speziell für Luxemburg</strong> entwickelt. Was heute verfügbar ist:</p>
<ul>
    <li>Vermerke des Artikels 63 und fortlaufende Nummerierung automatisch, die 4 MwSt.-Sätze, innergemeinschaftliche und Ausfuhr-Szenarien</li>
    <li>Handhabung der Freigrenze von 50 000 € mit Warnung bei Annäherung</li>
    <li><strong>FAIA-2.01</strong>-Export sowie Buchhaltungsexporte <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Treuhänderportal</strong> mit Lesezugriff</li>
    <li><strong>PDF/A</strong>-Archivierung mit SHA-256-Fingerabdruck und Zehnjahresfrist</li>
    <li>Europäisches Hosting, Zwei-Faktor-Authentifizierung, Datentrennung je Konto</li>
    <li>Oberfläche in <strong>5 Sprachen</strong> (FR, DE, EN, LB, PT) und <strong>kostenloses Einstiegsangebot</strong></li>
</ul>
<p>Was <strong>in Arbeit</strong> ist: die Produktivsetzung des Peppol-Versands für alle Konten und danach der Empfang von Lieferantenrechnungen. Das Format steht technisch bereit, der Rollout noch nicht — wir schreiben es lieber, als ein Häkchen zu setzen.</p>

<h2>FAQ — Rechnungssoftware in Luxemburg wählen</h2>
<h3>Ist eine Rechnungssoftware in Luxemburg Pflicht?</h3>
<p>Nein, aber Ihre Rechnungen müssen die gesetzlichen Vorgaben erfüllen (Vermerke, Nummerierung, MwSt., Archivierung). Eine konforme Software ist der einfachste Weg, all das sicherzustellen.</p>
<h3>Wird die FAIA-Datei von allen verlangt?</h3>
<p>Nein. Die FAQ der AED nennt vier kumulative Bedingungen: normierter Kontenplan, keine vereinfachte Regelung, Umsatz über 112 000 € und etwa 500 Buchungstransaktionen jährlich. Fehlt eine, sind Sie nicht betroffen — das Werkzeug sollte Ihrem Wachstum aber folgen können.</p>
<h3>Darf man in Luxemburg mit Excel fakturieren?</h3>
<p>Förmlich verboten ist es nicht, doch Excel garantiert weder die lückenlose Nummerierung noch die Pflichtangaben. Das Prüfungsrisiko ist real, sobald das Geschäft wächst.</p>
<h3>Was kostet eine Rechnungssoftware?</h3>
<p>Von kostenlos (eingeschränkte Angebote) bis etwa 15-30 €/Monat für eine vollständige Lösung. Der richtige Reflex: vergleichen, was <strong>enthalten</strong> ist, statt nur den Einstiegspreis.</p>
<h3>Welche Software bei MwSt.-Freigrenze?</h3>
<p>Wählen Sie ein Werkzeug, das die Freigrenzenregelung beherrscht (Schwelle 50 000 €), den passenden Vermerk automatisch setzt und Sie <strong>vor Erreichen der Schwelle warnt</strong>. Prüfen Sie auch die <strong>Restpflichten</strong>: sobald Sie Leistungen an EU-Unternehmer fakturieren, bleiben eine vereinfachte Jahreserklärung und Zusammenfassende Meldungen verpflichtend.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Eine für Luxemburg gedachte Software, kostenlos zum Start</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt konforme Rechnungen (Vermerke, MwSt., Nummerierung) und die Exporte für Ihren Treuhänder — in wenigen Minuten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Kostenlos testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">In brief</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 criteria</strong> to choose by: Luxembourg compliance (art. 63 details, numbering, 4 VAT rates, €50,000 exemption), full cycle (quotes, credit notes, recurring invoices), simplicity, all-in pricing, security (EU hosting, 2FA), accountant integration.</li><li><strong>FAIA</strong> is not required of everyone: four cumulative conditions apply. It remains the criterion that protects you <strong>as you grow</strong>.</li><li>Beware headline pricing: compare what is <strong>actually included</strong>, not just the advertised rate.</li></ul></div>
<p class="lead">Choosing invoicing software as a freelancer or SME in Luxembourg is not about picking one more "invoice tool". It is about picking one that keeps you <strong>compliant with rules specific to the Grand Duchy</strong> (mandatory details, VAT, numbering, archiving). Here are the 6 criteria that genuinely matter, and how to assess them.</p>

<h2>Why Luxembourg changes the picture</h2>
<p>A "generic" invoicing tool (French, Belgian, international) often ticks the basic boxes but ignores Luxembourg obligations. The Registration Duties, Estates and VAT Authority (AED) can require, during an audit, exact legal wording, flawless numbering and — under certain conditions — an audit file in a precise format. The right software must therefore be built <strong>for</strong> Luxembourg, not merely "available" in Luxembourg.</p>

<h2>Criterion 1: Luxembourg compliance</h2>
<p>This is the central criterion. Check precisely:</p>
<ul>
    <li><strong>Mandatory details (article 63 LIVA)</strong>: contact details, VAT number, unique sequential number, rates and amounts — applied automatically and correctly.</li>
    <li><strong>Unbroken sequential numbering</strong>: no gaps, no duplicates, locked once the invoice is finalised.</li>
    <li><strong>The 4 Luxembourg VAT rates</strong>: 17% (standard), 14% (intermediate), 8% (reduced) and 3% (super-reduced), applied to the right scenario.</li>
    <li><strong>Exemption regime</strong>: handling of the <strong>€50,000</strong> threshold (art. 57bis LIVA) with the correct wording and an alert as you approach it.</li>
    <li><strong>International VAT scenarios</strong>: intra-EU reverse charge, export, exemption — with the right legal wording applied automatically.</li>
    <li><strong>10-year archiving</strong>, in a format designed for long-term legibility (PDF/A) with an integrity fingerprint.</li>
    <li><strong>FAIA 2.01 export</strong>: see below — the criterion is more nuanced than usually claimed.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">FAIA does not concern everyone</p>
    <p>Many comparisons — including, until now, this page — present FAIA export as a deal-breaker. That overstates it. According to the AED's FAQ, the obligation requires <strong>four conditions met at once</strong>: being subject to the standard chart of accounts, not benefiting from a simplified regime, exceeding €112,000 in turnover, and exceeding roughly 500 accounting transactions a year. Many freelancers are not covered today.</p>
    <p class="mt-2">That does not make the criterion useless: it becomes decisive <strong>the day your business crosses those thresholds</strong>, and switching tools at that point costs far more than having planned for it. See our <a href="/en/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">complete FAIA guide</a>.</p>
</div>

<h2>Criterion 2: essential features</h2>
<p>Beyond the invoice, your business needs a full cycle: <strong>quotes</strong> (convertible into invoices), <strong>credit notes</strong>, <strong>deposit invoices</strong> (with the right VAT chargeability), <strong>recurring invoices</strong>, tracking of <strong>expenses</strong> and <strong>deductible VAT</strong>, multi-currency handling, and <strong>payment reminders</strong>. The more of the cycle the software covers, the less you juggle between tools.</p>

<h2>Criterion 3: ease of use</h2>
<p>You are not an accountant: the software should save you time, not cost it. Test the <strong>time to issue your first compliant invoice</strong>, the clarity of the interface and availability in English (and ideally in your clients' language). A good tool needs no training.</p>

<h2>Criterion 4: pricing that fits your business</h2>
<p>Beware headline pricing: look at what is <strong>actually included</strong>. A "cheap" plan that charges extra for accounting exports or invoice volume can cost more than an all-in package. Check for a free tier to start with, and transparency across tiers.</p>

<h2>Criterion 5: security and GDPR compliance</h2>
<p>Your client and financial data are sensitive. Insist on <strong>European hosting</strong> (GDPR), encryption, two-factor authentication, <strong>regular backups</strong> and strict data isolation between accounts. Hosting outside the EU is a warning sign — and, for your invoice archives, it obliges you to declare the place of storage to the administration.</p>

<h2>Criterion 6: integration with your accountant</h2>
<p>The real saving in time and money happens with your accountant. Software that exports clean, importable data (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — or offers a <strong>dedicated portal</strong> where the accountant reads your data — spares them re-keying, and lowers your fees.</p>

<h2>What about e-invoicing?</h2>
<p>Two separate deadlines, often conflated:</p>
<ul>
    <li><strong>B2G (Luxembourg public sector)</strong>: e-invoicing is already mandatory to invoice the State and municipalities. If you work with the public sector, this is an immediate need.</li>
    <li><strong>ViDA (European reform)</strong>: the timetable is staggered — OSS extension in January 2027, mandatory reverse charge and platform rules in July 2028, then mandatory e-invoicing and digital reporting for intra-EU B2B on 1 July 2030. <strong>2027 and 2028</strong> come first, not 2030.</li>
</ul>
<p>So ask your vendor not "do you do Peppol?" but "<strong>by when, and for what scope</strong>?". A dated plan beats a ticked box.</p>

<h2>Quick comparison grid</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Criterion</th><th class="text-left p-2 bg-slate-100">What to require</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU compliance</td><td class="p-2 border-b">Art. 63 details, numbering, 4 VAT rates, €50,000 exemption</td></tr>
        <tr><td class="p-2 border-b">Full cycle</td><td class="p-2 border-b">Quotes, credit notes, deposits, recurring, expenses, reminders</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Available the day you cross the thresholds</td></tr>
        <tr><td class="p-2 border-b">E-invoicing</td><td class="p-2 border-b">B2G today; a dated roadmap for ViDA</td></tr>
        <tr><td class="p-2 border-b">Archiving</td><td class="p-2 border-b">PDF/A, 10 years, integrity fingerprint</td></tr>
        <tr><td class="p-2 border-b">Security</td><td class="p-2 border-b">EU hosting, 2FA, backups</td></tr>
        <tr><td class="p-2 border-b">Accountant</td><td class="p-2 border-b">FAIA/Sage/CSV exports, accountant portal</td></tr>
        <tr><td class="p-2 border-b">Price</td><td class="p-2 border-b">All-in, free tier, transparency</td></tr>
    </tbody>
</table>

<h2>Excel or software: is the switch worth it?</h2>
<p>Excel looks free, but it guarantees neither unbroken numbering nor mandatory details, and it will not produce a FAIA file the day one is required. Once you issue more than a handful of invoices a month, compliant software saves time and reduces risk. We go into this in our article <a href="/en/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel vs invoicing software</a>.</p>

<h2>Where faktur.lu stands on these criteria</h2>
<p>faktur.lu was built <strong>specifically for Luxembourg</strong>. What is available today:</p>
<ul>
    <li>Article 63 wording and sequential numbering applied automatically, the 4 VAT rates, intra-EU and export scenarios</li>
    <li>Handling of the €50,000 exemption threshold with an alert as you approach it</li>
    <li><strong>FAIA 2.01</strong> export, plus accounting exports for <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li>Read-only <strong>accountant portal</strong></li>
    <li><strong>PDF/A</strong> archiving with a SHA-256 fingerprint and a ten-year expiry</li>
    <li>European hosting, two-factor authentication, per-account data isolation</li>
    <li>Interface in <strong>5 languages</strong> (FR, DE, EN, LB, PT) and a <strong>free tier to start</strong></li>
</ul>
<p>What is <strong>in progress</strong>: rolling Peppol sending into production for all accounts, then receiving supplier invoices. The format is technically ready, the deployment is not — we would rather say so than tick a box.</p>

<h2>FAQ — choosing invoicing software in Luxembourg</h2>
<h3>Is invoicing software mandatory in Luxembourg?</h3>
<p>No, but your invoices must meet the legal requirements (details, numbering, VAT, archiving). Compliant software is the simplest way to guarantee all of that.</p>
<h3>Is the FAIA file required of everyone?</h3>
<p>No. The AED's FAQ sets four cumulative conditions: standard chart of accounts, no simplified regime, turnover above €112,000, and roughly 500 accounting transactions a year. If one is missing, you are not covered — but the tool should keep up as you grow.</p>
<h3>Can you invoice with Excel in Luxembourg?</h3>
<p>Nothing formally forbids it, but Excel guarantees neither unbroken numbering nor mandatory details. The audit risk is real once activity picks up.</p>
<h3>How much does invoicing software cost?</h3>
<p>From free (limited offers) to around €15-30/month for a full solution. The right instinct: compare what is <strong>included</strong> rather than the headline price alone.</p>
<h3>Which software if I am under the VAT exemption?</h3>
<p>Choose a tool that handles the exemption regime (€50,000 threshold), adds the correct wording automatically and <strong>alerts you as you approach the threshold</strong>. Check too that it accounts for the <strong>residual obligations</strong>: as soon as you invoice services to EU businesses, a simplified annual return and recapitulative statements remain mandatory.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Software built for Luxembourg, free to start</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates compliant invoices (wording, VAT, numbering) and the exports your accountant needs — in minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">A kuerz</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 Critèren</strong> fir ze wielen: Lëtzebuerger Konformitéit (Mentiounen Art. 63, Nummeréierung, 4 TVA-Tariffer, Franchise 50 000 €), kompletten Zyklus (Devisen, Avoiren, récurrent Rechnungen), Einfachheet, Komplettpräis, Sécherheet (EU-Hosting, 2FA), Integratioun mat der Fiduciaire.</li><li>De <strong>FAIA</strong> gëtt net vun alle verlaangt: véier kumulativ Bedingunge gëllen. En bleift awer de Critère, deen Iech schützt, <strong>wann Dir wuesst</strong>.</li><li>Oppassen bei Lockpräisser: vergläicht, wat <strong>tatsächlech abegraff</strong> ass, net nëmmen den ugewisenen Tarif.</li></ul></div>
<p class="lead">Eng Fakturatiounssoftware als Onofhängegen oder KMU zu Lëtzebuerg ze wielen heescht net, nach en „Rechnungstool" ze wielen. Et heescht, en Instrument ze wielen, dat Iech <strong>mat de ganz spezifesche Reegele vum Grand-Duché konform</strong> mécht (obligatoresch Mentiounen, TVA, Nummeréierung, Archivéierung). Hei sinn déi 6 Critèren, déi wierklech zielen, a wéi Dir se evaluéiert.</p>

<h2>Firwat Lëtzebuerg den Ënnerscheed mécht</h2>
<p>Eng „generesch" Fakturatiounssoftware (franséisch, belsch, international) erfëllt dacks d'Grondufuerderungen, ignoréiert awer d'Lëtzebuerger Flichten. D'Administration de l'enregistrement, des domaines et de la TVA (AED) kann bei enger Kontroll genee gesetzlech Mentiounen, eng onbeanstandbar Nummeréierung an — ënner gewësse Bedingungen — en Auditfichier an engem präzise Format verlaangen. Déi richteg Software muss also <strong>fir</strong> Lëtzebuerg geduecht sinn, net nëmmen „zu" Lëtzebuerg disponibel.</p>

<h2>Critère 1: d'Lëtzebuerger Konformitéit</h2>
<p>Dat ass den zentrale Critère. Iwwerpréift genee:</p>
<ul>
    <li><strong>Obligatoresch Mentiounen (Artikel 63 LIVA)</strong>: Kontaktdaten, TVA-Nummer, eenzeg fortlafend Nummer, Tariffer a Betrag — automatesch a korrekt ugebruecht.</li>
    <li><strong>Fortlafend Nummeréierung ouni Ënnerbriechung</strong>: ouni Lack an ouni Duebel, gespaart soubal d'Rechnung finaliséiert ass.</li>
    <li><strong>Déi 4 Lëtzebuerger TVA-Tariffer</strong>: 17 % (normal), 14 % (intermediär), 8 % (reduzéiert) an 3 % (superreduzéiert), no dem richtege Szenario applizéiert.</li>
    <li><strong>Franchiseregime</strong>: Gestioun vum Seuil vun <strong>50 000 €</strong> (Art. 57bis LIVA) mat der passender Mentioun an engem Alert bei Untéierung.</li>
    <li><strong>International TVA-Szenarien</strong>: innergemeinschaftlech Autoliquidatioun, Export, Franchise — mat der automatesch gesatener gesetzlecher Mentioun.</li>
    <li><strong>Archivéierung iwwer 10 Joer</strong>, an engem Format fir d'Laangzäitliesbarkeet (PDF/A) mat engem Integritéitsofdrock.</li>
    <li><strong>FAIA-2.01-Export</strong>: kuckt hei ënnen, de Critère ass méi nuancéiert wéi dacks gesot gëtt.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">De FAIA betrëfft net jiddereen</p>
    <p>Vill Verglach — bis elo och dës Säit — stellen de FAIA-Export als eliminatoresch duer. Dat ass iwwerdriwwen. No der FAQ vun der AED setzt d'Flicht <strong>véier gläichzäiteg erfëllte Bedingungen</strong> viraus: dem normaliséierte Comptesplang ënnerleien, kee vereinfachte Regime a Usproch huelen, iwwer 112 000 € Ëmsaz realiséieren, an ongeféier 500 comptabel Transaktioune pro Joer iwwerschreiden. Vill Onofhängeger falen haut net dorënner.</p>
    <p class="mt-2">Dat mécht de Critère net wäertlos: en gëtt entscheedend <strong>um Dag, wou Är Aktivitéit dës Seuiler iwwerschreit</strong>, an d'Instrument zu deem Moment ze wiesselen kascht vill méi, wéi et virgesinn ze hunn. Kuckt eise <a href="/lb/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">komplette FAIA-Guide</a>.</p>
</div>

<h2>Critère 2: déi wesentlech Funktionalitéiten</h2>
<p>Iwwer d'Rechnung eraus brauch Är Aktivitéit e kompletten Zyklus: <strong>Devisen</strong> (a Rechnungen ëmwandelbar), <strong>Avoiren / Kreditnoten</strong>, <strong>Akontsrechnungen</strong> (mat der richteger TVA-Exigibilitéit), <strong>récurrent Rechnungen</strong>, Verfollegung vun den <strong>Ausgaben</strong> an der <strong>ofzéibarer TVA</strong>, Multi-Devise-Gestioun, a <strong>Bezuelungsrelancen</strong>. Wat d'Software méi vum Zyklus ofdeckt, wat Dir manner tëscht Instrumenter jongléiert.</p>

<h2>Critère 3: d'Einfachheet</h2>
<p>Dir sidd kee Comptabel: d'Software soll Iech Zäit spueren, net kaschten. Test d'<strong>Zäit bis zur éischter konformer Rechnung</strong>, d'Kloerheet vum Interface an d'Disponibilitéit op Lëtzebuergesch (an idealerweis an der Sprooch vun Äre Clienten). E gutt Instrument benotzt ee ouni Formatioun.</p>

<h2>Critère 4: e Präis, deen zu Ärer Aktivitéit passt</h2>
<p>Mësstraut de Lockpräisser: kuckt, wat <strong>tatsächlech abegraff</strong> ass. E „bëllegen" Forfait, deen d'comptabel Exporter oder d'Zuel vu Rechnungen extra berechent, ka méi deier ginn wéi e Komplettpaket. Préift, ob et eng gratis Offer fir den Ufank gëtt, an d'Transparenz vun de Paliere.</p>

<h2>Critère 5: Sécherheet a RGPD-Konformitéit</h2>
<p>Är Clients- a Finanzdaten si sensibel. Verlaangt en <strong>europäescht Hosting</strong> (RGPD), Verschlësselung, Zwee-Faktor-Authentifikatioun, <strong>reegelméisseg Backups</strong> an eng strikt Datentrennung tëscht Konten. En Hosting ausserhalb vun der EU ass en Alarmsignal — a wat Är Rechnungsarchiven ugeet, verflicht et Iech, d'Späicherplaz der Administratioun ze deklaréieren.</p>

<h2>Critère 6: d'Integratioun mat Ärer Fiduciaire</h2>
<p>De richtege Gewënn u Zäit (a Suen) spillt sech bei Ärem Comptabel of. Eng Software, déi propper an integréierbar Daten exportéiert (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — oder souguer e <strong>dediéierte Portal</strong>, wou d'Fiduciaire Är Daten a Lieszougrëff hëlt — erspuert him d'Neierfaassung a reduzéiert domat Är Honoraren.</p>

<h2>A wat ass mat der elektronescher Fakturatioun?</h2>
<p>Zwee getrennten Terminer, déi dacks verwiesselt ginn:</p>
<ul>
    <li><strong>B2G (Lëtzebuerger ëffentleche Secteur)</strong>: d'elektronesch Fakturatioun ass scho Flicht, fir de Staat an d'Gemengen ze fakturéieren. Wien mam ëffentleche Secteur schafft, brauch se direkt.</li>
    <li><strong>ViDA (europäesch Reform)</strong>: de Kalenner ass gestaffelt — Erweiderung vum OSS-Guichet am Januar 2027, obligatoresch Autoliquidatioun a Plattformreegelen am Juli 2028, duerno obligatoresch elektronesch Fakturatioun an digital Deklaratioun fir innergemeinschaftlech B2B-Operatiounen den 1. Juli 2030. Als éischt kommen <strong>2027 an 2028</strong>, net 2030.</li>
</ul>
<p>Frot Äre Editeur also net „maacht Dir Peppol?", mä „<strong>bis wéini, a fir wéi ee Perimeter</strong>?". E datéierte Projet ass méi wäert wéi eng ugekräizte Case.</p>

<h2>Séier Verglachstabell</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Wat ze verlaangen ass</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">LU-Konformitéit</td><td class="p-2 border-b">Mentiounen Art. 63, Nummeréierung, 4 TVA-Tariffer, Franchise 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Kompletten Zyklus</td><td class="p-2 border-b">Devisen, Avoiren, Akonten, récurrent Rechnungen, Ausgaben, Relancen</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Disponibel um Dag, wou Dir d'Seuiler iwwerschreit</td></tr>
        <tr><td class="p-2 border-b">E-Fakturatioun</td><td class="p-2 border-b">B2G haut; datéiert Roadmap fir ViDA</td></tr>
        <tr><td class="p-2 border-b">Archivéierung</td><td class="p-2 border-b">PDF/A, 10 Joer, Integritéitsofdrock</td></tr>
        <tr><td class="p-2 border-b">Sécherheet</td><td class="p-2 border-b">EU-Hosting, 2FA, Backups</td></tr>
        <tr><td class="p-2 border-b">Fiduciaire</td><td class="p-2 border-b">Exporter FAIA/Sage/CSV, Comptabelsportal</td></tr>
        <tr><td class="p-2 border-b">Präis</td><td class="p-2 border-b">Alles abegraff, gratis Offer, Transparenz</td></tr>
    </tbody>
</table>

<h2>Excel oder Software: soll ee wierklech de Schrëtt maachen?</h2>
<p>Excel schéngt gratis, mä et garantéiert weder déi fortlafend Nummeréierung nach déi gesetzlech Mentiounen, an et produzéiert kee FAIA um Dag, wou deen néideg gëtt. Soubal Dir méi wéi e puer Rechnungen am Mount ausstellt, spuert eng konform Software Zäit a schaaft Sécherheet. Mir vertiefen de Verglach an eisem Artikel <a href="/lb/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel géint Fakturatiounssoftware</a>.</p>

<h2>Wou faktur.lu bei dëse Critère steet</h2>
<p>faktur.lu gouf <strong>speziell fir Lëtzebuerg</strong> entwéckelt. Wat haut disponibel ass:</p>
<ul>
    <li>Mentioune vum Artikel 63 a fortlafend Nummeréierung automatesch, déi 4 TVA-Tariffer, innergemeinschaftlech an Export-Szenarien</li>
    <li>Gestioun vun der Franchise vun 50 000 € mat engem Alert bei Untéierung</li>
    <li><strong>FAIA-2.01</strong>-Export, a comptabel Exporter <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Fiduciaire-Portal</strong> a Lieszougrëff</li>
    <li><strong>PDF/A</strong>-Archivéierung mat SHA-256-Ofdrock an Zéngjoresfrist</li>
    <li>Europäescht Hosting, Zwee-Faktor-Authentifikatioun, Datentrennung pro Kont</li>
    <li>Interface a <strong>5 Sproochen</strong> (FR, DE, EN, LB, PT) an eng <strong>gratis Offer fir unzefänken</strong></li>
</ul>
<p>Wat <strong>am Gaang</strong> ass: d'Produktivsetzung vum Peppol-Versand fir all Konten, duerno den Empfang vu Fournisseursrechnungen. D'Format ass technesch prett, den Deploiement nach net — mir schreiwen et léiwer, wéi eng Case unzekräizen.</p>

<h2>FAQ — seng Fakturatiounssoftware zu Lëtzebuerg wielen</h2>
<h3>Ass eng Fakturatiounssoftware zu Lëtzebuerg obligatoresch?</h3>
<p>Neen, mä Är Rechnunge mussen déi gesetzlech Flichten erfëllen (Mentiounen, Nummeréierung, TVA, Archivéierung). Eng konform Software ass dee einfachste Wee, dat alles ze garantéieren.</p>
<h3>Gëtt de FAIA-Fichier vun alle verlaangt?</h3>
<p>Neen. D'FAQ vun der AED setzt véier kumulativ Bedingungen: normaliséierte Comptesplang, kee vereinfachte Regime, Ëmsaz iwwer 112 000 €, an ongeféier 500 comptabel Transaktioune pro Joer. Feelt eng, sidd Dir net betraff — mä d'Instrument soll Ärem Wuesstem kënne follegen.</p>
<h3>Kann een zu Lëtzebuerg mat Excel fakturéieren?</h3>
<p>Näischt verbitt et formell, mä Excel garantéiert weder déi fortlafend Nummeréierung nach déi obligatoresch Mentiounen. De Risiko bei enger Kontroll ass reell, soubal d'Aktivitéit wuesst.</p>
<h3>Wat kascht eng Fakturatiounssoftware?</h3>
<p>Vu gratis (limitéiert Offeren) bis ongeféier 15-30 €/Mount fir eng komplett Léisung. De richtege Reflex: vergläichen, wat <strong>abegraff</strong> ass, an net nëmmen den Ufankspräis.</p>
<h3>Wéi eng Software, wann ech an der TVA-Franchise sinn?</h3>
<p>Wielt en Instrument, dat de Franchiseregime beherrscht (Seuil 50 000 €), déi passend Mentioun automatesch setzt an Iech <strong>bei Untéierung vum Seuil alertéiert</strong>. Préift och, ob et déi <strong>Restflichten</strong> berücksichtegt: soubal Dir Leeschtungen un EU-Professioneller fakturéiert, bleiwen eng vereinfacht Jooresdeklaratioun a Récapitulatiffen obligatoresch.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Eng Software fir Lëtzebuerg geduecht, gratis fir unzefänken</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert konform Rechnungen (Mentiounen, TVA, Nummeréierung) an d'Exporter fir Är Fiduciaire — a wéinege Minutten.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Gratis testen</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4"><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Em resumo</p><ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm"><li><strong>6 critérios</strong> para escolher: conformidade LU (menções art. 63, numeração, 4 taxas de IVA, isenção 50 000 €), ciclo completo (orçamentos, notas de crédito, faturas recorrentes), simplicidade, preço tudo incluído, segurança (alojamento na UE, 2FA), integração com o contabilista.</li><li>O <strong>FAIA</strong> não é exigível a todos: aplicam-se quatro condições cumulativas. Continua, ainda assim, a ser o critério que o protege <strong>quando crescer</strong>.</li><li>Cuidado com os preços de chamariz: compare o que está <strong>realmente incluído</strong>, não apenas o valor anunciado.</li></ul></div>
<p class="lead">Escolher um software de faturação sendo independente ou PME no Luxemburgo não é escolher mais «uma ferramenta de faturas». É escolher uma ferramenta que o coloca <strong>em conformidade com regras muito específicas do Grão-Ducado</strong> (menções obrigatórias, IVA, numeração, arquivo). Eis os 6 critérios que realmente contam, e como avaliá-los.</p>

<h2>Porque é que o Luxemburgo muda tudo</h2>
<p>Um software de faturação «genérico» (francês, belga, internacional) cumpre muitas vezes o básico mas ignora as obrigações luxemburguesas. Ora a Administração do registo, dos domínios e do IVA (AED) pode exigir, numa inspeção, menções legais exatas, uma numeração irrepreensível e — sob certas condições — um ficheiro de auditoria num formato preciso. O bom software deve, portanto, ser pensado <strong>para</strong> o Luxemburgo, e não apenas estar «disponível» no Luxemburgo.</p>

<h2>Critério 1: a conformidade luxemburguesa</h2>
<p>É o critério central. Verifique com precisão:</p>
<ul>
    <li><strong>Menções obrigatórias (artigo 63 LIVA)</strong>: dados, número de IVA, número sequencial único, taxas e montantes — aplicados automática e corretamente.</li>
    <li><strong>Numeração sequencial contínua</strong>: sem falhas nem duplicados, bloqueada assim que a fatura é finalizada.</li>
    <li><strong>As 4 taxas de IVA luxemburguesas</strong>: 17 % (normal), 14 % (intermédia), 8 % (reduzida) e 3 % (super-reduzida), aplicadas ao cenário correto.</li>
    <li><strong>Regime de isenção</strong>: gestão do limiar de <strong>50 000 €</strong> (art. 57bis LIVA) com a menção adequada e um alerta ao aproximar-se do limiar.</li>
    <li><strong>Cenários de IVA internacionais</strong>: autoliquidação intra-UE, exportação, isenção — com a menção legal correta aplicada automaticamente.</li>
    <li><strong>Arquivo de 10 anos</strong>, num formato pensado para a legibilidade a longo prazo (PDF/A) e com uma impressão de integridade.</li>
    <li><strong>Exportação FAIA 2.01</strong>: ver abaixo, o critério é mais matizado do que se costuma dizer.</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O FAIA não diz respeito a toda a gente</p>
    <p>Muitos comparativos — incluindo, até agora, esta página — apresentam a exportação FAIA como eliminatória. É excessivo. Segundo as FAQ da AED, a obrigação pressupõe <strong>quatro condições reunidas ao mesmo tempo</strong>: estar sujeito ao plano de contas normalizado, não beneficiar de um regime simplificado, ultrapassar 112 000 € de volume de negócios e ultrapassar cerca de 500 transações contabilísticas por ano. Muitos independentes não estão hoje abrangidos.</p>
    <p class="mt-2">Isso não torna o critério inútil: torna-se determinante <strong>no dia em que a sua atividade ultrapassa esses limiares</strong>, e mudar de ferramenta nesse momento custa bem mais do que tê-lo previsto. Ver o nosso <a href="/pt/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600">guia completo do FAIA</a>.</p>
</div>

<h2>Critério 2: as funcionalidades essenciais</h2>
<p>Para além da fatura, a sua atividade precisa de um ciclo completo: <strong>orçamentos</strong> (convertíveis em fatura), <strong>notas de crédito</strong>, <strong>faturas de adiantamento</strong> (com a exigibilidade de IVA correta), <strong>faturas recorrentes</strong>, acompanhamento das <strong>despesas</strong> e do <strong>IVA dedutível</strong>, gestão multimoeda e <strong>lembretes de pagamento</strong>. Quanto mais o software cobrir o ciclo, menos anda a saltar entre ferramentas.</p>

<h2>Critério 3: a simplicidade de utilização</h2>
<p>Não é contabilista: o software deve poupar-lhe tempo, não custá-lo. Teste o <strong>tempo até emitir a primeira fatura</strong> conforme, a clareza da interface e a disponibilidade em português (e idealmente na língua dos seus clientes). Uma boa ferramenta usa-se sem formação.</p>

<h2>Critério 4: um preço adequado à sua atividade</h2>
<p>Desconfie dos preços de chamariz: olhe para o que está <strong>realmente incluído</strong>. Um plano «barato» que cobra à parte as exportações contabilísticas ou o número de faturas pode sair mais caro do que um pacote tudo incluído. Verifique a existência de uma oferta gratuita para começar e a transparência dos escalões.</p>

<h2>Critério 5: segurança e conformidade RGPD</h2>
<p>Os seus dados de clientes e financeiros são sensíveis. Exija <strong>alojamento europeu</strong> (RGPD), cifragem, autenticação de dois fatores, <strong>cópias de segurança regulares</strong> e um isolamento estrito dos dados entre contas. Um alojamento fora da UE é um sinal de alerta — e, quanto aos seus arquivos de faturas, obriga-o a declarar o local de armazenamento à administração.</p>

<h2>Critério 6: a integração com o seu contabilista</h2>
<p>O verdadeiro ganho de tempo (e de dinheiro) joga-se com o seu contabilista. Um software que exporta dados limpos e integráveis (<strong>FAIA 2.01, Sage BOB 50, Sage 100, CSV</strong>) — ou até um <strong>portal dedicado</strong> onde o contabilista consulta os seus dados em leitura — evita-lhe a reintrodução manual e reduz os seus honorários.</p>

<h2>E a faturação eletrónica?</h2>
<p>Dois prazos distintos, muitas vezes confundidos:</p>
<ul>
    <li><strong>B2G (setor público luxemburguês)</strong>: a faturação eletrónica já é obrigatória para faturar ao Estado e aos municípios. Se trabalha com o setor público, é uma necessidade imediata.</li>
    <li><strong>ViDA (reforma europeia)</strong>: o calendário é faseado — extensão do balcão OSS em janeiro de 2027, autoliquidação obrigatória e regras das plataformas em julho de 2028, e depois faturação eletrónica e declaração digital obrigatórias para as operações intra-UE B2B a 1 de julho de 2030. São <strong>2027 e 2028</strong> que chegam primeiro, não 2030.</li>
</ul>
<p>Pergunte então ao seu fornecedor não «faz Peppol?» mas «<strong>para quando, e com que âmbito</strong>?». Um projeto datado vale mais do que uma caixa assinalada.</p>

<h2>Grelha de comparação rápida</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critério</th><th class="text-left p-2 bg-slate-100">O que exigir</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Conformidade LU</td><td class="p-2 border-b">Menções art. 63, numeração, 4 taxas de IVA, isenção 50 000 €</td></tr>
        <tr><td class="p-2 border-b">Ciclo completo</td><td class="p-2 border-b">Orçamentos, notas de crédito, adiantamentos, recorrentes, despesas, lembretes</td></tr>
        <tr><td class="p-2 border-b">FAIA 2.01</td><td class="p-2 border-b">Disponível no dia em que ultrapassar os limiares</td></tr>
        <tr><td class="p-2 border-b">Faturação eletrónica</td><td class="p-2 border-b">B2G hoje; roteiro datado para o ViDA</td></tr>
        <tr><td class="p-2 border-b">Arquivo</td><td class="p-2 border-b">PDF/A, 10 anos, impressão de integridade</td></tr>
        <tr><td class="p-2 border-b">Segurança</td><td class="p-2 border-b">Alojamento na UE, 2FA, cópias de segurança</td></tr>
        <tr><td class="p-2 border-b">Contabilista</td><td class="p-2 border-b">Exportações FAIA/Sage/CSV, portal do contabilista</td></tr>
        <tr><td class="p-2 border-b">Preço</td><td class="p-2 border-b">Tudo incluído, oferta gratuita, transparência</td></tr>
    </tbody>
</table>

<h2>Excel ou software: vale mesmo a pena dar o passo?</h2>
<p>O Excel parece gratuito, mas não garante nem a numeração contínua nem as menções legais, e não produz um FAIA no dia em que este passar a ser exigível. A partir do momento em que emite mais do que algumas faturas por mês, um software conforme poupa tempo e reduz o risco. Detalhamos esta comparação no nosso artigo <a href="/pt/blog/excel-vs-logiciel-facturation-pourquoi-switch">Excel vs software de faturação</a>.</p>

<h2>Onde está o faktur.lu nestes critérios</h2>
<p>O faktur.lu foi concebido <strong>especificamente para o Luxemburgo</strong>. O que está disponível hoje:</p>
<ul>
    <li>Menções do artigo 63 e numeração sequencial automáticas, as 4 taxas de IVA, cenários intra-UE e de exportação</li>
    <li>Gestão da isenção a 50 000 € com alerta ao aproximar-se do limiar</li>
    <li>Exportação <strong>FAIA 2.01</strong> e exportações contabilísticas <strong>Sage BOB 50, Sage 100, CSV</strong></li>
    <li><strong>Portal do contabilista</strong> em modo de leitura</li>
    <li>Arquivo <strong>PDF/A</strong> com impressão SHA-256 e prazo de dez anos</li>
    <li>Alojamento europeu, autenticação de dois fatores, isolamento de dados por conta</li>
    <li>Interface em <strong>5 línguas</strong> (FR, DE, EN, LB, PT) e <strong>oferta gratuita para começar</strong></li>
</ul>
<p>O que está <strong>em curso</strong>: a entrada em produção do envio Peppol para todas as contas e, depois, a receção de faturas de fornecedores. O formato está tecnicamente pronto, a implementação ainda não — preferimos escrevê-lo a assinalar uma caixa.</p>

<h2>FAQ — escolher o software de faturação no Luxemburgo</h2>
<h3>Um software de faturação é obrigatório no Luxemburgo?</h3>
<p>Não, mas as suas faturas devem respeitar as obrigações legais (menções, numeração, IVA, arquivo). Um software conforme é a forma mais simples de garantir tudo isso.</p>
<h3>O ficheiro FAIA é exigível a todos?</h3>
<p>Não. As FAQ da AED estabelecem quatro condições cumulativas: plano de contas normalizado, ausência de regime simplificado, volume de negócios superior a 112 000 € e cerca de 500 transações contabilísticas por ano. Se faltar uma, não está abrangido — mas a ferramenta deve acompanhar o seu crescimento.</p>
<h3>É possível faturar com Excel no Luxemburgo?</h3>
<p>Nada o proíbe formalmente, mas o Excel não garante nem a numeração contínua nem as menções obrigatórias. O risco numa inspeção é real assim que a atividade cresce.</p>
<h3>Quanto custa um software de faturação?</h3>
<p>De gratuito (ofertas limitadas) a cerca de 15-30 €/mês para uma solução completa. O bom reflexo: comparar o que está <strong>incluído</strong> e não apenas o preço de entrada.</p>
<h3>Que software escolher se estiver em isenção de IVA?</h3>
<p>Escolha uma ferramenta que gere o regime de isenção (limiar 50 000 €), acrescente automaticamente a menção adequada e o <strong>alerte ao aproximar-se do limiar</strong>. Verifique também se contempla as <strong>obrigações residuais</strong>: assim que fatura serviços a profissionais da UE, uma declaração anual simplificada e mapas recapitulativos continuam obrigatórios.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Um software pensado para o Luxemburgo, gratuito para começar</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera faturas conformes (menções, IVA, numeração) e as exportações para o seu contabilista — em poucos minutos.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar gratuitamente</a>
</div>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>
HTML;
    }
};
