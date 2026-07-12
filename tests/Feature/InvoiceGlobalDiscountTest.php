<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGlobalDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function draftWithTwoRates(): Invoice
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create();

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'A', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'B', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 3]);

        return $invoice->fresh();
    }

    public function test_global_amount_discount_ventilates_and_cascades(): void
    {
        $invoice = $this->draftWithTwoRates();

        InvoiceDiscount::create([
            'invoice_id' => $invoice->id,
            'label' => 'Remise première commande',
            'type' => 'amount',
            'value' => 50,
        ]);

        $invoice->refresh();

        // 200 HT − 50 = 150 ; TVA ventilée : 75×17% + 75×3% = 12.75 + 2.25 = 15.00
        $this->assertSame('150.0000', (string) $invoice->total_ht);
        $this->assertSame('15.0000', (string) $invoice->total_vat);
        $this->assertSame('165.0000', (string) $invoice->total_ttc);
    }

    public function test_removing_discount_restores_totals(): void
    {
        $invoice = $this->draftWithTwoRates();

        $discount = InvoiceDiscount::create([
            'invoice_id' => $invoice->id,
            'type' => 'percent',
            'value' => 10,
        ]);

        $invoice->refresh();
        $this->assertSame('180.0000', (string) $invoice->total_ht);

        $discount->delete();

        $invoice->refresh();
        $this->assertSame('200.0000', (string) $invoice->total_ht);
        $this->assertSame('20.0000', (string) $invoice->total_vat); // 100×17% + 100×3%
    }

    public function test_line_and_global_discounts_combine(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'user_id' => $user->id, 'status' => Invoice::STATUS_DRAFT]);

        // Line: 2×100 −10% line discount → 180 HT
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'title' => 'A', 'quantity' => 2, 'unit_price' => 100,
            'discount_type' => 'percent', 'discount_value' => 10, 'vat_rate' => 17,
        ]);
        // Global: −20 € on the 180 subtotal → 160 HT
        InvoiceDiscount::create(['invoice_id' => $invoice->id, 'type' => 'amount', 'value' => 20]);

        $invoice->refresh();
        $this->assertSame('160.0000', (string) $invoice->total_ht);
        $this->assertSame('27.2000', (string) $invoice->total_vat); // 160×17%
    }
}
