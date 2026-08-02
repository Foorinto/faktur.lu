<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Relancer un client qui ne paie pas » — attribution du texte de loi
 * dans les quatre traductions.
 *
 * La passe précédente n'avait abouti qu'en français : les formulations
 * retenues pour la ligne des 40 euros ne correspondaient pas exactement à
 * celles des traductions. Corrigé ici sur les chaînes réelles.
 *
 * Le fond reste le même : la majoration de 8 points et l'indemnité forfaitaire
 * de 40 euros viennent de la loi du 29 mars 2013, qui a modifié celle du
 * 18 avril 2004 pour transposer la directive 2011/7/UE. L'article « délais de
 * paiement » avertit que citer le seul texte de 2004 revient à décrire un
 * régime dépassé.
 */
return new class extends Migration
{
    private const KEY = 'relancer-client-impaye-luxembourg';

    /** locale => [avant, après] */
    private function fixes(): array
    {
        return [
            'de' => [
                '<li><strong>Pauschale Entschädigung</strong>: 40 € für Beitreibungskosten (ohne Nachweis, Art. 6 des Gesetzes)</li>',
                "<li><strong>Pauschale Entschädigung</strong>: 40 € für Beitreibungskosten, ohne Nachweis</li>\n    <li>Der Zuschlag von 8 Punkten und diese Entschädigung stammen aus dem <strong>Gesetz vom 29. März 2013</strong>, das jenes vom 18. April 2004 zur Umsetzung der Richtlinie 2011/7/EU geändert hat</li>",
            ],
            'en' => [
                '<li><strong>Fixed compensation</strong>: €40 for recovery costs (no supporting document required, art. 6 of the Law)</li>',
                "<li><strong>Fixed compensation</strong>: €40 for recovery costs, with no supporting document required</li>\n    <li>The 8-point margin and this compensation come from the <strong>law of 29 March 2013</strong>, which amended the law of 18 April 2004 to transpose directive 2011/7/EU</li>",
            ],
            'lb' => [
                '<li><strong>Forfaitaire Entschiedegung</strong>: 40 € fir Bäitreiwungskäschten (ouni Beleg, Art. 6 vum Gesetz)</li>',
                "<li><strong>Forfaitaire Entschiedegung</strong>: 40 € fir Bäitreiwungskäschten, ouni Beleg</li>\n    <li>D'Majoratioun vun 8 Punkten an dës Entschiedegung kommen aus dem <strong>Gesetz vum 29. Mäerz 2013</strong>, dat dat vum 18. Abrëll 2004 geännert huet fir d'Richtlinn 2011/7/EU ëmzesetzen</li>",
            ],
            'pt' => [
                '<li><strong>Indemnização fixa</strong>: 40 € para despesas de cobrança (sem justificativo, art. 6.º da lei)</li>',
                "<li><strong>Indemnização fixa</strong>: 40 € para despesas de cobrança, sem justificativo</li>\n    <li>A majoração de 8 pontos e esta indemnização decorrem da <strong>lei de 29 de março de 2013</strong>, que alterou a de 18 de abril de 2004 para transpor a diretiva 2011/7/UE</li>",
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->fixes() as $locale => [$from, $to]) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post || str_contains($post->content, '2013')) {
                continue;
            }

            if (! str_contains($post->content, $from)) {
                echo "  !! {$locale} : motif introuvable\n";

                continue;
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => str_replace($from, $to, $post->content),
                'updated_at' => now(),
            ]);

            echo "  {$locale} : attribution corrigée\n";
        }
    }

    public function down(): void
    {
        // Réattribuer les règles de 2013 au texte de 2004 n'aurait pas de sens.
    }
};
