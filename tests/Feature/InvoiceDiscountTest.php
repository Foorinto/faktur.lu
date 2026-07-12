<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();
        BusinessSettings::factory()->create();
    }

    public function test_line_discount_cascades_to_invoice_totals(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Line 1: 2 × 100 with a 10 % discount → HT 180, VAT 30.60
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation A',
            'quantity' => 2,
            'unit_price' => 100,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'vat_rate' => 17,
        ]);

        // Line 2: 1 × 50, no discount → HT 50, VAT 8.50
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation B',
            'quantity' => 1,
            'unit_price' => 50,
            'vat_rate' => 17,
        ]);

        $invoice->refresh();

        // Totals must reflect the discounted line
        $this->assertSame('230.0000', (string) $invoice->total_ht);   // 180 + 50
        $this->assertSame('39.1000', (string) $invoice->total_vat);   // 30.60 + 8.50
        $this->assertSame('269.1000', (string) $invoice->total_ttc);  // 269.10
    }

    public function test_store_route_accepts_discount_and_recalculates(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user)
            ->post(route('invoices.items.store', $invoice), [
                'title' => 'Prestation remisée',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_type' => 'amount',
                'discount_value' => 50,
                'vat_rate' => 17,
            ])
            ->assertRedirect();

        $item = $invoice->items()->first();
        $this->assertSame('amount', $item->discount_type);
        $this->assertSame('50.0000', (string) $item->discount_value);
        $this->assertSame('150.0000', (string) $item->total_ht); // 200 - 50

        $invoice->refresh();
        $this->assertSame('150.0000', (string) $invoice->total_ht);
        $this->assertSame('25.5000', (string) $invoice->total_vat);
    }

    public function test_existing_invoices_are_unaffected_by_default(): void
    {
        // An item created without any discount input keeps neutral behaviour
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Sans remise',
            'quantity' => 3,
            'unit_price' => 30,
            'vat_rate' => 17,
        ]);

        $this->assertSame('90.0000', (string) $item->total_ht);

        // DB defaults (applied at insert) — reload to read them back
        $item->refresh();
        $this->assertSame('percent', $item->discount_type); // default
        $this->assertSame('0.0000', (string) $item->discount_value); // neutral
    }
}
