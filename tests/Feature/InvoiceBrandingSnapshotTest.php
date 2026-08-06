<?php

namespace Tests\Feature;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Intégrité du pied de page d'une facture finalisée (FEAT-104).
 *
 * La mention « Créé avec faktur.lu » n'apparaît qu'au plan gratuit. Elle était
 * décidée au moment de fabriquer le PDF, à partir du plan **courant** : un
 * changement d'abonnement la faisait donc apparaître ou disparaître sur tout
 * l'historique, et deux exemplaires du même numéro de facture pouvaient
 * différer.
 *
 * Une facture finalisée est un document comptable immuable. Le reste du code
 * l'applique avec rigueur (coordonnées, IBAN, conditions de paiement, couleur,
 * taille du texte, tous figés) ; ce recalcul en était la dernière exception.
 */
class InvoiceBrandingSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans abonnement ni essai en cours, le compte est au plan gratuit.
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => null,
        ]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Un essai en cours suffit à sortir du plan gratuit, sans passer par Stripe.
     *
     * `forceFill` et non `update` : `trial_ends_at` est volontairement absent du
     * `$fillable` de User, pour qu'une affectation de masse ne puisse pas
     * prolonger un essai. Un `update()` serait donc ignoré en silence.
     */
    private function changeDeTrialEnd(?\Carbon\Carbon $date): void
    {
        $this->user->forceFill(['trial_ends_at' => $date])->save();
        $this->user->refresh();
    }

    private function passeAuPayant(): void
    {
        $this->changeDeTrialEnd(now()->addDays(14));
    }

    private function repasseAuGratuit(): void
    {
        $this->changeDeTrialEnd(now()->subDay());
    }

    private function draft(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'seller_snapshot' => null,
            'number' => null,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        return $invoice->fresh();
    }

    private function finalise(Invoice $invoice): Invoice
    {
        return app(FinalizeInvoiceAction::class)->execute($invoice);
    }

    /** La mention est-elle présente dans le PDF d'une facture finalisée ? */
    private function mentionPresente(Invoice $invoice): bool
    {
        return str_contains(
            app(InvoicePdfService::class)->preview($invoice->fresh()),
            '<div class="footer-branding">'
        );
    }

    // --- L'instantané porte la décision ------------------------------------------

    public function test_la_finalisation_fige_la_mention_dans_l_instantane(): void
    {
        $invoice = $this->finalise($this->draft());

        $this->assertTrue($invoice->seller_snapshot['show_branding']);
    }

    public function test_un_compte_payant_fige_l_absence_de_mention(): void
    {
        $this->passeAuPayant();

        $invoice = $this->finalise($this->draft());

        $this->assertFalse($invoice->seller_snapshot['show_branding']);
        $this->assertFalse($this->mentionPresente($invoice));
    }

    // --- Le cœur du sujet : le PDF ne bouge plus ---------------------------------

    public function test_s_abonner_n_efface_pas_la_mention_des_factures_deja_emises(): void
    {
        $invoice = $this->finalise($this->draft());
        $this->assertTrue($this->mentionPresente($invoice), 'Prérequis : la mention est bien là au départ.');

        $this->passeAuPayant();

        // L'exemplaire envoyé au client la portait : le retéléchargement aussi.
        $this->assertTrue($this->mentionPresente($invoice));
    }

    public function test_repasser_au_gratuit_n_ajoute_pas_la_mention_a_posteriori(): void
    {
        $this->passeAuPayant();
        $invoice = $this->finalise($this->draft());
        $this->assertFalse($this->mentionPresente($invoice));

        $this->repasseAuGratuit();

        $this->assertFalse($this->mentionPresente($invoice));
    }

    // --- Ce qui existait avant la correction -------------------------------------

    public function test_une_facture_anterieure_conserve_l_ancien_comportement(): void
    {
        $invoice = $this->finalise($this->draft());

        // Instantané d'une facture finalisée avant FEAT-104 : la clé n'y est pas.
        $snapshot = $invoice->seller_snapshot;
        unset($snapshot['show_branding']);
        Invoice::withoutEvents(fn () => $invoice->update(['seller_snapshot' => $snapshot]));

        // Le repli recalcule, comme autrefois. Figer une valeur inventée
        // reviendrait à réécrire l'historique qu'on cherche à protéger.
        $this->assertTrue($this->mentionPresente($invoice));

        $this->passeAuPayant();

        $this->assertFalse($this->mentionPresente($invoice));
    }

    // --- Les brouillons restent vivants -------------------------------------------

    public function test_un_brouillon_suit_le_plan_courant(): void
    {
        $invoice = $this->draft();
        $service = app(InvoicePdfService::class);

        $this->assertStringContainsString('<div class="footer-branding">', $service->previewDraft($invoice));

        $this->passeAuPayant();

        // Rien n'est figé avant la finalisation : l'aperçu doit montrer le
        // résultat réel du plan d'aujourd'hui.
        $this->assertStringNotContainsString('<div class="footer-branding">', $service->previewDraft($invoice->fresh()));
    }
}
