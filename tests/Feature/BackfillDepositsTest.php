<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reprise des acomptes déjà saisis.
 *
 * Le passage à « seuls les encaissements connus avant l'émission figurent sur
 * la facture » aurait fait disparaître le bloc des documents déjà annotés,
 * d'un déploiement à l'autre. Un client qui avait pris le temps de renseigner
 * ses acomptes les aurait vus s'évanouir.
 *
 * La reprise rattrape ceux qui sont manifestement des acomptes : versés avant
 * la date d'émission de la facture. Elle ne touche pas aux règlements
 * postérieurs, qui n'ont jamais figuré sur un document envoyé.
 */
class BackfillDepositsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    private function encaissement(string $emission, string $versement): InvoicePayment
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'issued_at' => $emission,
            'finalized_at' => $emission,
            'total_ht' => 1000, 'total_vat' => 0, 'total_ttc' => 1000,
        ]);

        // Comme les données antérieures : saisi après l'émission.
        return $facture->payments()->create([
            'amount' => 300,
            'paid_at' => $versement,
            'method' => 'transfer',
            'recorded_before_issue' => false,
        ]);
    }

    private function rejouerLaReprise(): void
    {
        $migration = require database_path(
            'migrations/2026_08_31_150000_backfill_deposits_recorded_before_issue.php'
        );

        $migration->up();
    }

    public function test_a_payment_made_before_the_issue_date_is_recovered(): void
    {
        $acompte = $this->encaissement('2026-03-20', '2026-03-12');

        $this->rejouerLaReprise();

        $this->assertTrue($acompte->fresh()->recorded_before_issue);
    }

    /**
     * Un règlement postérieur n'a jamais figuré sur un document envoyé : la
     * reprise ne doit pas l'y faire apparaître.
     */
    public function test_a_payment_made_after_the_issue_date_is_left_alone(): void
    {
        $solde = $this->encaissement('2026-03-20', '2026-03-25');

        $this->rejouerLaReprise();

        $this->assertFalse($solde->fresh()->recorded_before_issue);
    }

    /**
     * Le jour même n'est pas « avant » : rien ne dit que l'argent précédait la
     * facture, et dans le doute on ne réécrit pas un document.
     */
    public function test_a_payment_made_on_the_issue_date_is_left_alone(): void
    {
        $memeJour = $this->encaissement('2026-03-20', '2026-03-20');

        $this->rejouerLaReprise();

        $this->assertFalse($memeJour->fresh()->recorded_before_issue);
    }

    /**
     * La reprise est rejouable sans dégât : une migration peut être relancée
     * sur un environnement remis à zéro.
     */
    public function test_running_it_twice_changes_nothing(): void
    {
        $acompte = $this->encaissement('2026-03-20', '2026-03-12');
        $solde = $this->encaissement('2026-03-20', '2026-03-25');

        $this->rejouerLaReprise();
        $this->rejouerLaReprise();

        $this->assertTrue($acompte->fresh()->recorded_before_issue);
        $this->assertFalse($solde->fresh()->recorded_before_issue);
    }

    /**
     * Une facture sans date d'émission — un brouillon — ne fournit aucun point
     * de comparaison. La reprise doit l'ignorer plutôt que deviner.
     */
    public function test_a_draft_without_issue_date_is_ignored(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $brouillon = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'issued_at' => null,
            'finalized_at' => null,
            'number' => null,
            'total_ht' => 500, 'total_vat' => 0, 'total_ttc' => 500,
        ]);
        $paiement = $brouillon->payments()->create([
            'amount' => 100, 'paid_at' => '2026-03-12', 'method' => 'cash',
            'recorded_before_issue' => false,
        ]);

        $this->rejouerLaReprise();

        $this->assertFalse($paiement->fresh()->recorded_before_issue);
        $this->assertSame(0, DB::table('invoice_payments')->where('recorded_before_issue', true)->count());
    }
}
