<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 2e passe (suite) — retire la « rénovation » de la liste détaillée du taux 8 %
 * de l'article "tva-luxembourg-taux-calcul-obligations" en DE/EN/LB/PT (la
 * rénovation de logement est au taux super-réduit 3 %, pas 8 %). Le FR a déjà
 * été traité (déplacé en 3 %) par la migration précédente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");
        $OBL = 'tva-luxembourg-taux-calcul-obligations';
        $removals = [
            'de' => "\n    <li>Bestimmte Renovierungsarbeiten an Wohnungen</li>",
            'en' => "\n    <li>Certain housing renovation work</li>",
            'lb' => "\n    <li>Verschidden Renovatiounsaarbechten un Wunnengen</li>",
            'pt' => "\n    <li>Certas obras de renovação de habitações</li>",
        ];
        foreach ($removals as $loc => $old) {
            $post = DB::table('blog_posts')->where('translation_key', $OBL)->where('locale', $loc)->first();
            if (!$post || !str_contains($post->content, $old)) { $out("  [$loc] ⚠️ NON TROUVÉ"); continue; }
            DB::table('blog_posts')->where('id', $post->id)->update(['content' => str_replace($old, '', $post->content)]);
            $out("  [$loc] ✅ rénovation retirée du 8 %");
        }
    }

    public function down(): void {}
};
