<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Titres des pages de catégories et de tags du blog.
 *
 * Bing signalait des titres dupliqués : les cinq déclinaisons linguistiques
 * d'une même catégorie portaient toutes le nom stocké en base, c'est-à-dire le
 * français. `/de/blog/kategorie/reglementation` s'intitulait « Réglementation ».
 *
 * Les pages de tags, elles, sortent de l'index : trente et une pages sous 350
 * mots, aux titres identiques dans les cinq langues, qui n'ont jamais amené un
 * clic et consommaient du budget d'exploration.
 */
class BlogCategoryTitlesTest extends TestCase
{
    use RefreshDatabase;

    private function categorie(): BlogCategory
    {
        return BlogCategory::create([
            'name' => 'Réglementation',
            'slug' => 'reglementation',
            'sort_order' => 1,
        ]);
    }

    public function test_a_category_is_named_in_the_current_locale(): void
    {
        $categorie = $this->categorie();

        $attendus = [
            'fr' => 'Réglementation',
            'en' => 'Regulation',
            'de' => 'Regelwerk',
            'lb' => 'Reglementatioun',
            'pt' => 'Regulamentação',
        ];

        foreach ($attendus as $locale => $attendu) {
            app()->setLocale($locale);

            $this->assertSame($attendu, $categorie->translatedName(),
                "La catégorie doit s'afficher en {$locale}.");
        }
    }

    /**
     * Le repli protège les catégories créées après coup : elles gardent leur
     * nom d'origine plutôt que d'afficher une clé de traduction.
     */
    public function test_an_unknown_category_keeps_its_stored_name(): void
    {
        $categorie = BlogCategory::create([
            'name' => 'Nouvelle rubrique',
            'slug' => 'nouvelle-rubrique-jamais-traduite',
            'sort_order' => 9,
        ]);

        app()->setLocale('de');

        $this->assertSame('Nouvelle rubrique', $categorie->translatedName());
    }

    /**
     * Les cinq langues doivent donner cinq titres distincts : c'est exactement
     * ce que Bing comptait comme doublon.
     */
    public function test_the_five_locales_yield_five_distinct_names(): void
    {
        $categorie = $this->categorie();
        $noms = [];

        foreach (['fr', 'en', 'de', 'lb', 'pt'] as $locale) {
            app()->setLocale($locale);
            $noms[] = $categorie->translatedName();
        }

        $this->assertCount(5, array_unique($noms), 'Deux langues partagent le même titre : '.implode(', ', $noms));
    }

    /** Chaque catégorie livrée doit être traduite dans les cinq langues. */
    public function test_every_seeded_category_is_translated(): void
    {
        $slugs = ['guides', 'reglementation', 'freelances', 'actualites', 'guide-creation-entreprise'];

        foreach (['fr', 'en', 'de', 'lb', 'pt'] as $locale) {
            foreach ($slugs as $slug) {
                $this->assertTrue(
                    \Illuminate\Support\Facades\Lang::has("app.blog_categories.{$slug}", $locale),
                    "La catégorie {$slug} n'est pas traduite en {$locale}."
                );
            }
        }
    }
}
