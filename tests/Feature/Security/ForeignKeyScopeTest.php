<?php

namespace Tests\Feature\Security;

use App\Models\Client;
use App\Models\HR\Employee;
use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ISO-1 / ISO-2 — clés étrangères validées par exists: sans portée tenant.
 *
 * Une FK (employee_id, client_id…) d'un autre tenant passait la validation et
 * créait une référence croisée : le nom/libellé de l'autre entreprise
 * apparaissait ensuite à l'écran. Les règles sont désormais scopées.
 */
class ForeignKeyScopeTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function test_leave_request_rejects_another_tenants_employee(): void
    {
        $autre = $this->pro();
        $employeeAutre = Employee::factory()->create(['user_id' => $autre->id]);

        $attaquant = $this->pro();
        // L'attaquant a besoin d'un type de congé et d'un employé à lui pour que
        // seul employee_id soit en cause.
        $typeAttaquant = \App\Models\HR\LeaveType::create([
            'user_id' => $attaquant->id, 'name' => 'Congés payés', 'default_days_per_year' => 25,
        ]);

        $this->actingAs($attaquant)
            ->post(route('hr.leaves.store'), [
                'employee_id' => $employeeAutre->id,
                'leave_type_id' => $typeAttaquant->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'days_count' => 2,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_recurring_invoice_update_rejects_another_tenants_client(): void
    {
        $autre = $this->pro();
        $clientAutre = Client::factory()->create(['user_id' => $autre->id]);

        $owner = $this->pro();
        $clientOwner = Client::factory()->create(['user_id' => $owner->id]);
        $recurring = RecurringInvoice::create([
            'user_id' => $owner->id,
            'client_id' => $clientOwner->id,
            'frequency' => 'monthly',
            'next_invoice_date' => now()->addMonth()->toDateString(),
            'currency' => 'EUR',
        ]);

        $this->actingAs($owner)
            ->put(route('recurring-invoices.update', $recurring->id), [
                'client_id' => $clientAutre->id,
                'frequency' => 'monthly',
                'next_invoice_date' => now()->addMonth()->toDateString(),
                'currency' => 'EUR',
                'items' => [[
                    'title' => 'X', 'quantity' => 1, 'unit' => 'u', 'unit_price' => 100, 'vat_rate' => 17,
                ]],
            ])
            ->assertNotFound();

        $this->assertSame(
            $clientOwner->id,
            $recurring->fresh()->client_id,
            'Le client de la récurrence ne doit pas devenir celui d\'un autre tenant'
        );
    }
}
