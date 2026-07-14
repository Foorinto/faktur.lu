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

class InvoiceFooterHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_html_is_rendered_and_sanitized_in_pdf(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create();

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        $invoice->forceFill([
            'footer_message' => '<p><strong>Merci</strong> pour votre <em>confiance</em></p><script>alert(1)</script>',
        ])->save();

        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        $html = app(InvoicePdfService::class)->previewDraft($invoice->fresh());

        // Formatting flows through to the PDF
        $this->assertStringContainsString('<strong>Merci</strong>', $html);
        $this->assertStringContainsString('<em>confiance</em>', $html);

        // Malicious markup is stripped
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }
}
