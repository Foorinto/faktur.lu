<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-shot seeder for the 5 scheduled long-tail SEO articles published
 * weekly every Monday in June 2026.
 *
 * Idempotent: skips any article whose slug already exists.
 * Each article has status='published' but a future published_at, so it only
 * appears in the listing on its scheduled date (BlogPost::scopePublished()
 * filters by published_at <= now()).
 */
class BlogScheduledJune2026Seeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip: {$article['slug']} (already exists)");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'fr',
                'translation_key' => $article['slug'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Created: {$article['slug']} (published_at {$article['published_at']})");
        }
    }

    protected function articles(): array
    {
        return [
            // ===== 1 — 2026-06-01 — Contrôle AED =====
            [
                'category_id' => 2, // reglementation
                'title' => "Contrôle fiscal AED Luxembourg : comment s'y préparer en 2026",
                'slug' => 'controle-fiscal-aed-luxembourg-2026-preparation',
                'meta_title' => 'Contrôle fiscal AED Luxembourg 2026 : guide de préparation | faktur.lu',
                'meta_description' => "Tout ce qu'il faut savoir sur le contrôle fiscal AED au Luxembourg : préparation, FAIA, documents requis, déroulement type. Guide pratique 2026 pour freelances et PME.",
                'excerpt' => "Recevoir une notification de contrôle de l'AED inquiète tous les indépendants luxembourgeois. Voici ce qu'il faut préparer et comment faktur.lu vous aide à passer le contrôle en 30 minutes au lieu de 3 jours.",
                'published_at' => '2026-06-01 09:00:00',
                'content' => <<<HTML
<p class="lead">Recevoir une notification de contrôle de l'<strong>Administration de l'Enregistrement, des Domaines et de la TVA (AED)</strong> inquiète tous les indépendants et dirigeants de PME au Luxembourg. Pourtant, avec une bonne préparation, un contrôle fiscal se passe en quelques heures sans stress. Voici comment vous y préparer en 2026.</p>

<h2>Qui est concerné par un contrôle AED ?</h2>

<p>Toute entreprise immatriculée à la TVA au Luxembourg peut faire l'objet d'un contrôle de l'AED. En pratique, l'administration cible :</p>

<ul>
    <li><strong>Les entreprises en croissance rapide</strong> dont le chiffre d'affaires fluctue de manière inhabituelle</li>
    <li><strong>Les secteurs à risque</strong> identifiés par l'AED (commerce, restauration, BTP, conseil)</li>
    <li><strong>Les déclarations TVA atypiques</strong> (crédit de TVA récurrent, opérations intra-UE importantes)</li>
    <li><strong>Les contrôles aléatoires</strong> : entre 3 % et 5 % des entreprises sont contrôlées chaque année selon des critères statistiques</li>
</ul>

<p>Un contrôle peut porter sur les <strong>5 dernières années</strong> (durée de prescription standard, portée à 10 ans en cas de fraude présumée).</p>

<h2>Les 3 types de contrôle AED</h2>

<h3>1. Contrôle sur pièces</h3>

<p>L'AED vous demande d'envoyer des documents par courrier ou voie électronique. Pas de visite physique. C'est le type le plus fréquent et le moins lourd.</p>

<h3>2. Contrôle sur place</h3>

<p>Un agent de l'AED se déplace dans vos locaux pour vérifier vos livres comptables, factures, justificatifs de dépenses. Annoncé par courrier avec préavis de 8 jours minimum.</p>

<h3>3. Contrôle inopiné</h3>

<p>Rare, réservé aux soupçons de fraude grave. L'agent se présente sans préavis. Vous avez le droit de demander la présence de votre fiduciaire pendant le contrôle.</p>

<h2>Les documents que l'AED peut demander</h2>

<p>La liste varie selon votre activité, mais voici les documents systématiquement contrôlés :</p>

<ul>
    <li><strong>Toutes vos factures émises et reçues</strong> sur la période contrôlée, avec mentions LIVA obligatoires</li>
    <li><strong>Le fichier FAIA</strong> (Fichier d'Audit Informatisé de l'AED) au format 2.01</li>
    <li><strong>Le livre des recettes et dépenses</strong> ou la comptabilité complète selon votre régime</li>
    <li><strong>Les déclarations de TVA</strong> mensuelles ou trimestrielles signées</li>
    <li><strong>Les relevés bancaires</strong> professionnels sur la période</li>
    <li><strong>Les justificatifs des dépenses déduites</strong> (notes de frais, factures fournisseurs)</li>
    <li><strong>Les contrats clients/fournisseurs</strong> significatifs</li>
    <li><strong>La preuve d'autoliquidation</strong> pour les opérations B2B intra-UE (numéros TVA validés via VIES)</li>
</ul>

<h2>FAIA : le point critique du contrôle</h2>

<p>Le <strong>FAIA</strong> est devenu le pivot de tous les contrôles AED depuis 2020. Si vous ne pouvez pas fournir un fichier FAIA conforme à la version 2.01, le contrôleur considère que votre comptabilité n'est pas tenue selon les standards.</p>

<p>Le FAIA est un fichier XML structuré qui regroupe :</p>

<ul>
    <li>Les en-têtes de l'entreprise (raison sociale, n° TVA, RCS, période)</li>
    <li>L'ensemble des factures de vente sur la période contrôlée</li>
    <li>Les écritures comptables associées</li>
    <li>Les totaux débit/crédit pour chaque mouvement</li>
</ul>

<p>Si vous facturez avec un tableur Excel ou un système qui ne génère pas de FAIA, vous devrez le reconstituer manuellement — ce qui peut prendre <strong>plusieurs jours de travail</strong> et expose à des erreurs.</p>

<h2>Comment se déroule un contrôle sur place</h2>

<ol>
    <li><strong>Notification écrite</strong> avec préavis minimum de 8 jours (sauf contrôle inopiné)</li>
    <li><strong>Arrivée du ou des agents</strong> AED munis d'une commission de contrôle</li>
    <li><strong>Demande des documents</strong> selon une liste pré-établie</li>
    <li><strong>Vérification des écritures</strong> et croisement avec les déclarations TVA</li>
    <li><strong>Entretien</strong> sur les opérations non standard ou les écarts détectés</li>
    <li><strong>Notification des conclusions</strong> par courrier dans les 60 jours</li>
</ol>

<p>Vous avez ensuite <strong>30 jours pour contester</strong> les conclusions par voie de réclamation auprès du directeur de l'AED.</p>

<h2>Les 5 erreurs qui coûtent cher</h2>

<ol>
    <li><strong>Numérotation non séquentielle des factures</strong> (Article 61 LIVA) : trous dans la séquence ou doublons = présomption d'omission de chiffre d'affaires</li>
    <li><strong>Mentions LIVA manquantes</strong> sur les factures : numéro TVA, RCS, matricule, date d'émission, etc.</li>
    <li><strong>Autoliquidation B2B intra-UE non documentée</strong> : sans validation VIES du n° TVA client, l'AED peut requalifier en opération imposable</li>
    <li><strong>Justificatifs de dépenses manquants</strong> ou illisibles (note manuscrite, ticket de caisse délavé)</li>
    <li><strong>Incohérences entre la déclaration TVA et les factures</strong> : un écart même minime déclenche une vérification approfondie</li>
</ol>

<h2>Préparer son contrôle en 5 étapes</h2>

<p>Que vous soyez freelance ou PME, voici la checklist à exécuter dès la notification :</p>

<ol>
    <li><strong>Préparer le FAIA</strong> sur la période demandée — idéalement en un clic depuis votre logiciel de facturation</li>
    <li><strong>Imprimer ou exporter</strong> toutes les factures émises (PDF/A pour l'archivage légal)</li>
    <li><strong>Vérifier la cohérence</strong> avec vos déclarations TVA déposées</li>
    <li><strong>Constituer un dossier</strong> par mois : factures + relevés bancaires + déclaration TVA correspondante</li>
    <li><strong>Prévenir votre fiduciaire</strong> et lui demander d'être présente pendant le contrôle (recommandé)</li>
</ol>

<h2>Comment faktur.lu vous prépare</h2>

<p>Avec une plateforme de facturation conforme Luxembourg, le contrôle AED se prépare en <strong>30 minutes au lieu de plusieurs jours</strong> :</p>

<ul>
    <li>Numérotation séquentielle automatique conforme à l'<strong>Article 61 LIVA</strong></li>
    <li>Mentions LIVA obligatoires générées automatiquement sur chaque facture</li>
    <li>Validation VIES temps réel pour les opérations B2B intra-UE</li>
    <li>Export <strong>FAIA 2.01</strong> en un clic sur n'importe quelle période</li>
    <li>Archivage <strong>PDF/A</strong> des factures (norme légale 10 ans, art. 16 du Code de commerce)</li>
    <li>Portail comptable : votre fiduciaire récupère tout ce qu'il faut sans aller-retour mail</li>
</ul>

<p><a href="/fr/register" class="text-primary-500 hover:underline font-medium">Démarrer gratuitement avec faktur.lu</a> et préparer votre prochain contrôle sans stress.</p>

<h2>FAQ — Contrôle fiscal AED</h2>

<h3>Combien de temps dure un contrôle AED ?</h3>
<p>Un contrôle sur pièces : 2 à 4 semaines (le temps que vous envoyiez les documents). Un contrôle sur place : généralement 1 à 3 jours en entreprise + 30 à 60 jours d'analyse côté AED.</p>

<h3>Puis-je refuser un contrôle ?</h3>
<p>Non. Le refus de contrôle est sanctionné par une amende administrative et présume la mauvaise foi. Vous pouvez en revanche demander un report pour motif légitime (maladie, absence prolongée) et exiger la présence de votre fiduciaire.</p>

<h3>Quelles sont les amendes en cas d'erreur ?</h3>
<p>Variable selon la nature : 10 % à 50 % de la TVA non déclarée en cas d'omission de bonne foi, jusqu'à 200 % en cas de fraude caractérisée. Des amendes forfaitaires (50 € à 25 000 €) peuvent aussi être appliquées pour les manquements formels (mentions manquantes, FAIA non fourni, etc.).</p>

<h3>Le contrôle peut-il porter sur des années anciennes ?</h3>
<p>Oui : la prescription est de 5 ans pour les manquements de bonne foi, portée à 10 ans en cas de fraude présumée. Il est donc crucial d'archiver ses documents comptables 10 ans (obligation légale, art. 16 du Code de commerce luxembourgeois).</p>

<h3>Que faire si je ne suis pas d'accord avec les conclusions ?</h3>
<p>Vous avez 30 jours pour adresser une réclamation écrite au directeur de l'AED, en exposant vos arguments et joignant les pièces justificatives. En cas de rejet, recours possible devant le Tribunal administratif dans les 3 mois.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Préparez votre prochain contrôle dès aujourd'hui</h3>
    <p class="text-primary-800 mb-4">Numérotation automatique, FAIA 2.01 en un clic, mentions LIVA conformes, archivage PDF/A 10 ans. Le contrôle AED devient une formalité.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Démarrer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2 — 2026-06-08 — FAIA =====
            [
                'category_id' => 2,
                'title' => "FAIA Luxembourg : guide complet du fichier d'audit informatisé en 2026",
                'slug' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'meta_title' => 'FAIA Luxembourg 2026 : guide complet du fichier d\'audit AED | faktur.lu',
                'meta_description' => "FAIA expliqué pour les freelances et PME luxembourgeois : qu'est-ce que c'est, qui est concerné, comment générer un FAIA 2.01 conforme. Validateur gratuit inclus.",
                'excerpt' => "Le FAIA est le format imposé par l'AED pour vos contrôles fiscaux. Voici tout ce qu'il faut comprendre en 2026 : la structure du fichier XML, qui est concerné, comment le générer, et comment le valider gratuitement.",
                'published_at' => '2026-06-08 09:00:00',
                'content' => <<<HTML
<p class="lead">Le <strong>FAIA</strong> (Fichier d'Audit Informatisé de l'AED) est le standard exigé par l'administration fiscale luxembourgeoise lors de tout contrôle de TVA. Pourtant, peu de freelances et de PME savent vraiment ce qu'il contient. Ce guide vous explique tout en 2026.</p>

<h2>Qu'est-ce que le FAIA ?</h2>

<p>Le <strong>FAIA</strong> est un fichier XML structuré, basé sur la norme internationale <strong>SAF-T</strong> (Standard Audit File for Tax) développée par l'OCDE. Il regroupe l'ensemble des données comptables d'une entreprise sur une période donnée, dans un format lisible automatiquement par les outils de l'AED.</p>

<p>Le format actuellement en vigueur est la <strong>version 2.01</strong>, publiée par l'AED en 2020. Le schéma XSD officiel est disponible sur le site de l'administration et fait foi en cas de litige sur la conformité.</p>

<h2>Qui doit fournir un FAIA ?</h2>

<p>Toute entreprise immatriculée à la TVA au Luxembourg <strong>doit pouvoir produire un FAIA conforme</strong> en cas de contrôle fiscal. Cela concerne :</p>

<ul>
    <li><strong>Les freelances et indépendants</strong>, y compris en régime de franchise TVA</li>
    <li><strong>Les PME</strong> sous tout régime (réel simplifié, réel normal)</li>
    <li><strong>Les sociétés commerciales</strong> (SARL, SA, SAS)</li>
    <li><strong>Les associations</strong> exerçant une activité économique soumise à la TVA</li>
</ul>

<p>L'obligation n'est pas de générer le FAIA chaque mois — il suffit de pouvoir le produire <strong>à la demande de l'AED</strong> dans un délai raisonnable (généralement 15 jours après notification).</p>

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

<p>La liste des comptes utilisés (clients, fournisseurs, comptes généraux). Pour un freelance ou une petite structure, cette section peut être minimale.</p>

<h3>3. Factures de vente (SalesInvoices)</h3>

<ul>
    <li>Numéro de facture (séquentiel, conforme Article 61 LIVA)</li>
    <li>Date de finalisation</li>
    <li>Client : nom, n° TVA, adresse</li>
    <li>Lignes de facture : description, quantité, prix unitaire, TVA</li>
    <li>Totaux HT, TVA, TTC</li>
</ul>

<h3>4. Mouvements (GeneralLedgerEntries)</h3>

<p>Les écritures comptables correspondantes (débit/crédit par compte).</p>

<h3>5. Pied de fichier (Footer)</h3>

<p>Totaux de contrôle : montant total débité, montant total crédité, nombre de transactions.</p>

<h2>Les 4 erreurs FAIA les plus fréquentes</h2>

<ol>
    <li><strong>Numérotation des factures non conforme</strong> à l'Article 61 LIVA (trous dans la séquence ou doublons). Le validateur officiel rejette le fichier.</li>
    <li><strong>Champs obligatoires manquants</strong> : n° TVA client, adresse complète, mention d'autoliquidation pour le B2B intra-UE.</li>
    <li><strong>Totaux incohérents</strong> entre les sections (par exemple, somme des factures ≠ Total Sales Invoices déclaré).</li>
    <li><strong>Format de date incorrect</strong> : le FAIA exige le format ISO 8601 (YYYY-MM-DD), pas DD/MM/YYYY.</li>
</ol>

<h2>Comment générer un FAIA conforme</h2>

<p>Trois options selon votre situation :</p>

<h3>Option 1 — Logiciel de facturation natif FAIA</h3>

<p>La méthode la plus simple et la plus sûre. Un logiciel comme <a href="/fr" class="text-primary-500 hover:underline font-medium">faktur.lu</a> génère le FAIA 2.01 à la demande, sur n'importe quelle période, avec un seul clic. Le fichier est automatiquement conforme au schéma XSD officiel.</p>

<h3>Option 2 — Export depuis un logiciel comptable</h3>

<p>Sage BOB 50, Sage 100 et la plupart des logiciels comptables professionnels permettent l'export FAIA. Vérifiez auprès de votre éditeur que la version supportée est bien la 2.01.</p>

<h3>Option 3 — Construction manuelle (déconseillée)</h3>

<p>Théoriquement possible avec un développeur XML, mais source d'erreurs fréquentes. À réserver aux cas extrêmes (legacy data, migration).</p>

<h2>Valider votre FAIA gratuitement</h2>

<p>Avant d'envoyer votre FAIA à l'AED, validez-le contre le schéma officiel pour détecter les erreurs avant le contrôleur.</p>

<p>faktur.lu propose un <a href="/fr/validateur-faia" class="text-primary-500 hover:underline font-medium">validateur FAIA gratuit</a> qui :</p>

<ul>
    <li>Vérifie la conformité XML au schéma XSD AED 2.01</li>
    <li>Détecte les champs obligatoires manquants</li>
    <li>Contrôle la cohérence des totaux</li>
    <li>Vérifie la séquentialité des numéros de facture</li>
    <li>Ne stocke aucune donnée (le fichier reste sur votre machine)</li>
</ul>

<h2>FAQ — FAIA</h2>

<h3>Faut-il envoyer un FAIA chaque année ?</h3>
<p>Non. Le FAIA est produit uniquement <strong>sur demande de l'AED</strong> en cas de contrôle. Vous devez cependant pouvoir le générer rapidement (sous 15 jours).</p>

<h3>Que se passe-t-il si je ne peux pas fournir de FAIA ?</h3>
<p>Le contrôleur considère que votre comptabilité n'est pas tenue selon les standards et peut appliquer une <strong>amende administrative</strong> ou estimer votre chiffre d'affaires de manière forfaitaire (en votre défaveur). Dans le pire cas, redressement TVA majoré.</p>

<h3>Le FAIA est-il obligatoire pour la franchise TVA ?</h3>
<p>Oui. Même en franchise (article 56 ter LIVA), vous devez pouvoir produire un FAIA si l'AED en fait la demande. Le FAIA inclura les mentions "TVA non applicable" pour chaque facture.</p>

<h3>Comment savoir si mon FAIA est conforme avant un contrôle ?</h3>
<p>Utilisez un <a href="/fr/validateur-faia" class="text-primary-500 hover:underline font-medium">validateur FAIA</a> qui le compare au schéma XSD officiel AED 2.01. C'est gratuit, sans inscription, et ça prend 5 secondes.</p>

<h3>Mon comptable peut-il générer le FAIA pour moi ?</h3>
<p>Oui, votre fiduciaire peut générer le FAIA depuis son propre outil ou récupérer le vôtre via un portail comptable. Avec faktur.lu, vous invitez votre fiduciaire en lecture seule et elle accède au FAIA en un clic.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 en un clic, conforme AED</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère votre FAIA sur n'importe quelle période, valide automatiquement contre le schéma XSD officiel. Inclus dès le plan Gratuit.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Tester gratuitement</a>
</div>
HTML,
            ],

            // ===== 3 — 2026-06-15 — Article 21 LIVA =====
            [
                'category_id' => 3, // freelances
                'title' => "Article 21 LIVA : autoliquidation TVA B2B intra-UE pour les freelances luxembourgeois",
                'slug' => 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'meta_title' => 'Article 21 LIVA : autoliquidation B2B intra-UE Luxembourg | faktur.lu',
                'meta_description' => "Vous facturez un client B2B en Europe ? L'article 21 LIVA impose l'autoliquidation. Voici les règles, les mentions obligatoires, la validation VIES et les pièges à éviter en 2026.",
                'excerpt' => "Vous êtes freelance luxembourgeois et vous facturez un client B2B en France, Belgique ou Allemagne ? L'article 21 LIVA s'applique : c'est l'autoliquidation TVA. Voici les règles, les mentions obligatoires et comment ne pas se tromper.",
                'published_at' => '2026-06-15 09:00:00',
                'content' => <<<HTML
<p class="lead">Vous êtes freelance luxembourgeois et vous facturez un client B2B en France, en Allemagne ou en Belgique ? L'<strong>article 21 LIVA</strong> s'applique : c'est l'autoliquidation TVA. Si vous oubliez la mention ou que vous facturez la TVA luxembourgeoise par erreur, vous risquez un redressement. Voici les règles claires.</p>

<h2>L'article 21 LIVA en bref</h2>

<p>L'<strong>article 21 de la loi luxembourgeoise sur la TVA (LIVA)</strong> transpose le principe européen du <strong>reverse charge</strong> pour les prestations de services entre assujettis dans deux États membres différents de l'UE.</p>

<p>Concrètement, lorsqu'un freelance luxembourgeois facture une prestation de service à une entreprise située dans un autre pays de l'UE :</p>

<ul>
    <li>La <strong>TVA luxembourgeoise n'est pas due</strong></li>
    <li>Le client étranger <strong>autoliquide la TVA</strong> dans son propre pays selon le taux local applicable</li>
    <li>La facture mentionne explicitement <strong>"Autoliquidation, article 21 LIVA"</strong></li>
</ul>

<h2>Quand l'article 21 LIVA s'applique-t-il ?</h2>

<p>Trois conditions cumulatives doivent être remplies :</p>

<ol>
    <li><strong>Prestation de services</strong> (pas ventes de biens — règles différentes)</li>
    <li><strong>Client professionnel (B2B)</strong> dans un autre État membre de l'Union européenne</li>
    <li><strong>Numéro de TVA intracommunautaire valide</strong> du client (validation obligatoire via VIES)</li>
</ol>

<p>Si l'une de ces conditions manque, la règle change :</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Règle TVA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Client B2B intra-UE avec n° TVA valide</td><td class="border border-gray-300 px-4 py-2"><strong>Autoliquidation (Art. 21 LIVA)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client B2C (particulier) intra-UE</td><td class="border border-gray-300 px-4 py-2">TVA luxembourgeoise 17 % (ou OSS si seuil dépassé)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client B2B intra-UE sans n° TVA validé</td><td class="border border-gray-300 px-4 py-2">TVA luxembourgeoise 17 %</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Client hors UE (B2B ou B2C)</td><td class="border border-gray-300 px-4 py-2">Exonération avec mention adaptée</td></tr>
    </tbody>
</table>

<h2>La validation VIES, étape critique</h2>

<p>VIES (<em>VAT Information Exchange System</em>) est le service européen qui permet de vérifier en temps réel la validité d'un numéro de TVA intracommunautaire.</p>

<p>Avant d'émettre une facture en autoliquidation, vous <strong>devez impérativement</strong> valider le n° TVA de votre client via VIES. Si le numéro n'est pas valide à la date de la facture, l'AED considère que la prestation est imposable au Luxembourg et vous redressera la TVA non collectée.</p>

<p>Le service VIES officiel est accessible à <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. Mais en pratique, votre logiciel de facturation doit le faire automatiquement à chaque facture.</p>

<h2>Mentions obligatoires sur la facture</h2>

<p>Une facture en autoliquidation article 21 LIVA doit comporter, en plus des mentions habituelles :</p>

<ul>
    <li>Votre <strong>numéro TVA luxembourgeois</strong> (LU + 8 chiffres)</li>
    <li>Le <strong>numéro TVA du client</strong> (préfixe pays + chiffres)</li>
    <li>La mention exacte : <strong>"Autoliquidation, article 21 LIVA"</strong> (ou équivalent dans la langue du client)</li>
    <li><strong>Pas de TVA ajoutée</strong> sur le total (TVA = 0 €)</li>
    <li>Le total HT qui devient aussi le total TTC</li>
</ul>

<p><strong>Important</strong> : la mention "autoliquidation" doit être visible et compréhensible. L'AED a déjà sanctionné des entreprises ayant utilisé des formulations trop vagues comme "TVA non applicable" sans précision.</p>

<h2>Exemple concret</h2>

<p>Marie est freelance UX designer à Luxembourg-Ville. Elle facture <strong>2 500 € HT</strong> à <strong>Acme SA</strong>, agence parisienne (n° TVA FR12345678901, validé via VIES).</p>

<p>Sa facture comporte :</p>

<ul>
    <li><strong>Émetteur</strong> : Marie Dupont, LU12345678</li>
    <li><strong>Destinataire</strong> : Acme SA, 10 rue de Rivoli 75001 Paris, FR12345678901</li>
    <li><strong>Prestation</strong> : Design UX site web — 2 500,00 €</li>
    <li><strong>TVA (0 %)</strong> : 0,00 €</li>
    <li><strong>Total TTC</strong> : 2 500,00 €</li>
    <li><strong>Mention</strong> : <em>"Autoliquidation, article 21 LIVA — TVA due par le preneur dans son pays."</em></li>
</ul>

<p>Acme SA déclare la TVA française (20 %) dans sa propre déclaration TVA, à la fois en TVA collectée et en TVA déductible (opération neutre pour eux).</p>

<h2>Les 3 erreurs qui coûtent cher</h2>

<ol>
    <li><strong>Oublier la validation VIES</strong> : si le n° TVA s'avère invalide ou suspendu, vous devez collecter la TVA luxembourgeoise (17 %). Sans le client pour payer, c'est de votre poche.</li>
    <li><strong>Mention "autoliquidation" manquante ou vague</strong> : l'AED requalifie en opération imposable au Luxembourg.</li>
    <li><strong>Mauvais traitement comptable</strong> : l'opération doit apparaître dans la déclaration TVA luxembourgeoise (rubrique services intracommunautaires) et dans la <strong>déclaration récapitulative VIES</strong> (mensuelle ou trimestrielle).</li>
</ol>

<h2>Comment faktur.lu vous protège</h2>

<p>faktur.lu détecte automatiquement les conditions d'autoliquidation et applique l'article 21 LIVA :</p>

<ul>
    <li><strong>Validation VIES temps réel</strong> dès que vous saisissez un client B2B intra-UE</li>
    <li>Mention <strong>"Autoliquidation, article 21 LIVA"</strong> ajoutée automatiquement sur la facture</li>
    <li><strong>TVA forcée à 0</strong> pour les prestations concernées, calculs vérifiés</li>
    <li><strong>Déclaration récapitulative VIES</strong> générée automatiquement en fin de période</li>
    <li>Mentions traduites dans la langue du client (FR, DE, EN, PT)</li>
</ul>

<p>Vous évitez les erreurs et facturez vos clients étrangers en toute conformité.</p>

<h2>FAQ — Article 21 LIVA</h2>

<h3>Et si mon client n'a pas de n° TVA ?</h3>
<p>Sans n° TVA validé via VIES, l'autoliquidation ne s'applique pas. Vous devez facturer la TVA luxembourgeoise (17 % standard). C'est aussi le cas pour les particuliers (B2C).</p>

<h3>Qu'en est-il des livraisons de biens (pas services) ?</h3>
<p>Pour les ventes de biens en B2B intra-UE, c'est l'<strong>article 43 LIVA</strong> qui s'applique (livraisons intra-communautaires exonérées). Les règles et mentions sont différentes.</p>

<h3>Faut-il déclarer l'opération à l'AED ?</h3>
<p>Oui. L'opération apparaît dans :</p>
<ul>
    <li>Votre <strong>déclaration TVA</strong> luxembourgeoise (case prestations de services intra-UE)</li>
    <li>La <strong>déclaration récapitulative VIES</strong> (à déposer mensuellement si CA > 50 000 € sur 12 mois, sinon trimestriellement)</li>
</ul>

<h3>Le client peut-il refuser l'autoliquidation ?</h3>
<p>Non, c'est une règle européenne obligatoire. En revanche, vous pouvez refuser de facturer si le client n'a pas de n° TVA validé (ou facturer en TVA luxembourgeoise standard).</p>

<h3>Que se passe-t-il en cas de contrôle AED ?</h3>
<p>L'AED vérifie la <strong>preuve de validation VIES</strong> à la date de la facture (un screenshot ou un log automatique fait foi). Sans cette preuve, l'opération est requalifiée en opération imposable et vous devez la TVA + pénalités.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">L'autoliquidation, sans risque d'erreur</h3>
    <p class="text-primary-800 mb-4">faktur.lu valide VIES en temps réel, applique l'article 21 LIVA automatiquement, génère vos déclarations récapitulatives. Plus de stress pour vos clients européens.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Démarrer gratuitement</a>
</div>
HTML,
            ],

            // ===== 4 — 2026-06-22 — TVA 4 taux =====
            [
                'category_id' => 1, // guides
                'title' => "TVA Luxembourg 2026 : les 4 taux (17 %, 14 %, 8 %, 3 %) expliqués",
                'slug' => 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'meta_title' => 'TVA Luxembourg 2026 : guide des 4 taux (17%, 14%, 8%, 3%) | faktur.lu',
                'meta_description' => "Quel taux de TVA appliquer au Luxembourg en 2026 ? Standard 17%, intermédiaire 14%, réduit 8%, super-réduit 3%. Guide pratique avec exemples concrets pour chaque secteur.",
                'excerpt' => "Le Luxembourg applique 4 taux de TVA. Lequel utiliser pour quoi ? Voici le guide pratique 2026 avec des exemples concrets pour chaque taux : services, alimentation, livres, restauration, gaz, électricité.",
                'published_at' => '2026-06-22 09:00:00',
                'content' => <<<HTML
<p class="lead">Le Luxembourg applique <strong>4 taux de TVA différents</strong> en 2026 : 17 % (standard), 14 % (intermédiaire), 8 % (réduit) et 3 % (super-réduit). Bien choisir le bon taux est crucial pour la conformité fiscale. Voici le guide complet 2026 avec exemples concrets.</p>

<h2>Les 4 taux de TVA en vigueur en 2026</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Taux</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Désignation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">À qui ça s'applique</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>17 %</strong></td><td class="border border-gray-300 px-4 py-2">Standard</td><td class="border border-gray-300 px-4 py-2">La majorité des biens et services</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>14 %</strong></td><td class="border border-gray-300 px-4 py-2">Intermédiaire</td><td class="border border-gray-300 px-4 py-2">Vins, certains combustibles</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>8 %</strong></td><td class="border border-gray-300 px-4 py-2">Réduit</td><td class="border border-gray-300 px-4 py-2">Gaz, électricité, coiffure, certains travaux</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>3 %</strong></td><td class="border border-gray-300 px-4 py-2">Super-réduit</td><td class="border border-gray-300 px-4 py-2">Alimentation, livres, médicaments, transports publics</td></tr>
    </tbody>
</table>

<p>Ces taux sont en vigueur depuis le <strong>1er janvier 2024</strong> (le taux standard est revenu de 16 % à 17 % à cette date après la baisse temporaire de 2023).</p>

<h2>Taux standard 17 % : par défaut</h2>

<p>Le taux standard s'applique <strong>à toutes les opérations imposables</strong> qui ne relèvent pas explicitement d'un autre taux. Concrètement :</p>

<ul>
    <li><strong>Prestations de services</strong> : conseil, design, développement, marketing, formation, etc.</li>
    <li><strong>Ventes de biens</strong> non alimentaires : matériel informatique, mobilier, vêtements, etc.</li>
    <li><strong>Location de matériel professionnel</strong></li>
    <li><strong>Hébergement hôtelier</strong> (à partir de 4 étoiles)</li>
    <li><strong>Restaurants</strong> (boissons alcoolisées)</li>
</ul>

<p>C'est <strong>le taux par défaut</strong> que vous devez appliquer si vous avez un doute, en attendant de clarifier la situation avec votre fiduciaire.</p>

<h2>Taux intermédiaire 14 % : vins et combustibles</h2>

<p>Le taux intermédiaire concerne principalement :</p>

<ul>
    <li><strong>Vins</strong> (tranquilles et mousseux, sauf champagne)</li>
    <li><strong>Certains combustibles solides</strong> (charbon, bois de chauffage)</li>
    <li><strong>Quelques publications publicitaires</strong></li>
</ul>

<p>Ce taux est rare. Si vous n'êtes pas dans le secteur viticole ou du chauffage, vous ne l'utiliserez probablement jamais.</p>

<h2>Taux réduit 8 % : énergie et services à la personne</h2>

<p>Le taux réduit s'applique à de nombreux secteurs :</p>

<ul>
    <li><strong>Fourniture de gaz</strong> et d'électricité</li>
    <li><strong>Coiffure</strong></li>
    <li><strong>Restauration</strong> (hors boissons alcoolisées)</li>
    <li><strong>Hébergement hôtelier</strong> (1 à 3 étoiles)</li>
    <li><strong>Travaux de rénovation</strong> de logements anciens (sous conditions)</li>
    <li><strong>Spectacles vivants</strong> (théâtre, concerts)</li>
    <li><strong>Cordonnerie, réparation de bicyclettes, modification de vêtements</strong></li>
</ul>

<h2>Taux super-réduit 3 % : produits essentiels</h2>

<p>Le taux le plus bas couvre les produits jugés essentiels :</p>

<ul>
    <li><strong>Produits alimentaires</strong> de base (pain, lait, viande, légumes, fruits)</li>
    <li><strong>Livres, journaux, périodiques</strong> (papier et numérique depuis 2020)</li>
    <li><strong>Médicaments</strong> (sur prescription ou en vente libre)</li>
    <li><strong>Transports publics de personnes</strong> (bus, train, tram)</li>
    <li><strong>Production agricole</strong> (semences, engrais)</li>
    <li><strong>Eau distribuée par les réseaux publics</strong></li>
    <li><strong>Œuvres d'art</strong> (sous certaines conditions)</li>
</ul>

<h2>Cas particuliers fréquents</h2>

<h3>Restauration : 8 % ou 17 % ?</h3>

<p>Les <strong>repas servis sur place</strong> dans un restaurant sont à <strong>8 %</strong>, mais les <strong>boissons alcoolisées</strong> restent à <strong>17 %</strong>. Une bouteille de vin servie avec un repas est facturée distinctement.</p>

<h3>Hôtellerie : 8 % ou 17 % ?</h3>

<p>L'hébergement en hôtel 1 à 3 étoiles est à <strong>8 %</strong>. À partir de 4 étoiles, c'est <strong>17 %</strong>. Les services annexes (spa, room service) suivent leur propre régime.</p>

<h3>E-books : 3 % ou 17 % ?</h3>

<p>Depuis 2020, les <strong>livres numériques (e-books)</strong> bénéficient du même taux que les livres papier, soit <strong>3 %</strong>. Mais attention : les abonnements à des plateformes de streaming (Netflix, Spotify) restent à 17 %.</p>

<h3>Travaux de rénovation : 8 % ou 17 % ?</h3>

<p>Les travaux de rénovation d'un logement <strong>de plus de 2 ans</strong> bénéficient du taux réduit de <strong>8 %</strong>, sous condition que les matériaux et la main-d'œuvre soient facturés ensemble par l'entreprise du bâtiment. Pour un logement neuf : 17 %.</p>

<h2>Comment calculer la TVA luxembourgeoise</h2>

<p>Si vous facturez 1 000 € HT à un client luxembourgeois au taux standard 17 % :</p>

<ul>
    <li><strong>HT</strong> : 1 000,00 €</li>
    <li><strong>TVA (17 %)</strong> : 170,00 €</li>
    <li><strong>TTC</strong> : 1 170,00 €</li>
</ul>

<p>À l'inverse, si vous connaissez le TTC et que vous cherchez le HT :</p>

<ul>
    <li><strong>HT</strong> = TTC ÷ (1 + taux/100)</li>
    <li><strong>Exemple</strong> : 1 170 € TTC ÷ 1,17 = 1 000 € HT</li>
</ul>

<p>Pour gagner du temps, utilisez notre <a href="/fr/outils/calculateur-tva" class="text-primary-500 hover:underline font-medium">calculateur TVA Luxembourg gratuit</a> qui supporte les 4 taux.</p>

<h2>Erreurs de taux : les conséquences</h2>

<p>Appliquer le mauvais taux n'est pas anodin :</p>

<ul>
    <li><strong>Sous-facturation TVA</strong> (taux trop bas) : redressement par l'AED + intérêts de retard + pénalités (10 % à 50 %)</li>
    <li><strong>Sur-facturation TVA</strong> (taux trop élevé) : le client peut réclamer le remboursement, et vous devez régulariser auprès de l'AED</li>
    <li><strong>Refus de déduction côté client</strong> : si le taux est manifestement erroné, le client peut refuser de récupérer la TVA</li>
</ul>

<h2>Comment faktur.lu vous évite les erreurs</h2>

<p>faktur.lu applique automatiquement le bon taux selon la nature de la prestation :</p>

<ul>
    <li>Sélection du taux par <strong>type de prestation</strong> à la création du produit/service</li>
    <li>Détection automatique des cas <strong>B2B intra-UE</strong> (autoliquidation art. 21 LIVA)</li>
    <li>Application du <strong>taux 0</strong> en cas de franchise (art. 56 ter)</li>
    <li>Mentions LIVA obligatoires générées sur chaque facture</li>
</ul>

<h2>FAQ — TVA Luxembourg</h2>

<h3>Le taux standard est-il vraiment à 17 % en 2026 ?</h3>
<p>Oui, depuis le 1er janvier 2024. Le taux était temporairement à 16 % en 2023 pour soutenir le pouvoir d'achat, puis est revenu à 17 % qui est le taux historique.</p>

<h3>Mon activité est-elle exonérée de TVA ?</h3>
<p>Certaines activités sont exonérées (santé, éducation, assurance, location immobilière à usage d'habitation), mais cela signifie aussi que vous ne pouvez pas <strong>récupérer la TVA</strong> sur vos achats professionnels. Vérifiez votre cas avec votre fiduciaire.</p>

<h3>Quand changer de taux sur une facture en cours ?</h3>
<p>Si le taux change entre l'émission du devis et la livraison de la prestation, c'est le taux <strong>en vigueur à la date de finalisation de la facture</strong> qui s'applique (pas la date du devis).</p>

<h3>Et pour les ventes B2C transfrontalières ?</h3>
<p>Depuis 2021, le régime <strong>OSS (One-Stop Shop)</strong> s'applique : au-delà de 10 000 € de ventes B2C intra-UE par an, vous devez appliquer le taux du pays de destination. Sinon, vous pouvez appliquer la TVA luxembourgeoise.</p>

<h3>Comment justifier l'application d'un taux particulier en cas de contrôle ?</h3>
<p>Conservez les <strong>justificatifs de la nature de la prestation</strong> : bons de livraison, contrats, fiches techniques. L'AED peut demander à voir la nomenclature ou la composition d'un produit pour valider le taux appliqué.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Calculer la TVA luxembourgeoise en 5 secondes</h3>
    <p class="text-primary-800 mb-4">Notre calculateur TVA gratuit supporte les 4 taux luxembourgeois (17 %, 14 %, 8 %, 3 %). Sans inscription, sans carte.</p>
    <a href="/fr/outils/calculateur-tva" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Ouvrir le calculateur</a>
</div>
HTML,
            ],

            // ===== 5 — 2026-06-29 — Article 61 LIVA =====
            [
                'category_id' => 3, // freelances
                'title' => "Article 61 LIVA : pourquoi la numérotation séquentielle de vos factures est obligatoire",
                'slug' => 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'meta_title' => 'Article 61 LIVA Luxembourg : numérotation séquentielle obligatoire | faktur.lu',
                'meta_description' => "L'article 61 LIVA impose une numérotation continue de vos factures au Luxembourg. Trous, doublons, formats : ce qu'il faut savoir et comment l'automatiser pour rester conforme.",
                'excerpt' => "Vos factures au Luxembourg doivent porter un numéro unique, séquentiel et continu. C'est l'article 61 LIVA. Un trou dans la séquence ou un doublon peut déclencher un redressement. Voici comment respecter la règle sans y penser.",
                'published_at' => '2026-06-29 09:00:00',
                'content' => <<<HTML
<p class="lead">L'<strong>article 61 LIVA</strong> impose une règle simple mais fondamentale : vos factures luxembourgeoises doivent porter un <strong>numéro unique, séquentiel et continu</strong>. Un trou dans la séquence ou un doublon peut déclencher un redressement fiscal. Voici la règle expliquée et comment ne plus jamais y penser.</p>

<h2>Que dit exactement l'article 61 LIVA ?</h2>

<p>L'article 61 de la loi luxembourgeoise sur la TVA précise que toute facture émise par un assujetti doit comporter un <strong>numéro séquentiel</strong>, attribué de manière <strong>chronologique et continue</strong> pour chaque exercice fiscal.</p>

<p>En clair, trois règles cumulatives :</p>

<ol>
    <li><strong>Unicité</strong> : deux factures ne peuvent jamais porter le même numéro</li>
    <li><strong>Séquentialité</strong> : les numéros se suivent dans l'ordre (1, 2, 3, 4...)</li>
    <li><strong>Continuité</strong> : aucun trou (pas de passage de 5 à 8 sans 6 et 7)</li>
</ol>

<p>Cette obligation s'applique à <strong>tous les assujettis</strong> à la TVA luxembourgeoise, y compris en franchise (article 56 ter), même si la facture porte la mention "TVA non applicable".</p>

<h2>Pourquoi cette règle existe</h2>

<p>L'objectif est simple : <strong>empêcher la dissimulation de chiffre d'affaires</strong>. Sans numérotation continue, une entreprise pourrait facilement "oublier" certaines factures dans sa déclaration TVA. La séquentialité permet à l'AED de vérifier d'un coup d'œil si toutes les factures émises ont bien été déclarées.</p>

<p>C'est aussi pour cela que le <strong>FAIA</strong> (Fichier d'Audit Informatisé de l'AED) inclut systématiquement la liste des numéros de facture sur la période contrôlée. Un trou détecté dans le FAIA déclenche immédiatement une demande d'explication.</p>

<h2>Les formats acceptés</h2>

<p>L'article 61 ne précise pas un format unique. Vous pouvez utiliser n'importe quelle convention, tant que la séquentialité est respectée :</p>

<ul>
    <li><strong>Numérotation simple</strong> : 1, 2, 3, 4...</li>
    <li><strong>Avec préfixe</strong> : F-001, F-002, F-003...</li>
    <li><strong>Avec année</strong> : 2026-001, 2026-002, 2026-003...</li>
    <li><strong>Avec préfixe + année</strong> : F-2026-001, F-2026-002...</li>
    <li><strong>Avec client</strong> (déconseillé) : ACME-001, ACME-002 (rompt la continuité globale)</li>
</ul>

<p><strong>Astuce</strong> : un numéro avec <strong>année + compteur séquentiel</strong> (ex: F-2026-001) est le plus lisible et permet de repartir à 1 chaque année. C'est la pratique la plus répandue au Luxembourg.</p>

<h2>Reset annuel : autorisé ou pas ?</h2>

<p>Oui, vous pouvez recommencer la numérotation à <strong>1 chaque année fiscale</strong>. C'est même la pratique la plus courante. L'important est que <strong>dans une même année</strong>, la séquence soit continue.</p>

<p>Exemple valide :</p>

<ul>
    <li>F-2025-148 (dernière facture de 2025)</li>
    <li>F-2026-001 (première facture de 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>Le passage de 148 à 001 est autorisé car on change d'année. Mais à l'intérieur de 2026, vous ne pouvez pas passer de F-2026-001 à F-2026-003 sans avoir émis F-2026-002.</p>

<h2>Que se passe-t-il en cas d'erreur ?</h2>

<h3>Cas 1 — Vous avez émis un doublon (deux factures avec le même numéro)</h3>

<p>L'AED considère que l'une des deux factures est <strong>fictive ou frauduleuse</strong>. Vous devez :</p>

<ol>
    <li>Émettre un <strong>avoir</strong> sur l'une des deux factures (annulation comptable)</li>
    <li>Émettre une <strong>nouvelle facture</strong> avec le prochain numéro disponible</li>
    <li>Conserver les 3 documents (les 2 factures initiales + l'avoir)</li>
</ol>

<h3>Cas 2 — Vous avez un trou dans la séquence (manque le numéro 5 entre 4 et 6)</h3>

<p>Cas plus grave. Vous devez pouvoir <strong>expliquer le trou</strong> à l'AED. Trois explications recevables :</p>

<ul>
    <li>La facture a été émise puis <strong>annulée par avoir</strong> (vous gardez les deux documents)</li>
    <li>Erreur technique : vous avez créé un brouillon non finalisé. Dans ce cas, mieux vaut le finaliser et l'archiver, même si la prestation n'a pas eu lieu (en mentionnant "facture annulée")</li>
    <li>Bug informatique documenté (rare)</li>
</ul>

<p>Sans explication crédible, l'AED suppose une dissimulation et peut <strong>estimer forfaitairement</strong> le chiffre d'affaires manquant.</p>

<h3>Cas 3 — Vous voulez supprimer une facture déjà finalisée</h3>

<p>Impossible légalement. Une facture finalisée doit rester archivée 10 ans (article 16 du Code de commerce). Pour annuler :</p>

<ul>
    <li>Émettre un <strong>avoir</strong> du même montant (numérotation séparée généralement : AV-2026-001)</li>
    <li>Conserver les 2 documents (facture + avoir)</li>
</ul>

<h2>Les pièges classiques à éviter</h2>

<ol>
    <li><strong>Numérotation Excel manuelle</strong> : risque très élevé de doublons ou de trous (oubli de la dernière facture, formule cassée, copier-coller raté)</li>
    <li><strong>Plusieurs séries en parallèle</strong> (une par client ou par projet) : difficile à justifier en cas de contrôle</li>
    <li><strong>Numérotation reset en cours d'année</strong> sans changement d'exercice fiscal : interdit</li>
    <li><strong>Suppression de factures finalisées</strong> sans avoir de remplacement : violation de l'article 16 du Code de commerce + article 61 LIVA</li>
</ol>

<h2>Comment l'automatiser sans y penser</h2>

<p>Un logiciel de facturation comme <a href="/fr" class="text-primary-500 hover:underline font-medium">faktur.lu</a> gère la numérotation pour vous :</p>

<ul>
    <li><strong>Numérotation séquentielle automatique</strong> : impossible de créer un doublon</li>
    <li><strong>Pas de trou possible</strong> : à chaque finalisation, le compteur s'incrémente</li>
    <li><strong>Format personnalisable</strong> : préfixe, année, longueur du compteur, le tout configurable</li>
    <li><strong>Reset annuel automatique</strong> : compteur remis à 1 chaque 1er janvier (ou non, selon votre préférence)</li>
    <li><strong>Séquences séparées</strong> pour factures, avoirs et devis (compteurs indépendants mais chacun continu)</li>
    <li><strong>Vérification d'unicité</strong> à chaque création</li>
</ul>

<p>Avec ça, l'article 61 LIVA n'est plus une préoccupation : votre numérotation est conforme par construction.</p>

<h2>FAQ — Article 61 LIVA</h2>

<h3>Et si je facture en franchise TVA (article 56 ter) ?</h3>
<p>L'article 61 LIVA s'applique <strong>quand même</strong>. Toute facture émise, même sans TVA collectée, doit porter un numéro séquentiel et continu.</p>

<h3>Puis-je avoir une numérotation par client (ACME-001, ACME-002...) ?</h3>
<p>Techniquement, oui — mais c'est très fortement déconseillé. En cas de contrôle, l'AED demande la séquentialité <strong>globale</strong>. Vous devrez alors prouver qu'il n'y a pas de trou en cumulant toutes les séries clients, ce qui est complexe et risqué.</p>

<h3>Les devis sont-ils concernés ?</h3>
<p>Non, l'article 61 LIVA concerne uniquement les <strong>factures et avoirs</strong>. Vous pouvez numéroter vos devis comme vous voulez (mais une numérotation séquentielle reste recommandée pour votre propre suivi).</p>

<h3>Puis-je faire des séries séparées pour factures et avoirs ?</h3>
<p>Oui, c'est même recommandé. Une série pour les factures (F-2026-001, F-2026-002...) et une série pour les avoirs (AV-2026-001, AV-2026-002...). Chacune doit être continue, mais elles sont indépendantes l'une de l'autre.</p>

<h3>Que faire si je migre vers un nouveau logiciel en cours d'année ?</h3>
<p>Vous devez <strong>continuer la séquence</strong> dans le nouveau logiciel. Si vous étiez à F-2026-148 dans l'ancien, votre première facture dans le nouveau doit être F-2026-149. faktur.lu permet de configurer le compteur de départ à l'inscription pour éviter le trou.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Une numérotation conforme, sans y penser</h3>
    <p class="text-primary-800 mb-4">faktur.lu gère automatiquement votre numérotation séquentielle, l'article 61 LIVA est respecté par construction. Format personnalisable, reset annuel configurable.</p>
    <a href="/fr/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Démarrer gratuitement</a>
</div>
HTML,
            ],
        ];
    }
}
