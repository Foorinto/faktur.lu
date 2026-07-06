<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO / maillage interne (blog only) — ajoute un lien vers l'article comparatif
 * "choisir-logiciel-facturation-luxembourg-comparatif" (striking distance, pos ~11)
 * depuis 4 articles FR qui prennent des impressions en GSC et ne le liaient pas
 * encore (dans leur section "Articles connexes").
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");
        $li = '<li><a href="/fr/blog/choisir-logiciel-facturation-luxembourg-comparatif" class="text-primary-500 hover:text-primary-600 text-sm">Comparatif : choisir son logiciel de facturation →</a></li>';

        // [tk => dernier <li> de la liste "Articles connexes" suivi de </ul>]
        $anchors = [
            'facturer-etranger-depuis-luxembourg' =>
                '<li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">Mentions obligatoires facture LU →</a></li></ul>',
            'tva-intracommunautaire-guide-entreprises-luxembourgeoises' =>
                '<li><a href="/fr/blog/facturer-etranger-depuis-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">facturer à l\'étranger →</a></li></ul>',
            'note-de-credit-luxembourg-comment-etablir' =>
                '<li><a href="/fr/blog/guide-complet-facturation-luxembourg-2026" class="text-primary-500 hover:text-primary-600 text-sm">guide de facturation →</a></li></ul>',
            'guide-complet-facturation-luxembourg-2026' =>
                '<li><a href="/fr/blog/mentions-obligatoires-facture-luxembourg" class="text-primary-500 hover:text-primary-600 text-sm">mentions obligatoires →</a></li></ul>',
        ];

        foreach ($anchors as $tk => $old) {
            $post = DB::table('blog_posts')->where('translation_key', $tk)->where('locale', 'fr')->first();
            if (!$post || !str_contains($post->content, $old)) { $out("  [$tk] ⚠️ ancre NON TROUVÉE"); continue; }
            if (str_contains($post->content, 'choisir-logiciel-facturation-luxembourg-comparatif')) { $out("  [$tk] ⏭ déjà lié"); continue; }
            $new = str_replace($old, str_replace('</ul>', $li . '</ul>', $old), $post->content);
            DB::table('blog_posts')->where('id', $post->id)->update(['content' => $new]);
            $out("  [$tk] ✅ lien ajouté");
        }
    }

    public function down(): void {}
};
