<?php

namespace Tests\Unit;

use App\Actions\CreateCreditNoteAction;
use App\Actions\FinalizeInvoiceAction;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateCreditNoteActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateCreditNoteAction $action;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create business settings
        BusinessSettings::factory()->assujetti()->create();

        // Create a finalized invoice
        $client = Client::factory()->create();
        $this->invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'type' => Invoice::TYPE_INVOICE,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => 'FAC-2026-001',
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
        ]);

        // Add items
        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'title' => 'Service A',
            'quantity' => 10,
            'unit_price' => 50,
            'vat_rate' => 17,
            'total_ht' => 500,
            'total_vat' => 85,
            'total_ttc' => 585,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'title' => 'Service B',
            'quantity' => 10,
            'unit_price' => 50,
            'vat_rate' => 17,
            'total_ht' => 500,
            'total_vat' => 85,
            'total_ttc' => 585,
        ]);

        $this->invoice->load('items');

        $this->action = app(CreateCreditNoteAction::class);
    }

    public function test_creates_full_credit_note(): void
    {
        $creditNote = $this->action->execute($this->invoice, 'cancellation');

        $this->assertEquals(Invoice::TYPE_CREDIT_NOTE, $creditNote->type);
        $this->assertEquals(Invoice::STATUS_DRAFT, $creditNote->status);
        $this->assertEquals($this->invoice->id, $creditNote->credit_note_for);
        $this->assertEquals('cancellation', $creditNote->credit_note_reason);
        $this->assertCount(2, $creditNote->items);

        // Check that quantities are negative
        foreach ($creditNote->items as $item) {
            $this->assertLessThan(0, $item->quantity);
        }
    }

    public function test_creates_partial_credit_note(): void
    {
        $itemId = $this->invoice->items->first()->id;

        $creditNote = $this->action->execute($this->invoice, 'billing_error', [$itemId]);

        $this->assertEquals(Invoice::TYPE_CREDIT_NOTE, $creditNote->type);
        $this->assertEquals('billing_error', $creditNote->credit_note_reason);
        $this->assertCount(1, $creditNote->items);
        $this->assertStringContainsString('partiel', $creditNote->notes);
    }

    public function test_cannot_create_credit_note_for_draft_invoice(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'status' => Invoice::STATUS_DRAFT,
            'type' => Invoice::TYPE_INVOICE,
        ]);

        $this->expectException(ValidationException::class);
        $this->action->execute($draftInvoice);
    }

    public function test_cannot_create_credit_note_for_credit_note(): void
    {
        $creditNote = Invoice::factory()->create([
            'status' => Invoice::STATUS_FINALIZED,
            'type' => Invoice::TYPE_CREDIT_NOTE,
        ]);

        $this->expectException(ValidationException::class);
        $this->action->execute($creditNote);
    }

    public function test_cannot_create_second_credit_note_for_same_invoice(): void
    {
        // Create first credit note
        $this->action->execute($this->invoice, 'cancellation');

        // Try to create second - should fail
        $this->expectException(ValidationException::class);
        $this->action->execute($this->invoice, 'other');
    }

    public function test_credit_note_copies_vat_mention_from_original(): void
    {
        // Update original invoice with VAT mention
        Invoice::withoutEvents(function () {
            $this->invoice->update([
                'vat_mention' => 'reverse_charge',
            ]);
        });

        $creditNote = $this->action->execute($this->invoice->refresh());

        $this->assertEquals('reverse_charge', $creditNote->vat_mention);
    }
}
