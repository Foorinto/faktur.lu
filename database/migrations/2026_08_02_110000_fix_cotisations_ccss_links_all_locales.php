<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Cotisations sociales de l'indépendant (CCSS) ».
 *
 * Le fond n'appelait pas de correction. C'est l'un des articles les mieux
 * calibrés du blog : les taux y sont annoncés comme un « ordre de grandeur »,
 * chaque valeur est précédée d'un tilde, et une note renvoie explicitement au
 * CCSS et à la fiduciaire pour les chiffres en vigueur. Il est de surcroît
 * cohérent avec le guide « entreprise individuelle au Luxembourg », corrigé
 * plus tôt, qui retient la même fourchette de 25 %.
 *
 * Deux améliorations seulement.
 *
 * 1. LIEN INTERNE MANQUANT. Les quatre traductions n'avaient qu'un lien contre
 *    deux : le renvoi vers l'article « livre des recettes », qui accompagne le
 *    conseil de tenir une comptabilité claire, n'avait pas été transposé.
 *
 * 2. CCSS CITÉ SANS ÊTRE CLIQUABLE. La note de prudence mentionne
 *    « ccss.public.lu » en texte brut dans les cinq langues. Puisque toute la
 *    valeur de cette note tient dans le renvoi à la source, autant qu'il soit
 *    atteignable.
 */
return new class extends Migration
{
    private const KEY = 'cotisations-sociales-independant-ccss-luxembourg-2026';

    /** locale => [slug livre des recettes, phrase avant, phrase après] */
    private function bookkeeping(): array
    {
        return [
            'de' => ['einnahmenbuch-luxemburg-pflichten-vorlage',
                '<li>Führen Sie eine klare Buchhaltung, um Ihren tatsächlichen Gewinn jederzeit zu kennen.</li>',
                '<li>Führen Sie eine <a href="/de/blog/einnahmenbuch-luxemburg-pflichten-vorlage">klare Buchhaltung</a>, um Ihren tatsächlichen Gewinn jederzeit zu kennen.</li>'],
            'lb' => ['akommessbuch-letzebuerg-obligatiounen-modell',
                "<li>Féiert eng kloer Comptabilitéit, fir Äre richtege Gewënn zu all Moment ze kennen.</li>",
                "<li>Féiert eng <a href=\"/lb/blog/akommessbuch-letzebuerg-obligatiounen-modell\">kloer Comptabilitéit</a>, fir Äre richtege Gewënn zu all Moment ze kennen.</li>"],
            'pt' => ['livro-de-receitas-no-luxemburgo-obrigacoes-e-modelo',
                '<li>Mantenha uma contabilidade clara para conhecer o seu lucro real a qualquer momento.</li>',
                '<li>Mantenha uma <a href="/pt/blog/livro-de-receitas-no-luxemburgo-obrigacoes-e-modelo">contabilidade clara</a> para conhecer o seu lucro real a qualquer momento.</li>'],
        ];
    }

    /** L'anglais formule autrement : on l'ancre sur le mot clé. */
    private const EN_BOOKKEEPING = [
        '<li>Keep clear accounting to know your real profit at any time.</li>',
        '<li>Keep <a href="/en/blog/revenue-book-luxembourg-obligations-template">clear accounting</a> to know your real profit at any time.</li>',
    ];

    /** Le renvoi au CCSS, en texte brut dans les cinq langues. */
    private const CCSS_LINK = '<a href="https://ccss.public.lu/" target="_blank" rel="noopener">ccss.public.lu</a>';

    public function up(): void
    {
        foreach (['fr', 'de', 'en', 'lb', 'pt'] as $locale) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = $post->content;

            // 1. Lien interne vers le livre des recettes (traductions seules ;
            //    le français l'a déjà).
            $map = $this->bookkeeping();

            if (isset($map[$locale])) {
                [$slug, $from, $to] = $map[$locale];

                if (! str_contains($content, '/blog/'.$slug)) {
                    $content = str_replace($from, $to, $content);
                }
            }

            if ($locale === 'en' && ! str_contains($content, '/blog/revenue-book-luxembourg-obligations-template')) {
                [$from, $to] = self::EN_BOOKKEEPING;
                $content = str_replace($from, $to, $content);
            }

            // 2. Rendre ccss.public.lu cliquable, sans toucher au reste de la
            //    phrase ni aux occurrences déjà liées.
            if (str_contains($content, '(ccss.public.lu)')) {
                $content = str_replace('(ccss.public.lu)', '('.self::CCSS_LINK.')', $content);
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retirer des liens valides n'aurait pas de sens.
    }
};
