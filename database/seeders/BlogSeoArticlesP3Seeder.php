<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogSeoArticlesP3Seeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->first()?->id ?? 1;

        $articles = $this->getArticles();

        foreach ($articles as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command->info("Skip: {$article['slug']} (existe deja)");
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

            foreach ($article['tags'] as $tagId) {
                DB::table('blog_post_tag')->insert([
                    'blog_post_id' => $postId,
                    'blog_tag_id' => $tagId,
                ]);
            }

            $this->command->info("Cree: {$article['title']}");
        }
    }

    private function getArticles(): array
    {
        return [
            // Article 11 : 5 erreurs facture freelance
            [
                'title' => '5 erreurs frequentes sur une facture freelance au Luxembourg',
                'slug' => '5-erreurs-frequentes-facture-freelance-luxembourg',
                'meta_title' => '5 Erreurs Frequentes sur une Facture Freelance Luxembourg',
                'meta_description' => 'Evitez ces 5 erreurs courantes sur vos factures freelance au Luxembourg : numerotation, mentions legales, TVA, delais. Conseils pratiques.',
                'excerpt' => 'Numerotation incorrecte, TVA oubliee, mentions manquantes... Decouvrez les 5 erreurs les plus frequentes sur les factures freelance et comment les eviter.',
                'category_id' => 3, // Freelances
                'tags' => [3, 4, 5], // Facturation, Luxembourg, Freelance
                'content' => <<<'HTML'
<p class="lead">En tant que freelance au Luxembourg, vos factures sont votre vitrine professionnelle et un document legal. Pourtant, <strong>plus de 60% des factures freelance contiennent au moins une erreur</strong>. Voici les 5 plus frequentes et comment les eviter.</p>

<h2>Erreur 1 : Numerotation non sequentielle</h2>

<p>La loi luxembourgeoise exige une <strong>numerotation strictement sequentielle</strong> de vos factures. Cela signifie :</p>

<ul>
    <li><strong>Pas de trou</strong> : si votre derniere facture est F-2026-042, la prochaine doit etre F-2026-043</li>
    <li><strong>Pas de doublon</strong> : deux factures ne peuvent pas avoir le meme numero</li>
    <li><strong>Pas de modification</strong> : vous ne pouvez pas changer un numero apres emission</li>
</ul>

<p><strong>Pourquoi c'est grave ?</strong> Lors d'un controle fiscal, une numerotation incoherente laisse supposer que des factures ont ete supprimees ou dissimulees. C'est un <strong>red flag</strong> pour l'AED.</p>

<p><strong>Solution :</strong> utilisez un logiciel qui genere automatiquement les numeros. Avec faktur.lu, la numerotation est sequentielle, sans trou et immutable.</p>

<h2>Erreur 2 : Mentions legales manquantes</h2>

<p>Une facture au Luxembourg doit obligatoirement contenir :</p>

<ul>
    <li>Le <strong>nom et l'adresse</strong> de votre entreprise</li>
    <li>Votre <strong>numero de TVA</strong> (ou la mention d'exemption)</li>
    <li>Votre <strong>numero RCS</strong> ou matricule</li>
    <li>Le nom et l'adresse du <strong>client</strong></li>
    <li>Le <strong>numero de la facture</strong></li>
    <li>La <strong>date d'emission</strong></li>
    <li>La <strong>description detaillee</strong> de la prestation</li>
    <li>Le <strong>montant HT, le taux de TVA et le montant TTC</strong></li>
</ul>

<p><strong>L'erreur classique :</strong> oublier le numero de TVA du client pour les factures intracommunautaires, ou ne pas mentionner le motif d'exoneration quand la TVA est a 0%.</p>

<p><strong>Solution :</strong> faktur.lu ajoute automatiquement toutes les mentions obligatoires selon le type de client et le scenario TVA.</p>

<h2>Erreur 3 : Mauvais taux de TVA</h2>

<p>Au Luxembourg, il existe <strong>4 taux de TVA</strong> :</p>

<ul>
    <li><strong>17%</strong> : taux normal (la plupart des prestations de services)</li>
    <li><strong>14%</strong> : taux intermediaire (certains services specifiques)</li>
    <li><strong>8%</strong> : taux reduit (electricite, gaz, coiffure...)</li>
    <li><strong>3%</strong> : taux super-reduit (alimentation, livres, presse)</li>
</ul>

<p><strong>Les erreurs courantes :</strong></p>

<ul>
    <li>Appliquer 20% (taux francais) au lieu de 17%</li>
    <li>Facturer avec TVA a un client intracommunautaire (autoliquidation)</li>
    <li>Oublier la TVA pour un client luxembourgeois B2C</li>
    <li>Ne pas mentionner la franchise TVA quand on est en dessous du seuil</li>
</ul>

<p><strong>Solution :</strong> faktur.lu determine automatiquement le bon taux et la bonne mention selon le pays et le type de client.</p>

<h2>Erreur 4 : Pas de conditions de paiement</h2>

<p>Beaucoup de freelances oublient de preciser les <strong>modalites de paiement</strong> sur leurs factures :</p>

<ul>
    <li><strong>Date d'echeance</strong> : sans echeance precisee, le delai legal est de 30 jours, mais votre client peut l'ignorer</li>
    <li><strong>Moyen de paiement</strong> : indiquez votre IBAN pour faciliter le virement</li>
    <li><strong>Penalites de retard</strong> : mentionnez les interets applicables (taux BCE + 8 points)</li>
    <li><strong>Indemnite forfaitaire</strong> : les 40 EUR pour frais de recouvrement</li>
</ul>

<p><strong>Pourquoi c'est important :</strong> sans conditions claires, vous n'avez aucune base legale pour reclamer des interets de retard en cas d'impaye.</p>

<h2>Erreur 5 : Modifier une facture finalisee</h2>

<p>Une fois qu'une facture est envoyee au client, elle est <strong>immutable</strong>. Vous ne pouvez pas :</p>

<ul>
    <li>Changer le montant</li>
    <li>Modifier le client</li>
    <li>Supprimer la facture</li>
    <li>Changer le numero</li>
</ul>

<p>Si vous avez fait une erreur, la <strong>seule solution legale</strong> est d'emettre une <strong>note de credit</strong> (avoir) qui annule la facture erronee, puis de creer une nouvelle facture corrigee.</p>

<p><strong>L'erreur classique :</strong> modifier le fichier Excel ou Word de la facture et reenvoyer une "version corrigee". En cas de controle fiscal, cela peut etre considere comme de la <strong>falsification</strong>.</p>

<h2>Bonus : la checklist de la facture parfaite</h2>

<p>Avant d'envoyer chaque facture, verifiez :</p>

<ul>
    <li>&#9745; Numero sequentiel correct</li>
    <li>&#9745; Date d'emission et date d'echeance</li>
    <li>&#9745; Toutes les mentions legales presentes</li>
    <li>&#9745; Bon taux de TVA selon le scenario</li>
    <li>&#9745; IBAN et conditions de paiement</li>
    <li>&#9745; Montants HT, TVA et TTC corrects</li>
    <li>&#9745; Description claire de la prestation</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez sans erreur avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu verifie automatiquement chaque facture : numerotation, mentions legales, TVA, immutabilite. Zero risque d'erreur.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 12 : Facturer à l'étranger
            [
                'title' => 'Comment facturer a l etranger depuis le Luxembourg',
                'slug' => 'facturer-etranger-depuis-luxembourg',
                'meta_title' => 'Facturer a l Etranger depuis le Luxembourg : Guide TVA et Regles',
                'meta_description' => 'Comment facturer un client etranger depuis le Luxembourg ? Regles TVA par zone (UE, hors UE), mentions obligatoires et pieges a eviter.',
                'excerpt' => 'Facturer un client en France, en Allemagne ou hors UE depuis le Luxembourg ? Decouvrez les regles TVA et les mentions a appliquer selon chaque cas.',
                'category_id' => 1,
                'tags' => [2, 3, 4, 7],
                'content' => <<<'HTML'
<p class="lead">Vous etes base au Luxembourg et vous facturez des clients a l'etranger ? Les regles de TVA varient considerablement selon la zone geographique et le type de client. Voici un guide clair pour chaque situation.</p>

<h2>Cas 1 : Client entreprise dans l'UE (B2B intracommunautaire)</h2>

<p>C'est le cas le plus frequent pour les freelances et PME luxembourgeoises. Exemple : un consultant luxembourgeois facture une societe allemande.</p>

<h3>Regles a appliquer</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Le client declare la TVA dans son pays (<strong>autoliquidation / reverse charge</strong>)</li>
    <li>Vous devez verifier le numero TVA du client sur <strong>VIES</strong></li>
    <li>Mention obligatoire : <em>"Autoliquidation - Article 44 de la directive 2006/112/CE"</em></li>
</ul>

<h3>Documents necessaires</h3>
<ul>
    <li>Votre numero TVA luxembourgeois sur la facture</li>
    <li>Le numero TVA du client (verifie VIES)</li>
    <li>Declaration dans l'<strong>etat recapitulatif</strong> TVA mensuel/trimestriel</li>
</ul>

<h2>Cas 2 : Client particulier dans l'UE (B2C)</h2>

<p>Vous vendez a un particulier dans un autre pays UE. Les regles dependent du type de prestation :</p>

<h3>Services classiques (conseil, design, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA luxembourgeoise (17%)</strong></li>
    <li>Pas d'autoliquidation pour les particuliers</li>
</ul>

<h3>Services electroniques (SaaS, formations en ligne, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA du pays du client</strong></li>
    <li>Via le regime <strong>OSS (One-Stop Shop)</strong> : une seule declaration pour tous les pays UE</li>
    <li>Seuil : 10 000 EUR/an de ventes B2C dans l'UE</li>
</ul>

<h2>Cas 3 : Client hors UE (export)</h2>

<p>Vous facturez un client en Suisse, aux Etats-Unis, au Royaume-Uni ou dans tout autre pays hors UE.</p>

<h3>Services</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Mention : <em>"Prestation de services hors du champ d'application de la TVA luxembourgeoise"</em></li>
    <li>Pas d'etat recapitulatif necessaire</li>
</ul>

<h3>Biens (export)</h3>
<ul>
    <li>Vous facturez <strong>hors taxes</strong> (exportation exoneree)</li>
    <li>Vous devez conserver la <strong>preuve d'exportation</strong> (document douanier)</li>
    <li>Mention : <em>"Exportation exoneree - Article 146 directive 2006/112/CE"</em></li>
</ul>

<h2>Cas special : la Suisse</h2>

<p>La Suisse n'est pas dans l'UE. Cependant, de nombreux freelances luxembourgeois facturent des clients suisses. Les regles :</p>

<ul>
    <li>Services : facturez <strong>HT</strong>, le client suisse declare la TVA dans le cadre du mecanisme d'importation de services</li>
    <li>Pas d'etat recapitulatif (reserve aux echanges intra-UE)</li>
    <li>Facturez en <strong>EUR ou CHF</strong> selon l'accord avec le client</li>
</ul>

<h2>Tableau recapitulatif</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Scenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mention</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxembourg</td><td class="border border-gray-300 px-4 py-2">17%</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE</td><td class="border border-gray-300 px-4 py-2">0% (autoliquidation)</td><td class="border border-gray-300 px-4 py-2">Art. 44 directive</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (services)</td><td class="border border-gray-300 px-4 py-2">17% LU</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (electronique)</td><td class="border border-gray-300 px-4 py-2">TVA pays client</td><td class="border border-gray-300 px-4 py-2">Regime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B hors UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Hors champ</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C hors UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Hors champ</td></tr>
    </tbody>
</table>

<h2>Devises et taux de change</h2>

<p>Vous pouvez facturer en <strong>devises etrangeres</strong> (USD, CHF, GBP), mais pour votre declaration TVA, vous devrez convertir en EUR au <strong>taux de change du jour de la facture</strong> (taux BCE).</p>

<p>faktur.lu supporte la facturation <strong>multi-devises</strong> et conserve le taux de change utilise pour chaque facture.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez a l'international en conformite</h3>
    <p class="text-primary-800 mb-4">faktur.lu detecte automatiquement le scenario TVA selon le pays et le type de client, et applique les bonnes mentions. Multi-devises inclus.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 13 : Franchise TVA
            [
                'title' => 'Franchise TVA au Luxembourg : seuil, obligations et passage au regime normal',
                'slug' => 'franchise-tva-luxembourg-seuil-obligations-regime-normal',
                'meta_title' => 'Franchise TVA Luxembourg : Seuil 35 000 EUR, Obligations et Passage',
                'meta_description' => 'Tout sur la franchise TVA au Luxembourg : seuil de 35 000 EUR, obligations, avantages, inconvenients et comment passer au regime normal.',
                'excerpt' => 'La franchise TVA vous exempte de facturer la TVA sous le seuil de 35 000 EUR. Decouvrez les regles, avantages et comment gerer le passage au regime normal.',
                'category_id' => 2,
                'tags' => [2, 4, 5, 8],
                'content' => <<<'HTML'
<p class="lead">Au Luxembourg, les petites entreprises dont le chiffre d'affaires annuel ne depasse pas <strong>35 000 EUR</strong> peuvent beneficier du <strong>regime de franchise TVA</strong>. Ce regime les dispense de facturer la TVA. Voici tout ce qu'il faut savoir.</p>

<h2>Qu'est-ce que la franchise TVA ?</h2>

<p>La franchise TVA (article 57 bis de la loi TVA luxembourgeoise) est un <strong>regime simplifie</strong> qui permet aux petites entreprises de :</p>

<ul>
    <li><strong>Ne pas facturer de TVA</strong> a leurs clients</li>
    <li><strong>Ne pas declarer de TVA</strong> periodiquement</li>
    <li><strong>Ne pas produire de declaration TVA annuelle</strong></li>
</ul>

<p>En contrepartie, vous ne pouvez <strong>pas deduire la TVA</strong> sur vos achats professionnels.</p>

<h2>Conditions d'eligibilite</h2>

<ul>
    <li>Chiffre d'affaires annuel HT <strong>inferieur a 35 000 EUR</strong></li>
    <li>Activite exercee au Luxembourg</li>
    <li>Ne pas avoir opte pour le regime normal volontairement</li>
    <li>Certaines activites sont exclues (immobilier, vehicules neufs)</li>
</ul>

<h2>Mentions obligatoires sur vos factures</h2>

<p>Meme en franchise TVA, vos factures doivent contenir une <strong>mention specifique</strong> :</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "TVA non applicable - Article 57 bis de la loi modifiee du 12 fevrier 1979"
</blockquote>

<p>Vous devez egalement :</p>
<ul>
    <li>Ne <strong>pas mentionner de TVA</strong> (pas de ligne TVA sur la facture)</li>
    <li>Ne pas indiquer de <strong>taux de TVA</strong></li>
    <li>Indiquer uniquement le <strong>montant net</strong> (pas de distinction HT/TTC)</li>
</ul>

<h2>Avantages de la franchise</h2>

<ul>
    <li><strong>Simplicite</strong> : pas de declaration TVA periodique</li>
    <li><strong>Competitivite</strong> : vos prix B2C sont plus bas (pas de TVA a ajouter)</li>
    <li><strong>Tresorerie</strong> : pas de decalage entre TVA encaissee et TVA reversee</li>
    <li><strong>Moins de paperasse</strong> : administration simplifiee</li>
</ul>

<h2>Inconvenients</h2>

<ul>
    <li><strong>Pas de deduction TVA</strong> : vous payez la TVA sur vos achats sans pouvoir la recuperer</li>
    <li><strong>Desavantage B2B</strong> : vos clients entreprises ne peuvent pas deduire de TVA sur vos factures</li>
    <li><strong>Image</strong> : certains clients B2B preferent travailler avec des entreprises assujetties</li>
    <li><strong>Seuil contraignant</strong> : si vous depassez 35 000 EUR, le passage est obligatoire</li>
</ul>

<h2>Quand passer au regime normal ?</h2>

<h3>Passage obligatoire</h3>
<p>Si votre chiffre d'affaires depasse <strong>35 000 EUR sur 12 mois glissants</strong>, vous devez :</p>

<ol>
    <li>Contacter l'<strong>AED</strong> dans les 15 jours suivant le depassement</li>
    <li>Commencer a facturer la TVA <strong>immediatement</strong></li>
    <li>Soumettre vos declarations TVA periodiquement</li>
</ol>

<h3>Passage volontaire</h3>
<p>Vous pouvez aussi opter pour le regime normal <strong>volontairement</strong>, meme en dessous du seuil. C'est recommande si :</p>

<ul>
    <li>Vos clients sont majoritairement des entreprises (B2B) qui deduisent la TVA</li>
    <li>Vous avez des investissements importants (equipement, vehicule) et voulez recuperer la TVA</li>
    <li>Vous approchez du seuil et preferez anticiper</li>
</ul>

<h2>Impact sur vos factures avec faktur.lu</h2>

<p>faktur.lu gere les deux regimes :</p>

<ul>
    <li><strong>Franchise TVA</strong> : les factures affichent automatiquement la mention d'exemption, sans ligne TVA</li>
    <li><strong>Regime normal</strong> : la TVA est calculee automatiquement avec le bon taux</li>
    <li><strong>Alerte seuil</strong> : faktur.lu vous previent quand vous approchez des 35 000 EUR pour anticiper le passage</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gerez votre franchise TVA en toute serenite</h3>
    <p class="text-primary-800 mb-4">faktur.lu s'adapte automatiquement a votre regime TVA et vous alerte avant le depassement du seuil de 35 000 EUR.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 14 : Automatiser sa facturation
            [
                'title' => '7 conseils pour automatiser sa facturation et gagner du temps',
                'slug' => 'automatiser-facturation-7-conseils-gagner-temps',
                'meta_title' => 'Automatiser sa Facturation : 7 Conseils pour Gagner du Temps',
                'meta_description' => 'Gagnez 5h par mois en automatisant votre facturation. 7 conseils pratiques : modeles, relances, exports comptables, suivi du temps.',
                'excerpt' => 'Vous perdez trop de temps sur la facturation ? Voici 7 conseils concrets pour automatiser et gagner 5 heures par mois.',
                'category_id' => 1,
                'tags' => [3, 5, 7],
                'content' => <<<'HTML'
<p class="lead">La facturation prend en moyenne <strong>5 a 8 heures par mois</strong> pour un freelance ou une petite entreprise. Bonne nouvelle : la plupart de ces taches peuvent etre automatisees. Voici 7 conseils pour y arriver.</p>

<h2>1. Pre-enregistrez vos produits et services</h2>

<p>Plutot que de retaper la description et le prix a chaque facture, <strong>creez un catalogue</strong> de vos prestations :</p>

<ul>
    <li>Nom du service (ex: "Consulting - taux journalier")</li>
    <li>Prix unitaire</li>
    <li>Taux de TVA par defaut</li>
    <li>Unite (heure, jour, forfait)</li>
</ul>

<p>Lors de la creation d'une facture, selectionnez simplement le service dans la liste. <strong>Gain : 2-3 minutes par facture.</strong></p>

<h2>2. Automatisez les relances d'impayes</h2>

<p>Ne perdez plus de temps a verifier manuellement quelles factures sont en retard. Configurez des <strong>relances automatiques</strong> :</p>

<ul>
    <li><strong>J+7</strong> : premier rappel amical par email</li>
    <li><strong>J+15</strong> : relance formelle</li>
    <li><strong>J+30</strong> : derniere relance avant mise en demeure</li>
</ul>

<p>Avec faktur.lu, les relances sont envoyees automatiquement. Vous pouvez personnaliser les delais et le contenu des emails. <strong>Gain : 1-2 heures par mois.</strong></p>

<h2>3. Utilisez le suivi du temps integre</h2>

<p>Si vous facturez au temps passe, arretez de noter vos heures sur papier ou dans Excel :</p>

<ul>
    <li>Lancez un <strong>timer en 1 clic</strong> quand vous commencez a travailler</li>
    <li>Associez chaque entree a un <strong>projet et un client</strong></li>
    <li>En fin de mois, <strong>convertissez vos heures en facture</strong> automatiquement</li>
</ul>

<p><strong>Gain : 30-45 minutes par mois</strong> et zero oubli d'heures facturables.</p>

<h2>4. Convertissez vos devis en factures en 1 clic</h2>

<p>Quand un devis est accepte, ne recreez pas la facture de zero. Cliquez sur <strong>"Convertir en facture"</strong> et toutes les informations sont reprises automatiquement :</p>

<ul>
    <li>Client, adresse, TVA</li>
    <li>Lignes de detail, quantites, prix</li>
    <li>Notes et conditions</li>
</ul>

<p><strong>Gain : 5-10 minutes par conversion.</strong></p>

<h2>5. Planifiez vos factures recurrentes</h2>

<p>Vous facturez le meme montant chaque mois (abonnement, forfait maintenance, loyer) ? Creez une <strong>facture recurrente</strong> :</p>

<ul>
    <li>Definissez la frequence (mensuelle, trimestrielle, annuelle)</li>
    <li>La facture est generee et envoyee automatiquement</li>
    <li>Le numero sequentiel est attribue automatiquement</li>
</ul>

<p><strong>Gain : total.</strong> Vous n'avez plus a y penser.</p>

<h2>6. Transmettez vos donnees a votre comptable automatiquement</h2>

<p>Plus besoin d'envoyer un classeur Excel ou un zip de PDF a votre fiduciaire :</p>

<ul>
    <li>Donnez-lui un <strong>acces comptable</strong> en lecture seule a votre logiciel</li>
    <li>Il accede en temps reel a vos factures, depenses et livre des recettes</li>
    <li>Exportez en <strong>Sage BOB ou FID-Manager</strong> en 1 clic</li>
</ul>

<p>faktur.lu propose un portail comptable dedie des le plan Essentiel. <strong>Gain : 1-2 heures par mois.</strong></p>

<h2>7. Importez vos clients en masse</h2>

<p>Vous avez une liste de clients dans un fichier Excel ? Ne les ressaisissez pas un par un :</p>

<ul>
    <li>Exportez votre fichier en CSV ou Excel</li>
    <li>Importez-le dans faktur.lu avec le <strong>mapping automatique des colonnes</strong></li>
    <li>Verifiez l'apercu et validez</li>
</ul>

<p><strong>Gain : plusieurs heures</strong> si vous avez plus de 20 clients.</p>

<h2>Recapitulatif des gains de temps</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Action automatisee</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Gain estime</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Pre-enregistrement produits</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Relances automatiques</td><td class="border border-gray-300 px-4 py-2">1-2h/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Suivi du temps</td><td class="border border-gray-300 px-4 py-2">45 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Conversion devis → facture</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Factures recurrentes</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Portail comptable</td><td class="border border-gray-300 px-4 py-2">1-2h/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Import clients</td><td class="border border-gray-300 px-4 py-2">Ponctuel</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5h/mois</td></tr>
    </tbody>
</table>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisez votre facturation avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">Relances automatiques, suivi du temps, conversion devis, export comptable : tout est inclus. Gagnez 5 heures par mois.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],

            // Article 15 : Contrôle fiscal
            [
                'title' => 'Controle fiscal au Luxembourg : comment se preparer',
                'slug' => 'controle-fiscal-luxembourg-comment-preparer',
                'meta_title' => 'Controle Fiscal Luxembourg : Comment se Preparer | Guide FAIA',
                'meta_description' => 'Comment se preparer a un controle fiscal au Luxembourg ? Documents requis, export FAIA, bonnes pratiques. Guide pour ne pas paniquer.',
                'excerpt' => 'Un controle fiscal peut arriver a tout moment. Decouvrez comment vous preparer : documents a conserver, export FAIA et bonnes pratiques.',
                'category_id' => 2,
                'tags' => [1, 4, 6, 8, 9],
                'content' => <<<'HTML'
<p class="lead">Un courrier de l'<strong>AED (Administration de l'enregistrement, des domaines et de la TVA)</strong> vous annoncant un controle fiscal ? Pas de panique. Si votre comptabilite est bien tenue, le controle se passera sans probleme. Voici comment vous preparer.</p>

<h2>Qui peut etre controle ?</h2>

<p>Toute entreprise assujettie a la TVA au Luxembourg peut faire l'objet d'un controle fiscal. Les controles sont :</p>

<ul>
    <li><strong>Aleatoires</strong> : selection au hasard dans la base des contribuables</li>
    <li><strong>Cibles</strong> : suite a des incoherences detectees dans vos declarations</li>
    <li><strong>Sectoriels</strong> : controles par secteur d'activite</li>
    <li><strong>Sur demande</strong> : suite a une demande de remboursement TVA importante</li>
</ul>

<p>Les <strong>petites entreprises et freelances</strong> ne sont pas epargnes. L'AED controle toutes les tailles d'entreprise.</p>

<h2>Le fichier FAIA : l'element central</h2>

<p>Lors d'un controle, l'AED peut exiger votre <strong>FAIA (Fichier d'Audit Informatise)</strong>. C'est un fichier XML standardise qui contient :</p>

<ul>
    <li><strong>Informations de l'entreprise</strong> : matricule, TVA, adresse</li>
    <li><strong>Plan comptable</strong> : liste des comptes utilises</li>
    <li><strong>Ecritures comptables</strong> : toutes les transactions de l'exercice</li>
    <li><strong>Factures emises</strong> : detail de chaque facture client</li>
    <li><strong>Factures recues</strong> : detail des factures fournisseurs</li>
</ul>

<p><strong>Important :</strong> si vous ne pouvez pas fournir un FAIA conforme, l'administration peut proceder a une <strong>estimation d'office</strong> de votre chiffre d'affaires et de votre TVA. Les consequences financieres peuvent etre lourdes.</p>

<h2>Documents a conserver</h2>

<p>Gardez ces documents pendant <strong>10 ans</strong> :</p>

<ul>
    <li><strong>Toutes les factures emises</strong> (numerotation sequentielle, sans trou)</li>
    <li><strong>Toutes les factures recues</strong> (fournisseurs, prestataires)</li>
    <li><strong>Le livre des recettes</strong></li>
    <li><strong>Les declarations TVA</strong> deposees</li>
    <li><strong>Les releves bancaires</strong></li>
    <li><strong>Les contrats</strong> avec vos clients</li>
    <li><strong>Les preuves de livraison</strong> (pour les biens exportes)</li>
    <li><strong>Les verifications VIES</strong> (pour les clients intracommunautaires)</li>
</ul>

<h2>Deroulement d'un controle</h2>

<ol>
    <li><strong>Notification</strong> : l'AED vous informe par courrier, generalement 2-4 semaines a l'avance</li>
    <li><strong>Preparation</strong> : rassemblez vos documents, generez votre FAIA</li>
    <li><strong>Controle sur place</strong> : un inspecteur examine vos documents, souvent chez vous ou chez votre comptable</li>
    <li><strong>Questions</strong> : l'inspecteur peut poser des questions sur des transactions specifiques</li>
    <li><strong>Rapport</strong> : l'AED redige un rapport avec ses conclusions</li>
    <li><strong>Regularisation</strong> : si des erreurs sont trouvees, vous recevez un avis de redressement</li>
</ol>

<h2>Les points les plus controles</h2>

<ul>
    <li><strong>Coherence TVA</strong> : la TVA declaree correspond-elle aux factures emises ?</li>
    <li><strong>Deductions TVA</strong> : les factures fournisseurs sont-elles conformes ?</li>
    <li><strong>Operations intracommunautaires</strong> : les exonerations sont-elles justifiees (VIES, preuves) ?</li>
    <li><strong>Numerotation des factures</strong> : sequence continue, sans trou</li>
    <li><strong>Notes de credit</strong> : sont-elles liees a des factures existantes ?</li>
    <li><strong>Franchise TVA</strong> : le seuil de 35 000 EUR est-il respecte ?</li>
</ul>

<h2>Comment se preparer avec faktur.lu</h2>

<p>faktur.lu est concu pour que vous soyez <strong>toujours pret</strong> en cas de controle :</p>

<ul>
    <li><strong>Export FAIA en 1 clic</strong> : fichier XML conforme au format 2.01 de l'AED, generable a tout moment</li>
    <li><strong>Numerotation immutable</strong> : aucune possibilite de trou ou de doublon</li>
    <li><strong>Tracabilite complete</strong> : chaque action est journalisee (audit trail)</li>
    <li><strong>Archivage PDF/A</strong> : factures archivees avec empreinte numerique (plan Pro)</li>
    <li><strong>Livre des recettes automatique</strong> : exportable en PDF ou CSV</li>
    <li><strong>Verification VIES</strong> : log de validation des numeros TVA intracommunautaires</li>
</ul>

<h2>Conseils pour un controle serein</h2>

<ul>
    <li><strong>Soyez organise</strong> : classez vos documents par annee et par type</li>
    <li><strong>Soyez cooperatif</strong> : repondez clairement aux questions de l'inspecteur</li>
    <li><strong>Faites-vous accompagner</strong> : votre comptable peut etre present lors du controle</li>
    <li><strong>Anticipez</strong> : generez un FAIA de test chaque trimestre pour verifier la coherence</li>
    <li><strong>Corrigez vos erreurs</strong> : une erreur corrigee spontanement avant le controle est vue plus favorablement</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Soyez pret pour le controle fiscal</h3>
    <p class="text-primary-800 mb-4">Avec faktur.lu, votre FAIA est toujours pret, vos factures sont conformes et votre tracabilite est complete. Facturez l'esprit tranquille.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
            ],
        ];
    }
}
