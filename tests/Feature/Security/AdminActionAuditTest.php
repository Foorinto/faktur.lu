<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AdminActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * ADM-1 — actions administratives sensibles sans trace ni notification.
 *
 * L'usurpation, la réinitialisation de mot de passe / 2FA, la suppression
 * n'étaient pas journalisées : aucun moyen de savoir qui avait agi. Elles sont
 * désormais consignées (acteur = admin, cible = utilisateur), et l'utilisateur
 * est prévenu pour les réinitialisations.
 */
class AdminActionAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    }

    public function test_impersonation_is_audited(): void
    {
        $admin = $this->admin();
        $cible = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $cible->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.user.impersonate',
            'auditable_type' => (new User())->getMorphClass(),
            'auditable_id' => $cible->id,
        ]);
    }

    public function test_password_reset_is_audited_and_notifies_the_user(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $cible = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.reset-password', $cible->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.user.password_reset',
            'auditable_id' => $cible->id,
        ]);
        Notification::assertSentTo($cible, AdminActionNotification::class);
    }

    public function test_force_delete_is_audited(): void
    {
        $admin = $this->admin();
        $cible = User::factory()->create();
        $cible->delete(); // soft delete préalable

        $this->actingAs($admin)->delete(route('admin.users.force-delete', $cible->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.user.force_deleted',
            'auditable_id' => $cible->id,
        ]);
    }
}
