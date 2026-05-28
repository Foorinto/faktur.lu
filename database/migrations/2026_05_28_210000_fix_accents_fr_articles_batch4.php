<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration (batch 4 — final) for the last 2 affected FR blog
 * articles. Also replaces the non-existent "FID-Manager" export reference
 * (→ Sage BOB 50 / Sage 100 / CSV) in the automation article.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->corrections() as $slug => $content) {
            BlogPost::where('locale', 'fr')->where('slug', $slug)->update(['content' => $content]);
        }
    }

    public function down(): void
    {
        // No-op.
    }

    private function corrections(): array
    {
        return [
            'peppol-b2g-luxembourg-guide-complet-2026' => <<<'HTML'
<p class="lead">Depuis 2022, la facturation électronique via <strong>Peppol</strong> est devenue une obligation pour les fournisseurs du secteur public au Luxembourg. Ce guide vous explique tout ce que vous devez savoir pour vous mettre en conformité.</p>

<h2>Qu'est-ce que Peppol ?</h2>

<p><strong>Peppol (Pan-European Public Procurement OnLine)</strong> est un réseau international de facturation électronique qui permet d'envoyer des documents commerciaux (factures, bons de commande) de manière standardisée entre entreprises et administrations publiques.</p>

<p>Au Luxembourg, Peppol est le canal officiel pour la facturation électronique B2G (Business-to-Government). Cela signifie que toute entreprise qui facture l'État, les communes ou les établissements publics doit utiliser ce format.</p>

<h2>Qui est concerné ?</h2>

<p>Si vous êtes fournisseur de l'une des entités suivantes, vous devez facturer via Peppol :</p>

<ul>
    <li><strong>L'État luxembourgeois</strong> et ses ministères</li>
    <li><strong>Les communes</strong> luxembourgeoises</li>
    <li><strong>Les établissements publics</strong> (hôpitaux, écoles, etc.)</li>
    <li><strong>Les marchés publics</strong> de plus de 30 000 EUR</li>
</ul>

<p>Les entreprises qui ne facturent qu'à des clients privés (B2B ou B2C) ne sont pas encore concernées par cette obligation, mais l'Union européenne pousse vers une généralisation à partir de 2028.</p>

<h2>Comment fonctionne Peppol ?</h2>

<p>Le réseau Peppol fonctionne sur un modèle de "quatre coins" :</p>

<ol>
    <li><strong>L'expéditeur</strong> (votre entreprise) crée la facture</li>
    <li><strong>Le point d'accès expéditeur</strong> (votre logiciel de facturation) envoie la facture sur le réseau Peppol</li>
    <li><strong>Le point d'accès destinataire</strong> (côté administration) reçoit la facture</li>
    <li><strong>Le destinataire</strong> (l'administration publique) traite la facture</li>
</ol>

<p>Chaque participant au réseau est identifié par un <strong>Peppol ID</strong> unique. Au Luxembourg, ce numéro est généralement basé sur le numéro de TVA (schéma 0184).</p>

<h2>Le format UBL</h2>

<p>Les factures Peppol utilisent le format <strong>UBL (Universal Business Language)</strong>, un standard XML international. Votre facture doit contenir :</p>

<ul>
    <li>Les informations de l'expéditeur (nom, adresse, TVA)</li>
    <li>Les informations du destinataire (nom, Peppol ID)</li>
    <li>Les lignes de facturation (description, quantité, prix unitaire)</li>
    <li>Les montants TVA ventilés par taux</li>
    <li>Les totaux (HT, TVA, TTC)</li>
    <li>Les références de commande ou de contrat</li>
</ul>

<h2>Comment envoyer une facture Peppol depuis faktur.lu ?</h2>

<p>Avec <strong>faktur.lu</strong>, l'envoi de factures Peppol est intégré directement dans le logiciel :</p>

<ol>
    <li>Créez votre facture normalement</li>
    <li>Assurez-vous que le Peppol ID du client est renseigné</li>
    <li>Cliquez sur <strong>"Envoyer via Peppol"</strong></li>
    <li>La facture est convertie en UBL et transmise via le réseau Peppol</li>
    <li>Vous recevez une confirmation de réception</li>
</ol>

<p>Le plan Essentiel inclut 10 envois Peppol par mois, et le plan Pro offre des envois illimités.</p>

<h2>Avantages de la facturation Peppol</h2>

<ul>
    <li><strong>Traitement accéléré</strong> : les factures Peppol sont traitées automatiquement, réduisant les délais de paiement</li>
    <li><strong>Réduction des erreurs</strong> : le format structuré élimine les erreurs de saisie manuelle</li>
    <li><strong>Traçabilité</strong> : chaque facture est tracée de bout en bout sur le réseau</li>
    <li><strong>Conformité</strong> : vous respectez les obligations légales luxembourgeoises</li>
    <li><strong>Interopérabilité</strong> : Peppol est reconnu dans plus de 35 pays</li>
</ul>

<h2>Questions fréquentes</h2>

<h3>La facturation Peppol est-elle obligatoire pour le B2B ?</h3>

<p>Pas encore au Luxembourg. Cependant, la directive européenne ViDA prévoit une généralisation de la facturation électronique pour les transactions intracommunautaires à partir de 2028-2030.</p>

<h3>Combien coûte l'envoi via Peppol ?</h3>

<p>Avec faktur.lu, l'envoi Peppol est inclus dans votre abonnement. Le plan Essentiel comprend 10 envois/mois et le plan Pro offre des envois illimités.</p>

<h3>Comment trouver le Peppol ID d'une administration ?</h3>

<p>Les Peppol ID des administrations luxembourgeoises sont disponibles sur le <a href="https://directory.peppol.eu" target="_blank" rel="noopener">Peppol Directory</a>. Vous pouvez aussi les rechercher directement dans faktur.lu lors de la création du client.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/factur-x-zugferd-facturation-electronique-europeenne" class="text-primary-500 hover:text-primary-600 text-sm">Factur-X / ZUGFeRD →</a></li><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">logiciel de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Prêt à facturer via Peppol ?</h3>
    <p class="text-primary-800 mb-4">faktur.lu intègre nativement la transmission Peppol. Créez votre compte gratuit et envoyez votre première facture électronique en quelques minutes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'automatiser-facturation-7-conseils-gagner-temps' => <<<'HTML'
<p class="lead">La facturation prend en moyenne <strong>5 à 8 heures par mois</strong> pour un freelance ou une petite entreprise. Bonne nouvelle : la plupart de ces tâches peuvent être automatisées. Voici 7 conseils pour y arriver.</p>

<h2>1. Pré-enregistrez vos produits et services</h2>

<p>Plutôt que de retaper la description et le prix à chaque facture, <strong>créez un catalogue</strong> de vos prestations :</p>

<ul>
    <li>Nom du service (ex: "Consulting - taux journalier")</li>
    <li>Prix unitaire</li>
    <li>Taux de TVA par défaut</li>
    <li>Unité (heure, jour, forfait)</li>
</ul>

<p>Lors de la création d'une facture, sélectionnez simplement le service dans la liste. <strong>Gain : 2-3 minutes par facture.</strong></p>

<h2>2. Automatisez les relances d'impayés</h2>

<p>Ne perdez plus de temps à vérifier manuellement quelles factures sont en retard. Configurez des <strong>relances automatiques</strong> :</p>

<ul>
    <li><strong>J+7</strong> : premier rappel amical par email</li>
    <li><strong>J+15</strong> : relance formelle</li>
    <li><strong>J+30</strong> : dernière relance avant mise en demeure</li>
</ul>

<p>Avec faktur.lu, les relances sont envoyées automatiquement. Vous pouvez personnaliser les délais et le contenu des emails. <strong>Gain : 1-2 heures par mois.</strong></p>

<h2>3. Utilisez le suivi du temps intégré</h2>

<p>Si vous facturez au temps passé, arrêtez de noter vos heures sur papier ou dans Excel :</p>

<ul>
    <li>Lancez un <strong>timer en 1 clic</strong> quand vous commencez à travailler</li>
    <li>Associez chaque entrée à un <strong>projet et un client</strong></li>
    <li>En fin de mois, <strong>convertissez vos heures en facture</strong> automatiquement</li>
</ul>

<p><strong>Gain : 30-45 minutes par mois</strong> et zéro oubli d'heures facturables.</p>

<h2>4. Convertissez vos devis en factures en 1 clic</h2>

<p>Quand un devis est accepté, ne recréez pas la facture de zéro. Cliquez sur <strong>"Convertir en facture"</strong> et toutes les informations sont reprises automatiquement :</p>

<ul>
    <li>Client, adresse, TVA</li>
    <li>Lignes de détail, quantités, prix</li>
    <li>Notes et conditions</li>
</ul>

<p><strong>Gain : 5-10 minutes par conversion.</strong></p>

<h2>5. Planifiez vos factures récurrentes</h2>

<p>Vous facturez le même montant chaque mois (abonnement, forfait maintenance, loyer) ? Créez une <strong>facture récurrente</strong> :</p>

<ul>
    <li>Définissez la fréquence (mensuelle, trimestrielle, annuelle)</li>
    <li>La facture est générée et envoyée automatiquement</li>
    <li>Le numéro séquentiel est attribué automatiquement</li>
</ul>

<p><strong>Gain : total.</strong> Vous n'avez plus à y penser.</p>

<h2>6. Transmettez vos données à votre comptable automatiquement</h2>

<p>Plus besoin d'envoyer un classeur Excel ou un zip de PDF à votre fiduciaire :</p>

<ul>
    <li>Donnez-lui un <strong>accès comptable</strong> en lecture seule à votre logiciel</li>
    <li>Il accède en temps réel à vos factures, dépenses et livre des recettes</li>
    <li>Exportez en <strong>Sage BOB 50, Sage 100 ou CSV</strong> en 1 clic</li>
</ul>

<p>faktur.lu propose un portail comptable dédié dès le plan gratuit. <strong>Gain : 1-2 heures par mois.</strong></p>

<h2>7. Importez vos clients en masse</h2>

<p>Vous avez une liste de clients dans un fichier Excel ? Ne les ressaisissez pas un par un :</p>

<ul>
    <li>Exportez votre fichier en CSV ou Excel</li>
    <li>Importez-le dans faktur.lu avec le <strong>mapping automatique des colonnes</strong></li>
    <li>Vérifiez l'aperçu et validez</li>
</ul>

<p><strong>Gain : plusieurs heures</strong> si vous avez plus de 20 clients.</p>

<h2>Récapitulatif des gains de temps</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Action automatisée</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Gain estimé</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Pré-enregistrement produits</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Relances automatiques</td><td class="border border-gray-300 px-4 py-2">1-2h/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Suivi du temps</td><td class="border border-gray-300 px-4 py-2">45 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Conversion devis → facture</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Factures récurrentes</td><td class="border border-gray-300 px-4 py-2">30 min/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Portail comptable</td><td class="border border-gray-300 px-4 py-2">1-2h/mois</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Import clients</td><td class="border border-gray-300 px-4 py-2">Ponctuel</td></tr>
        <tr class="bg-primary-50"><td class="border border-gray-300 px-4 py-2 font-bold">Total</td><td class="border border-gray-300 px-4 py-2 font-bold">~5h/mois</td></tr>
    </tbody>
</table>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">logiciel de facturation →</a></li><li><a href="/fr/blog/excel-vs-logiciel-facturation-pourquoi-switch" class="text-primary-500 hover:text-primary-600 text-sm">quitter Excel →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisez votre facturation avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">Relances automatiques, suivi du temps, conversion devis, export comptable : tout est inclus. Gagnez 5 heures par mois.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
        ];
    }
};
