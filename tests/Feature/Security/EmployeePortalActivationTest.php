<?php

namespace Tests\Feature\Security;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HIGH-2 — rattachement forcé d'un compte existant à un salarié.
 *
 * activatePortal retrouvait un User par email_pro, forçait la vérification de
 * son e-mail et posait account_id dessus : un employeur pouvait s'approprier le
 * compte d'un tiers en saisissant son adresse. Désormais le portail ne s'active
 * que sur un compte NEUF créé pour le salarié.
 */
class EmployeePortalActivationTest extends TestCase
{
    use RefreshDatabase;

    private function employer(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function test_activation_refuses_to_attach_a_pre_existing_account(): void
    {
        // Compte autonome d'un tiers, non vérifié (le cas que l'attaque exploitait).
        $victime = User::factory()->create([
            'email' => 'victime@example.com',
            'email_verified_at' => null,
        ]);

        $employer = $this->employer();
        $employee = Employee::factory()->create([
            'user_id' => $employer->id,
            'email_pro' => 'victime@example.com',
        ]);

        $this->actingAs($employer)
            ->post(route('hr.employees.activate-portal', $employee->id))
            ->assertSessionHas('error');

        $victime->refresh();
        $employee->refresh();

        $this->assertNull($victime->email_verified_at, "L'e-mail du tiers ne doit pas être forcé");
        $this->assertNull($employee->account_id, 'Le compte du tiers ne doit pas être rattaché');
    }

    public function test_activation_creates_a_fresh_account_when_email_is_free(): void
    {
        $employer = $this->employer();
        $employee = Employee::factory()->create([
            'user_id' => $employer->id,
            'email_pro' => 'nouveau.salarie@example.com',
        ]);

        $this->actingAs($employer)
            ->post(route('hr.employees.activate-portal', $employee->id))
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertNotNull($employee->account_id, 'Un compte neuf doit être créé et rattaché');

        $compte = User::find($employee->account_id);
        $this->assertSame('nouveau.salarie@example.com', $compte->email);
    }
}
