<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\VentilationEncaissements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les encaissements fantômes à zéro euro.
 *
 * Signalés par un client payant le 2026-09-02 :
 *
 *     "J'ai également sur mes dernières factures, dans les encaissements une
 *      erreur : il y a le montant global par virement et une ligne a 0 EUR non
 *      renseigné : il y en a 6 d'après mon tableau de bord"
 *
 * L'enchaînement, reproduit à l'identique. Depuis l'acompte, un brouillon
 * accepte les encaissements. Si l'acompte couvre tout, la finalisation ne
 * recalculait PAS le statut de règlement : la facture ressortait « finalisée »
 * avec un reste dû nul et `isPaid()` à faux. Le bouton « Marquer comme payée »
 * restait donc actif, et le clic créait un encaissement du reste dû, c'est-à-
 * dire zéro, sans moyen de paiement.
 *
 * Effet de bord invisible mais comptable : ces lignes remontaient dans la
 * ventilation par moyen de paiement sous « Non renseigné », donc dans le livre
 * de recettes.
 */
class EncaissementsFantomesTest extends TestCase
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

    /**
     * La cause racine : une facture déjà soldée à l'émission doit naître payée.
     */
    public function test_an_invoice_fully_settled_by_a_deposit_is_paid_once_issued(): void
    {
        $facture = $this->brouillon(1000);
        $facture->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture);

        $facture->refresh();

        $this->assertTrue($facture->isPaid(), "Réglée avant d'être émise, elle naît payée");
        $this->assertSame(0.0, $facture->amountDue());
    }

    /**
     * LA régression, telle que le client l'a vue.
     */
    public function test_marking_as_paid_never_creates_a_zero_euro_payment(): void
    {
        $facture = $this->brouillon(1000);
        $facture->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture);

        // Le geste qui créait le fantôme, tenté malgré la correction.
        $this->post(route('invoices.mark-paid', $facture->fresh()));

        $this->assertSame(1, $facture->fresh()->payments()->count(), 'Un seul encaissement, le vrai');
        $this->assertSame(0, $facture->fresh()->payments()->where('amount', 0)->count());
    }

    /**
     * L'effet comptable : plus de ligne « Non renseigné » à zéro dans la
     * ventilation, donc plus dans le livre de recettes.
     */
    public function test_the_breakdown_carries_no_phantom_line(): void
    {
        $facture = $this->brouillon(1000);
        $facture->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture);
        $this->post(route('invoices.mark-paid', $facture->fresh()));

        $ventilation = app(VentilationEncaissements::class)->surPeriode(
            $this->user->id,
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString()
        );

        $this->assertSame(1000.0, $ventilation['total']);
        $this->assertCount(1, $ventilation['lignes']);
        $this->assertSame('transfer', $ventilation['lignes'][0]['method']);
    }

    /**
     * ⚠️ Régression introduite PAR la correction ci-dessus, trouvée en la
     * relisant plutôt qu'en la testant.
     *
     * Depuis que la finalisation reconnaît une facture déjà soldée, celle-ci
     * peut naître « payée ». Or `isFinalized()` renvoie vrai pour ce statut :
     * le garde de `markAsSent()` laissait donc passer, et la facture repassait
     * de « payée » à « envoyée ». Intégralement réglée, et de nouveau due.
     *
     * L'envoi est un FAIT, le règlement est un ÉTAT. On enregistre le fait
     * sans mentir sur l'état.
     */
    public function test_marking_a_settled_invoice_as_sent_does_not_unpay_it(): void
    {
        $facture = $this->brouillon(1000);
        $facture->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture);
        $this->assertTrue($facture->refresh()->isPaid());

        $this->post(route('invoices.mark-sent', $facture));

        $facture->refresh();

        $this->assertTrue($facture->isPaid(), 'Une facture réglée reste réglée');
        $this->assertNotNull($facture->sent_at, "L'envoi est tout de même enregistré");
    }

    /**
     * Audit du correctif précédent : la date d'envoi ne doit pas être écrasée.
     *
     * En laissant passer une facture payée dans `markAsSent()` pour y inscrire
     * `sent_at`, j'ai ouvert un second chemin : une facture envoyée le 1er,
     * réglée le 5, puis repassée par ce bouton verrait sa date d'envoi
     * remplacée par celle du jour. La vraie date d'envoi serait perdue, et
     * elle est exposée par l'API.
     */
    public function test_marking_as_sent_again_does_not_erase_the_original_send_date(): void
    {
        $facture = $this->brouillon(1000);
        app(FinalizeInvoiceAction::class)->execute($facture);

        $envoiInitial = now()->subDays(5)->startOfDay();
        $facture->refresh()->update([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => $envoiInitial,
        ]);

        $facture->fresh()->payments()->create([
            'amount' => 1000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);
        $facture->fresh()->refreshPaymentStatus();
        $this->assertTrue($facture->fresh()->isPaid());

        $this->post(route('invoices.mark-sent', $facture));

        $this->assertSame(
            $envoiInitial->toDateString(),
            $facture->fresh()->sent_at->toDateString(),
            "La date d'envoi réelle ne doit pas être remplacée par celle du jour"
        );
    }

    /**
     * Le cas voisin, qui ne doit PAS basculer : un acompte partiel laisse la
     * facture due.
     */
    public function test_a_partial_deposit_leaves_the_invoice_due(): void
    {
        $facture = $this->brouillon(1000);
        $facture->payments()->create([
            'amount' => 400,
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
        ]);

        app(FinalizeInvoiceAction::class)->execute($facture);

        $facture->refresh();

        $this->assertFalse($facture->isPaid());
        $this->assertSame(600.0, $facture->amountDue());
    }

    /**
     * Et sans aucun encaissement, la finalisation ne change rien au statut.
     */
    public function test_an_unpaid_invoice_is_not_marked_paid_at_issue(): void
    {
        $facture = $this->brouillon(1000);

        app(FinalizeInvoiceAction::class)->execute($facture);

        $this->assertFalse($facture->refresh()->isPaid());
        $this->assertSame(0, $facture->payments()->count());
    }
}
