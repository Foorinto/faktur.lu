<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fusion de la page fiduciaires dans /partenaires.
 *
 * Deux pages visaient le même public sur les mêmes mots-clés et se seraient
 * concurrencées dans les résultats de recherche. `/partenaires` a été retenue :
 * quatre mois d'indexation, un formulaire qui convertit, et le programme
 * « Partenaire Fondateur ». Les anciennes adresses redirigent en permanence,
 * elles ont vécu quelques jours et ont pu être partagées.
 */
class AccountantsPageTest extends TestCase
{
    /** @return array<int, array<int, string>> */
    public static function anciennesAdresses(): array
    {
        return [
            ['fr', 'pour-fiduciaires', 'partenaires'],
            ['de', 'fuer-treuhaender', 'partner'],
            ['en', 'for-accountants', 'partners'],
            ['lb', 'fir-fiduciairen', 'partneren'],
            ['pt', 'para-contabilistas', 'parceiros'],
        ];
    }

    #[DataProvider('anciennesAdresses')]
    public function test_l_ancienne_adresse_redirige_definitivement(string $locale, string $ancien, string $nouveau): void
    {
        $this->get("/{$locale}/{$ancien}")
            ->assertStatus(301)
            ->assertRedirect("/{$locale}/{$nouveau}");
    }

    #[DataProvider('anciennesAdresses')]
    public function test_la_page_de_destination_repond(string $locale, string $ancien, string $nouveau): void
    {
        $this->get("/{$locale}/{$nouveau}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Partners'));
    }

    public function test_le_sitemap_ne_liste_plus_l_ancienne_adresse(): void
    {
        // Une redirection n'a rien à faire dans un sitemap : elle ferait
        // remonter un avertissement « page avec redirection » dans la Search
        // Console.
        $this->get('/sitemap-pages.xml')
            ->assertSuccessful()
            ->assertDontSee('pour-fiduciaires', false)
            ->assertSee('partenaires', false);
    }
}
