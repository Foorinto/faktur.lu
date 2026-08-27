<?php

namespace Tests\Feature;

use App\Models\AccountingSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Accounting\GenericCsvFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'export comptable porte les encaissements (FEAT-114).
 *
 * Ils forment un tableau à part, et non une colonne du journal des ventes.
 * Une facture réglée moitié espèces moitié virement porterait sinon deux moyens
 * sur une même ligne — ou un seul, faux. Et la date d'encaissement n'est pas
 * celle d'émission, qui est celle du journal des ventes.
 *
 * C'est ce tableau qui permet à la fiduciaire de rapprocher banque et caisse.
 */
class AccountingExportCarriesPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function exporter(Invoice ...$invoices): string
    {
        $reglages = AccountingSetting::firstOrCreate([
            'user_id' => $invoices[0]->user_id,
        ]);

        // Rechargées par requête : `load()` n'existe que sur une collection
        // Eloquent, et le formateur attend les relations en place.
        $collection = Invoice::with(['client', 'items', 'payments'])
            ->whereIn('id', collect($invoices)->pluck('id'))
            ->get();

        return (new GenericCsvFormatter())->format($collection, $reglages);
    }

    private function facture(User $user, float $ttc): Invoice
    {
        $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Dupont']);

        return Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subMonth(),
            'issued_at' => '2026-03-01',
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_the_export_carries_one_line_per_payment(): void
    {
        $user = User::factory()->create();
        $facture = $this->facture($user, 1000);

        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-10', 'method' => 'cash'],
            ['amount' => 700, 'paid_at' => '2026-04-05', 'method' => 'transfer', 'reference' => 'VIR-42'],
        ]);

        $csv = $this->exporter($facture);

        $this->assertStringContainsString('Date encaissement;N° Facture;Client;Montant;Moyen de paiement;Référence', $csv);
        $this->assertStringContainsString('10/03/2026;'.$facture->number.';Dupont;300,00;Espèces;', $csv);
        $this->assertStringContainsString('05/04/2026;'.$facture->number.';Dupont;700,00;Virement bancaire;VIR-42', $csv);
    }

    /**
     * Le moyen ne contamine pas le journal des ventes.
     *
     * L'en-tête des factures doit rester celui qu'une fiduciaire attend : y
     * glisser une colonne « moyen » casserait les imports existants.
     */
    public function test_the_sales_journal_header_is_untouched(): void
    {
        $user = User::factory()->create();
        $facture = $this->facture($user, 500);
        $facture->payments()->create(['amount' => 500, 'paid_at' => '2026-03-10', 'method' => 'card']);

        $enTete = explode("\r\n", $this->exporter($facture))[0];

        $this->assertStringContainsString('Date;N° Facture;Client', $enTete);
        $this->assertStringNotContainsString('Moyen', $enTete);
    }

    /**
     * Un encaissement sans moyen le dit.
     */
    public function test_a_payment_without_a_method_says_so(): void
    {
        $user = User::factory()->create();
        $facture = $this->facture($user, 200);
        $facture->payments()->create(['amount' => 200, 'paid_at' => '2026-03-12', 'method' => null]);

        $csv = $this->exporter($facture);

        $this->assertStringContainsString(__('app.payment_methods.unknown'), $csv);
        $this->assertStringNotContainsString(
            '200,00;'.__('app.payment_methods.transfer'),
            $csv,
            "Un moyen inconnu ne doit pas être rangé sous « virement »."
        );
    }

    /**
     * Sans encaissement, aucun tableau supplémentaire.
     */
    public function test_no_section_without_payments(): void
    {
        $user = User::factory()->create();
        $facture = $this->facture($user, 300);

        $this->assertStringNotContainsString('Date encaissement', $this->exporter($facture));
    }
}
