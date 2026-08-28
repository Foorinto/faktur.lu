<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Livre de recettes : la page est ouverte, l'historique est vendu.
 *
 * Le livre était entièrement réservé aux plans payants. Un compte gratuit
 * plafonne à cinq factures par mois : lui cacher la vue de ses propres
 * recettes ne protégeait pas grand-chose et ne se voyait même pas — la page
 * n'était pas dans son menu, il ne pouvait pas savoir qu'elle existait.
 *
 * La coupure retenue : l'année en cours pour tous, l'historique et les exports
 * CSV/PDF pour les plans payants. Les années passées sont MONTRÉES verrouillées,
 * avec leur total, parce qu'une limite invisible ne se lève jamais.
 *
 * ⚠️ Le bornage vit dans le contrôleur, jamais dans la page : sinon il suffit
 * d'écrire `?start_date=2024-01-01` dans la barre d'adresse.
 */
class RevenueBookHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
    }

    private function compteGratuit(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function comptePayant(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function factureEncaissee(User $user, string $paidAt, float $ttc = 1000): Invoice
    {
        $client = Client::factory()->create(['user_id' => $user->id]);

        return Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_PAID,
            'finalized_at' => $paidAt,
            'issued_at' => $paidAt,
            'paid_at' => $paidAt,
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_a_free_account_can_open_the_revenue_book(): void
    {
        $this->actingAs($this->compteGratuit())
            ->get(route('reports.revenue-book'))
            ->assertSuccessful();
    }

    public function test_a_free_account_sees_the_current_year(): void
    {
        $user = $this->compteGratuit();
        $this->factureEncaissee($user, now()->startOfYear()->addMonth()->toDateString(), 800);

        $this->actingAs($user)
            ->get(route('reports.revenue-book'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('totals.count', 1)
                ->where('historiqueComplet', false)
            );
    }

    /**
     * Le cœur du dispositif : la date passée dans l'URL est rognée.
     */
    public function test_a_free_account_cannot_reach_last_year_through_the_url(): void
    {
        $user = $this->compteGratuit();
        $this->factureEncaissee($user, now()->subYear()->startOfYear()->addMonth()->toDateString(), 5000);

        $this->actingAs($user)
            ->get(route('reports.revenue-book', [
                'start_date' => now()->subYear()->startOfYear()->toDateString(),
                'end_date' => now()->subYear()->endOfYear()->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // La facture de l'an dernier ne doit pas apparaître…
                ->where('totals.count', 0)
                // …et la période servie est bien celle de l'année en cours.
                ->where('filters.start_date', now()->startOfYear()->toDateString())
                ->where('filters.end_date', now()->endOfYear()->toDateString())
            );
    }

    /**
     * Une plage à cheval est rognée, pas rejetée : le mois en cours reste
     * consultable même si l'utilisateur a demandé plus large.
     */
    public function test_an_overlapping_range_is_trimmed_not_refused(): void
    {
        $user = $this->compteGratuit();
        $this->factureEncaissee($user, now()->startOfYear()->addDays(10)->toDateString(), 400);

        $this->actingAs($user)
            ->get(route('reports.revenue-book', [
                'start_date' => now()->subYears(2)->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.start_date', now()->startOfYear()->toDateString())
                ->where('totals.count', 1)
            );
    }

    /**
     * Les années passées se voient, avec leur total. C'est tout l'intérêt :
     * une limite qu'on ne voit pas ne se lève jamais.
     */
    public function test_locked_years_are_shown_with_their_total(): void
    {
        $user = $this->compteGratuit();
        $this->factureEncaissee($user, now()->subYear()->startOfYear()->addMonth()->toDateString(), 1200);
        $this->factureEncaissee($user, now()->subYear()->startOfYear()->addMonths(2)->toDateString(), 300);

        $this->actingAs($user)
            ->get(route('reports.revenue-book'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // Une seule année verrouillée, la précédente, et le total des
                // deux factures cumulé — 1500 et non 1200 : c'est le montant
                // affiché qui donne envie de rouvrir l'année.
                ->has('anneesVerrouillees', 1)
                ->where('anneesVerrouillees.0.annee', now()->subYear()->year)
                ->where('anneesVerrouillees.0.total', 1500)
            );
    }

    public function test_a_paying_account_reaches_the_whole_history(): void
    {
        $user = $this->comptePayant();
        $this->factureEncaissee($user, now()->subYear()->startOfYear()->addMonth()->toDateString(), 5000);

        $this->actingAs($user)
            ->get(route('reports.revenue-book', [
                'start_date' => now()->subYear()->startOfYear()->toDateString(),
                'end_date' => now()->subYear()->endOfYear()->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('totals.count', 1)
                ->where('historiqueComplet', true)
                ->where('anneesVerrouillees', [])
            );
    }

    /**
     * Les exports restent la part vendue : c'est le levier qui joue tout de
     * suite, l'historique ne mordant qu'à partir de la deuxième année.
     */
    public function test_the_exports_stay_behind_the_paid_plan(): void
    {
        $gratuit = $this->compteGratuit();

        $this->actingAs($gratuit)->get(route('reports.revenue-book.csv'))->assertRedirect();
        $this->actingAs($gratuit)->get(route('reports.revenue-book.pdf'))->assertRedirect();
    }
}
