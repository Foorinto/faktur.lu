<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration (batch 2/2 — heavy articles) for FR blog articles
 * seeded without diacritics. Same approach as batch 1. Also corrects the
 * non-existent "FID-Manager" export reference (replaced by Sage BOB 50 /
 * Sage 100 / CSV) in the software-comparison article, consistent with the
 * earlier partners-page correction.
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
            'tva-intracommunautaire-guide-entreprises-luxembourgeoises' => <<<'HTML'
<p class="lead">Vous facturez des clients dans d'autres pays de l'Union européenne ? La <strong>TVA intracommunautaire</strong> suit des règles spécifiques que tout entrepreneur luxembourgeois doit maîtriser. Ce guide vous explique tout.</p>

<h2>Qu'est-ce que la TVA intracommunautaire ?</h2>

<p>La TVA intracommunautaire est le régime de TVA qui s'applique aux échanges de biens et services entre entreprises situées dans différents pays de l'<strong>Union européenne</strong>. Le principe fondamental est l'<strong>autoliquidation</strong> : c'est l'acheteur, et non le vendeur, qui déclare et paie la TVA dans son pays.</p>

<h2>Le numéro de TVA intracommunautaire</h2>

<p>Au Luxembourg, votre numéro de TVA intracommunautaire a le format <strong>LU + 8 chiffres</strong> (exemple : LU12345678). Ce numéro est :</p>

<ul>
    <li>Attribué par l'<strong>AED</strong> (Administration de l'enregistrement, des domaines et de la TVA)</li>
    <li>Obligatoire pour toute transaction intracommunautaire</li>
    <li>Vérifiable sur le <strong>système VIES</strong> de la Commission européenne</li>
</ul>

<p><strong>Conseil :</strong> faktur.lu vérifie automatiquement les numéros TVA via VIES lorsque vous ajoutez un client intracommunautaire.</p>

<h2>Règles de facturation intracommunautaire</h2>

<h3>Vente de services B2B (cas le plus courant)</h3>

<p>Quand vous vendez un service à une entreprise située dans un autre pays UE :</p>

<ol>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Vous mentionnez sur la facture : <strong>"Autoliquidation - Article 44 de la directive 2006/112/CE"</strong></li>
    <li>Vous indiquez votre numéro TVA <strong>et</strong> celui du client</li>
    <li>Le client déclare la TVA dans son propre pays (mécanisme de "reverse charge")</li>
</ol>

<h3>Vente de biens B2B</h3>

<p>Pour les livraisons de biens à une entreprise UE :</p>

<ol>
    <li>Vous facturez <strong>hors taxes</strong> (livraison intracommunautaire exonérée)</li>
    <li>Vous mentionnez : <strong>"Livraison intracommunautaire exonérée - Article 138 directive 2006/112/CE"</strong></li>
    <li>Vous devez prouver que les biens ont quitté le Luxembourg</li>
    <li>La transaction doit figurer dans votre <strong>état récapitulatif</strong> (déclaration intracommunautaire)</li>
</ol>

<h3>Vente à un particulier (B2C)</h3>

<p>Attention, les règles sont différentes pour les ventes aux particuliers dans l'UE :</p>

<ul>
    <li><strong>Services électroniques</strong> : TVA du pays du client (régime OSS)</li>
    <li><strong>Ventes à distance de biens</strong> : TVA luxembourgeoise jusqu'au seuil de 10 000 EUR, puis TVA du pays du client</li>
    <li><strong>Services classiques</strong> : généralement TVA luxembourgeoise</li>
</ul>

<h2>Déclarations obligatoires</h2>

<p>En tant qu'entreprise luxembourgeoise effectuant des opérations intracommunautaires, vous devez :</p>

<ul>
    <li><strong>Déclaration TVA périodique</strong> : déclarer vos opérations intracommunautaires dans les cases appropriées</li>
    <li><strong>État récapitulatif</strong> : déclaration mensuelle ou trimestrielle listant toutes vos ventes intracommunautaires par client et par pays</li>
    <li><strong>Intrastat</strong> : déclaration statistique pour les mouvements de biens dépassant certains seuils</li>
</ul>

<h2>Validation VIES : pourquoi c'est crucial</h2>

<p>Avant de facturer HT à un client UE, vous <strong>devez vérifier</strong> que son numéro de TVA est valide via le système <strong>VIES (VAT Information Exchange System)</strong>. Si le numéro est invalide :</p>

<ul>
    <li>Vous devez facturer <strong>avec TVA luxembourgeoise</strong></li>
    <li>Vous risquez un <strong>redressement fiscal</strong> si vous facturez HT sans vérification</li>
    <li>Conservez une <strong>preuve de vérification VIES</strong> (capture d'écran ou log)</li>
</ul>

<p>faktur.lu vérifie automatiquement chaque numéro TVA intracommunautaire et conserve un log de la validation.</p>

<h2>Cas pratiques courants</h2>

<h3>Consultant luxembourgeois facturant un client allemand</h3>
<p>Vous facturez HT avec mention d'autoliquidation. Le client allemand déclare la TVA allemande (19%) dans sa propre déclaration. Vous déclarez l'opération dans votre état récapitulatif.</p>

<h3>Agence web luxembourgeoise facturant un client français</h3>
<p>Même principe : facture HT, autoliquidation. Le client français déclare 20% de TVA française. Vous devez vérifier son numéro TVA sur VIES avant de facturer.</p>

<h3>E-commerce luxembourgeois vendant à un particulier belge</h3>
<p>Si vos ventes B2C dans l'UE dépassent 10 000 EUR/an, vous devez appliquer la TVA du pays du client (21% en Belgique) via le régime <strong>OSS (One-Stop Shop)</strong>.</p>

<h2>Mentions obligatoires sur la facture</h2>

<p>Toute facture intracommunautaire doit mentionner :</p>

<ul>
    <li>Votre numéro de TVA luxembourgeois</li>
    <li>Le numéro de TVA du client</li>
    <li>La mention légale d'exonération (autoliquidation ou livraison intracommunautaire)</li>
    <li>Le montant HT et la mention "TVA 0%"</li>
</ul>

<p>faktur.lu détecte automatiquement le scénario TVA selon le pays et le type de client, et applique les mentions légales appropriées.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">TVA au Luxembourg →</a></li><li><a href="/fr/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">facturer à l'étranger →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez dans toute l'UE en conformité</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement les scénarios TVA intracommunautaires, vérifie les numéros TVA via VIES et applique les bonnes mentions légales.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'choisir-logiciel-facturation-luxembourg-comparatif' => <<<'HTML'
<p class="lead">Vous cherchez un logiciel de facturation adapté au Luxembourg ? Le marché propose de nombreuses solutions, mais toutes ne sont pas conformes à la législation luxembourgeoise. Voici les <strong>critères essentiels</strong> pour faire le bon choix.</p>

<h2>Critère 1 : Conformité luxembourgeoise</h2>

<p>C'est le critère le plus important. Un logiciel adapté au Luxembourg <strong>doit</strong> proposer :</p>

<ul>
    <li><strong>Export FAIA</strong> : le fichier d'audit informatisé exigé par l'AED en cas de contrôle fiscal. Sans FAIA, vous êtes en infraction.</li>
    <li><strong>Numérotation séquentielle</strong> : sans trou ni doublon, conforme à la loi luxembourgeoise</li>
    <li><strong>Mentions légales automatiques</strong> : TVA, RCS, matricule selon le scénario</li>
    <li><strong>Taux de TVA luxembourgeois</strong> : 17%, 14%, 8% et 3% avec calcul automatique</li>
    <li><strong>Gestion de la franchise TVA</strong> : pour les entreprises sous le seuil de 35 000 EUR</li>
</ul>

<p><strong>Attention :</strong> la plupart des logiciels internationaux (QuickBooks, FreshBooks, Wave) ne supportent pas l'export FAIA ni les spécificités luxembourgeoises.</p>

<h2>Critère 2 : Fonctionnalités essentielles</h2>

<p>Au-delà de la conformité, un bon logiciel de facturation doit offrir :</p>

<ul>
    <li><strong>Factures et devis</strong> avec conversion en 1 clic</li>
    <li><strong>Notes de crédit</strong> liées aux factures d'origine</li>
    <li><strong>Gestion des clients</strong> avec historique de facturation</li>
    <li><strong>Multi-devises</strong> (EUR, USD, CHF, GBP)</li>
    <li><strong>Envoi par email</strong> intégré avec suivi d'ouverture</li>
    <li><strong>Relances automatiques</strong> pour les factures impayées</li>
    <li><strong>Exports comptables</strong> (Sage BOB 50, Sage 100, CSV)</li>
    <li><strong>Livre des recettes</strong> généré automatiquement</li>
</ul>

<h2>Critère 3 : Simplicité d'utilisation</h2>

<p>Vous êtes entrepreneur, pas comptable. Le logiciel doit être :</p>

<ul>
    <li><strong>Intuitif</strong> : créer une facture en moins de 2 minutes</li>
    <li><strong>Accessible en ligne</strong> : pas d'installation, accessible depuis n'importe où</li>
    <li><strong>Mobile-friendly</strong> : pouvoir créer une facture depuis votre téléphone</li>
    <li><strong>En français</strong> : interface et support dans votre langue</li>
</ul>

<h2>Critère 4 : Prix adapté</h2>

<p>Les prix varient énormément selon les solutions :</p>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Gamme</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Prix mensuel</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Public cible</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Gratuit</td><td class="border border-gray-300 px-4 py-2">0 EUR</td><td class="border border-gray-300 px-4 py-2">Test / micro-activité</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Essentiel</td><td class="border border-gray-300 px-4 py-2">5 - 15 EUR</td><td class="border border-gray-300 px-4 py-2">Freelances / indépendants</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Pro</td><td class="border border-gray-300 px-4 py-2">15 - 30 EUR</td><td class="border border-gray-300 px-4 py-2">PME / équipes</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Entreprise</td><td class="border border-gray-300 px-4 py-2">50+ EUR</td><td class="border border-gray-300 px-4 py-2">Grandes structures</td></tr>
    </tbody>
</table>

<p><strong>Notre conseil :</strong> évitez les solutions trop bon marché qui n'incluent pas le FAIA, et les solutions trop chères si vous êtes freelance. Un bon rapport qualité/prix se situe entre 5 et 15 EUR/mois.</p>

<h2>Critère 5 : Sécurité et RGPD</h2>

<ul>
    <li><strong>Hébergement en Europe</strong> : vos données doivent rester dans l'UE (RGPD)</li>
    <li><strong>Sauvegardes automatiques</strong> : pas de perte de données</li>
    <li><strong>Authentification 2FA</strong> : sécurité renforcée pour votre compte</li>
    <li><strong>Chiffrement</strong> : données chiffrées en transit et au repos</li>
</ul>

<h2>Critère 6 : Intégration comptable</h2>

<p>Si vous travaillez avec une fiduciaire luxembourgeoise, vérifiez que le logiciel propose :</p>

<ul>
    <li><strong>Export Sage BOB 50</strong> : le logiciel le plus utilisé par les fiduciaires au Luxembourg</li>
    <li><strong>Export Sage 100</strong> : autre format répandu</li>
    <li><strong>Portail comptable</strong> : accès en lecture seule pour votre comptable</li>
    <li><strong>Export CSV/Excel</strong> : format universel en dernier recours</li>
</ul>

<h2>Pourquoi faktur.lu ?</h2>

<p>faktur.lu a été conçu <strong>spécifiquement pour le Luxembourg</strong>. Il répond à tous les critères ci-dessus :</p>

<ul>
    <li>Export FAIA natif, conforme au format XML 2.01 de l'AED</li>
    <li>TVA luxembourgeoise avec gestion de la franchise</li>
    <li>Peppol B2G intégré pour le secteur public</li>
    <li>Exports Sage BOB 50, Sage 100 et CSV</li>
    <li>Hébergé en Europe (RGPD conforme)</li>
    <li>Interface en 5 langues (FR, EN, DE, LB, PT)</li>
    <li>Plan gratuit pour démarrer</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/excel-vs-logiciel-facturation-pourquoi-switch" class="text-primary-500 hover:text-primary-600 text-sm">Excel vs logiciel →</a></li><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">export FAIA →</a></li><li><a href="/fr/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Le logiciel de facturation fait pour le Luxembourg</h3>
    <p class="text-primary-800 mb-4">faktur.lu est le seul logiciel de facturation conçu 100% pour le marché luxembourgeois. FAIA, Peppol, TVA, multi-langues : tout est intégré.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'livre-des-recettes-luxembourg-obligations-modele' => <<<'HTML'
<p class="lead">Le <strong>livre des recettes</strong> est un document comptable obligatoire au Luxembourg pour tous les contribuables soumis à la TVA. Il recense l'ensemble des recettes encaissées au cours d'un exercice fiscal. Voici tout ce que vous devez savoir.</p>

<h2>Qu'est-ce que le livre des recettes ?</h2>

<p>Le livre des recettes est un registre chronologique qui répertorie toutes les factures émises et les paiements reçus par votre entreprise. Il fait partie des <strong>obligations comptables</strong> définies par la loi fiscale luxembourgeoise.</p>

<p>Contrairement au grand livre comptable, le livre des recettes est un document simplifié, principalement destiné aux <strong>indépendants, freelances et petites entreprises</strong> qui ne tiennent pas une comptabilité en partie double.</p>

<h2>Qui doit tenir un livre des recettes ?</h2>

<ul>
    <li><strong>Tous les assujettis à la TVA</strong> au Luxembourg</li>
    <li><strong>Les indépendants et professions libérales</strong></li>
    <li><strong>Les auto-entrepreneurs</strong> même en franchise de TVA (en dessous du seuil de 35 000 EUR)</li>
    <li><strong>Les sociétés</strong> (en complément de leur comptabilité)</li>
</ul>

<h2>Que doit contenir le livre des recettes ?</h2>

<p>Chaque entrée du livre des recettes doit mentionner :</p>

<ul>
    <li><strong>La date</strong> de la facture ou du paiement</li>
    <li><strong>Le numéro de facture</strong> (numérotation séquentielle)</li>
    <li><strong>Le nom du client</strong></li>
    <li><strong>La description</strong> de la prestation ou du bien vendu</li>
    <li><strong>Le montant hors taxes (HT)</strong></li>
    <li><strong>Le taux de TVA appliqué</strong> (17%, 14%, 8% ou 3%)</li>
    <li><strong>Le montant de la TVA</strong></li>
    <li><strong>Le montant toutes taxes comprises (TTC)</strong></li>
</ul>

<h2>Format et conservation</h2>

<p>Le livre des recettes peut être tenu :</p>

<ul>
    <li><strong>Au format papier</strong> : dans un cahier dédié, sans ratures ni blancs</li>
    <li><strong>Au format numérique</strong> : via un logiciel de facturation, un tableur ou un fichier PDF</li>
</ul>

<p>Important : le livre des recettes doit être conservé pendant <strong>10 ans</strong> à compter de la fin de l'exercice fiscal, conformément à l'article 60 de la loi générale des impôts (Abgabenordnung).</p>

<h2>Générer son livre des recettes avec faktur.lu</h2>

<p>Avec <strong>faktur.lu</strong>, votre livre des recettes est généré automatiquement à partir de vos factures :</p>

<ol>
    <li>Allez dans <strong>Comptabilité > Livre des recettes</strong></li>
    <li>Sélectionnez la période souhaitée (mois, trimestre, année)</li>
    <li>Consultez le détail de chaque facture avec ventilation TVA</li>
    <li>Exportez en <strong>PDF</strong> ou <strong>CSV</strong> pour votre comptable</li>
</ol>

<p>Toutes les mentions obligatoires sont incluses automatiquement, et le livre est conforme aux exigences de l'<strong>Administration des contributions directes (ACD)</strong>.</p>

<h2>Livre des recettes vs export FAIA</h2>

<p>Ne confondez pas le livre des recettes avec l'export FAIA :</p>

<ul>
    <li>Le <strong>livre des recettes</strong> est un document de suivi courant, utilisé au quotidien et transmis à votre comptable</li>
    <li>Le <strong>FAIA</strong> est un fichier XML exigé uniquement en cas de contrôle fiscal par l'AED (Administration de l'enregistrement, des domaines et de la TVA)</li>
</ul>

<p>Les deux sont générés automatiquement par faktur.lu.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">export FAIA →</a></li><li><a href="/fr/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">archivage →</a></li><li><a href="/fr/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">contrôle fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Générez votre livre des recettes en 1 clic</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère automatiquement votre livre des recettes conforme à la législation luxembourgeoise, avec export PDF et CSV.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
        ];
    }
};
