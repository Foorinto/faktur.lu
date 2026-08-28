<?php

namespace Tests\Feature;

use App\Models\AccountingExport;
use App\Models\AccountingSetting;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Accounting\AccountingExportService;
use App\Services\Accounting\XlsxFormatter;
use Carbon\Carbon;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Export comptable au format classeur, trois onglets (FEAT-114).
 *
 * Le CSV met les trois tableaux sur une feuille unique, séparés par des lignes
 * vides : les colonnes ne s'alignent pas d'un tableau à l'autre, et un tableur
 * applique aux suivants la largeur du premier.
 *
 * Le CSV reste le format d'import ; celui-ci est le format de lecture.
 */
class AccountingExportXlsxTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Les exports comptables sont réservés à Essentiel et Pro ; un compte en
        // période d'essai obtient les fonctionnalités Pro. Les plans doivent
        // exister avant le compte, sinon il retombe sur l'offre gratuite.
        $this->seed(PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function produire(?Collection $expenses = null): string
    {
        $reglages = AccountingSetting::firstOrCreate(['user_id' => $this->user->id]);

        $invoices = Invoice::with(['client', 'items', 'payments'])
            ->where('user_id', $this->user->id)
            ->get();

        return (new XlsxFormatter)->format($invoices, $reglages, $expenses);
    }

    private function lire(string $binaire): Spreadsheet
    {
        $chemin = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
        file_put_contents($chemin, $binaire);

        try {
            return IOFactory::load($chemin);
        } finally {
            @unlink($chemin);
        }
    }

    private function facture(float $ttc): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Dupont']);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'number' => 'FAC-2026-001',
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subMonth(),
            'issued_at' => '2026-03-15',
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_the_workbook_has_a_sheet_per_table(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 400, 'paid_at' => '2026-03-10', 'method' => 'cash']);

        $noms = $this->lire($this->produire())->getSheetNames();

        $this->assertContains(__('app.export_sheet_sales'), $noms);
        $this->assertContains(__('app.export_sheet_payments'), $noms);
    }

    /**
     * Sans dépense, pas d'onglet vide.
     */
    public function test_no_expenses_sheet_when_there_are_none(): void
    {
        $this->facture(500)->payments()->create([
            'amount' => 500, 'paid_at' => '2026-03-10', 'method' => 'transfer',
        ]);

        $this->assertNotContains(
            __('app.export_sheet_expenses'),
            $this->lire($this->produire())->getSheetNames()
        );
    }

    /**
     * ⚠️ Les montants doivent être des NOMBRES.
     *
     * Un « 1 234,56 » écrit en texte ne s'additionne pas, et la première chose
     * que fait une fiduciaire est de sélectionner une colonne pour en lire la
     * somme.
     */
    public function test_amounts_are_written_as_numbers(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 400.5, 'paid_at' => '2026-03-10', 'method' => 'cash']);

        $feuille = $this->lire($this->produire())->getSheetByName(__('app.export_sheet_payments'));

        $montant = $feuille->getCell('D2')->getValue();

        $this->assertIsNumeric($montant);
        $this->assertSame(400.5, (float) $montant);
    }

    public function test_the_payments_sheet_carries_the_method(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-10', 'method' => 'cash'],
            ['amount' => 700, 'paid_at' => '2026-04-05', 'method' => 'transfer', 'reference' => 'VIR-42'],
        ]);

        $feuille = $this->lire($this->produire())->getSheetByName(__('app.export_sheet_payments'));

        // Triés par date : les espèces de mars avant le virement d'avril.
        $this->assertSame(__('app.payment_methods.cash'), $feuille->getCell('E2')->getValue());
        $this->assertSame(__('app.payment_methods.transfer'), $feuille->getCell('E3')->getValue());
        $this->assertSame('VIR-42', $feuille->getCell('F3')->getValue());
    }

    /**
     * Un moyen inconnu se nomme, il ne se range pas sous « virement ».
     */
    public function test_an_unknown_method_says_so(): void
    {
        $this->facture(200)->payments()->create([
            'amount' => 200, 'paid_at' => '2026-03-12', 'method' => null,
        ]);

        $feuille = $this->lire($this->produire())->getSheetByName(__('app.export_sheet_payments'));

        $this->assertSame(__('app.payment_methods.unknown'), $feuille->getCell('E2')->getValue());
    }

    /**
     * L'en-tête est figé : une fiduciaire fait défiler des centaines de lignes.
     */
    public function test_the_header_row_is_frozen(): void
    {
        $this->facture(100)->payments()->create([
            'amount' => 100, 'paid_at' => '2026-03-10', 'method' => 'card',
        ]);

        $feuille = $this->lire($this->produire())->getSheetByName(__('app.export_sheet_sales'));

        $this->assertSame('A2', $feuille->getFreezePane());
    }

    /**
     * Bout en bout, par le service : c'est lui qui choisit le formateur, et
     * c'est là que les dépenses arrivent réellement.
     */
    public function test_the_service_produces_the_three_sheets(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 1000, 'paid_at' => '2026-03-15', 'method' => 'wero']);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-03-08',
            'provider_name' => 'Fournisseur SARL',
            'amount_ht' => 100,
            'vat_rate' => 17,
            'is_deductible' => true,
        ]);

        $binaire = app(AccountingExportService::class)->generateContent(
            $this->user,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
            AccountingExport::FORMAT_XLSX,
            ['scope' => 'both']
        );

        $classeur = $this->lire($binaire);

        // Les onglets existent même sur un export vide : ce qui prouve quelque
        // chose, c'est qu'ils portent des lignes.
        $this->assertSame('FAC-2026-001', $classeur->getSheetByName(__('app.export_sheet_sales'))->getCell('B2')->getValue());
        $this->assertSame('Fournisseur SARL', $classeur->getSheetByName(__('app.export_sheet_expenses'))->getCell('C2')->getValue());
        $this->assertSame(1000.0, (float) $classeur->getSheetByName(__('app.export_sheet_payments'))->getCell('D2')->getValue());
    }

    /**
     * Le format doit être proposé : un formateur qu'aucun écran n'offre
     * n'existe pas pour l'utilisateur.
     */
    public function test_the_format_is_offered_in_the_export_screen(): void
    {
        $this->assertArrayHasKey(
            AccountingExport::FORMAT_XLSX,
            AccountingExport::FORMATS
        );
    }

    /**
     * Le tour complet, par les routes : l'écran enregistre l'export, le disque
     * le garde, le téléchargement le rend.
     *
     * Un classeur est un fichier BINAIRE : le moindre passage par un encodage
     * de texte le rendrait illisible, et rien d'autre ne l'attraperait.
     */
    public function test_the_downloaded_file_is_a_readable_workbook(): void
    {
        $this->facture(1000)->payments()->create([
            'amount' => 1000, 'paid_at' => '2026-03-15', 'method' => 'payconiq',
        ]);

        $this->post(route('exports.accounting.store'), [
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'format' => AccountingExport::FORMAT_XLSX,
            'scope' => 'sales',
        ])->assertSessionHasNoErrors();

        $export = AccountingExport::where('user_id', $this->user->id)->latest()->firstOrFail();

        $this->assertTrue($export->isCompleted(), $export->error_message ?? '');
        $this->assertStringEndsWith('.xlsx', $export->file_name);

        $reponse = $this->get(route('exports.accounting.download', $export));
        $reponse->assertSuccessful();

        $classeur = $this->lire($reponse->streamedContent());

        $this->assertContains(__('app.export_sheet_payments'), $classeur->getSheetNames());
        $this->assertSame(
            __('app.payment_methods.payconiq'),
            $classeur->getSheetByName(__('app.export_sheet_payments'))->getCell('E2')->getValue()
        );
    }
}
