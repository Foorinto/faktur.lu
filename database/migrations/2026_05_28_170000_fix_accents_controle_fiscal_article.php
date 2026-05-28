<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;

/**
 * Full accent restoration for the FR article
 * "controle-fiscal-luxembourg-comment-preparer".
 *
 * The content was seeded without diacritics. A word-map fixer can't handle
 * context-dependent cases (preposition "a" → "à", cedillas like
 * "annoncant" → "annonçant"), so the prose is rewritten with correct French.
 * HTML tags, classes and links are preserved verbatim.
 *
 * Idempotent: only updates if the current content still contains the
 * unaccented marker "annoncant".
 */
return new class extends Migration
{
    public function up(): void
    {
        $post = BlogPost::where('locale', 'fr')
            ->where('slug', 'controle-fiscal-luxembourg-comment-preparer')
            ->first();

        if (!$post || !str_contains($post->content, 'annoncant')) {
            return;
        }

        $post->update(['content' => $this->content()]);
    }

    public function down(): void
    {
        // No-op: reverting would re-introduce the unaccented content.
    }

    private function content(): string
    {
        return <<<'HTML'
<p class="lead">Un courrier de l'<strong>AED (Administration de l'enregistrement, des domaines et de la TVA)</strong> vous annonçant un contrôle fiscal ? Pas de panique. Si votre comptabilité est bien tenue, le contrôle se passera sans problème. Voici comment vous préparer.</p>

<h2>Qui peut être contrôlé ?</h2>

<p>Toute entreprise assujettie à la TVA au Luxembourg peut faire l'objet d'un contrôle fiscal. Les contrôles sont :</p>

<ul>
    <li><strong>Aléatoires</strong> : sélection au hasard dans la base des contribuables</li>
    <li><strong>Ciblés</strong> : suite à des incohérences détectées dans vos déclarations</li>
    <li><strong>Sectoriels</strong> : contrôles par secteur d'activité</li>
    <li><strong>Sur demande</strong> : suite à une demande de remboursement TVA importante</li>
</ul>

<p>Les <strong>petites entreprises et freelances</strong> ne sont pas épargnés. L'AED contrôle toutes les tailles d'entreprise.</p>

<h2>Le fichier FAIA : l'élément central</h2>

<p>Lors d'un contrôle, l'AED peut exiger votre <strong>FAIA (Fichier d'Audit Informatisé)</strong>. C'est un fichier XML standardisé qui contient :</p>

<ul>
    <li><strong>Informations de l'entreprise</strong> : matricule, TVA, adresse</li>
    <li><strong>Plan comptable</strong> : liste des comptes utilisés</li>
    <li><strong>Écritures comptables</strong> : toutes les transactions de l'exercice</li>
    <li><strong>Factures émises</strong> : détail de chaque facture client</li>
    <li><strong>Factures reçues</strong> : détail des factures fournisseurs</li>
</ul>

<p><strong>Important :</strong> si vous ne pouvez pas fournir un FAIA conforme, l'administration peut procéder à une <strong>estimation d'office</strong> de votre chiffre d'affaires et de votre TVA. Les conséquences financières peuvent être lourdes.</p>

<h2>Documents à conserver</h2>

<p>Gardez ces documents pendant <strong>10 ans</strong> :</p>

<ul>
    <li><strong>Toutes les factures émises</strong> (numérotation séquentielle, sans trou)</li>
    <li><strong>Toutes les factures reçues</strong> (fournisseurs, prestataires)</li>
    <li><strong>Le livre des recettes</strong></li>
    <li><strong>Les déclarations TVA</strong> déposées</li>
    <li><strong>Les relevés bancaires</strong></li>
    <li><strong>Les contrats</strong> avec vos clients</li>
    <li><strong>Les preuves de livraison</strong> (pour les biens exportés)</li>
    <li><strong>Les vérifications VIES</strong> (pour les clients intracommunautaires)</li>
</ul>

<h2>Déroulement d'un contrôle</h2>

<ol>
    <li><strong>Notification</strong> : l'AED vous informe par courrier, généralement 2-4 semaines à l'avance</li>
    <li><strong>Préparation</strong> : rassemblez vos documents, générez votre FAIA</li>
    <li><strong>Contrôle sur place</strong> : un inspecteur examine vos documents, souvent chez vous ou chez votre comptable</li>
    <li><strong>Questions</strong> : l'inspecteur peut poser des questions sur des transactions spécifiques</li>
    <li><strong>Rapport</strong> : l'AED rédige un rapport avec ses conclusions</li>
    <li><strong>Régularisation</strong> : si des erreurs sont trouvées, vous recevez un avis de redressement</li>
</ol>

<h2>Les points les plus contrôlés</h2>

<ul>
    <li><strong>Cohérence TVA</strong> : la TVA déclarée correspond-elle aux factures émises ?</li>
    <li><strong>Déductions TVA</strong> : les factures fournisseurs sont-elles conformes ?</li>
    <li><strong>Opérations intracommunautaires</strong> : les exonérations sont-elles justifiées (VIES, preuves) ?</li>
    <li><strong>Numérotation des factures</strong> : séquence continue, sans trou</li>
    <li><strong>Notes de crédit</strong> : sont-elles liées à des factures existantes ?</li>
    <li><strong>Franchise TVA</strong> : le seuil de 35 000 EUR est-il respecté ?</li>
</ul>

<h2>Comment se préparer avec faktur.lu</h2>

<p>faktur.lu est conçu pour que vous soyez <strong>toujours prêt</strong> en cas de contrôle :</p>

<ul>
    <li><strong>Export FAIA en 1 clic</strong> : fichier XML conforme au format 2.01 de l'AED, générable à tout moment</li>
    <li><strong>Numérotation immuable</strong> : aucune possibilité de trou ou de doublon</li>
    <li><strong>Traçabilité complète</strong> : chaque action est journalisée (audit trail)</li>
    <li><strong>Archivage PDF/A</strong> : factures archivées avec empreinte numérique (plan Pro)</li>
    <li><strong>Livre des recettes automatique</strong> : exportable en PDF ou CSV</li>
    <li><strong>Vérification VIES</strong> : log de validation des numéros TVA intracommunautaires</li>
</ul>

<h2>Conseils pour un contrôle serein</h2>

<ul>
    <li><strong>Soyez organisé</strong> : classez vos documents par année et par type</li>
    <li><strong>Soyez coopératif</strong> : répondez clairement aux questions de l'inspecteur</li>
    <li><strong>Faites-vous accompagner</strong> : votre comptable peut être présent lors du contrôle</li>
    <li><strong>Anticipez</strong> : générez un FAIA de test chaque trimestre pour vérifier la cohérence</li>
    <li><strong>Corrigez vos erreurs</strong> : une erreur corrigée spontanément avant le contrôle est vue plus favorablement</li>
</ul>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide" class="text-primary-500 hover:text-primary-600 text-sm">export FAIA →</a></li><li><a href="/fr/blog/archivage-factures-luxembourg-duree-legale-format" class="text-primary-500 hover:text-primary-600 text-sm">archivage →</a></li><li><a href="/fr/blog/livre-des-recettes-luxembourg-obligations-modele" class="text-primary-500 hover:text-primary-600 text-sm">livre des recettes →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Soyez prêt pour le contrôle fiscal</h3>
    <p class="text-primary-800 mb-4">Avec faktur.lu, votre FAIA est toujours prêt, vos factures sont conformes et votre traçabilité est complète. Facturez l'esprit tranquille.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML;
    }
};
