<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration (batch 3) for FR blog articles seeded without
 * diacritics. Same approach as batches 1-2.
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
            'excel-vs-logiciel-facturation-pourquoi-switch' => <<<'HTML'
<p class="lead">Vous utilisez encore Excel pour faire vos factures ? Vous n'êtes pas seul : <strong>40% des freelances et micro-entreprises</strong> au Luxembourg facturent avec un tableur. Mais cette pratique comporte des risques sérieux.</p>

<h2>Les limites d'Excel pour la facturation</h2>

<h3>1. Aucune conformité garantie</h3>

<p>Excel ne vérifie pas que votre facture respecte les obligations légales luxembourgeoises. Vous risquez d'oublier :</p>

<ul>
    <li>La <strong>numérotation séquentielle</strong> obligatoire (sans trou ni doublon)</li>
    <li>Les <strong>mentions légales</strong> requises (TVA, RCS, matricule)</li>
    <li>La bonne <strong>mention TVA</strong> selon le scénario (intracommunautaire, franchise, etc.)</li>
    <li>Le calcul correct de la TVA (erreurs d'arrondi fréquentes)</li>
</ul>

<h3>2. Pas d'export FAIA</h3>

<p>En cas de contrôle fiscal, l'AED peut exiger un <strong>fichier FAIA</strong> (Fichier d'Audit Informatisé). Impossible à générer depuis Excel. Vous devrez reconstituer manuellement l'ensemble de votre comptabilité — un cauchemar.</p>

<h3>3. Risque d'erreurs</h3>

<p>Avec Excel, les erreurs sont fréquentes et difficiles à détecter :</p>

<ul>
    <li><strong>Formules cassées</strong> : un copier-coller malencontreux peut fausser tous vos calculs</li>
    <li><strong>Doublons de numéros</strong> : sans contrôle automatique, vous pouvez attribuer deux fois le même numéro</li>
    <li><strong>Oubli de lignes</strong> : une facture non enregistrée fausse votre chiffre d'affaires déclaré</li>
    <li><strong>Pas de sauvegarde</strong> : un fichier corrompu ou supprimé = données perdues</li>
</ul>

<h3>4. Perte de temps</h3>

<p>Avec Excel, chaque facture demande du temps :</p>

<ul>
    <li>Recopier les informations du client à chaque facture</li>
    <li>Calculer la TVA manuellement</li>
    <li>Générer un PDF, l'enregistrer, l'envoyer par email</li>
    <li>Suivre les paiements dans un autre fichier</li>
    <li>Préparer le livre des recettes en fin de mois</li>
</ul>

<p>Un freelance passe en moyenne <strong>5 heures par mois</strong> sur la gestion administrative avec Excel. Avec un logiciel adapté, c'est <strong>moins d'une heure</strong>.</p>

<h2>Ce qu'un logiciel de facturation apporte</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Fonctionnalité</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Excel</th>
            <th class="border border-gray-300 px-4 py-2 text-center">Logiciel</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Numérotation automatique</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Calcul TVA automatique</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Mentions légales conformes</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export FAIA</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Envoi email intégré</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Suivi des paiements</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Relances automatiques</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Livre des recettes auto</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Sauvegarde automatique</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Multi-devises</td><td class="border border-gray-300 px-4 py-2 text-center">&#10060;</td><td class="border border-gray-300 px-4 py-2 text-center">&#9989;</td></tr>
    </tbody>
</table>

<h2>Comment migrer d'Excel à un logiciel</h2>

<p>La migration est plus simple que vous ne le pensez :</p>

<ol>
    <li><strong>Exportez vos clients</strong> depuis Excel au format CSV</li>
    <li><strong>Importez-les</strong> dans le logiciel (faktur.lu supporte l'import Excel/CSV avec mapping de colonnes)</li>
    <li><strong>Configurez votre entreprise</strong> (nom, TVA, adresse, logo)</li>
    <li><strong>Créez votre première facture</strong> : en 2 minutes, pas 15</li>
</ol>

<p>Vous n'avez pas besoin de ressaisir vos anciens clients un par un. L'<strong>import Excel/CSV</strong> de faktur.lu détecte automatiquement les colonnes et vous propose un mapping intelligent.</p>

<h2>Combien ça coûte ?</h2>

<p>Un logiciel de facturation adapté au Luxembourg coûte entre <strong>0 et 15 EUR/mois</strong>. C'est le prix d'un café par semaine pour :</p>

<ul>
    <li>Gagner 4+ heures par mois</li>
    <li>Éviter les erreurs de conformité</li>
    <li>Être prêt en cas de contrôle fiscal</li>
    <li>Avoir l'esprit tranquille</li>
</ul>

<p>faktur.lu propose un <strong>plan gratuit</strong> pour démarrer (5 factures/mois) et un plan Essentiel à 5 EUR/mois pour les freelances.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">choisir votre logiciel →</a></li><li><a href="/fr/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">automatiser votre facturation →</a></li><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">export FAIA →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Passez d'Excel à faktur.lu en 5 minutes</h3>
    <p class="text-primary-800 mb-4">Importez vos clients depuis Excel, créez votre première facture conforme et exportez votre FAIA. Gratuit pour commencer.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            '5-erreurs-frequentes-facture-freelance-luxembourg' => <<<'HTML'
<p class="lead">En tant que freelance au Luxembourg, vos factures sont votre vitrine professionnelle et un document légal. Pourtant, <strong>plus de 60% des factures freelance contiennent au moins une erreur</strong>. Voici les 5 plus fréquentes et comment les éviter.</p>

<h2>Erreur 1 : Numérotation non séquentielle</h2>

<p>La loi luxembourgeoise exige une <strong>numérotation strictement séquentielle</strong> de vos factures. Cela signifie :</p>

<ul>
    <li><strong>Pas de trou</strong> : si votre dernière facture est F-2026-042, la prochaine doit être F-2026-043</li>
    <li><strong>Pas de doublon</strong> : deux factures ne peuvent pas avoir le même numéro</li>
    <li><strong>Pas de modification</strong> : vous ne pouvez pas changer un numéro après émission</li>
</ul>

<p><strong>Pourquoi c'est grave ?</strong> Lors d'un contrôle fiscal, une numérotation incohérente laisse supposer que des factures ont été supprimées ou dissimulées. C'est un <strong>red flag</strong> pour l'AED.</p>

<p><strong>Solution :</strong> utilisez un logiciel qui génère automatiquement les numéros. Avec faktur.lu, la numérotation est séquentielle, sans trou et immuable.</p>

<h2>Erreur 2 : Mentions légales manquantes</h2>

<p>Une facture au Luxembourg doit obligatoirement contenir :</p>

<ul>
    <li>Le <strong>nom et l'adresse</strong> de votre entreprise</li>
    <li>Votre <strong>numéro de TVA</strong> (ou la mention d'exemption)</li>
    <li>Votre <strong>numéro RCS</strong> ou matricule</li>
    <li>Le nom et l'adresse du <strong>client</strong></li>
    <li>Le <strong>numéro de la facture</strong></li>
    <li>La <strong>date d'émission</strong></li>
    <li>La <strong>description détaillée</strong> de la prestation</li>
    <li>Le <strong>montant HT, le taux de TVA et le montant TTC</strong></li>
</ul>

<p><strong>L'erreur classique :</strong> oublier le numéro de TVA du client pour les factures intracommunautaires, ou ne pas mentionner le motif d'exonération quand la TVA est à 0%.</p>

<p><strong>Solution :</strong> faktur.lu ajoute automatiquement toutes les mentions obligatoires selon le type de client et le scénario TVA.</p>

<h2>Erreur 3 : Mauvais taux de TVA</h2>

<p>Au Luxembourg, il existe <strong>4 taux de TVA</strong> :</p>

<ul>
    <li><strong>17%</strong> : taux normal (la plupart des prestations de services)</li>
    <li><strong>14%</strong> : taux intermédiaire (certains services spécifiques)</li>
    <li><strong>8%</strong> : taux réduit (électricité, gaz, coiffure...)</li>
    <li><strong>3%</strong> : taux super-réduit (alimentation, livres, presse)</li>
</ul>

<p><strong>Les erreurs courantes :</strong></p>

<ul>
    <li>Appliquer 20% (taux français) au lieu de 17%</li>
    <li>Facturer avec TVA à un client intracommunautaire (autoliquidation)</li>
    <li>Oublier la TVA pour un client luxembourgeois B2C</li>
    <li>Ne pas mentionner la franchise TVA quand on est en dessous du seuil</li>
</ul>

<p><strong>Solution :</strong> faktur.lu détermine automatiquement le bon taux et la bonne mention selon le pays et le type de client.</p>

<h2>Erreur 4 : Pas de conditions de paiement</h2>

<p>Beaucoup de freelances oublient de préciser les <strong>modalités de paiement</strong> sur leurs factures :</p>

<ul>
    <li><strong>Date d'échéance</strong> : sans échéance précisée, le délai légal est de 30 jours, mais votre client peut l'ignorer</li>
    <li><strong>Moyen de paiement</strong> : indiquez votre IBAN pour faciliter le virement</li>
    <li><strong>Pénalités de retard</strong> : mentionnez les intérêts applicables (taux BCE + 8 points)</li>
    <li><strong>Indemnité forfaitaire</strong> : les 40 EUR pour frais de recouvrement</li>
</ul>

<p><strong>Pourquoi c'est important :</strong> sans conditions claires, vous n'avez aucune base légale pour réclamer des intérêts de retard en cas d'impayé.</p>

<h2>Erreur 5 : Modifier une facture finalisée</h2>

<p>Une fois qu'une facture est envoyée au client, elle est <strong>immuable</strong>. Vous ne pouvez pas :</p>

<ul>
    <li>Changer le montant</li>
    <li>Modifier le client</li>
    <li>Supprimer la facture</li>
    <li>Changer le numéro</li>
</ul>

<p>Si vous avez fait une erreur, la <strong>seule solution légale</strong> est d'émettre une <strong>note de crédit</strong> (avoir) qui annule la facture erronée, puis de créer une nouvelle facture corrigée.</p>

<p><strong>L'erreur classique :</strong> modifier le fichier Excel ou Word de la facture et renvoyer une "version corrigée". En cas de contrôle fiscal, cela peut être considéré comme de la <strong>falsification</strong>.</p>

<h2>Bonus : la checklist de la facture parfaite</h2>

<p>Avant d'envoyer chaque facture, vérifiez :</p>

<ul>
    <li>&#9745; Numéro séquentiel correct</li>
    <li>&#9745; Date d'émission et date d'échéance</li>
    <li>&#9745; Toutes les mentions légales présentes</li>
    <li>&#9745; Bon taux de TVA selon le scénario</li>
    <li>&#9745; IBAN et conditions de paiement</li>
    <li>&#9745; Montants HT, TVA et TTC corrects</li>
    <li>&#9745; Description claire de la prestation</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mentions obligatoires →</a></li><li><a href="/fr/blog/note-de-credit-luxembourg-comment-etablir" class="text-primary-500 hover:text-primary-600 text-sm">note de crédit →</a></li><li><a href="/fr/blog/guide-complet-facturation-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">guide complet →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez sans erreur avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu vérifie automatiquement chaque facture : numérotation, mentions légales, TVA, immuabilité. Zéro risque d'erreur.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'facturer-etranger-depuis-luxembourg' => <<<'HTML'
<p class="lead">Vous êtes basé au Luxembourg et vous facturez des clients à l'étranger ? Les règles de TVA varient considérablement selon la zone géographique et le type de client. Voici un guide clair pour chaque situation.</p>

<h2>Cas 1 : Client entreprise dans l'UE (B2B intracommunautaire)</h2>

<p>C'est le cas le plus fréquent pour les freelances et PME luxembourgeoises. Exemple : un consultant luxembourgeois facture une société allemande.</p>

<h3>Règles à appliquer</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Le client déclare la TVA dans son pays (<strong>autoliquidation / reverse charge</strong>)</li>
    <li>Vous devez vérifier le numéro TVA du client sur <strong>VIES</strong></li>
    <li>Mention obligatoire : <em>"Autoliquidation - Article 44 de la directive 2006/112/CE"</em></li>
</ul>

<h3>Documents nécessaires</h3>
<ul>
    <li>Votre numéro TVA luxembourgeois sur la facture</li>
    <li>Le numéro TVA du client (vérifié VIES)</li>
    <li>Déclaration dans l'<strong>état récapitulatif</strong> TVA mensuel/trimestriel</li>
</ul>

<h2>Cas 2 : Client particulier dans l'UE (B2C)</h2>

<p>Vous vendez à un particulier dans un autre pays UE. Les règles dépendent du type de prestation :</p>

<h3>Services classiques (conseil, design, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA luxembourgeoise (17%)</strong></li>
    <li>Pas d'autoliquidation pour les particuliers</li>
</ul>

<h3>Services électroniques (SaaS, formations en ligne, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA du pays du client</strong></li>
    <li>Via le régime <strong>OSS (One-Stop Shop)</strong> : une seule déclaration pour tous les pays UE</li>
    <li>Seuil : 10 000 EUR/an de ventes B2C dans l'UE</li>
</ul>

<h2>Cas 3 : Client hors UE (export)</h2>

<p>Vous facturez un client en Suisse, aux États-Unis, au Royaume-Uni ou dans tout autre pays hors UE.</p>

<h3>Services</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0% TVA)</strong></li>
    <li>Mention : <em>"Prestation de services hors du champ d'application de la TVA luxembourgeoise"</em></li>
    <li>Pas d'état récapitulatif nécessaire</li>
</ul>

<h3>Biens (export)</h3>
<ul>
    <li>Vous facturez <strong>hors taxes</strong> (exportation exonérée)</li>
    <li>Vous devez conserver la <strong>preuve d'exportation</strong> (document douanier)</li>
    <li>Mention : <em>"Exportation exonérée - Article 146 directive 2006/112/CE"</em></li>
</ul>

<h2>Cas spécial : la Suisse</h2>

<p>La Suisse n'est pas dans l'UE. Cependant, de nombreux freelances luxembourgeois facturent des clients suisses. Les règles :</p>

<ul>
    <li>Services : facturez <strong>HT</strong>, le client suisse déclare la TVA dans le cadre du mécanisme d'importation de services</li>
    <li>Pas d'état récapitulatif (réservé aux échanges intra-UE)</li>
    <li>Facturez en <strong>EUR ou CHF</strong> selon l'accord avec le client</li>
</ul>

<h2>Tableau récapitulatif</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Scénario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mention</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxembourg</td><td class="border border-gray-300 px-4 py-2">17%</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE</td><td class="border border-gray-300 px-4 py-2">0% (autoliquidation)</td><td class="border border-gray-300 px-4 py-2">Art. 44 directive</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (services)</td><td class="border border-gray-300 px-4 py-2">17% LU</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (électronique)</td><td class="border border-gray-300 px-4 py-2">TVA pays client</td><td class="border border-gray-300 px-4 py-2">Régime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B hors UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Hors champ</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C hors UE</td><td class="border border-gray-300 px-4 py-2">0%</td><td class="border border-gray-300 px-4 py-2">Hors champ</td></tr>
    </tbody>
</table>

<h2>Devises et taux de change</h2>

<p>Vous pouvez facturer en <strong>devises étrangères</strong> (USD, CHF, GBP), mais pour votre déclaration TVA, vous devrez convertir en EUR au <strong>taux de change du jour de la facture</strong> (taux BCE).</p>

<p>faktur.lu supporte la facturation <strong>multi-devises</strong> et conserve le taux de change utilisé pour chaque facture.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">TVA intracommunautaire →</a></li><li><a href="/fr/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">TVA au Luxembourg →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez à l'international en conformité</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement le scénario TVA selon le pays et le type de client, et applique les bonnes mentions. Multi-devises inclus.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'relancer-client-impaye-luxembourg' => <<<'HTML'
<p class="lead">Les factures impayées sont le cauchemar de tout entrepreneur. Au Luxembourg, <strong>30% des factures sont payées en retard</strong>. Voici comment gérer les relances efficacement, du rappel amical à la mise en demeure.</p>

<h2>Étape 1 : Le rappel amical (J+7 après échéance)</h2>

<p>Un simple oubli ? C'est souvent le cas. Le premier rappel doit être <strong>courtois et professionnel</strong> :</p>

<ul>
    <li>Envoyez un email rappelant le numéro et le montant de la facture</li>
    <li>Joignez une copie de la facture originale</li>
    <li>Proposez un nouveau délai de paiement si nécessaire</li>
    <li>Restez factuel et cordial</li>
</ul>

<p><strong>Conseil :</strong> avec faktur.lu, vous pouvez automatiser ce premier rappel. Le système détecte les factures en retard et envoie un email de relance automatique.</p>

<h2>Étape 2 : La relance formelle (J+15)</h2>

<p>Si le premier rappel reste sans réponse, envoyez une <strong>relance plus formelle</strong> :</p>

<ul>
    <li>Mentionnez clairement le retard de paiement</li>
    <li>Rappelez les conditions de paiement convenues</li>
    <li>Indiquez que des intérêts de retard pourront être appliqués</li>
    <li>Fixez un délai précis (ex: 8 jours)</li>
</ul>

<h2>Étape 3 : La mise en demeure (J+30)</h2>

<p>La mise en demeure est un document formel qui constitue une <strong>preuve juridique</strong>. Elle doit être envoyée par <strong>lettre recommandée avec accusé de réception</strong> et contenir :</p>

<ul>
    <li>La mention <strong>"Mise en demeure"</strong> en objet</li>
    <li>Le détail des factures impayées (numéros, montants, dates)</li>
    <li>Le montant total dû (principal + intérêts de retard éventuels)</li>
    <li>Un <strong>délai de 8 jours</strong> pour régulariser</li>
    <li>La mention que vous vous réservez le droit d'engager des poursuites</li>
</ul>

<h2>Les intérêts de retard au Luxembourg</h2>

<p>Au Luxembourg, les intérêts de retard sont régis par la <strong>loi du 18 avril 2004</strong> sur les délais de paiement :</p>

<ul>
    <li><strong>Transactions B2B</strong> : le taux d'intérêt de retard est le taux de la BCE + 8 points (soit environ 12% en 2026)</li>
    <li><strong>Transactions B2G</strong> : même taux, mais délai de paiement maximum de 30 jours</li>
    <li><strong>Indemnité forfaitaire</strong> : 40 EUR pour frais de recouvrement (sans justificatif)</li>
</ul>

<h2>Étape 4 : Le recouvrement (J+60)</h2>

<p>Si toutes les relances échouent, plusieurs options s'offrent à vous :</p>

<ul>
    <li><strong>Société de recouvrement</strong> : elle se charge des démarches moyennant une commission (15-25%)</li>
    <li><strong>Injonction de payer</strong> : procédure simplifiée devant le juge de paix (pour les créances < 15 000 EUR)</li>
    <li><strong>Assignation au tribunal</strong> : pour les montants plus importants</li>
    <li><strong>Médiation commerciale</strong> : solution alternative, plus rapide et moins coûteuse</li>
</ul>

<h2>Bonnes pratiques pour éviter les impayés</h2>

<ul>
    <li><strong>Facturez rapidement</strong> : plus vous attendez, plus le risque d'impayé augmente</li>
    <li><strong>Conditions claires</strong> : mentionnez les délais et modalités de paiement sur chaque facture</li>
    <li><strong>Acompte</strong> : demandez un acompte de 30-50% pour les grosses prestations</li>
    <li><strong>Relances automatiques</strong> : utilisez un logiciel comme faktur.lu pour ne jamais oublier de relancer</li>
    <li><strong>Vérifiez la solvabilité</strong> : pour les nouveaux clients, consultez le RCS Luxembourg</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/delais-paiement-luxembourg-cadre-legal-2026" class="text-primary-500 hover:text-primary-600 text-sm">délais de paiement légaux →</a></li><li><a href="/fr/blog/automatiser-facturation-7-conseils-gagner-temps" class="text-primary-500 hover:text-primary-600 text-sm">automatiser →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Automatisez vos relances avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement les factures en retard et envoie des relances par email. Ne laissez plus aucune facture impayée tomber dans l'oubli.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
        ];
    }
};
