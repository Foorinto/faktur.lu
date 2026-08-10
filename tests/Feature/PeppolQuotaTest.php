<?php

namespace Tests\Feature;

use App\Models\PeppolTransmission;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plafond mensuel des transmissions Peppol.
 *
 * Chaque document transmis se paie au point d'accès. Le quota du plan Pro
 * valait `null`, donc illimité, sur un abonnement à 15 EUR/mois : un
 * utilisateur intensif coûtait plus qu'il ne rapportait, et d'autant plus
 * qu'il réussissait.
 *
 * 15 EUR ÷ 0,30 EUR (le tarif le plus élevé que nous paierions) = 50. C'est le
 * seuil exact où la marge s'annule.
 *
 * Ces tests portent sur le comptage et la valeur du plafond, pas sur la route :
 * `plan.limit:peppol` s'appuie sur `PlanService::canExportPeppol()`, testé ici
 * directement.
 */
class PeppolQuotaTest extends TestCase
{
    use RefreshDatabase;

    private const PLAFOND = 50;

    private PlanService $plans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plans = app(PlanService::class);
    }

    /**
     * Utilisateur en essai : `getUserPlan()` lui accorde le plan Pro sans
     * qu'il faille simuler un abonnement Stripe.
     *
     * `trial_ends_at` est volontairement exclu de `$fillable`, d'où le
     * `forceFill`.
     */
    private function proUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['trial_ends_at' => now()->addDays(14)])->save();

        return $user->fresh();
    }

    private function transmissions(User $user, int $nombre, ?string $quand = null): void
    {
        // `invoice_id` est NOT NULL. Une seule facture suffit : la table ne
        // porte pas de contrainte d'unicité, seulement une clé étrangère.
        $client = \App\Models\Client::factory()->create(['user_id' => $user->id]);
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);

        $ids = [];

        foreach (range(1, $nombre) as $i) {
            $ids[] = PeppolTransmission::create([
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'status' => 'sent',
            ])->id;
        }

        // Passer `created_at` à `create()` ne suffit pas : Eloquent gère
        // lui-même les horodatages et écrase la valeur. On force donc par le
        // constructeur de requêtes, qui ne les touche pas.
        if ($quand !== null) {
            PeppolTransmission::whereIn('id', $ids)->update(['created_at' => $quand]);
        }
    }

    // --- Le plafond existe et vaut ce qui est annoncé ----------------------------

    public function test_le_plan_pro_declare_un_plafond_fini(): void
    {
        $this->assertSame(self::PLAFOND, Plan::pro()->getLimit('max_peppol_per_month'));
    }

    public function test_le_plan_de_repli_porte_le_meme_plafond(): void
    {
        // `getDefaultProPlan()` sert quand la table `plans` est vide. Sans la
        // clé, il rouvrirait l'illimité que la migration vient de fermer.
        Plan::query()->delete();

        $this->assertSame(
            self::PLAFOND,
            $this->plans->getUserPlan($this->proUser())->getLimit('max_peppol_per_month')
        );
    }

    // --- Le plafond agit ---------------------------------------------------------

    public function test_sous_le_plafond_la_transmission_reste_ouverte(): void
    {
        $user = $this->proUser();
        $this->transmissions($user, self::PLAFOND - 1);

        $this->assertTrue($this->plans->canExportPeppol($user));
    }

    public function test_au_plafond_la_transmission_se_ferme(): void
    {
        $user = $this->proUser();
        $this->transmissions($user, self::PLAFOND);

        $this->assertFalse($this->plans->canExportPeppol($user));
    }

    public function test_le_compteur_repart_au_mois_suivant(): void
    {
        $user = $this->proUser();
        $this->transmissions($user, self::PLAFOND, now()->subMonth()->toDateTimeString());

        $this->assertTrue(
            $this->plans->canExportPeppol($user),
            'Le quota est mensuel : le mois écoulé ne doit pas bloquer le mois courant.'
        );
    }

    public function test_le_quota_est_cloisonne_par_utilisateur(): void
    {
        $voisin = $this->proUser();
        $this->transmissions($voisin, self::PLAFOND);

        $this->assertTrue($this->plans->canExportPeppol($this->proUser()));
    }

    // --- Le décompte passe par un point unique -----------------------------------

    public function test_le_decompte_est_centralise_dans_une_seule_methode(): void
    {
        // Le point d'accès facture les deux sens. `peppolDocumentsThisMonth()`
        // est l'endroit unique où la réception devra s'ajouter (FEAT-097) :
        // si un autre appelant recomptait de son côté, la réception serait
        // oubliée là-bas. Ce test fixe le contrat.
        $user = $this->proUser();
        $this->transmissions($user, 7);

        $this->assertSame(7, $this->plans->peppolDocumentsThisMonth($user));
    }

    public function test_le_decompte_ignore_les_mois_precedents(): void
    {
        $user = $this->proUser();
        $this->transmissions($user, 4, now()->subMonth()->toDateTimeString());

        $this->assertSame(0, $this->plans->peppolDocumentsThisMonth($user));
    }

    // --- Ce qui ne coûte rien reste illimité -------------------------------------

    public function test_l_export_manuel_n_est_pas_plafonne(): void
    {
        // Télécharger le XML ne passe par aucun point d'accès et ne nous coûte
        // rien : seule la transmission sur le réseau est comptée. La route
        // d'export ne porte d'ailleurs pas `plan.limit:peppol`.
        $routes = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->getName() === 'invoices.peppol'
        );

        $this->assertNotNull($routes, 'La route d\'export Peppol a disparu.');
        $this->assertNotContains('plan.limit:peppol', $routes->gatherMiddleware());
    }
}
