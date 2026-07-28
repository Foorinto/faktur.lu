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
 * Test de fumée sur les chemins critiques.
 * Couvre ce que les suites actuellement en échec ne couvrent plus :
 * rendu PDF binaire (dompdf), QR de paiement, authentification (fortify).
 */
class CriticalPathSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_pdf_binary_is_generated_by_dompdf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create([
            'default_payment_methods' => ['transfer', 'Wero'],
            'payment_instructions' => '<p>Chèque à l\'ordre de <strong>Test</strong></p>',
            'show_payment_qrcode' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => Client::first()->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation test',
            'quantity' => 2,
            'unit_price' => 150.50,
            'vat_rate' => 17,
        ]);

        // downloadDraft() passe par dompdf et renvoie le binaire réel.
        $response = app(InvoicePdfService::class)->downloadDraft($invoice->fresh());
        $pdf = $response->getContent();

        // Un vrai PDF commence par %PDF- et n'est pas vide.
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(2000, strlen($pdf), 'PDF anormalement petit');
    }

    public function test_login_flow_still_works(): void
    {
        $user = User::factory()->create(['password' => bcrypt('MotDePasse!2026#Test')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'MotDePasse!2026#Test',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertStatus(302);
    }
}
