<?php

namespace Tests\Feature;

use App\Http\Controllers\AlternativeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlternativePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_alternative_pages_render(): void
    {
        foreach (AlternativeController::competitorSlugs() as $slug) {
            $this->get("/fr/alternative-{$slug}-luxembourg")->assertOk();
        }
    }

    public function test_unknown_competitor_returns_404(): void
    {
        $this->get('/fr/alternative-unknown-luxembourg')->assertNotFound();
    }

    public function test_other_locale_returns_404(): void
    {
        // FR only for now
        $this->get('/de/alternative-sage-luxembourg')->assertNotFound();
    }
}
