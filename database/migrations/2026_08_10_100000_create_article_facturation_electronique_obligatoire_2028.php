<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nouvel article : le calendrier luxembourgeois de la facturation électronique
 * B2B (projet de loi déposé le 30 juillet 2026).
 *
 * Pourquoi un article neuf plutôt qu'une retouche des quatre articles qui
 * parlent déjà du sujet : ils décrivent tous ViDA, correctement, mais aucun ne
 * mentionne le calendrier national, publié après leur dernière révision.
 * Recopier le même tableau dans quatre articles créerait la cannibalisation
 * qu'on a déjà eu à démêler ailleurs. Un article canonique, et les autres y
 * renverront par un lien.
 *
 * ⚠ Le texte n'est pas voté. L'article le dit trois fois, et renvoie à la
 * fiduciaire pour toute décision d'investissement.
 *
 * Cette migration **insère** seulement. Si le slug existe déjà, elle ne touche
 * à rien : les articles se modifient depuis l'administration, et une migration
 * ne doit jamais écraser une retouche faite en production.
 */
return new class extends Migration
{
    private const SLUG = 'facturation-electronique-obligatoire-luxembourg-2028';

    public function up(): void
    {
        if (DB::table('blog_posts')->where('slug', self::SLUG)->exists()) {
            echo '  → article FR déjà présent, laissé intact'.PHP_EOL;

            return;
        }

        // Auteur et catégorie résolus à l'exécution plutôt qu'en dur. Une base
        // fraîchement migrée n'a ni utilisateur ni catégorie (c'est le cas des
        // tests, qui repartent d'un schéma vide) : y insérer un article viole la
        // contrainte de clé étrangère et fait tomber toute la suite.
        $auteur = DB::table('users')->min('id');
        $categorie = DB::table('blog_categories')->where('slug', 'reglementation')->value('id');

        if ($auteur === null || $categorie === null) {
            echo '  → auteur ou catégorie « reglementation » introuvable, article FR non créé'.PHP_EOL;

            return;
        }

        DB::table('blog_posts')->insert([
            'author_id' => $auteur,
            'category_id' => $categorie,
            'locale' => 'fr',
            'translation_key' => self::SLUG,
            'slug' => self::SLUG,
            'title' => 'Facturation électronique obligatoire au Luxembourg : le calendrier 2028-2029',
            'meta_title' => 'Facture électronique obligatoire Luxembourg 2028 | faktur.lu',
            'excerpt' => 'Un projet de loi rendu public le 30 juillet 2026 rend la facturation électronique obligatoire entre entreprises luxembourgeoises : réception au 1er janvier 2028, émission courant 2028 pour les plus grandes et au 1er janvier 2029 pour les autres. Ce qu\'il faut retenir, et ce qui reste incertain.',
            'meta_description' => 'Facturation électronique obligatoire au Luxembourg : réception au 1er janvier 2028, émission en 2028 puis au 1er janvier 2029 pour les autres entreprises.',
            'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
            'content' => $this->contenu(),
            'status' => 'published',
            // Heure UTC, et volontairement tôt : scopePublished écarte tout
            // published_at situé dans le futur. Une heure « ronde » choisie au
            // hasard peut se retrouver après now() selon le moment du déploiement,
            // et l'article resterait invisible sans que rien ne le signale.
            'published_at' => '2026-08-10 06:00:00',
            'views_count' => 0,
            'created_at' => '2026-08-10 06:00:00',
            'updated_at' => '2026-08-10 06:00:00',
        ]);

        echo '  → article FR créé : '.self::SLUG.PHP_EOL;
    }

    public function down(): void
    {
        DB::table('blog_posts')->where('slug', self::SLUG)->delete();
    }

    private function contenu(): string
    {
        return <<<'HTML'
<p class="lead">Le Luxembourg s'apprête à rendre la <strong>facturation électronique obligatoire entre entreprises</strong>. Un projet de loi déposé le 30 juillet 2026 impose de pouvoir <strong>recevoir</strong> une facture électronique dès le 1<sup>er</sup> janvier 2028. L'obligation d'<strong>émettre</strong> suit : d'abord les entreprises qui dépassent certains seuils de taille, courant 2028, puis toutes les autres au 1<sup>er</sup> janvier 2029.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">En bref</p>
    <ul>
        <li><strong>1<sup>er</sup> janvier 2028</strong> : toutes les entreprises établies au Luxembourg doivent pouvoir <strong>recevoir</strong> une facture électronique structurée.</li>
        <li><strong>Courant 2028</strong> : obligation d'<strong>émettre</strong> pour les entreprises dépassant au moins deux des trois seuils (7,5 M€ de bilan, 15 M€ de chiffre d'affaires, 50 salariés), appréciés sur les comptes 2026.</li>
        <li><strong>1<sup>er</sup> janvier 2029</strong> : obligation d'<strong>émettre</strong> pour toutes les autres, ce qui couvre l'essentiel des indépendants, TPE et PME.</li>
        <li>Réseau <strong>Peppol</strong>, norme européenne <strong>EN 16931</strong>.</li>
        <li>Les ventes aux particuliers (B2C) ne sont pas concernées.</li>
        <li>Le texte est un <strong>projet de loi</strong> : les dates ne sont pas définitives, et les seuils seront précisés par règlement grand-ducal.</li>
    </ul>
</div>

<h2>Ce que prévoit le projet de loi</h2>

<p>Le gouvernement a approuvé en juillet 2026 un projet de loi étendant l'obligation de facturation électronique aux transactions entre entreprises établies au Luxembourg. Le texte a été rendu public le <strong>30 juillet 2026</strong> et circule sous le numéro 8815.</p>

<p>Il ne crée pas un cadre à partir de rien : il <strong>modifie deux lois existantes</strong>, la <a href="http://journalofficiel.lu/eli/etat/leg/loi/2019/05/16/a345/jo" target="_blank" rel="noopener">loi du 16 mai 2019 relative à la facturation électronique dans le cadre des marchés publics</a>, et la loi TVA du 12 février 1979. Autrement dit, le Luxembourg élargit au privé un dispositif déjà rodé sur le secteur public.</p>

<p>Un projet de règlement grand-ducal l'accompagne. Son objet est d'éviter que chaque entreprise déploie une solution technique de son côté : il fixe un réseau commun, pour que l'émetteur et le destinataire n'aient pas à s'accorder au cas par cas.</p>

<h2>Le calendrier en détail</h2>

<p>La première marche concerne <strong>tout le monde en même temps</strong>. Les suivantes dépendent de la taille de l'entreprise.</p>

<table class="w-full my-6">
    <thead>
        <tr>
            <th class="border border-gray-300 px-4 py-2 text-left">Date</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Obligation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Qui</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1<sup>er</sup> janvier 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Recevoir et traiter une facture électronique conforme</td>
            <td class="border border-gray-300 px-4 py-2">Toutes les entreprises établies au Luxembourg</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>Courant 2028</strong></td>
            <td class="border border-gray-300 px-4 py-2">Émettre ses factures au format électronique</td>
            <td class="border border-gray-300 px-4 py-2">Entreprises dépassant au moins deux des trois seuils ci-dessous</td>
        </tr>
        <tr>
            <td class="border border-gray-300 px-4 py-2"><strong>1<sup>er</sup> janvier 2029</strong></td>
            <td class="border border-gray-300 px-4 py-2">Émettre ses factures au format électronique</td>
            <td class="border border-gray-300 px-4 py-2">Toutes les autres entreprises</td>
        </tr>
    </tbody>
</table>

<h3>Quels seuils de taille ?</h3>

<p>Une entreprise rejoint la première vague d'émission si elle dépasse <strong>au moins deux</strong> des trois critères suivants, appréciés à la clôture de l'exercice <strong>2026</strong> :</p>

<ul>
    <li><strong>7,5 millions d'euros</strong> de total de bilan ;</li>
    <li><strong>15 millions d'euros</strong> de chiffre d'affaires net ;</li>
    <li><strong>50 salariés</strong> en moyenne, en équivalent temps plein.</li>
</ul>

<p>Ce sont les critères de taille déjà employés en droit comptable luxembourgeois. Pour l'immense majorité des indépendants, TPE et PME, aucun des trois n'est atteint : l'échéance d'émission est donc le <strong>1<sup>er</sup> janvier 2029</strong>.</p>

<p>Les seuils définitifs et une partie des modalités techniques doivent être fixés par <strong>règlement grand-ducal</strong>. Ils peuvent donc encore évoluer.</p>

<p>Cette asymétrie entre réception et émission n'est pas un détail. Une petite entreprise n'a rien à émettre avant 2029, mais elle devra <strong>encaisser des factures électroniques dès janvier 2028</strong>, y compris de fournisseurs qui ne seront eux-mêmes obligés que plus tard. En pratique, beaucoup émettront plus tôt : rien n'interdit d'anticiper.</p>

<h2>Qui est concerné, et qui ne l'est pas</h2>

<p>Le dispositif vise les opérations pour lesquelles <strong>l'émetteur et le destinataire sont tous deux établis au Luxembourg</strong>. Il s'agit d'un mandat domestique.</p>

<p>Restent en dehors :</p>

<ul>
    <li><strong>les ventes aux particuliers</strong> (B2C), explicitement exclues ;</li>
    <li><strong>les opérations transfrontalières</strong>, qui relèvent d'un autre calendrier, européen celui-là (voir plus bas) ;</li>
    <li>les opérations dont le destinataire n'est pas assujetti à la TVA.</li>
</ul>

<p>Si vous facturez surtout des particuliers, ce texte vous concerne donc peu en émission. Mais dès que vous achetez auprès d'un fournisseur luxembourgeois, l'obligation de réception s'applique.</p>

<h2>Comment cela fonctionnera techniquement</h2>

<p>Le Luxembourg réutilise <strong>Peppol</strong>, le réseau déjà en place pour la facturation au secteur public. Les factures circuleront selon le modèle dit « en quatre coins » : vous déposez votre facture chez votre point d'accès, qui la remet au point d'accès de votre client, qui la lui livre.</p>

<p>Vous n'avez donc pas à vous connecter à chacun de vos clients. Vous vous raccordez une fois au réseau, et le réseau se charge du reste. C'est le même principe que le courrier électronique : votre fournisseur de messagerie parle à celui de votre correspondant sans que vous ayez à le configurer.</p>

<p>Le format devra respecter la norme européenne <strong>EN 16931</strong>, dans une syntaxe autorisée. Concrètement, une facture structurée lisible par une machine, et non un PDF envoyé par courriel. Un PDF classique, même signé, ne remplit pas cette condition.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Un PDF n'est pas une facture électronique</p>
    <p>C'est la confusion la plus fréquente. Au sens de la réglementation, une facture électronique est un fichier <strong>structuré</strong> que le logiciel du destinataire peut lire et comptabiliser sans ressaisie. Un PDF est une image de facture : lisible par un humain, opaque pour une machine. Les formats hybrides comme <a href="/fr/blog/factur-x-zugferd-facturation-electronique-europeenne">Factur-X</a> répondent aux deux besoins en glissant le fichier structuré à l'intérieur du PDF.</p>
</div>

<h2>Ne confondez pas avec ViDA</h2>

<p>Deux calendriers coexistent, et on les mélange constamment.</p>

<ul>
    <li><strong>Le calendrier luxembourgeois</strong>, celui de cet article : opérations <strong>domestiques</strong>, 2028 et 2029.</li>
    <li><strong>Le calendrier européen ViDA</strong> (« TVA à l'ère numérique ») : opérations <strong>intracommunautaires</strong>, facturation électronique et déclaration numérique obligatoires au <strong>1<sup>er</sup> juillet 2030</strong>, avec une harmonisation des systèmes nationaux visée à l'horizon 2035.</li>
</ul>

<p>La conséquence pratique est simple : <strong>l'échéance nationale arrive d'abord</strong>. Une entreprise luxembourgeoise qui ne facture que des clients luxembourgeois est concernée en 2028, pas en 2030. Beaucoup d'articles ne citent que ViDA et laissent croire que rien ne presse avant la fin de la décennie. C'est inexact depuis juillet 2026.</p>

<h2>Ce qui est déjà obligatoire aujourd'hui</h2>

<p>La facturation électronique n'a rien de nouveau au Luxembourg pour qui travaille avec le secteur public. Depuis 2022-2023, toute facture adressée à l'État, à une commune ou à un établissement public doit passer par Peppol, <strong>sans seuil de montant</strong>. Nous détaillons ce dispositif dans notre <a href="/fr/blog/peppol-b2g-luxembourg-guide-complet-2026">guide complet Peppol B2G</a>.</p>

<p>C'est précisément parce que cette infrastructure existe et fonctionne que le gouvernement a choisi de l'étendre plutôt que d'en bâtir une autre.</p>

<h2>Comment se préparer</h2>

<p>Il reste plus d'un an avant la première échéance. Trois choses utiles, par ordre de priorité.</p>

<h3>1. Vérifiez que votre logiciel sait produire du structuré</h3>

<p>La question à poser à votre éditeur n'est pas « faites-vous de la facturation électronique », à laquelle tout le monde répond oui. Elle est : <strong>« générez-vous du Peppol BIS 3.0 ou de l'EN 16931, et à quelle échéance saurez-vous transmettre sur le réseau ? »</strong> La différence entre produire le fichier et savoir l'acheminer est réelle, et c'est celle qui coûte du temps.</p>

<h3>2. Anticipez la réception, pas seulement l'émission</h3>

<p>C'est le point le plus souvent négligé, alors que c'est la première obligation dans le temps. Recevoir suppose d'être identifiable sur le réseau et de disposer d'un canal pour récupérer les factures entrantes. Si vous n'y êtes pas, vos fournisseurs ne pourront pas vous facturer.</p>

<h3>3. Mettez à jour vos données clients</h3>

<p>Le réseau fonctionne par identifiants. Au Luxembourg, l'identifiant repose sur le numéro de TVA. Un fichier client dans lequel les numéros de TVA sont absents, mal saisis ou périmés deviendra un frein direct. Autant nettoyer maintenant, tranquillement.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Ce n'est pas encore la loi</p>
    <p>Le texte décrit ici est un <strong>projet de loi</strong>, déposé mais pas voté. Le périmètre exact, l'enchaînement des dates et les modalités techniques peuvent évoluer au cours des débats parlementaires. Les échéances ci-dessus traduisent l'intention du gouvernement, pas un droit acquis.</p>
    <p class="mt-2">Les analyses publiées <strong>divergent d'ailleurs déjà</strong> sur l'échelonnement de l'émission : la plupart retiennent une première vague au 1<sup>er</sup> juillet 2028, d'autres évoquent le 1<sup>er</sup> janvier 2028 pour les plus grandes entreprises. Nous avons retenu la formulation la plus prudente. La date de réception, elle, fait consensus.</p>
    <p class="mt-2">Avant d'engager une dépense ou de changer d'outil sur cette base, <strong>parlez-en à votre fiduciaire ou à votre conseil</strong>. Cet article vous informe, il ne remplace pas un avis professionnel sur votre situation.</p>
</div>

<h2>Questions fréquentes</h2>

<h3>Suis-je obligé d'émettre mes factures électroniquement dès 2028 ?</h3>

<p>Uniquement si vous dépassez au moins deux des trois seuils (7,5 M€ de bilan, 15 M€ de chiffre d'affaires, 50 salariés) sur vos comptes 2026. Sinon, votre échéance d'émission est le <strong>1<sup>er</sup> janvier 2029</strong>. En revanche, <strong>l'obligation de recevoir s'applique à tous dès le 1<sup>er</sup> janvier 2028</strong>.</p>

<h3>Et mes factures aux particuliers ?</h3>

<p>Elles ne sont pas concernées. Le dispositif vise les opérations entre assujettis à la TVA.</p>

<h3>Un PDF envoyé par courriel suffira-t-il ?</h3>

<p>Non. Il faudra un fichier structuré conforme à la norme EN 16931, transmis sur le réseau. Un PDF seul ne répond pas à la définition.</p>

<h3>Dois-je changer de logiciel de facturation ?</h3>

<p>Pas nécessairement, mais votre logiciel devra savoir produire le format structuré et le transmettre. Posez la question à votre éditeur dès maintenant : c'est le meilleur moyen de savoir si vous devrez changer, et vous avez le temps de le faire sereinement. Nos critères de choix sont détaillés dans notre <a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif">comparatif des logiciels de facturation</a>.</p>

<h3>Que se passe-t-il si je ne suis pas prêt ?</h3>

<p>Les sanctions relèveront du régime TVA modifié par le texte. Comme le projet n'est pas voté, leur nature exacte n'est pas arrêtée. Le risque immédiat est surtout commercial : un client qui ne peut pas vous envoyer sa facture, ou qui ne peut pas traiter la vôtre, cherchera un fournisseur qui sait le faire.</p>

<h2>Pour aller plus loin</h2>

<ul>
    <li><a href="/fr/blog/peppol-b2g-luxembourg-guide-complet-2026">Peppol B2G au Luxembourg : guide complet</a></li>
    <li><a href="/fr/blog/factur-x-zugferd-facturation-electronique-europeenne">Factur-X et ZUGFeRD : la facturation électronique européenne expliquée</a></li>
    <li><a href="https://www.cc.lu/en/all-information/news/detail/facturation-electronique-obligations-et-echeances-au-luxembourg-et-en-europe" target="_blank" rel="noopener">Chambre de Commerce : obligations et échéances au Luxembourg et en Europe</a></li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <p class="font-semibold">faktur.lu génère déjà le format</p>
    <p>Vos factures peuvent être produites au format Peppol BIS 3.0 (UBL 2.1) et Factur-X, conformes à la norme EN 16931. La transmission automatique via point d'accès certifié est en cours de raccordement, en vue des échéances décrites ici.</p>
    <p class="mt-3"><a href="/register" class="font-semibold">Essayer faktur.lu gratuitement</a></p>
</div>
HTML;
    }
};
