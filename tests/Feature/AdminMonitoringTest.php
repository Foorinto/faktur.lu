<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Page de monitoring de l'administration.
 *
 * Le 26/09/2026, elle tombait en production (« Une erreur inattendue ») :
 * le compte des utilisateurs actifs lisait une colonne `last_activity_at`
 * qui n'a jamais existé. SQLite, ici, prend un nom de colonne inconnu pour
 * du texte et ne lève rien ; MySQL refuse la requête. D'où des comptes
 * exacts plutôt qu'un simple « la page s'affiche » : avec l'ancienne
 * requête, SQLite comptait tous les comptes comme actifs.
 */
class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function connexion(User $user, $quand): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $user->id, 'action' => AuditLog::ACTION_LOGIN, 'status' => 'success', 'created_at' => $quand,
        ]);
    }

    public function test_les_utilisateurs_actifs_sont_ceux_qui_se_sont_connectes(): void
    {
        [$aujourdhui, $cetteSemaine, $ceMois, $jamais] = User::factory()->count(4)->create()->all();
        $this->connexion($aujourdhui, now()->subHours(2));
        $this->connexion($aujourdhui, now()->subHour());
        $this->connexion($cetteSemaine, now()->subDays(3));
        $this->connexion($ceMois, now()->subDays(20));
        DB::table('audit_logs')->insert(['user_id' => $jamais->id, 'action' => 'invoice.created', 'status' => 'success', 'created_at' => now()]);

        $stats = app(MonitoringService::class)->getApplicationStats();

        // Deux connexions du même compte ne le comptent qu'une fois ; une
        // autre action que la connexion ne compte pas.
        $this->assertSame(1, $stats['users_active_24h']);
        $this->assertSame(2, $stats['users_active_7d']);
        $this->assertSame(3, $stats['users_active_30d']);
    }

    public function test_la_page_de_monitoring_s_affiche_pour_un_administrateur(): void
    {
        $admin = User::factory()->admin()->create();
        $this->connexion($admin, now());

        foreach (['1h', '24h', '7d', '30d'] as $periode) {
            $this->actingAs($admin)
                ->get('/'.config('admin.url_prefix').'/monitoring?period='.$periode)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Admin/Monitoring/Index')
                    ->where('metrics.application.users_active_24h', 1)
                    ->has('metrics.system.disk_used_percent')
                );
        }
    }
}
