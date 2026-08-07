<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantsPageTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['fr'], ['en'], ['de'], ['lb'], ['pt']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('locales')]
    public function test_la_page_se_charge_dans_chaque_langue(string $locale): void
    {
        $this->get(route("for_accountants.$locale", ['locale' => $locale]))
            ->assertSuccessful()
            ->assertInertia(fn ($p) => $p->component('Segments/Accountants'));
    }

    public function test_la_page_figure_dans_le_sitemap(): void
    {
        $this->get('/sitemap-pages.xml')->assertSuccessful()->assertSee('pour-fiduciaires', false);
    }
}
