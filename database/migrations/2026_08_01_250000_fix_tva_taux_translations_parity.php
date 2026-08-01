<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « TVA Luxembourg : taux, calcul et obligations ».
 *
 * LE FRANÇAIS N'EST PAS TOUCHÉ. Il a déjà été corrigé lors de la fusion des
 * doublons TVA — il porte l'article 65bis, l'amende proportionnelle de 10 à
 * 50 %, les sanctions de l'article 80 et la règle du fait générateur. Il fait
 * de surcroît partie des deux articles édités directement dans
 * l'administration de production : toute réécriture en bloc y détruirait un
 * encart absent du dépôt.
 *
 * Les quatre traductions portent déjà ces corrections de fond. Deux écarts
 * seulement subsistaient, traités ici de façon chirurgicale.
 *
 * 1. TERMINOLOGIE ALLEMANDE. Le tableau des taux nommait le 14 %
 *    « Ermäßigter Satz » et le 8 % « Reduzierter Satz » — deux façons de dire
 *    « réduit », ce qui efface la distinction. Le 14 % est le taux
 *    INTERMÉDIAIRE (Zwischensatz) ; le 8 % est le taux réduit. L'anglais, le
 *    luxembourgeois et le portugais faisaient déjà correctement la
 *    différence.
 *
 * 2. BLOC « ARTICLES CONNEXES » absent en DE, EN et LB (présent en FR et PT).
 *    Trois liens internes manquaient donc dans ces langues, d'où 4 liens
 *    contre 7. Ajouté avec les slugs propres à chaque langue.
 */
return new class extends Migration
{
    private const KEY = 'tva-luxembourg-taux-calcul-obligations';

    /** Libellés de taux, allemand uniquement. */
    private const DE_LABELS = [
        ['<td class="border border-gray-300 px-4 py-2">Ermäßigter Satz</td>', '<td class="border border-gray-300 px-4 py-2">Zwischensatz</td>'],
        ['<td class="border border-gray-300 px-4 py-2">Reduzierter Satz</td>', '<td class="border border-gray-300 px-4 py-2">Ermäßigter Satz</td>'],
    ];

    /** locale => [titre du bloc, [slug, libellé] × 3] */
    private const RELATED = [
        'de' => ['Verwandte Artikel', [
            ['innergemeinschaftliche-mwst-leitfaden-luxemburgische-unternehmen', 'Innergemeinschaftliche MwSt'],
            ['mwst-befreiung-luxemburg-schwelle-pflichten-normalregime', 'MwSt-Befreiung'],
            ['aus-luxemburg-ins-ausland-fakturieren', 'ins Ausland fakturieren'],
        ]],
        'en' => ['Related articles', [
            ['intra-community-vat-guide-luxembourg-businesses', 'intra-EU VAT'],
            ['vat-exemption-luxembourg-threshold-obligations-normal-regime', 'VAT exemption'],
            ['invoice-foreign-clients-from-luxembourg', 'invoicing abroad'],
        ]],
        'lb' => ['Verbonnen Artikelen', [
            ['innergemeinschaftlech-tva-guide-letzebuergesch-entreprisen', 'Intracommunautär TVA'],
            ['tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime', 'TVA-Franchise'],
            ['vu-letzebuerg-aus-ausland-fakturieren', 'an d\'Ausland fakturéieren'],
        ]],
    ];

    public function up(): void
    {
        // 1. Terminologie allemande. L'ordre compte : on renomme d'abord le
        //    14 %, sinon le 8 % prendrait la place libérée.
        $de = DB::table('blog_posts')
            ->where('translation_key', self::KEY)
            ->where('locale', 'de')
            ->first(['id', 'content']);

        if ($de) {
            $content = $de->content;

            foreach (self::DE_LABELS as [$from, $to]) {
                $pos = mb_strpos($content, $from);
                if ($pos !== false) {
                    $content = mb_substr($content, 0, $pos).$to.mb_substr($content, $pos + mb_strlen($from));
                }
            }

            DB::table('blog_posts')->where('id', $de->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
        }

        // 2. Bloc « articles connexes » là où il manque.
        foreach (self::RELATED as $locale => [$title, $links]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || str_contains($post->content, $title)) {
                continue;
            }

            $items = '';
            foreach ($links as [$slug, $label]) {
                $items .= '<li><a href="/'.$locale.'/blog/'.$slug.'" class="text-primary-500 hover:text-primary-600 text-sm">'.$label.' →</a></li>';
            }

            $block = "\n\n".'<div class="mt-8 p-4 bg-slate-50 rounded-xl">'
                .'<h3 class="text-base font-semibold text-slate-900 mb-3">'.$title.'</h3>'
                .'<ul class="space-y-1">'.$items.'</ul></div>';

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => rtrim($post->content)."\n".$block,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retirer des liens valides et remettre une terminologie ambiguë
        // n'aurait pas de sens.
    }
};
