<?php

namespace Tests\Unit\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\FiscalSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Récapitulatif fiscal annuel.
 *
 * Ce sont les chiffres que l'utilisateur transmet à sa fiduciaire (et qui
 * alimentent le formulaire 152). Une erreur ici ne se voit pas à l'écran mais
 * se retrouve dans une déclaration.
 */
class FiscalSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private FiscalSummaryService $service;
    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        BusinessSettings::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->service = app(FiscalSummaryService::class);
    }

    private function issueInvoice(float $ht, float $vatRate, ?string $finalizedAt = null): Invoice
    {
        $vat = round($ht * $vatRate / 100, 4);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => $finalizedAt ?? now(),
            'finalized_at' => $finalizedAt ?? now(),
            'total_ht' => $ht,
            'total_vat' => $vat,
            'total_ttc' => $ht + $vat,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => $ht,
            'vat_rate' => $vatRate,
            'total_ht' => $ht,
            'total_vat' => $vat,
            'total_ttc' => $ht + $vat,
        ]);

        return $invoice;
    }

    private function recordExpense(float $ht, string $category = 'office'): Expense
    {
        return Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur',
            'category' => $category,
            'amount_ht' => $ht,
            'vat_rate' => 17,
            'amount_vat' => round($ht * 0.17, 4),
            'amount_ttc' => round($ht * 1.17, 4),
            'is_deductible' => true,
        ]);
    }

    public function test_the_taxable_profit_is_revenue_minus_expenses(): void
    {
        $this->issueInvoice(10000, 17);
        $this->recordExpense(2500);

        $summary = $this->service->getSummary(now()->year);

        $this->assertEquals(10000, $summary['revenue']['total_ht']);
        $this->assertEquals(2500, $summary['expenses']['total_ht']);
        // C'est cette ligne qui part dans la déclaration.
        $this->assertEquals(7500, $summary['net_profit']);
    }

    public function test_the_profit_can_be_negative(): void
    {
        $this->issueInvoice(1000, 17);
        $this->recordExpense(4000);

        // Un exercice déficitaire doit être restitué tel quel, pas ramené à zéro.
        $this->assertEquals(-3000, $this->service->getSummary(now()->year)['net_profit']);
    }

    public function test_only_the_requested_year_is_counted(): void
    {
        $this->issueInvoice(10000, 17);
        $this->issueInvoice(50000, 17, now()->subYear()->format('Y-m-d H:i:s'));

        $summary = $this->service->getSummary(now()->year);

        $this->assertEquals(10000, $summary['revenue']['total_ht']);
    }

    public function test_expenses_are_broken_down_by_category(): void
    {
        $this->recordExpense(1000, 'office');
        $this->recordExpense(500, 'office');
        $this->recordExpense(300, 'travel');

        $byCategory = $this->service->getSummary(now()->year)['expenses']['by_category'];

        $this->assertEquals(1500, $byCategory['office']['total_ht']);
        $this->assertEquals(2, $byCategory['office']['count']);
        $this->assertEquals(300, $byCategory['travel']['total_ht']);
    }

    public function test_each_expense_category_carries_its_form_152_label(): void
    {
        $this->recordExpense(1000, 'office');

        $byCategory = $this->service->getSummary(now()->year)['expenses']['by_category'];

        // Le libellé sert de correspondance avec le formulaire fiscal.
        $this->assertArrayHasKey('form152_label', $byCategory['office']);
        $this->assertNotEmpty($byCategory['office']['form152_label']);
    }

    public function test_another_company_figures_never_leak_in(): void
    {
        $this->issueInvoice(10000, 17);

        $other = User::factory()->create();
        $this->actingAs($other);
        BusinessSettings::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $other->id]);
        Invoice::factory()->create([
            'user_id' => $other->id,
            'client_id' => $otherClient->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now(),
            'finalized_at' => now(),
            'total_ht' => 99999,
        ]);

        // On repasse sur le premier utilisateur : son récapitulatif doit ignorer
        // totalement l'activité de l'autre entreprise.
        $this->actingAs($this->user);

        $this->assertEquals(10000, $this->service->getSummary(now()->year)['revenue']['total_ht']);
    }

    public function test_a_year_without_activity_returns_zeroes_not_an_error(): void
    {
        $summary = $this->service->getSummary(now()->subYears(3)->year);

        $this->assertEquals(0, $summary['revenue']['total_ht']);
        $this->assertEquals(0, $summary['expenses']['total_ht']);
        $this->assertEquals(0, $summary['net_profit']);
    }
}
