<?php

namespace Tests\Feature;

use App\Mail\AuditAlert;
use App\Models\AuditLog;
use App\Models\User;
use App\Security\AuditChain;
use App\Services\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Le journal d'audit démontrable (FEAT-125) : chaque entrée est scellée dans
 * une chaîne d'empreintes, une modification ou une suppression après coup se
 * voit, la copie hors site est signée, la rétention pose une ancre, et un
 * tiers peut refaire la chaîne depuis l'export sans la base.
 */
class AuditChainTest extends TestCase
{
    use RefreshDatabase;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossier = storage_path('framework/testing/audit-exports-'.uniqid());
        config([
            'audit.export.local_path' => $this->dossier,
            'audit.export.cloud' => false,
            'audit.export.enabled' => true,
            'audit.notification_email' => 'admin@example.lu',
            'backup.encryption_key' => null,
        ]);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossier);

        parent::tearDown();
    }

    // --- Outils ----------------------------------------------------------------

    private function entree(array $attributs = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'action' => 'test.event',
            'status' => AuditLog::STATUS_SUCCESS,
            'new_values' => ['montant' => 10],
            'ip_address' => '127.0.0.1',
        ], $attributs));
    }

    private function vieillir(int $id, int $jours): void
    {
        DB::table('audit_logs')->where('id', $id)->update(['created_at' => now()->subDays($jours)->format('Y-m-d H:i:s')]);
    }

    private function brut(int $id): object
    {
        return DB::table('audit_logs')->where('id', $id)->first();
    }

    private function chaine(): AuditChain
    {
        return app(AuditChain::class);
    }

    // --- Le scellement ---------------------------------------------------------

    public function test_le_scellement_chaine_les_entrees_dans_l_ordre(): void
    {
        [$a, $b, $c] = [$this->entree(), $this->entree(), $this->entree()];

        $this->artisan('audit:seal')->assertSuccessful()->expectsOutputToContain('3 entrée(s) scellée(s)');

        [$ra, $rb, $rc] = [$this->brut($a->id), $this->brut($b->id), $this->brut($c->id)];
        $this->assertNull($ra->previous_hash);
        $this->assertSame($ra->hash, $rb->previous_hash);
        $this->assertSame($rb->hash, $rc->previous_hash);
        $this->assertSame(AuditChain::hashRow($ra, null), $ra->hash);
        $this->assertSame(AuditChain::hashRow($rc, $rb->hash), $rc->hash);
        $this->assertSame(64, strlen($rc->hash));
    }

    public function test_la_verification_passe_et_le_scellement_est_stable(): void
    {
        $this->entree();
        $this->entree();
        $this->chaine()->sealPending();

        $this->assertSame(0, $this->chaine()->sealPending());
        $resultat = $this->chaine()->verify();

        $this->assertTrue($resultat['ok']);
        $this->assertSame(2, $resultat['checked']);
        $this->assertSame(2, $resultat['head_id']);
        $this->artisan('audit:verify')->assertSuccessful()->expectsOutputToContain('Chaîne intacte : 2 entrée(s)');
    }

    public function test_une_entree_modifiee_apres_scellement_est_detectee(): void
    {
        $this->entree();
        $b = $this->entree();
        $this->entree();
        $this->chaine()->sealPending();

        DB::table('audit_logs')->where('id', $b->id)->update(['new_values' => json_encode(['montant' => 9999])]);

        $resultat = $this->chaine()->verify();
        $this->assertFalse($resultat['ok']);
        $this->assertSame($b->id, $resultat['break_id']);
        $this->assertStringContainsString('contenu modifié', $resultat['reason']);
        $this->artisan('audit:verify')->assertFailed();
    }

    public function test_une_entree_supprimee_au_milieu_est_detectee(): void
    {
        $this->entree();
        $b = $this->entree();
        $c = $this->entree();
        $this->chaine()->sealPending();

        DB::table('audit_logs')->where('id', $b->id)->delete();

        $resultat = $this->chaine()->verify();
        $this->assertFalse($resultat['ok']);
        $this->assertSame($c->id, $resultat['break_id']);
        $this->assertStringContainsString('chaîne rompue', $resultat['reason']);
    }

    public function test_une_entree_reecrite_avec_une_empreinte_coherente_casse_le_maillon_suivant(): void
    {
        $a = $this->entree();
        $b = $this->entree();
        $c = $this->entree();
        $this->chaine()->sealPending();

        // L'attaquant modifie l'entrée ET recalcule son empreinte : sans la
        // chaîne, rien ne le trahirait. Le maillon suivant, lui, ne ment pas.
        DB::table('audit_logs')->where('id', $b->id)->update(['new_values' => json_encode(['montant' => 1])]);
        $rb = $this->brut($b->id);
        DB::table('audit_logs')->where('id', $b->id)->update(['hash' => AuditChain::hashRow($rb, $this->brut($a->id)->hash)]);

        $resultat = $this->chaine()->verify();
        $this->assertFalse($resultat['ok']);
        $this->assertSame($c->id, $resultat['break_id']);
    }

    public function test_les_entrees_non_scellees_en_fin_de_chaine_sont_tolerees_puis_signalees(): void
    {
        $this->entree();
        $this->chaine()->sealPending();
        $tardive = $this->entree();

        $resultat = $this->chaine()->verify();
        $this->assertTrue($resultat['ok']);
        $this->assertSame(1, $resultat['pending']);
        $this->artisan('audit:verify --alert')->assertSuccessful();
        Mail::assertNothingSent();

        // Vingt minutes sans scellement : le scelleur ne tourne plus.
        DB::table('audit_logs')->where('id', $tardive->id)->update(['created_at' => now()->subMinutes(20)->format('Y-m-d H:i:s')]);
        $this->artisan('audit:verify --alert')->assertFailed();
        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => $mail->hasTo('admin@example.lu') && str_contains(implode(' ', $mail->lignes), 'non scellée'));
    }

    public function test_une_rupture_declenche_une_alerte(): void
    {
        $a = $this->entree();
        $this->entree();
        $this->chaine()->sealPending();
        DB::table('audit_logs')->where('id', $a->id)->update(['action' => 'autre.chose']);

        $this->artisan('audit:verify --alert')->assertFailed();

        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => str_contains($mail->sujet, 'anomalie'));
    }

    // --- L'export --------------------------------------------------------------

    public function test_l_export_ecrit_un_fichier_signe_et_reprend_apres_la_tete(): void
    {
        $this->entree();
        $this->entree();
        $this->entree();
        $this->chaine()->sealPending();

        $this->artisan('audit:export')->assertSuccessful()->expectsOutputToContain('3 entrée(s) exportée(s) (#1 à #3)');

        $fichiers = glob($this->dossier.'/audit-*.jsonl.gz');
        $this->assertCount(1, $fichiers);
        $manifeste = json_decode(file_get_contents(glob($this->dossier.'/audit-*.manifest.json')[0]), true);
        $this->assertSame(1, $manifeste['from_id']);
        $this->assertSame(3, $manifeste['to_id']);
        $this->assertSame($this->brut(3)->hash, $manifeste['head_hash']);
        $this->assertSame(hash_file('sha256', $fichiers[0]), $manifeste['file_sha256']);
        $signature = $manifeste['signature'];
        unset($manifeste['signature'], $manifeste['algorithm']);
        $this->assertSame(AuditChain::sign(AuditChain::exportFields($manifeste + ['remote_path' => null])), $signature);
        $this->assertDatabaseHas('audit_chain_exports', ['from_id' => 1, 'to_id' => 3, 'entries' => 3, 'remote_path' => null]);

        // Rien de nouveau : rien n'est écrit. Deux entrées de plus : on reprend à #4.
        $this->artisan('audit:export')->expectsOutputToContain('Rien de nouveau');
        $this->entree();
        $this->entree();
        $this->chaine()->sealPending();
        $this->artisan('audit:export')->expectsOutputToContain('2 entrée(s) exportée(s) (#4 à #5)');
        $this->assertTrue($this->chaine()->verify()['ok']);
    }

    public function test_un_tiers_peut_refaire_la_chaine_depuis_l_export_sans_la_base(): void
    {
        $this->entree(['new_values' => ['iban' => 'LU28 **** 0000', 'note' => 'à vérifier / "guillemets"']]);
        $this->entree(['user_agent' => 'Mozilla/5.0']);
        $this->chaine()->sealPending();
        $this->artisan('audit:export');

        $gz = gzopen(glob($this->dossier.'/audit-*.jsonl.gz')[0], 'rb');
        $precedente = null;
        $lignes = 0;
        while (($ligne = gzgets($gz)) !== false) {
            $champs = json_decode($ligne, false);
            $this->assertSame($precedente, $champs->previous_hash);
            $this->assertSame(AuditChain::hashRow($champs, $precedente), $champs->hash, "entrée #{$champs->id}");
            $precedente = $champs->hash;
            $lignes++;
        }
        gzclose($gz);

        $this->assertSame(2, $lignes);
    }

    public function test_l_export_hors_site_exige_le_chiffrement(): void
    {
        config(['audit.export.cloud' => true]);
        $this->entree();
        $this->chaine()->sealPending();

        $this->artisan('audit:export --alert')->assertFailed()->expectsOutputToContain('chiffrement');

        $this->assertDatabaseCount('audit_chain_exports', 0);
        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => str_contains($mail->sujet, 'export'));
    }

    public function test_l_export_hors_site_chiffre_et_passe_par_rclone(): void
    {
        config(['audit.export.cloud' => true, 'backup.encryption_key' => 'cle-de-test', 'backup.cloud.remote' => 'pcloud', 'backup.cloud.path' => '/Backups/Facturation', 'backup.cloud.verify_delays' => []]);
        Process::fake(fn (PendingProcess $p) => str_contains($p->command, 'lsf') ? Process::result(output: "present\n") : Process::result());
        $this->entree();
        $this->chaine()->sealPending();

        $this->artisan('audit:export')->assertSuccessful()->expectsOutputToContain('déposé sur pcloud:/Backups/Facturation/audit');

        Process::assertRan(fn (PendingProcess $p) => str_starts_with($p->command, 'openssl enc'));
        Process::assertRanTimes(fn (PendingProcess $p) => str_contains($p->command, ' copy ') && str_contains($p->command, 'pcloud:/Backups/Facturation/audit'), 2);
        $this->assertDatabaseHas('audit_chain_exports', ['encrypted' => 1, 'remote_path' => 'pcloud:/Backups/Facturation/audit']);
        $this->assertTrue($this->chaine()->verify()['ok']);
    }

    public function test_la_queue_coupee_apres_un_export_est_detectee(): void
    {
        $this->entree();
        $this->entree();
        $c = $this->entree();
        $this->chaine()->sealPending();
        $this->artisan('audit:export');

        DB::table('audit_logs')->where('id', $c->id)->delete();

        $resultat = $this->chaine()->verify();
        $this->assertFalse($resultat['ok']);
        $this->assertStringContainsString("l'entrée #{$c->id} exportée comme tête manque", $resultat['reason']);
    }

    public function test_une_trace_d_export_falsifiee_est_detectee(): void
    {
        $this->entree();
        $this->chaine()->sealPending();
        $this->artisan('audit:export');

        DB::table('audit_chain_exports')->update(['head_hash' => str_repeat('0', 64)]);

        $resultat = $this->chaine()->verify();
        $this->assertFalse($resultat['ok']);
        $this->assertStringContainsString('signature invalide', $resultat['reason']);
    }

    // --- La rétention ----------------------------------------------------------

    public function test_la_purge_respecte_la_retention_exige_l_export_et_pose_une_ancre(): void
    {
        $anciennes = [$this->entree(), $this->entree(), $this->entree()];
        $recentes = [$this->entree(), $this->entree()];
        foreach ($anciennes as $e) {
            $this->vieillir($e->id, 6 * 365);
        }
        $this->chaine()->sealPending();

        // Pas exportées : rien ne part.
        $this->artisan('audit:prune')->expectsOutputToContain('Rien à purger');
        $this->assertDatabaseCount('audit_logs', 5);

        $this->artisan('audit:export');
        $this->artisan('audit:prune')->assertSuccessful()->expectsOutputToContain('3 entrée(s) supprimée(s), ancre #1');

        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_chain_anchors', ['last_pruned_id' => $anciennes[2]->id, 'last_pruned_hash' => $this->brut($recentes[0]->id)->previous_hash, 'pruned_count' => 3]);

        // La chaîne repart de l'ancre, et l'export purgé n'est plus exigé.
        $resultat = $this->chaine()->verify();
        $this->assertTrue($resultat['ok'], (string) $resultat['reason']);
        $this->assertSame(2, $resultat['checked']);

        // Les entrées suivantes s'enchaînent toujours, et une purge sans rien de nouveau ne fait rien.
        $this->entree();
        $this->chaine()->sealPending();
        $this->assertTrue($this->chaine()->verify()['ok']);
        $this->artisan('audit:prune')->expectsOutputToContain('Rien à purger');
    }

    public function test_la_suppression_definitive_d_un_compte_ne_casse_pas_la_chaine(): void
    {
        // Avant, la clé étrangère mettait user_id à NULL : l'entrée changeait
        // de contenu après scellement. Le journal garde qui a agi.
        $user = User::factory()->create();
        $this->entree(['user_id' => $user->id]);
        $this->entree(['user_id' => $user->id]);
        $this->chaine()->sealPending();

        $user->forceDelete();

        $this->assertSame($user->id, (int) $this->brut(1)->user_id);
        $this->assertTrue($this->chaine()->verify()['ok']);
    }

    public function test_une_retention_inferieure_a_un_an_est_refusee(): void
    {
        $this->artisan('audit:prune --days=30')->assertFailed();
    }

    // --- Le reste ----------------------------------------------------------------

    public function test_la_planification_est_en_place(): void
    {
        // La planification vit dans bootstrap/app.php : on la lit comme
        // l'opérateur, par schedule:list.
        Artisan::call('schedule:list');
        $sortie = Artisan::output();

        foreach (['audit:seal', 'audit:export --alert', 'audit:verify --alert', 'audit:prune'] as $attendue) {
            $this->assertStringContainsString($attendue, $sortie);
        }
    }

    public function test_le_monitoring_admin_expose_l_etat_du_journal(): void
    {
        $this->entree();
        $this->chaine()->sealPending();

        $audit = app(MonitoringService::class)->getOverview('24h')['audit'];

        $this->assertSame('ok', $audit['status']);
        $this->assertSame(1, $audit['head_id']);
        $this->assertSame(0, $audit['pending']);
        $this->assertSame(1825, $audit['retention_days']);

        // Une entrée non scellée depuis vingt minutes : le scelleur ne tourne plus.
        $tardive = $this->entree();
        DB::table('audit_logs')->where('id', $tardive->id)->update(['created_at' => now()->subMinutes(20)->format('Y-m-d H:i:s')]);
        $audit = app(MonitoringService::class)->getOverview('24h')['audit'];
        $this->assertSame('warning', $audit['status']);
        $this->assertTrue($audit['seal_stalled']);
    }

    public function test_le_statut_se_lit(): void
    {
        $this->entree();
        $this->chaine()->sealPending();

        $this->artisan('audit:status')->assertSuccessful()->expectsOutputToContain('1825 jours');
    }

    public function test_la_migration_a_scelle_l_existant(): void
    {
        // RefreshDatabase rejoue la migration sur une base vide ; on rejoue le
        // scellement initial sur des entrées créées avant : même code, même
        // résultat.
        $this->entree();
        $this->entree();
        $this->assertSame(2, $this->chaine()->sealPending());
        $this->assertTrue($this->chaine()->verify()['ok']);
    }
}
