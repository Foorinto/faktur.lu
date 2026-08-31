<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FiscalSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * La ventilation TVA doit tenir compte des remises globales.
 *
 * Défaut trouvé par un client payant le 2026-08-31, capture à l'appui : son
 * récapitulatif TVA affichait 4 407,50 € de base à 17 % pour un total de
 * 3 888,78 €. Vérifié sur ses données : 518,72 € de remises globales.
 *
 * ⚠️ Une remise globale porte sur le DOCUMENT, pas sur les lignes, qui gardent
 * leur montant. Additionner `invoice_items.total_ht` groupé par taux ignore
 * donc la remise, et le tableau affichait une base supérieure à son propre
 * total — celle qu'on recopie dans une déclaration de TVA.
 *
 * Le même code existait en deux exemplaires : livre de recettes et
 * récapitulatif fiscal. Les deux sont couverts ici.
 */
class VatBreakdownWithDiscountsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    /**
     * Trois lignes de 100 € HT à 17 %, moins 50 € de remise globale.
     *
     *   base brute  : 300,00 €
     *   remise      :  50,00 €
     *   base nette  : 250,00 €   ← ce que la facture enregistre
     *   TVA         :  42,50 €
     */
    private function factureAvecRemise(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addMonth()->toDateString(),
            'finalized_at' => now()->startOfYear()->addMonth()->toDateString(),
            'paid_at' => now()->startOfYear()->addMonth()->toDateString(),
            'total_ht' => 250, 'total_vat' => 42.5, 'total_ttc' => 292.5,
        ]);

        foreach (range(1, 3) as $position) {
            $facture->items()->create([
                'title' => "Ligne {$position}",
                'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17,
                'total_ht' => 100, 'total_vat' => 17, 'position' => $position,
            ]);
        }

        $facture->discounts()->create(['type' => 'amount', 'value' => 50, 'label' => 'Geste commercial']);

        return $facture->fresh();
    }

    public function test_the_revenue_book_deducts_the_global_discount(): void
    {
        $this->factureAvecRemise();

        $this->get(route('reports.revenue-book'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // La base par taux, remise déduite — 250 € et non 300 €.
                ->where('vatBreakdown.0.base', 250)
                ->where('vatBreakdown.0.amount', 42.5)
            );
    }

    /**
     * ⚠️ Le test qui manquait : un tableau dont la ligne « Total » ne totalise
     * pas sa propre colonne. C'est ce que le client a vu.
     */
    public function test_the_rows_add_up_to_the_total(): void
    {
        $this->factureAvecRemise();

        $this->get(route('reports.revenue-book'))
            ->assertInertia(function (AssertableInertia $page) {
                $lignes = collect($page->toArray()['props']['vatBreakdown']);
                $totaux = $page->toArray()['props']['totals'];

                $this->assertSame(
                    round((float) $totaux['ht'], 2),
                    round($lignes->sum('base'), 2),
                    'La somme des bases par taux doit égaler le total HT.'
                );
                $this->assertSame(
                    round((float) $totaux['vat'], 2),
                    round($lignes->sum('amount'), 2),
                    'La somme des TVA par taux doit égaler le total de TVA.'
                );
            });
    }

    public function test_the_fiscal_summary_deducts_it_too(): void
    {
        $this->factureAvecRemise();

        // La ventilation vit sous `revenue`, à côté du total dont elle doit
        // être la décomposition.
        $recettes = app(FiscalSummaryService::class)->getSummary(now()->year)['revenue'];
        $ventilation = collect($recettes['vat_breakdown']);

        $this->assertSame(250.0, round($ventilation->sum('base'), 2));
        $this->assertSame(round((float) $recettes['total_ht'], 2), round($ventilation->sum('base'), 2));
        $this->assertSame(round((float) $recettes['total_vat'], 2), round($ventilation->sum('amount'), 2));
    }

    /**
     * Sans remise, rien ne change : la base par taux reste celle des lignes.
     */
    public function test_without_a_discount_nothing_moves(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addMonth()->toDateString(),
            'finalized_at' => now()->startOfYear()->addMonth()->toDateString(),
            'paid_at' => now()->startOfYear()->addMonth()->toDateString(),
            'total_ht' => 300, 'total_vat' => 51, 'total_ttc' => 351,
        ]);
        $facture->items()->create([
            'title' => 'Prestation', 'quantity' => 3, 'unit_price' => 100,
            'vat_rate' => 17, 'total_ht' => 300, 'total_vat' => 51, 'position' => 1,
        ]);

        $this->get(route('reports.revenue-book'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('vatBreakdown.0.base', 300)
                ->where('vatBreakdown.0.amount', 51)
            );
    }

    /**
     * Deux taux sur une même facture : la remise se répartit, elle ne tombe pas
     * entièrement sur l'un d'eux.
     */
    public function test_the_discount_is_spread_across_rates(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addMonth()->toDateString(),
            'finalized_at' => now()->startOfYear()->addMonth()->toDateString(),
            'paid_at' => now()->startOfYear()->addMonth()->toDateString(),
            'total_ht' => 180, 'total_vat' => 0, 'total_ttc' => 180,
        ]);
        $facture->items()->createMany([
            ['title' => 'A', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17, 'total_ht' => 100, 'total_vat' => 17, 'position' => 1],
            ['title' => 'B', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 3, 'total_ht' => 100, 'total_vat' => 3, 'position' => 2],
        ]);
        $facture->discounts()->create(['type' => 'amount', 'value' => 20, 'label' => 'Remise']);

        $this->get(route('reports.revenue-book'))
            ->assertInertia(function (AssertableInertia $page) {
                $lignes = collect($page->toArray()['props']['vatBreakdown'])->keyBy('rate');

                // 20 € de remise sur 200 € de base, soit 10 € par taux.
                $this->assertSame(90.0, round((float) $lignes[17]['base'], 2));
                $this->assertSame(90.0, round((float) $lignes[3]['base'], 2));
            });
    }

    /**
     * Deux factures au MÊME taux : leurs bases s'additionnent.
     *
     * Sans ce cas, remplacer le cumul par une simple affectation passait
     * inaperçu — chaque test n'avait qu'une facture par taux.
     */
    public function test_two_invoices_at_the_same_rate_are_summed(): void
    {
        $this->factureAvecRemise();
        $this->factureAvecRemise();

        $this->get(route('reports.revenue-book'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // 250 € nets par facture, deux factures.
                ->where('vatBreakdown.0.base', 500)
                ->where('vatBreakdown.0.amount', 85)
                ->has('vatBreakdown', 1)
            );
    }

    /**
     * Le taux le plus élevé d'abord : c'est l'ordre d'une déclaration, et
     * celui que le tableau annonce.
     */
    public function test_rates_are_listed_highest_first(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addMonth()->toDateString(),
            'finalized_at' => now()->startOfYear()->addMonth()->toDateString(),
            'paid_at' => now()->startOfYear()->addMonth()->toDateString(),
            'total_ht' => 300, 'total_vat' => 0, 'total_ttc' => 300,
        ]);
        $facture->items()->createMany([
            ['title' => 'A', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 3, 'total_ht' => 100, 'total_vat' => 3, 'position' => 1],
            ['title' => 'B', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17, 'total_ht' => 100, 'total_vat' => 17, 'position' => 2],
            ['title' => 'C', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 8, 'total_ht' => 100, 'total_vat' => 8, 'position' => 3],
        ]);

        $this->get(route('reports.revenue-book'))
            ->assertInertia(function (AssertableInertia $page) {
                $taux = collect($page->toArray()['props']['vatBreakdown'])->pluck('rate')->all();

                $this->assertSame([17.0, 8.0, 3.0], array_map('floatval', $taux));
            });
    }

    /**
     * ⚠️ `vat_breakdown` lit les lignes ET les remises de chaque facture.
     * Sans chargement anticipé, un exercice complet ferait deux requêtes par
     * facture — invisible sur un jeu de test, sensible sur un mutualisé.
     */
    public function test_the_page_does_not_query_once_per_invoice(): void
    {
        foreach (range(1, 8) as $i) {
            $this->factureAvecRemise();
        }

        \DB::enableQueryLog();
        $this->get(route('reports.revenue-book'))->assertSuccessful();
        $requetes = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Huit factures ne doivent pas coûter huit requêtes de plus.
        $this->assertLessThan(
            20,
            $requetes,
            "Le livre de recettes a exécuté {$requetes} requêtes : le chargement anticipé des lignes et des remises a disparu."
        );
    }
}
