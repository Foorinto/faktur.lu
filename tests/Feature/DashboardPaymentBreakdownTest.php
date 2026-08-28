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

    private function factureEncaissee(array $encaissements, float $ttc = 1000, ?User $proprietaire = null): Invoice
    {
        $proprietaire ??= $this->user;
        $client = Client::factory()->create(['user_id' => $proprietaire->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $proprietaire->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'issued_at' => now()->startOfMonth()->toDateString(),
            'finalized_at' => now()->startOfMonth()->toDateString(),
            'paid_at' => now()->startOfMonth()->toDateString(),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);

        foreach ($encaissements as [$montant, $date, $moyen]) {
            $facture->payments()->create(['amount' => $montant, 'paid_at' => $date, 'method' => $moyen]);
        }

        return $facture;
    }

    public function test_the_dashboard_carries_this_month_breakdown(): void
    {
        $this->factureEncaissee([
            [300, now()->startOfMonth()->toDateString(), 'cash'],
            [700, now()->startOfMonth()->addDay()->toDateString(), 'transfer'],
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.total', 1000)
                ->has('encaissementsParMoyen.lignes', 2)
                // Le plus gros moyen d'abord : c'est l'ordre qu'on lit.
                ->where('encaissementsParMoyen.lignes.0.method', 'transfer')
                ->where('encaissementsParMoyen.lignes.0.part', 70)
            );
    }

    /**
     * Le mois EN COURS, et lui seul. C'est la question qu'on se pose depuis un
     * tableau de bord ; le détail par période vit dans le livre de recettes.
     */
    public function test_a_payment_from_another_month_stays_out(): void
    {
        $this->factureEncaissee([
            [400, now()->startOfMonth()->toDateString(), 'cash'],
            [600, now()->subMonth()->startOfMonth()->toDateString(), 'transfer'],
            // Un encaissement daté du mois prochain — un chèque post-daté se
            // saisit — ne doit pas non plus entrer dans le mois en cours.
            [900, now()->addMonth()->startOfMonth()->toDateString(), 'check'],
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.total', 400)
                ->has('encaissementsParMoyen.lignes', 1)
                ->where('encaissementsParMoyen.lignes.0.method', 'cash')
            );
    }

    /**
     * Un moyen inconnu se nomme. Les encaissements repris lors de la migration
     * n'en portent pas, et les ranger sous « virement » fabriquerait une donnée
     * comptable.
     */
    public function test_an_unknown_method_is_named_not_guessed(): void
    {
        $this->factureEncaissee([[500, now()->startOfMonth()->toDateString(), null]], 500);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.lignes.0.label', __('app.payment_methods.unknown'))
                ->where('encaissementsParMoyen.lignes.0.method', null)
            );
    }

    /**
     * La carte est ouverte à tous : le mois en cours appartient à l'année en
     * cours, que tous les plans consultent. Un compte gratuit doit la voir.
     */
    public function test_a_free_account_sees_the_card(): void
    {
        $gratuit = User::factory()->create(['email_verified_at' => now()]);
        $this->factureEncaissee(
            [[200, now()->startOfMonth()->toDateString(), 'payconiq']],
            200,
            $gratuit
        );

        $this->actingAs($gratuit)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.total', 200)
            );
    }

    /**
     * `InvoicePayment` ne porte pas de portée globale : l'oubli du filtre
     * additionnerait les encaissements de tout le monde.
     */
    public function test_another_users_payments_stay_out(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now()]);
        $this->factureEncaissee(
            [[9999, now()->startOfMonth()->toDateString(), 'cash']],
            9999,
            $autre
        );

        $this->factureEncaissee([[100, now()->startOfMonth()->toDateString(), 'cash']], 100);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.total', 100)
            );
    }

    /**
     * Chaque ligne renvoie au listing filtré : la carte donne le chiffre, le
     * listing donne les factures qui le composent. Le filtre attend la clé
     * technique du moyen, pas son libellé traduit.
     */
    public function test_the_method_key_survives_for_the_invoice_list_filter(): void
    {
        $this->factureEncaissee([[250, now()->startOfMonth()->toDateString(), 'wero']], 250);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('encaissementsParMoyen.lignes.0.method', 'wero')
            );

        // Et ce filtre existe bien de l'autre côté.
        $this->get(route('invoices.index', ['payment_method' => 'wero']))
            ->assertSuccessful();
    }
}
