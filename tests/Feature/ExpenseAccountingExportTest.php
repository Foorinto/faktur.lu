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

        // Les exports comptables sont réservés à Essentiel et Pro : un compte en
        // période d'essai obtient les fonctionnalités Pro.
        $this->seed(\Database\Seeders\PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
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

    /**
     * Le régime explicite rend la dépense non déductible — c'est voulu, la TVA
     * allemande ne va pas dans la déclaration luxembourgeoise. Elle reste due
     * par le fisc allemand : son compte de créance doit continuer d'être servi,
     * sans quoi marquer correctement sa dépense la ferait moins bien traiter
     * que de ne rien marquer du tout.
     */
    public function test_le_regime_etranger_prime_sur_la_deduction_par_le_taux(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense([
                'supplier_country' => 'DE',
                'vat_rate' => 19,
                'vat_regime' => Expense::REGIME_FOREIGN_VAT,
            ])]),
            $this->settings()
        );

        $this->assertCount(3, $entries, 'La TVA étrangère garde son écriture propre.');
        $this->assertBalanced($entries);
        $this->assertSame('421811', $entries[1]['account']);
        $this->assertEquals(100.0, $entries[0]['debit'], 'La charge ne doit pas absorber la TVA étrangère.');
    }

    /**
     * Un taux luxembourgeois sur une dépense que l'utilisateur a lui-même
     * déclarée non déductible : la TVA est perdue, elle grossit la charge.
     * C'est le seul cas où les deux notions divergent.
     */
    public function test_une_tva_nationale_non_deductible_reste_dans_la_charge(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense([
                'vat_rate' => 17,
                'vat_regime' => Expense::REGIME_NATIONAL,
                'is_deductible' => false,
            ])]),
            $this->settings()
        );

        $this->assertCount(2, $entries);
        $this->assertEquals(117.0, $entries[0]['debit']);
    }

    /**
     * Achat intracommunautaire de 1 000 € : le fournisseur ne facture aucune
     * taxe, mais l'acheteur doit inscrire la TVA des deux côtés. Quatre lignes,
     * dont deux qui s'annulent — et le fournisseur n'est crédité que du HT.
     */
    public function test_une_autoliquidation_produit_quatre_lignes_equilibrees(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense([
                'supplier_country' => 'BE',
                'amount_ht' => 1000,
                'vat_rate' => 0,
                'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
            ])]),
            $this->settings()
        );

        $this->assertCount(4, $entries);
        $this->assertBalanced($entries);

        $debits = array_column($entries, 'debit');
        $credits = array_column($entries, 'credit');

        $this->assertContains(1000.0, $debits, 'La charge reste au hors taxe.');
        $this->assertContains(170.0, $debits, 'TVA déductible sur acquisition.');
        $this->assertContains(170.0, $credits, 'TVA due sur acquisition.');
        $this->assertContains(1000.0, $credits, 'Le fournisseur n\'est dû que du hors taxe.');

        $fournisseur = collect($entries)->firstWhere('account', '44111');
        $this->assertEquals(1000.0, $fournisseur['credit'], 'Aucune taxe ne transite par le compte fournisseur.');
    }

    /**
     * Sans droit à déduction, la ligne de TVA déductible disparaît : la taxe
     * due est définitivement supportée et grossit la charge.
     */
    public function test_une_autoliquidation_non_deductible_grossit_la_charge(): void
    {
        $entries = $this->service()->buildExpenseEntries(
            collect([$this->expense([
                'supplier_country' => 'BE',
                'amount_ht' => 1000,
                'vat_rate' => 0,
                'vat_regime' => Expense::REGIME_REVERSE_CHARGE,
                'is_deductible' => false,
            ])]),
            $this->settings()
        );

        $this->assertCount(3, $entries);
        $this->assertBalanced($entries);
        $this->assertEquals(1170.0, $entries[0]['debit'], 'La charge absorbe la TVA non récupérable.');

        $fournisseur = collect($entries)->firstWhere('account', '44111');
        $this->assertEquals(1000.0, $fournisseur['credit']);
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

    /** Contenu FEC des achats de mars 2026, découpé en lignes de colonnes. */
    private function fecRows(): array
    {
        $content = $this->service()->generateContent(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            \App\Models\AccountingExport::FORMAT_FEC,
            ['scope' => 'purchases']
        );

        $lines = explode("\r\n", $content);
        array_shift($lines);

        return array_map(fn ($line) => explode("\t", $line), array_filter($lines));
    }

    public function test_le_journal_des_achats_ne_s_intitule_pas_ventes(): void
    {
        $this->expense();

        // Le libellé était écrit en dur quand seules des ventes sortaient.
        foreach ($this->fecRows() as $row) {
            $this->assertSame('AC', $row[0]);
            $this->assertSame('Achats', $row[1], 'JournalLib doit suivre le code du journal.');
        }
    }

    public function test_les_deux_colonnes_auxiliaires_se_servent_ensemble(): void
    {
        $this->expense(['provider_name' => 'Bureau Vallée']);

        // La norme rejette un libellé auxiliaire sans numéro de compte
        // auxiliaire. Faute de fichier fournisseurs, les deux restent vides.
        foreach ($this->fecRows() as $row) {
            $this->assertSame(
                $row[6] === '',
                $row[7] === '',
                'CompAuxNum et CompAuxLib doivent être servis tous les deux ou aucun.'
            );
        }
    }

    public function test_le_fournisseur_reste_lisible_dans_le_libelle_d_ecriture(): void
    {
        $this->expense(['provider_name' => 'Bureau Vallée']);

        // Vider les colonnes auxiliaires ne doit pas perdre le fournisseur.
        $libelles = array_column($this->fecRows(), 10);

        $this->assertNotEmpty(array_filter($libelles, fn ($l) => str_contains($l, 'Bureau Vallée')));
    }

    public function test_le_compte_porte_son_intitule_officiel_du_pcn(): void
    {
        PurchaseCategory::ensureDefaultsFor($this->user);
        PurchaseCategory::where('key', Expense::CATEGORY_OFFICE)->update([
            'label' => 'Mes logiciels à moi',
            'pcn_account' => '6413',
        ]);

        $entries = $this->service()->buildExpenseEntries(collect([$this->expense()]), $this->settings());

        // Le libellé de compte vient du plan comptable, pas du nom que
        // l'utilisateur a donné à sa catégorie.
        $this->assertSame('Licences informatiques', $entries[0]['account_label']);
    }

    public function test_sans_compte_pcn_le_libelle_retombe_sur_la_categorie(): void
    {
        PurchaseCategory::ensureDefaultsFor($this->user);
        PurchaseCategory::where('key', Expense::CATEGORY_OFFICE)->update(['label' => 'Fournitures']);

        $entries = $this->service()->buildExpenseEntries(collect([$this->expense()]), $this->settings());

        // 6188 appartient au PCN : son intitulé officiel prime malgré tout.
        $this->assertSame('Autres charges externes diverses', $entries[0]['account_label']);
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

    // --- Chaîne HTTP -----------------------------------------------------------

    public function test_l_ecran_d_export_accepte_le_perimetre(): void
    {
        $this->expense();

        $this->getJson(route('exports.accounting.preview', [
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'scope' => 'purchases',
        ]))->assertSuccessful()->assertJsonPath('expenses_count', 1);
    }

    public function test_un_perimetre_inconnu_est_refuse(): void
    {
        $this->getJson(route('exports.accounting.preview', [
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'scope' => 'nimporte_quoi',
        ]))->assertStatus(422);
    }

    public function test_le_perimetre_est_conserve_dans_l_export_enregistre(): void
    {
        $this->expense();

        $this->post(route('exports.accounting.store'), [
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'format' => 'fec',
            'scope' => 'both',
        ])->assertSessionHasNoErrors();

        $export = \App\Models\AccountingExport::where('user_id', $this->user->id)->latest()->firstOrFail();

        $this->assertSame('both', $export->options['scope']);
    }

    public function test_les_comptes_d_achat_sont_parametrables(): void
    {
        $this->putJson(route('settings.accounting.update'), [
            'sales_account' => '702000',
            'vat_collected_accounts' => ['17' => '461100'],
            'clients_account' => '411000',
            'bank_account' => '512000',
            'sales_journal' => 'VE',
            'client_prefix' => 'C',
            'suppliers_account' => '44121',
            'default_expense_account' => '61888',
        ])->assertSuccessful();

        $settings = AccountingSetting::getForUser($this->user)->fresh();

        $this->assertSame('44121', $settings->suppliers_account);
        $this->assertSame('61888', $settings->default_expense_account);
        // Non transmis : la valeur en place est conservée, pas vidée.
        $this->assertSame('421611', $settings->vat_deductible_account);
    }
}
