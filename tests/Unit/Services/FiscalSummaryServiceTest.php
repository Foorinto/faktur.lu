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

    /**
     * Le total de la colonne « TVA déductible » doit être la somme de ses
     * lignes. Elles affichaient la TVA payée quand le total, lui, ne retenait
     * que la part récupérable : un achat allemand à 19 % faisait afficher
     * 19 € sur sa ligne et 0 € dans le total, sans que rien ne l'explique.
     */
    public function test_the_deductible_column_adds_up_to_its_own_total(): void
    {
        $this->recordExpense(200, 'office');

        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Amazon.de',
            'supplier_country' => 'DE',
            'category' => 'hardware',
            'amount_ht' => 100,
            'vat_rate' => 19,
            'vat_regime' => Expense::REGIME_FOREIGN_VAT,
        ]);

        $expenses = $this->service->getSummary(now()->year)['expenses'];
        $byCategory = $expenses['by_category'];

        $this->assertEquals(0, $byCategory['hardware']['total_vat_deductible'], 'La TVA allemande ne se déduit pas ici.');
        $this->assertEquals(19, $byCategory['hardware']['total_vat_non_deductible']);
        $this->assertEquals(34, $byCategory['office']['total_vat_deductible']);

        $sommeDesLignes = array_sum(array_column($byCategory, 'total_vat_deductible'));

        $this->assertEquals(
            $expenses['total_vat_deductible'],
            $sommeDesLignes,
            'Les lignes doivent s\'additionner au total affiché juste en dessous.'
        );
        $this->assertEquals(34, $expenses['total_vat_deductible']);
    }

    /**
     * Une catégorie rebaptisée doit s'afficher sous son nouveau nom, tout en
     * continuant de se reporter sur sa ligne du formulaire 152. Les deux
     * libellés coexistent et ne disent pas la même chose.
     */
    public function test_a_renamed_category_keeps_its_own_label(): void
    {
        \App\Models\PurchaseCategory::create([
            'user_id' => $this->user->id,
            'key' => 'office',
            'label' => 'Loyer et charges',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Expense::forgetCategoryMapCache();

        $this->recordExpense(200, 'office');

        $byCategory = $this->service->getSummary(now()->year)['expenses']['by_category'];

        $this->assertSame('Loyer et charges', $byCategory['office']['label']);
        $this->assertSame('Fournitures de bureau', $byCategory['office']['form152_label']);
    }

    /**
     * L'autoliquidation gonfle les deux colonnes du même montant et laisse le
     * solde intact. C'est tout l'intérêt du mécanisme : rien de plus à payer,
     * mais l'opération apparaît, et l'AED peut la recouper avec ce que l'État
     * du fournisseur a déclaré.
     */
    public function test_reverse_charge_shows_on_both_sides_and_nets_out(): void
    {
        $avant = $this->service->getSummary(now()->year)['vat']['balance'];

        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => 'hardware',
            'amount_ht' => 1000,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
        ]);

        $vat = $this->service->getSummary(now()->year)['vat'];

        $this->assertEquals(170, $vat['reverse_charge_due']);
        $this->assertEquals(170, $vat['reverse_charge_deductible']);
        $this->assertEquals($avant, $vat['balance'], 'Le solde à payer ne doit pas bouger.');
    }

    /**
     * Les deux origines doivent rester lisibles séparément : ce qui a été
     * facturé à des clients, et ce que l'entreprise s'est facturé à elle-même.
     * Les confondre sous « TVA collectée » ferait passer une autoliquidation
     * pour du chiffre d'affaires.
     */
    public function test_the_two_origins_of_vat_due_stay_distinguishable(): void
    {
        $this->issueInvoice(10000, 17); // 1 700 € facturés à un client

        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => 'hardware',
            'amount_ht' => 2000,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
        ]);

        $vat = $this->service->getSummary(now()->year)['vat'];

        $this->assertTrue($vat['has_reverse_charge']);
        $this->assertEquals(1700, $vat['collected_on_sales'], 'Seul ce qui a été facturé à un client.');
        $this->assertEquals(340, $vat['reverse_charge_due']);
        $this->assertEquals(2040, $vat['collected'], 'Le total reste la somme des deux.');

        $this->assertEquals(
            $vat['collected_on_sales'] + $vat['reverse_charge_due'],
            $vat['collected'],
            'Les sous-lignes doivent s\'additionner à la ligne affichée au-dessus.'
        );
    }

    public function test_without_reverse_charge_the_summary_keeps_its_old_shape(): void
    {
        $this->issueInvoice(10000, 17);
        $this->recordExpense(1000);

        $vat = $this->service->getSummary(now()->year)['vat'];

        $this->assertFalse($vat['has_reverse_charge'], 'Aucun détail superflu quand il n\'y a rien à détailler.');
        $this->assertEquals(0, $vat['reverse_charge_due']);
        $this->assertEquals($vat['collected'], $vat['collected_on_sales']);
    }

    /**
     * Sans droit à déduction, la contrepartie disparaît : la TVA reste due, et
     * cette fois elle se paie réellement.
     */
    public function test_a_non_deductible_reverse_charge_is_actually_owed(): void
    {
        $avant = $this->service->getSummary(now()->year)['vat']['balance'];

        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => 'hardware',
            'amount_ht' => 1000,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
            'is_deductible' => false,
        ]);

        $vat = $this->service->getSummary(now()->year)['vat'];

        $this->assertEquals(170, $vat['reverse_charge_due']);
        $this->assertEquals(0, $vat['reverse_charge_deductible']);
        $this->assertEquals($avant + 170, $vat['balance']);
    }

    /**
     * Écartée de la TVA déductible — à raison — cette TVA disparaissait de la
     * vue. Elle n'est pourtant pas perdue : elle se récupère par une demande
     * de remboursement, déposée pays par pays.
     */
    private function achatEtranger(string $pays, float $ht, float $taux): void
    {
        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur '.$pays,
            'supplier_country' => $pays,
            'category' => 'hardware',
            'amount_ht' => $ht,
            'vat_rate' => $taux,
            'vat_regime' => Expense::REGIME_FOREIGN_VAT,
        ]);
    }

    public function test_foreign_vat_is_broken_down_by_country(): void
    {
        $this->achatEtranger('DE', 1000, 19);   // 190 €
        $this->achatEtranger('DE', 500, 19);    // 95 €
        $this->achatEtranger('FR', 1000, 20);   // 200 €

        $etranger = $this->service->getSummary(now()->year)['foreign_vat'];

        $this->assertCount(2, $etranger['par_pays']);

        // Trié par montant décroissant : c'est le pays qui pèse le plus qu'on
        // veut voir en premier.
        $this->assertSame('DE', $etranger['par_pays'][0]['code']);
        $this->assertEquals(285, $etranger['par_pays'][0]['tva']);
        $this->assertSame(2, $etranger['par_pays'][0]['achats']);
        $this->assertEquals(200, $etranger['par_pays'][1]['tva']);
        $this->assertEquals(485, $etranger['total']);
    }

    /**
     * Le seuil ne change pas le montant, il change la décision : en dessous de
     * 50 € sur une année, l'État de remboursement n'examine pas la demande.
     */
    public function test_an_amount_below_the_threshold_is_flagged_as_not_worth_claiming(): void
    {
        $this->achatEtranger('BE', 100, 21);    // 21 € — sous le seuil
        $this->achatEtranger('DE', 1000, 19);   // 190 € — au-dessus

        $etranger = $this->service->getSummary(now()->year)['foreign_vat'];

        $parPays = collect($etranger['par_pays'])->keyBy('code');

        $this->assertTrue($parPays['DE']['recuperable']);
        $this->assertFalse($parPays['BE']['recuperable'], 'Sous 50 €, la demande ne serait pas examinée.');

        // Le total récupérable ne retient que ce qui l'est réellement.
        $this->assertEquals(190, $etranger['total_recuperable']);
        $this->assertEquals(211, $etranger['total'], 'Le montant payé reste affiché en entier.');
    }

    /**
     * Une créance qu'on oublie de réclamer est une créance perdue : la date
     * limite fait partie de l'information.
     */
    public function test_the_deadline_is_the_september_after_the_year(): void
    {
        $etranger = $this->service->getSummary(2026)['foreign_vat'];

        $this->assertSame('30/09/2027', $etranger['date_limite']);
    }

    /**
     * Seule la TVA étrangère est concernée. Une TVA luxembourgeoise se déduit
     * normalement, une acquisition autoliquidée n'a rien à réclamer.
     */
    public function test_only_foreign_vat_appears_in_the_claim(): void
    {
        $this->recordExpense(1000);            // TVA luxembourgeoise déductible
        $this->achatEtranger('DE', 1000, 19);

        Expense::create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur BE',
            'supplier_country' => 'BE',
            'category' => 'software',
            'amount_ht' => 2000,
            'vat_rate' => 0,
            'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
        ]);

        $etranger = $this->service->getSummary(now()->year)['foreign_vat'];

        $this->assertCount(1, $etranger['par_pays']);
        $this->assertSame('DE', $etranger['par_pays'][0]['code']);
        $this->assertEquals(190, $etranger['total']);
    }

    public function test_a_year_without_foreign_purchases_shows_nothing(): void
    {
        $this->recordExpense(1000);

        $etranger = $this->service->getSummary(now()->year)['foreign_vat'];

        $this->assertSame([], $etranger['par_pays']);
        $this->assertEquals(0, $etranger['total']);
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
