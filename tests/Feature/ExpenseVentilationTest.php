<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\BusinessSettings;
use App\Models\Expense;
use App\Models\PurchaseCategory;
use App\Models\User;
use App\Services\Accounting\AccountingExportService;
use App\Services\FiscalSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ventilation d'une dépense sur plusieurs catégories (FEAT-115).
 *
 * La vraie cible est l'export comptable : une dépense répartie entre l'achat de
 * marchandises et une prestation doit produire une écriture de charge par
 * compte, sans jamais déséquilibrer débit et crédit. Et une dépense à une seule
 * ligne — le cas de l'immense majorité — doit rester rigoureusement identique à
 * ce qu'elle produisait avant la ventilation.
 */
class ExpenseVentilationTest extends TestCase
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

        PurchaseCategory::ensureDefaultsFor($this->user);
        PurchaseCategory::where('key', Expense::CATEGORY_OFFICE)->update(['pcn_account' => '61112']);
        PurchaseCategory::where('key', Expense::CATEGORY_HARDWARE)->update(['pcn_account' => '21830']);
    }

    private function service(): AccountingExportService
    {
        return app(AccountingExportService::class);
    }

    private function settings(): AccountingSetting
    {
        return AccountingSetting::getForUser($this->user);
    }

    /**
     * @param  array<int, array{category: string, amount_ht: float, vat_rate?: float}>  $lineSpecs
     */
    private function ventilated(array $lineSpecs, array $attrs = []): Expense
    {
        $expense = Expense::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'is_deductible' => true,
        ], $attrs));

        $expense->lines()->delete();

        foreach (array_values($lineSpecs) as $i => $spec) {
            $expense->lines()->create([
                'user_id' => $this->user->id,
                'category' => $spec['category'],
                'amount_ht' => $spec['amount_ht'],
                'vat_rate' => $spec['vat_rate'] ?? 17,
                'sort_order' => $i,
            ]);
        }

        $expense->load('lines');
        $expense->save();

        return $expense->fresh('lines');
    }

    /** @param  array<int, array<string, mixed>>  $entries */
    private function assertBalanced(array $entries): void
    {
        $debit = round(array_sum(array_column($entries, 'debit')), 2);
        $credit = round(array_sum(array_column($entries, 'credit')), 2);

        $this->assertSame($debit, $credit, "Écriture déséquilibrée : {$debit} au débit contre {$credit} au crédit.");
    }

    public function test_une_depense_ventilee_produit_une_charge_par_compte(): void
    {
        $expense = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 80],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 20],
        ]);

        $entries = $this->service()->buildExpenseEntries(collect([$expense]), $this->settings());

        // Deux charges (une par ligne), une TVA déductible, un crédit fournisseur.
        $this->assertCount(4, $entries);
        $this->assertBalanced($entries);

        $charges = array_values(array_filter($entries, fn ($e) => $e['debit'] > 0 && in_array($e['account'], ['61112', '21830'], true)));

        $office = collect($charges)->firstWhere('account', '61112');
        $hardware = collect($charges)->firstWhere('account', '21830');

        $this->assertEquals(80.0, $office['debit'], 'La charge Bureau part sur son compte.');
        $this->assertEquals(20.0, $hardware['debit'], 'La charge Matériel part sur le sien.');

        // TVA récupérable unique pour la dépense (13,60 + 3,40 = 17,00).
        $vat = collect($entries)->firstWhere('account', $this->settings()->vat_deductible_account);
        $this->assertEquals(17.0, $vat['debit']);

        // Crédit fournisseur unique, au TTC.
        $supplier = collect($entries)->firstWhere('account', $this->settings()->suppliers_account);
        $this->assertEquals(117.0, $supplier['credit']);
    }

    public function test_une_seule_ligne_produit_exactement_les_memes_ecritures_qu_avant(): void
    {
        // Deux dépenses identiques : l'une passe par une ligne de ventilation
        // (le chemin de production), l'autre est privée de ligne (le repli, qui
        // reproduit l'écriture historique). Les écritures doivent coïncider.
        $avecLigne = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 100],
        ]);

        $sansLigne = Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_ht' => 100,
            'vat_rate' => 17,
            'is_deductible' => true,
        ]);
        $sansLigne->lines()->delete();
        $sansLigne->refresh();

        $normalize = function (array $entries) {
            return array_map(fn ($e) => [
                'account' => $e['account'],
                'debit' => $e['debit'],
                'credit' => $e['credit'],
            ], $entries);
        };

        $depuisLigne = $normalize($this->service()->buildExpenseEntries(collect([$avecLigne]), $this->settings()));
        $depuisRepli = $normalize($this->service()->buildExpenseEntries(collect([$sansLigne]), $this->settings()));

        $this->assertSame($depuisRepli, $depuisLigne, 'Une dépense à une ligne ne doit rien changer aux écritures.');
    }

    public function test_l_arrondi_de_ventilation_ne_desequilibre_jamais(): void
    {
        // Des montants qui ne tombent pas juste : 33,33 + 33,33 + 33,34 = 100,00.
        // La somme des charges doit rester exactement égale au crédit.
        $expense = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 33.33],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 33.33],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 33.34],
        ]);

        $entries = $this->service()->buildExpenseEntries(collect([$expense]), $this->settings());

        $this->assertBalanced($entries);
    }

    public function test_une_tva_non_deductible_ventilee_reste_equilibree(): void
    {
        $expense = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 60],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 40],
        ], ['is_deductible' => false]);

        $entries = $this->service()->buildExpenseEntries(collect([$expense]), $this->settings());

        // Aucune TVA récupérable à isoler : deux charges (TTC) et le crédit.
        $this->assertCount(3, $entries);
        $this->assertBalanced($entries);

        // Chaque charge absorbe sa propre TVA.
        $office = collect($entries)->firstWhere('account', '61112');
        $hardware = collect($entries)->firstWhere('account', '21830');
        $this->assertEquals(70.2, $office['debit'], '60 + 17% de TVA non récupérable.');
        $this->assertEquals(46.8, $hardware['debit'], '40 + 17% de TVA non récupérable.');
    }

    public function test_le_recapitulatif_fiscal_ventile_par_ligne(): void
    {
        $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 80],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 20],
        ]);

        $byCategory = app(FiscalSummaryService::class)
            ->getSummary(2026)['expenses']['by_category'];

        // La dépense unique pèse sur ses DEUX catégories, pas seulement la
        // majoritaire.
        $this->assertEquals(80.0, $byCategory[Expense::CATEGORY_OFFICE]['total_ht']);
        $this->assertEquals(20.0, $byCategory[Expense::CATEGORY_HARDWARE]['total_ht']);

        // Et chaque catégorie porte son compte comptable (lot 0).
        $this->assertSame('61112', $byCategory[Expense::CATEGORY_OFFICE]['pcn_account']);
        $this->assertSame('21830', $byCategory[Expense::CATEGORY_HARDWARE]['pcn_account']);
    }

    public function test_le_formulaire_enregistre_les_lignes_et_recalcule_la_depense(): void
    {
        $this->post(route('expenses.store'), [
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'category' => Expense::CATEGORY_HARDWARE,
            'amount_input_mode' => 'ht',
            'amount_ht' => 100,
            'vat_rate' => 17,
            'vat_regime' => 'national',
            'is_deductible' => true,
            'lines' => [
                ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 30, 'vat_rate' => 17],
                ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 70, 'vat_rate' => 17],
            ],
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::latest('id')->first();

        $this->assertCount(2, $expense->lines);
        $this->assertEquals('100.0000', $expense->amount_ht);
        // Catégorie = ligne majoritaire (Matériel, 70).
        $this->assertSame(Expense::CATEGORY_HARDWARE, $expense->category);
    }

    public function test_sans_ligne_le_formulaire_cree_une_ligne_unique(): void
    {
        // Saisie mono-catégorie : le contrôleur dérive une ligne unique, pour
        // qu'aucune dépense ne reste sans ventilation.
        $this->post(route('expenses.store'), [
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_input_mode' => 'ht',
            'amount_ht' => 250,
            'vat_rate' => 17,
            'vat_regime' => 'national',
            'is_deductible' => true,
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::latest('id')->first();

        $this->assertCount(1, $expense->lines);
        $this->assertSame(Expense::CATEGORY_OFFICE, $expense->lines->first()->category);
        $this->assertEquals('250.0000', $expense->lines->first()->amount_ht);
    }

    public function test_la_modification_remplace_la_ventilation(): void
    {
        $expense = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 80],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 20],
        ]);

        $this->put(route('expenses.update', $expense->id), [
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_input_mode' => 'ht',
            'amount_ht' => 50,
            'vat_rate' => 17,
            'vat_regime' => 'national',
            'is_deductible' => true,
            'lines' => [
                ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 50, 'vat_rate' => 17],
            ],
        ])->assertRedirect(route('expenses.index'));

        $expense->refresh()->load('lines');

        $this->assertCount(1, $expense->lines, 'Les anciennes lignes doivent être remplacées.');
        $this->assertEquals('50.0000', $expense->amount_ht);
    }

    public function test_les_agregats_de_la_depense_derivent_des_lignes(): void
    {
        $expense = $this->ventilated([
            ['category' => Expense::CATEGORY_OFFICE, 'amount_ht' => 30],
            ['category' => Expense::CATEGORY_HARDWARE, 'amount_ht' => 70],
        ]);

        // Montants agrégés = somme des lignes.
        $this->assertEquals('100.0000', $expense->amount_ht);
        $this->assertEquals('117.0000', $expense->amount_ttc);

        // Catégorie = celle de la ligne au HT le plus élevé (Matériel, 70).
        $this->assertSame(Expense::CATEGORY_HARDWARE, $expense->category);
    }
}
