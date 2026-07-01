<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrections factuelles "confiance haute" du contenu blog (5 langues),
 * doublement vérifiées (moi + 2e avis IA + sources officielles) :
 *  - Art. « 4 taux TVA » : bois de chauffage 14->8 %, spectacles 8->3 %,
 *    services d'auteurs 8->3 % (FR), mazout 8->14 % (FR), rénovation logement
 *    8 %/2 ans -> 3 %/immeuble >20 ans/plafond 50 000 €.
 *  - Art. « TVA taux/obligations » : déclaration annuelle 1er mai -> 1er mars (FR).
 *  - France (créer entreprise) : seuils micro 188 700/77 700 -> 203 100/83 600.
 *  - Belgique : guichet Zenito -> Eunomia ; conservation 7 -> 10 ans.
 *
 * NON inclus (contestés -> fiduciaire) : mention franchise 57bis, prescription
 * art. 81, base légale FAIA, IHK Allemagne.
 *
 * Chaque paire est appliquée par str_replace ciblé ; la migration LOG le nombre
 * de remplacements effectués (0 = chaîne non trouvée, à investiguer).
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");

        foreach ($this->fixes() as $i => $f) {
            [$tk, $loc, $old, $new] = [$f['tk'], $f['loc'], $f['old'], $f['new']];
            $post = DB::table('blog_posts')->where('translation_key', $tk)->where('locale', $loc)->first();
            if (!$post) {
                $out("  [#$i] $loc/$tk : ARTICLE INTROUVABLE");
                continue;
            }
            if (!str_contains($post->content, $old)) {
                $out("  [#$i] $loc/$tk : ⚠️ CHAÎNE NON TROUVÉE");
                continue;
            }
            $content = str_replace($old, $new, $post->content);
            DB::table('blog_posts')->where('id', $post->id)->update(['content' => $content]);
            $out("  [#$i] $loc/$tk : ✅ OK");
        }
    }

    public function down(): void
    {
        // Correction de contenu rédactionnel : pas de rollback automatique.
    }

    private function fixes(): array
    {
        $TAUX = 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques';
        $OBL  = 'tva-luxembourg-taux-calcul-obligations';
        $FR7  = 'creer-entreprise-individuelle-france-guide-2025';
        $BE8  = 'creer-entreprise-individuelle-belgique-guide-2025';

        return [
            // ═══════════════ FR — article des 4 taux ═══════════════
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Gaz, électricité, mazout de chauffage, coiffure, certains travaux</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Gaz, électricité, coiffure, certains travaux</td>'],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Vins, combustibles minéraux solides, imprimés publicitaires</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Vins, combustibles minéraux solides, mazout de chauffage, imprimés publicitaires</td>'],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>"<li><strong>Bois de chauffage</strong></li>\n    <li><strong>Imprimés publicitaires</strong> (catalogues, dépliants)</li>",
             'new'=>"<li><strong>Mazout de chauffage</strong> (fioul domestique)</li>\n    <li><strong>Imprimés publicitaires</strong> (catalogues, dépliants)</li>"],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>'<strong>Attention :</strong> le mazout (fioul) destiné au chauffage résidentiel relève du taux 8 %, pas du 14 %.',
             'new'=>'<strong>Attention :</strong> le mazout (fioul) destiné au chauffage résidentiel relève du taux 14 %, pas du 8 %.'],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>"<ul>\n    <li><strong>Fourniture de gaz et d'électricité</strong></li>\n    <li><strong>Mazout de chauffage</strong> (fioul domestique)</li>\n    <li><strong>Coiffure</strong></li>\n    <li><strong>Travaux de rénovation</strong> de logements de plus de 2 ans (sous conditions, voir ci-dessous)</li>\n    <li><strong>Spectacles vivants</strong> (théâtre, concerts, cinéma)</li>\n    <li><strong>Cordonnerie, réparation de bicyclettes, modification de vêtements</strong></li>\n    <li><strong>Services d'auteurs et compositeurs</strong></li>\n</ul>",
             'new'=>"<ul>\n    <li><strong>Fourniture de gaz et d'électricité</strong></li>\n    <li><strong>Bois de chauffage</strong></li>\n    <li><strong>Coiffure</strong></li>\n    <li><strong>Cordonnerie, réparation de bicyclettes, modification de vêtements</strong></li>\n</ul>"],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>"    <li><strong>Eau distribuée par les réseaux publics</strong></li>\n    <li><strong>Œuvres d'art</strong> (sous certaines conditions)</li>",
             'new'=>"    <li><strong>Eau distribuée par les réseaux publics</strong></li>\n    <li><strong>Spectacles vivants et billets d'entrée</strong> (théâtre, concerts, cinéma)</li>\n    <li><strong>Services d'auteurs et compositeurs</strong></li>\n    <li><strong>Travaux de rénovation</strong> de logements affectés à l'habitation principale (sous conditions, voir ci-dessous)</li>\n    <li><strong>Œuvres d'art</strong> (sous certaines conditions)</li>"],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>'<h3>Travaux de rénovation : 8 % ou 17 %</h3>',
             'new'=>'<h3>Travaux de rénovation : 3 % ou 17 %</h3>'],
            ['tk'=>$TAUX,'loc'=>'fr',
             'old'=>"<p>Les travaux de rénovation d'un logement <strong>de plus de 2 ans</strong> bénéficient du taux réduit de <strong>8 %</strong>, sous condition que les matériaux et la main-d'œuvre soient facturés ensemble par l'entreprise du bâtiment. Pour un logement neuf ou des travaux ne respectant pas ces conditions : 17 %.</p>",
             'new'=>"<p>Les travaux de rénovation d'un logement affecté à l'<strong>habitation principale</strong>, dans un immeuble <strong>achevé depuis plus de 20 ans</strong>, bénéficient du taux super-réduit de <strong>3 %</strong>, dans la limite d'un <strong>plafond de faveur fiscale de 50 000 € par logement</strong>. Au-delà du plafond, pour un logement neuf, un bien non affecté à l'habitation principale ou des travaux ne respectant pas ces conditions : 17 %.</p>"],

            // ═══════════════ FR — art. TVA obligations (échéance) ═══════════════
            ['tk'=>$OBL,'loc'=>'fr',
             'old'=>"La <strong>déclaration annuelle</strong> est due avant le <strong>1<sup>er</sup> mai</strong> de l'année suivante.",
             'new'=>"La <strong>déclaration annuelle</strong> est due avant le <strong>1<sup>er</sup> mars</strong> de l'année suivante."],

            // ═══════════════ DE — article des 4 taux ═══════════════
            ['tk'=>$TAUX,'loc'=>'de',
             'old'=>'<li><strong>Bestimmte feste Brennstoffe</strong> (Kohle, Brennholz)</li>',
             'new'=>'<li><strong>Bestimmte feste Brennstoffe</strong> (Kohle)</li>'],
            ['tk'=>$TAUX,'loc'=>'de',
             'old'=>"    <li><strong>Hotelunterbringung</strong> (1 bis 3 Sterne)</li>\n    <li><strong>Renovierungsarbeiten</strong> in alten Wohnungen (unter Bedingungen)</li>\n    <li><strong>Live-Vorstellungen</strong> (Theater, Konzerte)</li>\n    <li><strong>Schuster, Fahrradreparatur, Kleidungsaenderung</strong></li>",
             'new'=>"    <li><strong>Hotelunterbringung</strong> (1 bis 3 Sterne)</li>\n    <li><strong>Brennholz</strong></li>\n    <li><strong>Schuster, Fahrradreparatur, Kleidungsaenderung</strong></li>"],
            ['tk'=>$TAUX,'loc'=>'de',
             'old'=>"    <li><strong>Wasser aus oeffentlichen Netzen</strong></li>\n    <li><strong>Kunstwerke</strong> (unter bestimmten Bedingungen)</li>",
             'new'=>"    <li><strong>Wasser aus oeffentlichen Netzen</strong></li>\n    <li><strong>Eintritte zu Veranstaltungen, Konzerten, Theater und Kino</strong></li>\n    <li><strong>Renovierung von Wohnraum</strong> (Hauptwohnsitz, unter Bedingungen)</li>\n    <li><strong>Kunstwerke</strong> (unter bestimmten Bedingungen)</li>"],
            ['tk'=>$TAUX,'loc'=>'de',
             'old'=>"<h3>Renovierungsarbeiten: 8% oder 17%?</h3>\n\n<p>Renovierungen in Wohnungen <strong>aelter als 2 Jahre</strong> erhalten den ermaessigten Satz <strong>8%</strong>, wenn Material und Arbeit gemeinsam vom Bauunternehmen fakturiert werden. Neubau: 17%.</p>",
             'new'=>"<h3>Renovierungsarbeiten: 3% oder 17%?</h3>\n\n<p>Renovierungen am <strong>Hauptwohnsitz</strong> in einem <strong>ueber 20 Jahre alten Gebaeude</strong> erhalten den super-ermaessigten Satz <strong>3%</strong>, bis zu einem <strong>Hoechstbetrag von 50.000 EUR pro Wohnung</strong>, wenn Material und Arbeit gemeinsam vom Bauunternehmen fakturiert werden. Neubau: 17%.</p>"],

            // ═══════════════ EN — article des 4 taux ═══════════════
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>'<li><strong>Certain solid fuels</strong> (coal, firewood)</li>',
             'new'=>'<li><strong>Certain solid fuels</strong> (coal)</li>'],
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>"<li><strong>Gas and electricity</strong> supply</li>",
             'new'=>"<li><strong>Gas and electricity</strong> supply</li>\n    <li><strong>Firewood</strong></li>"],
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>"    <li><strong>Live performances</strong> (theatre, concerts)</li>\n    <li><strong>Shoe repair, bicycle repair, clothing alteration</strong></li>",
             'new'=>"    <li><strong>Shoe repair, bicycle repair, clothing alteration</strong></li>"],
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>"    <li><strong>Water from public networks</strong></li>",
             'new'=>"    <li><strong>Water from public networks</strong></li>\n    <li><strong>Admission to live shows</strong> (theatre, concerts, cinema)</li>"],
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>"    <li><strong>Hotel accommodation</strong> (1 to 3 stars)</li>\n    <li><strong>Renovation works</strong> on old housing (under conditions)</li>",
             'new'=>"    <li><strong>Hotel accommodation</strong> (1 to 3 stars)</li>"],
            ['tk'=>$TAUX,'loc'=>'en',
             'old'=>"<h3>Renovation works: 8% or 17%?</h3>\n\n<p>Renovation works on housing <strong>over 2 years old</strong> benefit from the reduced rate of <strong>8%</strong>, provided materials and labour are invoiced together by the construction company. For new housing: 17%.</p>",
             'new'=>"<h3>Renovation works: 3% or 17%?</h3>\n\n<p>Renovation and improvement works on a dwelling used as a <strong>primary residence</strong> and <strong>completed more than 20 years ago</strong> benefit from the super-reduced rate of <strong>3%</strong>. The tax advantage is capped at <strong>€50,000 per dwelling</strong>. For new housing or works that do not meet these conditions: 17%.</p>"],

            // ═══════════════ LB — article des 4 taux ═══════════════
            ['tk'=>$TAUX,'loc'=>'lb',
             'old'=>'<li><strong>Bestëmmt fest Brennstoffer</strong> (Kuel, Brennholz)</li>',
             'new'=>'<li><strong>Bestëmmt fest Brennstoffer</strong> (Kuel)</li>'],
            ['tk'=>$TAUX,'loc'=>'lb',
             'old'=>"    <li><strong>Hotellerie</strong> (1 bis 3 Stären)</li>\n    <li><strong>Renovéierungsaarbechten</strong> vun ale Wunnengen (ënner Konditiounen)</li>\n    <li><strong>Live-Spektakelen</strong> (Theater, Concerten)</li>\n    <li><strong>Schousterei, Vëlosreparatur, Klederännerung</strong></li>",
             'new'=>"    <li><strong>Hotellerie</strong> (1 bis 3 Stären)</li>\n    <li><strong>Bréngholz</strong> (Brennholz)</li>\n    <li><strong>Schousterei, Vëlosreparatur, Klederännerung</strong></li>"],
            ['tk'=>$TAUX,'loc'=>'lb',
             'old'=>"    <li><strong>Waasser aus ëffentleche Netzer</strong></li>\n    <li><strong>Konstwierker</strong> (ënner bestëmmten Konditiounen)</li>",
             'new'=>"    <li><strong>Entrée fir Spektakelen</strong> (Theater, Concerten, Kino)</li>\n    <li><strong>Renovéierung vum Haaptwunnsëtz</strong> (Gebai méi al wéi 20 Joer, ënner Konditiounen)</li>\n    <li><strong>Waasser aus ëffentleche Netzer</strong></li>\n    <li><strong>Konstwierker</strong> (ënner bestëmmten Konditiounen)</li>"],
            ['tk'=>$TAUX,'loc'=>'lb',
             'old'=>'<h3>Renovéierungsaarbechten: 8% oder 17%?</h3>',
             'new'=>'<h3>Renovéierungsaarbechten: 3% oder 17%?</h3>'],
            ['tk'=>$TAUX,'loc'=>'lb',
             'old'=>"<p>Renovéierungen vu Wunnengen <strong>méi al wéi 2 Joer</strong> profitéieren vum reduzéierte Saaz <strong>8%</strong>, ënner Konditioun datt Material a Mainpower zesummen vum Bauunternehmen fakturéiert ginn. Fir Neibau: 17%.</p>",
             'new'=>"<p>Renovéierungen um <strong>Haaptwunnsëtz</strong> an engem <strong>Gebai méi al wéi 20 Joer</strong> profitéieren vum super-reduzéierte Saaz <strong>3%</strong>, mat engem <strong>Plafong vu 50.000 € pro Wunneng</strong>, ënner Konditioun datt Material a Mainpower zesummen vum Bauunternehmen fakturéiert ginn.</p>"],

            // ═══════════════ PT — article des 4 taux ═══════════════
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>'<li><strong>Certos combustíveis sólidos</strong> (carvão, lenha)</li>',
             'new'=>'<li><strong>Certos combustíveis sólidos</strong> (carvão)</li>'],
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>'<li><strong>Fornecimento de gás e eletricidade</strong></li>',
             'new'=>"<li><strong>Fornecimento de gás e eletricidade</strong></li>\n    <li><strong>Lenha</strong> (madeira para aquecimento)</li>"],
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>"<li><strong>Espetáculos ao vivo</strong> (teatro, concertos)</li>\n    <li><strong>Sapateiro",
             'new'=>"<li><strong>Sapateiro"],
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>'<li><strong>Água distribuída por redes públicas</strong></li>',
             'new'=>"<li><strong>Água distribuída por redes públicas</strong></li>\n    <li><strong>Bilhetes de espetáculos ao vivo</strong> (teatro, concertos, cinema)</li>"],
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>'<h3>Obras de renovação: 8% ou 17%?</h3>',
             'new'=>'<h3>Obras de renovação: 3% ou 17%?</h3>'],
            ['tk'=>$TAUX,'loc'=>'pt',
             'old'=>'<p>As obras de renovação de habitação com <strong>mais de 2 anos</strong> beneficiam da taxa reduzida de <strong>8%</strong>, sob condição de materiais e mão-de-obra serem faturados em conjunto pela empresa de construção. Para habitação nova: 17%.</p>',
             'new'=>'<p>As obras de renovação de uma <strong>habitação principal com mais de 20 anos</strong> beneficiam da taxa super-reduzida de <strong>3%</strong>, dentro de um limite de <strong>50 000 € por habitação</strong> e sob condição de os materiais e a mão-de-obra serem faturados em conjunto pela empresa de construção. Para habitação nova: 17%.</p>'],

            // ═══════════════ France — seuils micro (toutes langues) ═══════════════
            ['tk'=>$FR7,'loc'=>'fr','old'=>'<td class="p-2 border-b">188 700 €</td>','new'=>'<td class="p-2 border-b">203 100 €</td>'],
            ['tk'=>$FR7,'loc'=>'fr','old'=>'<td class="p-2 border-b">77 700 €</td>','new'=>'<td class="p-2 border-b">83 600 €</td>'],
            ['tk'=>$FR7,'loc'=>'de','old'=>'188.700 €','new'=>'203.100 €'],
            ['tk'=>$FR7,'loc'=>'de','old'=>'77.700 €','new'=>'83.600 €'],
            ['tk'=>$FR7,'loc'=>'en','old'=>'€188,700','new'=>'€203,100'],
            ['tk'=>$FR7,'loc'=>'en','old'=>'€77,700','new'=>'€83,600'],
            ['tk'=>$FR7,'loc'=>'lb','old'=>'188.700 €','new'=>'203.100 €'],
            ['tk'=>$FR7,'loc'=>'lb','old'=>'77.700 €','new'=>'83.600 €'],
            ['tk'=>$FR7,'loc'=>'pt','old'=>'188 700 €','new'=>'203 100 €'],
            ['tk'=>$FR7,'loc'=>'pt','old'=>'77 700 €','new'=>'83 600 €'],

            // ═══════════════ Belgique — Zenito -> Eunomia + conservation 7->10 (toutes langues) ═══════════════
            ['tk'=>$BE8,'loc'=>'fr','old'=>'<li>Zenito</li>','new'=>'<li>Eunomia</li>'],
            ['tk'=>$BE8,'loc'=>'de','old'=>'<li>Zenito</li>','new'=>'<li>Eunomia</li>'],
            ['tk'=>$BE8,'loc'=>'en','old'=>'<li>Zenito</li>','new'=>'<li>Eunomia</li>'],
            ['tk'=>$BE8,'loc'=>'lb','old'=>'<li>Zenito</li>','new'=>'<li>Eunomia</li>'],
            ['tk'=>$BE8,'loc'=>'pt','old'=>'<li>Zenito</li>','new'=>'<li>Eunomia</li>'],
            ['tk'=>$BE8,'loc'=>'fr','old'=>'<p><strong>Conservation des documents :</strong> 7 ans</p>','new'=>'<p><strong>Conservation des documents :</strong> 10 ans</p>'],
            ['tk'=>$BE8,'loc'=>'de','old'=>'<p><strong>Aufbewahrung der Dokumente:</strong> 7 Jahre</p>','new'=>'<p><strong>Aufbewahrung der Dokumente:</strong> 10 Jahre</p>'],
            ['tk'=>$BE8,'loc'=>'en','old'=>'<strong>Document retention:</strong> 7 years','new'=>'<strong>Document retention:</strong> 10 years'],
            ['tk'=>$BE8,'loc'=>'lb','old'=>'<p><strong>Opbewahrung vun den Dokumenter:</strong> 7 Joer</p>','new'=>'<p><strong>Opbewahrung vun den Dokumenter:</strong> 10 Joer</p>'],
            ['tk'=>$BE8,'loc'=>'pt','old'=>'<p><strong>Conservação dos documentos:</strong> 7 anos</p>','new'=>'<p><strong>Conservação dos documentos:</strong> 10 anos</p>'],

            // Belgique — dates INASTI (FR uniquement, les autres langues ne les listaient pas)
            ['tk'=>$BE8,'loc'=>'fr',
             'old'=>'<li>Paiement <strong>trimestriel</strong> (15 mars / 15 juin / 15 septembre / 15 décembre)</li>',
             'new'=>'<li>Paiement <strong>trimestriel</strong> (31 mars / 30 juin / 30 septembre / 31 décembre)</li>'],
        ];
    }
};
