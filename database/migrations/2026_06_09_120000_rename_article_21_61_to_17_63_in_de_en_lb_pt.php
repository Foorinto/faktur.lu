<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Propagation des renames de slugs LIVA 21→17 et 61→63 aux 4 autres langues.
 *
 * Pour rappel : ces 2 articles citaient des références d'articles LIVA fausses.
 * L'autoliquidation B2B intra-UE est régie par l'art. 17 LIVA (et non 21), et
 * la numérotation séquentielle par l'art. 63 LIVA (et non 61).
 *
 * Les slugs FR ont été renommés en batch 1 (déjà déployé). Cette migration
 * applique les renames aux 4 autres langues + met à jour titres et contenus.
 *
 * Les redirects 301 pour les anciens slugs sont ajoutés dans routes/web.php.
 *
 * Idempotent : ne renomme que si le nouveau slug n'existe pas déjà.
 */
return new class extends Migration
{
    private const RENAMES = [
        // === Article 21 LIVA → Article 17 LIVA (autoliquidation) ===
        'de' => [
            [
                'old_slug' => 'artikel-21-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg',
                'new_slug' => 'artikel-17-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg',
                'new_title' => 'Artikel 17 LIVA: Reverse-Charge bei innergemeinschaftlichem B2B fuer Luxemburger Freiberufler',
                'content_replacements' => [
                    'Artikel 21 LIVA' => 'Artikel 17 LIVA',
                    'Artikel 21 des Luxemburger Mehrwertsteuergesetzes' => 'Artikel 17 des Luxemburger Mehrwertsteuergesetzes',
                    'Art. 21 LIVA' => 'Art. 17 LIVA',
                ],
            ],
            [
                'old_slug' => 'artikel-61-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht',
                'new_slug' => 'artikel-63-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht',
                'new_title' => 'Artikel 63 LIVA: die sequenzielle Rechnungsnummerierung in Luxemburg (Punkt 3°)',
                'content_replacements' => [
                    'Artikel 61 LIVA' => 'Artikel 63 LIVA',
                    'Artikel 61 des Luxemburger Mehrwertsteuergesetzes' => 'Artikel 63 des Luxemburger Mehrwertsteuergesetzes',
                    'Art. 61 LIVA' => 'Art. 63 LIVA',
                ],
            ],
        ],
        'en' => [
            [
                'old_slug' => 'article-21-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers',
                'new_slug' => 'article-17-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers',
                'new_title' => 'Article 17 LIVA: intra-EU B2B VAT reverse charge for Luxembourg freelancers',
                'content_replacements' => [
                    'Article 21 LIVA' => 'Article 17 LIVA',
                    'Article 21 of the Luxembourg VAT law' => 'Article 17 of the Luxembourg VAT law',
                    'Art. 21 LIVA' => 'Art. 17 LIVA',
                ],
            ],
            [
                'old_slug' => 'article-61-liva-sequential-invoice-numbering-luxembourg-mandatory',
                'new_slug' => 'article-63-liva-sequential-invoice-numbering-luxembourg-mandatory',
                'new_title' => 'Article 63 LIVA: sequential invoice numbering in Luxembourg (point 3°)',
                'content_replacements' => [
                    'Article 61 LIVA' => 'Article 63 LIVA',
                    'Article 61 of the Luxembourg VAT law' => 'Article 63 of the Luxembourg VAT law',
                    'Art. 61 LIVA' => 'Art. 63 LIVA',
                ],
            ],
        ],
        'lb' => [
            [
                'old_slug' => 'artikel-21-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg',
                'new_slug' => 'artikel-17-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg',
                'new_title' => 'Artikel 17 LIVA: Autoliquidatioun B2B intra-EU fir Lëtzebuerger Freelancer',
                'content_replacements' => [
                    'Artikel 21 LIVA' => 'Artikel 17 LIVA',
                    'Art. 21 LIVA' => 'Art. 17 LIVA',
                ],
            ],
            [
                'old_slug' => 'artikel-61-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch',
                'new_slug' => 'artikel-63-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch',
                'new_title' => 'Artikel 63 LIVA: d\'sequentiell Rechnungs-Nummerung zu Lëtzebuerg (Punkt 3°)',
                'content_replacements' => [
                    'Artikel 61 LIVA' => 'Artikel 63 LIVA',
                    'Art. 61 LIVA' => 'Art. 63 LIVA',
                ],
            ],
        ],
        'pt' => [
            [
                'old_slug' => 'artigo-21-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo',
                'new_slug' => 'artigo-17-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo',
                'new_title' => 'Artigo 17 LIVA: autoliquidação de IVA B2B intra-UE para freelancers luxemburgueses',
                'content_replacements' => [
                    'Artigo 21 LIVA' => 'Artigo 17 LIVA',
                    'Artigo 21 da lei luxemburguesa do IVA' => 'Artigo 17 da lei luxemburguesa do IVA',
                    'Art. 21 LIVA' => 'Art. 17 LIVA',
                ],
            ],
            [
                'old_slug' => 'artigo-61-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria',
                'new_slug' => 'artigo-63-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria',
                'new_title' => 'Artigo 63 LIVA: a numeração sequencial de faturas no Luxemburgo (ponto 3°)',
                'content_replacements' => [
                    'Artigo 61 LIVA' => 'Artigo 63 LIVA',
                    'Art. 61 LIVA' => 'Art. 63 LIVA',
                ],
            ],
        ],
    ];

    public function up(): void
    {
        $totalRenamed = 0;
        $totalRelinked = 0;

        foreach (self::RENAMES as $locale => $renames) {
            foreach ($renames as $r) {
                // Idempotence
                $alreadyMigrated = DB::table('blog_posts')
                    ->where('slug', $r['new_slug'])
                    ->where('locale', $locale)
                    ->exists();

                if ($alreadyMigrated) {
                    echo "  → [$locale] {$r['new_slug']} : déjà migré, skip" . PHP_EOL;
                    continue;
                }

                $post = DB::table('blog_posts')
                    ->where('slug', $r['old_slug'])
                    ->where('locale', $locale)
                    ->first(['id', 'content']);

                if (!$post) {
                    echo "  → [$locale] {$r['old_slug']} : introuvable, skip" . PHP_EOL;
                    continue;
                }

                $newContent = str_replace(
                    array_keys($r['content_replacements']),
                    array_values($r['content_replacements']),
                    $post->content
                );

                DB::table('blog_posts')->where('id', $post->id)->update([
                    'slug' => $r['new_slug'],
                    'title' => $r['new_title'],
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);

                echo "  → [$locale] renommé : {$r['old_slug']} → {$r['new_slug']}" . PHP_EOL;
                $totalRenamed++;

                // Mise à jour des liens internes pointant vers l'ancien slug
                $linkedPosts = DB::table('blog_posts')
                    ->where('content', 'like', '%' . $r['old_slug'] . '%')
                    ->get(['id', 'content']);

                foreach ($linkedPosts as $linked) {
                    $newLinkedContent = str_replace($r['old_slug'], $r['new_slug'], $linked->content);
                    if ($newLinkedContent !== $linked->content) {
                        DB::table('blog_posts')->where('id', $linked->id)->update([
                            'content' => $newLinkedContent,
                        ]);
                        $totalRelinked++;
                    }
                }
            }
        }

        echo "  → Total : {$totalRenamed} slug(s) renommé(s), {$totalRelinked} lien(s) interne(s) corrigé(s)" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback.
    }
};
