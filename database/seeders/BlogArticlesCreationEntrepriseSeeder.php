<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogArticlesCreationEntrepriseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer ou récupérer la catégorie
        $category = BlogCategory::firstOrCreate(
            ['slug' => 'guide-creation-entreprise'],
            ['name' => 'Guide création entreprise', 'description' => 'Guides pour créer son entreprise en Europe']
        );

        $articles = [
            $this->getLuxembourgArticle(),
            $this->getFranceArticle(),
            $this->getBelgiqueArticle(),
            $this->getAllemagneArticle(),
        ];

        foreach ($articles as $article) {
            BlogPost::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, [
                    'category_id' => $category->id,
                    'author_id' => null,
                    'status' => 'published',
                    'published_at' => now(),
                    'views_count' => 0,
                ])
            );
        }
    }

    private function getLuxembourgArticle(): array
    {
        return [
            'title' => 'Créer une entreprise individuelle au Luxembourg : Guide complet 2025',
            'slug' => 'creer-entreprise-individuelle-luxembourg-guide-2025',
            'excerpt' => 'Découvrez toutes les étapes pour créer votre entreprise individuelle au Luxembourg : autorisation d\'établissement, immatriculation RCS, cotisations sociales et obligations fiscales.',
            'meta_title' => 'Créer une entreprise individuelle au Luxembourg | Guide 2025',
            'meta_description' => 'Guide complet pour créer une entreprise individuelle au Luxembourg : démarches, coûts (100-150€), délais (1-3 mois), obligations TVA et cotisations sociales.',
            'cover_image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200',
            'content' => <<<'HTML'
<p class="lead">Le Luxembourg offre un environnement favorable aux entrepreneurs avec des démarches administratives relativement simples et des coûts de création modérés. Ce guide vous accompagne pas à pas dans la création de votre entreprise individuelle au Grand-Duché.</p>

<h2>Les formes juridiques pour entreprise individuelle</h2>

<p>Au Luxembourg, l'entrepreneur indépendant exerce sa profession en son nom propre, en qualité de :</p>

<ul>
    <li><strong>Commerçant</strong> : pour les activités commerciales</li>
    <li><strong>Artisan</strong> : pour les activités artisanales</li>
    <li><strong>Travailleur intellectuel indépendant</strong> : pour les professions libérales</li>
</ul>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">À noter</p>
    <p>Il n'existe pas d'équivalent exact au statut d'auto-entrepreneur français au Luxembourg. L'entreprise individuelle est la forme la plus proche et la plus simple.</p>
</div>

<h3>Caractéristiques principales</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspect</th>
            <th class="text-left p-2 bg-slate-100">Détail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Personnalité juridique</td><td class="p-2 border-b">Aucune - l'entrepreneur agit en son nom propre</td></tr>
        <tr><td class="p-2 border-b">Capital minimum</td><td class="p-2 border-b">Aucun capital minimum requis</td></tr>
        <tr><td class="p-2 border-b">Responsabilité</td><td class="p-2 border-b"><strong>Illimitée</strong> - responsable sur ses biens personnels</td></tr>
        <tr><td class="p-2 border-b">Formalisme</td><td class="p-2 border-b">Minimal - pas de statuts à rédiger</td></tr>
    </tbody>
</table>

<h2>Conditions et prérequis</h2>

<h3>Autorisation d'établissement (obligatoire)</h3>

<p>Toute activité économique exercée de manière habituelle nécessite une <strong>autorisation d'établissement préalable</strong>.</p>

<p><strong>Conditions à remplir :</strong></p>

<ul>
    <li><strong>Établissement physique</strong> : installation matérielle appropriée au Luxembourg</li>
    <li><strong>Gestion effective</strong> : présence physique et gestion journalière par le détenteur</li>
    <li><strong>Honorabilité professionnelle</strong> : casier judiciaire vierge, respect des obligations fiscales et sociales antérieures</li>
    <li><strong>Qualification professionnelle</strong> : selon l'activité visée</li>
</ul>

<h3>Qualifications professionnelles requises</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type d'activité</th>
            <th class="text-left p-2 bg-slate-100">Qualification requise</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Activités commerciales</td><td class="p-2 border-b">Généralement pas de diplôme spécifique requis</td></tr>
        <tr><td class="p-2 border-b">Activités artisanales</td><td class="p-2 border-b">DAP, CATP ou Brevet de Maîtrise</td></tr>
        <tr><td class="p-2 border-b">Professions libérales</td><td class="p-2 border-b">Diplômes spécifiques selon la profession</td></tr>
    </tbody>
</table>

<h2>Étapes de création détaillées</h2>

<h3>Étape 1 : Élaboration du projet</h3>
<ul>
    <li>Rédiger un business plan</li>
    <li>Contacter les organismes d'accompagnement (House of Entrepreneurship, Chambre de Commerce, Chambre des Métiers)</li>
</ul>

<h3>Étape 2 : Vérification des prérequis</h3>
<ul>
    <li>Vérifier la disponibilité du nom commercial</li>
    <li>S'assurer de posséder les qualifications requises</li>
    <li>Demander la reconnaissance des diplômes si nécessaire</li>
</ul>

<h3>Étape 3 : Demande d'autorisation d'établissement</h3>
<p><strong>Où :</strong> En ligne via MyGuichet.lu (avec certificat LuxTrust) ou par courrier postal</p>
<p><strong>Documents requis :</strong></p>
<ul>
    <li>Formulaire de demande</li>
    <li>Justificatifs de qualification professionnelle</li>
    <li>Extrait de casier judiciaire (bulletin n°3)</li>
    <li>Copie de la carte d'identité</li>
    <li>Preuve de paiement du droit de chancellerie (50 EUR)</li>
</ul>

<h3>Étape 4 : Immatriculation au RCS</h3>
<p><strong>Où :</strong> Dépôt électronique sur le site LBR (Luxembourg Business Registers)</p>
<p><strong>Documents requis :</strong></p>
<ul>
    <li>Formulaire de réquisition</li>
    <li>Autorisation d'établissement</li>
    <li>Pièce d'identité</li>
    <li>Acte de mariage / contrat de mariage (si applicable)</li>
</ul>

<h3>Étape 5 : Affiliation à la sécurité sociale</h3>
<p>Inscription auprès du CCSS (Centre Commun de la Sécurité Sociale) en tant qu'indépendant.</p>

<h3>Étape 6 : Inscription fiscale</h3>
<ul>
    <li>Inscription auprès de l'Administration des Contributions Directes</li>
    <li>Inscription à la TVA si chiffre d'affaires > 35 000 EUR</li>
</ul>

<h2>Coûts de création</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Poste</th>
            <th class="text-left p-2 bg-slate-100">Montant</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorisation d'établissement</td><td class="p-2 border-b">50 EUR</td></tr>
        <tr><td class="p-2 border-b">Immatriculation RCS</td><td class="p-2 border-b">~20-25 EUR</td></tr>
        <tr><td class="p-2 border-b">Reconnaissance de diplôme</td><td class="p-2 border-b">75 EUR (si nécessaire)</td></tr>
        <tr><td class="p-2 border-b font-semibold">Total estimatif</td><td class="p-2 border-b font-semibold">~100-150 EUR</td></tr>
    </tbody>
</table>

<h2>Délais moyens</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Démarche</th>
            <th class="text-left p-2 bg-slate-100">Délai</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Autorisation d'établissement</td><td class="p-2 border-b">Jusqu'à 3 mois</td></tr>
        <tr><td class="p-2 border-b">Reconnaissance de diplôme</td><td class="p-2 border-b">2 à 6 semaines</td></tr>
        <tr><td class="p-2 border-b">Immatriculation RCS</td><td class="p-2 border-b">Quelques jours</td></tr>
        <tr><td class="p-2 border-b font-semibold">Délai total estimé</td><td class="p-2 border-b font-semibold">1 à 3 mois</td></tr>
    </tbody>
</table>

<h2>Obligations après création</h2>

<h3>TVA (Taxe sur la Valeur Ajoutée)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">CA annuel ≤ 35 000 EUR</td><td class="p-2 border-b">Franchise de TVA (pas d'inscription obligatoire)</td></tr>
        <tr><td class="p-2 border-b">CA annuel > 35 000 EUR</td><td class="p-2 border-b">Inscription obligatoire + déclarations périodiques</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mention obligatoire en franchise</p>
    <p>« TVA non applicable, art. 57 du Code de la TVA luxembourgeois (Régime de franchise de taxe) »</p>
</div>

<h3>Cotisations sociales (CCSS)</h3>

<p>Les cotisations représentent environ <strong>25,3%</strong> du revenu, réparties comme suit :</p>

<ul>
    <li>Assurance maladie (soins) : 5,60%</li>
    <li>Assurance maladie (espèces) : 0,50%</li>
    <li>Assurance dépendance : 1,40%</li>
    <li>Assurance pension : 17,00%</li>
    <li>Assurance accident : 0,65%</li>
    <li>Santé au travail : 0,14%</li>
</ul>

<h3>Comptabilité</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Chiffre d'affaires annuel</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">< 100 000 EUR HT</td><td class="p-2 border-b">Comptabilité simplifiée</td></tr>
        <tr><td class="p-2 border-b">≥ 100 000 EUR HT</td><td class="p-2 border-b">Comptabilité normalisée obligatoire</td></tr>
    </tbody>
</table>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/forme-juridique/entreprise-individuelle_societe-personnes/entreprise-individuelle.html" target="_blank" rel="noopener">Guichet.lu - Entreprise individuelle</a></li>
    <li><a href="https://guichet.public.lu/fr/entreprises/creation-developpement/autorisation-etablissement/autorisation-honorabilite/autorisation-etablissement.html" target="_blank" rel="noopener">Guichet.lu - Autorisation d'établissement</a></li>
    <li><a href="https://lbr.lu/" target="_blank" rel="noopener">Luxembourg Business Registers (LBR)</a></li>
    <li><a href="https://ccss.public.lu/fr/independants.html" target="_blank" rel="noopener">CCSS - Indépendants</a></li>
    <li><a href="https://www.houseofentrepreneurship.lu/" target="_blank" rel="noopener">House of Entrepreneurship</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">En résumé</p>
    <p>La création d'une entreprise individuelle au Luxembourg est relativement simple et peu coûteuse (environ 100-150 EUR). Le processus prend généralement 1 à 3 mois et comprend l'obtention de l'autorisation d'établissement et l'immatriculation au RCS. Les cotisations sociales représentent environ 25% du revenu.</p>
</div>
HTML,
        ];
    }

    private function getFranceArticle(): array
    {
        return [
            'title' => 'Créer une entreprise individuelle en France : Guide complet 2025',
            'slug' => 'creer-entreprise-individuelle-france-guide-2025',
            'excerpt' => 'Tout savoir pour créer votre entreprise individuelle ou micro-entreprise en France : démarches via le guichet unique INPI, régime fiscal, cotisations URSSAF et obligations.',
            'meta_title' => 'Créer une entreprise individuelle en France | Guide 2025',
            'meta_description' => 'Guide complet pour créer une entreprise individuelle en France : micro-entreprise gratuite, guichet unique INPI, SIRET en 1-2 semaines, cotisations 12-25%.',
            'cover_image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1200',
            'content' => <<<'HTML'
<p class="lead">La France offre un cadre simplifié pour créer son entreprise individuelle, notamment avec le régime de la micro-entreprise. Depuis 2023, toutes les formalités se font via le guichet unique de l'INPI. Découvrez les étapes, coûts et obligations pour vous lancer.</p>

<h2>Les formes juridiques pour entreprise individuelle</h2>

<h3>Entreprise Individuelle (EI)</h3>

<p>L'entreprise individuelle permet d'exercer une activité en son nom propre, sans création de personne morale.</p>

<ul>
    <li>Pas de capital social requis</li>
    <li>Pas de statuts à rédiger</li>
    <li>Activités possibles : commerciales, artisanales, agricoles ou libérales</li>
    <li><strong>Depuis février 2022</strong> : le patrimoine personnel et professionnel sont automatiquement séparés</li>
</ul>

<h3>Micro-entreprise (Auto-entrepreneur)</h3>

<p>La micro-entreprise est un régime simplifié de l'entreprise individuelle avec des seuils de chiffre d'affaires :</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type d'activité</th>
            <th class="text-left p-2 bg-slate-100">Seuil de CA (2025)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Vente de marchandises, hébergement</td><td class="p-2 border-b">188 700 €</td></tr>
        <tr><td class="p-2 border-b">Prestations de services</td><td class="p-2 border-b">77 700 €</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Bon à savoir</p>
    <p>L'EIRL n'existe plus depuis le 15 mai 2022. Le nouveau statut EI intègre désormais la séparation automatique des patrimoines.</p>
</div>

<h2>Conditions et prérequis</h2>

<h3>Conditions personnelles</h3>

<ul>
    <li>Être <strong>majeur</strong> (ou mineur émancipé)</li>
    <li>Avoir une <strong>adresse en France</strong></li>
    <li>Ne pas être sous tutelle ou curatelle</li>
    <li>Ne pas être frappé d'une interdiction de gérer</li>
    <li>Être de nationalité française, européenne, ou avoir un titre de séjour autorisant l'exercice</li>
</ul>

<h3>Activités réglementées</h3>

<p>Certaines professions nécessitent des diplômes ou qualifications spécifiques : coiffure, bâtiment, professions de santé, etc.</p>

<h2>Étapes de création via le Guichet Unique INPI</h2>

<h3>Étape 1 : Préparation des documents</h3>
<ul>
    <li>Pièce d'identité (carte d'identité ou passeport) au format PDF</li>
    <li>Justificatif de domicile (si activité exercée à domicile)</li>
    <li>Attestations de qualification pour les activités réglementées</li>
</ul>

<h3>Étape 2 : Création du compte</h3>
<p>Se rendre sur <a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">formalites.entreprises.gouv.fr</a> et créer un compte via France Connect (recommandé) ou un identifiant INPI.</p>

<h3>Étape 3 : Déclaration d'activité</h3>
<ol>
    <li>Cliquer sur « Déclarer »</li>
    <li>Sélectionner « Entrepreneur individuel »</li>
    <li>Renseigner : nature de l'activité, adresse, date de début, options fiscales et sociales</li>
</ol>

<h3>Étape 4 : Validation et suivi</h3>
<ul>
    <li>Joindre les pièces justificatives</li>
    <li>Procéder au paiement si nécessaire</li>
    <li>Suivre l'avancement depuis le tableau de bord</li>
    <li>Inscription automatique au RNE (Registre National des Entreprises)</li>
</ul>

<h2>Coûts de création</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type d'activité</th>
            <th class="text-left p-2 bg-slate-100">Coût</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Activité commerciale</td><td class="p-2 border-b text-green-600 font-semibold">Gratuit</td></tr>
        <tr><td class="p-2 border-b">Activité artisanale</td><td class="p-2 border-b text-green-600 font-semibold">Gratuit</td></tr>
        <tr><td class="p-2 border-b">Profession libérale</td><td class="p-2 border-b text-green-600 font-semibold">Gratuit</td></tr>
        <tr><td class="p-2 border-b">Agent commercial</td><td class="p-2 border-b">23,86 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Attention</p>
    <p>Méfiez-vous des sites privés qui facturent des frais pour un service normalement gratuit.</p>
</div>

<h2>Délais moyens</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Étape</th>
            <th class="text-left p-2 bg-slate-100">Délai</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Déclaration en ligne</td><td class="p-2 border-b">Quelques minutes</td></tr>
        <tr><td class="p-2 border-b">Récépissé de dépôt</td><td class="p-2 border-b">24 heures</td></tr>
        <tr><td class="p-2 border-b">Obtention du numéro SIRET</td><td class="p-2 border-b font-semibold">1 à 2 semaines</td></tr>
        <tr><td class="p-2 border-b">Notification URSSAF</td><td class="p-2 border-b">4 à 10 semaines</td></tr>
    </tbody>
</table>

<h2>Obligations après création</h2>

<h3>Cotisations URSSAF</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type d'activité</th>
            <th class="text-left p-2 bg-slate-100">Taux 2024</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Achat-revente</td><td class="p-2 border-b">12,3 %</td></tr>
        <tr><td class="p-2 border-b">Services commerciaux/artisanaux</td><td class="p-2 border-b">21,2 %</td></tr>
        <tr><td class="p-2 border-b">Autres services</td><td class="p-2 border-b">25,6 %</td></tr>
        <tr><td class="p-2 border-b">Professions libérales (Cipav)</td><td class="p-2 border-b">23,2 %</td></tr>
    </tbody>
</table>

<p><strong>Fréquence :</strong> Déclaration mensuelle ou trimestrielle (au choix). Obligation de déclarer même si le CA est nul.</p>

<h3>TVA - Franchise en base</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type d'activité</th>
            <th class="text-left p-2 bg-slate-100">Seuil de base</th>
            <th class="text-left p-2 bg-slate-100">Seuil majoré</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Vente/Négoce/Hébergement</td><td class="p-2 border-b">85 000 €</td><td class="p-2 border-b">93 500 €</td></tr>
        <tr><td class="p-2 border-b">Prestations de services</td><td class="p-2 border-b">37 500 €</td><td class="p-2 border-b">41 250 €</td></tr>
    </tbody>
</table>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mention obligatoire en franchise</p>
    <p>« TVA non applicable, art. 293 B du CGI »</p>
</div>

<h3>CFE (Cotisation Foncière des Entreprises)</h3>

<ul>
    <li><strong>1ère année :</strong> Exonéré de paiement</li>
    <li><strong>Exonération totale :</strong> Si CA annuel < 5 000 €</li>
    <li><strong>Obligation :</strong> Déposer la déclaration n°1447-C avant le 31 décembre de la 1ère année</li>
</ul>

<h3>Obligations comptables</h3>

<ol>
    <li>Établir des <strong>factures conformes</strong> pour chaque vente/prestation</li>
    <li>Tenir un <strong>livre des recettes</strong> chronologique</li>
    <li>Tenir un <strong>registre des achats</strong> (si activité de vente)</li>
    <li><strong>Conserver les justificatifs</strong> pendant 10 ans</li>
</ol>

<h2>Aides disponibles</h2>

<h3>ACRE (Aide aux Créateurs et Repreneurs d'Entreprise)</h3>

<ul>
    <li><strong>Exonération partielle</strong> des cotisations sociales la 1ère année (jusqu'à 50%)</li>
    <li>Conditions : demandeurs d'emploi, bénéficiaires RSA, jeunes de 18-25 ans, etc.</li>
    <li>Demande à effectuer au moment de la création ou dans les 45 jours</li>
</ul>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://entreprendre.service-public.gouv.fr/vosdroits/F37396" target="_blank" rel="noopener">Service Public - Entrepreneur Individuel</a></li>
    <li><a href="https://formalites.entreprises.gouv.fr/" target="_blank" rel="noopener">Guichet Unique des Formalités d'Entreprises</a></li>
    <li><a href="https://www.autoentrepreneur.urssaf.fr/" target="_blank" rel="noopener">URSSAF Auto-entrepreneur</a></li>
    <li><a href="https://www.inpi.fr/realiser-demarches/formalites-dentreprises/creer-son-entreprise-individuelle-ei" target="_blank" rel="noopener">INPI - Créer son entreprise individuelle</a></li>
    <li><a href="https://bpifrance-creation.fr/" target="_blank" rel="noopener">Bpifrance Création</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">En résumé</p>
    <p>Créer une micro-entreprise en France est gratuit et rapide (SIRET en 1-2 semaines). Les cotisations sociales varient de 12 à 26% selon l'activité. La franchise de TVA permet de ne pas facturer la TVA sous certains seuils.</p>
</div>
HTML,
        ];
    }

    private function getBelgiqueArticle(): array
    {
        return [
            'title' => 'Créer une entreprise individuelle en Belgique : Guide complet 2025',
            'slug' => 'creer-entreprise-individuelle-belgique-guide-2025',
            'excerpt' => 'Comment devenir indépendant en Belgique : inscription à la BCE via un guichet d\'entreprises, affiliation à une caisse sociale, obligations TVA et cotisations INASTI.',
            'meta_title' => 'Créer une entreprise individuelle en Belgique | Guide 2025',
            'meta_description' => 'Guide complet pour créer une entreprise en personne physique en Belgique : coûts (~200-500€), délai (1-2 semaines), cotisations sociales 20,5%, franchise TVA.',
            'cover_image' => 'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200',
            'content' => <<<'HTML'
<p class="lead">La Belgique offre un cadre favorable aux indépendants avec des démarches simplifiées depuis la suppression des connaissances de gestion de base. Ce guide vous accompagne dans la création de votre entreprise en personne physique.</p>

<h2>Forme juridique : entreprise en personne physique</h2>

<p>L'entreprise en personne physique (indépendant) est la forme la plus simple pour exercer seul une activité économique en Belgique.</p>

<h3>Caractéristiques principales</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Aspect</th>
            <th class="text-left p-2 bg-slate-100">Détail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Acte constitutif</td><td class="p-2 border-b">Aucun requis</td></tr>
        <tr><td class="p-2 border-b">Capital minimum</td><td class="p-2 border-b">Aucun requis</td></tr>
        <tr><td class="p-2 border-b">Responsabilité</td><td class="p-2 border-b"><strong>Illimitée</strong> - patrimoine personnel et professionnel confondus</td></tr>
        <tr><td class="p-2 border-b">Statistiques</td><td class="p-2 border-b">43% des PME belges (510 346 entreprises)</td></tr>
    </tbody>
</table>

<h2>Conditions et prérequis</h2>

<h3>Conditions générales</h3>

<ul>
    <li>Être âgé d'au minimum <strong>18 ans</strong></li>
    <li>Jouir de ses droits civils et politiques</li>
    <li>Être légalement capable</li>
</ul>

<h3>Connaissances de gestion de base : SUPPRIMÉES</h3>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">Bonne nouvelle !</p>
    <p>Les connaissances de gestion de base ont été supprimées dans toutes les régions :</p>
    <ul class="mt-2">
        <li><strong>Flandre :</strong> depuis 2018</li>
        <li><strong>Bruxelles :</strong> depuis le 15 janvier 2024</li>
        <li><strong>Wallonie :</strong> depuis le 1er octobre 2025</li>
    </ul>
</div>

<h3>Accès à la profession</h3>

<p>Certaines professions réglementées nécessitent toujours des <strong>compétences professionnelles spécifiques</strong> : coiffeur, boulanger, pâtissier, garagiste, couvreur, chauffagiste, restaurateur, etc.</p>

<h2>Étapes de création</h2>

<h3>Étape 1 : Ouvrir un compte bancaire professionnel</h3>
<p>Obligatoire pour séparer les opérations professionnelles et privées.</p>

<h3>Étape 2 : S'inscrire à la Banque-Carrefour des Entreprises (BCE)</h3>
<ul>
    <li>Passer par un <strong>guichet d'entreprises agréé</strong></li>
    <li>Obtention du <strong>numéro d'entreprise</strong> (identifiant unique)</li>
    <li>Vérification des compétences professionnelles si nécessaire</li>
</ul>

<h3>Étape 3 : Activer le numéro de TVA</h3>
<ul>
    <li>Auprès de l'Administration générale de la Fiscalité</li>
    <li>Peut être fait via le guichet d'entreprises</li>
    <li>Possibilité de demander le régime de franchise TVA (si CA < 25 000 €)</li>
</ul>

<h3>Étape 4 : S'affilier à une caisse d'assurances sociales</h3>
<p><strong>Obligatoire AVANT le début de l'activité</strong>. Affiliation possible jusqu'à 6 mois avant.</p>

<h3>Étape 5 : S'affilier à une mutualité</h3>
<p>Obligatoire pour bénéficier de l'assurance maladie-invalidité.</p>

<h3>Étape 6 : Souscrire les assurances nécessaires</h3>
<p>Assurance responsabilité civile professionnelle et autres selon l'activité.</p>

<h2>Les 8 guichets d'entreprises agréés</h2>

<ol>
    <li>Liantis (le plus grand)</li>
    <li>Acerta</li>
    <li>Partena Professional</li>
    <li>UCM</li>
    <li>Xerius</li>
    <li>Securex</li>
    <li>Zenito</li>
    <li>Formalis</li>
</ol>

<h2>Coûts de création</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Poste</th>
            <th class="text-left p-2 bg-slate-100">Montant (2025)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscription BCE via guichet</td><td class="p-2 border-b">109 - 111,50 € (exonéré TVA)</td></tr>
        <tr><td class="p-2 border-b">Frais divers</td><td class="p-2 border-b">Variable</td></tr>
        <tr><td class="p-2 border-b font-semibold">Budget total estimé</td><td class="p-2 border-b font-semibold">200 - 500 €</td></tr>
    </tbody>
</table>

<h2>Délais moyens</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Démarche</th>
            <th class="text-left p-2 bg-slate-100">Délai</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Inscription BCE via guichet</td><td class="p-2 border-b">Immédiat à quelques jours</td></tr>
        <tr><td class="p-2 border-b">Activation TVA</td><td class="p-2 border-b">Quelques jours</td></tr>
        <tr><td class="p-2 border-b">Affiliation caisse sociale</td><td class="p-2 border-b">Immédiate</td></tr>
        <tr><td class="p-2 border-b font-semibold">Processus complet</td><td class="p-2 border-b font-semibold">1 à 2 semaines</td></tr>
    </tbody>
</table>

<h2>Obligations après création</h2>

<h3>TVA</h3>

<h4>Régime normal</h4>
<ul>
    <li>Déclaration périodique de TVA (mensuelle ou trimestrielle)</li>
    <li>Facturation avec TVA</li>
    <li>Listing clients annuel</li>
</ul>

<h4>Régime de franchise (si CA < 25 000 €)</h4>
<ul>
    <li>Pas de déclaration périodique</li>
    <li>Pas de TVA à facturer ni à verser</li>
    <li>Communication du CA annuel avant le 31 mars</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mention obligatoire en franchise</p>
    <p>« Petite entreprise assujettie au régime de la franchise de taxe - TVA non applicable (Art. 56bis du Code TVA) »</p>
</div>

<h3>Cotisations sociales (INASTI)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Tranche de revenus</th>
            <th class="text-left p-2 bg-slate-100">Taux 2025</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">0 à 73 447,52 €</td><td class="p-2 border-b font-semibold">20,50%</td></tr>
        <tr><td class="p-2 border-b">73 447,52 à 108 238,40 €</td><td class="p-2 border-b">14,16%</td></tr>
        <tr><td class="p-2 border-b">Au-delà de 108 238,40 €</td><td class="p-2 border-b">Exonéré</td></tr>
    </tbody>
</table>

<p><strong>Cotisation minimale 2025 :</strong> 450,15 €/trimestre (indépendant à titre principal)</p>

<p><strong>Fonctionnement :</strong></p>
<ul>
    <li>Paiement <strong>trimestriel</strong></li>
    <li>Cotisations d'abord <strong>provisoires</strong> (basées sur revenus N-3)</li>
    <li>Régularisation une fois les revenus définitifs connus</li>
</ul>

<h3>Obligations comptables</h3>

<h4>Comptabilité simplifiée (CA < 500 000 €)</h4>
<p>3 journaux obligatoires :</p>
<ol>
    <li><strong>Journal d'achats :</strong> liste des dépenses</li>
    <li><strong>Journal de ventes :</strong> aperçu chronologique des factures</li>
    <li><strong>Journal de trésorerie :</strong> livre de caisse + livre de banque</li>
</ol>

<p><strong>Conservation des documents :</strong> 7 ans</p>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://economie.fgov.be/fr/themes/entreprises/creer-une-entreprise/demarches-pour-un-travailleur" target="_blank" rel="noopener">SPF Économie - Démarches pour un travailleur indépendant</a></li>
    <li><a href="https://1819.brussels/" target="_blank" rel="noopener">1819.brussels - Hub pour entrepreneurs</a></li>
    <li><a href="https://www.inasti.be/fr/faq/combien-de-cotisations-sociales-dois-je-payer" target="_blank" rel="noopener">INASTI - Cotisations sociales</a></li>
    <li><a href="https://finances.belgium.be/fr/entreprises/tva/assujettissement-tva/regime-franchise-taxe" target="_blank" rel="noopener">SPF Finances - Régime de franchise TVA</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">En résumé</p>
    <p>Devenir indépendant en Belgique coûte entre 200 et 500 € et prend 1 à 2 semaines. Les cotisations sociales représentent 20,5% du revenu. La franchise TVA est possible si le CA reste sous 25 000 €/an.</p>
</div>
HTML,
        ];
    }

    private function getAllemagneArticle(): array
    {
        return [
            'title' => 'Créer une entreprise individuelle en Allemagne : Guide complet 2025',
            'slug' => 'creer-entreprise-individuelle-allemagne-guide-2025',
            'excerpt' => 'Tout savoir pour créer votre Einzelunternehmen ou devenir Freiberufler en Allemagne : Gewerbeanmeldung, Finanzamt, Kleinunternehmerregelung et obligations fiscales.',
            'meta_title' => 'Créer une entreprise individuelle en Allemagne | Guide 2025',
            'meta_description' => 'Guide complet pour créer une entreprise individuelle en Allemagne : Gewerbeanmeldung (15-60€), délai 1-3 jours, Kleinunternehmerregelung, obligations IHK.',
            'cover_image' => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=1200',
            'content' => <<<'HTML'
<p class="lead">L'Allemagne offre plusieurs options pour créer une entreprise individuelle, avec des démarches relativement simples et rapides. Ce guide vous présente les différentes formes juridiques et les étapes pour vous lancer.</p>

<h2>Les formes juridiques pour entreprise individuelle</h2>

<h3>Einzelunternehmen (Entreprise Individuelle)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Caractéristique</th>
            <th class="text-left p-2 bg-slate-100">Détail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Définition</td><td class="p-2 border-b">Entreprise gérée par une seule personne</td></tr>
        <tr><td class="p-2 border-b">Capital minimum</td><td class="p-2 border-b">Aucun requis</td></tr>
        <tr><td class="p-2 border-b">Responsabilité</td><td class="p-2 border-b"><strong>Illimitée</strong></td></tr>
        <tr><td class="p-2 border-b">Création</td><td class="p-2 border-b">Gewerbeanmeldung + numéro fiscal</td></tr>
        <tr><td class="p-2 border-b">Imposition</td><td class="p-2 border-b">Impôt sur le revenu + Gewerbesteuer (si > 24 500 €/an)</td></tr>
    </tbody>
</table>

<p><strong>Sous-catégories :</strong></p>
<ul>
    <li><strong>Kleingewerbetreibender :</strong> Petit commerçant, pas d'inscription au registre du commerce</li>
    <li><strong>Eingetragener Kaufmann (e.K.) :</strong> Inscrit au registre du commerce</li>
</ul>

<h3>Freiberufler (Profession Libérale)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Caractéristique</th>
            <th class="text-left p-2 bg-slate-100">Détail</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Définition</td><td class="p-2 border-b">Activité intellectuelle, créative, scientifique ou éducative</td></tr>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b text-green-600">NON requise</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b text-green-600">NON applicable</td></tr>
        <tr><td class="p-2 border-b">IHK/HWK</td><td class="p-2 border-b text-green-600">Pas de cotisation obligatoire</td></tr>
        <tr><td class="p-2 border-b">Inscription</td><td class="p-2 border-b">Directement au Finanzamt</td></tr>
    </tbody>
</table>

<p><strong>Professions concernées (Katalogberufe) :</strong> médecins, avocats, architectes, ingénieurs, journalistes, traducteurs, artistes, enseignants...</p>

<h2>Conditions et prérequis</h2>

<h3>Pour les Gewerbetreibende</h3>

<ul>
    <li><strong>Âge minimum :</strong> 18 ans (majorité)</li>
    <li><strong>Résidence :</strong> Adresse en Allemagne</li>
    <li><strong>Documents :</strong> Passeport ou carte d'identité</li>
    <li><strong>Activité légale :</strong> Activité autorisée par la loi</li>
</ul>

<h3>Documents supplémentaires possibles</h3>

<ul>
    <li><strong>Führungszeugnis</strong> (extrait casier judiciaire) : ~13 €</li>
    <li><strong>Gewerbezentralregisterauszug :</strong> ~13 €</li>
    <li><strong>Carte d'artisan :</strong> 80-250 €</li>
</ul>

<h2>Étapes de création</h2>

<h3>Parcours A : Gewerbetreibender</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Étape 1 : Gewerbeanmeldung (Gewerbeamt)<br>
        ↓<br>
        Étape 2 : Notifications automatiques (Finanzamt, IHK/HWK, Berufsgenossenschaft)<br>
        ↓<br>
        Étape 3 : Fragebogen zur steuerlichen Erfassung (Finanzamt)<br>
        ↓<br>
        Étape 4 : Attribution Steuernummer<br>
        ↓<br>
        Étape 5 : Inscription Berufsgenossenschaft (7 jours)
    </p>
</div>

<h4>Gewerbeanmeldung</h4>
<ul>
    <li><strong>Où :</strong> Gewerbeamt de la commune du siège</li>
    <li><strong>Formulaire :</strong> GewA 1</li>
    <li><strong>Mode :</strong> En ligne (Gewerbe-Service-Portal) ou sur place</li>
    <li><strong>Délai :</strong> 1-3 jours</li>
</ul>

<h3>Parcours B : Freiberufler</h3>

<div class="bg-slate-50 p-4 rounded-lg my-4">
    <p class="font-mono text-sm">
        Étape 1 : Inscription au Finanzamt (dans les 4 semaines suivant le début)<br>
        ↓<br>
        Étape 2 : Fragebogen zur steuerlichen Erfassung<br>
        ↓<br>
        Étape 3 : Attribution Steuernummer
    </p>
</div>

<h2>Coûts de création</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Poste</th>
            <th class="text-left p-2 bg-slate-100">Montant</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung (base)</td><td class="p-2 border-b">12,50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Grandes villes (Munich, Stuttgart)</td><td class="p-2 border-b">50 - 60 €</td></tr>
        <tr><td class="p-2 border-b">Petites communes</td><td class="p-2 border-b">15 - 30 €</td></tr>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600 font-semibold">0 € (gratuit)</td></tr>
    </tbody>
</table>

<h2>Délais moyens</h2>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Étape</th>
            <th class="text-left p-2 bg-slate-100">Délai</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Traitement Gewerbeanmeldung</td><td class="p-2 border-b font-semibold">1-3 jours</td></tr>
        <tr><td class="p-2 border-b">Confirmation écrite Gewerbeamt</td><td class="p-2 border-b">3 jours maximum</td></tr>
        <tr><td class="p-2 border-b">Réception Fragebogen Finanzamt</td><td class="p-2 border-b">4-6 semaines</td></tr>
        <tr><td class="p-2 border-b">Attribution Steuernummer</td><td class="p-2 border-b">2-4 semaines</td></tr>
        <tr><td class="p-2 border-b font-semibold">Délai total</td><td class="p-2 border-b font-semibold">6-10 semaines</td></tr>
    </tbody>
</table>

<h2>Obligations après création</h2>

<h3>TVA / Umsatzsteuer</h3>

<h4>Régime normal</h4>
<ul>
    <li><strong>Taux standard :</strong> 19%</li>
    <li><strong>Taux réduit :</strong> 7%</li>
    <li>Déclaration mensuelle ou trimestrielle (Umsatzsteuer-Voranmeldung)</li>
</ul>

<h4>Kleinunternehmerregelung (§ 19 UStG)</h4>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Critère</th>
            <th class="text-left p-2 bg-slate-100">Seuil 2026</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">CA année précédente</td><td class="p-2 border-b">≤ 25 000 €</td></tr>
        <tr><td class="p-2 border-b">CA année en cours</td><td class="p-2 border-b">≤ 100 000 €</td></tr>
    </tbody>
</table>

<p><strong>Avantages :</strong></p>
<ul>
    <li>Pas de facturation de TVA</li>
    <li>Pas de déclarations de TVA</li>
    <li>Comptabilité simplifiée</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Mention obligatoire sur factures</p>
    <p>« Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG »<br>
    <em>(Pas de TVA indiquée, régime des petits entrepreneurs selon § 19 UStG)</em></p>
</div>

<h3>Gewerbesteuer (Taxe Professionnelle)</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Freiberufler</td><td class="p-2 border-b text-green-600">Exonéré</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender < 24 500 €/an</td><td class="p-2 border-b text-green-600">Exonéré (Freibetrag)</td></tr>
        <tr><td class="p-2 border-b">Gewerbetreibender ≥ 24 500 €/an</td><td class="p-2 border-b">Assujetti</td></tr>
    </tbody>
</table>

<h3>Cotisations sociales</h3>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Type</th>
            <th class="text-left p-2 bg-slate-100">Obligation</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Krankenversicherung (maladie)</td><td class="p-2 border-b text-red-600 font-semibold">OBLIGATOIRE</td></tr>
        <tr><td class="p-2 border-b">Pflegeversicherung (dépendance)</td><td class="p-2 border-b text-red-600 font-semibold">OBLIGATOIRE</td></tr>
        <tr><td class="p-2 border-b">Rentenversicherung (retraite)</td><td class="p-2 border-b">Facultative*</td></tr>
        <tr><td class="p-2 border-b">Arbeitslosenversicherung (chômage)</td><td class="p-2 border-b">Facultative</td></tr>
    </tbody>
</table>

<p><small>*Obligatoire pour certaines professions (artisans, enseignants, soignants)</small></p>

<h3>Cotisation IHK/HWK</h3>

<ul>
    <li>Adhésion automatique et obligatoire pour Gewerbetreibende</li>
    <li>Exonération si Gewerbeertrag < 5 200 €/an</li>
    <li>Cotisation progressive au-delà</li>
</ul>

<h2>Tableau comparatif</h2>

<table class="w-full my-4 text-sm">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Critère</th>
            <th class="text-left p-2 bg-slate-100">Einzelunternehmen</th>
            <th class="text-left p-2 bg-slate-100">Freiberufler</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Gewerbeanmeldung</td><td class="p-2 border-b">Oui</td><td class="p-2 border-b text-green-600">Non</td></tr>
        <tr><td class="p-2 border-b">Gewerbesteuer</td><td class="p-2 border-b">Oui (> 24 500 €)</td><td class="p-2 border-b text-green-600">Non</td></tr>
        <tr><td class="p-2 border-b">IHK-Mitgliedschaft</td><td class="p-2 border-b">Obligatoire</td><td class="p-2 border-b text-green-600">Non</td></tr>
        <tr><td class="p-2 border-b">Coût création</td><td class="p-2 border-b">12,50-60 €</td><td class="p-2 border-b text-green-600">0 €</td></tr>
        <tr><td class="p-2 border-b">Délai création</td><td class="p-2 border-b">1-3 jours</td><td class="p-2 border-b text-green-600">Immédiat</td></tr>
    </tbody>
</table>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://www.existenzgruendungsportal.de/" target="_blank" rel="noopener">Existenzgründungsportal (BMWK)</a></li>
    <li><a href="https://www.bmwk.de/" target="_blank" rel="noopener">Bundesministerium für Wirtschaft (BMWK)</a></li>
    <li><a href="https://www.ihk.de/" target="_blank" rel="noopener">IHK - Industrie- und Handelskammer</a></li>
    <li><a href="https://www.deutsche-rentenversicherung.de/" target="_blank" rel="noopener">Deutsche Rentenversicherung</a></li>
    <li><a href="https://gruenderplattform.de/" target="_blank" rel="noopener">Gründerplattform</a></li>
</ul>

<div class="bg-green-50 border-l-4 border-green-500 p-4 my-6">
    <p class="font-semibold">En résumé</p>
    <p>Créer une entreprise individuelle en Allemagne coûte entre 0 et 60 € selon le statut. La Gewerbeanmeldung est traitée en 1-3 jours. Le régime Kleinunternehmerregelung permet d'être exonéré de TVA sous certains seuils. Les Freiberufler bénéficient d'un régime simplifié sans Gewerbesteuer ni cotisation IHK.</p>
</div>
HTML,
        ];
    }
}
