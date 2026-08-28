<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Ventilation des encaissements sur le tableau de bord (FEAT-114).
 *
 * Demande d'un client payant, après la livraison de la fonctionnalité :
 *
 *     « Dans mon tableau de bord, il y a bien le CA mensuel, mais il ne fait
 *       pas apparaitre les differents mode de paiements »
 *
 * Le chiffre d'affaires mensuel y était ; ce qui manquait, c'est comment il a
 * été réglé. Le mois est le grain demandé — « le montant reçu en espèces, en
 * virement etc... par mois ».
 *
 * ⚠️ La ventilation lit les ENCAISSEMENTS, pas les factures : une facture
 * réglée en deux fois pèse dans deux moyens, et à deux dates.
 */
class DashboardPaymentBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlansSeeder::class);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $this->actingAs($this->user);
    }

    private function factureEncaissee(array $encaissements, float $ttc = 1000): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addDays(20)->toDateString(),
            'finalized_at' => now()->startOfYear()->addDays(20)->toDateString(),
            'paid_at' => now()->startOfYear()->addDays(20)->toDateString(),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);

        foreach ($encaissements as [$montant, $date, $moyen]) {
            $facture->payments()->create(['amount' => $montant, 'paid_at' => $date, 'method' => $moyen]);
        }

        return $facture;
    }

    public function test_the_dashboard_carries_the_breakdown(): void
    {
        $this->factureEncaissee([
            [300, now()->startOfYear()->addMonth()->toDateString(), 'cash'],
            [700, now()->startOfYear()->addMonth()->toDateString(), 'transfer'],
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.annuel.total', 1000)
                ->has('encaissementsParMoyen.annuel.lignes', 2)
            );
    }

    /**
     * Le grain demandé : un encaissement pèse dans SON mois.
     */
    public function test_a_payment_lands_in_its_own_month(): void
    {
        // Une facture unique, réglée en deux fois, à deux mois d'écart.
        $this->factureEncaissee([
            [400, now()->startOfYear()->addMonths(1)->toDateString(), 'cash'],
            [600, now()->startOfYear()->addMonths(3)->toDateString(), 'transfer'],
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.mois.2.0.total', 400)
                ->where('encaissementsParMoyen.mois.2.0.method', 'cash')
                ->where('encaissementsParMoyen.mois.4.0.total', 600)
                ->where('encaissementsParMoyen.mois.4.0.method', 'transfer')
            );
    }

    /**
     * Le même moyen, deux mois différents : deux lignes, pas une somme.
     *
     * C'est le cas qui distingue un regroupement par mois d'un regroupement
     * par moyen seul. Sans lui, un test peut passer avec l'un comme avec
     * l'autre — vérifié par mutation.
     */
    public function test_the_same_method_in_two_months_stays_split(): void
    {
        $this->factureEncaissee([
            [250, now()->startOfYear()->addMonths(1)->toDateString(), 'cash'],
            [750, now()->startOfYear()->addMonths(5)->toDateString(), 'cash'],
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('encaissementsParMoyen.mois.2', 1)
                ->where('encaissementsParMoyen.mois.2.0.total', 250)
                ->has('encaissementsParMoyen.mois.6', 1)
                ->where('encaissementsParMoyen.mois.6.0.total', 750)
                // Et le cumul annuel les additionne, lui.
                ->where('encaissementsParMoyen.annuel.total', 1000)
                ->has('encaissementsParMoyen.annuel.lignes', 1)
            );
    }

    /**
     * Un moyen inconnu se nomme. Les encaissements repris lors de la migration
     * n'en portent pas, et les ranger sous « virement » fabriquerait une donnée
     * comptable.
     */
    public function test_an_unknown_method_is_named_not_guessed(): void
    {
        $this->factureEncaissee([[500, now()->startOfYear()->addMonth()->toDateString(), null]], 500);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.annuel.lignes.0.label', __('app.payment_methods.unknown'))
            );
    }

    /**
     * Un compte gratuit consulte l'année en cours, comme dans le livre de
     * recettes. Sans cela, le tableau de bord rendrait par la bande
     * l'historique que le livre réserve aux plans payants.
     */
    public function test_a_free_account_cannot_see_a_past_year_here_either(): void
    {
        $gratuit = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($gratuit)
            ->get(route('dashboard', ['year' => now()->subYear()->year]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.verrouille', true)
                ->where('encaissementsParMoyen.mois', [])
            );
    }

    public function test_a_free_account_still_sees_the_current_year(): void
    {
        $gratuit = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::factory()->create(['user_id' => $gratuit->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $gratuit->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'finalized_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'paid_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'total_ht' => 200, 'total_vat' => 0, 'total_ttc' => 200,
        ]);
        $facture->payments()->create([
            'amount' => 200,
            'paid_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'method' => 'payconiq',
        ]);

        $this->actingAs($gratuit)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.verrouille', false)
                ->where('encaissementsParMoyen.annuel.total', 200)
            );
    }

    /**
     * La ventilation d'un utilisateur ne doit compter que ses encaissements.
     * `InvoicePayment` ne porte pas de portée globale : l'oubli du filtre
     * additionnerait ceux de tout le monde.
     */
    public function test_another_users_payments_stay_out(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now()]);
        $clientAutre = Client::factory()->create(['user_id' => $autre->id]);
        $factureAutre = Invoice::factory()->create([
            'user_id' => $autre->id,
            'client_id' => $clientAutre->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'finalized_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'paid_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'total_ht' => 9999, 'total_vat' => 0, 'total_ttc' => 9999,
        ]);
        $factureAutre->payments()->create([
            'amount' => 9999,
            'paid_at' => now()->startOfYear()->addDays(5)->toDateString(),
            'method' => 'cash',
        ]);

        $this->factureEncaissee([[100, now()->startOfYear()->addMonth()->toDateString(), 'cash']], 100);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.annuel.total', 100)
            );
    }
}
