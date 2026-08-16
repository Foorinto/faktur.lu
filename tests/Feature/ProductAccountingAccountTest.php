<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\Accounting\AccountingExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Compte de produits par article.
 *
 * Le cas qui a motivé la fonctionnalité : un utilisateur refacture des frais de
 * déplacement. Tout partait sur le compte de ventes générique, et ces frais
 * gonflaient donc son chiffre d'affaires d'un montant qui n'en est pas un — le
 * 708 se confondait avec le 706.
 */
class ProductAccountingAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        BusinessSettings::factory()->create();
    }

    private function factureAvecLignes(array $lignes): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'number' => 'F-2026-001',
            'issued_at' => '2026-03-10',
        ]);

        foreach ($lignes as $i => [$titre, $prix, $compte]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => $titre,
                'quantity' => 1,
                'unit_price' => $prix,
                'vat_rate' => 17,
                'pcn_account' => $compte,
                'sort_order' => $i,
            ]);
        }

        return $invoice->fresh(['items', 'client']);
    }

    private function ecritures(Invoice $invoice): array
    {
        return app(AccountingExportService::class)->buildEntries(
            collect([$invoice]),
            AccountingSetting::getForUser($this->user)
        );
    }

    /**
     * Le cas du client : une prestation et des frais refacturés sur la même
     * facture doivent partir sur deux comptes distincts.
     */
    public function test_rebilled_expenses_land_on_their_own_account(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation', 1000, null],       // compte générique
            ['Frais de déplacement', 200, '708'],
        ]);

        $ventes = collect($this->ecritures($facture))
            ->filter(fn ($e) => $e['credit'] > 0 && $e['account'] !== '411000')
            ->keyBy('account');

        $this->assertArrayHasKey('708', $ventes->all(), 'Les frais doivent porter leur propre compte.');
        $this->assertEquals(200, $ventes['708']['credit']);

        $this->assertArrayHasKey('702000', $ventes->all(), 'La prestation retombe sur le compte du paramétrage.');
        $this->assertEquals(1000, $ventes['702000']['credit']);
    }

    /**
     * Une écriture par compte, pas une par article : deux lignes partageant un
     * compte se regroupent, sinon un devis de vingt articles produirait vingt
     * écritures illisibles.
     */
    public function test_lines_sharing_an_account_are_grouped(): void
    {
        $facture = $this->factureAvecLignes([
            ['Frais de train', 100, '708'],
            ['Frais d\'hôtel', 150, '708'],
            ['Prestation', 500, null],
        ]);

        $ventes = collect($this->ecritures($facture))->where('account', '708');

        $this->assertCount(1, $ventes, 'Les lignes du même compte forment une seule écriture.');
        $this->assertEquals(250, $ventes->first()['credit']);
    }

    /**
     * La règle qui portait déjà les exports : chaque écriture doit s'équilibrer.
     */
    public function test_the_entry_still_balances(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation', 1000, null],
            ['Frais de déplacement', 200, '708'],
        ]);

        $ecritures = $this->ecritures($facture);

        $debit = round(array_sum(array_column($ecritures, 'debit')), 2);
        $credit = round(array_sum(array_column($ecritures, 'credit')), 2);

        $this->assertSame($debit, $credit, "Écriture déséquilibrée : {$debit} au débit contre {$credit} au crédit.");
    }

    /**
     * Les factures antérieures n'ont aucun compte sur leurs lignes : elles
     * doivent continuer de sortir exactement comme avant.
     */
    public function test_older_invoices_are_unaffected(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation A', 600, null],
            ['Prestation B', 400, null],
        ]);

        $ventes = collect($this->ecritures($facture))->where('account', '702000');

        $this->assertCount(1, $ventes);
        $this->assertEquals(1000, $ventes->first()['credit']);
    }

    private function creerArticle(?string $compte): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('products.store'), [
            'designation' => 'Frais de déplacement',
            'type' => 'service',
            'unit_price_ht' => 100,
            'vat_rate' => 17,
            'pcn_account' => $compte,
        ]);
    }

    /**
     * Une coquille est refusée quelle que soit la classe : un compte est une
     * suite de chiffres, pas un mot.
     */
    public function test_a_malformed_account_is_refused(): void
    {
        $this->creerArticle('70A')->assertSessionHasErrors('pcn_account');
        $this->creerArticle('7')->assertSessionHasErrors('pcn_account');
    }

    /**
     * En classe 6, le catalogue existe : un compte absent est donc refusé.
     */
    public function test_an_unknown_expense_account_is_refused(): void
    {
        $this->creerArticle('699999')->assertSessionHasErrors('pcn_account');
    }

    /**
     * En classe 7, il n'existe pas — le catalogue ne couvre que les charges.
     * Opposer son absence reviendrait à interdire la fonctionnalité : on s'en
     * tient à la forme, en attendant `pcn:build --class=7`.
     */
    public function test_a_revenue_account_is_accepted_on_its_form_alone(): void
    {
        $this->creerArticle('708')->assertSessionHasNoErrors();
    }

    public function test_a_real_account_is_accepted_on_a_product(): void
    {
        $this->post(route('products.store'), [
            'designation' => 'Frais de déplacement',
            'type' => 'service',
            'unit_price_ht' => 100,
            'vat_rate' => 17,
            'pcn_account' => '708',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'designation' => 'Frais de déplacement',
            'pcn_account' => '708',
        ]);
    }
}
