<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2e passe — résiduels de taux TVA dans l'article "tva-luxembourg-taux-calcul-obligations".
 * Ce 2e article des taux listait encore, sous 8 %, le mazout (FR) et la rénovation
 * de logement (FR + cellule résumé DE/EN/FR/PT).
 *  - Mazout de chauffage : 8 % -> 14 % (FR).
 *  - Rénovation de logement : 8 % -> 3 % (FR liste + retrait des cellules résumé 8 % DE/EN/FR/PT).
 * LB : déjà propre. Mazout déjà à 14 % en DE/EN/LB/PT (vérifié).
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");
        foreach ($this->fixes() as $i => $f) {
            $post = DB::table('blog_posts')->where('translation_key', $f['tk'])->where('locale', $f['loc'])->first();
            if (!$post) { $out("  [#$i] {$f['loc']} : INTROUVABLE"); continue; }
            if (!str_contains($post->content, $f['old'])) { $out("  [#$i] {$f['loc']} : ⚠️ NON TROUVÉ"); continue; }
            DB::table('blog_posts')->where('id', $post->id)->update(['content' => str_replace($f['old'], $f['new'], $post->content)]);
            $out("  [#$i] {$f['loc']} : ✅ OK");
        }
    }

    public function down(): void {}

    private function fixes(): array
    {
        $OBL = 'tva-luxembourg-taux-calcul-obligations';
        return [
            // FR — liste 8 % : retire mazout + rénovation
            ['tk'=>$OBL,'loc'=>'fr',
             'old'=>"<ul>\n    <li>Fourniture de gaz naturel et d'électricité</li>\n    <li><strong>Mazout de chauffage (fioul domestique)</strong></li>\n    <li>Services de coiffure</li>\n    <li>Certains travaux de rénovation de logements</li>\n    <li>Nettoyage de vitres</li>\n    <li>Petits services de réparation (vélos, chaussures, vêtements)</li>\n</ul>",
             'new'=>"<ul>\n    <li>Fourniture de gaz naturel et d'électricité</li>\n    <li>Services de coiffure</li>\n    <li>Nettoyage de vitres</li>\n    <li>Petits services de réparation (vélos, chaussures, vêtements)</li>\n</ul>"],
            // FR — liste 14 % : ajoute mazout
            ['tk'=>$OBL,'loc'=>'fr',
             'old'=>"<ul>\n    <li>Vins (moins de 13% d'alcool)</li>\n    <li>Combustibles minéraux solides (charbon, coke)</li>\n    <li>Certains imprimés publicitaires</li>\n</ul>",
             'new'=>"<ul>\n    <li>Vins (moins de 13% d'alcool)</li>\n    <li>Combustibles minéraux solides (charbon, coke)</li>\n    <li>Mazout de chauffage (fioul domestique)</li>\n    <li>Certains imprimés publicitaires</li>\n</ul>"],
            // FR — liste 3 % : ajoute rénovation
            ['tk'=>$OBL,'loc'=>'fr',
             'old'=>"<li>Soins médicaux et dentaires (non exonérés)</li>\n</ul>",
             'new'=>"<li>Soins médicaux et dentaires (non exonérés)</li>\n    <li>Rénovation de logements affectés à l'habitation principale (sous conditions)</li>\n</ul>"],
            // FR — cellule résumé 8 % : retire rénovation
            ['tk'=>$OBL,'loc'=>'fr',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Gaz, électricité, coiffure, travaux de rénovation</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Gaz, électricité, coiffure</td>'],
            // DE — cellule résumé 8 %
            ['tk'=>$OBL,'loc'=>'de',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Gas, Strom, Friseur, Renovierungsarbeiten</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Gas, Strom, Friseur</td>'],
            // EN — cellule résumé 8 %
            ['tk'=>$OBL,'loc'=>'en',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Gas, electricity, hairdressing, renovation work</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Gas, electricity, hairdressing</td>'],
            // PT — cellule résumé 8 %
            ['tk'=>$OBL,'loc'=>'pt',
             'old'=>'<td class="border border-gray-300 px-4 py-2">Gás, eletricidade, cabeleireiro, obras de renovação</td>',
             'new'=>'<td class="border border-gray-300 px-4 py-2">Gás, eletricidade, cabeleireiro</td>'],
        ];
    }
};
