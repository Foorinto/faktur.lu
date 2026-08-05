<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\BusinessSettings;
use App\Models\Expense;
use App\Models\PurchaseCategory;
use App\Models\User;
use App\Services\Accounting\AccountingExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Export comptable des achats (FEAT-107).
 *
 * Une écriture comptable ne vaut que si elle est équilibrée : la somme des
 * débits doit égaler celle des crédits, au centime. C'est ce que vérifie
 * l'essentiel de ces tests, avant même de regarder les comptes utilisés.
 */
class ExpenseAccountingExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function service(): AccountingExportService
    {
        return app(AccountingExportService::class);
    }

    private function settings(): AccountingSetting
    {
        return AccountingSetting::getForUser($this->user);
    }

    private function expense(array $attributes = []): Expense
    {
        return Expense::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'date' => '2026-03-10',
            'provider_name' => 'Fournisseur SARL',
            'category' => Expense::CATEGORY_OFFICE,
            'amount_ht' => 100,
            'vat_rate' => 17,
            'is_deductible' => true,
        ], $attributes));
    }

    /** @param  array<int, array<string, mixed>>  $entries */
    private function assertBalanced(array $entries): void
    {
        $debit = round(array_sum(array_column($entries, 'debit')), 2);
        $credit = round(array_sum(array_column($entries, 'credit')), 2);

        $this->assertSame($debit, $credit, "Écriture déséquilibrée : {$debit} au débit contre {$credit} au crédit.");
    }

    public function test_une_depense_produit_trois_lignes_equilibrees(): void
    {
        $expense = $this->expense();

        $entries = $this->service()->buildExpenseEntries(collect([$expense]), $this->settings());

        $this->assertCount(3, $entries);
        $this->assertBalanced($entries);

        // Charge au débit pour le HT, fournisseur au crédit pour le TTC.
        $this->assertEquals(100.0, $entries[0]['debit']);
        $this->assertEquals(17.0, $entries[1]['debit']);
        $this->assertEquals(117.0, $entries[2]['credit']);
        $this->assertSame('44111', $entries[2]['account']);
    }

    public function test_le_compte_de_charge_vient_de_la_categorie(): void
    {
        PurchaseCategory::ensureDefaultsFor($this->user);
        PurchaseCategory::where('key', Expense::CATEGORY_OFFICE)->update(['pcn_account' => '61112']);

        $entries = $this->service()->buildExpenseEntries(collect([$this->expense()]), $this->settings());

        $this->assertSame('61112', $entries[0]['account']);
    }

    public function test_sans_compte_sur_la_categorie_on_retombe_sur_le_generique(): void
    {
        PurchaseCategory::ensureDefaultsFor($this->user);

        $entries = $this->service()->buildExpenseEntries(collect([$this->expense()]), $this->settings());

        // 6188 « Autres charges externes diverses » : la dépense part malgré
        // tout, la fiduciaire ventilera.
        $this->assertSame('6188', $entries[0]['account']);
    }

    public function test_une_tva_non_deductible_grossit_la_charge(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense(['is_deductible' => false])]),
            $this->settings()
        );

        // Deux lignes seulement : aucune TVA récupérable à isoler.
        $this->assertCount(2, $entries);
        $this->assertBalanced($entries);
        $this->assertEquals(117.0, $entries[0]['debit'], 'La charge doit absorber le TTC.');
        $this->assertEquals(117.0, $entries[1]['credit']);
    }

    public function test_une_tva_etrangere_part_sur_son_propre_compte(): void
    {
        // 19 % : taux allemand, absent de la grille luxembourgeoise.
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense(['vat_rate' => 19])]),
            $this->settings()
        );

        $this->assertBalanced($entries);
        $this->assertSame('421811', $entries[1]['account'], 'La TVA étrangère ne se déduit pas ici.');
        $this->assertStringContainsString('étrangère', $entries[1]['label']);
    }

    public function test_une_tva_luxembourgeoise_part_sur_la_tva_en_amont(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense(['vat_rate' => 8])]),
            $this->settings()
        );

        $this->assertSame('421611', $entries[1]['account']);
    }

    public function test_le_journal_des_achats_est_distinct_de_celui_des_ventes(): void
    {
        $entries = $this->service()->buildExpenseEntries(collect([$this->expense()]), $this->settings());

        foreach ($entries as $entry) {
            $this->assertSame('AC', $entry['journal']);
        }

        $this->assertSame('VE', $this->settings()->sales_journal);
    }

    public function test_le_perimetre_par_defaut_reste_les_ventes_seules(): void
    {
        $this->expense();

        // Un export enregistré avant cette évolution doit produire exactement le
        // même fichier qu'auparavant : aucune écriture d'achat sans le demander.
        $preview = $this->service()->getPreview(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31')
        );

        $this->assertSame(0, $preview['expenses_count']);
    }

    public function test_le_perimetre_achats_remonte_les_depenses(): void
    {
        $this->expense();
        $this->expense(['amount_ht' => 50]);

        $preview = $this->service()->getPreview(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            ['scope' => 'purchases']
        );

        $this->assertSame(2, $preview['expenses_count']);
        $this->assertEquals(150.0, $preview['expenses_total_ht']);
    }

    public function test_les_depenses_hors_periode_sont_ecartees(): void
    {
        $this->expense(['date' => '2026-02-28']);
        $this->expense(['date' => '2026-04-01']);
        $this->expense(['date' => '2026-03-15']);

        $preview = $this->service()->getPreview(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            ['scope' => 'purchases']
        );

        $this->assertSame(1, $preview['expenses_count']);
    }

    public function test_les_depenses_d_un_autre_compte_ne_sortent_jamais(): void
    {
        $autre = User::factory()->create();
        Expense::factory()->create([
            'user_id' => $autre->id,
            'date' => '2026-03-10',
            'amount_ht' => 9999,
        ]);
        $this->expense();

        $preview = $this->service()->getPreview(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            ['scope' => 'purchases']
        );

        $this->assertSame(1, $preview['expenses_count']);
        $this->assertEquals(100.0, $preview['expenses_total_ht']);
    }

    public function test_le_format_fec_accepte_les_ecritures_d_achat(): void
    {
        $this->expense();

        $content = $this->service()->generateContent(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            \App\Models\AccountingExport::FORMAT_FEC,
            ['scope' => 'purchases']
        );

        $this->assertStringContainsString('44111', $content);
        $this->assertStringContainsString('AC', $content);
    }

    public function test_le_csv_generique_ajoute_un_tableau_de_depenses(): void
    {
        $this->expense(['provider_name' => 'Bureau Vallée']);

        $content = $this->service()->generateContent(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            \App\Models\AccountingExport::FORMAT_GENERIC,
            ['scope' => 'both']
        );

        $this->assertStringContainsString('Fournisseur', $content);
        $this->assertStringContainsString('Bureau Vallée', $content);
        $this->assertStringContainsString('TVA déductible', $content);
    }
}
