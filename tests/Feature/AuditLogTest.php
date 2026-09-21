<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Réauthentification à l'acte : les exports exigent une confirmation
        // récente du mot de passe, posée ici comme le ferait la page.
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->user = User::factory()->create();
    }

    public function test_audit_logger_creates_log_entry(): void
    {
        $this->actingAs($this->user);

        AuditLogger::log('test.action', metadata: ['test' => 'data']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'test.action',
            'status' => 'success',
        ]);
    }

    public function test_audit_logger_logs_login(): void
    {
        $this->actingAs($this->user);

        AuditLogger::logLogin();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => AuditLog::ACTION_LOGIN,
        ]);
    }

    public function test_audit_logger_logs_logout(): void
    {
        $this->actingAs($this->user);

        AuditLogger::logLogout();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => AuditLog::ACTION_LOGOUT,
        ]);
    }

    public function test_audit_logger_logs_failed_login(): void
    {
        AuditLogger::logLoginFailed('test@example.com');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN_FAILED,
            'status' => 'failed',
        ]);

        $log = AuditLog::where('action', AuditLog::ACTION_LOGIN_FAILED)->first();
        $this->assertEquals('test@example.com', $log->metadata['email']);
    }

    public function test_auditable_trait_logs_model_creation(): void
    {
        $this->actingAs($this->user);

        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'type' => 'b2b',
            'country_code' => 'LU',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'Client.created',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    public function test_auditable_trait_logs_model_update(): void
    {
        $this->actingAs($this->user);

        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'type' => 'b2b',
            'country_code' => 'LU',
            'user_id' => $this->user->id,
        ]);

        // Clear the creation log
        AuditLog::truncate();

        $client->update(['name' => 'Updated Client']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'Client.updated',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);

        $log = AuditLog::where('action', 'Client.updated')->first();
        $this->assertEquals('Test Client', $log->old_values['name']);
        $this->assertEquals('Updated Client', $log->new_values['name']);
    }

    public function test_auditable_trait_logs_model_deletion(): void
    {
        $this->actingAs($this->user);

        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'type' => 'b2b',
            'country_code' => 'LU',
            'user_id' => $this->user->id,
        ]);

        $clientId = $client->id;

        // Clear the creation log
        AuditLog::truncate();

        $client->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'Client.deleted',
            'auditable_type' => Client::class,
            'auditable_id' => $clientId,
        ]);
    }

    public function test_audit_log_filters_sensitive_data(): void
    {
        $this->actingAs($this->user);

        AuditLogger::log(
            'test.sensitive',
            newValues: [
                'name' => 'Test',
                'password' => 'secret123',
                'remember_token' => 'token123',
            ]
        );

        $log = AuditLog::where('action', 'test.sensitive')->first();

        $this->assertArrayHasKey('name', $log->new_values);
        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertArrayNotHasKey('remember_token', $log->new_values);
    }

    public function test_audit_log_index_page_loads(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user);

        // Create some logs
        AuditLogger::logLogin();
        AuditLogger::logLogout();

        $response = $this->get(route('audit-logs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AuditLogs/Index')
            ->has('logs')
            ->has('categories')
        );
    }

    public function test_audit_log_filters_by_category(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user);

        AuditLogger::logLogin();
        AuditLogger::log('Invoice.created');

        $response = $this->get(route('audit-logs.index', ['category' => 'auth']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('logs.data', fn ($logs) => collect($logs)->every(fn ($log) => str_starts_with($log['action'], 'auth.'))
            )
        );
    }

    public function test_audit_log_export_csv(): void
    {
        $this->actingAs($this->user);

        AuditLogger::logLogin();

        $response = $this->get(route('audit-logs.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_user_can_only_see_own_audit_logs(): void
    {
        $this->withoutVite();
        $otherUser = User::factory()->create();

        // Create log for other user
        $this->actingAs($otherUser);
        AuditLogger::logLogin();

        // Create log for current user
        $this->actingAs($this->user);
        AuditLogger::logLogin();

        $response = $this->get(route('audit-logs.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('logs.data', fn ($logs) => collect($logs)->every(fn ($log) => true) && count($logs) === 1
            )
        );
    }

    public function test_audit_log_action_labels(): void
    {
        $log = new AuditLog(['action' => 'auth.login']);
        $this->assertEquals('Connexion', $log->action_label);

        $log = new AuditLog(['action' => 'Invoice.created']);
        $this->assertEquals('Facture créée', $log->action_label);
    }

    public function test_audit_log_action_emojis(): void
    {
        $log = new AuditLog(['action' => 'auth.login']);
        $this->assertEquals('🔐', $log->action_emoji);

        $log = new AuditLog(['action' => 'Invoice.created']);
        $this->assertEquals('📄', $log->action_emoji);

        $log = new AuditLog(['action' => 'Client.created']);
        $this->assertEquals('👤', $log->action_emoji);
    }

    public function test_audit_log_changed_fields_attribute(): void
    {
        $log = new AuditLog([
            'old_values' => ['name' => 'Old Name', 'email' => 'old@test.com'],
            'new_values' => ['name' => 'New Name', 'email' => 'old@test.com'],
        ]);

        $changes = $log->changed_fields;

        $this->assertArrayHasKey('name', $changes);
        $this->assertEquals('Old Name', $changes['name']['old']);
        $this->assertEquals('New Name', $changes['name']['new']);
        $this->assertArrayNotHasKey('email', $changes);
    }
}
