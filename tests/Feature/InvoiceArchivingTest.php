<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Mail\AuditAlert;
use App\Models\AuditLog;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PdfArchiveService;
use App\Services\PlanService;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * L'archivage PDF/A automatique (FEAT-126) : chaque facture finalisée a son
 * exemplaire figé, avec son empreinte au journal ; un échec n'empêche jamais
 * l'émission ; le rattrapage, la vérification et la copie hors site tournent
 * la nuit ; et c'est pour tous les plans.
 */
class InvoiceArchivingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
        Storage::fake('local');
        Mail::fake();
        config([
            'archive.auto' => true,
            'archive.format' => PdfArchiveService::FORMAT_PDF, // pas de Ghostscript dans la plupart des tests : rapidité
            'archive.export.cloud' => false,
            'archive.notification_email' => 'admin@example.lu',
            'backup.encryption_key' => null,
            'backup.cloud.verify_delays' => [],
        ]);

        $this->user = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->addDays(14)]);
        $this->actingAs($this->user);
    }

    private function brouillon(?User $user = null): Invoice
    {
        $user ??= $this->user;
        BusinessSettings::withoutGlobalScopes()->where('user_id', $user->id)->exists()
            || BusinessSettings::factory()->create(['user_id' => $user->id]);
        $client = Client::factory()->create(['user_id' => $user->id]);
        $facture = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        $facture->items()->create([
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
            'total_ht' => 100, 'total_vat' => 17, 'total_ttc' => 117,
        ]);

        return $facture;
    }

    private function finaliser(Invoice $facture): Invoice
    {
        return app(FinalizeInvoiceAction::class)->execute($facture->fresh());
    }

    // --- À la finalisation ------------------------------------------------------

    public function test_la_finalisation_archive_la_facture_et_l_inscrit_au_journal(): void
    {
        $facture = $this->finaliser($this->brouillon());

        $this->assertNotNull($facture->archived_at);
        $this->assertSame(PdfArchiveService::FORMAT_PDF, $facture->archive_format);
        $this->assertMatchesRegularExpression("#^archive/{$this->user->id}/\\d{4}/\\d{2}/[A-Za-z0-9_\\-]+\\.pdf$#", $facture->archive_path);
        Storage::disk('local')->assertExists($facture->archive_path);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($facture->archive_path)), $facture->archive_checksum);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($facture->archive_path));

        $entree = AuditLog::where('action', 'Invoice.archived')->where('auditable_id', $facture->id)->first();
        $this->assertNotNull($entree);
        $this->assertSame($this->user->id, (int) $entree->user_id);
        $this->assertSame($facture->archive_checksum, $entree->metadata['checksum']);
    }

    public function test_un_archivage_qui_rate_ne_bloque_pas_l_emission(): void
    {
        $this->mock(PdfArchiveService::class, function ($mock) {
            $mock->shouldReceive('archiveQuietly')->once()->andReturnUsing(function () {
                // Ce que fait le vrai archiveQuietly : avaler l'échec.
                return false;
            });
        });

        $facture = $this->finaliser($this->brouillon());

        $this->assertTrue($facture->isFinalized());
        $this->assertNotNull($facture->number);
        $this->assertNull($facture->archived_at);
    }

    public function test_une_conversion_qui_explose_est_avalee_et_journalisee(): void
    {
        // Le service réel, mais une conversion impossible : le PDF est
        // stocké tel quel et la facture reste finalisée.
        $this->partialMock(PdfArchiveService::class, function ($mock) {
            $mock->shouldReceive('archive')->once()->andThrow(new \RuntimeException('Ghostscript a explosé'));
        });

        $facture = $this->finaliser($this->brouillon());

        $this->assertTrue($facture->isFinalized());
        $this->assertNull($facture->fresh()->archived_at);
    }

    public function test_deux_comptes_avec_le_meme_numero_ne_s_ecrasent_pas(): void
    {
        $autre = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->addDays(14)]);

        $a = $this->finaliser($this->brouillon());
        $this->actingAs($autre);
        $b = $this->finaliser($this->brouillon($autre));

        $this->assertSame($a->number, $b->number, 'Le montage suppose deux numérotations identiques.');
        $this->assertNotSame($a->archive_path, $b->archive_path);
        Storage::disk('local')->assertExists($a->archive_path);
        Storage::disk('local')->assertExists($b->archive_path);
    }

    public function test_l_archivage_automatique_peut_etre_coupe_par_configuration(): void
    {
        config(['archive.auto' => false]);

        $facture = $this->finaliser($this->brouillon());

        $this->assertTrue($facture->isFinalized());
        $this->assertNull($facture->archived_at);
    }

    public function test_avec_ghostscript_l_archive_est_un_vrai_pdf_a(): void
    {
        if (! app(PdfArchiveService::class)->isGhostscriptAvailable()) {
            $this->markTestSkipped('Ghostscript absent de ce poste.');
        }
        config(['archive.format' => PdfArchiveService::FORMAT_PDFA_1B]);

        $facture = $this->finaliser($this->brouillon());

        $this->assertSame(PdfArchiveService::FORMAT_PDFA_1B, $facture->archive_format);
        $contenu = Storage::disk('local')->get($facture->archive_path);
        $this->assertStringStartsWith('%PDF', $contenu);
        $this->assertStringContainsString('pdfaid', $contenu, 'Le PDF/A porte son identification XMP.');
    }

    // --- Le rattrapage -----------------------------------------------------------

    public function test_le_rattrapage_archive_les_factures_finalisees_sans_archive(): void
    {
        config(['archive.auto' => false]);
        $a = $this->finaliser($this->brouillon());
        $b = $this->finaliser($this->brouillon());
        $brouillon = $this->brouillon();

        $this->artisan('archive:catch-up --limit=1')->assertSuccessful()->expectsOutputToContain('1 facture(s) archivée(s), 0 en échec, 1 restante(s)');
        $this->assertNotNull($a->fresh()->archived_at);
        $this->assertNull($b->fresh()->archived_at);

        $this->artisan('archive:catch-up')->expectsOutputToContain('1 facture(s) archivée(s), 0 en échec, 0 restante(s)');
        $this->assertNotNull($b->fresh()->archived_at);
        $this->assertNull($brouillon->fresh()->archived_at);
    }

    public function test_une_facture_au_snapshot_encode_en_double_s_archive_quand_meme(): void
    {
        // Des lignes anciennes portent un JSON encodé deux fois : le rendu PDF
        // explosait, donc le rattrapage aussi, chaque nuit. Le cast tolérant
        // les lit comme les autres.
        config(['archive.auto' => false]);
        $facture = $this->finaliser($this->brouillon());
        DB::table('invoices')->where('id', $facture->id)->update([
            'seller_snapshot' => json_encode(json_encode($facture->seller_snapshot)),
            'buyer_snapshot' => json_encode(json_encode($facture->buyer_snapshot)),
        ]);
        $this->assertIsArray($facture->fresh()->seller_snapshot);

        $this->artisan('archive:catch-up')->assertSuccessful();

        $this->assertNotNull($facture->fresh()->archived_at);
    }

    public function test_le_rattrapage_signale_les_factures_qui_ne_s_archivent_pas(): void
    {
        config(['archive.auto' => false]);
        $facture = $this->finaliser($this->brouillon());
        $this->partialMock(PdfArchiveService::class, function ($mock) {
            $mock->shouldReceive('archive')->andThrow(new \RuntimeException('Ghostscript a explosé'));
        });

        $this->artisan('archive:catch-up --alert')->assertFailed()->expectsOutputToContain('1 en échec');

        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => str_contains($mail->lignes[0], "#{$facture->id}"));
    }

    // --- L'intégrité ---------------------------------------------------------------

    public function test_la_verification_detecte_une_archive_modifiee_ou_absente(): void
    {
        $a = $this->finaliser($this->brouillon());
        $b = $this->finaliser($this->brouillon());

        $this->artisan('archive:verify --alert')->assertSuccessful()->expectsOutputToContain('2 archive(s) intacte(s)');
        Mail::assertNothingSent();

        Storage::disk('local')->put($a->archive_path, '%PDF-falsifie');
        Storage::disk('local')->delete($b->archive_path);

        $this->artisan('archive:verify --alert')->assertFailed();
        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => count($mail->lignes) === 2
            && str_contains($mail->lignes[0], 'modifiée') && str_contains($mail->lignes[1], 'absente'));
    }

    // --- La copie hors site ----------------------------------------------------------

    public function test_la_copie_hors_site_exige_le_chiffrement(): void
    {
        config(['archive.export.cloud' => true]);
        $this->finaliser($this->brouillon());

        $this->artisan('archive:export --alert')->assertFailed()->expectsOutputToContain('chiffrement');

        Mail::assertSent(AuditAlert::class);
        $this->assertNull(Invoice::first()->archive_uploaded_at);
    }

    public function test_la_copie_hors_site_chiffre_depose_par_rclone_et_marque_les_archives(): void
    {
        config(['archive.export.cloud' => true, 'backup.encryption_key' => 'cle-de-test', 'backup.cloud.remote' => 'pcloud', 'backup.cloud.path' => '/Backups/faktur-lu']);
        $a = $this->finaliser($this->brouillon());
        $b = $this->finaliser($this->brouillon());
        Storage::disk('local')->put($b->archive_path, 'contenu-remplace'); // empreinte fausse : ne doit pas partir

        $relatifA = preg_replace('#^archive/#', '', $a->archive_path).'.enc';
        Process::fake(function (PendingProcess $p) use ($relatifA) {
            if (str_starts_with($p->command, 'openssl enc')) {
                // Le vrai openssl écrirait le fichier chiffré : on le simule.
                preg_match("/-out '([^']+)'/", $p->command, $m);
                file_put_contents($m[1], 'chiffre');

                return Process::result();
            }

            return str_contains($p->command, 'lsf') ? Process::result(output: $relatifA."\n") : Process::result();
        });

        $this->artisan('archive:export --alert')->assertSuccessful()
            ->expectsOutputToContain('1 archive(s) copiée(s) hors site, empreinte DIFFÉRENTE pour les factures #'.$b->id);

        Process::assertRan(fn (PendingProcess $p) => str_contains($p->command, ' copy ') && str_contains($p->command, 'pcloud:/Backups/faktur-lu/archives'));
        $this->assertNotNull($a->fresh()->archive_uploaded_at);
        $this->assertSame('pcloud:/Backups/faktur-lu/archives/'.$relatifA, $a->fresh()->archive_remote_path);
        $this->assertNull($b->fresh()->archive_uploaded_at);
        Mail::assertSent(AuditAlert::class, fn (AuditAlert $mail) => str_contains($mail->sujet, 'avant copie'));

        // Rien à faire au passage suivant pour la première ; la seconde reste en attente.
        Process::fake(fn () => Process::result());
        $this->artisan('archive:export')->expectsOutputToContain('0 archive(s) copiée(s) hors site');
    }

    // --- Pour tous les plans -------------------------------------------------------

    public function test_un_compte_gratuit_a_l_archivage(): void
    {
        $this->assertTrue(app(PlanService::class)->hasFeature($this->user, 'pdf_archive'));
        config(['archive.auto' => false]);
        $facture = $this->finaliser($this->brouillon());

        $this->post(route('invoices.archive', $facture), ['format' => PdfArchiveService::FORMAT_PDF])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertNotNull($facture->fresh()->archived_at);
        $this->get(route('archive.index'))->assertOk();
    }

    public function test_la_planification_est_en_place(): void
    {
        Artisan::call('schedule:list');
        $sortie = Artisan::output();

        foreach (['archive:catch-up --alert', 'archive:export --alert', 'archive:verify --alert'] as $attendue) {
            $this->assertStringContainsString($attendue, $sortie);
        }
    }
}
