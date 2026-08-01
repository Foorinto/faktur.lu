<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « TVA Luxembourg 2026 : quatre taux » — aligne la référence légale de
 * l'autoliquidation dans les quatre traductions sur la version française.
 *
 * Le français a déjà été corrigé en « art. 17 LIVA / art. 196 directive
 * 2006/112/CE » ; DE, EN, LB et PT citaient encore l'article 21 LIVA.
 *
 * L'article 21 LIVA porte sur le fait générateur et l'exigibilité de la taxe :
 * il n'a rien à voir avec l'autoliquidation. Les deux références justes sont
 *   - art. 17 LIVA          : lieu des prestations de services (pourquoi la
 *                             TVA luxembourgeoise ne s'applique pas) ;
 *   - art. 196 de la directive 2006/112/CE : le preneur est le redevable
 *                             (ce que la facture doit mentionner).
 *
 * Voir aussi la correction du même défaut dans resources/lang/*\/app.php et
 * dans les gabarits PDF, faite dans le même lot.
 */
return new class extends Migration
{
    private const KEY = 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques';

    /** locale => [avant, après] */
    private const REPLACEMENTS = [
        'de' => [
            '(Reverse-Charge Art. 21 LIVA)',
            '(Reverse-Charge, Art. 17 LIVA / Art. 196 Richtlinie 2006/112/EG)',
        ],
        'en' => [
            '(reverse charge art. 21 LIVA)',
            '(reverse charge, art. 17 LIVA / art. 196 Directive 2006/112/EC)',
        ],
        'lb' => [
            '(Autoliquidatioun Art. 21 LIVA)',
            '(Autoliquidatioun, Art. 17 LIVA / Art. 196 Richtlinn 2006/112/EG)',
        ],
        'pt' => [
            '(autoliquidação art. 21 LIVA)',
            '(autoliquidação, art. 17 LIVA / art. 196.º Diretiva 2006/112/CE)',
        ],
    ];

    public function up(): void
    {
        $this->apply(fn (array $pair) => $pair);
    }

    public function down(): void
    {
        $this->apply(fn (array $pair) => array_reverse($pair));
    }

    /**
     * @param  callable(array{0:string,1:string}): array{0:string,1:string}  $direction
     */
    private function apply(callable $direction): void
    {
        foreach (self::REPLACEMENTS as $locale => $pair) {
            [$from, $to] = $direction($pair);

            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || ! str_contains($post->content, $from)) {
                continue;
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => str_replace($from, $to, $post->content),
                'updated_at' => now(),
            ]);
        }
    }
};
