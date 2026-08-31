<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acomptes au brouillon, et facture figée après émission.
 *
 * Décision de l'auteur (2026-08-31), après avoir constaté que le PDF régénéré
 * changeait d'aspect au fil des encaissements :
 *
 *     « On garde les encaissements mais on ne les affiche pas sur la facture
 *       s'ils sont faits après la finalisation. On donne la possibilité de
 *       saisir des acomptes sur le brouillon. »
 *
 * Les deux règles se tiennent : l'acompte est reçu AVANT que la facture
 * n'existe — à la signature du devis — donc il se saisit sur le brouillon. Et
 * une facture émise ne change plus d'aspect : ce qui est enregistré après
 * l'émission reste en comptabilité mais ne paraît plus sur le document.
 *
 * ⚠️ Le critère est la date de SAISIE, pas la date du versement.
 */
class DraftPaymentsTest extends TestCase
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

    private function brouillon(float $ttc = 1000): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'finalized_at' => null,
            'number' => null,
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);

        $facture->items()->create([
            'title' => 'Prestation', 'quantity' => 1, 'unit_price' => $ttc,
            'vat_rate' => 0, 'total_ht' => $ttc, 'position' => 1,
        ]);

        return $facture->fresh();
    }

    private function encaissementsDuPdf(Invoice $facture): array
    {
        return app(InvoicePdfService::class)->prepareData($facture->fresh())['encaissements'];
    }

    public function test_a_draft_accepts_a_deposit(): void
    {
        $brouillon = $this->brouillon();

        $this->post(route('invoices.payments.store', $brouillon), [
            'amount' => 300,
            'paid_at' => now()->subDays(5)->toDateString(),
            'method' => 'transfer',
        ])->assertSessionHasNoErrors();

        $this->assertSame(300.0, $brouillon->fresh()->amountPaid());
    }

    /**
     * Un brouillon ne devient pas « payé » : il n'a pas encore été émis, et le
     * statut de règlement n'a de sens que sur une créance existante.
     */
    public function test_a_fully_paid_draft_stays_a_draft(): void
    {
        $brouillon = $this->brouillon(1000);

        $this->post(route('invoices.payments.store', $brouillon), [
            'amount' => 1000,
            'paid_at' => now()->subDay()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Invoice::STATUS_DRAFT, $brouillon->fresh()->status);
    }

    /**
     * Une facture annulée n'attend plus rien.
     */
    public function test_a_cancelled_invoice_accepts_nothing(): void
    {
        $facture = $this->brouillon();
        $facture->forceFill(['status' => Invoice::STATUS_CANCELLED])->saveQuietly();

        $this->post(route('invoices.payments.store', $facture), [
            'amount' => 100,
            'paid_at' => now()->subDay()->toDateString(),
        ]);

        $this->assertCount(0, $facture->fresh()->payments);
    }

    public function test_a_deposit_recorded_on_the_draft_appears_on_the_invoice(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());

        $this->assertCount(1, $this->encaissementsDuPdf($facture));
    }

    /**
     * ⚠️ Le cœur de la décision : ce qui est saisi APRÈS l'émission ne paraît
     * pas sur le document. La facture envoyée reste ce qu'elle était.
     */
    public function test_a_payment_recorded_after_finalization_stays_off_the_document(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());

        // Le solde, encaissé le jour de la prestation, après l'émission.
        $this->travel(1)->days();
        $facture->payments()->create([
            'amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);
        $this->travelBack();

        $encaissements = $this->encaissementsDuPdf($facture);

        $this->assertCount(1, $encaissements);
        $this->assertSame(300.0, $encaissements[0]['montant']);
        // …mais la comptabilité, elle, l'a bien enregistré.
        $this->assertSame(1000.0, $facture->fresh()->amountPaid());
    }

    /**
     * ⚠️ L'arithmétique du document doit tenir debout : le reste à payer
     * découle des encaissements AFFICHÉS. Sinon la facture annoncerait
     * « acompte 300 € » et « reste à payer 0 € » sous les yeux du client.
     */
    public function test_the_balance_follows_what_the_document_shows(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());

        $this->travel(1)->days();
        $facture->payments()->create([
            'amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);
        $this->travelBack();

        $donnees = app(InvoicePdfService::class)->prepareData($facture->fresh());

        $this->assertSame(700.0, $donnees['resteAPayer']);
    }

    /**
     * Le document ne bouge plus une fois émis : deux rendus successifs, entre
     * lesquels un règlement a été saisi, doivent donner le même bloc.
     */
    public function test_the_document_no_longer_changes_after_it_is_issued(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());
        $avant = app(InvoicePdfService::class)->preview($facture->fresh());

        $this->travel(1)->days();
        $facture->payments()->create([
            'amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);
        $this->travelBack();

        $apres = app(InvoicePdfService::class)->preview($facture->fresh());

        $this->assertSame($avant, $apres);
    }

    /**
     * Sur un brouillon, tout ce qui est saisi s'affiche : rien n'a encore été
     * émis, donc rien n'est figé.
     */
    public function test_everything_shows_while_it_is_still_a_draft(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->createMany([
            ['amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer'],
            ['amount' => 200, 'paid_at' => now()->subDay()->toDateString(), 'method' => 'cash'],
        ]);

        $this->assertCount(2, $this->encaissementsDuPdf($brouillon));
    }

    /**
     * ⚠️ Le cas que la comparaison d'horodatages laissait passer.
     *
     * Les dates sont stockées à la SECONDE. Un règlement saisi dans la même
     * seconde que la finalisation était indiscernable d'un acompte saisi juste
     * avant, et se retrouvait sur le document. D'où une réponse inscrite sur
     * l'encaissement à sa création, plutôt que déduite d'une horloge.
     */
    public function test_a_payment_recorded_in_the_same_second_as_issuance_stays_off(): void
    {
        $brouillon = $this->brouillon(1000);
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(5)->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());

        // Sans voyager dans le temps : même seconde que la finalisation.
        $facture->payments()->create([
            'amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $encaissements = $this->encaissementsDuPdf($facture);

        $this->assertCount(1, $encaissements);
        $this->assertSame(300.0, $encaissements[0]['montant']);
    }

    /**
     * L'encaissement porte lui-même la réponse, et il la porte dès sa création.
     */
    public function test_the_answer_is_written_on_the_payment_itself(): void
    {
        $brouillon = $this->brouillon(1000);

        $surBrouillon = $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDay()->toDateString(), 'method' => 'transfer',
        ]);

        $facture = app(FinalizeInvoiceAction::class)->execute($brouillon->fresh());

        $apresEmission = $facture->payments()->create([
            'amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $this->assertTrue($surBrouillon->fresh()->recorded_before_issue);
        $this->assertFalse($apresEmission->fresh()->recorded_before_issue);
    }
}
