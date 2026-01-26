<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_item_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $item->invoice);
        $this->assertEquals($invoice->id, $item->invoice->id);
    }

    public function test_invoice_item_calculates_totals_on_creation(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Test service',
            'description' => null,
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 17,
            'sort_order' => 0,
        ]);

        // Total HT = 2 * 100 = 200
        // Total VAT = 200 * 0.17 = 34
        // Total TTC = 200 + 34 = 234
        $this->assertEquals('200.0000', $item->total_ht);
        $this->assertEquals('34.0000', $item->total_vat);
        $this->assertEquals('234.0000', $item->total_ttc);
    }

    public function test_invoice_item_recalculates_totals_on_update(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);

        $item->update(['quantity' => 3]);

        // Total HT = 3 * 100 = 300
        // Total VAT = 300 * 0.17 = 51
        // Total TTC = 300 + 51 = 351
        $this->assertEquals('300.0000', $item->total_ht);
        $this->assertEquals('51.0000', $item->total_vat);
        $this->assertEquals('351.0000', $item->total_ttc);
    }

    public function test_invoice_item_handles_zero_vat_rate(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Test service',
            'quantity' => 5,
            'unit_price' => 200,
            'vat_rate' => 0,
            'sort_order' => 0,
        ]);

        // Total HT = 5 * 200 = 1000
        // Total VAT = 0 (franchise de TVA)
        // Total TTC = 1000
        $this->assertEquals('1000.0000', $item->total_ht);
        $this->assertEquals('0.0000', $item->total_vat);
        $this->assertEquals('1000.0000', $item->total_ttc);
    }

    public function test_invoice_item_handles_different_vat_rates(): void
    {
        $invoice = Invoice::factory()->create();

        $rates = [
            3 => ['total_vat' => '3.0000', 'total_ttc' => '103.0000'],
            8 => ['total_vat' => '8.0000', 'total_ttc' => '108.0000'],
            14 => ['total_vat' => '14.0000', 'total_ttc' => '114.0000'],
            17 => ['total_vat' => '17.0000', 'total_ttc' => '117.0000'],
        ];

        foreach ($rates as $rate => $expected) {
            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => "Service TVA $rate%",
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => $rate,
                'sort_order' => 0,
            ]);

            $this->assertEquals('100.0000', $item->total_ht, "HT for $rate%");
            $this->assertEquals($expected['total_vat'], $item->total_vat, "VAT for $rate%");
            $this->assertEquals($expected['total_ttc'], $item->total_ttc, "TTC for $rate%");
        }
    }

    public function test_invoice_item_handles_decimal_quantities(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Hours of work',
            'quantity' => 7.5,
            'unit_price' => 80,
            'vat_rate' => 17,
            'sort_order' => 0,
        ]);

        // Total HT = 7.5 * 80 = 600
        // Total VAT = 600 * 0.17 = 102
        // Total TTC = 600 + 102 = 702
        $this->assertEquals('600.0000', $item->total_ht);
        $this->assertEquals('102.0000', $item->total_vat);
        $this->assertEquals('702.0000', $item->total_ttc);
    }

    public function test_invoice_item_handles_decimal_prices(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Product',
            'quantity' => 3,
            'unit_price' => 49.99,
            'vat_rate' => 17,
            'sort_order' => 0,
        ]);

        // Total HT = 3 * 49.99 = 149.97
        // Total VAT = 149.97 * 0.17 = 25.4949
        // Total TTC = 149.97 + 25.4949 = 175.4649
        $this->assertEquals('149.9700', $item->total_ht);
        $this->assertEquals('25.4949', $item->total_vat);
        $this->assertEquals('175.4649', $item->total_ttc);
    }

    public function test_invoice_item_is_sorted_by_sort_order(): void
    {
        $invoice = Invoice::factory()->create();

        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 2, 'title' => 'Second']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 0, 'title' => 'First']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 1, 'title' => 'Middle']);

        $items = $invoice->items()->orderBy('sort_order')->get();

        $this->assertEquals('First', $items[0]->title);
        $this->assertEquals('Middle', $items[1]->title);
        $this->assertEquals('Second', $items[2]->title);
    }
}
