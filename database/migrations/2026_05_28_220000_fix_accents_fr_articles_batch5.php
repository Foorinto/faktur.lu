<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration (batch 5 — final) for the last 2 affected FR blog
 * articles (franchise TVA, délais de paiement).
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
            'franchise-tva-luxembourg-seuil-obligations-regime-normal' => <<<'HTML'
<p class="lead">Au Luxembourg, les petites entreprises dont le chiffre d'affaires annuel ne dépasse pas <strong>35 000 EUR</strong> peuvent bénéficier du <strong>régime de franchise TVA</strong>. Ce régime les dispense de facturer la TVA. Voici tout ce qu'il faut savoir.</p>

<h2>Qu'est-ce que la franchise TVA ?</h2>

<p>La franchise TVA (article 57 bis de la loi TVA luxembourgeoise) est un <strong>régime simplifié</strong> qui permet aux petites entreprises de :</p>

<ul>
    <li><strong>Ne pas facturer de TVA</strong> à leurs clients</li>
    <li><strong>Ne pas déclarer de TVA</strong> périodiquement</li>
    <li><strong>Ne pas produire de déclaration TVA annuelle</strong></li>
</ul>

<p>En contrepartie, vous ne pouvez <strong>pas déduire la TVA</strong> sur vos achats professionnels.</p>

<h2>Conditions d'éligibilité</h2>

<ul>
    <li>Chiffre d'affaires annuel HT <strong>inférieur à 35 000 EUR</strong></li>
    <li>Activité exercée au Luxembourg</li>
    <li>Ne pas avoir opté pour le régime normal volontairement</li>
    <li>Certaines activités sont exclues (immobilier, véhicules neufs)</li>
</ul>

<h2>Mentions obligatoires sur vos factures</h2>

<p>Même en franchise TVA, vos factures doivent contenir une <strong>mention spécifique</strong> :</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "TVA non applicable - Article 57 bis de la loi modifiée du 12 février 1979"
</blockquote>

<p>Vous devez également :</p>
<ul>
    <li>Ne <strong>pas mentionner de TVA</strong> (pas de ligne TVA sur la facture)</li>
    <li>Ne pas indiquer de <strong>taux de TVA</strong></li>
    <li>Indiquer uniquement le <strong>montant net</strong> (pas de distinction HT/TTC)</li>
</ul>

<h2>Avantages de la franchise</h2>

<ul>
    <li><strong>Simplicité</strong> : pas de déclaration TVA périodique</li>
    <li><strong>Compétitivité</strong> : vos prix B2C sont plus bas (pas de TVA à ajouter)</li>
    <li><strong>Trésorerie</strong> : pas de décalage entre TVA encaissée et TVA reversée</li>
    <li><strong>Moins de paperasse</strong> : administration simplifiée</li>
</ul>

<h2>Inconvénients</h2>

<ul>
    <li><strong>Pas de déduction TVA</strong> : vous payez la TVA sur vos achats sans pouvoir la récupérer</li>
    <li><strong>Désavantage B2B</strong> : vos clients entreprises ne peuvent pas déduire de TVA sur vos factures</li>
    <li><strong>Image</strong> : certains clients B2B préfèrent travailler avec des entreprises assujetties</li>
    <li><strong>Seuil contraignant</strong> : si vous dépassez 35 000 EUR, le passage est obligatoire</li>
</ul>

<h2>Quand passer au régime normal ?</h2>

<h3>Passage obligatoire</h3>
<p>Si votre chiffre d'affaires dépasse <strong>35 000 EUR sur 12 mois glissants</strong>, vous devez :</p>

<ol>
    <li>Contacter l'<strong>AED</strong> dans les 15 jours suivant le dépassement</li>
    <li>Commencer à facturer la TVA <strong>immédiatement</strong></li>
    <li>Soumettre vos déclarations TVA périodiquement</li>
</ol>

<h3>Passage volontaire</h3>
<p>Vous pouvez aussi opter pour le régime normal <strong>volontairement</strong>, même en dessous du seuil. C'est recommandé si :</p>

<ul>
    <li>Vos clients sont majoritairement des entreprises (B2B) qui déduisent la TVA</li>
    <li>Vous avez des investissements importants (équipement, véhicule) et voulez récupérer la TVA</li>
    <li>Vous approchez du seuil et préférez anticiper</li>
</ul>

<h2>Impact sur vos factures avec faktur.lu</h2>

<p>faktur.lu gère les deux régimes :</p>

<ul>
    <li><strong>Franchise TVA</strong> : les factures affichent automatiquement la mention d'exemption, sans ligne TVA</li>
    <li><strong>Régime normal</strong> : la TVA est calculée automatiquement avec le bon taux</li>
    <li><strong>Alerte seuil</strong> : faktur.lu vous prévient quand vous approchez des 35 000 EUR pour anticiper le passage</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">taux de TVA →</a></li><li><a href="/fr/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">freelance →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gérez votre franchise TVA en toute sérénité</h3>
    <p class="text-primary-800 mb-4">faktur.lu s'adapte automatiquement à votre régime TVA et vous alerte avant le dépassement du seuil de 35 000 EUR.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'delais-paiement-luxembourg-cadre-legal-2026' => <<<'HTML'
<p class="lead">Au Luxembourg, les délais de paiement sont encadrés par la loi. Que vous facturiez à une entreprise ou à une administration, voici les règles à connaître pour protéger votre trésorerie.</p>

<h2>Délais légaux de paiement</h2>

<h3>Transactions B2B (entre entreprises)</h3>

<p>La <strong>loi du 18 avril 2004</strong> relative aux délais de paiement dans les transactions commerciales fixe les règles suivantes :</p>

<ul>
    <li><strong>Délai par défaut</strong> : 30 jours à compter de la réception de la facture</li>
    <li><strong>Délai maximum contractuel</strong> : 60 jours (sauf accord spécifique)</li>
    <li>Les parties peuvent convenir d'un délai <strong>supérieur à 60 jours</strong> uniquement si cela ne constitue pas un abus manifeste</li>
</ul>

<h3>Transactions B2G (avec le secteur public)</h3>

<p>Les délais sont plus stricts pour les administrations :</p>

<ul>
    <li><strong>Délai maximum</strong> : 30 jours à compter de la réception de la facture</li>
    <li>Ce délai <strong>ne peut pas être allongé</strong> contractuellement</li>
    <li>La procédure de vérification ne peut excéder <strong>30 jours supplémentaires</strong></li>
</ul>

<h2>À partir de quand le délai court-il ?</h2>

<p>Le délai de paiement commence à courir à partir de :</p>

<ul>
    <li>La <strong>date de réception de la facture</strong> par le débiteur</li>
    <li>Ou la <strong>date de réception des biens/services</strong>, si la facture a été envoyée avant</li>
    <li>Ou la <strong>date de vérification</strong>, si un processus de vérification est prévu contractuellement</li>
</ul>

<p><strong>Conseil :</strong> mentionnez toujours la <strong>date d'échéance</strong> clairement sur votre facture. Avec faktur.lu, la date d'échéance est calculée automatiquement (30 jours par défaut, personnalisable).</p>

<h2>Intérêts de retard</h2>

<p>En cas de retard de paiement, des intérêts sont dus <strong>automatiquement</strong>, sans qu'il soit nécessaire d'envoyer une mise en demeure :</p>

<ul>
    <li><strong>Taux</strong> : taux directeur de la BCE + 8 points de pourcentage</li>
    <li><strong>Taux applicable en 2026</strong> : environ 12,5% annuel</li>
    <li>Les intérêts courent à partir du jour suivant la date d'échéance</li>
</ul>

<h2>Indemnité forfaitaire de recouvrement</h2>

<p>En plus des intérêts de retard, le créancier a droit à une <strong>indemnité forfaitaire de 40 EUR</strong> pour frais de recouvrement. Cette indemnité est due automatiquement, sans justificatif de dépenses.</p>

<p>Si les frais de recouvrement réels dépassent 40 EUR, le créancier peut réclamer le montant effectif sur présentation de justificatifs.</p>

<h2>Bonnes pratiques sur vos factures</h2>

<p>Pour vous protéger en cas de litige, mentionnez sur chaque facture :</p>

<ul>
    <li>La <strong>date d'échéance</strong> précise (pas seulement "30 jours")</li>
    <li>Les <strong>modalités de paiement</strong> (virement, avec IBAN)</li>
    <li>La mention des <strong>intérêts de retard</strong> applicables</li>
    <li>La mention de l'<strong>indemnité forfaitaire de 40 EUR</strong></li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/relancer-client-impaye-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">relancer un client →</a></li><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mentions obligatoires →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gérez vos délais de paiement avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu calcule automatiquement les dates d'échéance, détecte les retards et envoie des relances. Gardez le contrôle de votre trésorerie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
        ];
    }
};
