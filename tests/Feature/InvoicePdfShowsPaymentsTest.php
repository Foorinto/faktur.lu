<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La facture montre ce qui a déjà été versé (FEAT-114 / acompte).
 *
 * Demande d'un client payant, coiffure-esthétique (2026-08-28) :
 *
 *     « je n'établis qu'une facture finale […] Toute la TVA serait donc sur la
 *       facture finale, mais avec un paiement à une date pour l'acompte et une
 *       date pour le paiement final. »
 *
 * Sa cliente règle un acompte à la signature du devis pour fixer le rendez-vous,
 * puis le solde le jour de la prestation. Ce qu'elle doit lire sur la facture :
 * « Acompte versé le 12/09 : 300 € — Reste à payer : 700 € ».
 *
 * Les encaissements y portent `recorded_before_issue` : ce sont des acomptes
 * saisis sur le brouillon, seuls à figurer sur le document. La règle de
 * visibilité elle-même est couverte par DraftPaymentsTest.
 *
 * ⚠️ CE QUI NE DOIT PAS BOUGER : le total TTC et la TVA. Un acompte n'est pas
 * une remise. Représenté en remise, il réduirait la base taxable — sur 1 000 €
 * HT à 17 %, 300 € d'« acompte » en remise donnent 119 € de TVA au lieu de
 * 170 €, soit 51 € sous-déclarés par facture. Le prix ne change pas ; seul le
 * moment du paiement change.
 */
class InvoicePdfShowsPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        \App\Models\BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function facture(float $ttc = 1000): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Dupont']);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'number' => 'FAC-2026-001',
            'status' => Invoice::STATUS_SENT,
            'issued_at' => '2026-09-20',
            'finalized_at' => '2026-09-20',
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    private function donnees(Invoice $invoice): array
    {
        return app(InvoicePdfService::class)->prepareData($invoice->fresh());
    }

    public function test_a_payment_made_before_the_invoice_is_a_deposit(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 300, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true]);

        $donnees = $this->donnees($facture);

        $this->assertCount(1, $donnees['encaissements']);
        $this->assertSame(
            __('invoice.deposit_paid', ['date' => '12/09/2026']),
            $donnees['encaissements'][0]['libelle']
        );
        $this->assertSame(300.0, $donnees['encaissements'][0]['montant']);
    }

    /**
     * Un règlement postérieur à l'émission n'est pas un acompte : il n'a rien
     * précédé.
     */
    public function test_a_payment_made_after_the_invoice_is_a_settlement(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 700, 'paid_at' => '2026-09-25', 'method' => 'cash', 'recorded_before_issue' => true]);

        $donnees = $this->donnees($facture);

        $this->assertSame(
            __('invoice.payment_received', ['date' => '25/09/2026']),
            $donnees['encaissements'][0]['libelle']
        );
    }

    public function test_the_remaining_balance_is_carried(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 300, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true]);

        $this->assertSame(700.0, $this->donnees($facture)['resteAPayer']);
    }

    /**
     * ⚠️ Le cœur du test : ni le total ni la TVA ne bougent.
     */
    public function test_a_deposit_changes_neither_the_total_nor_the_vat(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'issued_at' => '2026-09-20',
            'finalized_at' => '2026-09-20',
            'total_ht' => 1000, 'total_vat' => 170, 'total_ttc' => 1170,
        ]);

        $avant = [$facture->total_ht, $facture->total_vat, $facture->total_ttc];

        $facture->payments()->create(['amount' => 300, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true]);
        $facture->refresh();

        $this->assertSame($avant, [$facture->total_ht, $facture->total_vat, $facture->total_ttc]);
        // 170 € de TVA sur 1 000 € HT, et pas 119 € comme le donnerait une remise.
        $this->assertSame('170.0000', (string) $facture->total_vat);
    }

    /**
     * Les versements se lisent dans l'ordre où ils ont eu lieu.
     */
    public function test_payments_are_listed_oldest_first(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->createMany([
            ['amount' => 700, 'paid_at' => '2026-09-25', 'method' => 'cash', 'recorded_before_issue' => true],
            ['amount' => 300, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true],
        ]);

        $montants = array_column($this->donnees($facture)['encaissements'], 'montant');

        $this->assertSame([300.0, 700.0], $montants);
    }

    /**
     * Une facture sans encaissement doit rendre exactement ce qu'elle rendait
     * avant : pas de bloc, pas de ligne vide.
     */
    public function test_an_invoice_without_payments_carries_nothing(): void
    {
        $donnees = $this->donnees($this->facture(1000));

        $this->assertSame([], $donnees['encaissements']);
    }

    /**
     * Le PDF effectivement rendu porte le bloc, pas seulement les données.
     */
    public function test_the_rendered_document_shows_the_deposit(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 300, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true]);

        $html = app(InvoicePdfService::class)->preview($facture->fresh());

        $this->assertStringContainsString(__('invoice.deposit_paid', ['date' => '12/09/2026']), $html);
        $this->assertStringContainsString(__('invoice.remaining_due'), $html);
        $this->assertStringContainsString('700,00', $html);
    }

    /**
     * Soldée, la facture le dit — « reste à payer : 0 € » se lit mal.
     */
    public function test_a_fully_paid_invoice_says_so(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create(['amount' => 1000, 'paid_at' => '2026-09-12', 'method' => 'transfer', 'recorded_before_issue' => true]);

        $html = app(InvoicePdfService::class)->preview($facture->fresh());

        $this->assertStringContainsString(__('invoice.fully_paid'), $html);
        $this->assertStringNotContainsString(__('invoice.remaining_due'), $html);
    }

    /**
     * Le libellé saisi l'emporte sur la déduction.
     *
     * La déduction par la date couvre le cas courant, mais c'est un texte que
     * le client lit sur un document commercial : l'utilisateur doit pouvoir
     * écrire « Arrhes », « Acompte à la réservation » ou ce qu'il veut.
     */
    public function test_a_typed_label_wins_over_the_inferred_one(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create([
            'amount' => 300,
            'paid_at' => '2026-09-12',
            'recorded_before_issue' => true,
            'method' => 'transfer',
            'label' => 'Arrhes à la réservation',
        ]);

        $donnees = $this->donnees($facture);

        $this->assertSame('Arrhes à la réservation', $donnees['encaissements'][0]['libelle']);
    }

    /**
     * Vide, le libellé automatique reprend la main — c'est ce qui permet de ne
     * rien changer pour qui ne s'en préoccupe pas.
     */
    public function test_an_empty_label_falls_back_to_the_inferred_one(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create([
            'amount' => 300,
            'paid_at' => '2026-09-12',
            'recorded_before_issue' => true,
            'method' => 'transfer',
            'label' => '',
        ]);

        $this->assertSame(
            __('invoice.deposit_paid', ['date' => '12/09/2026']),
            $this->donnees($facture)['encaissements'][0]['libelle']
        );
    }

    public function test_the_typed_label_reaches_the_document(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create([
            'amount' => 300,
            'paid_at' => '2026-09-12',
            'recorded_before_issue' => true,
            'method' => 'transfer',
            'label' => 'Arrhes à la réservation',
        ]);

        $html = app(InvoicePdfService::class)->preview($facture->fresh());

        $this->assertStringContainsString('Arrhes à la réservation', $html);
    }

    /**
     * Le libellé se saisit et se corrige par les routes, sinon le champ du
     * formulaire n'aurait nulle part où aller.
     */
    public function test_the_label_is_accepted_and_editable(): void
    {
        // Dates dans le passé : la saisie refuse un encaissement daté du futur,
        // et `assertRedirect()` ne le dirait pas — un refus de validation
        // redirige lui aussi.
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'issued_at' => now()->subDays(10)->toDateString(),
            'finalized_at' => now()->subDays(10)->toDateString(),
            'total_ht' => 1000, 'total_vat' => 0, 'total_ttc' => 1000,
        ]);

        $this->post(route('invoices.payments.store', $facture), [
            'amount' => 300,
            'paid_at' => now()->subDays(20)->toDateString(),
            'recorded_before_issue' => true,
            'method' => 'transfer',
            'label' => 'Acompte à la commande',
        ])->assertSessionHasNoErrors();

        $encaissement = $facture->payments()->firstOrFail();
        $this->assertSame('Acompte à la commande', $encaissement->label);

        $this->post(
            route('invoices.payments.update', [$facture, $encaissement]),
            ['amount' => 300, 'paid_at' => now()->subDays(20)->toDateString(), 'method' => 'transfer', 'label' => 'Arrhes'],
            ['X-HTTP-METHOD-OVERRIDE' => 'PATCH']
        )->assertSessionHasNoErrors();

        $this->assertSame('Arrhes', $encaissement->fresh()->label);
    }

    /**
     * ⚠️ Le libellé n'est pas la référence bancaire : celle-ci part dans
     * l'export comptable, celui-là ne sert qu'à l'affichage. Les confondre
     * ferait remonter « Acompte » dans un fichier remis à une fiduciaire.
     */
    public function test_the_label_is_not_the_bank_reference(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->create([
            'amount' => 300,
            'paid_at' => '2026-09-12',
            'recorded_before_issue' => true,
            'method' => 'transfer',
            'label' => 'Acompte à la commande',
            'reference' => 'VIR-2026-0912',
        ]);

        $encaissement = $facture->payments()->firstOrFail();

        $this->assertSame('Acompte à la commande', $encaissement->label);
        $this->assertSame('VIR-2026-0912', $encaissement->reference);
    }
}
