<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration (batch 1/2) for FR blog articles seeded without
 * diacritics. Prose rewritten in correct French (accents, cedillas,
 * context-dependent "a"→"à" / "ou"→"où", plus a few grammar fixes like
 * "généré"→"génère"). HTML tags, classes and links preserved verbatim.
 *
 * Idempotent: content is set to a fixed corrected value.
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
        // No-op: reverting would re-introduce unaccented content.
    }

    private function corrections(): array
    {
        return [
            'note-de-credit-luxembourg-comment-etablir' => <<<'HTML'
<p class="lead">Une erreur sur une facture ? Un client retourne un produit ? Vous devez émettre une <strong>note de crédit</strong> (aussi appelée "avoir"). Voici comment l'établir correctement au Luxembourg.</p>

<h2>Qu'est-ce qu'une note de crédit ?</h2>

<p>Une note de crédit est un document comptable qui <strong>annule ou corrige partiellement une facture</strong> déjà émise. Contrairement à une facture, les montants d'une note de crédit sont <strong>négatifs</strong>.</p>

<p><strong>Important :</strong> au Luxembourg, il est <strong>interdit de modifier ou supprimer une facture</strong> une fois qu'elle a été finalisée (principe d'immuabilité). La seule manière de corriger une erreur est d'émettre une note de crédit.</p>

<h2>Quand émettre une note de crédit ?</h2>

<ul>
    <li><strong>Erreur de facturation</strong> : montant incorrect, TVA erronée, mauvais client</li>
    <li><strong>Retour de marchandise</strong> : le client renvoie tout ou partie de la commande</li>
    <li><strong>Remise commerciale</strong> : accord sur un rabais après facturation</li>
    <li><strong>Annulation de prestation</strong> : le service n'a pas été rendu</li>
    <li><strong>Insolvabilité du client</strong> : créance irrécupérable</li>
</ul>

<h2>Mentions obligatoires</h2>

<p>Une note de crédit au Luxembourg doit contenir les mêmes mentions qu'une facture, plus :</p>

<ul>
    <li>La mention <strong>"Note de crédit"</strong> ou <strong>"Avoir"</strong> clairement visible</li>
    <li>La <strong>référence à la facture d'origine</strong> (numéro et date)</li>
    <li>Le <strong>motif</strong> de la note de crédit</li>
    <li>Les montants en <strong>négatif</strong> (ou avec la mention "à déduire")</li>
    <li>La ventilation TVA (mêmes taux que la facture d'origine)</li>
    <li>Une <strong>numérotation spécifique</strong> (ex: NC-2026-001 ou AV-2026-001)</li>
</ul>

<h2>Impact sur la TVA</h2>

<p>La note de crédit a un impact direct sur votre déclaration TVA :</p>

<ul>
    <li>La TVA de la note de crédit vient en <strong>déduction de la TVA collectée</strong></li>
    <li>Elle doit être déclarée dans la <strong>même période</strong> que celle où elle est émise</li>
    <li>Si le client a déjà déduit la TVA, il devra <strong>régulariser sa propre déclaration</strong></li>
</ul>

<h2>Créer une note de crédit avec faktur.lu</h2>

<p>Avec faktur.lu, la création d'une note de crédit est simple et sécurisée :</p>

<ol>
    <li>Ouvrez la facture d'origine</li>
    <li>Cliquez sur <strong>"Créer une note de crédit"</strong></li>
    <li>Sélectionnez le motif</li>
    <li>Choisissez une annulation totale ou partielle</li>
    <li>La note de crédit est générée automatiquement avec toutes les mentions requises</li>
</ol>

<p>La référence à la facture d'origine est ajoutée automatiquement, et les montants sont calculés en négatif. Votre livre des recettes et votre export FAIA sont mis à jour instantanément.</p>

<h2>Différence entre note de crédit et avoir</h2>

<p>En pratique, les termes <strong>"note de crédit"</strong> et <strong>"avoir"</strong> sont synonymes au Luxembourg. Le terme officiel utilisé par l'administration est "note de crédit" (Gutschrift en allemand), mais "avoir" est couramment utilisé en français.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mentions obligatoires →</a></li><li><a href="/fr/blog/guide-complet-facturation-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">guide de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Créez vos notes de crédit en 1 clic</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère automatiquement vos notes de crédit avec toutes les mentions obligatoires, liées à la facture d'origine et conformes à la législation luxembourgeoise.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'archivage-factures-luxembourg-duree-legale-format' => <<<'HTML'
<p class="lead">L'archivage des factures est une obligation légale au Luxembourg. Toute entreprise doit conserver ses documents comptables pendant une durée minimale de <strong>10 ans</strong>. Voici les règles à connaître pour être en conformité.</p>

<h2>Durée légale de conservation</h2>

<p>Selon l'article 60 de l'Abgabenordnung (AO) et le Code de commerce luxembourgeois, les documents comptables doivent être conservés pendant :</p>

<ul>
    <li><strong>10 ans</strong> pour les factures (émises et reçues)</li>
    <li><strong>10 ans</strong> pour les livres de comptabilité</li>
    <li><strong>10 ans</strong> pour les pièces justificatives</li>
    <li><strong>10 ans</strong> pour la correspondance commerciale</li>
</ul>

<p>Le délai court à partir de la <strong>fin de l'exercice fiscal</strong> auquel se rapporte le document. Une facture émise le 15 mars 2026 devra donc être conservée jusqu'au 31 décembre 2036.</p>

<h2>Formats d'archivage acceptés</h2>

<p>L'administration fiscale luxembourgeoise accepte deux types d'archivage :</p>

<h3>Archivage papier</h3>
<p>Les originaux papier doivent être conservés dans un endroit sec et accessible. Les factures ne doivent pas être altérées.</p>

<h3>Archivage numérique</h3>
<p>L'archivage électronique est autorisé sous certaines conditions :</p>

<ul>
    <li>Le format doit garantir l'<strong>intégrité</strong> du document (pas de modification possible)</li>
    <li>Le document doit être <strong>lisible</strong> pendant toute la durée de conservation</li>
    <li>Le format <strong>PDF/A</strong> (ISO 19005) est recommandé car il garantit la pérennité</li>
    <li>Une <strong>empreinte numérique</strong> (hash) peut prouver que le document n'a pas été modifié</li>
</ul>

<h2>Pourquoi le format PDF/A ?</h2>

<p>Le <strong>PDF/A</strong> est une norme ISO spécifiquement conçue pour l'archivage à long terme. Contrairement à un PDF standard :</p>

<ul>
    <li>Il <strong>embarque toutes les polices</strong> utilisées (pas de dépendance externe)</li>
    <li>Il <strong>interdit le JavaScript</strong> et les éléments multimédia</li>
    <li>Il garantit que le document sera <strong>lisible dans 10, 20 ou 50 ans</strong></li>
    <li>Il est <strong>reconnu par les administrations fiscales</strong> européennes</li>
</ul>

<h2>Archiver ses factures avec faktur.lu</h2>

<p>Le plan Pro de faktur.lu inclut l'<strong>archivage automatique en PDF/A</strong> :</p>

<ol>
    <li>Chaque facture finalisée est automatiquement archivée en PDF/A</li>
    <li>Une empreinte numérique (checksum SHA-256) est calculée</li>
    <li>Les archives sont conservées de manière sécurisée pendant 10 ans</li>
    <li>Vous pouvez télécharger vos archives à tout moment</li>
</ol>

<h2>Risques en cas de non-conformité</h2>

<p>En cas de contrôle fiscal, l'absence de factures ou un archivage non conforme peut entraîner :</p>

<ul>
    <li>Le <strong>rejet des déductions de TVA</strong> sur les factures manquantes</li>
    <li>Des <strong>pénalités financières</strong></li>
    <li>Une <strong>estimation d'office</strong> du chiffre d'affaires par l'administration</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">fichier FAIA →</a></li><li><a href="/fr/blog/controle-fiscal-luxembourg-comment-preparer" class="text-primary-500 hover:text-primary-600 text-sm">contrôle fiscal →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Archivage automatique avec faktur.lu</h3>
    <p class="text-primary-800 mb-4">Ne vous souciez plus de l'archivage. faktur.lu archive automatiquement vos factures en PDF/A avec empreinte numérique, conforme pendant 10 ans.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,

            'factur-x-zugferd-facturation-electronique-europeenne' => <<<'HTML'
<p class="lead"><strong>Factur-X</strong> (appelé <strong>ZUGFeRD</strong> en Allemagne) est le standard de facturation électronique adopté par la France et l'Allemagne, et en cours d'adoption dans toute l'Union européenne. Voici tout ce que vous devez savoir.</p>

<h2>Qu'est-ce que Factur-X ?</h2>

<p>Factur-X est un format de facture <strong>"hybride"</strong> qui combine deux éléments dans un seul fichier PDF :</p>

<ul>
    <li>La <strong>partie visuelle</strong> : le PDF lisible que vous voyez à l'écran et pouvez imprimer</li>
    <li>La <strong>partie structurée</strong> : un fichier XML intégré dans le PDF, contenant les données de la facture dans un format normalisé</li>
</ul>

<p>Ce double format permet à la fois aux <strong>humains</strong> de lire la facture et aux <strong>logiciels</strong> de l'intégrer automatiquement dans leur comptabilité.</p>

<h2>Factur-X vs ZUGFeRD : quelle différence ?</h2>

<p>Aucune différence technique. Il s'agit du <strong>même standard</strong> :</p>

<ul>
    <li><strong>Factur-X</strong> est le nom utilisé en France et dans les pays francophones</li>
    <li><strong>ZUGFeRD</strong> (Zentraler User Guide des Forums elektronische Rechnung Deutschland) est le nom utilisé en Allemagne</li>
    <li>Les deux sont basés sur la norme européenne <strong>EN 16931</strong></li>
</ul>

<h2>Les profils Factur-X</h2>

<p>Factur-X propose plusieurs niveaux de détail :</p>

<ul>
    <li><strong>Minimum</strong> : informations de base (expéditeur, destinataire, montant total)</li>
    <li><strong>Basic WL</strong> : sans lignes de détail, mais avec TVA ventilée</li>
    <li><strong>Basic</strong> : avec les lignes de facture détaillées</li>
    <li><strong>EN 16931</strong> : profil complet conforme à la norme européenne (recommandé)</li>
    <li><strong>Extended</strong> : données supplémentaires pour des besoins spécifiques</li>
</ul>

<p>faktur.lu génère des factures Factur-X au profil <strong>EN 16931</strong>, le plus largement accepté.</p>

<h2>Pourquoi Factur-X est important</h2>

<h3>Pour votre entreprise</h3>
<ul>
    <li><strong>Traitement automatisé</strong> : vos clients peuvent importer vos factures dans leur comptabilité sans ressaisie</li>
    <li><strong>Réduction des erreurs</strong> : les données structurées éliminent les erreurs de saisie manuelle</li>
    <li><strong>Paiement plus rapide</strong> : un traitement automatisé = délai de paiement réduit</li>
    <li><strong>Image professionnelle</strong> : vous montrez que votre entreprise est à la pointe</li>
</ul>

<h3>Pour l'Union européenne</h3>
<ul>
    <li><strong>Lutte contre la fraude TVA</strong> : les données structurées permettent des contrôles automatisés</li>
    <li><strong>Harmonisation</strong> : un seul format pour tous les pays de l'UE</li>
    <li><strong>Directive ViDA</strong> : la facturation électronique deviendra obligatoire pour le B2B intracommunautaire à partir de 2028-2030</li>
</ul>

<h2>Est-ce obligatoire au Luxembourg ?</h2>

<p>En 2026, Factur-X n'est <strong>pas encore obligatoire</strong> pour le B2B au Luxembourg. Cependant :</p>

<ul>
    <li>La <strong>France</strong> rend la facturation électronique obligatoire progressivement (2026-2027)</li>
    <li>L'<strong>Allemagne</strong> a adopté ZUGFeRD comme standard de référence</li>
    <li>La <strong>directive ViDA</strong> de l'UE prévoit une généralisation à partir de 2028</li>
    <li>Le <strong>secteur public</strong> luxembourgeois utilise déjà Peppol, basé sur des standards similaires</li>
</ul>

<p><strong>Anticipez :</strong> en adoptant Factur-X maintenant, vous êtes prêt pour les futures obligations et vous facilitez la vie de vos clients qui utilisent déjà ce format.</p>

<h2>Générer des factures Factur-X avec faktur.lu</h2>

<p>Le plan Pro de faktur.lu inclut la génération automatique de factures Factur-X :</p>

<ol>
    <li>Créez votre facture normalement</li>
    <li>Finalisez et envoyez</li>
    <li>Le PDF généré contient automatiquement les données XML Factur-X intégrées</li>
    <li>Votre client peut importer la facture dans son logiciel comptable en un clic</li>
</ol>

<p>Aucune action supplémentaire de votre part : c'est 100% automatique.</p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/peppol-b2g-luxembourg-guide-complet-2026" class="text-primary-500 hover:text-primary-600 text-sm">Peppol →</a></li><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">logiciel de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Générez des factures Factur-X automatiquement</h3>
    <p class="text-primary-800 mb-4">faktur.lu intègre le format Factur-X / ZUGFeRD dans toutes vos factures Pro. Anticipez les futures obligations européennes.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML,
        ];
    }
};
