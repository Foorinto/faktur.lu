<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « faia-luxembourg-guide-fichier-audit-informatise-2026 » —
 * vérification factuelle et traductions intégrales. 11e article traité.
 *
 * CONTEXTE : c'est le SECOND article FAIA du blog, doublon de
 * « faia-luxembourg-fichier-audit-informatise-guide » corrigé le
 * 2026-07-30. Bonne surprise : celui-ci était DÉJÀ correct sur le
 * périmètre — il énonçait clairement que tous les assujettis ne sont pas
 * concernés et listait trois des exclusions. C'est l'autre qui affirmait
 * l'inverse. Les deux sont désormais alignés.
 *
 * 1. QUATRIÈME CONDITION MANQUANTE. L'article listait trois exclusions
 *    (non soumis au PCN, régime simplifié, CA ≤ 112 000 EUR) mais omettait
 *    la quatrième : le volume de transactions comptables, que la FAQ AED
 *    situe autour de 500 par an. Elle change le résultat dans un cas
 *    concret que l'AED donne elle-même : 1 000 000 EUR de chiffre
 *    d'affaires mais 400 transactions → pas d'obligation FAIA. Sans cette
 *    condition, un lecteur au-dessus de 112 000 EUR se croit concerné à
 *    tort. Les quatre sont maintenant présentées comme cumulatives.
 *
 * 2. DÉFINITION DE « TRANSACTION » AJOUTÉE. La FAQ AED définit une
 *    transaction comme une chaîne entière de comptabilisation : un achat
 *    en compte quatre écritures liées et vaut UNE transaction. Sans cette
 *    précision, un lecteur compte ses factures et se croit très au-dessus
 *    du seuil. C'est le détail le plus utile de tout le sujet.
 *
 * 3. VERSION DU SCHÉMA. « Version 2.01, publiée par l'AED en 2020 » :
 *    juillet 2020 est la date de dernière mise à jour de la page de
 *    l'AED, pas celle du schéma, qui lui est antérieur — la FAQ de mars
 *    2013 discute déjà du passage 1.0 vers 2.0. Reformulé, comme dans
 *    l'article FAIA jumeau.
 *
 * 4. BASE LÉGALE COMPLÉTÉE. L'article citait correctement l'article 70
 *    LIVA. Ajout de la loi du 19 décembre 2008 (Mémorial A-206) qui a
 *    introduit le paragraphe 3 de cet article, pour cohérence avec
 *    l'article jumeau.
 *
 * 5. SANCTIONS COMPLÉTÉES. Ne figurait que l'amende de 250 à 10 000 EUR
 *    (art. 77). Ajout de l'amende pouvant atteindre 25 000 EUR PAR JOUR
 *    de retard après avertissement en cas de non-communication des pièces
 *    lors d'un contrôle — c'est précisément la situation d'un FAIA
 *    réclamé et non fourni, donc la sanction la plus pertinente ici.
 *
 * PRÉCISIONS AJOUTÉES, issues de la FAQ AED : les non-soumis au PCN
 * échappent au FAIA mais restent exposés à une demande d'export structuré
 * au titre de l'article 70 ; le fichier porte sur un exercice entier
 * aligné sur l'année civile, sans exercice tronqué ni fichier
 * multi-années.
 *
 * CLAIMS PRODUIT VÉRIFIÉS DANS LE CODE et confirmés exacts :
 * - « Inclus dès le plan Gratuit » : `faia_export` figure bien dans les
 *   features du plan de sort_order 0 (PlansSeeder, PlanService::free).
 * - Validateur FAIA public et gratuit : FaiaValidatorController, routes
 *   publiques hors authentification, throttlées.
 *
 * FAITS VÉRIFIÉS et confirmés exacts : trois schémas full / reduced A /
 * reduced B ; absence d'outil de validation AED ; production sur demande
 * uniquement ; aucun délai légal fixe ; numérotation art. 63 LIVA
 * point 3° ; franchise généralement hors périmètre.
 *
 * PARITÉ : les quatre traductions faisaient 7 288 à 7 615 caractères
 * contre 9 800, avec 8 liens contre 11. Structure H3 déjà saine (13
 * partout), ce qui est rare dans ce blog.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'faia-luxembourg-guide-fichier-audit-informatise-2026')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure omettait la
        // quatrième condition cumulative du périmètre FAIA.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Le <strong>FAIA</strong> (Fichier d'Audit Informatisé de l'AED) est le standard exigé par l'administration fiscale luxembourgeoise lors d'un contrôle de TVA — pour les entreprises qui doivent le produire. Or elles sont bien moins nombreuses qu'on ne le croit : <strong>quatre conditions doivent être réunies en même temps</strong>. Ce guide vous explique tout en 2026.</p>

<h2>Qu'est-ce que le FAIA ?</h2>

<p>Le <strong>FAIA</strong> est un fichier XML structuré, adapté par l'AED à partir du standard <strong>SAF-T</strong> (Standard Audit File for Tax) recommandé par l'OCDE. Il regroupe l'ensemble des données comptables d'une entreprise sur une période donnée, dans un format lisible automatiquement par les outils de l'AED.</p>

<p>Sa base légale est l'<strong>article 70, paragraphe 3, de la loi TVA</strong>, dans sa rédaction issue de la <strong>loi du 19 décembre 2008</strong> (Mémorial A-206) : les livres et documents existant sous forme électronique doivent, sur demande de l'administration, être communiqués sous une forme lisible et directement intelligible, ou selon toutes autres modalités techniques qu'elle détermine. Le FAIA est la modalité retenue.</p>

<p>Le format en vigueur est la <strong>version 2.01</strong>, dont la dernière mise à jour publiée par l'AED date de juillet 2020 — le schéma lui-même est plus ancien. Il en existe <strong>trois</strong> : un schéma complet (<em>full</em>) et deux schémas réduits (<em>reduced A</em> et <em>reduced B</em>), selon le type de comptabilité tenue.</p>

<h2>Qui doit fournir un FAIA ?</h2>

<p>Contrairement à une idée répandue, <strong>tous les assujettis TVA ne sont pas tenus de produire un FAIA</strong>. La <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">FAQ officielle de l'AED</a> pose quatre conditions, et il faut les réunir <strong>toutes les quatre</strong>.</p>

<h3>Les quatre conditions cumulatives</h3>

<ol>
    <li>Être <strong>soumis au Plan Comptable Normalisé (PCN)</strong></li>
    <li><strong>Ne pas bénéficier d'un régime simplifié</strong></li>
    <li>Réaliser un <strong>chiffre d'affaires annuel supérieur à 112 000 €</strong></li>
    <li>Dépasser un <strong>volume d'environ 500 transactions comptables</strong> par an</li>
</ol>

<p>Si une seule manque, vous n'êtes pas visé. L'AED le formule elle-même sans détour :</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>« Je réalise un chiffre d'affaires de 1.000.000 € et je n'ai que 400 transactions dans ma comptabilité. Est-ce que je suis obligé à fournir un fichier FAIA ? — <strong>Non.</strong> Bien que votre chiffre d'affaires dépasse les 112.000 €, votre volume de transaction reste dans les limites d'où un contrôle manuel est plus rationnel. »</p>
</blockquote>

<h3>Ce qu'est réellement une « transaction »</h3>

<p>C'est le point qui fait basculer le calcul, et presque personne ne le sait : une transaction <strong>n'est pas une facture</strong>. L'AED la définit comme une <strong>chaîne entière de comptabilisation</strong>. Un achat, par exemple, se décompose en quatre écritures liées — compte d'achat, TVA en amont, compte fournisseur, paiement — qui forment ensemble <strong>une seule</strong> transaction.</p>

<p>Un indépendant qui compte ses 600 factures et en conclut qu'il dépasse largement le seuil se trompe donc probablement d'unité de mesure.</p>

<h3>Si vous n'êtes pas soumis au PCN</h3>

<p>Vous échappez à l'obligation FAIA proprement dite, même avec un chiffre d'affaires élevé et plus de 500 transactions. Mais l'article 70 continue de s'appliquer : l'AED peut vous demander d'exporter vos données électroniques <strong>dans un format délimité et structuré</strong>. Être hors FAIA ne dispense pas de savoir sortir sa comptabilité proprement.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">À vérifier auprès de votre fiduciaire</p>
    <p>Le périmètre exact dépend de votre régime comptable et de votre activité. En cas de doute, demandez à votre comptable ou contactez directement l'AED — l'erreur classique est de croire que tout le monde est concerné (faux) ou que personne ne l'est (faux aussi).</p>
</div>

<p>Pour ceux qui sont concernés, l'obligation n'est pas de générer le FAIA chaque mois : il suffit de pouvoir le produire <strong>à la demande de l'AED</strong>, dans un délai fixé au cas par cas par le contrôleur — aucun délai légal fixe n'est publié.</p>

<p>Enfin, le fichier porte sur un <strong>exercice entier aligné sur l'année civile</strong> : les exercices tronqués sont refusés, et un fichier ne couvre qu'une seule période. Un contrôle portant sur trois ans exige donc trois fichiers.</p>

<h2>Que contient un FAIA ?</h2>

<p>Un FAIA 2.01 est structuré en plusieurs sections obligatoires :</p>

<h3>1. En-tête (Header)</h3>

<ul>
    <li>Raison sociale, numéro TVA, RCS, matricule</li>
    <li>Adresse de l'entreprise</li>
    <li>Période couverte (date de début et date de fin)</li>
    <li>Date de création du fichier</li>
    <li>Devise (EUR)</li>
</ul>

<h3>2. Plan comptable (MasterFiles)</h3>

<p>La liste des comptes utilisés (clients, fournisseurs, comptes généraux). Pour une petite structure, cette section peut être minimale.</p>

<h3>3. Factures de vente (SalesInvoices)</h3>

<ul>
    <li>Numéro de facture (séquentiel, conforme <strong>article 63 LIVA</strong> point 3°)</li>
    <li>Date de finalisation</li>
    <li>Client : nom, n° TVA, adresse</li>
    <li>Lignes de facture : description, quantité, prix unitaire, TVA</li>
    <li>Totaux HT, TVA, TTC</li>
</ul>

<h3>4. Mouvements (GeneralLedgerEntries)</h3>

<p>Les écritures comptables correspondantes (débit/crédit par compte), <strong>y compris celles sans lien direct avec la TVA</strong> : l'export doit porter sur l'entièreté de la comptabilité.</p>

<h3>5. Pied de fichier (Footer)</h3>

<p>Totaux de contrôle : montant total débité, montant total crédité, nombre de transactions.</p>

<h2>Les 4 erreurs FAIA les plus fréquentes</h2>

<ol>
    <li><strong>Numérotation des factures non conforme</strong> à l'article 63 LIVA point 3° (trous dans la séquence ou doublons). Le fichier peut être rejeté lors de la validation.</li>
    <li><strong>Champs obligatoires manquants</strong> : n° TVA client, adresse complète, mention d'autoliquidation pour le B2B intra-UE.</li>
    <li><strong>Totaux incohérents</strong> entre les sections (par exemple, somme des factures ≠ Total Sales Invoices déclaré).</li>
    <li><strong>Format de date incorrect</strong> : le FAIA exige le format ISO 8601 (YYYY-MM-DD), pas DD/MM/YYYY.</li>
</ol>

<h2>Comment générer un FAIA conforme</h2>

<p>Trois options selon votre situation :</p>

<h3>Option 1 - Logiciel de facturation natif FAIA</h3>

<p>La méthode la plus simple et la plus sûre. Un logiciel comme <a href="/fr" class="text-primary-500 hover:underline font-medium">faktur.lu</a> génère le FAIA 2.01 à la demande, sur n'importe quelle période, avec un seul clic. Le fichier est automatiquement conforme au schéma XSD officiel.</p>

<h3>Option 2 - Export depuis un logiciel comptable</h3>

<p>Plusieurs logiciels comptables professionnels (Sage BOB 50, Sage 100, etc.) permettent l'export FAIA. Vérifiez auprès de votre éditeur que la version supportée est bien la 2.01 et que le schéma utilisé (full / reduced) correspond à votre situation.</p>

<h3>Option 3 - Construction manuelle (déconseillée)</h3>

<p>Théoriquement possible avec un développeur XML, mais source d'erreurs fréquentes. À réserver aux cas extrêmes (legacy data, migration).</p>

<h2>Valider votre FAIA gratuitement</h2>

<p>Avant d'envoyer votre FAIA à l'AED, validez-le contre le schéma officiel pour détecter les erreurs avant le contrôleur. <strong>L'AED ne met à disposition aucun outil de validation</strong> — sa FAQ est explicite : « seul le schéma publié au site Internet de l'AED peut servir de mécanisme de contrôle ».</p>

<p>faktur.lu propose un <a href="/fr/validateur-faia" class="text-primary-500 hover:underline font-medium">validateur FAIA gratuit</a> qui :</p>

<ul>
    <li>Vérifie la conformité XML au schéma XSD AED 2.01</li>
    <li>Détecte les champs obligatoires manquants</li>
    <li>Contrôle la cohérence des totaux</li>
    <li>Vérifie la séquentialité des numéros de facture</li>
    <li>Ne stocke aucune donnée (le fichier reste sur votre machine)</li>
</ul>

<h2>FAQ - FAIA</h2>

<h3>Faut-il envoyer un FAIA chaque année ?</h3>
<p>Non. Le FAIA est produit uniquement <strong>sur demande de l'AED</strong> en cas de contrôle, pour les entreprises qui y sont soumises. Il ne se dépose jamais avec la déclaration de TVA.</p>

<h3>Que se passe-t-il si je ne peux pas fournir de FAIA alors que j'y suis tenu ?</h3>
<p>Le contrôleur considère que votre comptabilité n'est pas tenue selon les standards. Vous vous exposez à une <strong>amende administrative de 250 € à 10 000 € par infraction</strong> (art. 77 LIVA), à une <strong>taxation d'office</strong>, et — en cas de refus de communiquer les pièces lors du contrôle — à une amende pouvant atteindre <strong>25 000 € par jour de retard</strong> après avertissement.</p>

<h3>Le FAIA est-il obligatoire pour la franchise TVA ?</h3>
<p><strong>Non, en général.</strong> Le régime de franchise (article 57bis LIVA, CA ≤ 50 000 €) place l'assujetti en dessous du seuil de 112 000 €, et le régime simplifié est de toute façon une cause d'exclusion à lui seul. Les franchisés ne sont donc <strong>habituellement pas concernés</strong>. À vérifier si votre régime comptable est particulier.</p>

<h3>Comment savoir si mon FAIA est conforme avant un contrôle ?</h3>
<p>Utilisez un <a href="/fr/validateur-faia" class="text-primary-500 hover:underline font-medium">validateur FAIA</a> qui le compare au schéma XSD officiel AED 2.01. C'est gratuit, sans inscription, et ça prend 5 secondes.</p>

<h3>Mon comptable peut-il générer le FAIA pour moi ?</h3>
<p>Oui, votre fiduciaire peut générer le FAIA depuis son propre outil ou récupérer le vôtre via un portail comptable. Avec faktur.lu, vous invitez votre fiduciaire en lecture seule et elle accède au FAIA en un clic.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Le format FAIA, les seuils d'application et les exigences AED peuvent évoluer. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Présentation du FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – FAIA version 2.01 (schémas XSD)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAQ FAIA (champ d'application et exclusions)</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Loi modifiée du 12 février 1979 (LIVA) - article 70</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 31 juillet 2026.</em></p>

<h2>Pour aller plus loin</h2>
<ul>
    <li><a href="/fr/blog/controle-fiscal-aed-luxembourg-2026-preparation">Contrôle fiscal AED Luxembourg : comment s'y préparer en 2026</a></li>
    <li><a href="/fr/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire">Article 63 LIVA : la numérotation séquentielle obligatoire</a></li>
    <li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg">Mentions obligatoires sur une facture au Luxembourg</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 en un clic, conforme AED</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère votre FAIA sur n'importe quelle période, valide automatiquement contre le schéma XSD officiel. Inclus dès le plan Gratuit.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Tester gratuitement</a>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Die <strong>FAIA</strong> (informatisierte Prüfdatei der AED) ist der Standard, den die luxemburgische Steuerverwaltung bei einer MwSt.-Prüfung verlangt — von den Unternehmen, die sie erstellen müssen. Es sind weit weniger, als man annimmt: <strong>vier Bedingungen müssen gleichzeitig erfüllt sein</strong>. Dieser Leitfaden erklärt Ihnen 2026 alles Wesentliche.</p>

<h2>Was ist die FAIA?</h2>

<p>Die <strong>FAIA</strong> ist eine strukturierte XML-Datei, die die AED aus dem von der OECD empfohlenen Standard <strong>SAF-T</strong> (Standard Audit File for Tax) abgeleitet hat. Sie bündelt sämtliche Buchhaltungsdaten eines Unternehmens für einen bestimmten Zeitraum in einem Format, das die Werkzeuge der AED automatisch lesen können.</p>

<p>Ihre Rechtsgrundlage ist <strong>Artikel 70 Absatz 3 des MwSt.-Gesetzes</strong> in der Fassung des <strong>Gesetzes vom 19. Dezember 2008</strong> (Mémorial A-206): Bücher und Unterlagen, die elektronisch vorliegen, sind auf Verlangen der Verwaltung in lesbarer und unmittelbar verständlicher Form zu übermitteln oder nach anderen von ihr bestimmten technischen Modalitäten. Die FAIA ist die gewählte Modalität.</p>

<p>Das geltende Format ist die <strong>Version 2.01</strong>, deren letzte von der AED veröffentlichte Aktualisierung aus dem Juli 2020 stammt — das Schema selbst ist älter. Es existiert in <strong>drei</strong> Ausprägungen: ein vollständiges Schema (<em>full</em>) und zwei reduzierte (<em>reduced A</em> und <em>reduced B</em>), je nach geführter Buchhaltung.</p>

<h2>Wer muss eine FAIA liefern?</h2>

<p>Entgegen einer verbreiteten Vorstellung sind <strong>nicht alle MwSt.-Pflichtigen zur Erstellung einer FAIA verpflichtet</strong>. Die <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offizielle FAQ der AED</a> nennt vier Bedingungen, und es müssen <strong>alle vier</strong> zusammentreffen.</p>

<h3>Die vier kumulativen Bedingungen</h3>

<ol>
    <li>Dem <strong>normierten Kontenplan (PCN)</strong> unterliegen</li>
    <li><strong>Keine vereinfachte Regelung</strong> in Anspruch nehmen</li>
    <li>Einen <strong>Jahresumsatz von über 112 000 €</strong> erzielen</li>
    <li>Ein Volumen von <strong>etwa 500 Buchungstransaktionen</strong> jährlich überschreiten</li>
</ol>

<p>Fehlt auch nur eine, sind Sie nicht betroffen. Die AED formuliert es unmissverständlich:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ich erziele einen Umsatz von 1.000.000 € und habe nur 400 Transaktionen in meiner Buchhaltung. Bin ich verpflichtet, eine FAIA-Datei zu liefern? — <strong>Nein.</strong> Obwohl Ihr Umsatz die 112.000 € übersteigt, bleibt Ihr Transaktionsvolumen in Grenzen, in denen eine manuelle Prüfung rationeller ist."</p>
</blockquote>

<h3>Was eine „Transaktion" wirklich ist</h3>

<p>Das ist der Punkt, der die Rechnung kippen lässt, und kaum jemand kennt ihn: Eine Transaktion <strong>ist keine Rechnung</strong>. Die AED definiert sie als eine <strong>vollständige Buchungskette</strong>. Ein Einkauf zerfällt etwa in vier verbundene Buchungen — Aufwandskonto, Vorsteuer, Lieferantenkonto, Zahlung —, die zusammen <strong>eine einzige</strong> Transaktion bilden.</p>

<p>Wer seine 600 Rechnungen zählt und daraus schließt, die Schwelle weit zu überschreiten, misst also wahrscheinlich die falsche Einheit.</p>

<h3>Wenn Sie dem PCN nicht unterliegen</h3>

<p>Dann entgehen Sie der eigentlichen FAIA-Pflicht, selbst bei hohem Umsatz und mehr als 500 Transaktionen. Artikel 70 gilt jedoch weiter: Die AED kann verlangen, dass Sie Ihre elektronischen Daten <strong>in einem abgegrenzten und strukturierten Format</strong> exportieren. Außerhalb der FAIA zu stehen entbindet nicht davon, seine Buchhaltung sauber ausgeben zu können.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Mit Ihrem Treuhänder zu klären</p>
    <p>Der genaue Anwendungsbereich hängt von Ihrem Buchführungsregime und Ihrer Tätigkeit ab. Im Zweifel fragen Sie Ihren Buchhalter oder wenden sich direkt an die AED — der klassische Irrtum ist zu glauben, alle seien betroffen (falsch) oder niemand (ebenfalls falsch).</p>
</div>

<p>Für die Betroffenen besteht die Pflicht nicht darin, die FAIA monatlich zu erzeugen: Es genügt, sie <strong>auf Verlangen der AED</strong> vorlegen zu können, in einer vom Prüfer im Einzelfall gesetzten Frist — eine feste gesetzliche Frist ist nicht veröffentlicht.</p>

<p>Zudem umfasst die Datei ein <strong>vollständiges, am Kalenderjahr ausgerichtetes Geschäftsjahr</strong>: verkürzte Geschäftsjahre werden abgelehnt, und eine Datei deckt nur einen Zeitraum ab. Eine Prüfung über drei Jahre erfordert also drei Dateien.</p>

<h2>Was enthält eine FAIA?</h2>

<p>Eine FAIA 2.01 gliedert sich in mehrere Pflichtabschnitte:</p>

<h3>1. Kopfteil (Header)</h3>

<ul>
    <li>Firmenname, MwSt.-Nummer, RCS, Matrikel</li>
    <li>Anschrift des Unternehmens</li>
    <li>Abgedeckter Zeitraum (Beginn- und Enddatum)</li>
    <li>Erstellungsdatum der Datei</li>
    <li>Währung (EUR)</li>
</ul>

<h3>2. Kontenplan (MasterFiles)</h3>

<p>Die Liste der verwendeten Konten (Kunden, Lieferanten, Sachkonten). Bei einer kleinen Struktur kann dieser Abschnitt minimal ausfallen.</p>

<h3>3. Ausgangsrechnungen (SalesInvoices)</h3>

<ul>
    <li>Rechnungsnummer (fortlaufend, konform mit <strong>Artikel 63 LIVA</strong> Ziffer 3°)</li>
    <li>Datum der Finalisierung</li>
    <li>Kunde: Name, MwSt.-Nummer, Anschrift</li>
    <li>Rechnungspositionen: Beschreibung, Menge, Einzelpreis, MwSt.</li>
    <li>Summen netto, MwSt., brutto</li>
</ul>

<h3>4. Buchungen (GeneralLedgerEntries)</h3>

<p>Die zugehörigen Buchungssätze (Soll/Haben je Konto), <strong>einschließlich derjenigen ohne unmittelbaren MwSt.-Bezug</strong>: Der Export muss die gesamte Buchhaltung umfassen.</p>

<h3>5. Fußteil (Footer)</h3>

<p>Kontrollsummen: Gesamtbetrag Soll, Gesamtbetrag Haben, Anzahl der Transaktionen.</p>

<h2>Die 4 häufigsten FAIA-Fehler</h2>

<ol>
    <li><strong>Nicht konforme Rechnungsnummerierung</strong> nach Artikel 63 LIVA Ziffer 3° (Lücken in der Sequenz oder Dubletten). Die Datei kann bei der Validierung zurückgewiesen werden.</li>
    <li><strong>Fehlende Pflichtfelder</strong>: MwSt.-Nummer des Kunden, vollständige Anschrift, Reverse-Charge-Vermerk beim innergemeinschaftlichen B2B.</li>
    <li><strong>Unstimmige Summen</strong> zwischen den Abschnitten (etwa Summe der Rechnungen ≠ ausgewiesener Total Sales Invoices).</li>
    <li><strong>Falsches Datumsformat</strong>: Die FAIA verlangt ISO 8601 (YYYY-MM-DD), nicht DD/MM/YYYY.</li>
</ol>

<h2>Wie man eine konforme FAIA erzeugt</h2>

<p>Drei Möglichkeiten, je nach Situation:</p>

<h3>Option 1 – Rechnungssoftware mit nativer FAIA</h3>

<p>Der einfachste und sicherste Weg. Eine Software wie <a href="/de" class="text-primary-500 hover:underline font-medium">faktur.lu</a> erzeugt die FAIA 2.01 auf Abruf, für jeden beliebigen Zeitraum, mit einem einzigen Klick. Die Datei entspricht automatisch dem offiziellen XSD-Schema.</p>

<h3>Option 2 – Export aus einer Buchhaltungssoftware</h3>

<p>Mehrere professionelle Buchhaltungsprogramme (Sage BOB 50, Sage 100 usw.) bieten einen FAIA-Export. Prüfen Sie beim Anbieter, ob die unterstützte Version wirklich 2.01 ist und ob das verwendete Schema (full / reduced) zu Ihrer Situation passt.</p>

<h3>Option 3 – Manuelle Erstellung (nicht empfohlen)</h3>

<p>Theoretisch mit einem XML-Entwickler möglich, aber fehleranfällig. Nur für Extremfälle (Altdaten, Migration).</p>

<h2>Ihre FAIA kostenlos validieren</h2>

<p>Bevor Sie Ihre FAIA an die AED senden, prüfen Sie sie gegen das offizielle Schema, um Fehler vor dem Prüfer zu finden. <strong>Die AED stellt kein Validierungswerkzeug bereit</strong> — ihre FAQ ist eindeutig: „nur das auf der Website der AED veröffentlichte Schema kann als Kontrollmechanismus dienen".</p>

<p>faktur.lu bietet einen <a href="/de/validateur-faia" class="text-primary-500 hover:underline font-medium">kostenlosen FAIA-Validator</a>, der:</p>

<ul>
    <li>die XML-Konformität mit dem XSD-Schema AED 2.01 prüft</li>
    <li>fehlende Pflichtfelder erkennt</li>
    <li>die Stimmigkeit der Summen kontrolliert</li>
    <li>die Fortlaufendheit der Rechnungsnummern prüft</li>
    <li>keine Daten speichert (die Datei bleibt auf Ihrem Rechner)</li>
</ul>

<h2>FAQ – FAIA</h2>

<h3>Muss man jedes Jahr eine FAIA einsenden?</h3>
<p>Nein. Die FAIA wird ausschließlich <strong>auf Verlangen der AED</strong> im Prüfungsfall erzeugt, und nur von den betroffenen Unternehmen. Sie wird nie zusammen mit der MwSt.-Erklärung eingereicht.</p>

<h3>Was passiert, wenn ich keine FAIA liefern kann, obwohl ich dazu verpflichtet bin?</h3>
<p>Der Prüfer geht davon aus, dass Ihre Buchhaltung nicht standardgemäß geführt wird. Sie riskieren ein <strong>Verwaltungsbußgeld von 250 € bis 10 000 € je Verstoß</strong> (Art. 77 LIVA), eine <strong>Schätzung von Amts wegen</strong> und — bei Weigerung, die Unterlagen während der Prüfung vorzulegen — eine Geldbuße von bis zu <strong>25 000 € pro Verzugstag</strong> nach Verwarnung.</p>

<h3>Ist die FAIA bei MwSt.-Freigrenze Pflicht?</h3>
<p><strong>In der Regel nein.</strong> Die Freigrenzenregelung (Artikel 57bis LIVA, Umsatz ≤ 50 000 €) hält den Steuerpflichtigen unter der Schwelle von 112 000 €, und die vereinfachte Regelung ist ohnehin für sich genommen ein Ausschlussgrund. Freigrenzen-Nutzer sind also <strong>üblicherweise nicht betroffen</strong>. Zu prüfen, wenn Ihr Buchführungsregime besonders ist.</p>

<h3>Wie erfahre ich vor einer Prüfung, ob meine FAIA konform ist?</h3>
<p>Nutzen Sie einen <a href="/de/validateur-faia" class="text-primary-500 hover:underline font-medium">FAIA-Validator</a>, der sie mit dem offiziellen XSD-Schema AED 2.01 vergleicht. Kostenlos, ohne Anmeldung, in 5 Sekunden erledigt.</p>

<h3>Kann mein Buchhalter die FAIA für mich erzeugen?</h3>
<p>Ja, Ihr Treuhänder kann die FAIA aus seinem eigenen Werkzeug erzeugen oder Ihre über ein Buchhalterportal abrufen. Mit faktur.lu laden Sie Ihren Treuhänder mit Lesezugriff ein, und er greift mit einem Klick auf die FAIA zu.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Das FAIA-Format, die Anwendungsschwellen und die Anforderungen der AED können sich ändern. Diese Seite wird regelmäßig aktualisiert — für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Vorstellung der FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – FAIA Version 2.01 (XSD-Schemata)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Anwendungsbereich und Ausschlüsse)</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA) – Artikel 70</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 31. Juli 2026.</em></p>

<h2>Weiterführend</h2>
<ul>
    <li><a href="/de/blog/controle-fiscal-aed-luxembourg-2026-preparation">Steuerprüfung der AED in Luxemburg: Vorbereitung 2026</a></li>
    <li><a href="/de/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire">Artikel 63 LIVA: die verpflichtende fortlaufende Nummerierung</a></li>
    <li><a href="/de/blog/mentions-obligatoires-facture-luxembourg">Pflichtangaben auf einer luxemburgischen Rechnung</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 mit einem Klick, AED-konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erzeugt Ihre FAIA für jeden Zeitraum und validiert automatisch gegen das offizielle XSD-Schema. Bereits im Gratis-Tarif enthalten.</p>
    <a href="/de/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">The <strong>FAIA</strong> (the AED's computerised audit file) is the standard the Luxembourg tax authority requires during a VAT audit — from the businesses that must produce it. Far fewer are covered than people assume: <strong>four conditions must be met at the same time</strong>. This guide explains it all for 2026.</p>

<h2>What is the FAIA?</h2>

<p>The <strong>FAIA</strong> is a structured XML file, adapted by the AED from the <strong>SAF-T</strong> standard (Standard Audit File for Tax) recommended by the OECD. It gathers all of a company's accounting data for a given period in a format the AED's tools can read automatically.</p>

<p>Its legal basis is <strong>article 70, paragraph 3, of the VAT law</strong>, as enacted by the <strong>law of 19 December 2008</strong> (Mémorial A-206): books and documents held in electronic form must, at the administration's request, be communicated in a legible and directly intelligible form, or according to any other technical arrangements it determines. The FAIA is the arrangement chosen.</p>

<p>The current format is <strong>version 2.01</strong>, whose last update published by the AED dates from July 2020 — the schema itself is older. It comes in <strong>three</strong> variants: a full schema and two reduced ones (<em>reduced A</em> and <em>reduced B</em>), depending on the accounting regime.</p>

<h2>Who must provide a FAIA?</h2>

<p>Contrary to a widespread belief, <strong>not every VAT-registered business is required to produce a FAIA</strong>. The <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED's official FAQ</a> sets out four conditions, and <strong>all four</strong> must be met together.</p>

<h3>The four cumulative conditions</h3>

<ol>
    <li>Being <strong>subject to the standard chart of accounts (PCN)</strong></li>
    <li><strong>Not benefiting from a simplified regime</strong></li>
    <li>Achieving <strong>annual turnover above €112,000</strong></li>
    <li>Exceeding a volume of roughly <strong>500 accounting transactions</strong> per year</li>
</ol>

<p>If even one is missing, you are not covered. The AED puts it plainly:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>"I have turnover of €1,000,000 and only 400 transactions in my accounts. Am I required to provide a FAIA file? — <strong>No.</strong> Although your turnover exceeds €112,000, your transaction volume remains within limits where a manual audit is more rational."</p>
</blockquote>

<h3>What a "transaction" actually is</h3>

<p>This is the point that flips the calculation, and almost nobody knows it: a transaction <strong>is not an invoice</strong>. The AED defines it as an <strong>entire posting chain</strong>. A purchase, for instance, breaks down into four linked entries — expense account, input VAT, supplier account, payment — which together form <strong>a single</strong> transaction.</p>

<p>A freelancer counting 600 invoices and concluding they are far above the threshold is therefore probably measuring the wrong unit.</p>

<h3>If you are not subject to the PCN</h3>

<p>You escape the FAIA obligation as such, even with high turnover and more than 500 transactions. But article 70 still applies: the AED may require you to export your electronic data <strong>in a delimited, structured format</strong>. Being outside the FAIA does not excuse you from being able to output your accounts cleanly.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">To confirm with your accountant</p>
    <p>The exact scope depends on your accounting regime and your activity. When in doubt, ask your accountant or contact the AED directly — the classic mistake is believing everyone is covered (wrong) or that nobody is (also wrong).</p>
</div>

<p>For those who are covered, the obligation is not to generate the FAIA every month: it is enough to be able to produce it <strong>at the AED's request</strong>, within a deadline set case by case by the auditor — no fixed statutory deadline is published.</p>

<p>The file also covers a <strong>full financial year aligned on the calendar year</strong>: truncated years are refused, and one file covers only one period. An audit spanning three years therefore requires three files.</p>

<h2>What does a FAIA contain?</h2>

<p>A FAIA 2.01 is structured into several mandatory sections:</p>

<h3>1. Header</h3>

<ul>
    <li>Business name, VAT number, RCS, registration number</li>
    <li>Company address</li>
    <li>Period covered (start and end dates)</li>
    <li>File creation date</li>
    <li>Currency (EUR)</li>
</ul>

<h3>2. Chart of accounts (MasterFiles)</h3>

<p>The list of accounts used (customers, suppliers, general accounts). For a small structure, this section can be minimal.</p>

<h3>3. Sales invoices (SalesInvoices)</h3>

<ul>
    <li>Invoice number (sequential, compliant with <strong>article 63 LIVA</strong> point 3°)</li>
    <li>Finalisation date</li>
    <li>Customer: name, VAT number, address</li>
    <li>Invoice lines: description, quantity, unit price, VAT</li>
    <li>Net, VAT and gross totals</li>
</ul>

<h3>4. Entries (GeneralLedgerEntries)</h3>

<p>The corresponding accounting entries (debit/credit per account), <strong>including those with no direct VAT relevance</strong>: the export must cover the accounts in their entirety.</p>

<h3>5. Footer</h3>

<p>Control totals: total debited, total credited, number of transactions.</p>

<h2>The 4 most common FAIA mistakes</h2>

<ol>
    <li><strong>Invoice numbering not compliant</strong> with article 63 LIVA point 3° (gaps in the sequence or duplicates). The file can be rejected at validation.</li>
    <li><strong>Missing mandatory fields</strong>: customer VAT number, full address, reverse charge wording for intra-EU B2B.</li>
    <li><strong>Inconsistent totals</strong> between sections (for instance, sum of invoices ≠ declared Total Sales Invoices).</li>
    <li><strong>Incorrect date format</strong>: the FAIA requires ISO 8601 (YYYY-MM-DD), not DD/MM/YYYY.</li>
</ol>

<h2>How to generate a compliant FAIA</h2>

<p>Three options depending on your situation:</p>

<h3>Option 1 – invoicing software with native FAIA</h3>

<p>The simplest and safest route. Software such as <a href="/en" class="text-primary-500 hover:underline font-medium">faktur.lu</a> generates the FAIA 2.01 on demand, for any period, in a single click. The file is automatically compliant with the official XSD schema.</p>

<h3>Option 2 – export from accounting software</h3>

<p>Several professional accounting packages (Sage BOB 50, Sage 100, etc.) offer a FAIA export. Check with your vendor that the supported version really is 2.01 and that the schema used (full / reduced) matches your situation.</p>

<h3>Option 3 – building it by hand (not recommended)</h3>

<p>Theoretically possible with an XML developer, but error-prone. Reserve it for edge cases (legacy data, migration).</p>

<h2>Validate your FAIA for free</h2>

<p>Before sending your FAIA to the AED, validate it against the official schema to catch errors before the auditor does. <strong>The AED provides no validation tool</strong> — its FAQ is explicit: "only the schema published on the AED website may serve as a control mechanism".</p>

<p>faktur.lu offers a <a href="/en/faia-validator" class="text-primary-500 hover:underline font-medium">free FAIA validator</a> that:</p>

<ul>
    <li>checks XML compliance with the AED 2.01 XSD schema</li>
    <li>detects missing mandatory fields</li>
    <li>verifies the consistency of totals</li>
    <li>checks the sequentiality of invoice numbers</li>
    <li>stores no data (the file stays on your machine)</li>
</ul>

<h2>FAQ – FAIA</h2>

<h3>Do you have to send a FAIA every year?</h3>
<p>No. The FAIA is produced solely <strong>at the AED's request</strong> during an audit, and only by the businesses covered. It is never filed alongside the VAT return.</p>

<h3>What happens if I cannot provide a FAIA when I am required to?</h3>
<p>The auditor concludes that your accounts are not kept to standard. You risk an <strong>administrative fine of €250 to €10,000 per infringement</strong> (art. 77 LIVA), an <strong>assessment on the administration's own estimate</strong>, and — where you refuse to produce records during the audit — a fine of up to <strong>€25,000 per day of delay</strong> after a warning.</p>

<h3>Is the FAIA mandatory under the VAT exemption regime?</h3>
<p><strong>Generally no.</strong> The exemption regime (article 57bis LIVA, turnover ≤ €50,000) keeps the taxable person below the €112,000 threshold, and a simplified regime is in any case a ground for exclusion on its own. Exempt businesses are therefore <strong>usually not covered</strong>. Worth checking if your accounting regime is unusual.</p>

<h3>How do I know my FAIA is compliant before an audit?</h3>
<p>Use a <a href="/en/faia-validator" class="text-primary-500 hover:underline font-medium">FAIA validator</a> that compares it to the official AED 2.01 XSD schema. Free, no sign-up, five seconds.</p>

<h3>Can my accountant generate the FAIA for me?</h3>
<p>Yes, your accountant can generate the FAIA from their own tool or retrieve yours through an accountant portal. With faktur.lu you invite your accountant in read-only mode and they access the FAIA in one click.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>The FAIA format, its application thresholds and the AED's requirements may change. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – FAIA overview</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – FAIA version 2.01 (XSD schemas)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA FAQ (scope and exclusions)</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA) – article 70</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 31 July 2026.</em></p>

<h2>Going further</h2>
<ul>
    <li><a href="/en/blog/controle-fiscal-aed-luxembourg-2026-preparation">AED tax audits in Luxembourg: preparing for 2026</a></li>
    <li><a href="/en/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire">Article 63 LIVA: mandatory sequential numbering</a></li>
    <li><a href="/en/blog/mentions-obligatoires-facture-luxembourg">Mandatory details on a Luxembourg invoice</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 in one click, AED-compliant</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates your FAIA for any period and validates it automatically against the official XSD schema. Included from the Free plan.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Try it free</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">De <strong>FAIA</strong> (informatiséierten Auditfichier vun der AED) ass de Standard, deen d'Lëtzebuerger Steierverwaltung bei enger TVA-Kontroll verlaangt — vun deenen Entreprisen, déi en produzéiere mussen. Et si vill manner, wéi ee mengt: <strong>véier Bedingunge musse gläichzäiteg erfëllt sinn</strong>. Dëse Guide erkläert Iech alles fir 2026.</p>

<h2>Wat ass de FAIA?</h2>

<p>De <strong>FAIA</strong> ass e strukturéierten XML-Fichier, deen d'AED aus dem Standard <strong>SAF-T</strong> (Standard Audit File for Tax) vun der OECD ofgeleet huet. En sammelt all d'Comptabilitéitsdate vun enger Entreprise fir eng bestëmmte Period, an engem Format, dat d'Tools vun der AED automatesch liese kënnen.</p>

<p>Seng gesetzlech Basis ass den <strong>Artikel 70, Paragraf 3, vum TVA-Gesetz</strong>, an der Fassung vum <strong>Gesetz vum 19. Dezember 2008</strong> (Mémorial A-206): Bicher an Dokumenter, déi elektronesch existéieren, mussen op Ufro vun der Administratioun an enger liesbarer an direkt verständlecher Form iwwerdroe ginn, oder no anere technesche Modalitéiten, déi si bestëmmt. De FAIA ass déi gewielte Modalitéit.</p>

<p>D'Format a Kraaft ass d'<strong>Versioun 2.01</strong>, deenen hir lescht vun der AED publizéiert Aktualiséierung aus dem Juli 2020 stamt — d'Schema selwer ass méi al. Et gëtt der <strong>dräi</strong>: e komplett Schema (<em>full</em>) an zwee reduzéiert (<em>reduced A</em> an <em>reduced B</em>), no der gefouterter Comptabilitéit.</p>

<h2>Wien muss e FAIA liwweren?</h2>

<p>Am Géigesaz zu enger verbreeter Virstellung sinn <strong>net all TVA-Assujettien dozou verflicht, e FAIA ze produzéieren</strong>. D'<a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">offiziell FAQ vun der AED</a> stellt véier Bedingungen op, an et musse <strong>all véier</strong> zesummen erfëllt sinn.</p>

<h3>Déi véier kumulativ Bedingungen</h3>

<ol>
    <li>Dem <strong>normaliséierte Comptesplang (PCN)</strong> ënnerleien</li>
    <li><strong>Kee vereinfachte Regime</strong> a Usproch huelen</li>
    <li>E <strong>Joresëmsaz vun iwwer 112 000 €</strong> realiséieren</li>
    <li>E Volume vun ongeféier <strong>500 comptabel Transaktiounen</strong> pro Joer iwwerschreiden</li>
</ol>

<p>Feelt nëmmen eng eenzeg, sidd Dir net betraff. D'AED formuléiert et ouni Ëmschwëff:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>„Ech realiséieren en Ëmsaz vun 1.000.000 € an hunn nëmmen 400 Transaktiounen a menger Comptabilitéit. Sinn ech verflicht, e FAIA-Fichier ze liwweren? — <strong>Neen.</strong> Och wann Ären Ëmsaz déi 112.000 € iwwerschreit, bleift Äre Transaktiounsvolume a Grenzen, wou eng manuell Kontroll méi rationell ass."</p>
</blockquote>

<h3>Wat eng „Transaktioun" wierklech ass</h3>

<p>Dat ass de Punkt, deen d'Rechnung kippe léisst, a bal kee weess et: eng Transaktioun <strong>ass keng Rechnung</strong>. D'AED definéiert se als eng <strong>ganz Comptabiliséierungskette</strong>. En Akaf zerfält zum Beispill a véier verbonnen Écritureën — Akafskont, TVA en amont, Fournisseurskont, Bezuelung —, déi zesummen <strong>eng eenzeg</strong> Transaktioun bilden.</p>

<p>Wien seng 600 Rechnungen zielt an doraus schléisst, de Seuil wäit ze iwwerschreiden, miesst also wahrscheinlech déi falsch Eenheet.</p>

<h3>Wann Dir dem PCN net ënnerleit</h3>

<p>Da entkommt Dir der eigentlecher FAIA-Flicht, souguer mat engem héijen Ëmsaz a méi wéi 500 Transaktiounen. Mä den Artikel 70 gëllt weider: d'AED ka verlaangen, datt Dir Är elektronesch Daten <strong>an engem ofgegrenzten a strukturéierte Format</strong> exportéiert. Ausserhalb vum FAIA ze stoen entbënnt net dovunner, seng Comptabilitéit propper erausginn ze kënnen.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Mat Ärer Fiduciaire ze klären</p>
    <p>De genaue Perimeter hänkt vun Ärem Comptabilitéitsregime an Ärer Aktivitéit of. Am Zweiwel frot Äre Comptabel oder kontaktéiert d'AED direkt — de klassesche Feeler ass ze mengen, jiddereen wier betraff (falsch) oder keen (och falsch).</p>
</div>

<p>Fir déi Betraffe besteet d'Flicht net doran, de FAIA all Mount ze generéieren: et duergeet, en <strong>op Ufro vun der AED</strong> virleeën ze kënnen, an enger Frist, déi de Kontrolleur vun Fall zu Fall festleet — et gëtt keng publizéiert fest gesetzlech Frist.</p>

<p>Ausserdeem deckt de Fichier e <strong>ganzt Geschäftsjoer um Kalennerjoer ausgeriicht</strong> of: verkierzte Geschäftsjoere ginn refuséiert, an e Fichier deckt nëmmen eng Period of. Eng Kontroll iwwer dräi Joer verlaangt also dräi Fichieren.</p>

<h2>Wat enthält e FAIA?</h2>

<p>E FAIA 2.01 ass a méi obligatoresch Sektiounen ënnerdeelt:</p>

<h3>1. Kappdeel (Header)</h3>

<ul>
    <li>Firmennumm, TVA-Nummer, RCS, Matrikel</li>
    <li>Adress vun der Entreprise</li>
    <li>Ofgedeckte Period (Ufanks- an Enndatum)</li>
    <li>Erstellungsdatum vum Fichier</li>
    <li>Devise (EUR)</li>
</ul>

<h3>2. Comptesplang (MasterFiles)</h3>

<p>D'Lëscht vun de benotzte Konten (Clienten, Fournisseuren, allgemeng Konten). Bei enger klenger Struktur kann dës Sektioun minimal sinn.</p>

<h3>3. Verkafsrechnungen (SalesInvoices)</h3>

<ul>
    <li>Rechnungsnummer (fortlafend, konform mam <strong>Artikel 63 LIVA</strong> Punkt 3°)</li>
    <li>Datum vun der Finaliséierung</li>
    <li>Client: Numm, TVA-Nummer, Adress</li>
    <li>Rechnungslinnen: Beschreiwung, Quantitéit, Eenheetspräis, TVA</li>
    <li>Totaler ouni TVA, TVA, mat TVA</li>
</ul>

<h3>4. Beweegungen (GeneralLedgerEntries)</h3>

<p>Déi entspriechend comptabel Écritureën (Debit/Kredit pro Kont), <strong>och déi ouni direkte Bezuch zur TVA</strong>: den Export muss d'ganz Comptabilitéit ëmfaassen.</p>

<h3>5. Fousszeil (Footer)</h3>

<p>Kontrolltotaler: Gesamtbetrag debitéiert, Gesamtbetrag kreditéiert, Unzuel vun Transaktiounen.</p>

<h2>Déi 4 heefegst FAIA-Feeler</h2>

<ol>
    <li><strong>Net konform Rechnungsnummeréierung</strong> no dem Artikel 63 LIVA Punkt 3° (Lacken an der Sequenz oder Duebelen). De Fichier kann bei der Validatioun zréckgewise ginn.</li>
    <li><strong>Feelend obligatoresch Felder</strong>: TVA-Nummer vum Client, vollstänneg Adress, Mentioun vun der Autoliquidatioun beim innergemeinschaftleche B2B.</li>
    <li><strong>Inkohärent Totaler</strong> tëscht de Sektiounen (zum Beispill Zomm vun de Rechnungen ≠ deklaréierten Total Sales Invoices).</li>
    <li><strong>Falscht Datumsformat</strong>: de FAIA verlaangt ISO 8601 (YYYY-MM-DD), net DD/MM/YYYY.</li>
</ol>

<h2>Wéi generéiert een e konforme FAIA</h2>

<p>Dräi Optiounen no Ärer Situatioun:</p>

<h3>Optioun 1 – Fakturatiounssoftware mat nativem FAIA</h3>

<p>Déi einfachst a séchersten Method. Eng Software wéi <a href="/lb" class="text-primary-500 hover:underline font-medium">faktur.lu</a> generéiert de FAIA 2.01 op Ufro, fir all beliebeg Period, mat engem eenzege Klick. De Fichier ass automatesch konform mam offizielle XSD-Schema.</p>

<h3>Optioun 2 – Export aus enger Comptabilitéitssoftware</h3>

<p>Verschidde professionell Comptabilitéitsprogrammer (Sage BOB 50, Sage 100 asw.) erlaben den FAIA-Export. Préift bei Ärem Editeur, ob déi ënnerstëtzte Versioun wierklech 2.01 ass an ob dat benotzte Schema (full / reduced) zu Ärer Situatioun passt.</p>

<h3>Optioun 3 – Manuell Konstruktioun (net recommandéiert)</h3>

<p>Theoretesch mat engem XML-Entwéckler méiglech, mä feeleranfälleg. Nëmme fir Extremfäll (Altdaten, Migratioun).</p>

<h2>Äre FAIA gratis validéieren</h2>

<p>Ier Dir Äre FAIA un d'AED schéckt, validéiert en géint dat offiziellt Schema, fir Feeler virum Kontrolleur ze fannen. <strong>D'AED stellt keen Validatiounsinstrument zur Verfügung</strong> — hir FAQ ass kloer: „nëmmen d'Schema, dat op der Internetsäit vun der AED publizéiert ass, ka als Kontrollmechanismus déngen".</p>

<p>faktur.lu bitt e <a href="/lb/faia-validator" class="text-primary-500 hover:underline font-medium">gratis FAIA-Validator</a> un, deen:</p>

<ul>
    <li>d'XML-Konformitéit mam XSD-Schema AED 2.01 iwwerpréift</li>
    <li>feelend obligatoresch Felder erkennt</li>
    <li>d'Kohärenz vun den Totaler kontrolléiert</li>
    <li>d'Sequenzialitéit vun de Rechnungsnummeren iwwerpréift</li>
    <li>keng Daten späichert (de Fichier bleift op Ärer Maschinn)</li>
</ul>

<h2>FAQ – FAIA</h2>

<h3>Muss ee all Joer e FAIA schécken?</h3>
<p>Neen. De FAIA gëtt eleng <strong>op Ufro vun der AED</strong> bei enger Kontroll produzéiert, an nëmme vun de betraffenen Entreprisen. En gëtt ni mat der TVA-Deklaratioun ofginn.</p>

<h3>Wat geschitt, wann ech kee FAIA liwwere kann, obwuel ech dozou verflicht sinn?</h3>
<p>De Kontrolleur geet dovun aus, datt Är Comptabilitéit net no de Standarde gefouert gëtt. Dir riskéiert eng <strong>administrativ Geldstrof vun 250 € bis 10 000 € pro Infraktioun</strong> (Art. 77 LIVA), eng <strong>Taxatioun vun Amts wéinst</strong>, an — bei Refus, d'Stécker bei der Kontroll ze weisen — eng Geldstrof bis zu <strong>25 000 € pro Dag Verspéidung</strong> no engem Avertissement.</p>

<h3>Ass de FAIA bei der TVA-Franchise obligatoresch?</h3>
<p><strong>Allgemeng neen.</strong> De Franchiseregime (Artikel 57bis LIVA, Ëmsaz ≤ 50 000 €) hält den Assujetti ënner dem Seuil vun 112 000 €, an de vereinfachte Regime ass sowisou fir sech eleng e Grond fir den Ausschloss. Franchisen sinn also <strong>normalerweis net betraff</strong>. Ze préiwen, wann Äre Comptabilitéitsregime besonnesch ass.</p>

<h3>Wéi weess ech virun enger Kontroll, ob mäi FAIA konform ass?</h3>
<p>Notzt e <a href="/lb/faia-validator" class="text-primary-500 hover:underline font-medium">FAIA-Validator</a>, deen en mam offizielle XSD-Schema AED 2.01 vergläicht. Gratis, ouni Aschreiwung, a 5 Sekonnen erleedegt.</p>

<h3>Kann mäi Comptabel de FAIA fir mech generéieren?</h3>
<p>Jo, Är Fiduciaire kann de FAIA aus hirem eegenen Tool generéieren oder Ären iwwer e Comptabelsportal ofruffen. Mat faktur.lu lued Dir Är Fiduciaire mat Lieszougrëff an, a si gräift mat engem Klick op de FAIA zou.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'FAIA-Format, d'Applikatiounsseuiler an d'Ufuerderunge vun der AED kënnen änneren. Dës Säit gëtt reegelméisseg aktualiséiert — fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Presentatioun vum FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – FAIA Versioun 2.01 (XSD-Schemae)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAIA-FAQ (Applikatiounsberäich an Ausschlëss)</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA) – Artikel 70</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 31. Juli 2026.</em></p>

<h2>Fir méi wäit ze goen</h2>
<ul>
    <li><a href="/lb/blog/controle-fiscal-aed-luxembourg-2026-preparation">Steierkontroll vun der AED zu Lëtzebuerg: sech 2026 virbereeden</a></li>
    <li><a href="/lb/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire">Artikel 63 LIVA: déi obligatoresch fortlafend Nummeréierung</a></li>
    <li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 mat engem Klick, AED-konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu generéiert Äre FAIA fir all Period a validéiert automatesch géint dat offiziellt XSD-Schema. Schonn am Gratis-Plang abegraff.</p>
    <a href="/lb/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">O <strong>FAIA</strong> (ficheiro de auditoria informatizado da AED) é o padrão exigido pela administração fiscal luxemburguesa durante uma inspeção de IVA — às empresas que têm de o produzir. São bem menos do que se julga: <strong>quatro condições têm de estar reunidas ao mesmo tempo</strong>. Este guia explica-lhe tudo em 2026.</p>

<h2>O que é o FAIA?</h2>

<p>O <strong>FAIA</strong> é um ficheiro XML estruturado, adaptado pela AED a partir do padrão <strong>SAF-T</strong> (Standard Audit File for Tax) recomendado pela OCDE. Reúne a totalidade dos dados contabilísticos de uma empresa num dado período, num formato legível automaticamente pelas ferramentas da AED.</p>

<p>A sua base legal é o <strong>artigo 70.º, parágrafo 3, da lei do IVA</strong>, na redação dada pela <strong>lei de 19 de dezembro de 2008</strong> (Mémorial A-206): os livros e documentos existentes sob forma eletrónica devem, a pedido da administração, ser comunicados numa forma legível e diretamente inteligível, ou segundo outras modalidades técnicas que ela determine. O FAIA é a modalidade escolhida.</p>

<p>O formato em vigor é a <strong>versão 2.01</strong>, cuja última atualização publicada pela AED data de julho de 2020 — o esquema em si é anterior. Existem <strong>três</strong>: um esquema completo (<em>full</em>) e dois reduzidos (<em>reduced A</em> e <em>reduced B</em>), consoante a contabilidade mantida.</p>

<h2>Quem deve fornecer um FAIA?</h2>

<p>Ao contrário de uma ideia difundida, <strong>nem todos os sujeitos passivos de IVA são obrigados a produzir um FAIA</strong>. As <a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">FAQ oficiais da AED</a> estabelecem quatro condições, e é preciso reunir <strong>as quatro</strong>.</p>

<h3>As quatro condições cumulativas</h3>

<ol>
    <li>Estar <strong>sujeito ao plano de contas normalizado (PCN)</strong></li>
    <li><strong>Não beneficiar de um regime simplificado</strong></li>
    <li>Realizar um <strong>volume de negócios anual superior a 112 000 €</strong></li>
    <li>Ultrapassar um volume de cerca de <strong>500 transações contabilísticas</strong> por ano</li>
</ol>

<p>Se faltar uma só, não está abrangido. A própria AED o formula sem rodeios:</p>

<blockquote class="border-l-4 border-slate-300 pl-4 italic text-slate-600 my-6">
    <p>«Realizo um volume de negócios de 1.000.000 € e tenho apenas 400 transações na minha contabilidade. Sou obrigado a fornecer um ficheiro FAIA? — <strong>Não.</strong> Embora o seu volume de negócios ultrapasse os 112.000 €, o seu volume de transações mantém-se em limites em que um controlo manual é mais racional.»</p>
</blockquote>

<h3>O que é realmente uma «transação»</h3>

<p>É o ponto que faz virar a conta, e quase ninguém o sabe: uma transação <strong>não é uma fatura</strong>. A AED define-a como uma <strong>cadeia inteira de lançamentos</strong>. Uma compra, por exemplo, decompõe-se em quatro lançamentos ligados — conta de compra, IVA a montante, conta de fornecedor, pagamento — que formam em conjunto <strong>uma única</strong> transação.</p>

<p>Quem conta as suas 600 faturas e conclui que ultrapassa largamente o limiar está, portanto, provavelmente a medir a unidade errada.</p>

<h3>Se não estiver sujeito ao PCN</h3>

<p>Escapa à obrigação FAIA propriamente dita, mesmo com um volume de negócios elevado e mais de 500 transações. Mas o artigo 70.º continua a aplicar-se: a AED pode exigir que exporte os seus dados eletrónicos <strong>num formato delimitado e estruturado</strong>. Estar fora do FAIA não dispensa de saber extrair a contabilidade corretamente.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">A confirmar com o seu contabilista</p>
    <p>O âmbito exato depende do seu regime contabilístico e da sua atividade. Em caso de dúvida, pergunte ao seu contabilista ou contacte diretamente a AED — o erro clássico é julgar que todos estão abrangidos (falso) ou que ninguém está (também falso).</p>
</div>

<p>Para os abrangidos, a obrigação não é gerar o FAIA todos os meses: basta poder produzi-lo <strong>a pedido da AED</strong>, num prazo fixado caso a caso pelo inspetor — não há qualquer prazo legal fixo publicado.</p>

<p>Além disso, o ficheiro abrange um <strong>exercício inteiro alinhado pelo ano civil</strong>: os exercícios truncados são recusados e um ficheiro só cobre um período. Uma inspeção a três anos exige, portanto, três ficheiros.</p>

<h2>O que contém um FAIA?</h2>

<p>Um FAIA 2.01 está estruturado em várias secções obrigatórias:</p>

<h3>1. Cabeçalho (Header)</h3>

<ul>
    <li>Denominação social, número de IVA, RCS, matrícula</li>
    <li>Endereço da empresa</li>
    <li>Período abrangido (data de início e data de fim)</li>
    <li>Data de criação do ficheiro</li>
    <li>Moeda (EUR)</li>
</ul>

<h3>2. Plano de contas (MasterFiles)</h3>

<p>A lista das contas utilizadas (clientes, fornecedores, contas gerais). Numa estrutura pequena, esta secção pode ser mínima.</p>

<h3>3. Faturas de venda (SalesInvoices)</h3>

<ul>
    <li>Número de fatura (sequencial, conforme o <strong>artigo 63 LIVA</strong> ponto 3°)</li>
    <li>Data de finalização</li>
    <li>Cliente: nome, n.º de IVA, endereço</li>
    <li>Linhas de fatura: descrição, quantidade, preço unitário, IVA</li>
    <li>Totais sem IVA, IVA, com IVA</li>
</ul>

<h3>4. Movimentos (GeneralLedgerEntries)</h3>

<p>Os lançamentos contabilísticos correspondentes (débito/crédito por conta), <strong>incluindo os sem ligação direta ao IVA</strong>: a exportação deve abranger a totalidade da contabilidade.</p>

<h3>5. Rodapé (Footer)</h3>

<p>Totais de controlo: montante total debitado, montante total creditado, número de transações.</p>

<h2>Os 4 erros FAIA mais frequentes</h2>

<ol>
    <li><strong>Numeração das faturas não conforme</strong> ao artigo 63 LIVA ponto 3° (falhas na sequência ou duplicados). O ficheiro pode ser rejeitado na validação.</li>
    <li><strong>Campos obrigatórios em falta</strong>: n.º de IVA do cliente, endereço completo, menção de autoliquidação para o B2B intra-UE.</li>
    <li><strong>Totais incoerentes</strong> entre as secções (por exemplo, soma das faturas ≠ Total Sales Invoices declarado).</li>
    <li><strong>Formato de data incorreto</strong>: o FAIA exige o formato ISO 8601 (YYYY-MM-DD), não DD/MM/YYYY.</li>
</ol>

<h2>Como gerar um FAIA conforme</h2>

<p>Três opções consoante a situação:</p>

<h3>Opção 1 – software de faturação com FAIA nativo</h3>

<p>O método mais simples e seguro. Um software como o <a href="/pt" class="text-primary-500 hover:underline font-medium">faktur.lu</a> gera o FAIA 2.01 a pedido, em qualquer período, com um único clique. O ficheiro fica automaticamente conforme ao esquema XSD oficial.</p>

<h3>Opção 2 – exportação a partir de um software de contabilidade</h3>

<p>Vários programas de contabilidade profissionais (Sage BOB 50, Sage 100, etc.) permitem a exportação FAIA. Confirme junto do seu fornecedor que a versão suportada é mesmo a 2.01 e que o esquema utilizado (full / reduced) corresponde à sua situação.</p>

<h3>Opção 3 – construção manual (desaconselhada)</h3>

<p>Teoricamente possível com um programador XML, mas propensa a erros. A reservar para casos extremos (dados legados, migração).</p>

<h2>Validar o seu FAIA gratuitamente</h2>

<p>Antes de enviar o FAIA à AED, valide-o contra o esquema oficial para detetar erros antes do inspetor. <strong>A AED não disponibiliza qualquer ferramenta de validação</strong> — as suas FAQ são explícitas: «apenas o esquema publicado no sítio da AED pode servir de mecanismo de controlo».</p>

<p>O faktur.lu propõe um <a href="/pt/validador-faia" class="text-primary-500 hover:underline font-medium">validador FAIA gratuito</a> que:</p>

<ul>
    <li>verifica a conformidade XML com o esquema XSD AED 2.01</li>
    <li>deteta os campos obrigatórios em falta</li>
    <li>controla a coerência dos totais</li>
    <li>verifica a sequencialidade dos números de fatura</li>
    <li>não armazena qualquer dado (o ficheiro permanece na sua máquina)</li>
</ul>

<h2>FAQ – FAIA</h2>

<h3>É preciso enviar um FAIA todos os anos?</h3>
<p>Não. O FAIA é produzido unicamente <strong>a pedido da AED</strong> em caso de inspeção, e apenas pelas empresas abrangidas. Nunca se entrega juntamente com a declaração de IVA.</p>

<h3>O que acontece se não conseguir fornecer um FAIA estando obrigado?</h3>
<p>O inspetor considera que a sua contabilidade não é mantida segundo os padrões. Arrisca uma <strong>coima administrativa de 250 € a 10 000 € por infração</strong> (art. 77 LIVA), uma <strong>tributação oficiosa</strong> e — em caso de recusa de apresentação dos documentos durante a inspeção — uma coima que pode atingir <strong>25 000 € por dia de atraso</strong> após advertência.</p>

<h3>O FAIA é obrigatório em regime de isenção de IVA?</h3>
<p><strong>Em geral, não.</strong> O regime de isenção (artigo 57bis LIVA, volume de negócios ≤ 50 000 €) coloca o sujeito passivo abaixo do limiar de 112 000 €, e o regime simplificado é, de qualquer modo, por si só um motivo de exclusão. Os isentos não estão, portanto, <strong>habitualmente abrangidos</strong>. A verificar se o seu regime contabilístico for particular.</p>

<h3>Como saber se o meu FAIA está conforme antes de uma inspeção?</h3>
<p>Utilize um <a href="/pt/validador-faia" class="text-primary-500 hover:underline font-medium">validador FAIA</a> que o compara ao esquema XSD oficial AED 2.01. É gratuito, sem inscrição, e demora 5 segundos.</p>

<h3>O meu contabilista pode gerar o FAIA por mim?</h3>
<p>Sim, o seu contabilista pode gerar o FAIA a partir da sua própria ferramenta ou obter o seu através de um portal contabilístico. Com o faktur.lu, convida o seu contabilista em modo de leitura e ele acede ao FAIA num clique.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>O formato FAIA, os limiares de aplicação e as exigências da AED podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia.html" target="_blank" rel="noopener">AED – Apresentação do FAIA</a></li>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html" target="_blank" rel="noopener">AED – FAIA versão 2.01 (esquemas XSD)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/backup/FAIA/FAIA/FAIA-FAQ.pdf" target="_blank" rel="noopener">AED – FAQ FAIA (âmbito de aplicação e exclusões)</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA) – artigo 70.º</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 31 de julho de 2026.</em></p>

<h2>Para ir mais longe</h2>
<ul>
    <li><a href="/pt/blog/controle-fiscal-aed-luxembourg-2026-preparation">Inspeção fiscal da AED no Luxemburgo: preparar-se em 2026</a></li>
    <li><a href="/pt/blog/article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire">Artigo 63 LIVA: a numeração sequencial obrigatória</a></li>
    <li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg">Menções obrigatórias numa fatura luxemburguesa</a></li>
</ul>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 num clique, conforme AED</h3>
    <p class="text-primary-800 mb-4">O faktur.lu gera o seu FAIA em qualquer período e valida automaticamente contra o esquema XSD oficial. Incluído desde o plano Gratuito.</p>
    <a href="/pt/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Testar gratuitamente</a>
</div>
HTML;
    }
};
