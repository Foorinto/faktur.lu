<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Articles SEO longue traîne programmés chaque lundi de juillet-août 2026 (FR).
 *
 * Idempotent : ignore tout article dont le slug existe déjà.
 * status='published' + published_at futur => l'article n'apparaît dans la liste
 * qu'à sa date (BlogPost::scopePublished() filtre published_at <= now()).
 *
 * Les traductions DE/EN/LB/PT sont dans les seeders BlogScheduledSummer2026*Seeder
 * (même translation_key = slug FR).
 */
class BlogScheduledSummer2026Seeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        // Résout l'ID de catégorie par SLUG (robuste : les IDs peuvent différer
        // d'une base à l'autre ; fallback sur 'guides' si une catégorie manque).
        $catMap = [];
        foreach ([1 => 'guides', 2 => 'reglementation', 3 => 'freelances', 4 => 'actualites', 5 => 'guide-creation-entreprise'] as $oldId => $slug) {
            $catMap[$oldId] = \App\Models\BlogCategory::where('slug', $slug)->value('id') ?? 1;
        }

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip: {$article['slug']} (existe déjà)");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $catMap[$article['category_id']] ?? 1,
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'cover_image' => $article['cover_image'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'fr',
                'translation_key' => $article['slug'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Créé: {$article['slug']} (published_at {$article['published_at']})");
        }
    }

    protected function articles(): array
    {
        return [
            // ===== 2026-07-06 - Travailler avec une fiduciaire =====
            [
                'category_id' => 1, // guides
                'title' => 'Travailler avec une fiduciaire au Luxembourg : le guide pratique',
                'slug' => 'travailler-avec-fiduciaire-luxembourg-guide',
                'cover_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=630&fit=crop',
                'meta_title' => 'Travailler avec une fiduciaire au Luxembourg : guide pratique | faktur.lu',
                'meta_description' => "Quand faire appel à une fiduciaire au Luxembourg, comment la choisir, ce qu'elle attend de vous et comment lui transmettre des données propres pour gagner du temps (et de l'argent).",
                'excerpt' => "Une bonne fiduciaire vous fait gagner du temps et évite les erreurs. Encore faut-il bien la choisir et lui transmettre des données propres. Voici comment en tirer le meilleur.",
                'published_at' => '2026-07-06 09:00:00',
                'content' => <<<HTML
<p class="lead">Au Luxembourg, la plupart des indépendants et des PME délèguent leur comptabilité à une <strong>fiduciaire</strong> (cabinet comptable). Bien choisie et bien alimentée, elle vous fait gagner un temps précieux et vous évite des erreurs coûteuses. Voici comment collaborer efficacement avec elle.</p>

<h2>Qu'est-ce qu'une fiduciaire et à quoi sert-elle ?</h2>
<p>Une fiduciaire est un cabinet qui prend en charge tout ou partie de vos obligations comptables et fiscales : tenue de la comptabilité, déclarations de TVA, bilan annuel, déclaration d'impôt, conseil. Au Luxembourg, ce n'est pas légalement obligatoire pour un indépendant, mais c'est vivement recommandé dès que votre activité dépasse quelques factures par mois.</p>

<h2>Quand faire appel à une fiduciaire ?</h2>
<ul>
    <li>Vous dépassez le seuil de franchise de TVA et devez déclarer la TVA régulièrement.</li>
    <li>Vous créez une société (Sàrl, Sàrl-S) avec une comptabilité en partie double obligatoire.</li>
    <li>Vous manquez de temps ou de sérénité sur vos obligations fiscales.</li>
    <li>Vous voulez optimiser (charges déductibles, amortissements) sans prendre de risque lors d'un <a href="/fr/blog/controle-fiscal-aed-luxembourg-2026-preparation">contrôle de l'AED</a>.</li>
</ul>

<h2>Comment choisir la bonne fiduciaire</h2>
<p>Quelques critères concrets :</p>
<ul>
    <li><strong>La spécialité</strong> : un cabinet habitué aux indépendants et TPE vous comprendra mieux qu'un cabinet orienté grands groupes.</li>
    <li><strong>La réactivité</strong> : posez une question avant de signer, et regardez le délai de réponse.</li>
    <li><strong>Les outils</strong> : une fiduciaire qui accepte des données numériques (exports comptables, FAIA) plutôt que des piles de PDF vous fera gagner du temps des deux côtés.</li>
    <li><strong>La transparence des honoraires</strong> : forfait mensuel ou à l'acte ? Que comprend-il exactement ?</li>
</ul>
<p>L'<strong>Ordre des Experts-Comptables (OEC)</strong> tient un annuaire officiel des professionnels agréés (oec.lu).</p>

<h2>Le vrai levier : lui transmettre des données propres</h2>
<p>La principale source de temps perdu (et de coûts) côté fiduciaire, c'est la <strong>ressaisie</strong> : reprendre vos factures PDF ou Excel pour les réintégrer dans son logiciel. Plus vos données sont structurées, moins elle facture d'heures, et moins il y a d'erreurs.</p>
<p>Bonnes pratiques :</p>
<ul>
    <li>Émettez des <a href="/fr/blog/mentions-obligatoires-facture-luxembourg">factures conformes</a> avec une numérotation séquentielle continue.</li>
    <li>Centralisez tout dans un seul outil plutôt que des fichiers éparpillés.</li>
    <li>Fournissez des exports prêts à intégrer : <a href="/fr/blog/faia-luxembourg-fichier-audit-informatise-guide">FAIA</a>, Sage BOB 50, Sage 100 ou CSV.</li>
</ul>

<h2>faktur.lu : un portail gratuit pour votre fiduciaire</h2>
<p>Avec faktur.lu, vous facturez normalement, et votre fiduciaire accède à vos données <strong>en lecture seule</strong> via un portail comptable dédié : elle récupère vos exports (FAIA 2.01, Sage, CSV) en un clic, sans vous solliciter et sans ressaisie. Le portail est <strong>gratuit pour le cabinet</strong>. Si votre fiduciaire n'utilise pas encore faktur.lu, parlez-lui de notre <a href="/fr/partenaires">programme partenaire</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez proprement, votre comptable vous remerciera</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère des factures conformes au Luxembourg et des exports prêts pour votre fiduciaire (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-07-13 - Faire un devis professionnel =====
            [
                'category_id' => 1, // guides
                'title' => 'Faire un devis professionnel au Luxembourg : mentions, validité et conversion',
                'slug' => 'faire-devis-professionnel-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
                'meta_title' => 'Faire un devis professionnel au Luxembourg : guide complet | faktur.lu',
                'meta_description' => "Comment rédiger un devis professionnel au Luxembourg : mentions à inclure, durée de validité, valeur juridique et conversion en facture. Modèle et bonnes pratiques 2026.",
                'excerpt' => "Le devis engage votre client autant que vous. Voici les mentions à y faire figurer, sa valeur juridique, sa durée de validité et comment le convertir proprement en facture.",
                'published_at' => '2026-07-13 09:00:00',
                'content' => <<<HTML
<p class="lead">Avant la facture, il y a souvent le <strong>devis</strong>. Bien rédigé, il cadre la prestation, rassure le client et évite les litiges. Voici comment faire un devis professionnel au Luxembourg.</p>

<h2>À quoi sert un devis ?</h2>
<p>Le devis est une <strong>proposition commerciale chiffrée</strong> : il décrit la prestation ou les biens, le prix, et les conditions. Il n'est pas obligatoire au Luxembourg, mais fortement recommandé dès qu'une prestation a un minimum d'enjeu : il protège les deux parties en fixant le périmètre et le tarif avant de commencer.</p>

<h2>Les mentions à inclure dans un devis</h2>
<ul>
    <li>Vos coordonnées complètes (nom, adresse, numéro de TVA si assujetti).</li>
    <li>Les coordonnées du client.</li>
    <li>Un <strong>numéro de devis</strong> et la date d'émission.</li>
    <li>Le détail des prestations / produits, avec quantités et prix unitaires.</li>
    <li>Le montant <strong>hors taxes (HT)</strong>, la TVA applicable et le <strong>total TTC</strong>.</li>
    <li>La <strong>durée de validité</strong> de l'offre.</li>
    <li>Les conditions de paiement et, le cas échéant, l'acompte demandé.</li>
</ul>
<p>Si vous êtes en franchise de TVA, indiquez la mention correspondante au lieu de la TVA (voir notre article sur la <a href="/fr/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal">franchise de TVA</a>).</p>

<h2>Quelle valeur juridique ?</h2>
<p>Un devis <strong>signé et accepté</strong> par le client a une valeur contractuelle : il engage les deux parties sur le périmètre et le prix convenus. C'est votre meilleure protection en cas de désaccord. Pensez donc à faire dater et signer le devis (mention « Bon pour accord ») avant de démarrer.</p>

<h2>Durée de validité</h2>
<p>Indiquez toujours une durée de validité (par ex. 30 jours). Au-delà, vous n'êtes plus tenu par le prix proposé - utile si vos coûts évoluent. C'est aussi un levier commercial : une offre limitée dans le temps incite à décider.</p>

<h2>Du devis à la facture</h2>
<p>Une fois le devis accepté et la prestation réalisée, vous émettez la <a href="/fr/blog/mentions-obligatoires-facture-luxembourg">facture</a>. Pour éviter les erreurs et la double saisie, le mieux est de <strong>convertir le devis en facture</strong> : les lignes, montants et coordonnées sont repris automatiquement, et il ne reste qu'à attribuer le numéro de facture séquentiel.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Devis puis facture en un clic</h3>
    <p class="text-primary-800 mb-4">Avec faktur.lu, créez vos devis, faites-les accepter, et convertissez-les en factures conformes sans ressaisie.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Créer un devis gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-07-20 - Facturation de projet : forfait vs régie =====
            [
                'category_id' => 1, // guides
                'title' => 'Facturation de projet au Luxembourg : forfait ou régie ?',
                'slug' => 'facturation-projet-forfait-regie-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&h=630&fit=crop',
                'meta_title' => 'Facturation de projet : forfait vs régie au Luxembourg | faktur.lu',
                'meta_description' => "Forfait ou régie pour facturer un projet au Luxembourg ? Avantages, risques, acomptes et bonnes pratiques pour facturer vos prestations sans mauvaise surprise.",
                'excerpt' => "Facturer un projet au forfait ou en régie change tout : risque, trésorerie, relation client. Voici comment choisir et facturer proprement chaque modèle.",
                'published_at' => '2026-07-20 09:00:00',
                'content' => <<<HTML
<p class="lead">Quand on facture un projet - développement, design, conseil, chantier - deux grands modèles s'opposent : le <strong>forfait</strong> et la <strong>régie</strong>. Le bon choix dépend du niveau d'incertitude et de votre trésorerie.</p>

<h2>Le forfait : un prix fixe pour un périmètre défini</h2>
<p>Vous annoncez un prix global pour un livrable précis. Le client connaît son budget à l'avance, ce qui rassure. Mais le risque est pour vous : si le projet dérape, vous absorbez le dépassement.</p>
<p><strong>Quand l'utiliser :</strong> périmètre clair et stable, prestation que vous maîtrisez bien.</p>
<p><strong>Bonnes pratiques :</strong> cadrez précisément le périmètre dans le <a href="/fr/blog/faire-devis-professionnel-luxembourg">devis</a>, prévoyez une clause pour les demandes hors périmètre, et découpez le paiement (acompte + jalons + solde).</p>

<h2>La régie : facturation au temps passé</h2>
<p>Vous facturez les heures ou jours réellement travaillés, à un taux convenu. Le risque de dépassement passe côté client, et vous êtes payé pour tout le temps investi. En contrepartie, le client n'a pas de budget garanti.</p>
<p><strong>Quand l'utiliser :</strong> périmètre flou ou évolutif, missions longues, maintenance.</p>
<p><strong>Bonnes pratiques :</strong> suivez précisément votre temps, fournissez un relevé d'heures clair, et fixez éventuellement un plafond rassurant pour le client.</p>

<h2>Tableau comparatif</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Forfait</th><th class="text-left p-2 bg-slate-100">Régie</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Risque de dépassement</td><td class="p-2 border-b">Pour vous</td><td class="p-2 border-b">Pour le client</td></tr>
        <tr><td class="p-2 border-b">Visibilité budget client</td><td class="p-2 border-b">Élevée</td><td class="p-2 border-b">Faible</td></tr>
        <tr><td class="p-2 border-b">Idéal pour</td><td class="p-2 border-b">Périmètre stable</td><td class="p-2 border-b">Périmètre évolutif</td></tr>
    </tbody>
</table>

<h2>Les acomptes : protégez votre trésorerie</h2>
<p>Sur un projet de plusieurs semaines, ne facturez pas tout à la fin. Demandez un <strong>acompte au démarrage</strong> et facturez à des <strong>jalons</strong>. Cela sécurise votre trésorerie et limite le risque d'impayé (voir aussi nos conseils sur les <a href="/fr/blog/delais-paiement-luxembourg-cadre-legal-2026">délais de paiement</a>).</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Forfait, régie, acomptes : faktur.lu gère tout</h3>
    <p class="text-primary-800 mb-4">Suivi du temps, factures d'acompte et factures de projet conformes, en quelques clics.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-07-27 - Cotisations sociales CCSS =====
            [
                'category_id' => 2, // reglementation
                'title' => 'Cotisations sociales des indépendants au Luxembourg (CCSS) en 2026',
                'slug' => 'cotisations-sociales-independant-ccss-luxembourg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'meta_title' => 'Cotisations sociales indépendant Luxembourg (CCSS) 2026 | faktur.lu',
                'meta_description' => "Combien cotise un indépendant au Luxembourg ? Taux CCSS, base de calcul, assiette minimale et fonctionnement. Guide 2026 pour bien anticiper vos charges sociales.",
                'excerpt' => "En tant qu'indépendant au Luxembourg, vous cotisez au CCSS sur vos revenus. Voici comment ça marche, les taux indicatifs et comment anticiper ces charges.",
                'published_at' => '2026-07-27 09:00:00',
                'content' => <<<HTML
<p class="lead">Quand on se lance comme indépendant au Luxembourg, les <strong>cotisations sociales</strong> sont souvent la mauvaise surprise. Elles financent votre protection sociale (maladie, pension, dépendance) et se calculent sur vos revenus. Voici l'essentiel pour bien anticiper.</p>

<h2>Le CCSS, c'est quoi ?</h2>
<p>Le <strong>Centre Commun de la Sécurité Sociale (CCSS)</strong> est l'organisme qui centralise l'affiliation et le recouvrement des cotisations sociales au Luxembourg. Dès le début de votre activité indépendante, vous devez vous y affilier.</p>

<h2>Sur quoi cotise-t-on ?</h2>
<p>Les cotisations sont calculées sur votre <strong>revenu professionnel</strong> (le bénéfice, pas le chiffre d'affaires). Au démarrage, l'affiliation se fait souvent sur une base provisoire, régularisée ensuite selon vos revenus réels communiqués par l'Administration des contributions directes.</p>

<h2>Quels taux ? (ordre de grandeur)</h2>
<p>Au total, les cotisations d'un indépendant représentent <strong>environ 25 %</strong> du revenu, réparties entre les différentes branches :</p>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Branche</th><th class="text-left p-2 bg-slate-100">Taux indicatif</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Assurance pension</td><td class="p-2 border-b">~16 %</td></tr>
        <tr><td class="p-2 border-b">Assurance maladie (soins + indemnités)</td><td class="p-2 border-b">~6 %</td></tr>
        <tr><td class="p-2 border-b">Assurance dépendance</td><td class="p-2 border-b">~1,4 %</td></tr>
        <tr><td class="p-2 border-b">Assurance accident & santé au travail</td><td class="p-2 border-b">~1 %</td></tr>
    </tbody>
</table>
<p class="text-sm text-slate-500">Les taux exacts évoluent et dépendent de votre situation. Référez-vous toujours au CCSS (ccss.public.lu) ou à votre fiduciaire pour les chiffres en vigueur.</p>

<h2>L'assiette minimale et maximale</h2>
<p>Il existe une <strong>assiette minimale</strong> : même avec un revenu faible, vous cotisez sur une base plancher (liée au salaire social minimum). À l'autre bout, les cotisations sont plafonnées au-delà d'un certain revenu. Concrètement, un revenu très modeste génère quand même des cotisations minimales - à anticiper dans votre trésorerie.</p>

<h2>Comment bien anticiper</h2>
<ul>
    <li><strong>Mettez de côté ~25 %</strong> de votre bénéfice pour les cotisations, en plus de l'impôt.</li>
    <li>Attendez-vous à une <strong>régularisation</strong> : si vos revenus augmentent, un rattrapage de cotisations suivra.</li>
    <li>Tenez une <a href="/fr/blog/livre-des-recettes-luxembourg-obligations-modele-conservation">comptabilité claire</a> pour connaître votre bénéfice réel à tout moment.</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À retenir</p>
    <p>Les cotisations sociales ne sont pas de l'impôt : elles financent votre pension et votre couverture maladie. Mais elles pèsent ~25 % du revenu : il faut les provisionner dès la première facture.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Pilotez votre revenu en temps réel</h3>
    <p class="text-primary-800 mb-4">faktur.lu suit votre chiffre d'affaires et vos dépenses pour vous aider à provisionner cotisations et impôt sans stress.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-08-03 - Fixer son TJM en freelance =====
            [
                'category_id' => 3, // freelances
                'title' => 'Fixer son tarif journalier (TJM) en freelance au Luxembourg',
                'slug' => 'fixer-tjm-freelance-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'meta_title' => 'Fixer son TJM en freelance au Luxembourg : méthode 2026 | faktur.lu',
                'meta_description' => "Comment calculer son tarif journalier (TJM) en freelance au Luxembourg : charges, cotisations, jours non facturables et méthode simple pour ne pas se sous-vendre.",
                'excerpt' => "Beaucoup de freelances fixent leur tarif au feeling et se retrouvent à travailler à perte. Voici une méthode simple pour calculer un TJM qui couvre vraiment vos coûts.",
                'published_at' => '2026-08-03 09:00:00',
                'content' => <<<HTML
<p class="lead">Le piège classique du freelance qui débute : diviser un ancien salaire par 20 jours et en faire son tarif journalier. Résultat : on travaille à perte sans s'en rendre compte. Voici comment calculer un <strong>TJM</strong> (tarif journalier moyen) réaliste au Luxembourg.</p>

<h2>Pourquoi votre TJM n'est pas votre « salaire / 20 »</h2>
<p>Un salarié est payé pour ~220 jours/an, congés et maladie inclus, sans charges à gérer. Un freelance, lui, ne facture pas tous ses jours et porte toutes ses charges. Votre tarif doit couvrir bien plus que votre « salaire » souhaité.</p>

<h2>Les 4 postes à intégrer</h2>
<ul>
    <li><strong>Votre rémunération nette souhaitée.</strong> Ce que vous voulez réellement garder.</li>
    <li><strong>Les cotisations sociales (~25 %).</strong> Voir notre article sur les <a href="/fr/blog/cotisations-sociales-independant-ccss-luxembourg-2026">cotisations CCSS</a>.</li>
    <li><strong>L'impôt sur le revenu.</strong> Progressif, à provisionner.</li>
    <li><strong>Vos frais professionnels.</strong> Logiciels, assurance, comptable, matériel, formation, déplacements.</li>
</ul>

<h2>Les jours réellement facturables</h2>
<p>Sur ~252 jours ouvrés, retirez les congés, jours fériés, la maladie éventuelle, et surtout le temps <strong>non facturable</strong> : prospection, administratif, devis, formation. En pratique, beaucoup de freelances facturent <strong>180 à 200 jours par an</strong>, parfois moins la première année.</p>

<h2>La méthode de calcul</h2>
<p>Une approche simple :</p>
<ol>
    <li>Additionnez vos <strong>besoins annuels</strong> : rémunération nette + cotisations + impôt provisionné + frais pro.</li>
    <li>Divisez par votre <strong>nombre de jours facturables réalistes</strong>.</li>
    <li>Vous obtenez votre <strong>TJM plancher</strong> - en dessous, vous perdez de l'argent.</li>
</ol>
<p><strong>Exemple :</strong> si vos besoins annuels totaux sont de 90 000 € et que vous facturez 180 jours, votre TJM plancher est de 500 €. Tout tarif inférieur vous fait travailler à perte.</p>

<h2>Au-delà du plancher : la valeur</h2>
<p>Le TJM plancher couvre vos coûts ; votre tarif final dépend ensuite de votre <strong>valeur</strong> : expertise, rareté, résultats apportés au client. Ne facturez pas votre temps, facturez ce que vous faites gagner.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Suivez votre temps et votre rentabilité</h3>
    <p class="text-primary-800 mb-4">Avec faktur.lu, suivez vos jours facturés, vos dépenses et votre chiffre d'affaires pour vérifier que votre TJM tient ses promesses.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-08-10 - TVA déductible =====
            [
                'category_id' => 2, // reglementation
                'title' => 'Récupérer la TVA sur ses dépenses professionnelles au Luxembourg',
                'slug' => 'tva-deductible-depenses-professionnelles-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1599658880436-c61792e70672?w=1200&h=630&fit=crop',
                'meta_title' => 'TVA déductible au Luxembourg : récupérer la TVA sur vos achats | faktur.lu',
                'meta_description' => "Comment récupérer la TVA sur vos dépenses professionnelles au Luxembourg : conditions, dépenses déductibles ou non, et fonctionnement de la déclaration. Guide 2026.",
                'excerpt' => "La TVA que vous payez sur vos achats professionnels est en général récupérable. Voici les conditions, les cas particuliers et comment ça fonctionne concrètement.",
                'published_at' => '2026-08-10 09:00:00',
                'content' => <<<HTML
<p class="lead">Quand vous êtes assujetti à la TVA au Luxembourg, vous facturez de la TVA à vos clients (TVA collectée), mais vous pouvez aussi <strong>récupérer la TVA payée sur vos achats professionnels</strong> (TVA déductible). C'est un mécanisme central - voici comment il fonctionne.</p>

<h2>Le principe : TVA collectée - TVA déductible</h2>
<p>À chaque déclaration, vous reversez à l'État la différence entre la TVA que vous avez <strong>collectée</strong> sur vos ventes et la TVA <strong>déductible</strong> sur vos achats. Si vous avez beaucoup investi sur une période, la TVA déductible peut même dépasser la collectée : vous obtenez alors un crédit de TVA.</p>

<h2>Les conditions pour déduire la TVA</h2>
<ul>
    <li>Vous êtes <strong>assujetti</strong> à la TVA (pas en franchise - voir la <a href="/fr/blog/franchise-tva-luxembourg-seuil-obligations-regime-normal">franchise de TVA</a>).</li>
    <li>La dépense est <strong>professionnelle</strong> et liée à votre activité taxable.</li>
    <li>Vous détenez une <strong>facture conforme</strong> mentionnant la TVA et le numéro de TVA du fournisseur.</li>
</ul>

<h2>Dépenses généralement déductibles</h2>
<p>Matériel et fournitures, logiciels et abonnements professionnels, honoraires (comptable, avocat), loyer de locaux professionnels, frais de télécommunication, formation… dès lors qu'ils servent votre activité.</p>

<h2>Les cas particuliers (déduction limitée ou exclue)</h2>
<p>Certaines dépenses sont partiellement ou totalement exclues du droit à déduction, notamment quand elles ont une part privée ou de représentation. Les frais à caractère mixte (usage privé et professionnel), certaines dépenses de réception ou de véhicule peuvent être limités. En cas de doute, vérifiez auprès de l'<strong>AED</strong> ou de votre fiduciaire.</p>
<p class="text-sm text-slate-500">Les règles précises de déductibilité figurent dans la loi TVA luxembourgeoise et peuvent évoluer. Référez-vous à l'Administration de l'enregistrement, des domaines et de la TVA (AED) ou à un professionnel.</p>

<h2>Comment ça se passe concrètement</h2>
<p>Vous enregistrez vos dépenses avec leur TVA tout au long de la période, puis vous reportez le total de TVA déductible dans votre <strong>déclaration de TVA</strong> (mensuelle, trimestrielle ou annuelle selon votre régime). Une comptabilité de dépenses bien tenue évite d'oublier de la TVA récupérable - autant d'argent qui resterait sur la table.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Ne perdez plus de TVA récupérable</h3>
    <p class="text-primary-800 mb-4">faktur.lu enregistre vos dépenses et leur TVA, et prépare des données propres pour votre déclaration et votre fiduciaire.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-08-17 - Facture d'acompte =====
            [
                'category_id' => 1, // guides
                'title' => "Facture d'acompte au Luxembourg : règles, mentions et TVA",
                'slug' => 'facture-acompte-luxembourg-regles-mentions',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => "Facture d'acompte au Luxembourg : règles et mentions | faktur.lu",
                'meta_description' => "Comment établir une facture d'acompte au Luxembourg : mentions obligatoires, traitement de la TVA, et lien avec la facture finale. Guide pratique 2026.",
                'excerpt' => "Demander un acompte protège votre trésorerie, mais cela implique une vraie facture, avec ses règles de TVA. Voici comment faire correctement.",
                'published_at' => '2026-08-17 09:00:00',
                'content' => <<<HTML
<p class="lead">Demander un <strong>acompte</strong> avant de démarrer une mission est une excellente protection de trésorerie. Mais un acompte n'est pas un simple « versement » : il donne lieu à une véritable <strong>facture d'acompte</strong>, avec ses règles. Voici comment faire au Luxembourg.</p>

<h2>Pourquoi facturer un acompte ?</h2>
<p>Sur une prestation longue ou un projet, l'acompte sécurise votre trésorerie, engage le client et réduit le risque d'<a href="/fr/blog/delais-paiement-luxembourg-cadre-legal-2026">impayé</a>. C'est particulièrement utile en <a href="/fr/blog/facturation-projet-forfait-regie-luxembourg">facturation de projet</a>.</p>

<h2>Les mentions d'une facture d'acompte</h2>
<p>Une facture d'acompte est une facture à part entière : elle doit comporter les <a href="/fr/blog/mentions-obligatoires-facture-luxembourg">mentions obligatoires habituelles</a> (vos coordonnées, celles du client, numéro et date, numéro de TVA), avec en plus :</p>
<ul>
    <li>La mention claire qu'il s'agit d'un <strong>acompte</strong> et sur quelle commande/devis il porte.</li>
    <li>Le <strong>montant de l'acompte</strong> HT, la TVA applicable et le TTC.</li>
    <li>Un numéro séquentiel, comme toute facture.</li>
</ul>

<h2>Le traitement de la TVA</h2>
<p>Point important : pour une prestation, la TVA devient en principe <strong>exigible à l'encaissement de l'acompte</strong>, pour le montant reçu. Autrement dit, vous déclarez et reversez la TVA sur l'acompte dès qu'il est encaissé, sans attendre la facture finale.</p>
<p class="text-sm text-slate-500">Le moment d'exigibilité de la TVA dépend de la nature de l'opération. En cas de doute, vérifiez auprès de l'AED ou de votre fiduciaire.</p>

<h2>La facture finale</h2>
<p>À la fin de la prestation, vous émettez la <strong>facture de solde</strong>. Elle reprend le montant total de la prestation, puis <strong>déduit l'acompte déjà facturé</strong> (et sa TVA), pour ne facturer que le reste à payer. Le client ne paie ainsi jamais deux fois la même somme, et la TVA totale reste correcte.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">En résumé</p>
    <p>Acompte = vraie facture, avec TVA exigible à l'encaissement. La facture finale reprend le total et déduit l'acompte déjà facturé.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Acomptes et soldes gérés automatiquement</h3>
    <p class="text-primary-800 mb-4">faktur.lu génère vos factures d'acompte et de solde conformes, avec le bon traitement de la TVA.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-08-24 - Impôt des indépendants =====
            [
                'category_id' => 2, // reglementation
                'title' => 'Impôt des indépendants au Luxembourg : comment ça marche en 2026',
                'slug' => 'impot-independant-luxembourg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'meta_title' => 'Impôt des indépendants au Luxembourg 2026 : guide | faktur.lu',
                'meta_description' => "Comment l'indépendant est-il imposé au Luxembourg ? Bénéfice, barème progressif, avances trimestrielles, charges déductibles. Guide 2026 pour bien anticiper l'impôt.",
                'excerpt' => "L'impôt de l'indépendant ne se calcule pas sur le chiffre d'affaires mais sur le bénéfice, selon un barème progressif. Voici l'essentiel pour bien l'anticiper.",
                'published_at' => '2026-08-24 09:00:00',
                'content' => <<<HTML
<p class="lead">Après les <a href="/fr/blog/cotisations-sociales-independant-ccss-luxembourg-2026">cotisations sociales</a>, l'autre grand poste de l'indépendant, c'est l'<strong>impôt sur le revenu</strong>. Bonne nouvelle : on n'est pas imposé sur tout ce qu'on encaisse. Voici comment ça fonctionne au Luxembourg.</p>

<h2>On est imposé sur le bénéfice, pas sur le chiffre d'affaires</h2>
<p>La base imposable de l'indépendant, c'est le <strong>bénéfice</strong> : votre chiffre d'affaires moins vos <strong>charges professionnelles déductibles</strong> (frais, achats, amortissements, cotisations sociales…). Plus votre comptabilité est rigoureuse, plus vous déduisez ce à quoi vous avez droit.</p>

<h2>Un barème progressif</h2>
<p>L'impôt sur le revenu luxembourgeois est <strong>progressif</strong> : plus le revenu imposable est élevé, plus le taux marginal augmente, par tranches. Une part de revenu modeste n'est pas imposée, puis les taux montent jusqu'à un taux marginal supérieur élevé. S'y ajoute la <strong>contribution au fonds pour l'emploi</strong> (impôt de solidarité). Votre <strong>classe d'impôt</strong> (selon votre situation familiale) influence le calcul.</p>
<p class="text-sm text-slate-500">Les tranches et taux exacts du barème sont fixés par la loi et révisés régulièrement. Référez-vous à l'Administration des contributions directes (ACD, impotsdirects.public.lu) ou à votre fiduciaire pour le barème en vigueur.</p>

<h2>Les avances trimestrielles</h2>
<p>L'ACD vous demande généralement de payer des <strong>avances d'impôt trimestrielles</strong>, estimées sur la base de vos revenus passés. À la déclaration annuelle, le solde est régularisé : vous payez le complément ou êtes remboursé. Si vos revenus augmentent, attendez-vous à des avances plus élevées l'année suivante.</p>

<h2>La déclaration annuelle</h2>
<p>Chaque année, vous remplissez votre déclaration d'impôt (en ligne via MyGuichet ou avec votre fiduciaire). Vous y reportez votre bénéfice et vos éléments déductibles. C'est là que comptent tous les justificatifs collectés pendant l'année.</p>

<h2>Comment bien anticiper</h2>
<ul>
    <li><strong>Provisionnez l'impôt</strong> en plus des ~25 % de cotisations sociales : ne dépensez pas tout votre encaissé.</li>
    <li>Conservez tous vos <strong>justificatifs de charges</strong> : chaque dépense pro déductible réduit votre base imposable.</li>
    <li>Tenez une comptabilité à jour pour connaître votre bénéfice réel et éviter les mauvaises surprises à la régularisation.</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gardez l'œil sur votre bénéfice toute l'année</h3>
    <p class="text-primary-800 mb-4">faktur.lu suit recettes et dépenses pour vous donner une vision claire de votre bénéfice - et anticiper impôt et cotisations.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],

            // ===== 2026-08-31 - SARL-S vs entreprise individuelle =====
            [
                'category_id' => 5, // guide-creation-entreprise
                'title' => 'SARL-S ou entreprise individuelle au Luxembourg : quel statut choisir ?',
                'slug' => 'sarl-s-vs-entreprise-individuelle-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=630&fit=crop',
                'meta_title' => 'SARL-S ou entreprise individuelle au Luxembourg ? Comparatif | faktur.lu',
                'meta_description' => "SARL-S ou entreprise individuelle au Luxembourg : responsabilité, capital, comptabilité, fiscalité. Comparatif clair pour choisir le bon statut en 2026.",
                'excerpt' => "Se lancer en entreprise individuelle ou créer une SARL-S ? Les deux ont leurs avantages. Voici un comparatif clair pour choisir selon votre situation.",
                'published_at' => '2026-08-31 09:00:00',
                'content' => <<<HTML
<p class="lead">Au moment de se lancer au Luxembourg, une question revient sans cesse : rester en <strong>entreprise individuelle</strong> ou créer une <strong>SARL-S</strong> (société à responsabilité limitée simplifiée) ? Voici les vraies différences pour décider.</p>

<h2>L'entreprise individuelle (EI)</h2>
<p>C'est la forme la plus simple : vous exercez en votre nom propre, sans créer de personne morale distincte. Pas de capital, peu de formalisme. Voir notre <a href="/fr/blog/creer-entreprise-individuelle-luxembourg-guide-2026">guide de création d'une entreprise individuelle</a>.</p>
<p><strong>Le point clé :</strong> votre responsabilité est <strong>illimitée</strong> - vos biens personnels peuvent être engagés en cas de dettes professionnelles.</p>

<h2>La SARL-S (société simplifiée)</h2>
<p>La SARL-S a été créée pour faciliter l'entrepreneuriat. Elle est réservée aux <strong>personnes physiques</strong>, avec un <strong>capital de départ réduit</strong> (entre 1 € et 12 000 €), et offre une <strong>responsabilité limitée</strong> aux apports : vos biens personnels sont en principe protégés.</p>
<p>En contrepartie, elle implique plus de formalisme : constitution, comptabilité en partie double, obligations de société, et une part du bénéfice doit être affectée à une réserve légale jusqu'à atteindre le capital d'une SARL classique.</p>

<h2>Comparatif</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Critère</th><th class="text-left p-2 bg-slate-100">Entreprise individuelle</th><th class="text-left p-2 bg-slate-100">SARL-S</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Responsabilité</td><td class="p-2 border-b">Illimitée (biens personnels)</td><td class="p-2 border-b">Limitée aux apports</td></tr>
        <tr><td class="p-2 border-b">Capital</td><td class="p-2 border-b">Aucun</td><td class="p-2 border-b">1 € à 12 000 €</td></tr>
        <tr><td class="p-2 border-b">Formalisme</td><td class="p-2 border-b">Minimal</td><td class="p-2 border-b">Plus lourd (société)</td></tr>
        <tr><td class="p-2 border-b">Comptabilité</td><td class="p-2 border-b">Simplifiée possible</td><td class="p-2 border-b">Partie double</td></tr>
        <tr><td class="p-2 border-b">Fiscalité</td><td class="p-2 border-b">Impôt sur le revenu</td><td class="p-2 border-b">Impôt sur les sociétés</td></tr>
    </tbody>
</table>

<h2>Comment choisir ?</h2>
<ul>
    <li><strong>Activité à faible risque, démarrage léger</strong> → l'entreprise individuelle suffit souvent.</li>
    <li><strong>Risque financier, besoin de protéger votre patrimoine, image « société »</strong> → la SARL-S est intéressante.</li>
</ul>
<p>Le choix a des conséquences juridiques et fiscales durables : faites-vous accompagner par une <a href="/fr/blog/travailler-avec-fiduciaire-luxembourg-guide">fiduciaire</a> ou la House of Entrepreneurship avant de trancher.</p>
<p class="text-sm text-slate-500">Les conditions exactes (capital, réserve légale, autorisations) sont fixées par la loi et détaillées sur guichet.lu et auprès du Luxembourg Business Registers (LBR).</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Quel que soit votre statut, facturez conforme</h3>
    <p class="text-primary-800 mb-4">EI ou SARL-S, faktur.lu génère des factures conformes au Luxembourg et les exports pour votre comptable.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement</a>
</div>
HTML,
            ],
        ];
    }
}
