<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogSeoArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->first()?->id ?? 1;

        // Catégories: 1=Guides pratiques, 2=Réglementation, 3=Freelances
        // Tags: 1=FAIA, 2=TVA, 3=Facturation, 4=Luxembourg, 5=Freelance, 6=Conformité, 7=PME, 8=Obligations légales, 9=Comptabilité

        $articles = $this->getArticles();

        foreach ($articles as $article) {
            // Skip si slug existe déjà
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command->info("Skip: {$article['slug']} (existe déjà)");
                continue;
            }

            $postId = DB::table('blog_posts')->insertGetId([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'locale' => 'fr',
                'status' => 'published',
                'published_at' => now(),
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attach tags
            foreach ($article['tags'] as $tagId) {
                DB::table('blog_post_tag')->insert([
                    'blog_post_id' => $postId,
                    'blog_tag_id' => $tagId,
                ]);
            }

            $this->command->info("Créé: {$article['title']}");
        }
    }

    private function getArticles(): array
    {
        return [
            // Article 1 : Peppol
            [
                'title' => 'Peppol B2G au Luxembourg : guide complet 2026',
                'slug' => 'peppol-b2g-luxembourg-guide-complet-2026',
                'meta_title' => 'Peppol B2G Luxembourg : Guide Complet 2026 | Facturation Electronique',
                'meta_description' => 'Tout savoir sur Peppol B2G au Luxembourg : obligations, fonctionnement, comment envoyer vos factures electroniques au secteur public. Guide pratique 2026.',
                'excerpt' => 'Peppol est le standard europeen de facturation electronique pour le secteur public. Decouvrez comment envoyer vos factures B2G au Luxembourg via le reseau Peppol.',
                'category_id' => 2, // Réglementation
                'tags' => [3, 4, 6, 8], // Facturation, Luxembourg, Conformité, Obligations légales
                'content' => <<<'HTML'
<p class="lead">Depuis 2022, la facturation electronique via <strong>Peppol</strong> est devenue une obligation pour les fournisseurs du secteur public au Luxembourg. Ce guide vous explique tout ce que vous devez savoir pour vous mettre en conformite.</p>

<h2>Qu'est-ce que Peppol ?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> est un reseau international de facturation electronique qui permet d'envoyer des documents commerciaux (factures, bons de commande) de maniere standardisee entre entreprises et administrations publiques.</p>

<p>Au Luxembourg, Peppol est le canal officiel pour la facturation electronique B2G (Business-to-Government). Cela signifie que toute entreprise qui facture l'Etat, les communes ou les etablissements publics doit utiliser ce format.</p>

<h2>Qui est concerne ?</h2>

<p>Si vous etes fournisseur de l'une des entites suivantes, vous devez facturer via Peppol :</p>

<ul>
    <li><strong>L'Etat luxembourgeois</strong> et ses ministeres</li>
    <li><strong>Les communes</strong> luxembourgeoises</li>
    <li><strong>Les etablissements publics</strong> (hopitaux, ecoles, etc.)</li>
    <li><strong>Les marches publics</strong> de plus de 30 000 EUR</li>
</ul>

<p>Les entreprises qui ne facturent qu'a des clients prives (B2B ou B2C) ne sont pas encore concernees par cette obligation, mais l'Union europeenne pousse vers une generalisation a partir de 2028.</p>

<h2>Comment fonctionne Peppol ?</h2>

<p>Le reseau Peppol fonctionne sur un modele de "quatre coins" :</p>

<ol>
    <li><strong>L'expediteur</strong> (votre entreprise) cree la facture</li>
    <li><strong>Le point d'acces expediteur</strong> (votre logiciel de facturation) envoie la facture sur le reseau Peppol</li>
    <li><strong>Le point d'acces destinataire</strong> (cote administration) recoit la facture</li>
    <li><strong>Le destinataire</strong> (l'administration publique) traite la facture</li>
</ol>

<p>Chaque participant au reseau est identifie par un <strong>Peppol ID</strong> unique. Au Luxembourg, ce numero est generalement base sur le numero de TVA (schema 0184).</p>

<h2>Le format UBL</h2>

<p>Les factures Peppol utilisent le format <strong>UBL (Universal Business Language)</strong>, un standard XML international. Votre facture doit contenir :</p>

<ul>
    <li>Les informations de l'expediteur (nom, adresse, TVA)</li>
    <li>Les informations du destinataire (nom, Peppol ID)</li>
    <li>Les lignes de facturation (description, quantite, prix unitaire)</li>
    <li>Les montants TVA ventiles par taux</li>
    <li>Les totaux (HT, TVA, TTC)</li>
    <li>Les references de commande ou de contrat</li>
</ul>

<h2>Comment envoyer une facture Peppol depuis faktur.lu ?</h2>

<p>Avec <strong>faktur.lu</strong>, l'envoi de factures Peppol est integre directement dans le logiciel :</p>

<ol>
    <li>Creez votre facture normalement</li>
    <li>Assurez-vous que le Peppol ID du client est renseigne</li>
    <li>Cliquez sur <strong>"Envoyer via Peppol"</strong></li>
    <li>La facture est convertie en UBL et transmise via le reseau Peppol</li>
    <li>Vous recevez une confirmation de reception</li>
</ol>

<p>Le plan Essentiel inclut 10 envois Peppol par mois, et le plan Pro offre des envois illimites.</p>

<h2>Avantages de la facturation Peppol</h2>

<ul>
    <li><strong>Traitement accelere</strong> : les factures Peppol sont traitees automatiquement, reduisant les delais de paiement</li>
    <li><strong>Reduction des erreurs</strong> : le format structure elimine les erreurs de saisie manuelle</li>
    <li><strong>Tracabilite</strong> : chaque facture est tracee de bout en bout sur le reseau</li>
    <li><strong>Conformite</strong> : vous respectez les obligations legales luxembourgeoises</li>
    <li><strong>Interoperabilite</strong> : Peppol est reconnu dans plus de 35 pays</li>
</ul>

<h2>Questions frequentes</h2>

<h3>La facturation Peppol est-elle obligatoire pour le B2B ?</h3>

<p>Pas encore au Luxembourg. Cependant, la directive europeenne ViDA prevoit une generalisation de la facturation electronique pour les transactions intracommunautaires a partir de 2028-2030.</p>

<h3>Combien coute l'envoi via Peppol ?</h3>

<p>Avec faktur.lu, l'envoi Peppol est inclus dans votre abonnement. Le plan Essentiel comprend 10 envois/mois et le plan Pro offre des envois illimites.</p>

<h3>Comment trouver le Peppol ID d'une administration ?</h3>

<p>Les Peppol ID des administrations luxembourgeoises sont disponibles sur le <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Vous pouvez aussi les rechercher directement dans faktur.lu lors de la creation du client.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Pret a facturer via Peppol ?</h3>
    <p class="text-primary-800 mb-4">faktur.lu integre nativement la transmission Peppol. Creez votre compte gratuit et envoyez votre premiere facture electronique en quelques minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 2 : Livre des recettes
            [
                'title' => 'Livre des recettes au Luxembourg : obligations et modele',
                'slug' => 'livre-des-recettes-luxembourg-obligations-modele',
                'meta_title' => 'Livre des Recettes Luxembourg : Obligations et Modele | Guide 2026',
                'meta_description' => 'Le livre des recettes est obligatoire au Luxembourg. Decouvrez les mentions requises, le format legal et comment le generer automatiquement avec faktur.lu.',
                'excerpt' => 'Le livre des recettes est une obligation comptable au Luxembourg. Apprenez ce qu il doit contenir et comment le tenir a jour facilement.',
                'category_id' => 2,
                'tags' => [4, 6, 8, 9], // Luxembourg, Conformité, Obligations légales, Comptabilité
                'content' => <<<'HTML'
<p class="lead">Le <strong>livre des recettes</strong> est un document comptable obligatoire au Luxembourg pour tous les contribuables soumis a la TVA. Il recense l'ensemble des recettes encaissees au cours d'un exercice fiscal. Voici tout ce que vous devez savoir.</p>

<h2>Qu'est-ce que le livre des recettes ?</h2>

<p>Le livre des recettes est un registre chronologique qui repertorie toutes les factures emises et les paiements recus par votre entreprise. Il fait partie des <strong>obligations comptables</strong> definies par la loi fiscale luxembourgeoise.</p>

<p>Contrairement au grand livre comptable, le livre des recettes est un document simplifie, principalement destine aux <strong>independants, freelances et petites entreprises</strong> qui ne tiennent pas une comptabilite en partie double.</p>

<h2>Qui doit tenir un livre des recettes ?</h2>

<ul>
    <li><strong>Tous les assujettis a la TVA</strong> au Luxembourg</li>
    <li><strong>Les independants et professions liberales</strong></li>
    <li><strong>Les auto-entrepreneurs</strong> meme en franchise de TVA (en dessous du seuil de 35 000 EUR)</li>
    <li><strong>Les societes</strong> (en complement de leur comptabilite)</li>
</ul>

<h2>Que doit contenir le livre des recettes ?</h2>

<p>Chaque entree du livre des recettes doit mentionner :</p>

<ul>
    <li><strong>La date</strong> de la facture ou du paiement</li>
    <li><strong>Le numero de facture</strong> (numerotation sequentielle)</li>
    <li><strong>Le nom du client</strong></li>
    <li><strong>La description</strong> de la prestation ou du bien vendu</li>
    <li><strong>Le montant hors taxes (HT)</strong></li>
    <li><strong>Le taux de TVA applique</strong> (17%, 14%, 8% ou 3%)</li>
    <li><strong>Le montant de la TVA</strong></li>
    <li><strong>Le montant toutes taxes comprises (TTC)</strong></li>
</ul>

<h2>Format et conservation</h2>

<p>Le livre des recettes peut etre tenu :</p>

<ul>
    <li><strong>Au format papier</strong> : dans un cahier dedie, sans ratures ni blancs</li>
    <li><strong>Au format numerique</strong> : via un logiciel de facturation, un tableur ou un fichier PDF</li>
</ul>

<p>Important : le livre des recettes doit etre conserve pendant <strong>10 ans</strong> a compter de la fin de l'exercice fiscal, conformement a l'article 60 de la loi generale des impots (Abgabenordnung).</p>

<h2>Generer son livre des recettes avec faktur.lu</h2>

<p>Avec <strong>faktur.lu</strong>, votre livre des recettes est genere automatiquement a partir de vos factures :</p>

<ol>
    <li>Allez dans <strong>Comptabilite > Livre des recettes</strong></li>
    <li>Selectionnez la periode souhaitee (mois, trimestre, annee)</li>
    <li>Consultez le detail de chaque facture avec ventilation TVA</li>
    <li>Exportez en <strong>PDF</strong> ou <strong>CSV</strong> pour votre comptable</li>
</ol>

<p>Toutes les mentions obligatoires sont incluses automatiquement, et le livre est conforme aux exigences de l'<strong>Administration des contributions directes (ACD)</strong>.</p>

<h2>Livre des recettes vs export FAIA</h2>

<p>Ne confondez pas le livre des recettes avec l'export FAIA :</p>

<ul>
    <li>Le <strong>livre des recettes</strong> est un document de suivi courant, utilise au quotidien et transmis a votre comptable</li>
    <li>Le <strong>FAIA</strong> est un fichier XML exige uniquement en cas de controle fiscal par l'AED (Administration de l'enregistrement, des domaines et de la TVA)</li>
</ul>

<p>Les deux sont generes automatiquement par faktur.lu.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Generez votre livre des recettes en 1 clic</h3>
    <p class="text-primary-800 mb-4">faktur.lu genere automatiquement votre livre des recettes conforme a la legislation luxembourgeoise, avec export PDF et CSV.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 3 : Archivage factures
            [
                'title' => 'Archivage des factures au Luxembourg : duree legale et format',
                'slug' => 'archivage-factures-luxembourg-duree-legale-format',
                'meta_title' => 'Archivage Factures Luxembourg : Duree Legale et Format | Guide',
                'meta_description' => 'Combien de temps conserver vos factures au Luxembourg ? Decouvrez la duree legale (10 ans), les formats acceptes et comment archiver en PDF/A.',
                'excerpt' => 'Au Luxembourg, les factures doivent etre conservees pendant 10 ans. Decouvrez les regles d archivage, les formats acceptes et les bonnes pratiques.',
                'category_id' => 2,
                'tags' => [3, 4, 6, 8],
                'content' => <<<'HTML'
<p class="lead">L'archivage des factures est une obligation legale au Luxembourg. Toute entreprise doit conserver ses documents comptables pendant une duree minimale de <strong>10 ans</strong>. Voici les regles a connaitre pour etre en conformite.</p>

<h2>Duree legale de conservation</h2>

<p>Selon l'article 60 de l'Abgabenordnung (AO) et le Code de commerce luxembourgeois, les documents comptables doivent etre conserves pendant :</p>

<ul>
    <li><strong>10 ans</strong> pour les factures (emises et recues)</li>
    <li><strong>10 ans</strong> pour les livres de comptabilite</li>
    <li><strong>10 ans</strong> pour les pieces justificatives</li>
    <li><strong>10 ans</strong> pour la correspondance commerciale</li>
</ul>

<p>Le delai court a partir de la <strong>fin de l'exercice fiscal</strong> auquel se rapporte le document. Une facture emise le 15 mars 2026 devra donc etre conservee jusqu'au 31 decembre 2036.</p>

<h2>Formats d'archivage acceptes</h2>

<p>L'administration fiscale luxembourgeoise accepte deux types d'archivage :</p>

<h3>Archivage papier</h3>
<p>Les originaux papier doivent etre conserves dans un endroit sec et accessible. Les factures ne doivent pas etre alterees.</p>

<h3>Archivage numerique</h3>
<p>L'archivage electronique est autorise sous certaines conditions :</p>

<ul>
    <li>Le format doit garantir l'<strong>integrite</strong> du document (pas de modification possible)</li>
    <li>Le document doit etre <strong>lisible</strong> pendant toute la duree de conservation</li>
    <li>Le format <strong>PDF/A</strong> (ISO 19005) est recommande car il garantit la perennite</li>
    <li>Une <strong>empreinte numerique</strong> (hash) peut prouver que le document n'a pas ete modifie</li>
</ul>

<h2>Pourquoi le format PDF/A ?</h2>

<p>Le <strong>PDF/A</strong> est une norme ISO specifiquement concue pour l'archivage a long terme. Contrairement a un PDF standard :</p>

<ul>
    <li>Il <strong>embarque toutes les polices</strong> utilisees (pas de dependance externe)</li>
    <li>Il <strong>interdit le JavaScript</strong> et les elements multimedia</li>
    <li>Il garantit que le document sera <strong>lisible dans 10, 20 ou 50 ans</strong></li>
    <li>Il est <strong>reconnu par les administrations fiscales</strong> europeennes</li>
</ul>

<h2>Archiver ses factures avec faktur.lu</h2>

<p>Le plan Pro de faktur.lu inclut l'<strong>archivage automatique en PDF/A</strong> :</p>

<ol>
    <li>Chaque facture finalisee est automatiquement archivee en PDF/A</li>
    <li>Une empreinte numerique (checksum SHA-256) est calculee</li>
    <li>Les archives sont conservees de maniere securisee pendant 10 ans</li>
    <li>Vous pouvez telecharger vos archives a tout moment</li>
</ol>

<h2>Risques en cas de non-conformite</h2>

<p>En cas de controle fiscal, l'absence de factures ou un archivage non conforme peut entrainer :</p>

<ul>
    <li>Le <strong>rejet des deductions de TVA</strong> sur les factures manquantes</li>
    <li>Des <strong>penalites financieres</strong></li>
    <li>Une <strong>estimation d'office</strong> du chiffre d'affaires par l'administration</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Archivage automatique avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">Ne vous souciez plus de l'archivage. faktur.lu archive automatiquement vos factures en PDF/A avec empreinte numerique, conforme pendant 10 ans.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 4 : Relancer un client
            [
                'title' => 'Comment relancer un client qui ne paie pas au Luxembourg',
                'slug' => 'relancer-client-impaye-luxembourg',
                'meta_title' => 'Relancer un Client Impaye au Luxembourg : Guide Pratique',
                'meta_description' => 'Comment relancer un client qui ne paie pas ? Decouvrez les etapes, modeles d email et cadre legal des relances de paiement au Luxembourg.',
                'excerpt' => 'Un client ne paie pas ? Decouvrez comment gerer les factures impayees au Luxembourg : relances amiables, mises en demeure et recours legaux.',
                'category_id' => 1, // Guides pratiques
                'tags' => [3, 4, 7], // Facturation, Luxembourg, PME
                'content' => <<<'HTML'
<p class="lead">Les factures impayees sont le cauchemar de tout entrepreneur. Au Luxembourg, <strong>30% des factures sont payees en retard</strong>. Voici comment gerer les relances efficacement, du rappel amical a la mise en demeure.</p>

<h2>Etape 1 : Le rappel amical (J+7 apres echeance)</h2>

<p>Un simple oubli ? C'est souvent le cas. Le premier rappel doit etre <strong>courtois et professionnel</strong> :</p>

<ul>
    <li>Envoyez un email rappelant le numero et le montant de la facture</li>
    <li>Joignez une copie de la facture originale</li>
    <li>Proposez un nouveau delai de paiement si necessaire</li>
    <li>Restez factuel et cordial</li>
</ul>

<p><strong>Conseil :</strong> avec faktur.lu, vous pouvez automatiser ce premier rappel. Le systeme detecte les factures en retard et envoie un email de relance automatique.</p>

<h2>Etape 2 : La relance formelle (J+15)</h2>

<p>Si le premier rappel reste sans reponse, envoyez une <strong>relance plus formelle</strong> :</p>

<ul>
    <li>Mentionnez clairement le retard de paiement</li>
    <li>Rappelez les conditions de paiement convenues</li>
    <li>Indiquez que des interets de retard pourront etre appliques</li>
    <li>Fixez un delai precis (ex: 8 jours)</li>
</ul>

<h2>Etape 3 : La mise en demeure (J+30)</h2>

<p>La mise en demeure est un document formel qui constitue une <strong>preuve juridique</strong>. Elle doit etre envoyee par <strong>lettre recommandee avec accuse de reception</strong> et contenir :</p>

<ul>
    <li>La mention <strong>"Mise en demeure"</strong> en objet</li>
    <li>Le detail des factures impayees (numeros, montants, dates)</li>
    <li>Le montant total du (principal + interets de retard eventuels)</li>
    <li>Un <strong>delai de 8 jours</strong> pour regulariser</li>
    <li>La mention que vous vous reservez le droit d'engager des poursuites</li>
</ul>

<h2>Les interets de retard au Luxembourg</h2>

<p>Au Luxembourg, les interets de retard sont regis par la <strong>loi du 18 avril 2004</strong> sur les delais de paiement :</p>

<ul>
    <li><strong>Transactions B2B</strong> : le taux d'interet de retard est le taux de la BCE + 8 points (soit environ 12% en 2026)</li>
    <li><strong>Transactions B2G</strong> : meme taux, mais delai de paiement maximum de 30 jours</li>
    <li><strong>Indemnite forfaitaire</strong> : 40 EUR pour frais de recouvrement (sans justificatif)</li>
</ul>

<h2>Etape 4 : Le recouvrement (J+60)</h2>

<p>Si toutes les relances echouent, plusieurs options s'offrent a vous :</p>

<ul>
    <li><strong>Societe de recouvrement</strong> : elle se charge des demarches moyennant une commission (15-25%)</li>
    <li><strong>Injonction de payer</strong> : procedure simplifiee devant le juge de paix (pour les creances < 15 000 EUR)</li>
    <li><strong>Assignation au tribunal</strong> : pour les montants plus importants</li>
    <li><strong>Mediation commerciale</strong> : solution alternative, plus rapide et moins couteuse</li>
</ul>

<h2>Bonnes pratiques pour eviter les impayes</h2>

<ul>
    <li><strong>Facturez rapidement</strong> : plus vous attendez, plus le risque d'impaye augmente</li>
    <li><strong>Conditions claires</strong> : mentionnez les delais et modalites de paiement sur chaque facture</li>
    <li><strong>Acompte</strong> : demandez un acompte de 30-50% pour les grosses prestations</li>
    <li><strong>Relances automatiques</strong> : utilisez un logiciel comme faktur.lu pour ne jamais oublier de relancer</li>
    <li><strong>Verifiez la solvabilite</strong> : pour les nouveaux clients, consultez le RCS Luxembourg</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisez vos relances avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu detecte automatiquement les factures en retard et envoie des relances par email. Ne laissez plus aucune facture impayee tomber dans l'oubli.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 5 : Délais de paiement
            [
                'title' => 'Delais de paiement au Luxembourg : cadre legal 2026',
                'slug' => 'delais-paiement-luxembourg-cadre-legal-2026',
                'meta_title' => 'Delais de Paiement Luxembourg : Cadre Legal 2026 | Guide',
                'meta_description' => 'Quels sont les delais de paiement legaux au Luxembourg ? B2B, B2G, interets de retard. Tout le cadre legal explique simplement.',
                'excerpt' => 'Decouvrez les delais de paiement legaux au Luxembourg en 2026 : regles B2B et B2G, interets de retard, indemnites et recours en cas de retard.',
                'category_id' => 2,
                'tags' => [3, 4, 7, 8],
                'content' => <<<'HTML'
<p class="lead">Au Luxembourg, les delais de paiement sont encadres par la loi. Que vous facturiez a une entreprise ou a une administration, voici les regles a connaitre pour proteger votre tresorerie.</p>

<h2>Delais legaux de paiement</h2>

<h3>Transactions B2B (entre entreprises)</h3>

<p>La <strong>loi du 18 avril 2004</strong> relative aux delais de paiement dans les transactions commerciales fixe les regles suivantes :</p>

<ul>
    <li><strong>Delai par defaut</strong> : 30 jours a compter de la reception de la facture</li>
    <li><strong>Delai maximum contractuel</strong> : 60 jours (sauf accord specifique)</li>
    <li>Les parties peuvent convenir d'un delai <strong>superieur a 60 jours</strong> uniquement si cela ne constitue pas un abus manifeste</li>
</ul>

<h3>Transactions B2G (avec le secteur public)</h3>

<p>Les delais sont plus stricts pour les administrations :</p>

<ul>
    <li><strong>Delai maximum</strong> : 30 jours a compter de la reception de la facture</li>
    <li>Ce delai <strong>ne peut pas etre allonge</strong> contractuellement</li>
    <li>La procedure de verification ne peut exceder <strong>30 jours supplementaires</strong></li>
</ul>

<h2>A partir de quand le delai court-il ?</h2>

<p>Le delai de paiement commence a courir a partir de :</p>

<ul>
    <li>La <strong>date de reception de la facture</strong> par le debiteur</li>
    <li>Ou la <strong>date de reception des biens/services</strong>, si la facture a ete envoyee avant</li>
    <li>Ou la <strong>date de verification</strong>, si un processus de verification est prevu contractuellement</li>
</ul>

<p><strong>Conseil :</strong> mentionnez toujours la <strong>date d'echeance</strong> clairement sur votre facture. Avec faktur.lu, la date d'echeance est calculee automatiquement (30 jours par defaut, personnalisable).</p>

<h2>Interets de retard</h2>

<p>En cas de retard de paiement, des interets sont dus <strong>automatiquement</strong>, sans qu'il soit necessaire d'envoyer une mise en demeure :</p>

<ul>
    <li><strong>Taux</strong> : taux directeur de la BCE + 8 points de pourcentage</li>
    <li><strong>Taux applicable en 2026</strong> : environ 12,5% annuel</li>
    <li>Les interets courent a partir du jour suivant la date d'echeance</li>
</ul>

<h2>Indemnite forfaitaire de recouvrement</h2>

<p>En plus des interets de retard, le creancier a droit a une <strong>indemnite forfaitaire de 40 EUR</strong> pour frais de recouvrement. Cette indemnite est due automatiquement, sans justificatif de depenses.</p>

<p>Si les frais de recouvrement reels depassent 40 EUR, le creancier peut reclamer le montant effectif sur presentation de justificatifs.</p>

<h2>Bonnes pratiques sur vos factures</h2>

<p>Pour vous proteger en cas de litige, mentionnez sur chaque facture :</p>

<ul>
    <li>La <strong>date d'echeance</strong> precise (pas seulement "30 jours")</li>
    <li>Les <strong>modalites de paiement</strong> (virement, avec IBAN)</li>
    <li>La mention des <strong>interets de retard</strong> applicables</li>
    <li>La mention de l'<strong>indemnite forfaitaire de 40 EUR</strong></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gerez vos delais de paiement avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu calcule automatiquement les dates d'echeance, detecte les retards et envoie des relances. Gardez le controle de votre tresorerie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 6 : Note de crédit
            [
                'title' => 'Note de credit au Luxembourg : comment l etablir correctement',
                'slug' => 'note-de-credit-luxembourg-comment-etablir',
                'meta_title' => 'Note de Credit Luxembourg : Comment l Etablir Correctement',
                'meta_description' => 'Comment creer une note de credit (avoir) au Luxembourg ? Mentions obligatoires, cas d utilisation, impact TVA. Guide pratique avec exemples.',
                'excerpt' => 'La note de credit (avoir) permet de corriger ou annuler une facture. Decouvrez comment l etablir correctement au Luxembourg avec les mentions obligatoires.',
                'category_id' => 1,
                'tags' => [3, 4, 6, 9],
                'content' => <<<'HTML'
<p class="lead">Une erreur sur une facture ? Un client retourne un produit ? Vous devez emettre une <strong>note de credit</strong> (aussi appelee "avoir"). Voici comment l'etablir correctement au Luxembourg.</p>

<h2>Qu'est-ce qu'une note de credit ?</h2>

<p>Une note de credit est un document comptable qui <strong>annule ou corrige partiellement une facture</strong> deja emise. Contrairement a une facture, les montants d'une note de credit sont <strong>negatifs</strong>.</p>

<p><strong>Important :</strong> au Luxembourg, il est <strong>interdit de modifier ou supprimer une facture</strong> une fois qu'elle a ete finalisee (principe d'immutabilite). La seule maniere de corriger une erreur est d'emettre une note de credit.</p>

<h2>Quand emettre une note de credit ?</h2>

<ul>
    <li><strong>Erreur de facturation</strong> : montant incorrect, TVA erronee, mauvais client</li>
    <li><strong>Retour de marchandise</strong> : le client renvoie tout ou partie de la commande</li>
    <li><strong>Remise commerciale</strong> : accord sur un rabais apres facturation</li>
    <li><strong>Annulation de prestation</strong> : le service n'a pas ete rendu</li>
    <li><strong>Insolubilite du client</strong> : creance irrecuperable</li>
</ul>

<h2>Mentions obligatoires</h2>

<p>Une note de credit au Luxembourg doit contenir les memes mentions qu'une facture, plus :</p>

<ul>
    <li>La mention <strong>"Note de credit"</strong> ou <strong>"Avoir"</strong> clairement visible</li>
    <li>La <strong>reference a la facture d'origine</strong> (numero et date)</li>
    <li>Le <strong>motif</strong> de la note de credit</li>
    <li>Les montants en <strong>negatif</strong> (ou avec la mention "a deduire")</li>
    <li>La ventilation TVA (memes taux que la facture d'origine)</li>
    <li>Une <strong>numerotation specifique</strong> (ex: NC-2026-001 ou AV-2026-001)</li>
</ul>

<h2>Impact sur la TVA</h2>

<p>La note de credit a un impact direct sur votre declaration TVA :</p>

<ul>
    <li>La TVA de la note de credit vient en <strong>deduction de la TVA collectee</strong></li>
    <li>Elle doit etre declaree dans la <strong>meme periode</strong> que celle ou elle est emise</li>
    <li>Si le client a deja deduit la TVA, il devra <strong>regulariser sa propre declaration</strong></li>
</ul>

<h2>Creer une note de credit avec faktur.lu</h2>

<p>Avec faktur.lu, la creation d'une note de credit est simple et securisee :</p>

<ol>
    <li>Ouvrez la facture d'origine</li>
    <li>Cliquez sur <strong>"Creer une note de credit"</strong></li>
    <li>Selectionnez le motif</li>
    <li>Choisissez une annulation totale ou partielle</li>
    <li>La note de credit est generee automatiquement avec toutes les mentions requises</li>
</ol>

<p>La reference a la facture d'origine est ajoutee automatiquement, et les montants sont calcules en negatif. Votre livre des recettes et votre export FAIA sont mis a jour instantanement.</p>

<h2>Difference entre note de credit et avoir</h2>

<p>En pratique, les termes <strong>"note de credit"</strong> et <strong>"avoir"</strong> sont synonymes au Luxembourg. Le terme officiel utilise par l'administration est "note de credit" (Gutschrift en allemand), mais "avoir" est couramment utilise en francais.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Creez vos notes de credit en 1 clic</h3>
    <p class="text-primary-800 mb-4">faktur.lu genere automatiquement vos notes de credit avec toutes les mentions obligatoires, liees a la facture d'origine et conformes a la legislation luxembourgeoise.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],
        ];
    }
}
