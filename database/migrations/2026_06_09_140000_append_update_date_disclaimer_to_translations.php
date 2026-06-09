<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Option B de l'audit : ajoute une signature de mise à jour + disclaimer
 * standardisés à la fin de chaque article DE/EN/LB/PT (116 articles).
 *
 * Objectif :
 *   - Signal SEO « fresh content » (date de MAJ visible) sur les 4 langues
 *   - Cohérence visuelle avec les articles FR refondus (mêmes blocs en pied)
 *   - Protection juridique légère (disclaimer « consultez votre fiduciaire »)
 *   - Lien sortant vers l'AED dans chaque langue
 *
 * Le bloc s'applique à TOUS les articles non-FR, même ceux qui n'avaient
 * pas d'erreur factuelle — pour la cohérence visuelle inter-langues.
 *
 * Idempotent : un marqueur HTML unique par locale empêche les ajouts en double.
 */
return new class extends Migration
{
    /**
     * Marqueur d'idempotence — apposé une fois par article.
     */
    private const MARKER_TEMPLATE = '<!-- audit-translation-%s-v2026-06-09 -->';

    /**
     * Bloc à appender, par locale, avec traduction native.
     */
    private const APPEND_BLOCK = [
        'de' => <<<'HTML'

<!-- audit-translation-de-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualisiert am 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Jährlich zu überprüfen</p>
    <p>Die Schwellen, Sätze und Verfahren der luxemburgischen Steuergesetzgebung können sich ändern. Diese Seite wird regelmäßig aktualisiert, aber für Ihre persönliche Situation wenden Sie sich bitte an Ihren Treuhänder oder direkt an die <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
HTML,

        'en' => <<<'HTML'

<!-- audit-translation-en-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Article updated on 9 June 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">To verify yearly</p>
    <p>The thresholds, rates and procedures of Luxembourg tax law may evolve. This page is updated regularly, but for your personal situation, please consult your accountant or directly the <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
HTML,

        'lb' => <<<'HTML'

<!-- audit-translation-lb-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artikel aktualiséiert den 9. Juni 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">All Joer ze iwwerpréiwen</p>
    <p>D'Schwellen, Sätz a Prozeduren vun der Lëtzebuerger Steiergesetzgebung kënnen sech änneren. Dës Säit gëtt reegelméisseg aktualiséiert, mä fir Är perséinlech Situatioun, kontaktéiert w.e.g. Är Fiduciaire oder direkt d'<a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
HTML,

        'pt' => <<<'HTML'

<!-- audit-translation-pt-v2026-06-09 -->
<p class="text-sm text-slate-500 mt-6"><em>Artigo atualizado a 9 de junho de 2026.</em></p>
<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">A verificar todos os anos</p>
    <p>Os limites, taxas e procedimentos da legislação fiscal luxemburguesa podem evoluir. Esta página é atualizada regularmente, mas para a sua situação pessoal, consulte o seu fiduciário ou diretamente a <a href="https://pfi.public.lu/" target="_blank" rel="noopener">AED (Administration de l'Enregistrement, des Domaines et de la TVA)</a>.</p>
</div>
HTML,
    ];

    public function up(): void
    {
        $totalUpdated = 0;
        $byLocale = [];

        foreach (self::APPEND_BLOCK as $locale => $block) {
            $byLocale[$locale] = 0;
            $marker = sprintf(self::MARKER_TEMPLATE, $locale);

            $posts = DB::table('blog_posts')
                ->where('locale', $locale)
                ->get(['id', 'slug', 'content']);

            foreach ($posts as $post) {
                // Idempotence : skip si déjà marqué
                if (strpos($post->content, $marker) !== false) {
                    continue;
                }

                $newContent = $post->content . $block;

                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);

                $byLocale[$locale]++;
                $totalUpdated++;
            }
        }

        foreach ($byLocale as $loc => $n) {
            echo "  → [$loc] {$n} article(s) marqué(s) MAJ" . PHP_EOL;
        }
        echo "  → Total : {$totalUpdated} article(s) sur 4 langues" . PHP_EOL;
    }

    public function down(): void
    {
        // Pas de rollback automatique. Si besoin, retirer manuellement via str_replace.
    }
};
