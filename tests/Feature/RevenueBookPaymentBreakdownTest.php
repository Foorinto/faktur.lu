<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ventilation des encaissements par moyen, dans le livre de recettes (FEAT-114).
 *
 * C'est la demande d'origine du client : « avoir le calcul des différents modes
 * de paiement pour la comptabilité ».
 *
 * Le point délicat n'est pas la somme, c'est la PÉRIODE. Cette ventilation lit
 * les encaissements à leur date réelle, quand la liste des factures ci-dessus
 * lit `paid_at`, qui vaut la date du dernier règlement. Une facture réglée à
 * cheval sur deux mois se répartit donc ici, et pas là.
 */
class RevenueBookPaymentBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Essai en cours : le livre de recettes est derrière
        // `plan.feature:accounting_exports`, absent du plan gratuit.
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function facture(float $ttc, string $statut = Invoice::STATUS_SENT): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => $statut,
            'finalized_at' => now()->subMonths(3),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_the_breakdown_groups_amounts_by_method(): void
    {
        $facture = $this->facture(1000);

        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-10', 'method' => 'cash'],
            ['amount' => 200, 'paid_at' => '2026-03-15', 'method' => 'cash'],
            ['amount' => 500, 'paid_at' => '2026-03-20', 'method' => 'transfer'],
        ]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $lignes = collect($page->toArray()['props']['parMoyenDePaiement']['lignes']);

                $especes = $lignes->firstWhere('method', 'cash');
                $virement = $lignes->firstWhere('method', 'transfer');

                $this->assertSame(500.0, (float) $especes['total']);
                $this->assertSame(2, $especes['nombre']);
                $this->assertSame(500.0, (float) $virement['total']);
                $this->assertSame(1, $virement['nombre']);
            });
    }

    /**
     * Le point qui justifie de lire les encaissements plutôt que les factures.
     *
     * 300 € en mars, 700 € en avril : la facture porte un `paid_at` d'avril et
     * la liste lui attribue 1 000 € en avril. La ventilation, elle, place
     * l'argent dans le mois où il est rentré.
     */
    public function test_a_payment_straddling_two_months_falls_in_the_right_one(): void
    {
        $facture = $this->facture(1000);

        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => '2026-03-25', 'method' => 'cash'],
            ['amount' => 700, 'paid_at' => '2026-04-05', 'method' => 'transfer'],
        ]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ventilation = $page->toArray()['props']['parMoyenDePaiement'];

                $this->assertSame(300.0, (float) $ventilation['total'],
                    "Mars ne doit porter que les 300 € réellement encaissés en mars."
                );
            });
    }

    /**
     * Les encaissements sans moyen se comptent, et se nomment.
     */
    public function test_payments_without_a_method_are_shown_as_unknown(): void
    {
        $facture = $this->facture(400);

        $facture->payments()->create(['amount' => 400, 'paid_at' => '2026-03-12', 'method' => null]);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ligne = collect($page->toArray()['props']['parMoyenDePaiement']['lignes'])->first();

                $this->assertNull($ligne['method']);
                $this->assertSame(__('app.payment_methods.unknown'), $ligne['label']);
            });
    }

    /**
     * Les encaissements d'un autre utilisateur n'entrent pas dans le compte.
     */
    public function test_the_breakdown_is_scoped_to_the_user(): void
    {
        $this->facture(500)->payments()->create([
            'amount' => 500, 'paid_at' => '2026-03-10', 'method' => 'cash',
        ]);

        $autre = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $clientAutre = Client::factory()->create(['user_id' => $autre->id]);
        Invoice::factory()->create([
            'user_id' => $autre->id, 'client_id' => $clientAutre->id,
            'status' => Invoice::STATUS_SENT, 'finalized_at' => now()->subMonths(3),
            'total_ht' => 9999, 'total_vat' => 0, 'total_ttc' => 9999,
        ])->payments()->create(['amount' => 9999, 'paid_at' => '2026-03-11', 'method' => 'cash']);

        $this->actingAs($this->user)
            ->get('/reports/revenue-book?start_date=2026-03-01&end_date=2026-03-31')
            ->assertOk()
            ->assertInertia(function ($page) {
                $this->assertSame(
                    500.0,
                    (float) $page->toArray()['props']['parMoyenDePaiement']['total'],
                    "Les encaissements d'un autre utilisateur ne doivent pas être comptés."
                );
            });
    }
}
