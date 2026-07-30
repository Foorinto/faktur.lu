<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « facturer-etranger-depuis-luxembourg » —
 * vérification factuelle et traductions intégrales.
 *
 * La version française était la plus solide de la série : le seuil de
 * 10 000 EUR y était déjà présenté comme cumulé (biens à distance +
 * services TBE), la distinction art. 44 / art. 196 correctement traitée,
 * et les cas particuliers B2C honnêtement renvoyés à la fiduciaire.
 * Trois corrections tout de même, et un angle mort.
 *
 * 1. ANGLE MORT — L'IRLANDE DU NORD. L'article range le Royaume-Uni en
 *    bloc dans « hors UE ». C'est exact pour les services, faux pour les
 *    BIENS : en vertu du protocole Irlande / Irlande du Nord, la
 *    législation TVA de l'Union continue de s'appliquer aux biens à
 *    destination et en provenance de l'Irlande du Nord, avec des numéros
 *    de TVA préfixés « XI ». Un vendeur de biens qui suit l'article
 *    traiterait une vente en Irlande du Nord comme une exportation, avec
 *    preuve douanière, alors qu'il s'agit d'une livraison
 *    intracommunautaire exigeant la validation VIES du numéro XI et une
 *    ligne à l'état récapitulatif. Section dédiée ajoutée, plus une ligne
 *    au tableau récapitulatif.
 *
 * 2. VIDA DATÉ À TORT. L'article annonçait ViDA « à partir de 2030 ».
 *    Le calendrier réel est échelonné et commence bien avant :
 *      - janvier 2027 : extension de l'OSS ;
 *      - juillet 2028 : autoliquidation obligatoire, règles plateformes,
 *        nouvelle extension de l'OSS ;
 *      - 1er juillet 2030 : facturation électronique et déclaration
 *        numérique obligatoires pour les opérations intra-UE B2B ;
 *      - janvier 2035 : harmonisation des systèmes nationaux.
 *    Renvoyer le lecteur à 2030 lui fait manquer les échéances qui le
 *    concernent d'abord. Section dédiée ajoutée.
 *
 * 3. MULTI-DEVISES TROP LÂCHE. « Utilisez le taux de change BCE du jour
 *    de la facture (ou un taux constant convenu, à condition d'être
 *    cohérent) ». Le « taux constant convenu » ne correspond à aucune
 *    règle vérifiable. Reformulé sur ce qui est établi — conversion en
 *    euros, méthode constante, cohérence entre facture et comptabilité —
 *    et renvoi à la fiduciaire pour le choix du taux. Ajout d'une mise en
 *    garde : le taux de change DOUANIER, publié par l'Administration des
 *    douanes, est un taux distinct servant à la valeur en douane, à ne
 *    pas confondre avec la conversion TVA.
 *
 * 4. MENTION NON VÉRIFIABLE RETIRÉE : « état récapitulatif TVA
 *    mensuel/trimestriel (choix libre pour les services purs) ». La
 *    liberté de choix n'a pas pu être confirmée sur source officielle.
 *    Plutôt que de la reformuler au jugé, la parenthèse est supprimée et
 *    le lecteur renvoyé à l'AED.
 *
 * FAITS VÉRIFIÉS et confirmés exacts : autoliquidation B2B intra-UE
 * (art. 17 LIVA, art. 196 et 226 §11bis directive), TVA luxembourgeoise
 * par défaut en B2C services classiques, régime OSS et seuil cumulé de
 * 10 000 EUR, exonération à l'exportation (art. 43 §1 a) LIVA, art. 146
 * directive), impôt suisse sur les acquisitions.
 *
 * PARITÉ : allemand et luxembourgeois faisaient 3 681 et 3 800 caractères
 * contre 9 577, avec 2 liens contre 14 ; anglais et portugais guère
 * mieux. Les cas Suisse, multi-devises et sources officielles manquaient.
 *
 * ⚠️ La version luxembourgeoise mérite une relecture par un locuteur natif.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fr' => $this->fr(), 'de' => $this->de(), 'en' => $this->en(), 'lb' => $this->lb(), 'pt' => $this->pt()] as $locale => $content) {
            DB::table('blog_posts')
                ->where('translation_key', 'facturer-etranger-depuis-luxembourg')
                ->where('locale', $locale)
                ->update(['content' => $content, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure rangeait le
        // Royaume-Uni en bloc hors UE, y compris pour les biens.
    }

    private function fr(): string
    {
        return <<<'HTML'
<p class="lead">Vous êtes basé au Luxembourg et vous facturez des clients à l'étranger ? Les règles de TVA varient considérablement selon la zone géographique et le type de client. Voici un guide clair pour chaque situation, avec les bonnes mentions et bases légales (mise à jour 2026).</p>

<h2>Cas 1 : Client entreprise dans l'UE (B2B intracommunautaire)</h2>

<p>C'est le cas le plus fréquent pour les freelances et PME luxembourgeoises. Exemple : un consultant luxembourgeois facture une société allemande.</p>

<h3>Règles à appliquer</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0 % TVA)</strong> - lieu d'imposition chez le preneur (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">art. 17 LIVA, transposition art. 44 directive 2006/112/CE</a>)</li>
    <li>Le client déclare la TVA dans son pays (<strong>autoliquidation / reverse charge</strong>) - l'art. 196 directive le désigne comme redevable</li>
    <li>Vous devez vérifier le numéro TVA du client sur <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li>Mention obligatoire (art. 226 §11bis directive) : <em>« Autoliquidation - Article 196 de la directive 2006/112/CE »</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mention article 196, pas 44</p>
    <p>Beaucoup de modèles font l'erreur de mentionner « article 44 de la directive 2006/112/CE ». L'article 44 définit le <strong>lieu d'imposition</strong>. La mention obligatoire à apposer renvoie à l'<strong>article 196</strong> (qui désigne le preneur comme redevable de la TVA). Préférez la mention article 196.</p>
</div>

<h3>Documents nécessaires</h3>
<ul>
    <li>Votre numéro TVA luxembourgeois sur la facture</li>
    <li>Le numéro TVA du client (vérifié VIES, preuve à conserver)</li>
    <li>Déclaration dans l'<strong>état récapitulatif</strong> — sa périodicité est déterminée par l'AED selon votre situation</li>
</ul>

<h2>Cas 2 : Client particulier dans l'UE (B2C)</h2>

<p>Vous vendez à un particulier dans un autre pays UE. Les règles dépendent du type de prestation :</p>

<h3>Services classiques (conseil, design, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA luxembourgeoise (17 %)</strong> par défaut</li>
    <li>Pas d'autoliquidation pour les particuliers</li>
    <li><strong>Attention :</strong> certains services B2C ont des règles spécifiques (immobilier, transport, événements culturels/sportifs sur place…) et sont taxés là où ils sont exécutés. Si vous êtes dans un de ces cas, vérifiez avec votre fiduciaire.</li>
</ul>

<h3>Services électroniques (SaaS, formations en ligne, etc.)</h3>
<ul>
    <li>Vous appliquez la <strong>TVA du pays du client</strong></li>
    <li>Via le régime <strong>OSS (One-Stop Shop)</strong> : une seule déclaration pour tous les pays UE</li>
    <li>Seuil : <strong>10 000 €/an</strong> de ventes B2C dans l'UE (cumul biens à distance + services TBE). En-dessous, vous pouvez appliquer la TVA luxembourgeoise.</li>
</ul>

<h2>Cas 3 : Client hors UE (export)</h2>

<p>Vous facturez un client en Suisse, aux États-Unis, au Royaume-Uni ou dans tout autre pays hors UE.</p>

<h3>Services</h3>
<ul>
    <li>Vous facturez <strong>hors taxes (0 % TVA)</strong> - service localisé hors UE (art. 17 LIVA)</li>
    <li>Mention recommandée : <em>« TVA non applicable - service localisé hors UE »</em></li>
    <li>Pas d'état récapitulatif nécessaire (réservé aux échanges intra-UE)</li>
</ul>

<h3>Biens (export hors UE)</h3>
<ul>
    <li>Vous facturez <strong>hors taxes</strong> (exportation exonérée, art. 43 §1 a) LIVA)</li>
    <li>Vous devez conserver la <strong>preuve d'exportation</strong> (document douanier)</li>
    <li>Mention : <em>« Exonération de TVA - Article 146 directive 2006/112/CE »</em></li>
</ul>

<h3>Le cas particulier de l'Irlande du Nord</h3>

<p>Ranger le Royaume-Uni en bloc dans « hors UE » est exact pour les services, mais <strong>faux pour les biens</strong>. En vertu du protocole Irlande / Irlande du Nord, la législation TVA de l'Union continue de s'appliquer aux <strong>biens</strong> à destination et en provenance de l'Irlande du Nord.</p>

<ul>
    <li><strong>Biens vers l'Irlande du Nord</strong> : livraison intracommunautaire, et non exportation. Le client a un numéro de TVA préfixé <strong>« XI »</strong>, à valider sur VIES, et l'opération figure dans votre état récapitulatif</li>
    <li><strong>Services vers l'Irlande du Nord</strong> : régime pays tiers, comme pour le reste du Royaume-Uni</li>
    <li>Un numéro de TVA valide avec le bon préfixe est une <strong>condition de fond</strong> de l'exonération : un numéro « GB » ne permet pas de traiter l'opération en livraison intracommunautaire</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Une erreur qui coûte cher dans les deux sens</p>
    <p>Traiter une vente de biens en Irlande du Nord comme une exportation vous fait chercher une preuve douanière qui n'existera pas, et omettre l'état récapitulatif. À l'inverse, traiter une prestation de services comme intracommunautaire vous fait déclarer une opération qui n'a pas à l'être. Le critère est la <strong>nature de l'opération</strong>, pas le pays.</p>
</div>

<h2>Cas spécial : la Suisse</h2>

<p>La Suisse n'est pas dans l'UE. De nombreux freelances luxembourgeois facturent des clients suisses. Les règles :</p>

<ul>
    <li><strong>Services B2B</strong> : facturez <strong>HT</strong>, le client suisse déclare la TVA via le mécanisme de l'<a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">impôt sur les acquisitions (ESTV)</a></li>
    <li><strong>Services B2C</strong> : selon le type de service, la TVA LU peut s'appliquer (services électroniques notamment)</li>
    <li>Facturez en <strong>EUR ou CHF</strong> selon l'accord avec le client</li>
    <li>Pas d'état récapitulatif (réservé aux échanges intra-UE)</li>
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
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxembourg</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE (intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidation)</td><td class="border border-gray-300 px-4 py-2">Art. 196 directive 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (services classiques)</td><td class="border border-gray-300 px-4 py-2">17 % LU (par défaut)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (TBE / services électroniques) - &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">TVA pays client</td><td class="border border-gray-300 px-4 py-2">Régime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B hors UE</td><td class="border border-gray-300 px-4 py-2">0 % (non imposable au LU)</td><td class="border border-gray-300 px-4 py-2">« TVA non applicable - service localisé hors UE »</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export biens hors UE</td><td class="border border-gray-300 px-4 py-2">0 % (exonéré)</td><td class="border border-gray-300 px-4 py-2">Art. 146 directive 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Biens vers l'Irlande du Nord</strong></td><td class="border border-gray-300 px-4 py-2">0 % (livraison intracommunautaire)</td><td class="border border-gray-300 px-4 py-2">Art. 138 directive - numéro client préfixé « XI »</td></tr>
    </tbody>
</table>

<h2>Multi-devises</h2>

<p>Si vous facturez en devise étrangère, le montant de la TVA doit être <strong>converti en euros</strong> pour votre déclaration luxembourgeoise. Deux principes tiennent :</p>

<ul>
    <li>Retenez une <strong>méthode de conversion constante</strong> et appliquez-la à toutes vos opérations de l'exercice</li>
    <li>Assurez la <strong>cohérence entre la facture et la comptabilité</strong> : c'est le même taux qui doit s'y retrouver</li>
</ul>

<p>Le choix du taux de référence dépend de votre situation : faites-le valider par votre fiduciaire une fois pour toutes, plutôt que de l'improviser facture par facture.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ne confondez pas avec le taux douanier</p>
    <p>L'<a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Administration des douanes publie ses propres taux de change</a>, qui servent à établir la <strong>valeur en douane</strong> des marchandises. C'est un taux distinct de celui utilisé pour convertir la base d'imposition à la TVA. Si vous exportez des biens, vous manipulerez les deux.</p>
</div>

<h2>Ce qui change avec ViDA (2027-2030)</h2>

<p>Le paquet européen <strong>ViDA</strong> (« TVA à l'ère numérique ») est adopté. Contrairement à ce qu'on lit souvent, il ne commence pas en 2030 : le calendrier est <strong>échelonné</strong>, et les premières échéances arrivent bien avant.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Échéance</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Ce qui entre en vigueur</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Janvier 2027</td><td class="border border-gray-300 px-4 py-2">Première extension du guichet unique OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Juillet 2028</td><td class="border border-gray-300 px-4 py-2">Autoliquidation obligatoire dans certains cas, règles applicables aux plateformes, nouvelle extension de l'OSS aux ventes B2C domestiques</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1<sup>er</sup> juillet 2030</td><td class="border border-gray-300 px-4 py-2">Facturation électronique et déclaration numérique obligatoires pour les opérations intra-UE B2B</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Janvier 2035</td><td class="border border-gray-300 px-4 py-2">Harmonisation des systèmes nationaux existants avec la norme européenne</td></tr>
    </tbody>
</table>

<p>Si vous facturez régulièrement des clients dans l'UE, ce sont <strong>2027 et 2028</strong> qui vous concernent d'abord, pas 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">À vérifier chaque année</p>
    <p>Les règles TVA intra-UE et internationales évoluent, et le calendrier ViDA peut encore être précisé. Cette page est mise à jour régulièrement, mais pour votre situation, consultez votre fiduciaire ou l'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Sources officielles</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED - Détermination du lieu de prestation</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu - Transactions intracommunautaires</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/CE - articles 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validation TVA intra-UE</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Commission européenne - Lieu d'imposition</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article mis à jour le 30 juillet 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Articles connexes</h3><ul class="space-y-1"><li><a href="/fr/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Article 17 LIVA : autoliquidation B2B intra-UE →</a></li><li><a href="/fr/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">TVA intracommunautaire - guide complet →</a></li><li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mentions obligatoires facture LU →</a></li><li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparatif : choisir son logiciel de facturation →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Facturez à l'étranger en toute conformité</h3>
    <p class="text-primary-800 mb-4">faktur.lu détecte automatiquement le scénario TVA selon le client (pays, B2B/B2C) et applique la bonne mention. Validation VIES intégrée.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Essayer gratuitement 14 jours</a>
</div>
HTML;
    }

    private function de(): string
    {
        return <<<'HTML'
<p class="lead">Sie sind in Luxemburg ansässig und fakturieren Kunden im Ausland? Die MwSt.-Regeln unterscheiden sich erheblich je nach geografischer Zone und Kundentyp. Hier ist ein klarer Leitfaden für jede Situation, mit den richtigen Vermerken und Rechtsgrundlagen (Stand 2026).</p>

<h2>Fall 1: Unternehmenskunde in der EU (innergemeinschaftliches B2B)</h2>

<p>Das ist der häufigste Fall für luxemburgische Freiberufler und KMU. Beispiel: Ein luxemburgischer Berater fakturiert einer deutschen Gesellschaft.</p>

<h3>Anzuwendende Regeln</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong> – Ort der Besteuerung beim Leistungsempfänger (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">Art. 17 LIVA, Umsetzung von Art. 44 der Richtlinie 2006/112/EG</a>)</li>
    <li>Der Kunde meldet die MwSt. in seinem Land (<strong>Umkehrung der Steuerschuldnerschaft / Reverse Charge</strong>) – Art. 196 der Richtlinie benennt ihn als Steuerschuldner</li>
    <li>Sie müssen die MwSt.-Nummer des Kunden über <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> prüfen</li>
    <li>Pflichtvermerk (Art. 226 §11bis der Richtlinie): <em>„Umkehrung der Steuerschuldnerschaft – Artikel 196 der Richtlinie 2006/112/EG"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Vermerk Artikel 196, nicht 44</p>
    <p>Viele Vorlagen machen den Fehler, „Artikel 44 der Richtlinie 2006/112/EG" zu nennen. Artikel 44 bestimmt den <strong>Ort der Besteuerung</strong>. Der anzubringende Pflichtvermerk verweist auf <strong>Artikel 196</strong> (der den Leistungsempfänger als Steuerschuldner benennt). Bevorzugen Sie den Vermerk zu Artikel 196.</p>
</div>

<h3>Erforderliche Unterlagen</h3>
<ul>
    <li>Ihre luxemburgische MwSt.-Nummer auf der Rechnung</li>
    <li>Die MwSt.-Nummer des Kunden (über VIES geprüft, Nachweis aufbewahren)</li>
    <li>Meldung in der <strong>Zusammenfassenden Meldung</strong> – deren Periodizität legt die AED nach Ihrer Situation fest</li>
</ul>

<h2>Fall 2: Privatkunde in der EU (B2C)</h2>

<p>Sie verkaufen an eine Privatperson in einem anderen EU-Land. Die Regeln hängen von der Art der Leistung ab:</p>

<h3>Klassische Dienstleistungen (Beratung, Design usw.)</h3>
<ul>
    <li>Sie berechnen standardmäßig die <strong>luxemburgische MwSt. (17 %)</strong></li>
    <li>Kein Reverse Charge gegenüber Privatpersonen</li>
    <li><strong>Achtung:</strong> Bestimmte B2C-Leistungen unterliegen Sonderregeln (Immobilien, Beförderung, Kultur- und Sportveranstaltungen vor Ort…) und werden dort besteuert, wo sie ausgeführt werden. Trifft das auf Sie zu, klären Sie es mit Ihrem Treuhänder.</li>
</ul>

<h3>Elektronische Dienstleistungen (SaaS, Online-Schulungen usw.)</h3>
<ul>
    <li>Sie berechnen die <strong>MwSt. des Kundenlandes</strong></li>
    <li>Über das Verfahren <strong>OSS (One-Stop Shop)</strong>: eine einzige Erklärung für alle EU-Länder</li>
    <li>Schwelle: <strong>10 000 €/Jahr</strong> an B2C-Umsätzen in der EU (Summe aus Fernverkäufen von Waren und TBE-Leistungen). Darunter können Sie die luxemburgische MwSt. anwenden.</li>
</ul>

<h2>Fall 3: Kunde außerhalb der EU (Ausfuhr)</h2>

<p>Sie fakturieren einem Kunden in der Schweiz, den Vereinigten Staaten, dem Vereinigten Königreich oder einem beliebigen anderen Drittland.</p>

<h3>Dienstleistungen</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt. (0 %)</strong> – Leistungsort außerhalb der EU (Art. 17 LIVA)</li>
    <li>Empfohlener Vermerk: <em>„MwSt. nicht anwendbar – Leistungsort außerhalb der EU"</em></li>
    <li>Keine Zusammenfassende Meldung nötig (nur für innergemeinschaftlichen Verkehr)</li>
</ul>

<h3>Waren (Ausfuhr in Drittländer)</h3>
<ul>
    <li>Sie fakturieren <strong>ohne MwSt.</strong> (steuerfreie Ausfuhr, Art. 43 §1 a) LIVA)</li>
    <li>Sie müssen den <strong>Ausfuhrnachweis</strong> aufbewahren (Zolldokument)</li>
    <li>Vermerk: <em>„MwSt.-Befreiung – Artikel 146 der Richtlinie 2006/112/EG"</em></li>
</ul>

<h3>Der Sonderfall Nordirland</h3>

<p>Das Vereinigte Königreich pauschal als „Drittland" einzuordnen, stimmt für Dienstleistungen, ist aber <strong>falsch für Waren</strong>. Nach dem Protokoll zu Irland / Nordirland gilt das MwSt.-Recht der Union weiterhin für <strong>Waren</strong> nach und aus Nordirland.</p>

<ul>
    <li><strong>Waren nach Nordirland</strong>: innergemeinschaftliche Lieferung, keine Ausfuhr. Der Kunde hat eine MwSt.-Nummer mit dem Präfix <strong>„XI"</strong>, die über VIES zu prüfen ist, und der Umsatz erscheint in Ihrer Zusammenfassenden Meldung</li>
    <li><strong>Dienstleistungen nach Nordirland</strong>: Drittlandsregime, wie für das übrige Vereinigte Königreich</li>
    <li>Eine gültige MwSt.-Nummer mit dem richtigen Präfix ist eine <strong>materielle Voraussetzung</strong> der Befreiung: Mit einer „GB"-Nummer lässt sich der Umsatz nicht als innergemeinschaftliche Lieferung behandeln</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Ein Fehler, der in beide Richtungen teuer wird</p>
    <p>Behandeln Sie einen Warenverkauf nach Nordirland als Ausfuhr, suchen Sie nach einem Zollnachweis, den es nie geben wird, und lassen die Zusammenfassende Meldung aus. Umgekehrt melden Sie bei einer als innergemeinschaftlich behandelten Dienstleistung einen Umsatz, der dort nicht hingehört. Maßgeblich ist die <strong>Art des Umsatzes</strong>, nicht das Land.</p>
</div>

<h2>Sonderfall: die Schweiz</h2>

<p>Die Schweiz gehört nicht zur EU. Viele luxemburgische Freiberufler fakturieren Schweizer Kunden. Die Regeln:</p>

<ul>
    <li><strong>B2B-Dienstleistungen</strong>: fakturieren Sie <strong>ohne MwSt.</strong>, der Schweizer Kunde meldet die Steuer über die <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">Bezugsteuer (ESTV)</a></li>
    <li><strong>B2C-Dienstleistungen</strong>: je nach Art der Leistung kann luxemburgische MwSt. anfallen (insbesondere elektronische Leistungen)</li>
    <li>Fakturieren Sie in <strong>EUR oder CHF</strong> nach Absprache mit dem Kunden</li>
    <li>Keine Zusammenfassende Meldung (nur für innergemeinschaftlichen Verkehr)</li>
</ul>

<h2>Übersichtstabelle</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Szenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">MwSt.</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Vermerk</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxemburg</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (innergemeinschaftlich)</td><td class="border border-gray-300 px-4 py-2">0 % (Reverse Charge)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Richtlinie 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (klassische Leistungen)</td><td class="border border-gray-300 px-4 py-2">17 % LU (Standard)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / elektronische Leistungen) – &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">MwSt. des Kundenlandes</td><td class="border border-gray-300 px-4 py-2">OSS-Verfahren</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Drittland</td><td class="border border-gray-300 px-4 py-2">0 % (in LU nicht steuerbar)</td><td class="border border-gray-300 px-4 py-2">„MwSt. nicht anwendbar – Leistungsort außerhalb der EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Warenausfuhr in Drittländer</td><td class="border border-gray-300 px-4 py-2">0 % (befreit)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Richtlinie 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Waren nach Nordirland</strong></td><td class="border border-gray-300 px-4 py-2">0 % (innergemeinschaftliche Lieferung)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Richtlinie – Kundennummer mit Präfix „XI"</td></tr>
    </tbody>
</table>

<h2>Fremdwährungen</h2>

<p>Fakturieren Sie in Fremdwährung, muss der MwSt.-Betrag für Ihre luxemburgische Erklärung <strong>in Euro umgerechnet</strong> werden. Zwei Grundsätze gelten:</p>

<ul>
    <li>Wählen Sie eine <strong>konstante Umrechnungsmethode</strong> und wenden Sie sie auf alle Umsätze des Geschäftsjahres an</li>
    <li>Sorgen Sie für <strong>Übereinstimmung zwischen Rechnung und Buchhaltung</strong>: derselbe Kurs muss sich in beiden wiederfinden</li>
</ul>

<p>Welcher Referenzkurs zu wählen ist, hängt von Ihrer Situation ab: lassen Sie das einmal von Ihrem Treuhänder bestätigen, statt es Rechnung für Rechnung zu improvisieren.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Nicht mit dem Zollkurs verwechseln</p>
    <p>Die <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Zollverwaltung veröffentlicht eigene Wechselkurse</a>, die der Ermittlung des <strong>Zollwerts</strong> der Waren dienen. Das ist ein anderer Kurs als jener für die Umrechnung der MwSt.-Bemessungsgrundlage. Wer Waren ausführt, hat mit beiden zu tun.</p>
</div>

<h2>Was sich mit ViDA ändert (2027-2030)</h2>

<p>Das europäische Paket <strong>ViDA</strong> („MwSt. im digitalen Zeitalter") ist verabschiedet. Anders als oft zu lesen, beginnt es nicht 2030: der Zeitplan ist <strong>gestaffelt</strong>, und die ersten Fristen kommen deutlich früher.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Termin</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Was in Kraft tritt</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2027</td><td class="border border-gray-300 px-4 py-2">Erste Erweiterung des einheitlichen Schalters OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Juli 2028</td><td class="border border-gray-300 px-4 py-2">Verpflichtender Reverse Charge in bestimmten Fällen, Plattformregeln, weitere Erweiterung des OSS auf inländische B2C-Umsätze</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1. Juli 2030</td><td class="border border-gray-300 px-4 py-2">Verpflichtende elektronische Rechnungsstellung und digitale Meldung für innergemeinschaftliche B2B-Umsätze</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2035</td><td class="border border-gray-300 px-4 py-2">Harmonisierung bestehender nationaler Systeme mit der europäischen Norm</td></tr>
    </tbody>
</table>

<p>Wer regelmäßig Kunden in der EU fakturiert, den betreffen zuerst <strong>2027 und 2028</strong>, nicht 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu prüfen</p>
    <p>Die innergemeinschaftlichen und internationalen MwSt.-Regeln entwickeln sich, und der ViDA-Zeitplan kann noch präzisiert werden. Diese Seite wird regelmäßig aktualisiert – für Ihre Situation wenden Sie sich an Ihren Treuhänder oder die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offizielle Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestimmung des Leistungsorts</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu – Innergemeinschaftliche Umsätze</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Richtlinie 2006/112/EG – Artikel 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Prüfung innergemeinschaftlicher MwSt.-Nummern</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Europäische Kommission – Ort der Besteuerung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verwandte Artikel</h3><ul class="space-y-1"><li><a href="/de/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Artikel 17 LIVA: Reverse Charge B2B innergemeinschaftlich →</a></li><li><a href="/de/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">Innergemeinschaftliche MwSt. – vollständiger Leitfaden →</a></li><li><a href="/de/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Pflichtangaben auf einer luxemburgischen Rechnung →</a></li><li><a href="/de/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Vergleich: Rechnungssoftware wählen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturieren Sie ins Ausland rechtskonform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt das MwSt.-Szenario automatisch anhand des Kunden (Land, B2B/B2C) und setzt den richtigen Vermerk. VIES-Prüfung integriert.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Tage kostenlos testen</a>
</div>
HTML;
    }

    private function en(): string
    {
        return <<<'HTML'
<p class="lead">Based in Luxembourg and invoicing clients abroad? VAT rules vary considerably depending on the geographical zone and the type of client. Here is a clear guide for each situation, with the right wording and legal bases (updated 2026).</p>

<h2>Case 1: Business client in the EU (intra-community B2B)</h2>

<p>This is the most common case for Luxembourg freelancers and SMEs. Example: a Luxembourg consultant invoices a German company.</p>

<h3>Rules to apply</h3>
<ul>
    <li>You invoice <strong>excluding VAT (0%)</strong> - place of supply is the customer's (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">art. 17 LIVA, transposing art. 44 of Directive 2006/112/EC</a>)</li>
    <li>The client declares the VAT in their country (<strong>reverse charge</strong>) - art. 196 of the Directive designates them as liable</li>
    <li>You must check the client's VAT number on <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li>Mandatory wording (art. 226 §11bis of the Directive): <em>"Reverse charge - Article 196 of Directive 2006/112/EC"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Article 196, not 44</p>
    <p>Many templates make the mistake of citing "article 44 of Directive 2006/112/EC". Article 44 defines the <strong>place of supply</strong>. The mandatory wording to apply refers to <strong>article 196</strong> (which designates the customer as liable for the VAT). Prefer the article 196 wording.</p>
</div>

<h3>Required documents</h3>
<ul>
    <li>Your Luxembourg VAT number on the invoice</li>
    <li>The client's VAT number (VIES-checked, keep the evidence)</li>
    <li>Reporting in the <strong>recapitulative statement</strong> — its frequency is set by the AED according to your situation</li>
</ul>

<h2>Case 2: Private client in the EU (B2C)</h2>

<p>You sell to a private individual in another EU country. The rules depend on the type of supply:</p>

<h3>Standard services (consulting, design, etc.)</h3>
<ul>
    <li>You charge <strong>Luxembourg VAT (17%)</strong> by default</li>
    <li>No reverse charge for private individuals</li>
    <li><strong>Careful:</strong> some B2C services follow specific rules (immovable property, transport, cultural or sporting events on site…) and are taxed where they are performed. If that is your case, check with your accountant.</li>
</ul>

<h3>Electronic services (SaaS, online training, etc.)</h3>
<ul>
    <li>You charge the <strong>VAT of the client's country</strong></li>
    <li>Through the <strong>OSS (One-Stop Shop)</strong> scheme: a single return for all EU countries</li>
    <li>Threshold: <strong>€10,000/year</strong> of B2C sales in the EU (combining distance sales of goods and TBE services). Below that, you may apply Luxembourg VAT.</li>
</ul>

<h2>Case 3: Client outside the EU (export)</h2>

<p>You invoice a client in Switzerland, the United States, the United Kingdom or any other non-EU country.</p>

<h3>Services</h3>
<ul>
    <li>You invoice <strong>excluding VAT (0%)</strong> - service supplied outside the EU (art. 17 LIVA)</li>
    <li>Recommended wording: <em>"VAT not applicable - service supplied outside the EU"</em></li>
    <li>No recapitulative statement needed (reserved for intra-EU trade)</li>
</ul>

<h3>Goods (export outside the EU)</h3>
<ul>
    <li>You invoice <strong>excluding VAT</strong> (exempt export, art. 43 §1 a) LIVA)</li>
    <li>You must keep <strong>proof of export</strong> (customs document)</li>
    <li>Wording: <em>"VAT exemption - Article 146 of Directive 2006/112/EC"</em></li>
</ul>

<h3>The special case of Northern Ireland</h3>

<p>Placing the United Kingdom wholesale in the "non-EU" bucket is correct for services but <strong>wrong for goods</strong>. Under the Ireland / Northern Ireland Protocol, EU VAT legislation continues to apply to <strong>goods</strong> to and from Northern Ireland.</p>

<ul>
    <li><strong>Goods to Northern Ireland</strong>: intra-community supply, not an export. The client holds a VAT number prefixed <strong>"XI"</strong>, to be validated on VIES, and the transaction appears in your recapitulative statement</li>
    <li><strong>Services to Northern Ireland</strong>: third-country regime, as for the rest of the United Kingdom</li>
    <li>A valid VAT number with the right prefix is a <strong>substantive condition</strong> of the exemption: a "GB" number does not allow the transaction to be treated as an intra-community supply</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A mistake that costs in both directions</p>
    <p>Treating a sale of goods to Northern Ireland as an export sends you hunting for customs proof that will never exist, and skips the recapitulative statement. Conversely, treating a service as intra-community makes you report a transaction that does not belong there. The test is the <strong>nature of the transaction</strong>, not the country.</p>
</div>

<h2>Special case: Switzerland</h2>

<p>Switzerland is not in the EU. Many Luxembourg freelancers invoice Swiss clients. The rules:</p>

<ul>
    <li><strong>B2B services</strong>: invoice <strong>excluding VAT</strong>; the Swiss client declares the tax through the <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">acquisition tax mechanism (ESTV)</a></li>
    <li><strong>B2C services</strong>: depending on the type of service, Luxembourg VAT may apply (electronic services in particular)</li>
    <li>Invoice in <strong>EUR or CHF</strong> as agreed with the client</li>
    <li>No recapitulative statement (reserved for intra-EU trade)</li>
</ul>

<h2>Summary table</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Scenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">VAT</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wording</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxembourg</td><td class="border border-gray-300 px-4 py-2">17%</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (intra-EU)</td><td class="border border-gray-300 px-4 py-2">0% (reverse charge)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Directive 2006/112/EC</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (standard services)</td><td class="border border-gray-300 px-4 py-2">17% LU (default)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / electronic services) - &gt; €10k</td><td class="border border-gray-300 px-4 py-2">VAT of client's country</td><td class="border border-gray-300 px-4 py-2">OSS scheme</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (not taxable in LU)</td><td class="border border-gray-300 px-4 py-2">"VAT not applicable - service supplied outside the EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export of goods outside the EU</td><td class="border border-gray-300 px-4 py-2">0% (exempt)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Directive 2006/112/EC</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Goods to Northern Ireland</strong></td><td class="border border-gray-300 px-4 py-2">0% (intra-community supply)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Directive - client number prefixed "XI"</td></tr>
    </tbody>
</table>

<h2>Multi-currency</h2>

<p>If you invoice in a foreign currency, the VAT amount must be <strong>converted into euros</strong> for your Luxembourg return. Two principles hold:</p>

<ul>
    <li>Adopt a <strong>consistent conversion method</strong> and apply it to every transaction of the financial year</li>
    <li>Ensure <strong>consistency between the invoice and the accounts</strong>: the same rate must appear in both</li>
</ul>

<p>Which reference rate to use depends on your situation: have your accountant confirm it once and for all, rather than improvising invoice by invoice.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Do not confuse it with the customs rate</p>
    <p>The <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Customs Administration publishes its own exchange rates</a>, used to establish the <strong>customs value</strong> of goods. That is a distinct rate from the one used to convert the VAT taxable base. If you export goods, you will handle both.</p>
</div>

<h2>What changes with ViDA (2027-2030)</h2>

<p>The European <strong>ViDA</strong> package ("VAT in the Digital Age") has been adopted. Contrary to what is often written, it does not start in 2030: the timetable is <strong>staggered</strong>, and the first deadlines arrive well before.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Deadline</th>
            <th class="border border-gray-300 px-4 py-2 text-left">What takes effect</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">January 2027</td><td class="border border-gray-300 px-4 py-2">First extension of the OSS one-stop shop</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">July 2028</td><td class="border border-gray-300 px-4 py-2">Mandatory reverse charge in certain cases, platform rules, further extension of the OSS to domestic B2C supplies</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1 July 2030</td><td class="border border-gray-300 px-4 py-2">Mandatory e-invoicing and digital reporting for intra-EU B2B transactions</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">January 2035</td><td class="border border-gray-300 px-4 py-2">Harmonisation of existing national systems with the European standard</td></tr>
    </tbody>
</table>

<p>If you regularly invoice clients in the EU, it is <strong>2027 and 2028</strong> that concern you first, not 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To check every year</p>
    <p>Intra-EU and international VAT rules evolve, and the ViDA timetable may still be refined. This page is updated regularly, but for your situation consult your accountant or the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Official sources</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED - Determining the place of supply</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu - Intra-community transactions</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Directive 2006/112/EC - articles 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Intra-EU VAT validation</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">European Commission - Place of taxation</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Article updated on 30 July 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Related articles</h3><ul class="space-y-1"><li><a href="/en/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Article 17 LIVA: intra-EU B2B reverse charge →</a></li><li><a href="/en/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">Intra-community VAT - complete guide →</a></li><li><a href="/en/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mandatory details on a Luxembourg invoice →</a></li><li><a href="/en/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparison: choosing invoicing software →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice abroad in full compliance</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically detects the VAT scenario from the client (country, B2B/B2C) and applies the right wording. Built-in VIES validation.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try free for 14 days</a>
</div>
HTML;
    }

    private function lb(): string
    {
        return <<<'HTML'
<p class="lead">Sidd Dir zu Lëtzebuerg baséiert a fakturéiert Dir Clienten am Ausland? D'TVA-Reegele variéiere staark no der geografescher Zon an der Aart vu Client. Hei ass e kloere Guide fir all Situatioun, mat de richtege Mentiounen a gesetzleche Basen (Stand 2026).</p>

<h2>Fall 1: Entreprise-Client an der EU (innergemeinschaftlecht B2B)</h2>

<p>Dat ass de heefegste Fall fir Lëtzebuerger Freelancer a KMU. Beispill: e Lëtzebuerger Beroder fakturéiert enger däitscher Gesellschaft.</p>

<h3>Reegelen, déi ze applizéiere sinn</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong> – Ort vun der Besteierung beim Client (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">Art. 17 LIVA, Ëmsetzung vum Art. 44 vun der Direktiv 2006/112/EG</a>)</li>
    <li>De Client deklaréiert d'TVA a sengem Land (<strong>Autoliquidatioun / Reverse Charge</strong>) – den Art. 196 vun der Direktiv bezeechent en als Schëllner</li>
    <li>Dir musst d'TVA-Nummer vum Client op <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a> iwwerpréiwen</li>
    <li>Obligatoresch Mentioun (Art. 226 §11bis Direktiv): <em>„Autoliquidatioun – Artikel 196 vun der Direktiv 2006/112/EG"</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Mentioun Artikel 196, net 44</p>
    <p>Vill Virlage maachen de Feeler, „Artikel 44 vun der Direktiv 2006/112/EG" ze nennen. Den Artikel 44 definéiert den <strong>Ort vun der Besteierung</strong>. Déi obligatoresch Mentioun, déi unzebrénge ass, verweist op den <strong>Artikel 196</strong> (deen de Client als Schëllner vun der TVA bezeechent). Bevirzegt d'Mentioun Artikel 196.</p>
</div>

<h3>Néideg Dokumenter</h3>
<ul>
    <li>Är Lëtzebuerger TVA-Nummer op der Rechnung</li>
    <li>D'TVA-Nummer vum Client (op VIES iwwerpréift, Beweis opzebewahren)</li>
    <li>Deklaratioun am <strong>Récapitulatif</strong> – seng Periodizitéit gëtt vun der AED no Ärer Situatioun festgeluecht</li>
</ul>

<h2>Fall 2: Privatclient an der EU (B2C)</h2>

<p>Dir verkeeft un eng Privatpersoun an engem anere EU-Land. D'Reegelen hänken vun der Aart vun der Leeschtung of:</p>

<h3>Klassesch Déngschtleeschtungen (Beroodung, Design asw.)</h3>
<ul>
    <li>Dir applizéiert standardméisseg d'<strong>Lëtzebuerger TVA (17 %)</strong></li>
    <li>Keng Autoliquidatioun bei Privatpersounen</li>
    <li><strong>Opgepasst:</strong> Gewësse B2C-Leeschtungen hu spezifesch Reegelen (Immobilien, Transport, Kultur- a Sportevenementer op der Plaz…) a ginn do besteiert, wou se ausgefouert ginn. Wann dat op Iech zoutrëfft, klärt et mat Ärer Fiduciaire.</li>
</ul>

<h3>Elektronesch Déngschtleeschtungen (SaaS, Online-Formatiounen asw.)</h3>
<ul>
    <li>Dir applizéiert d'<strong>TVA vum Land vum Client</strong></li>
    <li>Iwwer de Regime <strong>OSS (One-Stop Shop)</strong>: eng eenzeg Deklaratioun fir all EU-Länner</li>
    <li>Seuil: <strong>10 000 €/Joer</strong> u B2C-Verkeef an der EU (Zomm vu Fernverkeef vu Wueren an TBE-Leeschtungen). Dorënner kënnt Dir d'Lëtzebuerger TVA applizéieren.</li>
</ul>

<h2>Fall 3: Client ausserhalb vun der EU (Export)</h2>

<p>Dir fakturéiert engem Client an der Schwäiz, an de Vereenegte Staaten, am Vereenegte Kinnekräich oder an engem beliebegen anere Land ausserhalb vun der EU.</p>

<h3>Déngschtleeschtungen</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA (0 %)</strong> – Déngscht ausserhalb vun der EU lokaliséiert (Art. 17 LIVA)</li>
    <li>Recommandéiert Mentioun: <em>„TVA net applicabel – Déngscht ausserhalb vun der EU lokaliséiert"</em></li>
    <li>Kee Récapitulatif néideg (reservéiert fir innergemeinschaftlechen Handel)</li>
</ul>

<h3>Wueren (Export ausserhalb vun der EU)</h3>
<ul>
    <li>Dir fakturéiert <strong>ouni TVA</strong> (befreiten Export, Art. 43 §1 a) LIVA)</li>
    <li>Dir musst de <strong>Exportbeweis</strong> opbewahren (Zolldokument)</li>
    <li>Mentioun: <em>„TVA-Befreiung – Artikel 146 vun der Direktiv 2006/112/EG"</em></li>
</ul>

<h3>De besonnesche Fall vun Nordirland</h3>

<p>D'Vereenegt Kinnekräich pauschal als „ausserhalb vun der EU" anzestufen, stëmmt fir Déngschtleeschtungen, ass awer <strong>falsch fir Wueren</strong>. No dem Protokoll Irland / Nordirland gëllt d'TVA-Legislatioun vun der Unioun weider fir <strong>Wueren</strong> op an aus Nordirland.</p>

<ul>
    <li><strong>Wueren op Nordirland</strong>: innergemeinschaftlech Liwwerung, kee Export. De Client huet eng TVA-Nummer mam Präfix <strong>„XI"</strong>, déi op VIES ze validéieren ass, an d'Operatioun steet an Ärem Récapitulatif</li>
    <li><strong>Déngschtleeschtungen op Nordirland</strong>: Drëttlandsregime, wéi fir de Rescht vum Vereenegte Kinnekräich</li>
    <li>Eng gëlteg TVA-Nummer mam richtege Präfix ass eng <strong>materiell Bedingung</strong> vun der Befreiung: mat enger „GB"-Nummer léisst sech d'Operatioun net als innergemeinschaftlech Liwwerung behandelen</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">E Feeler, deen an all zwou Richtungen deier gëtt</p>
    <p>Wann Dir e Wuerenverkaf op Nordirland als Export behandelt, sicht Dir no engem Zollbeweis, deen et ni gi wäert, a loosst de Récapitulatif aus. Ëmgedréint deklaréiert Dir bei enger als innergemeinschaftlech behandelter Déngschtleeschtung eng Operatioun, déi do net hikënnt. Entscheedend ass d'<strong>Aart vun der Operatioun</strong>, net d'Land.</p>
</div>

<h2>Besonnesche Fall: d'Schwäiz</h2>

<p>D'Schwäiz ass net an der EU. Vill Lëtzebuerger Freelancer fakturéiere Schwäizer Clienten. D'Reegelen:</p>

<ul>
    <li><strong>B2B-Déngschtleeschtungen</strong>: fakturéiert <strong>ouni TVA</strong>, de Schwäizer Client deklaréiert d'Steier iwwer de Mechanismus vun der <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">Bezuchssteier (ESTV)</a></li>
    <li><strong>B2C-Déngschtleeschtungen</strong>: no der Aart vun der Leeschtung kann d'Lëtzebuerger TVA ufalen (besonnesch elektronesch Leeschtungen)</li>
    <li>Fakturéiert an <strong>EUR oder CHF</strong> no Ofsprooch mam Client</li>
    <li>Kee Récapitulatif (reservéiert fir innergemeinschaftlechen Handel)</li>
</ul>

<h2>Iwwersiichtstabell</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Szenario</th>
            <th class="border border-gray-300 px-4 py-2 text-left">TVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Mentioun</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Lëtzebuerg</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B EU (innergemeinschaftlech)</td><td class="border border-gray-300 px-4 py-2">0 % (Autoliquidatioun)</td><td class="border border-gray-300 px-4 py-2">Art. 196 Direktiv 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (klassesch Déngschter)</td><td class="border border-gray-300 px-4 py-2">17 % LU (standardméisseg)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C EU (TBE / elektronesch Déngschter) – &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">TVA vum Land vum Client</td><td class="border border-gray-300 px-4 py-2">OSS-Regime</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (zu LU net steierbar)</td><td class="border border-gray-300 px-4 py-2">„TVA net applicabel – Déngscht ausserhalb vun der EU"</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Export vu Wueren ausserhalb vun der EU</td><td class="border border-gray-300 px-4 py-2">0 % (befreit)</td><td class="border border-gray-300 px-4 py-2">Art. 146 Direktiv 2006/112/EG</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Wueren op Nordirland</strong></td><td class="border border-gray-300 px-4 py-2">0 % (innergemeinschaftlech Liwwerung)</td><td class="border border-gray-300 px-4 py-2">Art. 138 Direktiv – Clientsnummer mam Präfix „XI"</td></tr>
    </tbody>
</table>

<h2>Multi-Devisen</h2>

<p>Wann Dir an enger auslännescher Devise fakturéiert, muss den TVA-Betrag fir Är Lëtzebuerger Deklaratioun <strong>an Euro ëmgerechent</strong> ginn. Zwee Prinzipie gëllen:</p>

<ul>
    <li>Wielt eng <strong>konstant Ëmrechnungsmethod</strong> an applizéiert se op all Är Operatioune vum Exercice</li>
    <li>Suergt fir <strong>Kohärenz tëscht der Rechnung an der Comptabilitéit</strong>: dee selwechten Taux muss sech an allen zwee erëmfannen</li>
</ul>

<p>Wéi ee Referenztaux ze wielen ass, hänkt vun Ärer Situatioun of: loosst dat eemol vun Ärer Fiduciaire bestätegen, amplaz et Rechnung fir Rechnung ze improviséieren.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Net mam Zolltaux verwiesselen</p>
    <p>D'<a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Zollverwaltung publizéiert hir eege Wiesselkueren</a>, déi zur Bestëmmung vum <strong>Zollwäert</strong> vun de Wuere déngen. Dat ass en anere Taux wéi deen, deen zur Ëmrechnung vun der TVA-Steierbasis benotzt gëtt. Wien Wueren exportéiert, huet mat allen zwee ze dinn.</p>
</div>

<h2>Wat sech mat ViDA ännert (2027-2030)</h2>

<p>De europäesche Paquet <strong>ViDA</strong> („TVA am digitalen Zäitalter") ass ugeholl. Am Géigesaz zu deem, wat een dacks liest, fänkt en net 2030 un: de Kalenner ass <strong>gestaffelt</strong>, an déi éischt Termine kommen däitlech méi fréi.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Termin</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Wat a Kraaft trëtt</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2027</td><td class="border border-gray-300 px-4 py-2">Éischt Erweiderung vum eenzege Guichet OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Juli 2028</td><td class="border border-gray-300 px-4 py-2">Obligatoresch Autoliquidatioun a gewësse Fäll, Reegele fir Plattformen, weider Erweiderung vum OSS op inlännesch B2C-Verkeef</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1. Juli 2030</td><td class="border border-gray-300 px-4 py-2">Obligatoresch elektronesch Fakturatioun an digital Deklaratioun fir innergemeinschaftlech B2B-Operatiounen</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Januar 2035</td><td class="border border-gray-300 px-4 py-2">Harmoniséierung vun de bestoenden nationale Systemer mat der europäescher Norm</td></tr>
    </tbody>
</table>

<p>Wien reegelméisseg Clienten an der EU fakturéiert, dee betreffen als éischt <strong>2027 an 2028</strong>, net 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze préiwen</p>
    <p>D'innergemeinschaftlech an international TVA-Reegele entwéckele sech, an de ViDA-Kalenner kann nach präziséiert ginn. Dës Säit gëtt reegelméisseg aktualiséiert – fir Är Situatioun frot Är Fiduciaire oder d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Offiziell Quellen</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED – Bestëmmung vum Leeschtungsort</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu – Innergemeinschaftlech Transaktiounen</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Direktiv 2006/112/EG – Artikelen 44, 138, 146, 196</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES – Validatioun vun EU-TVA-Nummeren</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Europäesch Kommissioun – Ort vun der Besteierung</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 30. Juli 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Verbonnen Artikelen</h3><ul class="space-y-1"><li><a href="/lb/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Artikel 17 LIVA: Autoliquidatioun B2B intra-EU →</a></li><li><a href="/lb/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">Innergemeinschaftlech TVA – komplette Guide →</a></li><li><a href="/lb/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Obligatoresch Mentiounen op enger Lëtzebuerger Rechnung →</a></li><li><a href="/lb/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Verglach: Fakturatiounssoftware wielen →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fakturéiert an d'Ausland konform</h3>
    <p class="text-primary-800 mb-4">faktur.lu erkennt automatesch d'TVA-Szenario no dem Client (Land, B2B/B2C) an applizéiert déi richteg Mentioun. VIES-Validatioun integréiert.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">14 Deeg gratis testen</a>
</div>
HTML;
    }

    private function pt(): string
    {
        return <<<'HTML'
<p class="lead">Está sediado no Luxemburgo e fatura clientes no estrangeiro? As regras de IVA variam consideravelmente consoante a zona geográfica e o tipo de cliente. Eis um guia claro para cada situação, com as menções e bases legais corretas (atualizado 2026).</p>

<h2>Caso 1: Cliente empresa na UE (B2B intracomunitário)</h2>

<p>É o caso mais frequente para freelancers e PME luxemburguesas. Exemplo: um consultor luxemburguês fatura uma sociedade alemã.</p>

<h3>Regras a aplicar</h3>
<ul>
    <li>Fatura <strong>sem IVA (0 %)</strong> - lugar de tributação no adquirente (<a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">art. 17 LIVA, transposição do art. 44.º da Diretiva 2006/112/CE</a>)</li>
    <li>O cliente declara o IVA no seu país (<strong>autoliquidação / reverse charge</strong>) - o art. 196.º da diretiva designa-o como devedor</li>
    <li>Deve verificar o número de IVA do cliente no <a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES</a></li>
    <li>Menção obrigatória (art. 226.º §11bis da diretiva): <em>«Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE»</em></li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">⚠ Menção artigo 196.º, não 44.º</p>
    <p>Muitos modelos cometem o erro de mencionar «artigo 44.º da Diretiva 2006/112/CE». O artigo 44.º define o <strong>lugar de tributação</strong>. A menção obrigatória a apor remete para o <strong>artigo 196.º</strong> (que designa o adquirente como devedor do IVA). Prefira a menção do artigo 196.º.</p>
</div>

<h3>Documentos necessários</h3>
<ul>
    <li>O seu número de IVA luxemburguês na fatura</li>
    <li>O número de IVA do cliente (verificado no VIES, prova a conservar)</li>
    <li>Declaração no <strong>mapa recapitulativo</strong> — a sua periodicidade é determinada pela AED consoante a sua situação</li>
</ul>

<h2>Caso 2: Cliente particular na UE (B2C)</h2>

<p>Vende a um particular noutro país da UE. As regras dependem do tipo de prestação:</p>

<h3>Serviços clássicos (consultoria, design, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA luxemburguês (17 %)</strong> por defeito</li>
    <li>Não há autoliquidação para particulares</li>
    <li><strong>Atenção:</strong> certos serviços B2C têm regras específicas (imóveis, transporte, eventos culturais/desportivos no local…) e são tributados onde são executados. Se for o seu caso, confirme com o seu contabilista.</li>
</ul>

<h3>Serviços eletrónicos (SaaS, formações em linha, etc.)</h3>
<ul>
    <li>Aplica o <strong>IVA do país do cliente</strong></li>
    <li>Através do regime <strong>OSS (One-Stop Shop)</strong>: uma única declaração para todos os países da UE</li>
    <li>Limiar: <strong>10 000 €/ano</strong> de vendas B2C na UE (soma de vendas à distância de bens e serviços TBE). Abaixo disso, pode aplicar o IVA luxemburguês.</li>
</ul>

<h2>Caso 3: Cliente fora da UE (exportação)</h2>

<p>Fatura um cliente na Suíça, nos Estados Unidos, no Reino Unido ou em qualquer outro país fora da UE.</p>

<h3>Serviços</h3>
<ul>
    <li>Fatura <strong>sem IVA (0 %)</strong> - serviço localizado fora da UE (art. 17 LIVA)</li>
    <li>Menção recomendada: <em>«IVA não aplicável - serviço localizado fora da UE»</em></li>
    <li>Sem mapa recapitulativo (reservado às trocas intra-UE)</li>
</ul>

<h3>Bens (exportação fora da UE)</h3>
<ul>
    <li>Fatura <strong>sem IVA</strong> (exportação isenta, art. 43 §1 a) LIVA)</li>
    <li>Deve conservar a <strong>prova de exportação</strong> (documento aduaneiro)</li>
    <li>Menção: <em>«Isenção de IVA - Artigo 146.º da Diretiva 2006/112/CE»</em></li>
</ul>

<h3>O caso particular da Irlanda do Norte</h3>

<p>Colocar o Reino Unido em bloco na categoria «fora da UE» é correto para os serviços, mas <strong>errado para os bens</strong>. Ao abrigo do protocolo Irlanda / Irlanda do Norte, a legislação de IVA da União continua a aplicar-se aos <strong>bens</strong> com destino e proveniência da Irlanda do Norte.</p>

<ul>
    <li><strong>Bens para a Irlanda do Norte</strong>: entrega intracomunitária, e não exportação. O cliente tem um número de IVA com o prefixo <strong>«XI»</strong>, a validar no VIES, e a operação consta do seu mapa recapitulativo</li>
    <li><strong>Serviços para a Irlanda do Norte</strong>: regime de país terceiro, como para o resto do Reino Unido</li>
    <li>Um número de IVA válido com o prefixo correto é uma <strong>condição substantiva</strong> da isenção: um número «GB» não permite tratar a operação como entrega intracomunitária</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Um erro que custa nos dois sentidos</p>
    <p>Tratar uma venda de bens para a Irlanda do Norte como exportação leva-o a procurar uma prova aduaneira que nunca existirá, e a omitir o mapa recapitulativo. Inversamente, tratar uma prestação de serviços como intracomunitária leva-o a declarar uma operação que ali não pertence. O critério é a <strong>natureza da operação</strong>, não o país.</p>
</div>

<h2>Caso especial: a Suíça</h2>

<p>A Suíça não pertence à UE. Muitos freelancers luxemburgueses faturam clientes suíços. As regras:</p>

<ul>
    <li><strong>Serviços B2B</strong>: fature <strong>sem IVA</strong>, o cliente suíço declara o imposto através do mecanismo do <a href="https://www.estv.admin.ch/" target="_blank" rel="noopener">imposto sobre as aquisições (ESTV)</a></li>
    <li><strong>Serviços B2C</strong>: consoante o tipo de serviço, o IVA luxemburguês pode aplicar-se (nomeadamente serviços eletrónicos)</li>
    <li>Fature em <strong>EUR ou CHF</strong> conforme o acordado com o cliente</li>
    <li>Sem mapa recapitulativo (reservado às trocas intra-UE)</li>
</ul>

<h2>Tabela recapitulativa</h2>

<table class="w-full border-collapse border border-gray-300 mt-4">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Cenário</th>
            <th class="border border-gray-300 px-4 py-2 text-left">IVA</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Menção</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B Luxemburgo</td><td class="border border-gray-300 px-4 py-2">17 %</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B UE (intra-UE)</td><td class="border border-gray-300 px-4 py-2">0 % (autoliquidação)</td><td class="border border-gray-300 px-4 py-2">Art. 196.º Diretiva 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (serviços clássicos)</td><td class="border border-gray-300 px-4 py-2">17 % LU (por defeito)</td><td class="border border-gray-300 px-4 py-2">Standard</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C UE (TBE / serviços eletrónicos) - &gt; 10 k€</td><td class="border border-gray-300 px-4 py-2">IVA do país do cliente</td><td class="border border-gray-300 px-4 py-2">Regime OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (não tributável no LU)</td><td class="border border-gray-300 px-4 py-2">«IVA não aplicável - serviço localizado fora da UE»</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Exportação de bens fora da UE</td><td class="border border-gray-300 px-4 py-2">0 % (isento)</td><td class="border border-gray-300 px-4 py-2">Art. 146.º Diretiva 2006/112/CE</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>Bens para a Irlanda do Norte</strong></td><td class="border border-gray-300 px-4 py-2">0 % (entrega intracomunitária)</td><td class="border border-gray-300 px-4 py-2">Art. 138.º Diretiva - número do cliente com prefixo «XI»</td></tr>
    </tbody>
</table>

<h2>Multimoeda</h2>

<p>Se faturar em moeda estrangeira, o montante do IVA deve ser <strong>convertido em euros</strong> para a sua declaração luxemburguesa. Dois princípios valem:</p>

<ul>
    <li>Adote um <strong>método de conversão constante</strong> e aplique-o a todas as operações do exercício</li>
    <li>Garanta a <strong>coerência entre a fatura e a contabilidade</strong>: é a mesma taxa que deve constar de ambas</li>
</ul>

<p>A escolha da taxa de referência depende da sua situação: peça ao seu contabilista que a valide de uma vez por todas, em vez de a improvisar fatura a fatura.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Não confunda com a taxa aduaneira</p>
    <p>A <a href="https://douanes.public.lu/fr/commerce-international/taux-change.html" target="_blank" rel="noopener">Administração das alfândegas publica as suas próprias taxas de câmbio</a>, que servem para estabelecer o <strong>valor aduaneiro</strong> das mercadorias. É uma taxa distinta da utilizada para converter a base tributável do IVA. Se exportar bens, lidará com ambas.</p>
</div>

<h2>O que muda com o ViDA (2027-2030)</h2>

<p>O pacote europeu <strong>ViDA</strong> («IVA na era digital») foi adotado. Ao contrário do que se lê frequentemente, não começa em 2030: o calendário é <strong>faseado</strong> e os primeiros prazos chegam bem antes.</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Prazo</th>
            <th class="border border-gray-300 px-4 py-2 text-left">O que entra em vigor</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">Janeiro de 2027</td><td class="border border-gray-300 px-4 py-2">Primeira extensão do balcão único OSS</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Julho de 2028</td><td class="border border-gray-300 px-4 py-2">Autoliquidação obrigatória em certos casos, regras aplicáveis às plataformas, nova extensão do OSS às vendas B2C domésticas</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">1 de julho de 2030</td><td class="border border-gray-300 px-4 py-2">Faturação eletrónica e declaração digital obrigatórias para as operações intra-UE B2B</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Janeiro de 2035</td><td class="border border-gray-300 px-4 py-2">Harmonização dos sistemas nacionais existentes com a norma europeia</td></tr>
    </tbody>
</table>

<p>Se fatura regularmente clientes na UE, são <strong>2027 e 2028</strong> que o afetam primeiro, não 2030.</p>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>As regras de IVA intra-UE e internacionais evoluem, e o calendário ViDA pode ainda ser precisado. Esta página é atualizada regularmente, mas para a sua situação, consulte o seu contabilista ou a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED</a>.</p>
</div>

<h2>Fontes oficiais</h2>

<ul>
    <li><a href="https://pfi.public.lu/fr/professionnel/tva/taxe-valeur-ajoutee/prestation-service/determination-lieu-prestation.html" target="_blank" rel="noopener">AED - Determinação do lugar da prestação</a></li>
    <li><a href="https://logistics.public.lu/fr/formalities-procedures/taxes/value-added-tax/intracommunity-transactions.html" target="_blank" rel="noopener">Logistics.lu - Transações intracomunitárias</a></li>
    <li><a href="https://eur-lex.europa.eu/legal-content/PT/TXT/?uri=CELEX:32006L0112" target="_blank" rel="noopener">Diretiva 2006/112/CE - artigos 44.º, 138.º, 146.º, 196.º</a></li>
    <li><a href="https://ec.europa.eu/taxation_customs/vies/" target="_blank" rel="noopener">VIES - Validação de IVA intra-UE</a></li>
    <li><a href="https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en" target="_blank" rel="noopener">Comissão Europeia - Lugar de tributação</a></li>
</ul>

<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 30 de julho de 2026.</em></p>

<div class="mt-8 p-4 bg-slate-50 rounded-xl"><h3 class="text-base font-semibold text-slate-900 mb-3">Artigos relacionados</h3><ul class="space-y-1"><li><a href="/pt/blog/article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Artigo 17 LIVA: autoliquidação B2B intra-UE →</a></li><li><a href="/pt/blog/tva-intracommunautaire-guide-entreprises-luxembourgeoises" class="text-primary-500 hover:text-primary-600 text-sm">IVA intracomunitário - guia completo →</a></li><li><a href="/pt/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Menções obrigatórias numa fatura luxemburguesa →</a></li><li><a href="/pt/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparativo: escolher software de faturação →</a></li></ul></div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fature para o estrangeiro em plena conformidade</h3>
    <p class="text-primary-800 mb-4">O faktur.lu deteta automaticamente o cenário de IVA consoante o cliente (país, B2B/B2C) e aplica a menção correta. Validação VIES integrada.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Experimentar 14 dias grátis</a>
</div>
HTML;
    }
};
