<?php

namespace Tests\Unit\Actions;

use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalizeInvoiceActionTest extends TestCase
{
    use RefreshDatabase;

    protected FinalizeInvoiceAction $action;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(FinalizeInvoiceAction::class);

        BusinessSettings::factory()->create([
            'company_name' => 'Test Company SARL',
            'matricule' => '12345678901',
            'vat_number' => 'LU12345678',
        ]);

        $client = Client::factory()->create([
            'name' => 'Test Client',
            'vat_number' => 'LU87654321',
        ]);

        $this->invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);
    }

    public function test_can_finalize_draft_invoice(): void
    {
        $result = $this->action->execute($this->invoice);

        $this->assertEquals(Invoice::STATUS_FINALIZED, $result->status);
        $this->assertNotNull($result->number);
        $this->assertNotNull($result->finalized_at);
    }

    public function test_generates_invoice_number(): void
    {
        $result = $this->action->execute($this->invoice);

        $year = now()->year;
        $this->assertMatchesRegularExpression("/^FAC-$year-\d{3}$/", $result->number);
    }

    public function test_creates_seller_snapshot(): void
    {
        $result = $this->action->execute($this->invoice);

        $this->assertIsArray($result->seller_snapshot);
        $this->assertEquals('Test Company SARL', $result->seller_snapshot['company_name']);
        $this->assertEquals('12345678901', $result->seller_snapshot['matricule']);
    }

    public function test_creates_buyer_snapshot(): void
    {
        $result = $this->action->execute($this->invoice);

        $this->assertIsArray($result->buyer_snapshot);
        $this->assertEquals('Test Client', $result->buyer_snapshot['name']);
    }

    public function test_sets_issued_at_date(): void
    {
        $result = $this->action->execute($this->invoice);

        $this->assertNotNull($result->issued_at);
        $this->assertEquals(now()->format('Y-m-d'), $result->issued_at->format('Y-m-d'));
    }

    public function test_sets_due_at_date(): void
    {
        $result = $this->action->execute($this->invoice);

        $this->assertNotNull($result->due_at);
        $this->assertEquals(now()->addDays(30)->format('Y-m-d'), $result->due_at->format('Y-m-d'));
    }

    public function test_can_set_custom_issued_at_date(): void
    {
        $customDate = '2024-06-15';
        $result = $this->action->execute($this->invoice, $customDate);

        $this->assertEquals($customDate, $result->issued_at->format('Y-m-d'));
        $this->assertEquals('2024-07-15', $result->due_at->format('Y-m-d'));
    }

    public function test_respects_custom_due_at_date(): void
    {
        $this->invoice->update(['due_at' => '2024-08-01']);

        $result = $this->action->execute($this->invoice, '2024-06-15');

        $this->assertEquals('2024-08-01', $result->due_at->format('Y-m-d'));
    }

    public function test_cannot_finalize_invoice_without_items(): void
    {
        $this->invoice->items()->delete();

        $this->expectException(ValidationException::class);

        $this->action->execute($this->invoice);
    }

    public function test_cannot_finalize_already_finalized_invoice(): void
    {
        $this->action->execute($this->invoice);

        $this->expectException(ValidationException::class);

        $this->action->execute($this->invoice);
    }

    public function test_calculates_totals_on_finalize(): void
    {
        $result = $this->action->execute($this->invoice);

        // 2 * 100 = 200 HT
        // 200 * 0.17 = 34 VAT
        // 200 + 34 = 234 TTC
        $this->assertEquals('200.0000', $result->total_ht);
        $this->assertEquals('34.0000', $result->total_vat);
        $this->assertEquals('234.0000', $result->total_ttc);
    }

    public function test_cannot_finalize_without_business_settings(): void
    {
        BusinessSettings::query()->delete();

        $this->expectException(ValidationException::class);

        $this->action->execute($this->invoice);
    }
}
