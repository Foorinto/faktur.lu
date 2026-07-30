<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « franchise-tva-luxembourg-seuil-obligations-regime-normal » —
 * vérification factuelle et traductions intégrales.
 *
 * Article pivot : trois articles déjà corrigés y renvoient.
 * Source de référence : FAQ officielle de l'AED sur le nouveau régime de
 * franchise (pfi.public.lu, faq-fr.pdf), lue intégralement.
 *
 * 1. ERREUR CENTRALE — « Ne pas produire de déclaration TVA annuelle »,
 *    repris en avantage sous la forme « pas de déclaration TVA
 *    périodique, pas d'état récapitulatif ». La FAQ AED dit l'inverse
 *    dans un cas très courant :
 *      « Lorsque l'assujetti réalise des prestations de services
 *        intracommunautaires [...] il doit obligatoirement soumettre une
 *        déclaration annuelle simplifiée avant le 1er mars de l'année
 *        civile suivante via le portail eCDF. Il est également tenu de
 *        fournir des états récapitulatifs relatifs à ses prestations de
 *        services intracommunautaires. »
 *    Un indépendant luxembourgeois en franchise qui facture ne serait-ce
 *    qu'un seul client professionnel allemand est donc tenu aux deux —
 *    et l'article lui affirmait le contraire, avec une échéance au 1er
 *    mars qu'il ne pouvait pas connaître. Section dédiée ajoutée.
 *
 * 2. OMISSION MAJEURE — le RÉGIME DE FRANCHISE TRANSFRONTALIER, entré en
 *    vigueur le 1er janvier 2025 en même temps que le relèvement du
 *    seuil, était totalement absent. Il permet à une petite entreprise
 *    luxembourgeoise de bénéficier de la franchise dans d'autres États
 *    membres, sous conditions cumulatives : respect du seuil national de
 *    chaque État concerné et chiffre d'affaires dans l'ensemble de
 *    l'Union inférieur à 100 000 EUR. Notification préalable via
 *    MyGuichet.lu, identification par un numéro « EX »
 *    (LU12345678-EX) sous une trentaine de jours ouvrables, déclaration
 *    TRIMESTRIELLE du chiffre d'affaires réalisé dans tous les États
 *    membres, et aucune identification rétroactive possible. Section
 *    complète ajoutée.
 *
 * 3. DATE DE SORTIE DÉCALÉE D'UN JOUR. L'article : sortie « dès le
 *    franchissement du seuil de tolérance », TVA due « à partir de cette
 *    opération ». La FAQ : « la franchise cesse de s'appliquer à partir
 *    du jour suivant le dépassement ». L'exemple officiel est sans
 *    ambiguïté : vente du 8 septembre portant le CA à 56 000 EUR,
 *    exclusion à partir du 9 septembre. La facture qui fait franchir le
 *    seuil reste donc en franchise. Un jour d'écart décide si une
 *    facture porte la TVA ou non.
 *
 * 4. EXCLUSION L'ANNÉE SUIVANTE, dans les DEUX cas. La FAQ :
 *    « Indépendamment du pourcentage de dépassement, l'assujetti qui a
 *    dépassé le seuil au cours d'une année civile est exclu du bénéfice
 *    de la franchise au Luxembourg pour l'année civile suivante. »
 *    L'article ne le disait que pour la tolérance à 10 %.
 *
 * 5. COHÉRENCE avec l'article freelance : la déclaration annuelle
 *    s'ajoute aux déclarations périodiques, elle ne les remplace pas.
 *
 * PRÉCISIONS AJOUTÉES, toutes issues de la FAQ AED :
 * - Option pour le régime normal : effet au 1er jour du mois suivant, et
 *   engagement pour au moins une année civile. Le verrou n'était pas dit.
 * - Factures simplifiées autorisées depuis le 1er janvier 2025.
 * - Liste complète des exclusions (opérations occasionnelles art. 12,
 *   moyens de transport neufs, groupe TVA, forfait agricole, options
 *   immobilières).
 * - Case 481 pour l'année où l'on relève des deux régimes.
 * - Cessation d'activité : déclaration dans les quinze jours.
 *
 * CLAIM ÉCONOMIQUE CORRIGÉ : « vos prix sont mécaniquement 17 % moins
 * chers » qu'un concurrent au régime normal. L'écart réel face au
 * consommateur est de 17/117, soit environ 14,5 %, et il est amputé par
 * la TVA non déductible sur vos achats.
 *
 * PARITÉ : luxembourgeois et allemand faisaient 2 959 et 3 063 caractères
 * contre 9 241, avec 2 liens contre 11 et aucun tableau.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'franchise-tva-luxembourg-seuil-obligations-regime-normal')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure affirmait que la
        // franchise dispense de toute déclaration annuelle.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Au Luxembourg, les petites entreprises dont le chiffre d'affaires annuel HT ne dépasse pas <strong>50 000 €</strong> peuvent bénéficier du <strong>régime de franchise TVA</strong> (article 57bis de la loi TVA luxembourgeoise). Ce seuil est passé de 35 000 € à 50 000 € le 1<sup>er</sup> janvier 2025, en même temps qu'est apparu un <strong>régime transfrontalier</strong> encore méconnu. Voici tout ce qu'il faut savoir en 2026.</p>

<h2>Qu'est-ce que la franchise TVA ?</h2>

<p>La franchise TVA (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">article 57bis LIVA</a>) est un <strong>régime particulier</strong> qui permet aux petites entreprises d'exonérer de TVA les biens et services qu'elles vendent. Concrètement, vous :</p>

<ul>
    <li><strong>Ne facturez pas de TVA</strong> à vos clients</li>
    <li><strong>Ne reversez pas de TVA</strong> à l'État</li>
    <li>Êtes déchargé de l'obligation de déposer des <strong>déclarations de TVA ordinaires</strong></li>
</ul>

<p>En contrepartie, vous ne pouvez <strong>pas récupérer la TVA</strong> payée sur vos achats professionnels.</p>

<h2>Conditions d'éligibilité</h2>

<ul>
    <li>Chiffre d'affaires annuel HT <strong>inférieur ou égal à 50 000 €</strong> sur l'année civile</li>
    <li><strong>Tolérance de 10 %</strong> : si vous dépassez en cours d'année sans excéder <strong>55 000 €</strong>, vous restez en franchise jusqu'au 31 décembre</li>
    <li>Siège de l'activité économique au Luxembourg (un simple établissement stable ne suffit pas)</li>
    <li>Le régime est <strong>optionnel</strong> : vous pouvez lui préférer le régime normal</li>
</ul>

<p><strong>Opérations exclues de la franchise :</strong> les opérations occasionnelles visées à l'article 12 de la directive 2006/112/CE, et les <strong>livraisons de moyens de transport neufs</strong> vers un autre État membre. Sont également exclus les assujettis relevant du régime du groupe TVA, du régime forfaitaire agricole et sylvicole, ou ayant exercé des options immobilières incompatibles.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Le saviez-vous ?</p>
    <p>Le seuil a été relevé de 35 000 € à 50 000 € au 1<sup>er</sup> janvier 2025, dans le cadre de la transposition de la <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">directive (UE) 2020/285</a>. Le plafond maximal qu'un État membre peut fixer est de 85 000 € ; le Luxembourg ne prévoit pas de seuils sectoriels.</p>
</div>

<h2>Ce dont la franchise ne vous dispense pas</h2>

<p>C'est le point le plus mal compris du régime, et il concerne beaucoup de monde. Être en franchise <strong>ne signifie pas être dispensé de toute obligation déclarative</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Si vous facturez des clients professionnels dans l'UE</p>
    <p class="text-red-700">Dès lors que vous réalisez des <strong>prestations de services intracommunautaires</strong> — ou que vous devenez redevable de la TVA au Luxembourg au titre de l'article 61 de la loi TVA — vous devez <strong>obligatoirement</strong> :</p>
    <ul class="text-red-700 mt-2">
        <li>déposer une <strong>déclaration annuelle simplifiée</strong> via le portail <strong>eCDF</strong>, <strong>avant le 1<sup>er</sup> mars</strong> de l'année civile suivante ;</li>
        <li>fournir des <strong>états récapitulatifs</strong> relatifs à ces prestations de services intracommunautaires.</li>
    </ul>
    <p class="text-red-700 mt-2">Un seul client professionnel en Allemagne, en France ou en Belgique suffit à déclencher ces deux obligations.</p>
</div>

<p>En dehors de ce cas, l'assujetti en franchise communique son chiffre d'affaires annuel à son bureau d'imposition — par voie postale, par courriel, ou au moyen du formulaire de déclaration annuelle simplifiée mis à disposition par l'AED.</p>

<p>Enfin, si vous relevez de la franchise puis du régime normal <strong>au cours d'une même année</strong>, le chiffre d'affaires réalisé sous la franchise s'indique à la <strong>case 481</strong> de la déclaration de TVA déposée au titre du régime normal.</p>

<h2>Mentions obligatoires sur vos factures</h2>

<p>Même en franchise TVA, vos factures doivent contenir la <strong>mention exacte</strong> suivante :</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    « TVA non applicable – Article 57bis de la loi modifiée du 12 février 1979 »
</blockquote>

<p>Vous devez également :</p>
<ul>
    <li>Ne <strong>pas mentionner de TVA</strong> (pas de ligne TVA sur la facture)</li>
    <li>Ne pas indiquer de <strong>taux de TVA</strong></li>
    <li>Indiquer uniquement le <strong>montant net</strong> (pas de distinction HT/TTC)</li>
</ul>

<p>Bonne nouvelle depuis le 1<sup>er</sup> janvier 2025 : l'assujetti bénéficiant de la franchise est autorisé à émettre des <strong>factures simplifiées</strong>.</p>

<h2>Avantages de la franchise</h2>

<ul>
    <li><strong>Simplicité</strong> : pas de déclaration TVA ordinaire, facturation simplifiée autorisée</li>
    <li><strong>Compétitivité B2C</strong> : à prix net égal, votre facture est environ <strong>14,5 % moins chère</strong> pour un particulier que celle d'un concurrent au régime normal (17 % de TVA sur 117 € TTC). Attention toutefois : cet écart est amputé par la TVA que vous ne récupérez pas sur vos propres achats</li>
    <li><strong>Trésorerie</strong> : pas de décalage entre TVA encaissée et TVA reversée</li>
    <li><strong>Moins de paperasse</strong> : administration simplifiée</li>
</ul>

<h2>Inconvénients</h2>

<ul>
    <li><strong>Pas de déduction TVA amont</strong> : vous payez la TVA sur vos achats sans pouvoir la récupérer (déterminant si vous investissez)</li>
    <li><strong>Désavantage B2B</strong> : vos clients entreprises ne déduisent aucune TVA sur vos factures, votre prix leur revient donc plus cher</li>
    <li><strong>Image</strong> : certains clients B2B préfèrent travailler avec des entreprises assujetties</li>
    <li><strong>Obligations résiduelles</strong> : dès que vous facturez des services à des professionnels de l'UE, la déclaration annuelle simplifiée et les états récapitulatifs redeviennent obligatoires</li>
    <li><strong>Plafond contraignant</strong> : au-delà du seuil et de sa tolérance, le passage au régime normal s'impose</li>
</ul>

<h2>Le régime de franchise transfrontalier (depuis 2025)</h2>

<p>C'est la seconde nouveauté du 1<sup>er</sup> janvier 2025, bien moins commentée que le relèvement du seuil. Jusqu'alors, la franchise ne valait que dans votre pays d'établissement. Désormais, une petite entreprise luxembourgeoise peut <strong>bénéficier de la franchise dans d'autres États membres</strong>.</p>

<h3>Les conditions</h3>

<ul>
    <li>Respecter le <strong>seuil national de chaque État membre</strong> dans lequel vous voulez la franchise (les seuils varient : consultez le <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">portail européen SME VAT rules</a>)</li>
    <li>Réaliser un chiffre d'affaires <strong>inférieur à 100 000 € dans l'ensemble de l'Union</strong> — c'est le « seuil de l'Union », et il inclut vos ventes au Luxembourg</li>
    <li>Avoir le <strong>siège de votre activité économique</strong> au Luxembourg. Un assujetti dont le siège est dans un pays tiers ne peut pas en bénéficier, même avec un établissement stable dans l'UE</li>
</ul>

<h3>Le numéro « EX »</h3>

<p>La procédure passe par une <strong>notification préalable</strong> sur votre espace professionnel <strong>MyGuichet.lu</strong>. L'AED transmet la demande aux États membres concernés. Dès qu'au moins un l'accepte, vous recevez un numéro d'identification <strong>« EX »</strong>, de la forme <strong>LU12345678-EX</strong> — généralement sous <strong>35 jours ouvrables</strong>.</p>

<p>La franchise ne commence à s'appliquer dans un État membre qu'<strong>à partir de la date de communication ou de confirmation</strong> de ce numéro. Point important : <strong>aucune identification rétroactive n'est possible</strong>. Les ventes réalisées avant l'obtention du numéro ne sont pas rattrapables.</p>

<h3>Vos obligations déclaratives</h3>

<ul>
    <li><strong>Déclaration trimestrielle</strong> à l'AED du chiffre d'affaires total réalisé dans <strong>tous</strong> les États membres — y compris le Luxembourg</li>
    <li>En contrepartie, vous n'avez pas à vous identifier ni à déposer de déclarations de TVA dans les États membres où vous n'êtes pas établi, pour les opérations couvertes par la franchise</li>
    <li>Les opérations <strong>non couvertes</strong> par la franchise dans ces États (acquisitions intracommunautaires notamment) restent soumises aux obligations locales</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Si vous dépassez le seuil de l'Union</p>
    <p>Au-delà de 100 000 € de chiffre d'affaires dans l'Union, vous perdez la franchise dans les États membres où vous n'êtes pas établi — <strong>y compris l'année civile suivante</strong>, même si les seuils nationaux y sont respectés. En revanche, vous conservez la franchise nationale au Luxembourg tant que vous y remplissez les conditions.</p>
</div>

<h2>Quand passer au régime normal ?</h2>

<h3>Passage obligatoire (dépassement du seuil)</h3>

<p>La règle dépend du <strong>niveau de dépassement</strong> du seuil de 50 000 € HT :</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Effet</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Dépassement de 10 % maximum (CA jusqu'à 55 000 €)</td><td class="p-2 border-b">Vous restez en franchise jusqu'au 31 décembre. Bascule au régime normal au <strong>1<sup>er</sup> janvier de l'année suivante</strong>.</td></tr>
        <tr><td class="p-2 border-b">Dépassement de plus de 10 % (CA supérieur à 55 000 €)</td><td class="p-2 border-b">La franchise cesse de s'appliquer <strong>à partir du jour suivant le dépassement</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">L'exemple officiel de l'AED</p>
    <p>Un assujetti atteint 51 000 € : dépassement inférieur à 10 %, il reste en franchise. Le <strong>8 septembre 2025</strong>, une vente de 5 000 € porte son chiffre d'affaires à <strong>56 000 €</strong>. Le dépassement excède 10 % : il est exclu du régime de franchise <strong>à partir du 9 septembre 2025</strong>, et le restera pendant toute l'année 2026.</p>
    <p class="mt-2">Notez la mécanique : c'est le <strong>jour suivant</strong>. La facture qui fait franchir le plafond est encore émise en franchise ; c'est la suivante qui porte la TVA.</p>
</div>

<p><strong>Dans les deux cas</strong>, le dépassement du seuil au cours d'une année civile vous exclut de la franchise pour <strong>toute l'année civile suivante</strong>, quel que soit le pourcentage de dépassement.</p>

<p>Vous devez alors :</p>

<ol>
    <li>Informer l'<strong>AED</strong> du changement de régime via MyGuichet.lu ou auprès de votre bureau d'imposition</li>
    <li>Commencer à facturer la TVA selon le calendrier ci-dessus</li>
    <li>Vous conformer à la périodicité de déclaration déterminée par l'AED</li>
</ol>

<h3>Passage volontaire (option pour le régime normal)</h3>

<p>Vous pouvez opter pour le régime normal <strong>même en dessous du seuil</strong>, en en faisant la demande auprès de votre bureau d'imposition. <strong>L'option prend effet le 1<sup>er</sup> jour du mois suivant</strong>, et vous engage pour <strong>au moins une année civile</strong> : ce n'est pas un aller-retour.</p>

<p>C'est souvent le bon choix si :</p>

<ul>
    <li>Vos clients sont majoritairement des <strong>entreprises (B2B)</strong> qui déduisent la TVA</li>
    <li>Vous avez des <strong>investissements importants</strong> (équipement, véhicule professionnel) et voulez récupérer la TVA amont</li>
    <li>Vous approchez du seuil et préférez <strong>anticiper</strong> plutôt que changer de tarification en cours d'année</li>
    <li>Vous facturez à l'<strong>international (intra-UE B2B)</strong> et avez besoin d'un numéro de TVA actif</li>
</ul>

<p>À noter également : en cas de <strong>cessation d'activité</strong>, une déclaration doit être adressée à votre bureau d'imposition dans les <strong>quinze jours</strong>. La franchise cesse à la date de cessation.</p>

<h2>Périodicité des déclarations TVA après bascule</h2>

<p>Une fois au régime normal, la périodicité dépend de votre chiffre d'affaires HT annuel. <strong>La déclaration annuelle s'ajoute aux déclarations périodiques, elle ne les remplace pas :</strong></p>

<ul>
    <li><strong>CA &lt; 112 000 €</strong> : déclaration annuelle uniquement</li>
    <li><strong>CA entre 112 000 € et 620 000 €</strong> : déclarations trimestrielles <strong>et</strong> déclaration annuelle</li>
    <li><strong>CA &gt; 620 000 €</strong> : déclarations mensuelles <strong>et</strong> déclaration annuelle</li>
</ul>

<p>C'est l'AED qui détermine votre régime — vous ne le choisissez pas.</p>

<h2>Impact sur vos factures avec faktur.lu</h2>

<p>faktur.lu gère les deux régimes :</p>

<ul>
    <li><strong>Franchise TVA</strong> : les factures affichent automatiquement la mention « TVA non applicable – Article 57bis de la loi modifiée du 12 février 1979 », sans ligne TVA ni distinction HT/TTC</li>
    <li><strong>Régime normal</strong> : la TVA est calculée automatiquement avec le bon taux (17 %, 14 %, 8 % ou 3 %)</li>
    <li><strong>Alerte seuil</strong> : faktur.lu vous prévient quand vous approchez du seuil de 50 000 € (et de la tolérance à 55 000 €) pour anticiper le passage</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les seuils, taux et procédures TVA peuvent évoluer, et le régime transfrontalier est récent. Cette page est mise à jour régulièrement, mais pour votre situation personnelle, consultez votre fiduciaire ou directement l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Régime particulier des petites entreprises (franchise)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Franchise (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Commission européenne – Seuils nationaux de franchise par État membre</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Loi modifiée du 12 février 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Directive (UE) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">Taux de TVA au Luxembourg →</a></li><li><a href="/fr/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freelance Luxembourg : facturer en conformité →</a></li><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mentions obligatoires sur une facture LU →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Gérez votre franchise TVA en toute sérénité</h3>
    <p class="text-primary-800 mb-4">faktur.lu s'adapte automatiquement à votre régime TVA et vous alerte avant le dépassement du seuil de 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">In Luxemburg können Kleinunternehmen mit einem Nettojahresumsatz bis <strong>50 000 €</strong> die <strong>MwSt.-Freigrenzenregelung</strong> in Anspruch nehmen (Artikel 57bis des luxemburgischen MwSt.-Gesetzes). Diese Schwelle wurde am 1. Januar 2025 von 35 000 € auf 50 000 € angehoben — zeitgleich mit einer noch wenig bekannten <strong>grenzüberschreitenden Regelung</strong>. Hier ist alles Wissenswerte für 2026.</p>

<h2>Was ist die MwSt.-Freigrenze?</h2>

<p>Die MwSt.-Freigrenze (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">Artikel 57bis LIVA</a>) ist eine <strong>Sonderregelung</strong>, die es Kleinunternehmen erlaubt, ihre Lieferungen und Leistungen von der MwSt. zu befreien. Konkret:</p>

<ul>
    <li>Sie berechnen Ihren Kunden <strong>keine MwSt.</strong></li>
    <li>Sie führen <strong>keine MwSt.</strong> an den Staat ab</li>
    <li>Sie sind von der Pflicht befreit, <strong>ordentliche MwSt.-Erklärungen</strong> einzureichen</li>
</ul>

<p>Im Gegenzug können Sie die auf Ihre betrieblichen Einkäufe gezahlte <strong>MwSt. nicht zurückholen</strong>.</p>

<h2>Voraussetzungen</h2>

<ul>
    <li>Nettojahresumsatz von <strong>höchstens 50 000 €</strong> im Kalenderjahr</li>
    <li><strong>Toleranz von 10 %</strong>: Überschreiten Sie unterjährig, ohne <strong>55 000 €</strong> zu übersteigen, bleiben Sie bis zum 31. Dezember in der Freigrenze</li>
    <li>Sitz der wirtschaftlichen Tätigkeit in Luxemburg (eine bloße feste Niederlassung genügt nicht)</li>
    <li>Die Regelung ist <strong>optional</strong>: Sie können das Normalregime vorziehen</li>
</ul>

<p><strong>Von der Freigrenze ausgeschlossene Umsätze:</strong> gelegentliche Umsätze im Sinne von Artikel 12 der Richtlinie 2006/112/EG sowie <strong>Lieferungen neuer Fahrzeuge</strong> in einen anderen Mitgliedstaat. Ausgeschlossen sind ferner Steuerpflichtige der MwSt.-Gruppe, der Pauschalregelung für Land- und Forstwirte oder mit unvereinbaren Immobilienoptionen.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wussten Sie schon?</p>
    <p>Die Schwelle wurde zum 1. Januar 2025 von 35 000 € auf 50 000 € angehoben, im Rahmen der Umsetzung der <a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Richtlinie (EU) 2020/285</a>. Die Obergrenze, die ein Mitgliedstaat festlegen darf, liegt bei 85 000 €; Luxemburg sieht keine sektoriellen Schwellen vor.</p>
</div>

<h2>Wovon die Freigrenze Sie nicht befreit</h2>

<p>Das ist der am häufigsten missverstandene Punkt der Regelung, und er betrifft viele. In der Freigrenze zu sein bedeutet <strong>nicht, von allen Meldepflichten befreit zu sein</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Wenn Sie gewerbliche Kunden in der EU fakturieren</p>
    <p class="text-red-700">Sobald Sie <strong>innergemeinschaftliche Dienstleistungen</strong> erbringen — oder nach Artikel 61 des MwSt.-Gesetzes in Luxemburg steuerschuldnerisch werden — müssen Sie <strong>zwingend</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>eine <strong>vereinfachte Jahreserklärung</strong> über das Portal <strong>eCDF</strong> einreichen, und zwar <strong>vor dem 1. März</strong> des folgenden Kalenderjahres;</li>
        <li><strong>Zusammenfassende Meldungen</strong> zu diesen innergemeinschaftlichen Dienstleistungen abgeben.</li>
    </ul>
    <p class="text-red-700 mt-2">Ein einziger gewerblicher Kunde in Deutschland, Frankreich oder Belgien löst beide Pflichten aus.</p>
</div>

<p>Außerhalb dieses Falls teilt der Steuerpflichtige in der Freigrenze seinen Jahresumsatz seinem Steueramt mit — per Post, per E-Mail oder über das von der AED bereitgestellte Formular der vereinfachten Jahreserklärung.</p>

<p>Fallen Sie <strong>innerhalb desselben Jahres</strong> unter die Freigrenze und dann unter das Normalregime, ist der unter der Freigrenze erzielte Umsatz in <strong>Feld 481</strong> der im Normalregime abzugebenden MwSt.-Erklärung einzutragen.</p>

<h2>Pflichtangaben auf Ihren Rechnungen</h2>

<p>Auch in der Freigrenze müssen Ihre Rechnungen folgenden <strong>exakten Vermerk</strong> tragen:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979"
</blockquote>

<p>Außerdem gilt:</p>
<ul>
    <li><strong>Keine MwSt. ausweisen</strong> (keine MwSt.-Zeile auf der Rechnung)</li>
    <li>Keinen <strong>MwSt.-Satz</strong> angeben</li>
    <li>Nur den <strong>Nettobetrag</strong> nennen (keine Unterscheidung netto/brutto)</li>
</ul>

<p>Gute Nachricht seit dem 1. Januar 2025: Wer die Freigrenze in Anspruch nimmt, darf <strong>vereinfachte Rechnungen</strong> ausstellen.</p>

<h2>Vorteile der Freigrenze</h2>

<ul>
    <li><strong>Einfachheit</strong>: keine ordentliche MwSt.-Erklärung, vereinfachte Fakturierung erlaubt</li>
    <li><strong>B2C-Wettbewerbsfähigkeit</strong>: bei gleichem Nettopreis ist Ihre Rechnung für eine Privatperson rund <strong>14,5 % günstiger</strong> als die eines Wettbewerbers im Normalregime (17 % MwSt. auf 117 € brutto). Achtung: dieser Vorsprung wird durch die nicht erstattete MwSt. auf Ihre eigenen Einkäufe geschmälert</li>
    <li><strong>Liquidität</strong>: keine Verschiebung zwischen vereinnahmter und abgeführter MwSt.</li>
    <li><strong>Weniger Papierkram</strong>: vereinfachte Verwaltung</li>
</ul>

<h2>Nachteile</h2>

<ul>
    <li><strong>Kein Vorsteuerabzug</strong>: Sie zahlen MwSt. auf Ihre Einkäufe, ohne sie zurückzuholen (entscheidend, wenn Sie investieren)</li>
    <li><strong>B2B-Nachteil</strong>: gewerbliche Kunden ziehen aus Ihren Rechnungen keine MwSt. ab, Ihr Preis kommt sie also teurer</li>
    <li><strong>Image</strong>: manche B2B-Kunden arbeiten lieber mit steuerpflichtigen Unternehmen</li>
    <li><strong>Restpflichten</strong>: sobald Sie Leistungen an EU-Unternehmer fakturieren, werden vereinfachte Jahreserklärung und Zusammenfassende Meldungen wieder verpflichtend</li>
    <li><strong>Enge Obergrenze</strong>: jenseits von Schwelle und Toleranz ist der Wechsel ins Normalregime zwingend</li>
</ul>

<h2>Die grenzüberschreitende Freigrenzenregelung (seit 2025)</h2>

<p>Das ist die zweite Neuerung vom 1. Januar 2025, weit weniger kommentiert als die Schwellenanhebung. Bis dahin galt die Freigrenze nur im Sitzstaat. Nun kann ein luxemburgisches Kleinunternehmen die <strong>Freigrenze auch in anderen Mitgliedstaaten</strong> nutzen.</p>

<h3>Die Bedingungen</h3>

<ul>
    <li>Die <strong>nationale Schwelle jedes Mitgliedstaats</strong> einhalten, in dem Sie die Freigrenze wollen (die Schwellen variieren: siehe das <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">EU-Portal SME VAT rules</a>)</li>
    <li>Einen Umsatz von <strong>unter 100 000 € in der gesamten Union</strong> erzielen — die „Unionsschwelle", die Ihre Umsätze in Luxemburg einschließt</li>
    <li>Den <strong>Sitz Ihrer wirtschaftlichen Tätigkeit</strong> in Luxemburg haben. Wer seinen Sitz in einem Drittland hat, kann sie nicht nutzen, auch nicht mit fester Niederlassung in der EU</li>
</ul>

<h3>Die „EX"-Nummer</h3>

<p>Das Verfahren läuft über eine <strong>vorherige Mitteilung</strong> in Ihrem Unternehmensbereich auf <strong>MyGuichet.lu</strong>. Die AED leitet den Antrag an die betroffenen Mitgliedstaaten weiter. Sobald mindestens einer zustimmt, erhalten Sie eine Identifikationsnummer <strong>„EX"</strong> der Form <strong>LU12345678-EX</strong> — in der Regel binnen <strong>35 Arbeitstagen</strong>.</p>

<p>Die Freigrenze gilt in einem Mitgliedstaat erst <strong>ab dem Datum der Mitteilung oder Bestätigung</strong> dieser Nummer. Wichtig: eine <strong>rückwirkende Identifizierung ist nicht möglich</strong>. Vor Erhalt der Nummer getätigte Verkäufe lassen sich nicht nachträglich einbeziehen.</p>

<h3>Ihre Meldepflichten</h3>

<ul>
    <li><strong>Vierteljährliche Meldung</strong> an die AED des Gesamtumsatzes in <strong>allen</strong> Mitgliedstaaten — Luxemburg eingeschlossen</li>
    <li>Im Gegenzug müssen Sie sich in den Mitgliedstaaten, in denen Sie nicht ansässig sind, für die von der Freigrenze erfassten Umsätze weder registrieren noch MwSt.-Erklärungen abgeben</li>
    <li>Für die dort <strong>nicht erfassten</strong> Umsätze (insbesondere innergemeinschaftliche Erwerbe) gelten weiterhin die örtlichen Pflichten</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Wenn Sie die Unionsschwelle überschreiten</p>
    <p>Über 100 000 € Umsatz in der Union verlieren Sie die Freigrenze in den Mitgliedstaaten, in denen Sie nicht ansässig sind — <strong>auch im folgenden Kalenderjahr</strong>, selbst wenn die nationalen Schwellen dort eingehalten werden. Die nationale Freigrenze in Luxemburg behalten Sie hingegen, solange Sie dort die Voraussetzungen erfüllen.</p>
</div>

<h2>Wann ins Normalregime wechseln?</h2>

<h3>Zwingender Wechsel (Schwellenüberschreitung)</h3>

<p>Die Regel hängt vom <strong>Ausmaß der Überschreitung</strong> der 50 000-€-Schwelle ab:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Wirkung</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Überschreitung um höchstens 10 % (Umsatz bis 55 000 €)</td><td class="p-2 border-b">Sie bleiben bis zum 31. Dezember in der Freigrenze. Wechsel ins Normalregime am <strong>1. Januar des Folgejahres</strong>.</td></tr>
        <tr><td class="p-2 border-b">Überschreitung um mehr als 10 % (Umsatz über 55 000 €)</td><td class="p-2 border-b">Die Freigrenze entfällt <strong>ab dem Tag nach der Überschreitung</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Das offizielle Beispiel der AED</p>
    <p>Ein Steuerpflichtiger erreicht 51 000 €: Überschreitung unter 10 %, er bleibt in der Freigrenze. Am <strong>8. September 2025</strong> bringt ein Verkauf von 5 000 € seinen Umsatz auf <strong>56 000 €</strong>. Die Überschreitung liegt über 10 %: er ist <strong>ab dem 9. September 2025</strong> von der Freigrenze ausgeschlossen und bleibt es das ganze Jahr 2026.</p>
    <p class="mt-2">Beachten Sie die Mechanik: es ist der <strong>Folgetag</strong>. Die Rechnung, die die Obergrenze reißt, wird noch ohne MwSt. ausgestellt; erst die nächste trägt sie.</p>
</div>

<p><strong>In beiden Fällen</strong> schließt die Überschreitung der Schwelle im Kalenderjahr Sie für das <strong>gesamte folgende Kalenderjahr</strong> von der Freigrenze aus, unabhängig vom Prozentsatz.</p>

<p>Sie müssen dann:</p>

<ol>
    <li>Die <strong>AED</strong> über MyGuichet.lu oder Ihr Steueramt über den Regimewechsel informieren</li>
    <li>Nach obigem Zeitplan mit der MwSt.-Berechnung beginnen</li>
    <li>Die von der AED festgelegte Erklärungsperiodizität einhalten</li>
</ol>

<h3>Freiwilliger Wechsel (Option für das Normalregime)</h3>

<p>Sie können <strong>auch unterhalb der Schwelle</strong> für das Normalregime optieren, mit Antrag bei Ihrem Steueramt. <strong>Die Option wirkt ab dem 1. Tag des Folgemonats</strong> und bindet Sie für <strong>mindestens ein Kalenderjahr</strong>: es ist keine Hin- und Rückfahrt.</p>

<p>Oft die richtige Wahl, wenn:</p>

<ul>
    <li>Ihre Kunden überwiegend <strong>Unternehmen (B2B)</strong> sind, die MwSt. abziehen</li>
    <li>Sie <strong>größere Investitionen</strong> haben (Ausrüstung, Firmenfahrzeug) und die Vorsteuer zurückholen wollen</li>
    <li>Sie sich der Schwelle nähern und lieber <strong>vorausschauend</strong> handeln, statt unterjährig die Preise zu ändern</li>
    <li>Sie <strong>international (innergemeinschaftliches B2B)</strong> fakturieren und eine aktive MwSt.-Nummer brauchen</li>
</ul>

<p>Ebenfalls zu beachten: bei <strong>Betriebsaufgabe</strong> ist Ihrem Steueramt binnen <strong>fünfzehn Tagen</strong> eine Erklärung zu übermitteln. Die Freigrenze endet mit dem Datum der Aufgabe.</p>

<h2>Erklärungsperiodizität nach dem Wechsel</h2>

<p>Im Normalregime richtet sich die Periodizität nach Ihrem Nettojahresumsatz. <strong>Die Jahreserklärung tritt zu den periodischen Erklärungen hinzu, sie ersetzt sie nicht:</strong></p>

<ul>
    <li><strong>Umsatz &lt; 112 000 €</strong>: nur Jahreserklärung</li>
    <li><strong>Umsatz zwischen 112 000 € und 620 000 €</strong>: Vierteljahreserklärungen <strong>und</strong> Jahreserklärung</li>
    <li><strong>Umsatz &gt; 620 000 €</strong>: Monatserklärungen <strong>und</strong> Jahreserklärung</li>
</ul>

<p>Die AED bestimmt Ihr Regime – Sie wählen es nicht selbst.</p>

<h2>Auswirkung auf Ihre Rechnungen mit faktur.lu</h2>

<p>faktur.lu beherrscht beide Regime:</p>

<ul>
    <li><strong>MwSt.-Freigrenze</strong>: die Rechnungen tragen automatisch den Vermerk „MwSt. nicht anwendbar – Artikel 57bis des geänderten Gesetzes vom 12. Februar 1979", ohne MwSt.-Zeile und ohne Netto-/Bruttounterscheidung</li>
    <li><strong>Normalregime</strong>: die MwSt. wird automatisch mit dem richtigen Satz berechnet (17 %, 14 %, 8 % oder 3 %)</li>
    <li><strong>Schwellenwarnung</strong>: faktur.lu meldet sich, wenn Sie sich der Schwelle von 50 000 € (und der Toleranz bei 55 000 €) nähern</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Schwellen, Sätze und MwSt.-Verfahren können sich ändern, und die grenzüberschreitende Regelung ist neu. Diese Seite wird regelmäßig aktualisiert – für Ihre persönliche Situation wenden Sie sich an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Sonderregelung für Kleinunternehmen (Freigrenze)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Freigrenze (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Europäische Kommission – Nationale Freigrenzen je Mitgliedstaat</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geändertes Gesetz vom 12. Februar 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Richtlinie (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">MwSt.-Sätze in Luxemburg →</a></li><li><a href="/de/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freiberufler Luxemburg: konform fakturieren →</a></li><li><a href="/de/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Verwalten Sie Ihre MwSt.-Freigrenze gelassen</h3>
    <p class="text-primary-800 mb-4">faktur.lu passt sich automatisch Ihrem MwSt.-Regime an und warnt Sie vor der Überschreitung der Schwelle von 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">In Luxembourg, small businesses with annual net turnover not exceeding <strong>€50,000</strong> can use the <strong>VAT exemption regime</strong> (article 57bis of the Luxembourg VAT law). That threshold rose from €35,000 to €50,000 on 1 January 2025, alongside a still little-known <strong>cross-border scheme</strong>. Here is everything you need to know in 2026.</p>

<h2>What is the VAT exemption regime?</h2>

<p>The VAT exemption (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">article 57bis LIVA</a>) is a <strong>special scheme</strong> allowing small businesses to exempt from VAT the goods and services they supply. In practice you:</p>

<ul>
    <li><strong>Charge no VAT</strong> to your clients</li>
    <li><strong>Remit no VAT</strong> to the State</li>
    <li>Are relieved of the obligation to file <strong>ordinary VAT returns</strong></li>
</ul>

<p>In exchange, you <strong>cannot reclaim the VAT</strong> paid on your business purchases.</p>

<h2>Eligibility conditions</h2>

<ul>
    <li>Annual net turnover <strong>of €50,000 or less</strong> over the calendar year</li>
    <li><strong>10% tolerance</strong>: if you exceed it during the year without going beyond <strong>€55,000</strong>, you stay exempt until 31 December</li>
    <li>Seat of economic activity in Luxembourg (a mere fixed establishment is not enough)</li>
    <li>The scheme is <strong>optional</strong>: you may prefer the normal regime</li>
</ul>

<p><strong>Transactions excluded from the exemption:</strong> occasional transactions referred to in article 12 of Directive 2006/112/EC, and <strong>supplies of new means of transport</strong> to another Member State. Also excluded are taxable persons under the VAT group scheme, the flat-rate scheme for farmers and foresters, or holding incompatible immovable property options.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Did you know?</p>
    <p>The threshold rose from €35,000 to €50,000 on 1 January 2025, as part of transposing <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Directive (EU) 2020/285</a>. The maximum a Member State may set is €85,000; Luxembourg provides for no sectoral thresholds.</p>
</div>

<h2>What the exemption does not free you from</h2>

<p>This is the most misunderstood part of the scheme, and it affects a lot of people. Being exempt <strong>does not mean being free of every reporting obligation</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ If you invoice business clients in the EU</p>
    <p class="text-red-700">As soon as you supply <strong>intra-community services</strong> — or become liable for VAT in Luxembourg under article 61 of the VAT law — you <strong>must</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>file a <strong>simplified annual return</strong> through the <strong>eCDF</strong> portal, <strong>before 1 March</strong> of the following calendar year;</li>
        <li>submit <strong>recapitulative statements</strong> for those intra-community services.</li>
    </ul>
    <p class="text-red-700 mt-2">A single business client in Germany, France or Belgium triggers both obligations.</p>
</div>

<p>Outside that case, an exempt taxable person reports annual turnover to their tax office — by post, by email, or using the simplified annual return form provided by the AED.</p>

<p>Finally, if you fall under the exemption and then the normal regime <strong>within the same year</strong>, the turnover achieved under the exemption goes in <strong>box 481</strong> of the VAT return filed under the normal regime.</p>

<h2>Mandatory wording on your invoices</h2>

<p>Even when exempt, your invoices must carry the following <strong>exact wording</strong>:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    "VAT not applicable – Article 57bis of the amended law of 12 February 1979"
</blockquote>

<p>You must also:</p>
<ul>
    <li><strong>Show no VAT</strong> (no VAT line on the invoice)</li>
    <li>State no <strong>VAT rate</strong></li>
    <li>Show only the <strong>net amount</strong> (no net/gross distinction)</li>
</ul>

<p>Good news since 1 January 2025: a taxable person using the exemption may issue <strong>simplified invoices</strong>.</p>

<h2>Advantages of the exemption</h2>

<ul>
    <li><strong>Simplicity</strong>: no ordinary VAT return, simplified invoicing allowed</li>
    <li><strong>B2C competitiveness</strong>: at the same net price, your invoice is around <strong>14.5% cheaper</strong> for a consumer than a normal-regime competitor's (17% VAT on €117 gross). Note, though, that this edge is eroded by the VAT you cannot reclaim on your own purchases</li>
    <li><strong>Cash flow</strong>: no gap between VAT collected and VAT remitted</li>
    <li><strong>Less paperwork</strong>: simplified administration</li>
</ul>

<h2>Drawbacks</h2>

<ul>
    <li><strong>No input VAT deduction</strong>: you pay VAT on purchases without reclaiming it (decisive if you invest)</li>
    <li><strong>B2B disadvantage</strong>: business clients deduct no VAT from your invoices, so your price costs them more</li>
    <li><strong>Image</strong>: some B2B clients prefer working with VAT-registered businesses</li>
    <li><strong>Residual obligations</strong>: as soon as you invoice services to EU businesses, the simplified annual return and recapitulative statements become mandatory again</li>
    <li><strong>Binding ceiling</strong>: beyond the threshold and its tolerance, switching to the normal regime is compulsory</li>
</ul>

<h2>The cross-border exemption scheme (since 2025)</h2>

<p>This is the second change of 1 January 2025, far less commented on than the threshold increase. Until then, the exemption applied only in your country of establishment. Now a Luxembourg small business can <strong>use the exemption in other Member States</strong>.</p>

<h3>The conditions</h3>

<ul>
    <li>Respect the <strong>national threshold of each Member State</strong> where you want the exemption (thresholds vary: see the <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">European SME VAT rules portal</a>)</li>
    <li>Achieve turnover <strong>below €100,000 across the whole Union</strong> — the "Union threshold", which includes your Luxembourg sales</li>
    <li>Have the <strong>seat of your economic activity</strong> in Luxembourg. A taxable person seated in a third country cannot use it, even with a fixed establishment in the EU</li>
</ul>

<h3>The "EX" number</h3>

<p>The procedure runs through a <strong>prior notification</strong> in your business area on <strong>MyGuichet.lu</strong>. The AED forwards the request to the Member States concerned. As soon as at least one accepts, you receive an <strong>"EX"</strong> identification number of the form <strong>LU12345678-EX</strong> — generally within <strong>35 working days</strong>.</p>

<p>The exemption only starts applying in a Member State <strong>from the date that number is communicated or confirmed</strong>. Important: <strong>retroactive identification is not possible</strong>. Sales made before you obtain the number cannot be brought in afterwards.</p>

<h3>Your reporting obligations</h3>

<ul>
    <li><strong>Quarterly declaration</strong> to the AED of total turnover across <strong>all</strong> Member States — Luxembourg included</li>
    <li>In exchange, you need not register or file VAT returns in Member States where you are not established, for transactions covered by the exemption</li>
    <li>Transactions <strong>not covered</strong> by the exemption there (intra-community acquisitions in particular) remain subject to local obligations</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">If you exceed the Union threshold</p>
    <p>Beyond €100,000 of turnover in the Union, you lose the exemption in Member States where you are not established — <strong>including the following calendar year</strong>, even if the national thresholds are respected there. You do, however, keep the national exemption in Luxembourg as long as you meet the conditions.</p>
</div>

<h2>When to move to the normal regime?</h2>

<h3>Compulsory switch (exceeding the threshold)</h3>

<p>The rule depends on <strong>how far</strong> you exceed the €50,000 threshold:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situation</th>
            <th class="text-left p-2 bg-slate-100">Effect</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Exceeded by 10% at most (turnover up to €55,000)</td><td class="p-2 border-b">You stay exempt until 31 December. Switch to the normal regime on <strong>1 January of the following year</strong>.</td></tr>
        <tr><td class="p-2 border-b">Exceeded by more than 10% (turnover above €55,000)</td><td class="p-2 border-b">The exemption ceases to apply <strong>from the day after the threshold is exceeded</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">The AED's official example</p>
    <p>A taxable person reaches €51,000: exceeded by less than 10%, they stay exempt. On <strong>8 September 2025</strong>, a €5,000 sale brings turnover to <strong>€56,000</strong>. The excess is over 10%: they are excluded from the exemption <strong>from 9 September 2025</strong>, and remain excluded throughout 2026.</p>
    <p class="mt-2">Note the mechanics: it is the <strong>following day</strong>. The invoice that breaks the ceiling is still issued exempt; it is the next one that carries VAT.</p>
</div>

<p><strong>In both cases</strong>, exceeding the threshold during a calendar year excludes you from the exemption for the <strong>whole of the following calendar year</strong>, whatever the percentage.</p>

<p>You must then:</p>

<ol>
    <li>Inform the <strong>AED</strong> of the change of regime via MyGuichet.lu or your tax office</li>
    <li>Start charging VAT on the timetable above</li>
    <li>Comply with the filing frequency determined by the AED</li>
</ol>

<h3>Voluntary switch (opting for the normal regime)</h3>

<p>You may opt for the normal regime <strong>even below the threshold</strong>, by applying to your tax office. <strong>The option takes effect on the 1st day of the following month</strong> and commits you for <strong>at least one calendar year</strong>: it is not a round trip.</p>

<p>It is often the right call if:</p>

<ul>
    <li>Your clients are mostly <strong>businesses (B2B)</strong> that deduct VAT</li>
    <li>You have <strong>significant investments</strong> (equipment, company vehicle) and want to reclaim input VAT</li>
    <li>You are approaching the threshold and prefer to <strong>plan ahead</strong> rather than change your pricing mid-year</li>
    <li>You invoice <strong>internationally (intra-EU B2B)</strong> and need an active VAT number</li>
</ul>

<p>Also worth noting: on <strong>cessation of activity</strong>, a declaration must reach your tax office within <strong>fifteen days</strong>. The exemption ends on the date of cessation.</p>

<h2>VAT filing frequency after the switch</h2>

<p>Once in the normal regime, frequency depends on your annual net turnover. <strong>The annual return is in addition to the periodic ones, not instead of them:</strong></p>

<ul>
    <li><strong>Turnover &lt; €112,000</strong>: annual return only</li>
    <li><strong>Turnover between €112,000 and €620,000</strong>: quarterly returns <strong>and</strong> an annual return</li>
    <li><strong>Turnover &gt; €620,000</strong>: monthly returns <strong>and</strong> an annual return</li>
</ul>

<p>The AED determines your regime — you do not choose it.</p>

<h2>Impact on your invoices with faktur.lu</h2>

<p>faktur.lu handles both regimes:</p>

<ul>
    <li><strong>VAT exemption</strong>: invoices automatically display "VAT not applicable – Article 57bis of the amended law of 12 February 1979", with no VAT line and no net/gross distinction</li>
    <li><strong>Normal regime</strong>: VAT is calculated automatically at the right rate (17%, 14%, 8% or 3%)</li>
    <li><strong>Threshold alert</strong>: faktur.lu warns you as you approach €50,000 (and the €55,000 tolerance) so you can plan the switch</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>VAT thresholds, rates and procedures may change, and the cross-border scheme is recent. This page is updated regularly, but for your own situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a> directly.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Special scheme for small enterprises (exemption)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – Exemption FAQ (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">European Commission – National exemption thresholds by Member State</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Amended law of 12 February 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Directive (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">VAT rates in Luxembourg →</a></li><li><a href="/en/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freelancing in Luxembourg: invoicing in compliance →</a></li><li><a href="/en/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Manage your VAT exemption with confidence</h3>
    <p class="text-primary-800 mb-4">faktur.lu adapts automatically to your VAT regime and alerts you before you cross the €50,000 threshold.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Zu Lëtzebuerg kënne kleng Entreprisen, deenen hire Joresëmsaz ouni TVA 50 000 € net iwwerschreit, vun der <strong>TVA-Franchise</strong> profitéieren (Artikel 57bis vum Lëtzebuerger TVA-Gesetz). Dëse Seuil ass den 1. Januar 2025 vun 35 000 € op 50 000 € eropgesat ginn — zur selwechter Zäit wéi e nach wéineg bekannte <strong>transfrontaliere Regime</strong>. Hei ass alles, wat een 2026 wësse muss.</p>

<h2>Wat ass d'TVA-Franchise?</h2>

<p>D'TVA-Franchise (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">Artikel 57bis LIVA</a>) ass e <strong>besonnesche Regime</strong>, deen et klengen Entreprisen erlaabt, hir Liwwerungen a Leeschtunge vun der TVA ze befreien. Konkret:</p>

<ul>
    <li>Dir fakturéiert Äre Clienten <strong>keng TVA</strong></li>
    <li>Dir féiert <strong>keng TVA</strong> un de Staat of</li>
    <li>Dir sidd vun der Flicht befreit, <strong>ordinär TVA-Deklaratiounen</strong> anzereechen</li>
</ul>

<p>Als Géigeleeschtung kënnt Dir d'TVA op Är berufflech Akeef <strong>net zréckhuelen</strong>.</p>

<h2>Bedingungen</h2>

<ul>
    <li>Joresëmsaz ouni TVA vun <strong>héchstens 50 000 €</strong> am Kalennerjoer</li>
    <li><strong>Toleranz vun 10 %</strong>: iwwerschreit Dir am Laf vum Joer ouni iwwer <strong>55 000 €</strong> ze goen, bleift Dir bis den 31. Dezember an der Franchise</li>
    <li>Sëtz vun der wirtschaftlecher Aktivitéit zu Lëtzebuerg (e bloussen Etablissement stable duergeet net)</li>
    <li>De Regime ass <strong>optional</strong>: Dir kënnt de Normalregime virzéien</li>
</ul>

<p><strong>Vun der Franchise ausgeschloss Operatiounen:</strong> geleeëntlech Operatiounen no Artikel 12 vun der Direktiv 2006/112/EG, an d'<strong>Liwwerunge vun neie Verkéiersmëttel</strong> an en anere Member-Staat. Ausgeschloss sinn ausserdeem Assujettien am Regime vun der TVA-Grupp, am Forfaitsregime fir Bauer a Fierschter, oder mat onvereinbaren Immobiliekoptiounen.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Wousst Dir?</p>
    <p>De Seuil gouf den 1. Januar 2025 vun 35 000 € op 50 000 € eropgesat, am Kader vun der Ëmsetzung vun der <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Direktiv (EU) 2020/285</a>. De maximale Plafong, deen e Member-Staat festleeë kann, läit bei 85 000 €; Lëtzebuerg gesäit keng sektoriell Seuilen vir.</p>
</div>

<h2>Wovun d'Franchise Iech net befreit</h2>

<p>Dat ass de meescht mëssverstanene Punkt vum Regime, an en betrëfft vill Leit. An der Franchise ze sinn heescht <strong>net, vun alle Meldeflichte befreit ze sinn</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Wann Dir professionell Clienten an der EU fakturéiert</p>
    <p class="text-red-700">Soubal Dir <strong>innergemeinschaftlech Déngschtleeschtungen</strong> erbréngt — oder no Artikel 61 vum TVA-Gesetz zu Lëtzebuerg Schëllner vun der TVA gitt — musst Dir <strong>obligatoresch</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>eng <strong>vereinfacht Jooresdeklaratioun</strong> iwwer de Portal <strong>eCDF</strong> areechen, a zwar <strong>virum 1. Mäerz</strong> vum nächste Kalennerjoer;</li>
        <li><strong>Récapitulatiffen</strong> zu dësen innergemeinschaftlechen Déngschtleeschtungen ofginn.</li>
    </ul>
    <p class="text-red-700 mt-2">E eenzege professionelle Client an Däitschland, a Frankräich oder an der Belsch léist béid Flichten aus.</p>
</div>

<p>Ausserhalb vun dësem Fall deelt den Assujetti an der Franchise säi Joresëmsaz sengem Steierbüro mat — per Post, per E-Mail, oder mam Formulaire vun der vereinfachter Jooresdeklaratioun, deen d'AED zur Verfügung stellt.</p>

<p>Falls Dir <strong>am selwechte Joer</strong> ënner d'Franchise an duerno ënnert de Normalregime fält, gëtt den ënner der Franchise realiséierten Ëmsaz an d'<strong>Case 481</strong> vun der TVA-Deklaratioun agedroen, déi am Normalregime ofzeginn ass.</p>

<h2>Obligatoresch Mentiounen op Äre Rechnungen</h2>

<p>Och an der Franchise mussen Är Rechnungen dës <strong>genee Mentioun</strong> droen:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    „TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979"
</blockquote>

<p>Dir musst ausserdeem:</p>
<ul>
    <li><strong>Keng TVA uginn</strong> (keng TVA-Linn op der Rechnung)</li>
    <li>Kee <strong>TVA-Taux</strong> uginn</li>
    <li>Nëmmen de <strong>Nettobetrag</strong> uginn (keng Ënnerscheedung ouni/mat TVA)</li>
</ul>

<p>Gutt Neiegkeet zanter dem 1. Januar 2025: wien d'Franchise notzt, däerf <strong>vereinfacht Rechnungen</strong> ausstellen.</p>

<h2>Virdeeler vun der Franchise</h2>

<ul>
    <li><strong>Einfachheet</strong>: keng ordinär TVA-Deklaratioun, vereinfacht Fakturatioun erlaabt</li>
    <li><strong>B2C-Konkurrenzfäegkeet</strong>: bei gläichem Nettopräis ass Är Rechnung fir eng Privatpersoun ronn <strong>14,5 % méi bëlleg</strong> wéi déi vun engem Concurrent am Normalregime (17 % TVA op 117 € brutto). Opgepasst awer: dëse Virsprong gëtt duerch déi net erstatt TVA op Är eege Akeef geschmälert</li>
    <li><strong>Tresorerie</strong>: keng Verschiebung tëscht agehuelener an ofgefouerter TVA</li>
    <li><strong>Manner Pabeierkrom</strong>: vereinfacht Administratioun</li>
</ul>

<h2>Nodeeler</h2>

<ul>
    <li><strong>Kee Virsteierofzuch</strong>: Dir bezuelt TVA op Är Akeef, ouni se zréckzehuelen (entscheedend, wann Dir investéiert)</li>
    <li><strong>B2B-Nodeel</strong>: professionell Clienten zéien aus Äre Rechnunge keng TVA of, Äre Präis kënnt hinnen also méi deier</li>
    <li><strong>Image</strong>: gewësse B2B-Clienten schaffe léiwer mat assujettéierten Entreprisen</li>
    <li><strong>Restflichten</strong>: soubal Dir Leeschtungen un EU-Professioneller fakturéiert, gi vereinfacht Jooresdeklaratioun a Récapitulatiffen erëm obligatoresch</li>
    <li><strong>Enke Plafong</strong>: iwwer de Seuil a seng Toleranz eraus ass de Wiessel an den Normalregime zwéngend</li>
</ul>

<h2>Den transfrontaliere Franchiseregime (zanter 2025)</h2>

<p>Dat ass déi zweet Neierung vum 1. Januar 2025, vill manner kommentéiert wéi d'Erhéijung vum Seuil. Bis dohin gouf d'Franchise nëmmen am Etablissementsstaat. Elo kann eng Lëtzebuerger kleng Entreprise <strong>d'Franchise och an anere Member-Staaten</strong> notzen.</p>

<h3>D'Bedingungen</h3>

<ul>
    <li>De <strong>nationale Seuil vun all Member-Staat</strong> respektéieren, an deem Dir d'Franchise wëllt (d'Seuiler variéieren: kuckt de <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">europäesche Portal SME VAT rules</a>)</li>
    <li>En Ëmsaz vun <strong>ënner 100 000 € an der ganzer Unioun</strong> realiséieren — de „Seuil vun der Unioun", deen Är Verkeef zu Lëtzebuerg abezitt</li>
    <li>De <strong>Sëtz vun Ärer wirtschaftlecher Aktivitéit</strong> zu Lëtzebuerg hunn. Wien säi Sëtz an engem Drëttland huet, kann en net notzen, och net mat engem Etablissement stable an der EU</li>
</ul>

<h3>D'Nummer „EX"</h3>

<p>D'Prozedur leeft iwwer eng <strong>virdrun Notifikatioun</strong> an Ärem beruffleche Raum op <strong>MyGuichet.lu</strong>. D'AED leet d'Ufro un déi betraffe Member-Staaten weider. Soubal op mannst ee se akzeptéiert, kritt Dir eng Identifikatiounsnummer <strong>„EX"</strong> vun der Form <strong>LU12345678-EX</strong> — allgemeng bannent <strong>35 Aarbechtsdeeg</strong>.</p>

<p>D'Franchise fänkt an engem Member-Staat eréischt <strong>vum Datum vun der Matdeelung oder Bestätegung</strong> vun dëser Nummer un ze gëllen. Wichteg: eng <strong>réckwierkend Identifizéierung ass net méiglech</strong>. Verkeef virum Erhale vun der Nummer sinn net nozehuelen.</p>

<h3>Är Meldeflichten</h3>

<ul>
    <li><strong>Trimestriell Deklaratioun</strong> un d'AED vum Gesamtëmsaz an <strong>alle</strong> Member-Staaten — Lëtzebuerg abegraff</li>
    <li>Als Géigeleeschtung musst Dir Iech an de Member-Staaten, wou Dir net etabléiert sidd, fir déi vun der Franchise gedeckten Operatiounen weder identifizéieren nach TVA-Deklaratiounen ofginn</li>
    <li>Fir déi do <strong>net gedeckten</strong> Operatiounen (besonnesch innergemeinschaftlech Acquisitiounen) gëllen d'lokal Flichte weider</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Wann Dir de Seuil vun der Unioun iwwerschreit</p>
    <p>Iwwer 100 000 € Ëmsaz an der Unioun verléiert Dir d'Franchise an de Member-Staaten, wou Dir net etabléiert sidd — <strong>och am nächste Kalennerjoer</strong>, och wann d'national Seuiler do respektéiert ginn. D'national Franchise zu Lëtzebuerg behaalt Dir hannergéint, soulaang Dir do d'Bedingungen erfëllt.</p>
</div>

<h2>Wéini an den Normalregime wiesselen?</h2>

<h3>Zwéngende Wiessel (Iwwerschreide vum Seuil)</h3>

<p>D'Reegel hänkt vum <strong>Ausmooss vun der Iwwerschreidung</strong> vum Seuil vun 50 000 € of:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situatioun</th>
            <th class="text-left p-2 bg-slate-100">Effet</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Iwwerschreidung ëm héchstens 10 % (Ëmsaz bis 55 000 €)</td><td class="p-2 border-b">Dir bleift bis den 31. Dezember an der Franchise. Wiessel an den Normalregime den <strong>1. Januar vum nächste Joer</strong>.</td></tr>
        <tr><td class="p-2 border-b">Iwwerschreidung ëm méi wéi 10 % (Ëmsaz iwwer 55 000 €)</td><td class="p-2 border-b">D'Franchise hält op ze gëllen <strong>vum Dag no der Iwwerschreidung un</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Dat offiziellt Beispill vun der AED</p>
    <p>En Assujetti erreecht 51 000 €: Iwwerschreidung ënner 10 %, hie bleift an der Franchise. Den <strong>8. September 2025</strong> bréngt e Verkaf vu 5 000 € säin Ëmsaz op <strong>56 000 €</strong>. D'Iwwerschreidung läit iwwer 10 %: hien ass <strong>vum 9. September 2025 un</strong> aus der Franchise ausgeschloss, a bleift et d'ganzt Joer 2026.</p>
    <p class="mt-2">Bemierkt d'Mechanik: et ass den <strong>Dag duerno</strong>. D'Rechnung, déi de Plafong sprengt, gëtt nach an der Franchise ausgestallt; eréischt déi nächst dréit d'TVA.</p>
</div>

<p><strong>An allen zwee Fäll</strong> schléisst d'Iwwerschreide vum Seuil am Kalennerjoer Iech fir dat <strong>ganzt nächst Kalennerjoer</strong> aus der Franchise aus, egal wéi héich de Prozentsaz ass.</p>

<p>Dir musst dann:</p>

<ol>
    <li>D'<strong>AED</strong> iwwer MyGuichet.lu oder Äre Steierbüro iwwer de Regimewiessel informéieren</li>
    <li>No uewestoenden Zäitplang ufänken TVA ze fakturéieren</li>
    <li>Déi vun der AED festgeluecht Deklaratiounsperiodizitéit anhalen</li>
</ol>

<h3>Fräiwëllege Wiessel (Optioun fir den Normalregime)</h3>

<p>Dir kënnt <strong>och ënner dem Seuil</strong> fir den Normalregime optéieren, mat enger Ufro bei Ärem Steierbüro. <strong>D'Optioun gëllt vum 1. Dag vum nächste Mount un</strong> a bënnt Iech fir <strong>op mannst ee Kalennerjoer</strong>: et ass keng Hin- an Hierfaart.</p>

<p>Dacks déi richteg Wiel, wann:</p>

<ul>
    <li>Är Clienten haaptsächlech <strong>Entreprisen (B2B)</strong> sinn, déi TVA ofzéien</li>
    <li>Dir <strong>wichteg Investissementer</strong> hutt (Material, Beruffsgefier) an d'Virsteier zréckhuele wëllt</li>
    <li>Dir Iech dem Seuil nätert an et virzitt, <strong>virauszekucken</strong>, amplaz d'Tariffer am Laf vum Joer z'änneren</li>
    <li>Dir <strong>international (intra-EU B2B)</strong> fakturéiert an eng aktiv TVA-Nummer braucht</li>
</ul>

<p>Och ze bemierken: bei <strong>Aktivitéitsopgab</strong> muss eng Deklaratioun bannent <strong>fofzéng Deeg</strong> bei Ärem Steierbüro sinn. D'Franchise hält um Datum vun der Opgab op.</p>

<h2>Deklaratiounsperiodizitéit nom Wiessel</h2>

<p>Am Normalregime hänkt d'Periodizitéit vun Ärem Joresëmsaz ouni TVA of. <strong>D'Jooresdeklaratioun kënnt zu de periodeschen Deklaratiounen derbäi, si ersetzt se net:</strong></p>

<ul>
    <li><strong>Ëmsaz &lt; 112 000 €</strong>: nëmmen d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz tëscht 112 000 € an 620 000 €</strong>: Trimesterdeklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
    <li><strong>Ëmsaz &gt; 620 000 €</strong>: Méintlech Deklaratiounen <strong>an</strong> d'Jooresdeklaratioun</li>
</ul>

<p>Et ass d'AED, déi Äert Regime bestëmmt — Dir wielt et net.</p>

<h2>Impakt op Är Rechnunge mat faktur.lu</h2>

<p>faktur.lu beherrscht allen zwee Regimer:</p>

<ul>
    <li><strong>TVA-Franchise</strong>: d'Rechnungen weisen automatesch d'Mentioun „TVA net applicabel – Artikel 57bis vum geännerte Gesetz vum 12. Februar 1979", ouni TVA-Linn an ouni Ënnerscheedung</li>
    <li><strong>Normalregime</strong>: d'TVA gëtt automatesch mam richtegen Taux berechent (17 %, 14 %, 8 % oder 3 %)</li>
    <li><strong>Seuil-Alert</strong>: faktur.lu warnt Iech, wann Dir Iech dem Seuil vun 50 000 € (an der Toleranz bei 55 000 €) nätert</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'Seuilen, Tariffer an TVA-Prozedure kënnen änneren, an den transfrontaliere Regime ass rezent. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är perséinlech Situatioun frot Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Besonnesche Regime fir kleng Entreprisen (Franchise)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Franchise (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Europäesch Kommissioun – National Franchise-Seuiler pro Member-Staat</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Geännert Gesetz vum 12. Februar 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Direktiv (EU) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">TVA-Tariffer zu Lëtzebuerg →</a></li><li><a href="/lb/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer Lëtzebuerg: konform fakturéieren →</a></li><li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Geréiert Är TVA-Franchise gelooss</h3>
    <p class="text-primary-800 mb-4">faktur.lu passt sech automatesch Ärem TVA-Regime un a warnt Iech virum Iwwerschreide vum Seuil vun 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">No Luxemburgo, as pequenas empresas cujo volume de negócios anual sem IVA não excede <strong>50 000 €</strong> podem beneficiar do <strong>regime de isenção de IVA</strong> (artigo 57bis da lei do IVA luxemburguesa). Este limiar passou de 35 000 € para 50 000 € a 1 de janeiro de 2025, ao mesmo tempo que surgiu um <strong>regime transfronteiriço</strong> ainda pouco conhecido. Eis tudo o que é preciso saber em 2026.</p>

<h2>O que é a isenção de IVA?</h2>

<p>A isenção de IVA (<a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">artigo 57bis LIVA</a>) é um <strong>regime especial</strong> que permite às pequenas empresas isentar de IVA os bens e serviços que vendem. Na prática:</p>

<ul>
    <li><strong>Não fatura IVA</strong> aos seus clientes</li>
    <li><strong>Não entrega IVA</strong> ao Estado</li>
    <li>Está dispensado da obrigação de apresentar <strong>declarações de IVA ordinárias</strong></li>
</ul>

<p>Em contrapartida, <strong>não pode recuperar o IVA</strong> pago nas suas compras profissionais.</p>

<h2>Condições de elegibilidade</h2>

<ul>
    <li>Volume de negócios anual sem IVA <strong>igual ou inferior a 50 000 €</strong> no ano civil</li>
    <li><strong>Tolerância de 10 %</strong>: se ultrapassar durante o ano sem exceder <strong>55 000 €</strong>, mantém-se isento até 31 de dezembro</li>
    <li>Sede da atividade económica no Luxemburgo (um simples estabelecimento estável não basta)</li>
    <li>O regime é <strong>opcional</strong>: pode preferir o regime normal</li>
</ul>

<p><strong>Operações excluídas da isenção:</strong> as operações ocasionais referidas no artigo 12.º da Diretiva 2006/112/CE, e as <strong>entregas de meios de transporte novos</strong> para outro Estado-Membro. Estão igualmente excluídos os sujeitos passivos do regime do grupo de IVA, do regime forfetário agrícola e florestal, ou com opções imobiliárias incompatíveis.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Sabia que?</p>
    <p>O limiar subiu de 35 000 € para 50 000 € a 1 de janeiro de 2025, no âmbito da transposição da <a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Diretiva (UE) 2020/285</a>. O teto máximo que um Estado-Membro pode fixar é de 85 000 €; o Luxemburgo não prevê limiares setoriais.</p>
</div>

<h2>Aquilo de que a isenção não o dispensa</h2>

<p>É o ponto mais mal compreendido do regime, e afeta muita gente. Estar isento <strong>não significa estar dispensado de todas as obrigações declarativas</strong>.</p>

<div class="bg-red-50 border-l-4 border-red-500 p-4 my-6">
    <p class="font-semibold text-red-800">⚠️ Se fatura clientes profissionais na UE</p>
    <p class="text-red-700">A partir do momento em que realiza <strong>prestações de serviços intracomunitárias</strong> — ou se torna devedor de IVA no Luxemburgo ao abrigo do artigo 61 da lei do IVA — deve <strong>obrigatoriamente</strong>:</p>
    <ul class="text-red-700 mt-2">
        <li>entregar uma <strong>declaração anual simplificada</strong> através do portal <strong>eCDF</strong>, <strong>antes de 1 de março</strong> do ano civil seguinte;</li>
        <li>apresentar <strong>mapas recapitulativos</strong> relativos a essas prestações de serviços intracomunitárias.</li>
    </ul>
    <p class="text-red-700 mt-2">Um único cliente profissional na Alemanha, em França ou na Bélgica desencadeia ambas as obrigações.</p>
</div>

<p>Fora desse caso, o sujeito passivo isento comunica o seu volume de negócios anual à repartição de finanças — por correio, por e-mail, ou através do formulário de declaração anual simplificada disponibilizado pela AED.</p>

<p>Por fim, se estiver sob isenção e depois sob o regime normal <strong>no mesmo ano</strong>, o volume de negócios realizado sob isenção indica-se no <strong>campo 481</strong> da declaração de IVA entregue no âmbito do regime normal.</p>

<h2>Menções obrigatórias nas suas faturas</h2>

<p>Mesmo em isenção de IVA, as suas faturas devem conter a <strong>menção exata</strong> seguinte:</p>

<blockquote class="border-l-4 border-primary-500 pl-4 italic text-slate-600">
    «IVA não aplicável – Artigo 57bis da lei alterada de 12 de fevereiro de 1979»
</blockquote>

<p>Deve igualmente:</p>
<ul>
    <li><strong>Não mencionar IVA</strong> (sem linha de IVA na fatura)</li>
    <li>Não indicar qualquer <strong>taxa de IVA</strong></li>
    <li>Indicar apenas o <strong>montante líquido</strong> (sem distinção sem IVA/com IVA)</li>
</ul>

<p>Boa notícia desde 1 de janeiro de 2025: o sujeito passivo que beneficia da isenção está autorizado a emitir <strong>faturas simplificadas</strong>.</p>

<h2>Vantagens da isenção</h2>

<ul>
    <li><strong>Simplicidade</strong>: sem declaração de IVA ordinária, faturação simplificada autorizada</li>
    <li><strong>Competitividade B2C</strong>: a preço líquido igual, a sua fatura é cerca de <strong>14,5 % mais barata</strong> para um particular do que a de um concorrente no regime normal (17 % de IVA sobre 117 € com IVA). Atenção, porém: essa vantagem é reduzida pelo IVA que não recupera nas suas próprias compras</li>
    <li><strong>Tesouraria</strong>: sem desfasamento entre IVA recebido e IVA entregue</li>
    <li><strong>Menos burocracia</strong>: administração simplificada</li>
</ul>

<h2>Inconvenientes</h2>

<ul>
    <li><strong>Sem dedução do IVA a montante</strong>: paga IVA nas compras sem o recuperar (decisivo se investir)</li>
    <li><strong>Desvantagem B2B</strong>: os clientes empresas não deduzem IVA das suas faturas, pelo que o seu preço lhes sai mais caro</li>
    <li><strong>Imagem</strong>: alguns clientes B2B preferem trabalhar com empresas sujeitas a IVA</li>
    <li><strong>Obrigações residuais</strong>: assim que fatura serviços a profissionais da UE, a declaração anual simplificada e os mapas recapitulativos voltam a ser obrigatórios</li>
    <li><strong>Teto restritivo</strong>: ultrapassado o limiar e a sua tolerância, a passagem ao regime normal impõe-se</li>
</ul>

<h2>O regime de isenção transfronteiriço (desde 2025)</h2>

<p>É a segunda novidade de 1 de janeiro de 2025, bem menos comentada do que a subida do limiar. Até então, a isenção só valia no país de estabelecimento. Agora, uma pequena empresa luxemburguesa pode <strong>beneficiar da isenção noutros Estados-Membros</strong>.</p>

<h3>As condições</h3>

<ul>
    <li>Respeitar o <strong>limiar nacional de cada Estado-Membro</strong> onde pretende a isenção (os limiares variam: consulte o <a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">portal europeu SME VAT rules</a>)</li>
    <li>Realizar um volume de negócios <strong>inferior a 100 000 € em toda a União</strong> — o «limiar da União», que inclui as suas vendas no Luxemburgo</li>
    <li>Ter a <strong>sede da sua atividade económica</strong> no Luxemburgo. Um sujeito passivo com sede num país terceiro não pode beneficiar dele, mesmo com um estabelecimento estável na UE</li>
</ul>

<h3>O número «EX»</h3>

<p>O procedimento passa por uma <strong>notificação prévia</strong> no seu espaço profissional em <strong>MyGuichet.lu</strong>. A AED transmite o pedido aos Estados-Membros em causa. Assim que pelo menos um aceite, recebe um número de identificação <strong>«EX»</strong>, com a forma <strong>LU12345678-EX</strong> — geralmente no prazo de <strong>35 dias úteis</strong>.</p>

<p>A isenção só começa a aplicar-se num Estado-Membro <strong>a partir da data de comunicação ou de confirmação</strong> desse número. Ponto importante: <strong>não é possível qualquer identificação retroativa</strong>. As vendas realizadas antes da obtenção do número não são recuperáveis.</p>

<h3>As suas obrigações declarativas</h3>

<ul>
    <li><strong>Declaração trimestral</strong> à AED do volume de negócios total realizado em <strong>todos</strong> os Estados-Membros — incluindo o Luxemburgo</li>
    <li>Em contrapartida, não tem de se identificar nem entregar declarações de IVA nos Estados-Membros onde não está estabelecido, quanto às operações abrangidas pela isenção</li>
    <li>As operações <strong>não abrangidas</strong> pela isenção nesses Estados (nomeadamente aquisições intracomunitárias) continuam sujeitas às obrigações locais</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Se ultrapassar o limiar da União</p>
    <p>Acima de 100 000 € de volume de negócios na União, perde a isenção nos Estados-Membros onde não está estabelecido — <strong>incluindo no ano civil seguinte</strong>, mesmo que os limiares nacionais aí sejam respeitados. Em contrapartida, mantém a isenção nacional no Luxemburgo enquanto cumprir as condições.</p>
</div>

<h2>Quando passar ao regime normal?</h2>

<h3>Passagem obrigatória (ultrapassagem do limiar)</h3>

<p>A regra depende do <strong>nível de ultrapassagem</strong> do limiar de 50 000 € sem IVA:</p>

<table class="w-full my-4">
    <thead>
        <tr>
            <th class="text-left p-2 bg-slate-100">Situação</th>
            <th class="text-left p-2 bg-slate-100">Efeito</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="p-2 border-b">Ultrapassagem de 10 % no máximo (até 55 000 €)</td><td class="p-2 border-b">Mantém-se isento até 31 de dezembro. Passagem ao regime normal a <strong>1 de janeiro do ano seguinte</strong>.</td></tr>
        <tr><td class="p-2 border-b">Ultrapassagem superior a 10 % (acima de 55 000 €)</td><td class="p-2 border-b">A isenção deixa de se aplicar <strong>a partir do dia seguinte ao da ultrapassagem</strong>.</td></tr>
    </tbody>
</table>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">O exemplo oficial da AED</p>
    <p>Um sujeito passivo atinge 51 000 €: ultrapassagem inferior a 10 %, mantém-se isento. A <strong>8 de setembro de 2025</strong>, uma venda de 5 000 € leva o volume de negócios a <strong>56 000 €</strong>. A ultrapassagem excede 10 %: fica excluído do regime de isenção <strong>a partir de 9 de setembro de 2025</strong>, e assim permanece durante todo o ano de 2026.</p>
    <p class="mt-2">Repare na mecânica: é o <strong>dia seguinte</strong>. A fatura que faz ultrapassar o teto é ainda emitida em isenção; é a seguinte que leva IVA.</p>
</div>

<p><strong>Em ambos os casos</strong>, ultrapassar o limiar durante um ano civil exclui-o da isenção durante <strong>todo o ano civil seguinte</strong>, qualquer que seja a percentagem.</p>

<p>Deve então:</p>

<ol>
    <li>Informar a <strong>AED</strong> da mudança de regime através do MyGuichet.lu ou da sua repartição de finanças</li>
    <li>Começar a faturar IVA segundo o calendário acima</li>
    <li>Cumprir a periodicidade declarativa determinada pela AED</li>
</ol>

<h3>Passagem voluntária (opção pelo regime normal)</h3>

<p>Pode optar pelo regime normal <strong>mesmo abaixo do limiar</strong>, mediante pedido à sua repartição de finanças. <strong>A opção produz efeitos no 1.º dia do mês seguinte</strong> e vincula-o por <strong>pelo menos um ano civil</strong>: não é uma ida e volta.</p>

<p>É muitas vezes a escolha certa se:</p>

<ul>
    <li>Os seus clientes são maioritariamente <strong>empresas (B2B)</strong> que deduzem IVA</li>
    <li>Tem <strong>investimentos importantes</strong> (equipamento, viatura profissional) e quer recuperar o IVA a montante</li>
    <li>Está a aproximar-se do limiar e prefere <strong>antecipar</strong> em vez de mudar a tarifação a meio do ano</li>
    <li>Fatura <strong>internacionalmente (B2B intra-UE)</strong> e precisa de um número de IVA ativo</li>
</ul>

<p>A reter também: em caso de <strong>cessação de atividade</strong>, deve ser enviada uma declaração à repartição de finanças no prazo de <strong>quinze dias</strong>. A isenção cessa na data da cessação.</p>

<h2>Periodicidade das declarações após a passagem</h2>

<p>Uma vez no regime normal, a periodicidade depende do volume de negócios anual sem IVA. <strong>A declaração anual acresce às declarações periódicas, não as substitui:</strong></p>

<ul>
    <li><strong>Volume de negócios &lt; 112 000 €</strong>: apenas declaração anual</li>
    <li><strong>Entre 112 000 € e 620 000 €</strong>: declarações trimestrais <strong>e</strong> declaração anual</li>
    <li><strong>Superior a 620 000 €</strong>: declarações mensais <strong>e</strong> declaração anual</li>
</ul>

<p>É a AED que determina o seu regime — não é o contribuinte que o escolhe.</p>

<h2>Impacto nas suas faturas com o faktur.lu</h2>

<p>O faktur.lu gere ambos os regimes:</p>

<ul>
    <li><strong>Isenção de IVA</strong>: as faturas apresentam automaticamente a menção «IVA não aplicável – Artigo 57bis da lei alterada de 12 de fevereiro de 1979», sem linha de IVA nem distinção</li>
    <li><strong>Regime normal</strong>: o IVA é calculado automaticamente com a taxa correta (17 %, 14 %, 8 % ou 3 %)</li>
    <li><strong>Alerta de limiar</strong>: o faktur.lu avisa-o quando se aproxima do limiar de 50 000 € (e da tolerância a 55 000 €)</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limiares, taxas e procedimentos de IVA podem evoluir, e o regime transfronteiriço é recente. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu contabilista ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/sme.html" target="_blank" rel="noopener">AED – Regime especial das pequenas empresas (isenção)</a></li>
    <li><a href="https://pfi.public.lu/dam-assets/pdf/tva/sme/faq-fr.pdf" target="_blank" rel="noopener">AED – FAQ Isenção (PDF)</a></li>
    <li><a href="https://sme-vat-rules.ec.europa.eu/" target="_blank" rel="noopener">Comissão Europeia – Limiares nacionais de isenção por Estado-Membro</a></li>
    <li><a href="https://legilux.public.lu/eli/etat/leg/loi/1979/02/12/n1/jo" target="_blank" rel="noopener">Lei alterada de 12 de fevereiro de 1979 (LIVA)</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32020L0285" target="_blank" rel="noopener">Diretiva (UE) 2020/285</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/tva-luxembourg-taux-calcul-obligations" class="text-primary-500 hover:text-primary-600 text-sm">Taxas de IVA no Luxemburgo →</a></li><li><a href="/pt/blog/freelance-luxembourg-facturer-conformite" class="text-primary-500 hover:text-primary-600 text-sm">Freelancer Luxemburgo: faturar em conformidade →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Faça a gestão da sua isenção de IVA com tranquilidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu adapta-se automaticamente ao seu regime de IVA e avisa-o antes de ultrapassar o limiar de 50 000 €.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
