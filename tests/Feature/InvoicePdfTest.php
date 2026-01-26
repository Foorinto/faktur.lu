<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Invoice $finalizedInvoice;
    protected Invoice $draftInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::factory()->create([
            'email' => 'client@example.com',
        ]);

        // Create business settings
        BusinessSettings::factory()->create([
            'vat_regime' => 'assujetti',
            'vat_number' => 'LU12345678',
            'matricule' => '12345678901',
        ]);

        // Create finalized invoice
        $this->finalizedInvoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-001',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
            'seller_snapshot' => [
                'company_name' => 'Test Company',
                'matricule' => '12345678901',
                'vat_regime' => 'assujetti',
                'vat_number' => 'LU12345678',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'iban' => 'LU12 3456 7890 1234',
                'bic' => 'BGLLLULL',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client Company',
                'address' => '45 Avenue Client',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
                'email' => 'client@example.com',
            ],
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->finalizedInvoice->id,
            'title' => 'Development services',
            'quantity' => 10,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        // Create draft invoice
        $this->draftInvoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
    }

    public function test_guest_cannot_download_pdf(): void
    {
        $this->get(route('invoices.pdf', $this->finalizedInvoice))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_download_pdf_for_finalized_invoice(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.pdf', $this->finalizedInvoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_download_pdf_for_draft_invoice(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.pdf', $this->draftInvoice))
            ->assertStatus(400);
    }

    public function test_user_can_stream_pdf(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.pdf.stream', $this->finalizedInvoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_preview_pdf(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.pdf.preview', $this->finalizedInvoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/PdfPreview')
                ->has('invoice')
                ->has('htmlContent')
            );
    }

    public function test_user_can_send_invoice_by_email(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('invoices.send', $this->finalizedInvoice), [
                'email' => 'recipient@example.com',
                'message' => 'Please find attached your invoice.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(\App\Mail\InvoiceMail::class, function ($mail) {
            return $mail->hasTo('recipient@example.com');
        });
    }

    public function test_sending_invoice_marks_as_sent(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('invoices.send', $this->finalizedInvoice), [
                'email' => 'recipient@example.com',
            ]);

        $this->finalizedInvoice->refresh();
        $this->assertEquals(Invoice::STATUS_SENT, $this->finalizedInvoice->status);
        $this->assertNotNull($this->finalizedInvoice->sent_at);
    }

    public function test_cannot_send_draft_invoice(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('invoices.send', $this->draftInvoice), [
                'email' => 'recipient@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_send_email_requires_valid_email(): void
    {
        $this->actingAs($this->user)
            ->post(route('invoices.send', $this->finalizedInvoice), [
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_pdf_contains_invoice_number(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('invoices.pdf', $this->finalizedInvoice));

        // Check the PDF was generated (content-disposition header with filename)
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('2026-001', $contentDisposition);
    }

    public function test_franchise_invoice_has_correct_mention(): void
    {
        // Create a franchise invoice from scratch
        $franchiseInvoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-002',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'seller_snapshot' => [
                'company_name' => 'Franchise Company',
                'matricule' => '12345678901',
                'vat_regime' => 'franchise',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client Company',
                'address' => '45 Avenue Client',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
            ],
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $franchiseInvoice->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('invoices.pdf.preview', $franchiseInvoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('htmlContent', fn ($html) =>
                    str_contains($html, 'TVA non applicable') &&
                    str_contains($html, 'art. 57')
                )
            );
    }
}
