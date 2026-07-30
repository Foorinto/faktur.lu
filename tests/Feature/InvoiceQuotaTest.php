<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quota de factures du plan gratuit (5/mois).
 *
 * Cas réel : des utilisateurs possèdent déjà plus de 5 brouillons, créés avant
 * que la limite ne soit appliquée sur toutes les routes. Ces brouillons doivent
 * être CONSERVÉS, mais ne pas pouvoir être émis au-delà du quota.
 */
class InvoiceQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function freeUserWithClient(): User
    {
        $user = User::factory()->create(['trial_ends_at' => now()->subMonth()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create();
        Client::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function makeDraft(User $user): Invoice
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => Client::first()->id,
            'status' => Invoice::STATUS_DRAFT,
            'finalized_at' => null,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        return $invoice;
    }

    public function test_existing_drafts_beyond_quota_are_never_deleted(): void
    {
        $user = $this->freeUserWithClient();

        // 6 brouillons : un de plus que le quota gratuit.
        for ($i = 0; $i < 6; $i++) {
            $this->makeDraft($user);
        }

        $this->assertSame(6, $user->userInvoices()->count());

        // Le contrôle de quota ne supprime rien : les 6 sont toujours là.
        app(PlanService::class)->canFinalizeInvoice($user);
        $this->assertSame(6, $user->fresh()->userInvoices()->count());
    }

    public function test_finalization_is_allowed_up_to_the_quota_then_blocked(): void
    {
        $user = $this->freeUserWithClient();
        $plan = app(PlanService::class);

        // Aucune facture émise : la finalisation est possible.
        $this->assertTrue($plan->canFinalizeInvoice($user));

        // 5 factures déjà émises ce mois-ci = quota atteint.
        for ($i = 0; $i < 5; $i++) {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'client_id' => Client::first()->id,
                'status' => Invoice::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);
        }

        $this->assertFalse($plan->canFinalizeInvoice($user));

        // Le brouillon en trop existe toujours, il est juste inémettable.
        $draft = $this->makeDraft($user);
        $this->assertDatabaseHas('invoices', ['id' => $draft->id, 'status' => Invoice::STATUS_DRAFT]);
    }

    public function test_drafts_do_not_count_towards_the_finalization_quota(): void
    {
        $user = $this->freeUserWithClient();

        // 6 brouillons, zéro émise : on doit pouvoir en émettre.
        for ($i = 0; $i < 6; $i++) {
            $this->makeDraft($user);
        }

        $this->assertTrue(app(PlanService::class)->canFinalizeInvoice($user));
    }

    public function test_creating_a_draft_is_refused_once_the_monthly_quota_is_reached(): void
    {
        $user = $this->freeUserWithClient();

        // 5 factures créées ce mois-ci = quota du plan gratuit.
        for ($i = 0; $i < 5; $i++) {
            $this->makeDraft($user);
        }

        $this->post(route('invoices.store'), [
            'client_id' => Client::first()->id,
            'items' => [[
                'title' => 'Une de trop',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 17,
            ]],
        ])->assertSessionHas('error');

        // Rien n'a été créé : on reste à 5.
        $this->assertSame(5, $user->userInvoices()->count());
    }

    public function test_the_creation_form_warns_before_letting_the_user_fill_it_in(): void
    {
        $user = $this->freeUserWithClient();

        for ($i = 0; $i < 5; $i++) {
            $this->makeDraft($user);
        }

        // Ouvrir un formulaire qu'on ne pourra pas enregistrer est une impasse :
        // l'écran doit le dire AVANT la saisie.
        $this->get(route('invoices.create'))
            ->assertInertia(fn ($page) => $page->where('invoiceQuotaReached', true));
    }
}
