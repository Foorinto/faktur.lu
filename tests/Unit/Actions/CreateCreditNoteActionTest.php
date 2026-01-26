<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateCreditNoteAction;
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

    protected CreateCreditNoteAction $action;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CreateCreditNoteAction::class);

        BusinessSettings::factory()->create();

        $client = Client::factory()->create();

        $this->invoice = Invoice::factory()->finalized()->create([
            'client_id' => $client->id,
            'total_ht' => 200,
            'total_vat' => 34,
            'total_ttc' => 234,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Original service',
            'quantity' => 2,
            'unit_price' => 100,
            'vat_rate' => 17,
            'total_ht' => 200,
            'total_vat' => 34,
            'total_ttc' => 234,
        ]);
    }

    public function test_can_create_credit_note(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertEquals(Invoice::TYPE_CREDIT_NOTE, $creditNote->type);
        $this->assertEquals($this->invoice->id, $creditNote->credit_note_for);
    }

    public function test_credit_note_has_negative_amounts(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertLessThan(0, $creditNote->total_ht);
        $this->assertLessThan(0, $creditNote->total_vat);
        $this->assertLessThan(0, $creditNote->total_ttc);
    }

    public function test_credit_note_copies_items_with_negative_amounts(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertCount(1, $creditNote->items);

        $item = $creditNote->items->first();
        $this->assertEquals('Original service', $item->description);
        $this->assertLessThan(0, $item->quantity);
    }

    public function test_credit_note_has_same_client(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertEquals($this->invoice->client_id, $creditNote->client_id);
    }

    public function test_credit_note_is_draft_by_default(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertEquals(Invoice::STATUS_DRAFT, $creditNote->status);
        $this->assertNull($creditNote->number);
    }

    public function test_credit_note_can_be_finalized(): void
    {
        $creditNote = $this->action->execute($this->invoice, finalize: true);

        $this->assertEquals(Invoice::STATUS_FINALIZED, $creditNote->status);
        $this->assertNotNull($creditNote->number);
        $this->assertNotNull($creditNote->finalized_at);
        $this->assertNotNull($creditNote->seller_snapshot);
        $this->assertNotNull($creditNote->buyer_snapshot);
    }

    public function test_cannot_create_credit_note_for_draft_invoice(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'client_id' => $this->invoice->client_id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->expectException(ValidationException::class);

        $this->action->execute($draftInvoice);
    }

    public function test_cannot_create_credit_note_for_credit_note(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->expectException(ValidationException::class);

        $this->action->execute($creditNote);
    }

    public function test_finalized_credit_note_has_snapshots(): void
    {
        $creditNote = $this->action->execute($this->invoice, finalize: true);

        // Credit note should have valid snapshots when finalized
        $this->assertIsArray($creditNote->seller_snapshot);
        $this->assertIsArray($creditNote->buyer_snapshot);
        $this->assertNotEmpty($creditNote->seller_snapshot);
        $this->assertNotEmpty($creditNote->buyer_snapshot);
    }

    public function test_credit_note_references_original_invoice(): void
    {
        $creditNote = $this->action->execute($this->invoice);

        $this->assertEquals($this->invoice->id, $creditNote->credit_note_for);
        $this->assertInstanceOf(Invoice::class, $creditNote->originalInvoice);
        $this->assertEquals($this->invoice->id, $creditNote->originalInvoice->id);
    }
}
