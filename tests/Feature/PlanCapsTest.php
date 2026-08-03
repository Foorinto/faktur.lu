<?php

namespace Tests\Feature;

use App\Models\Accountant;
use App\Models\AccountantInvitation;
use App\Models\HR\Employee;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plafonds annoncés sur les pages marketing : 15 employés et 3 comptables
 * externes sur le plan Pro.
 *
 * Ces deux chiffres ont longtemps été affichés sans exister nulle part dans le
 * code. Ces tests existent pour que l'écart ne puisse pas se reformer : si l'un
 * d'eux tombe, c'est que le site promet de nouveau quelque chose que le produit
 * n'applique pas.
 */
class PlanCapsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les plafonds sont portés par les plans en base, pas par des valeurs
        // codées en dur : on teste ceux qui partiront réellement en production.
        $this->seed(PlansSeeder::class);
    }

    /** Un compte en période d'essai reçoit les fonctionnalités du plan Pro. */
    private function proUser(): User
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(14),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function makeEmployees(User $user, int $count, string $status = 'active'): void
    {
        for ($i = 0; $i < $count; $i++) {
            Employee::create([
                'user_id' => $user->id,
                'first_name' => 'Prénom'.$i,
                'last_name' => 'Nom'.$i,
                'contract_type' => 'CDI',
                'contract_start' => '2026-01-01',
                'status' => $status,
            ]);
        }
    }

    private function validEmployeePayload(): array
    {
        return [
            'first_name' => 'Nouvelle',
            'last_name' => 'Recrue',
            'contract_type' => 'CDI',
            'contract_start' => '2026-08-01',
            'status' => 'active',
        ];
    }

    // --- Plafond d'employés -------------------------------------------------

    public function test_les_plans_portent_les_plafonds_annonces(): void
    {
        $this->assertSame(15, Plan::pro()->getLimit('max_employees'));
        $this->assertSame(3, Plan::pro()->getLimit('max_accountants'));

        // Le module RH est réservé à Pro : le plafond le redit explicitement.
        $this->assertSame(0, Plan::essentiel()->getLimit('max_employees'));
        $this->assertSame(1, Plan::essentiel()->getLimit('max_accountants'));
        $this->assertSame(0, Plan::free()->getLimit('max_employees'));
    }

    public function test_le_quinzieme_employe_passe_le_seizieme_est_refuse(): void
    {
        $user = $this->proUser();
        $this->makeEmployees($user, 14);

        $this->post(route('hr.employees.store'), $this->validEmployeePayload());
        $this->assertSame(15, Employee::forUser($user)->count(), 'Le 15e employé devait être accepté.');

        $response = $this->post(route('hr.employees.store'), $this->validEmployeePayload());

        $response->assertSessionHas('error');
        $this->assertSame(15, Employee::forUser($user)->count(), 'Le 16e employé ne devait pas être créé.');
    }

    public function test_le_formulaire_de_creation_est_refuse_une_fois_le_plafond_atteint(): void
    {
        $user = $this->proUser();
        $this->makeEmployees($user, 15);

        $this->get(route('hr.employees.create'))
            ->assertRedirect(route('hr.employees.index'))
            ->assertSessionHas('error');
    }

    public function test_un_employe_sorti_libere_sa_place(): void
    {
        $user = $this->proUser();
        $this->makeEmployees($user, 14);
        $this->makeEmployees($user, 3, 'terminated');

        $quota = app(PlanService::class)->employeeQuota($user);

        $this->assertSame(14, $quota['used'], 'Les employés sortis ne comptent pas dans l\'effectif.');
        $this->assertTrue(app(PlanService::class)->canCreateEmployee($user));
    }

    public function test_reintegrer_un_employe_sorti_ne_permet_pas_de_depasser_le_plafond(): void
    {
        $user = $this->proUser();
        $this->makeEmployees($user, 15);
        $this->makeEmployees($user, 1, 'terminated');

        $sorti = Employee::forUser($user)->where('status', 'terminated')->firstOrFail();

        $response = $this->put(route('hr.employees.update', $sorti), [
            'first_name' => $sorti->first_name,
            'last_name' => $sorti->last_name,
            'contract_type' => 'CDI',
            'contract_start' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('terminated', $sorti->fresh()->status);
    }

    // --- Plafond de comptables externes --------------------------------------

    public function test_le_quatrieme_comptable_est_refuse(): void
    {
        $user = $this->proUser();

        foreach (['a@fid.lu', 'b@fid.lu', 'c@fid.lu'] as $email) {
            $this->post(route('settings.accountant.invite'), ['email' => $email])
                ->assertSessionHasNoErrors();
        }

        $this->post(route('settings.accountant.invite'), ['email' => 'd@fid.lu'])
            ->assertSessionHasErrors('email');

        $this->assertSame(3, AccountantInvitation::where('user_id', $user->id)->count());
    }

    public function test_les_acces_actifs_et_les_invitations_en_attente_se_cumulent(): void
    {
        $user = $this->proUser();

        $accountant = Accountant::create([
            'email' => 'titulaire@fid.lu',
            'name' => 'Fiduciaire',
            'password' => bcrypt('secret-value'),
            'email_verified_at' => now(),
        ]);
        $accountant->clients()->attach($user->id, [
            'status' => 'active',
            'granted_at' => now(),
        ]);

        AccountantInvitation::createForUser($user, 'attente1@fid.lu');
        AccountantInvitation::createForUser($user, 'attente2@fid.lu');

        $quota = app(PlanService::class)->accountantQuota($user);

        $this->assertSame(3, $quota['used'], '1 accès actif + 2 invitations = 3 places occupées.');
        $this->assertFalse(app(PlanService::class)->canInviteAccountant($user));
    }

    public function test_une_invitation_annulee_libere_une_place(): void
    {
        $user = $this->proUser();

        foreach (['a@fid.lu', 'b@fid.lu', 'c@fid.lu'] as $email) {
            AccountantInvitation::createForUser($user, $email);
        }

        $this->assertFalse(app(PlanService::class)->canInviteAccountant($user));

        AccountantInvitation::where('user_id', $user->id)->first()->revoke();

        $this->assertTrue(app(PlanService::class)->canInviteAccountant($user));
    }
}
