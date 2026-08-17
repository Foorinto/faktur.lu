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

    /**
     * Une remise globale ne s'attache à aucune ligne. Répartir les ventes par
     * compte en sommant les lignes revenait donc à l'ignorer : le crédit
     * dépassait le débit du montant de la remise, et l'écriture aurait été
     * refusée à l'import chez le comptable.
     */
    public function test_a_global_discount_keeps_the_entry_balanced(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation', 1000, null],
            ['Frais de déplacement', 200, '708'],
        ]);

        \App\Models\InvoiceDiscount::create([
            'invoice_id' => $facture->id,
            'label' => 'Geste commercial',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $facture = $facture->fresh(['items', 'client', 'discounts']);
        $ecritures = $this->ecritures($facture);

        $debit = round(array_sum(array_column($ecritures, 'debit')), 2);
        $credit = round(array_sum(array_column($ecritures, 'credit')), 2);
        $this->assertSame($debit, $credit, "Écriture déséquilibrée : {$debit} au débit contre {$credit} au crédit.");

        // La remise se ventile au prorata : 10 % de moins sur chaque compte.
        $ventes = collect($ecritures)->filter(fn ($e) => $e['credit'] > 0 && $e['account'] !== '411000')->keyBy('account');
        $this->assertEquals(900, $ventes['702000']['credit']);
        $this->assertEquals(180, $ventes['708']['credit']);
    }

    /**
     * Le CSV générique s'écrit document par document, pas écriture par écriture :
     * il ne passe pas par `buildEntries()`. Sa colonne « Compte Ventes » portait
     * donc le compte du paramétrage quoi qu'il arrive — la ventilation était
     * invisible dans le seul format que l'utilisateur ouvre lui-même.
     */
    public function test_the_generic_csv_splits_the_invoice_per_account(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation', 1000, null],
            ['Frais de déplacement', 200, '708'],
        ]);

        $csv = (new \App\Services\Accounting\GenericCsvFormatter())->format(
            collect([$facture]),
            AccountingSetting::getForUser($this->user)
        );

        $lignes = collect(explode("\r\n", $csv))
            ->filter(fn ($l) => str_contains($l, 'F-2026-001'))
            ->values();

        $this->assertCount(2, $lignes, 'Deux comptes de ventes, deux lignes.');

        // `sort()` comparerait ces comptes comme des nombres — 708 avant 702000.
        $comptes = $lignes->map(fn ($l) => explode(';', $l)[8])
            ->sort(fn ($x, $y) => strcmp($x, $y))->values()->all();
        $this->assertSame(['702000', '708'], $comptes);

        // La somme des lignes doit faire le total du document, sinon le fichier
        // se contredit lui-même sous les yeux du comptable.
        $sommeHt = $lignes->sum(fn ($l) => (float) str_replace(',', '.', explode(';', $l)[4]));
        $this->assertEquals(1200.0, round($sommeHt, 2));
    }

    /**
     * Une facture antérieure — aucun compte sur ses lignes — doit sortir sur une
     * seule ligne, exactement comme avant.
     */
    public function test_the_generic_csv_is_unchanged_for_older_invoices(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation A', 600, null],
            ['Prestation B', 400, null],
        ]);

        $csv = (new \App\Services\Accounting\GenericCsvFormatter())->format(
            collect([$facture]),
            AccountingSetting::getForUser($this->user)
        );

        $lignes = collect(explode("\r\n", $csv))->filter(fn ($l) => str_contains($l, 'F-2026-001'));

        $this->assertCount(1, $lignes);
        $this->assertSame('702000', explode(';', $lignes->first())[8]);
    }

    /**
     * Le cas relevé sur un export réel : une facture à plusieurs taux n'affichait
     * que le dominant, avec le total de sa TVA en face. La ligne ne se recoupait
     * pas, et toute la TVA partait sur le compte du taux dominant.
     */
    public function test_the_generic_csv_splits_a_mixed_rate_invoice(): void
    {
        // F-2026-10000 : 5 020 € à 17 % et 50 € à 0 % donnaient une seule ligne
        // « 5 070,00 / 853,40 / 17 % », dont 17 % font 861,90.
        $facture = $this->factureAvecLignes([
            ['Prestation', 5020, null],
            ['Débours', 50, null],
        ]);
        $facture->items()->where('title', 'Débours')->update(['vat_rate' => 0]);
        app(\App\Actions\CalculateInvoiceTotalsAction::class)->execute($facture);
        $facture = $facture->fresh(['items', 'client', 'discounts']);

        $lignes = $this->csv($facture);

        $this->assertCount(2, $lignes, 'Deux taux, deux lignes.');

        foreach ($lignes as $ligne) {
            [$ht, $tva, $ttc, $taux] = [
                $this->montant($ligne, 4), $this->montant($ligne, 5),
                $this->montant($ligne, 6), (float) rtrim(explode(';', $ligne)[7], '%'),
            ];

            $this->assertEqualsWithDelta($ht * $taux / 100, $tva, 0.01,
                "La ligne à {$taux}% annonce {$tva} de TVA sur {$ht} de base.");
            $this->assertEqualsWithDelta($ht + $tva, $ttc, 0.01);
        }

        // Le document reste le document.
        $this->assertEqualsWithDelta(5070, collect($lignes)->sum(fn ($l) => $this->montant($l, 4)), 0.01);
        $this->assertEqualsWithDelta(853.40, collect($lignes)->sum(fn ($l) => $this->montant($l, 5)), 0.01);
    }

    /**
     * Les deux ventilations se composent : deux taux et deux comptes de ventes
     * font quatre lignes, et le total du document est conservé.
     */
    public function test_rate_and_account_ventilations_compose(): void
    {
        $facture = $this->factureAvecLignes([
            ['Prestation', 1000, null],
            ['Frais de déplacement', 200, '708'],
        ]);
        $facture->items()->where('title', 'Frais de déplacement')->update(['vat_rate' => 3]);
        app(\App\Actions\CalculateInvoiceTotalsAction::class)->execute($facture);
        $facture = $facture->fresh(['items', 'client', 'discounts']);

        $lignes = $this->csv($facture);
        $this->assertCount(2, $lignes);

        $parCompte = collect($lignes)->keyBy(fn ($l) => explode(';', $l)[8]);
        $this->assertEqualsWithDelta(170, $this->montant($parCompte['702000'], 5), 0.01);
        $this->assertEqualsWithDelta(6, $this->montant($parCompte['708'], 5), 0.01);

        // Chacune sur le compte de TVA de son taux : c'est là que la version
        // précédente se trompait, en versant les 176 € sur le seul 461100.
        $this->assertNotSame(
            explode(';', $parCompte['702000'])[9],
            explode(';', $parCompte['708'])[9],
            'Deux taux distincts appellent deux comptes de TVA distincts.'
        );
    }

    /** Lignes du CSV concernant la facture, en-tête et dépenses exclus. */
    private function csv(Invoice $facture): array
    {
        $csv = (new \App\Services\Accounting\GenericCsvFormatter())->format(
            collect([$facture]),
            AccountingSetting::getForUser($this->user)
        );

        return collect(explode("\r\n", $csv))
            ->filter(fn ($l) => str_contains($l, $facture->number))
            ->values()->all();
    }

    private function montant(string $ligne, int $colonne): float
    {
        return (float) str_replace(',', '.', explode(';', $ligne)[$colonne]);
    }

    /**
     * Le sélecteur doit trouver les comptes de produits — et surtout ne pas
     * proposer de comptes de charges sur un article vendu.
     */
    public function test_the_picker_searches_the_revenue_class(): void
    {
        $reponse = $this->getJson(route('settings.pcn-accounts', ['q' => '708', 'class' => '7']));

        $reponse->assertOk();
        $comptes = collect($reponse->json('accounts'));

        $this->assertTrue($comptes->contains('ref', '708'), 'Le 708 doit être proposé.');
        $this->assertTrue(
            $comptes->every(fn ($c) => str_starts_with($c['ref'], '7')),
            'Une recherche en classe 7 ne doit renvoyer que des comptes de produits.'
        );
    }

    /**
     * Et réciproquement : les dépenses cherchaient dans la classe 6 avant que
     * les articles n'aient un compte, elles doivent continuer.
     */
    public function test_the_picker_still_defaults_to_the_expense_class(): void
    {
        $comptes = collect($this->getJson(route('settings.pcn-accounts', ['q' => '61']))->json('accounts'));

        $this->assertNotEmpty($comptes);
        $this->assertTrue(
            $comptes->every(fn ($c) => str_starts_with($c['ref'], '6')),
            'Sans classe précisée, la recherche reste celle des charges.'
        );
    }

    /**
     * Les deux catalogues doivent être lisibles et distincts : une commande dont
     * le fichier de sortie était codé en dur a un jour écrasé l'un par l'autre.
     */
    public function test_both_account_catalogues_are_loaded(): void
    {
        $pcn = app(\App\Services\Accounting\PcnAccountService::class);

        $this->assertTrue($pcn->exists('61112'), 'Un compte de charges doit exister.');
        $this->assertTrue($pcn->exists('708'), 'Un compte de produits doit exister.');

        $this->assertTrue($pcn->all('6')->every(fn ($c) => str_starts_with($c['ref'], '6')));
        $this->assertTrue($pcn->all('7')->every(fn ($c) => str_starts_with($c['ref'], '7')));
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
     * En classe 7, la vérification s'arrête à la forme : le PCN normalise les
     * comptes mais une comptabilité les subdivise — 702000, notre propre compte
     * de ventes par défaut, ne figure pas au plan.
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
