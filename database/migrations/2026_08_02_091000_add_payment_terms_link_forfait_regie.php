<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Facturer un projet : forfait ou régie » — renvoi manquant.
 *
 * La version française termine sa section sur les acomptes par un renvoi vers
 * l'article « délais de paiement ». Les quatre traductions s'arrêtaient à la
 * phrase précédente : le lien n'existait donc pas, d'où deux liens contre
 * trois après la première passe de corrections.
 *
 * Le renvoi est ajouté comme phrase autonome, juste avant le rappel sur
 * l'article 63 §5, à la même place que dans le français.
 */
return new class extends Migration
{
    private const KEY = 'facturation-projet-forfait-regie-luxembourg';

    /** locale => [slug cible, phrase complète] */
    private const SENTENCES = [
        'de' => ['zahlungsfristen-luxemburg-rechtlicher-rahmen-2026',
            'Siehe dazu auch unsere Hinweise zu den %s.', 'Zahlungsfristen'],
        'en' => ['payment-terms-luxembourg-legal-framework-2026',
            'See also our guidance on %s.', 'payment terms'],
        'lb' => ['bezuelungsfristen-letzebuerg-rechtleche-kader-2026',
            'Kuckt dozou och eis Rotschléi zu de %s.', 'Bezuelungsfristen'],
        'pt' => ['prazos-de-pagamento-no-luxemburgo-quadro-legal-2026',
            'Veja também os nossos conselhos sobre os %s.', 'prazos de pagamento'],
    ];

    public function up(): void
    {
        foreach (self::SENTENCES as $locale => [$slug, $template, $label]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || str_contains($post->content, '/blog/'.$slug)) {
                continue;
            }

            $link = '<a href="/'.$locale.'/blog/'.$slug.'">'.$label.'</a>';
            $sentence = '<p>'.sprintf($template, $link).'</p>';

            // Inséré juste avant le rappel sur l'article 63 §5, donc à la fin
            // de la section consacrée aux acomptes.
            $anchor = '<p>';
            $pos = mb_strpos($post->content, '<div class="bg-primary-50 rounded-xl p-6 mt-8">');

            if ($pos === false) {
                continue;
            }

            // On remonte au dernier bloc <p> avant le CTA pour rester dans la
            // section acomptes plutôt que d'écrire après le CTA.
            $before = mb_substr($post->content, 0, $pos);
            $marker = mb_strrpos($before, '<p class="text-sm text-slate-500">');

            if ($marker === false) {
                continue;
            }

            $content = mb_substr($post->content, 0, $marker)
                .$sentence."\n\n"
                .mb_substr($post->content, $marker);

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retirer un lien interne valide n'aurait pas de sens.
    }
};
