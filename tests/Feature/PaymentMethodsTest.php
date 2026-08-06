<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-098: moyens de paiement personnalisables sur la facture.
 */
class PaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_payment_methods_default_and_freetext(): void
    {
        $settings = new BusinessSettings();
        // Aucun réglage → défaut Virement.
        $this->assertSame(['transfer'], $settings->getEffectivePaymentMethods());

        // Texte libre (FEAT-098) : valeurs conservées, vides retirées, ordre préservé.
        $settings->default_payment_methods = ['Wero', '  ', 'Mon moyen à moi'];
        $this->assertSame(['Wero', 'Mon moyen à moi'], $settings->getEffectivePaymentMethods());

        // Les anciennes clés restent acceptées telles quelles (compat).
        $settings->default_payment_methods = ['transfer', 'cash'];
        $this->assertSame(['transfer', 'cash'], $settings->getEffectivePaymentMethods());
    }

    public function test_invoice_pdf_shows_configured_payment_methods(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create([
            // Clé connue traduite ('transfer') + texte libre affiché tel quel.
            'default_payment_methods' => ['transfer', 'Wero', 'Espèces sur place'],
        ]);

        $client = Client::first();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        $html = app(InvoicePdfService::class)->previewDraft($invoice->fresh());

        $this->assertStringContainsString(__('app.payment_methods.transfer'), $html);
        $this->assertStringContainsString('Wero', $html);
        $this->assertStringContainsString('Espèces sur place', $html);
    }

    // --- Override par facture (FEAT-098, 2e volet) -------------------------------

    /** Facture de brouillon prête à être rendue, avec réglages d'entreprise. */
    private function draftWithSettings(array $defaults, ?array $invoiceMethods = null): Invoice
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        BusinessSettings::factory()->create([
            'user_id' => $user->id,
            'default_payment_methods' => $defaults,
        ]);
        $client = Client::factory()->create(['user_id' => $user->id]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
            'payment_methods' => $invoiceMethods,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        return $invoice->fresh();
    }

    public function test_une_facture_peut_imposer_ses_propres_moyens(): void
    {
        // Le besoin d'origine : « Payconiq pour certains cas ou cash pour les
        // clients privés ». Un réglage global n'y répond pas.
        $invoice = $this->draftWithSettings(['transfer'], ['Payconiq', 'cash']);

        $this->assertSame(['Payconiq', 'cash'], $invoice->effectivePaymentMethods());

        $html = app(InvoicePdfService::class)->previewDraft($invoice);

        $this->assertStringContainsString('Payconiq', $html);
        $this->assertStringContainsString(__('app.payment_methods.cash'), $html);
        $this->assertStringNotContainsString(__('app.payment_methods.transfer'), $html);
    }

    public function test_sans_precision_la_facture_suit_les_reglages(): void
    {
        $invoice = $this->draftWithSettings(['Wero', 'cash'], null);

        $this->assertSame(['Wero', 'cash'], $invoice->effectivePaymentMethods());
    }

    public function test_une_liste_vide_vaut_absence_de_precision(): void
    {
        // Deux écritures possibles de la même intention : elles doivent mener
        // au même résultat, sans quoi un formulaire vidé perdrait le réglage.
        $invoice = $this->draftWithSettings(['Wero'], []);

        $this->assertSame(['Wero'], $invoice->effectivePaymentMethods());
    }

    public function test_sans_reglage_ni_precision_le_virement_reste_le_defaut(): void
    {
        // Comportement d'origine, codé en dur avant cette fonctionnalité :
        // les factures antérieures doivent se rendre à l'identique.
        $invoice = $this->draftWithSettings([], null);

        $this->assertSame(['transfer'], $invoice->effectivePaymentMethods());
    }

    public function test_les_valeurs_vides_sont_ecartees(): void
    {
        $invoice = $this->draftWithSettings(['transfer'], ['Payconiq', '   ', '']);

        $this->assertSame(['Payconiq'], $invoice->effectivePaymentMethods());
    }

    public function test_le_formulaire_enregistre_les_moyens_de_la_facture(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create(['user_id' => $user->id]);
        $client = Client::factory()->create(['user_id' => $user->id]);

        $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issued_at' => '2026-08-06',
            'due_at' => '2026-09-06',
            'payment_methods' => ['Payconiq', 'cash'],
            'items' => [['title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]],
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertSame(['Payconiq', 'cash'], $invoice->payment_methods);
    }

    public function test_un_tableau_vide_transmis_est_ramene_a_null(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create(['user_id' => $user->id]);
        $client = Client::factory()->create(['user_id' => $user->id]);

        $this->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issued_at' => '2026-08-06',
            'due_at' => '2026-09-06',
            'payment_methods' => [],
            'items' => [['title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]],
        ])->assertSessionHasNoErrors();

        // Une seule écriture de « rien de précisé » en base.
        $this->assertNull(Invoice::where('client_id', $client->id)->latest('id')->firstOrFail()->payment_methods);
    }

    public function test_invoice_pdf_shows_payment_instructions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create([
            'payment_instructions' => '<p>Chèque à l\'ordre de <strong>Test SARL</strong>. <a href="https://pay.me">Payer</a></p><script>alert(1)</script>',
        ]);

        $client = Client::first();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        $html = app(InvoicePdfService::class)->previewDraft($invoice->fresh());

        // Mise en forme WYSIWYG conservée (gras + lien), script retiré.
        $this->assertStringContainsString('<strong>Test SARL</strong>', $html);
        $this->assertStringContainsString('href="https://pay.me"', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_payment_methods_persist_and_cast_to_array(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $settings = BusinessSettings::factory()->create([
            'default_payment_methods' => ['transfer', 'payconiq'],
        ]);

        $this->assertSame(['transfer', 'payconiq'], $settings->fresh()->getEffectivePaymentMethods());
        // Inclus dans le snapshot (factures finalisées).
        $this->assertSame(['transfer', 'payconiq'], $settings->fresh()->toSnapshot()['default_payment_methods']);
    }
}
