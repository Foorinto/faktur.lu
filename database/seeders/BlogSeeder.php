<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Guides pratiques', 'slug' => 'guides', 'description' => 'Guides complets pour maîtriser la facturation au Luxembourg', 'sort_order' => 1],
            ['name' => 'Réglementation', 'slug' => 'reglementation', 'description' => 'Tout sur les obligations légales et fiscales luxembourgeoises', 'sort_order' => 2],
            ['name' => 'Freelances', 'slug' => 'freelances', 'description' => 'Conseils spécifiques pour les indépendants au Luxembourg', 'sort_order' => 3],
            ['name' => 'Actualités', 'slug' => 'actualites', 'description' => 'Les dernières nouvelles sur la facturation et la fiscalité', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            BlogCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }

        // Create tags
        $tags = [
            ['name' => 'FAIA', 'slug' => 'faia'],
            ['name' => 'TVA', 'slug' => 'tva'],
            ['name' => 'Facturation', 'slug' => 'facturation'],
            ['name' => 'Luxembourg', 'slug' => 'luxembourg'],
            ['name' => 'Freelance', 'slug' => 'freelance'],
            ['name' => 'Conformité', 'slug' => 'conformite'],
            ['name' => 'PME', 'slug' => 'pme'],
            ['name' => 'Obligations légales', 'slug' => 'obligations-legales'],
            ['name' => 'Comptabilité', 'slug' => 'comptabilite'],
            ['name' => 'Auto-entrepreneur', 'slug' => 'auto-entrepreneur'],
        ];

        foreach ($tags as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }

        // Get first admin user as author
        $author = User::first();

        // Create blog posts
        $this->createArticle1($author);
        $this->createArticle2($author);
        $this->createArticle3($author);
        $this->createArticle4($author);
        $this->createArticle5($author);
    }

    private function createArticle1($author): void
    {
        $category = BlogCategory::where('slug', 'guides')->first();

        $post = BlogPost::updateOrCreate(
            ['slug' => 'guide-complet-facturation-luxembourg-2026'],
            [
                'author_id' => $author?->id,
                'category_id' => $category->id,
                'title' => 'Guide complet de la facturation au Luxembourg en 2026',
                'excerpt' => 'Découvrez toutes les règles de facturation au Luxembourg : mentions obligatoires, numérotation, TVA, conservation des documents. Le guide de référence pour les entreprises et freelances.',
                'content' => $this->getArticle1Content(),
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'meta_title' => 'Facturation Luxembourg 2026 : Guide Complet des Règles et Obligations',
                'meta_description' => 'Guide complet sur la facturation au Luxembourg en 2026. Mentions obligatoires, TVA, numérotation, FAIA : tout ce que vous devez savoir pour facturer en conformité.',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $post->tags()->sync(
            BlogTag::whereIn('slug', ['facturation', 'luxembourg', 'conformite', 'obligations-legales'])->pluck('id')
        );
    }

    private function createArticle2($author): void
    {
        $category = BlogCategory::where('slug', 'reglementation')->first();

        $post = BlogPost::updateOrCreate(
            ['slug' => 'faia-luxembourg-fichier-audit-informatise-guide'],
            [
                'author_id' => $author?->id,
                'category_id' => $category->id,
                'title' => 'FAIA Luxembourg : Tout savoir sur le fichier d\'audit informatisé',
                'excerpt' => 'Le FAIA (Fichier d\'Audit Informatisé) est obligatoire au Luxembourg. Découvrez ce qu\'il contient, qui doit le produire, et comment générer un fichier FAIA conforme.',
                'content' => $this->getArticle2Content(),
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'meta_title' => 'FAIA Luxembourg : Guide Complet du Fichier d\'Audit Informatisé',
                'meta_description' => 'Tout savoir sur le FAIA au Luxembourg : définition, obligations, contenu du fichier, comment le générer. Guide pratique pour être en conformité avec l\'AED.',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        $post->tags()->sync(
            BlogTag::whereIn('slug', ['faia', 'luxembourg', 'conformite', 'comptabilite'])->pluck('id')
        );
    }

    private function createArticle3($author): void
    {
        $category = BlogCategory::where('slug', 'reglementation')->first();

        $post = BlogPost::updateOrCreate(
            ['slug' => 'tva-luxembourg-taux-calcul-obligations'],
            [
                'author_id' => $author?->id,
                'category_id' => $category->id,
                'title' => 'TVA au Luxembourg : taux, calcul et obligations pour les entreprises',
                'excerpt' => 'Maîtrisez la TVA luxembourgeoise : les différents taux (17%, 14%, 8%, 3%), le calcul, les déclarations, et les cas d\'exonération. Guide complet pour les entreprises.',
                'content' => $this->getArticle3Content(),
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'meta_title' => 'TVA Luxembourg 2026 : Taux, Calcul et Obligations Fiscales',
                'meta_description' => 'Guide complet sur la TVA au Luxembourg : taux normal 17%, taux réduits, calcul, déclarations trimestrielles. Tout pour gérer la TVA de votre entreprise.',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]
        );

        $post->tags()->sync(
            BlogTag::whereIn('slug', ['tva', 'luxembourg', 'conformite', 'pme'])->pluck('id')
        );
    }

    private function createArticle4($author): void
    {
        $category = BlogCategory::where('slug', 'freelances')->first();

        $post = BlogPost::updateOrCreate(
            ['slug' => 'freelance-luxembourg-facturer-conformite'],
            [
                'author_id' => $author?->id,
                'category_id' => $category->id,
                'title' => 'Freelance au Luxembourg : comment facturer en toute conformité',
                'excerpt' => 'Vous êtes freelance au Luxembourg ? Découvrez comment créer des factures conformes, gérer la TVA, et respecter toutes les obligations légales luxembourgeoises.',
                'content' => $this->getArticle4Content(),
                'cover_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&h=630&fit=crop',
                'meta_title' => 'Freelance Luxembourg : Guide de Facturation Conforme 2026',
                'meta_description' => 'Guide complet pour freelances au Luxembourg : créer des factures conformes, gérer la TVA, obligations FAIA. Tout pour facturer en toute légalité.',
                'status' => 'published',
                'published_at' => now()->subDays(7),
            ]
        );

        $post->tags()->sync(
            BlogTag::whereIn('slug', ['freelance', 'facturation', 'luxembourg', 'auto-entrepreneur'])->pluck('id')
        );
    }

    private function createArticle5($author): void
    {
        $category = BlogCategory::where('slug', 'guides')->first();

        $post = BlogPost::updateOrCreate(
            ['slug' => 'mentions-obligatoires-facture-luxembourg'],
            [
                'author_id' => $author?->id,
                'category_id' => $category->id,
                'title' => 'Mentions obligatoires sur une facture au Luxembourg : checklist complète',
                'excerpt' => 'Quelles sont les mentions obligatoires sur une facture luxembourgeoise ? Découvrez la checklist complète pour créer des factures conformes à la législation.',
                'content' => $this->getArticle5Content(),
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => 'Mentions Obligatoires Facture Luxembourg : Checklist 2026',
                'meta_description' => 'Liste complète des mentions obligatoires sur une facture au Luxembourg. Checklist pratique pour créer des factures conformes et éviter les sanctions.',
                'status' => 'published',
                'published_at' => now()->subDays(10),
            ]
        );

        $post->tags()->sync(
            BlogTag::whereIn('slug', ['facturation', 'luxembourg', 'obligations-legales', 'conformite'])->pluck('id')
        );
    }

    private function getArticle1Content(): string
    {
        return <<<'HTML'
<p class="lead">La facturation au Luxembourg obéit à des règles précises définies par la législation fiscale. Que vous soyez une PME, un freelance ou une grande entreprise, ce guide vous explique tout ce que vous devez savoir pour facturer en conformité.</p>

<h2>Pourquoi la conformité de vos factures est essentielle</h2>

<p>Au Luxembourg, une facture n'est pas un simple document commercial. C'est une <strong>pièce comptable officielle</strong> qui sert de base pour :</p>

<ul>
    <li>Le calcul et la récupération de la TVA</li>
    <li>Les contrôles fiscaux de l'Administration des Contributions Directes (ACD)</li>
    <li>La génération du fichier FAIA pour l'Administration de l'Enregistrement et des Domaines (AED)</li>
    <li>La preuve de vos transactions commerciales</li>
</ul>

<p>Une facture non conforme peut entraîner le <strong>rejet de la déduction de TVA</strong> pour votre client, et des <strong>sanctions financières</strong> pour votre entreprise.</p>

<h2>Les mentions obligatoires sur une facture luxembourgeoise</h2>

<p>Selon l'article 63 de la loi TVA luxembourgeoise, toute facture doit contenir les informations suivantes :</p>

<h3>Informations sur l'émetteur</h3>

<ul>
    <li><strong>Nom ou raison sociale</strong> de votre entreprise</li>
    <li><strong>Adresse complète</strong> du siège social</li>
    <li><strong>Numéro de TVA intracommunautaire</strong> (format LU + 8 chiffres)</li>
    <li><strong>Numéro d'autorisation d'établissement</strong> (si applicable)</li>
</ul>

<h3>Informations sur le client</h3>

<ul>
    <li><strong>Nom ou raison sociale</strong> du client</li>
    <li><strong>Adresse complète</strong></li>
    <li><strong>Numéro de TVA</strong> (obligatoire pour les transactions B2B intracommunautaires)</li>
</ul>

<h3>Informations sur la facture</h3>

<ul>
    <li><strong>Numéro de facture unique</strong> suivant une séquence chronologique</li>
    <li><strong>Date d'émission</strong> de la facture</li>
    <li><strong>Date de livraison</strong> ou de prestation (si différente)</li>
</ul>

<h3>Détail des prestations</h3>

<ul>
    <li><strong>Description</strong> claire des biens ou services</li>
    <li><strong>Quantité</strong> et <strong>prix unitaire HT</strong></li>
    <li><strong>Taux de TVA applicable</strong> pour chaque ligne</li>
    <li><strong>Montant de TVA</strong> par taux</li>
    <li><strong>Total HT, TVA et TTC</strong></li>
</ul>

<h2>La numérotation des factures</h2>

<p>La numérotation de vos factures doit respecter des règles strictes :</p>

<ul>
    <li><strong>Séquence unique et chronologique</strong> : pas de trou dans la numérotation</li>
    <li><strong>Format libre</strong> mais cohérent (ex: 2026-0001, FAC-2026-001)</li>
    <li><strong>Une seule série</strong> par exercice comptable (sauf cas particuliers)</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conseil</p>
    <p class="text-purple-700">Utilisez un logiciel de facturation comme faktur.lu pour garantir automatiquement une numérotation conforme et éviter les erreurs.</p>
</div>

<h2>Les différents taux de TVA au Luxembourg</h2>

<p>Le Luxembourg applique quatre taux de TVA :</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taux</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taux normal (majorité des biens et services)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taux intermédiaire (vins, certains combustibles)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taux réduit (gaz, électricité, coiffure)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td>
            <td class="border border-gray-300 px-4 py-2">Taux super-réduit (alimentation, livres, médicaments)</td>
        </tr>
    </tbody>
</table>

<h2>Délais d'émission et de conservation</h2>

<h3>Délai d'émission</h3>

<p>Une facture doit être émise <strong>au plus tard le 15 du mois suivant</strong> la livraison du bien ou l'achèvement de la prestation.</p>

<h3>Durée de conservation</h3>

<p>Vous devez conserver vos factures pendant <strong>10 ans</strong> à partir de la fin de l'exercice comptable concerné. Cette obligation s'applique aux factures émises ET reçues.</p>

<h2>Le fichier FAIA : une obligation luxembourgeoise</h2>

<p>Le <strong>FAIA (Fichier d'Audit Informatisé)</strong> est un fichier XML standardisé que toute entreprise utilisant un logiciel de comptabilité ou de facturation doit pouvoir produire sur demande de l'administration fiscale.</p>

<p>Ce fichier contient :</p>

<ul>
    <li>Toutes vos écritures comptables</li>
    <li>Vos factures émises et reçues</li>
    <li>Vos clients et fournisseurs</li>
    <li>Vos paiements</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu génère automatiquement votre fichier FAIA</p>
    <p class="text-green-700">Notre logiciel produit un fichier FAIA conforme en un clic, prêt à être transmis à l'AED en cas de contrôle.</p>
</div>

<h2>Les erreurs à éviter</h2>

<ol>
    <li><strong>Oublier le numéro de TVA</strong> sur les factures B2B intracommunautaires</li>
    <li><strong>Utiliser une numérotation non séquentielle</strong> (trous dans la série)</li>
    <li><strong>Ne pas distinguer les taux de TVA</strong> quand plusieurs s'appliquent</li>
    <li><strong>Émettre des factures en retard</strong> (après le 15 du mois suivant)</li>
    <li><strong>Ne pas conserver les factures 10 ans</strong></li>
</ol>

<h2>Conclusion</h2>

<p>La facturation au Luxembourg requiert rigueur et conformité. En utilisant un <strong>logiciel de facturation adapté</strong> comme faktur.lu, vous vous assurez de respecter toutes les obligations légales tout en gagnant un temps précieux.</p>

<p>Notre solution génère automatiquement des factures conformes avec toutes les mentions obligatoires, une numérotation correcte, et un export FAIA intégré.</p>
HTML;
    }

    private function getArticle2Content(): string
    {
        return <<<'HTML'
<p class="lead">Le FAIA (Fichier d'Audit Informatisé) est une obligation légale au Luxembourg pour toutes les entreprises utilisant un logiciel de comptabilité ou de facturation. Découvrez ce qu'il faut savoir pour être en conformité.</p>

<h2>Qu'est-ce que le FAIA ?</h2>

<p>Le <strong>FAIA (Fichier d'Audit Informatisé)</strong>, aussi appelé <strong>SAF-T Luxembourg</strong>, est un fichier au format XML standardisé qui contient l'ensemble des données comptables et fiscales d'une entreprise pour une période donnée.</p>

<p>Ce fichier a été introduit par le <strong>règlement grand-ducal du 28 janvier 2009</strong> et permet à l'Administration de l'Enregistrement et des Domaines (AED) de réaliser des contrôles fiscaux de manière efficace et automatisée.</p>

<h2>Qui doit produire un fichier FAIA ?</h2>

<p>L'obligation de produire un fichier FAIA concerne <strong>toute entreprise ou personne</strong> qui :</p>

<ul>
    <li>Est assujettie à la TVA au Luxembourg</li>
    <li>Utilise un <strong>système informatique</strong> pour sa comptabilité ou sa facturation</li>
    <li>Fait l'objet d'un <strong>contrôle fiscal</strong> de l'AED</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Important</p>
    <p class="text-amber-700">Le FAIA n'est pas à transmettre de manière régulière. Il doit être produit <strong>sur demande</strong> de l'administration fiscale, généralement dans le cadre d'un contrôle.</p>
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
    <li>Toutes les écritures de la période</li>
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

<h2>Format technique du FAIA</h2>

<p>Le fichier FAIA doit respecter des spécifications techniques précises :</p>

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
            <td class="border border-gray-300 px-4 py-2">FAIA_v2.01_2022.xsd (version actuelle)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-semibold bg-gray-50">Période</td>
            <td class="border border-gray-300 px-4 py-2">Généralement un exercice comptable complet</td>
        </tr>
    </tbody>
</table>

<h2>Comment générer un fichier FAIA conforme ?</h2>

<p>Pour produire un fichier FAIA valide, vous avez plusieurs options :</p>

<h3>Option 1 : Logiciel de facturation compatible</h3>

<p>C'est la solution la plus simple. Un logiciel comme <strong>faktur.lu</strong> génère automatiquement un fichier FAIA conforme à partir de vos données de facturation.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Export FAIA en un clic avec faktur.lu</p>
    <p class="text-green-700">Notre logiciel génère un fichier FAIA validé selon le schéma XSD officiel, prêt à être transmis à l'AED.</p>
</div>

<h3>Option 2 : Logiciel comptable</h3>

<p>Les logiciels de comptabilité professionnels (Sage, BOB, etc.) proposent généralement un module d'export FAIA.</p>

<h3>Option 3 : Développement sur mesure</h3>

<p>Pour les grandes entreprises avec des systèmes propriétaires, un développement spécifique peut être nécessaire pour extraire et formater les données selon le schéma FAIA.</p>

<h2>Validation du fichier FAIA</h2>

<p>Avant de transmettre votre fichier FAIA à l'administration, il est recommandé de le valider :</p>

<ol>
    <li><strong>Validation XSD</strong> : vérifier que le fichier respecte le schéma XML officiel</li>
    <li><strong>Contrôle des totaux</strong> : s'assurer que les sommes sont cohérentes</li>
    <li><strong>Vérification des références</strong> : tous les identifiants (clients, comptes) doivent être présents</li>
</ol>

<p>L'AED met à disposition un <strong>outil de validation en ligne</strong> permettant de vérifier la conformité technique de votre fichier avant soumission.</p>

<h2>Délais et sanctions</h2>

<h3>Délai de production</h3>

<p>Lorsque l'AED demande un fichier FAIA dans le cadre d'un contrôle, l'entreprise dispose généralement d'un <strong>délai de 1 mois</strong> pour le produire.</p>

<h3>Sanctions en cas de non-conformité</h3>

<p>Le non-respect de l'obligation FAIA peut entraîner :</p>

<ul>
    <li>Des <strong>amendes administratives</strong></li>
    <li>Une <strong>taxation d'office</strong> par l'administration</li>
    <li>Le <strong>rejet de la comptabilité</strong> comme preuve</li>
</ul>

<h2>Bonnes pratiques</h2>

<ol>
    <li><strong>Testez régulièrement</strong> votre export FAIA, pas seulement lors d'un contrôle</li>
    <li><strong>Archivez</strong> les fichiers FAIA générés pour chaque exercice</li>
    <li><strong>Vérifiez la cohérence</strong> entre vos factures et vos écritures comptables</li>
    <li><strong>Utilisez un logiciel certifié</strong> ou testé pour l'export FAIA</li>
</ol>

<h2>Conclusion</h2>

<p>Le fichier FAIA est une obligation incontournable pour les entreprises luxembourgeoises utilisant des outils informatiques. En choisissant un logiciel de facturation compatible comme faktur.lu, vous vous assurez de pouvoir produire un fichier conforme à tout moment.</p>

<p>Notre solution intègre nativement l'export FAIA, validé selon les dernières spécifications de l'AED, pour vous permettre de répondre sereinement à toute demande de l'administration fiscale.</p>
HTML;
    }

    private function getArticle3Content(): string
    {
        return <<<'HTML'
<p class="lead">La TVA (Taxe sur la Valeur Ajoutée) est un élément central de la fiscalité luxembourgeoise. Comprendre les différents taux, savoir les appliquer correctement et respecter les obligations déclaratives est essentiel pour toute entreprise.</p>

<h2>Les taux de TVA au Luxembourg en 2026</h2>

<p>Le Luxembourg applique <strong>quatre taux de TVA</strong>, parmi les plus bas de l'Union Européenne :</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Taux</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Nom</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">17%</td>
            <td class="border border-gray-300 px-4 py-2">Taux normal</td>
            <td class="border border-gray-300 px-4 py-2">Majorité des biens et services</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">14%</td>
            <td class="border border-gray-300 px-4 py-2">Taux intermédiaire</td>
            <td class="border border-gray-300 px-4 py-2">Vins, combustibles solides, imprimés publicitaires</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">8%</td>
            <td class="border border-gray-300 px-4 py-2">Taux réduit</td>
            <td class="border border-gray-300 px-4 py-2">Gaz, électricité, coiffure, travaux de rénovation</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2 font-bold text-lg">3%</td>
            <td class="border border-gray-300 px-4 py-2">Taux super-réduit</td>
            <td class="border border-gray-300 px-4 py-2">Alimentation, livres, médicaments, transports</td>
        </tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold text-blue-800">ℹ️ Le saviez-vous ?</p>
    <p class="text-blue-700">Le taux normal de 17% au Luxembourg est le plus bas de l'Union Européenne, où la moyenne se situe autour de 21%.</p>
</div>

<h2>Détail des taux par catégorie</h2>

<h3>Taux super-réduit de 3%</h3>

<ul>
    <li>Produits alimentaires (hors alcool et restauration)</li>
    <li>Livres, journaux et périodiques</li>
    <li>Médicaments</li>
    <li>Transports de personnes</li>
    <li>Hébergement hôtelier</li>
    <li>Entrées aux événements culturels et sportifs</li>
    <li>Soins médicaux et dentaires (non exonérés)</li>
</ul>

<h3>Taux réduit de 8%</h3>

<ul>
    <li>Fourniture de gaz naturel et d'électricité</li>
    <li>Services de coiffure</li>
    <li>Certains travaux de rénovation de logements</li>
    <li>Nettoyage de vitres</li>
    <li>Petits services de réparation (vélos, chaussures, vêtements)</li>
</ul>

<h3>Taux intermédiaire de 14%</h3>

<ul>
    <li>Vins (moins de 13% d'alcool)</li>
    <li>Combustibles minéraux solides</li>
    <li>Mazout de chauffage</li>
    <li>Certains imprimés publicitaires</li>
</ul>

<h3>Taux normal de 17%</h3>

<p>Tous les biens et services qui ne bénéficient pas d'un taux réduit sont soumis au taux normal de 17%.</p>

<h2>Les opérations exonérées de TVA</h2>

<p>Certaines opérations sont <strong>exonérées de TVA</strong> au Luxembourg :</p>

<ul>
    <li>Services médicaux et paramédicaux</li>
    <li>Services d'enseignement</li>
    <li>Opérations bancaires et financières</li>
    <li>Opérations d'assurance</li>
    <li>Location de biens immobiliers (sauf option)</li>
    <li>Livraisons intracommunautaires (sous conditions)</li>
    <li>Exportations hors UE</li>
</ul>

<h2>Calcul de la TVA</h2>

<h3>Calculer la TVA à partir du HT</h3>

<p>Pour calculer le montant TTC à partir du prix HT :</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Montant TTC = Montant HT × (1 + taux TVA)</p>
    <p class="mt-2 text-sm text-gray-600">Exemple : 100€ HT × 1,17 = 117€ TTC</p>
</div>

<h3>Calculer le HT à partir du TTC</h3>

<p>Pour retrouver le montant HT à partir du TTC :</p>

<div class="bg-gray-100 p-4 rounded-lg my-4 font-mono">
    <p>Montant HT = Montant TTC ÷ (1 + taux TVA)</p>
    <p class="mt-2 text-sm text-gray-600">Exemple : 117€ TTC ÷ 1,17 = 100€ HT</p>
</div>

<h2>Les obligations déclaratives</h2>

<h3>Déclaration périodique de TVA</h3>

<p>Les assujettis doivent déposer une <strong>déclaration de TVA</strong> selon leur chiffre d'affaires :</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Chiffre d'affaires annuel</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Périodicité</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Supérieur à 620 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Mensuelle</strong></td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Entre 112 000€ et 620 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Trimestrielle</strong></td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Inférieur à 112 000€</td>
            <td class="border border-gray-300 px-4 py-2"><strong>Annuelle</strong></td>
        </tr>
    </tbody>
</table>

<h3>Délai de dépôt</h3>

<p>La déclaration doit être déposée <strong>avant le 15 du mois suivant</strong> la période concernée (ou le 1er mars pour les déclarations annuelles).</p>

<h3>Paiement de la TVA</h3>

<p>Le paiement de la TVA due doit accompagner la déclaration. En cas de crédit de TVA, un remboursement peut être demandé.</p>

<h2>La TVA intracommunautaire</h2>

<h3>Ventes à des professionnels UE (B2B)</h3>

<p>Les livraisons de biens et prestations de services à des assujettis dans d'autres pays de l'UE sont <strong>exonérées de TVA luxembourgeoise</strong>. Le client auto-liquide la TVA dans son pays.</p>

<p><strong>Conditions :</strong></p>
<ul>
    <li>Le client doit avoir un numéro de TVA intracommunautaire valide</li>
    <li>Ce numéro doit figurer sur la facture</li>
    <li>La mention "Exonération TVA - Article 43 paragraphe 1 k) de la loi TVA" doit apparaître</li>
</ul>

<h3>Ventes à des particuliers UE (B2C)</h3>

<p>Pour les ventes à distance à des particuliers, des seuils s'appliquent. Au-delà de 10 000€ de ventes annuelles vers d'autres pays UE, vous devez vous immatriculer à la TVA dans ces pays ou utiliser le <strong>guichet unique OSS</strong>.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu gère automatiquement la TVA</p>
    <p class="text-green-700">Notre logiciel applique le bon taux de TVA selon le type de client et génère les mentions légales appropriées sur vos factures.</p>
</div>

<h2>Le numéro de TVA intracommunautaire</h2>

<p>Le numéro de TVA luxembourgeois a le format <strong>LU + 8 chiffres</strong> (ex: LU12345678).</p>

<p>Ce numéro doit figurer sur :</p>
<ul>
    <li>Toutes vos factures</li>
    <li>Vos déclarations de TVA</li>
    <li>Vos déclarations d'échanges de biens (DEB)</li>
</ul>

<h2>Récupération de la TVA</h2>

<p>En tant qu'assujetti, vous pouvez <strong>déduire la TVA</strong> payée sur vos achats professionnels. Pour cela :</p>

<ul>
    <li>Vous devez posséder une <strong>facture conforme</strong></li>
    <li>L'achat doit être lié à votre <strong>activité professionnelle</strong></li>
    <li>La TVA doit être <strong>correctement mentionnée</strong> sur la facture</li>
</ul>

<h2>Conseils pratiques</h2>

<ol>
    <li><strong>Vérifiez toujours le taux applicable</strong> avant de facturer</li>
    <li><strong>Validez les numéros de TVA</strong> de vos clients UE sur le site VIES</li>
    <li><strong>Conservez vos factures 10 ans</strong> pour justifier vos déductions</li>
    <li><strong>Utilisez un logiciel adapté</strong> pour éviter les erreurs de calcul</li>
    <li><strong>Anticipez vos déclarations</strong> pour éviter les pénalités de retard</li>
</ol>

<h2>Conclusion</h2>

<p>La gestion de la TVA au Luxembourg requiert une bonne connaissance des taux applicables et des obligations déclaratives. En utilisant un logiciel de facturation comme faktur.lu, vous bénéficiez d'une application automatique des bons taux et de factures conformes aux exigences légales.</p>
HTML;
    }

    private function getArticle4Content(): string
    {
        return <<<'HTML'
<p class="lead">Vous lancez votre activité de freelance au Luxembourg ? La facturation est un aspect crucial de votre activité. Ce guide vous explique comment créer des factures conformes et gérer vos obligations fiscales.</p>

<h2>Le statut de freelance au Luxembourg</h2>

<p>Au Luxembourg, le freelance (ou indépendant) exerce généralement sous l'un de ces statuts :</p>

<ul>
    <li><strong>Entreprise individuelle</strong> : le statut le plus courant pour démarrer</li>
    <li><strong>Société unipersonnelle (SARL-S)</strong> : une société à responsabilité limitée simplifiée</li>
    <li><strong>Profession libérale</strong> : pour certaines activités réglementées</li>
</ul>

<p>Quel que soit votre statut, vous devez respecter les mêmes règles de facturation.</p>

<h2>S'immatriculer à la TVA</h2>

<p>Avant de commencer à facturer, vous devez obtenir un <strong>numéro de TVA intracommunautaire</strong> auprès de l'Administration de l'Enregistrement et des Domaines (AED).</p>

<h3>Procédure d'immatriculation</h3>

<ol>
    <li>Obtenir une <strong>autorisation d'établissement</strong> auprès du Ministère de l'Économie</li>
    <li>S'inscrire au <strong>Registre de Commerce</strong> (RCS) si applicable</li>
    <li>Demander l'<strong>immatriculation TVA</strong> via MyGuichet.lu</li>
    <li>Recevoir votre numéro au format <strong>LU + 8 chiffres</strong></li>
</ol>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold text-amber-800">⚠️ Attention</p>
    <p class="text-amber-700">Ne facturez jamais sans numéro de TVA valide. Vos factures seraient non conformes et vous vous exposeriez à des sanctions.</p>
</div>

<h2>Les mentions obligatoires sur vos factures</h2>

<p>En tant que freelance, vos factures doivent contenir :</p>

<h3>Vos informations</h3>

<ul>
    <li><strong>Nom complet</strong> ou raison sociale</li>
    <li><strong>Adresse professionnelle</strong> au Luxembourg</li>
    <li><strong>Numéro de TVA</strong> (LU12345678)</li>
    <li><strong>Numéro d'autorisation d'établissement</strong></li>
    <li>Éventuellement votre numéro RCS</li>
</ul>

<h3>Informations du client</h3>

<ul>
    <li>Nom ou raison sociale</li>
    <li>Adresse complète</li>
    <li>Numéro de TVA (obligatoire pour les clients professionnels)</li>
</ul>

<h3>Détails de la prestation</h3>

<ul>
    <li><strong>Numéro de facture</strong> unique et séquentiel</li>
    <li><strong>Date d'émission</strong></li>
    <li><strong>Description détaillée</strong> des services rendus</li>
    <li><strong>Nombre d'heures ou de jours</strong> (recommandé)</li>
    <li><strong>Tarif unitaire HT</strong></li>
    <li><strong>Montant HT, TVA et TTC</strong></li>
    <li><strong>Taux de TVA applicable</strong></li>
</ul>

<h2>Quel taux de TVA appliquer ?</h2>

<p>En tant que freelance, vous appliquez généralement le <strong>taux normal de 17%</strong> pour la plupart des prestations de services.</p>

<h3>Cas particuliers</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA applicable</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Client professionnel au Luxembourg</td>
            <td class="border border-gray-300 px-4 py-2">TVA 17%</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Client professionnel dans l'UE</td>
            <td class="border border-gray-300 px-4 py-2">Exonéré (autoliquidation)</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Client hors UE</td>
            <td class="border border-gray-300 px-4 py-2">Exonéré</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Client particulier au Luxembourg</td>
            <td class="border border-gray-300 px-4 py-2">TVA 17%</td>
        </tr>
    </tbody>
</table>

<h2>Facturer un client à l'étranger</h2>

<h3>Client professionnel dans l'UE</h3>

<p>Si votre client est une entreprise dans un autre pays de l'UE :</p>

<ol>
    <li><strong>Vérifiez son numéro de TVA</strong> sur le système VIES</li>
    <li><strong>N'appliquez pas de TVA</strong> sur votre facture</li>
    <li><strong>Ajoutez la mention</strong> : "Exonération de TVA - Article 44 de la loi du 12 février 1979"</li>
    <li><strong>Indiquez le numéro de TVA</strong> du client sur la facture</li>
</ol>

<h3>Client hors UE</h3>

<p>Pour les clients situés hors de l'Union Européenne, la prestation est exonérée de TVA. Mentionnez "Exonération de TVA - Prestation de services hors UE".</p>

<h2>La numérotation de vos factures</h2>

<p>Vos factures doivent suivre une <strong>numérotation chronologique et continue</strong> :</p>

<ul>
    <li>Pas de trou dans la séquence</li>
    <li>Format libre mais cohérent (ex: 2026-001, 2026-002...)</li>
    <li>Une seule série par année</li>
</ul>

<div class="bg-purple-50 border-l-4 border-purple-500 p-4 my-6">
    <p class="font-semibold text-purple-800">💡 Conseil</p>
    <p class="text-purple-700">Utilisez un logiciel de facturation comme faktur.lu pour générer automatiquement des numéros conformes et éviter les erreurs.</p>
</div>

<h2>Gérer vos déclarations de TVA</h2>

<p>En fonction de votre chiffre d'affaires, vous devez déposer des déclarations de TVA :</p>

<ul>
    <li><strong>Moins de 112 000€/an</strong> : déclaration annuelle</li>
    <li><strong>Entre 112 000€ et 620 000€/an</strong> : déclaration trimestrielle</li>
    <li><strong>Plus de 620 000€/an</strong> : déclaration mensuelle</li>
</ul>

<p>La déclaration se fait en ligne via <strong>eCDF</strong> (eTVA).</p>

<h2>Le fichier FAIA pour les freelances</h2>

<p>Si vous utilisez un logiciel de facturation, vous devez être capable de produire un <strong>fichier FAIA</strong> sur demande de l'administration fiscale.</p>

<p>Ce fichier contient l'ensemble de vos factures et données comptables dans un format XML standardisé.</p>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ faktur.lu génère votre fichier FAIA</p>
    <p class="text-green-700">Notre logiciel produit automatiquement un fichier FAIA conforme, prêt pour tout contrôle fiscal.</p>
</div>

<h2>Conseils pour les freelances débutants</h2>

<ol>
    <li><strong>Utilisez un logiciel adapté</strong> dès le départ pour éviter les erreurs</li>
    <li><strong>Conservez toutes vos factures</strong> (émises et reçues) pendant 10 ans</li>
    <li><strong>Séparez vos comptes</strong> personnels et professionnels</li>
    <li><strong>Facturez rapidement</strong> (dans les 15 jours suivant la prestation)</li>
    <li><strong>Vérifiez les numéros de TVA</strong> de vos clients UE avant de facturer</li>
    <li><strong>Anticipez vos déclarations</strong> pour éviter les pénalités</li>
    <li><strong>Consultez un comptable</strong> pour les questions complexes</li>
</ol>

<h2>Erreurs courantes à éviter</h2>

<ul>
    <li>❌ Facturer sans numéro de TVA</li>
    <li>❌ Oublier des mentions obligatoires</li>
    <li>❌ Appliquer un mauvais taux de TVA</li>
    <li>❌ Numérotation non séquentielle</li>
    <li>❌ Facturer en retard (après le 15 du mois suivant)</li>
    <li>❌ Ne pas vérifier les numéros de TVA UE</li>
</ul>

<h2>Conclusion</h2>

<p>La facturation en tant que freelance au Luxembourg n'est pas compliquée si vous suivez les règles. L'utilisation d'un logiciel de facturation adapté comme faktur.lu vous permet de créer des factures conformes en quelques clics, avec toutes les mentions obligatoires et les bons taux de TVA appliqués automatiquement.</p>

<p>Concentrez-vous sur votre métier, nous nous occupons de votre conformité !</p>
HTML;
    }

    private function getArticle5Content(): string
    {
        return <<<'HTML'
<p class="lead">Une facture non conforme peut être rejetée par votre client et vous exposer à des sanctions fiscales. Voici la checklist complète des mentions obligatoires pour créer des factures luxembourgeoises irréprochables.</p>

<h2>Checklist des mentions obligatoires</h2>

<p>Selon l'article 63 de la loi TVA luxembourgeoise, votre facture doit impérativement contenir les éléments suivants :</p>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur l'émetteur</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nom complet ou raison sociale</strong></li>
        <li>☐ <strong>Adresse complète</strong> du siège social</li>
        <li>☐ <strong>Numéro de TVA intracommunautaire</strong> (format LU + 8 chiffres)</li>
        <li>☐ <strong>Numéro RCS</strong> (si société inscrite)</li>
        <li>☐ <strong>Forme juridique</strong> (SARL, SA, etc.)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur le client</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Nom complet ou raison sociale</strong></li>
        <li>☐ <strong>Adresse complète</strong></li>
        <li>☐ <strong>Numéro de TVA</strong> (obligatoire pour B2B intracommunautaire)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations sur la facture</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Numéro de facture unique</strong> (séquence chronologique)</li>
        <li>☐ <strong>Date d'émission</strong> de la facture</li>
        <li>☐ <strong>Date de livraison</strong> ou de prestation (si différente)</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Détail des biens ou services</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Description claire</strong> et détaillée</li>
        <li>☐ <strong>Quantité</strong> livrée ou prestée</li>
        <li>☐ <strong>Prix unitaire HT</strong></li>
        <li>☐ <strong>Réductions ou rabais</strong> éventuels</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Informations TVA</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Taux de TVA</strong> applicable par ligne</li>
        <li>☐ <strong>Montant de TVA</strong> par taux</li>
        <li>☐ <strong>Base imposable</strong> par taux de TVA</li>
        <li>☐ <strong>Mention d'exonération</strong> si applicable</li>
    </ul>
</div>

<div class="bg-white border-2 border-purple-200 rounded-xl p-6 my-6">
    <h3 class="text-lg font-bold text-purple-800 mb-4">✅ Totaux</h3>
    <ul class="space-y-2">
        <li>☐ <strong>Total HT</strong> (hors taxes)</li>
        <li>☐ <strong>Total TVA</strong></li>
        <li>☐ <strong>Total TTC</strong> (toutes taxes comprises)</li>
    </ul>
</div>

<h2>Mentions conditionnelles selon les cas</h2>

<h3>Autoliquidation (client UE)</h3>

<p>Si vous facturez un client professionnel dans un autre pays de l'UE sans TVA :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Exonération de TVA - Article 44 de la loi du 12 février 1979 - Autoliquidation"</p>
</div>

<h3>Exportation hors UE</h3>

<p>Pour les ventes hors Union Européenne :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Exonération de TVA - Exportation hors UE"</p>
</div>

<h3>Franchise de TVA</h3>

<p>Si vous bénéficiez de la franchise de base (petite entreprise) :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"TVA non applicable - Article 60bis de la loi du 12 février 1979"</p>
</div>

<h3>Facture d'acompte</h3>

<p>Pour une facture d'acompte, ajoutez :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Acompte sur commande n° [référence] du [date]"</p>
</div>

<h3>Facture d'avoir (note de crédit)</h3>

<p>Pour un avoir, mentionnez obligatoirement :</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-4">
    <p class="font-mono text-sm">"Avoir sur facture n° [numéro] du [date]"</p>
</div>

<h2>La numérotation des factures</h2>

<p>La numérotation doit respecter ces règles :</p>

<ul>
    <li><strong>Unique</strong> : chaque facture a un numéro distinct</li>
    <li><strong>Chronologique</strong> : les numéros se suivent dans l'ordre d'émission</li>
    <li><strong>Sans rupture</strong> : pas de trou dans la séquence</li>
    <li><strong>Non réutilisable</strong> : un numéro ne peut être attribué qu'une fois</li>
</ul>

<h3>Exemples de formats acceptés</h3>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead class="bg-gray-100">
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Format</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Exemple</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Année + numéro</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">2026-0001, 2026-0002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Préfixe + numéro</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">FAC-001, FAC-002</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2">Numéro simple</td>
            <td class="border border-gray-300 px-4 py-2 font-mono">00001, 00002</td>
        </tr>
    </tbody>
</table>

<h2>Conditions de paiement</h2>

<p>Bien que non strictement obligatoires, il est recommandé d'indiquer :</p>

<ul>
    <li><strong>Délai de paiement</strong> (ex: 30 jours)</li>
    <li><strong>Date d'échéance</strong></li>
    <li><strong>Coordonnées bancaires</strong> (IBAN, BIC)</li>
    <li><strong>Pénalités de retard</strong> applicables</li>
</ul>

<h2>Conséquences d'une facture non conforme</h2>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Risques encourus</p>
    <ul class="text-red-700 mt-2">
        <li>Rejet de la déduction de TVA par votre client</li>
        <li>Amendes administratives de l'AED</li>
        <li>Redressement fiscal en cas de contrôle</li>
        <li>Perte de crédibilité commerciale</li>
    </ul>
</div>

<h2>Exemple de facture conforme</h2>

<p>Voici les éléments essentiels d'une facture conforme :</p>

<div class="bg-gray-50 border border-gray-200 rounded-lg p-6 my-6 text-sm">
    <div class="flex justify-between mb-6">
        <div>
            <p class="font-bold">Votre Société SARL</p>
            <p>123 rue du Commerce</p>
            <p>L-1234 Luxembourg</p>
            <p>TVA: LU12345678</p>
            <p>RCS: B123456</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-xl">FACTURE</p>
            <p>N° 2026-0042</p>
            <p>Date: 15/02/2026</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-semibold">Facturé à:</p>
        <p>Client Entreprise SA</p>
        <p>456 avenue des Affaires</p>
        <p>L-5678 Luxembourg</p>
        <p>TVA: LU87654321</p>
    </div>

    <table class="w-full mb-6">
        <thead class="border-b-2 border-gray-300">
            <tr>
                <th class="text-left py-2">Description</th>
                <th class="text-right py-2">Qté</th>
                <th class="text-right py-2">P.U. HT</th>
                <th class="text-right py-2">TVA</th>
                <th class="text-right py-2">Total HT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="py-2">Prestation de conseil</td>
                <td class="text-right">5h</td>
                <td class="text-right">150,00€</td>
                <td class="text-right">17%</td>
                <td class="text-right">750,00€</td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <p>Total HT: <strong>750,00€</strong></p>
        <p>TVA 17%: <strong>127,50€</strong></p>
        <p class="text-lg">Total TTC: <strong>877,50€</strong></p>
    </div>
</div>

<h2>Simplifiez-vous la vie avec faktur.lu</h2>

<p>Créer des factures conformes manuellement peut être source d'erreurs. <strong>faktur.lu</strong> automatise la conformité :</p>

<ul>
    <li>✅ Toutes les mentions obligatoires pré-remplies</li>
    <li>✅ Numérotation automatique et séquentielle</li>
    <li>✅ Calcul automatique de la TVA selon le cas</li>
    <li>✅ Mentions légales adaptées (autoliquidation, export...)</li>
    <li>✅ Export FAIA intégré pour les contrôles fiscaux</li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold text-green-800">✅ Créez des factures conformes en 2 minutes</p>
    <p class="text-green-700">Essayez faktur.lu gratuitement pendant 14 jours et découvrez la simplicité de la facturation conforme au Luxembourg.</p>
</div>

<h2>Conclusion</h2>

<p>Respecter les mentions obligatoires sur vos factures est essentiel pour être en conformité avec la législation luxembourgeoise. Utilisez cette checklist comme référence et adoptez un logiciel de facturation qui automatise ces obligations pour vous concentrer sur votre activité.</p>
HTML;
    }
}
