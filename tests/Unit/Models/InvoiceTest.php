<?php

namespace Tests\Unit\Models;

use App\Exceptions\ImmutableInvoiceException;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($client->id, $invoice->client->id);
    }

    public function test_invoice_has_many_items(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(3, $invoice->items);
    }

    public function test_invoice_is_mutable_when_draft(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);

        $invoice->notes = 'Updated notes';
        $invoice->save();

        $this->assertEquals('Updated notes', $invoice->fresh()->notes);
    }

    public function test_invoice_is_immutable_when_finalized(): void
    {
        $invoice = Invoice::factory()->finalized()->create();

        $this->expectException(ImmutableInvoiceException::class);

        $invoice->notes = 'Try to update';
        $invoice->save();
    }

    public function test_invoice_status_can_change_when_finalized(): void
    {
        $invoice = Invoice::factory()->finalized()->create();

        $invoice->status = Invoice::STATUS_SENT;
        $invoice->sent_at = now();
        $invoice->save();

        $this->assertEquals(Invoice::STATUS_SENT, $invoice->fresh()->status);
    }

    public function test_invoice_cannot_be_deleted_when_finalized(): void
    {
        $invoice = Invoice::factory()->finalized()->create();

        $this->expectException(ImmutableInvoiceException::class);

        $invoice->delete();
    }

    public function test_invoice_can_be_deleted_when_draft(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);
        $invoiceId = $invoice->id;

        $invoice->delete();

        $this->assertNull(Invoice::find($invoiceId));
    }

    public function test_is_immutable_returns_correct_value(): void
    {
        $draft = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);
        $finalized = Invoice::factory()->finalized()->create();
        $sent = Invoice::factory()->sent()->create();
        $paid = Invoice::factory()->paid()->create();

        $this->assertFalse($draft->isImmutable());
        $this->assertTrue($finalized->isImmutable());
        $this->assertTrue($sent->isImmutable());
        $this->assertTrue($paid->isImmutable());
    }

    public function test_invoice_casts_dates_correctly(): void
    {
        $invoice = Invoice::factory()->finalized()->create([
            'issued_at' => '2024-06-15',
            'due_at' => '2024-07-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->issued_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->due_at);
        $this->assertEquals('2024-06-15', $invoice->issued_at->format('Y-m-d'));
        $this->assertEquals('2024-07-15', $invoice->due_at->format('Y-m-d'));
    }

    public function test_invoice_casts_snapshots_to_array(): void
    {
        $sellerSnapshot = ['company_name' => 'Test Co', 'matricule' => '12345678901'];
        $buyerSnapshot = ['name' => 'Client Name', 'vat_number' => 'LU12345678'];

        $invoice = Invoice::factory()->finalized()->create([
            'seller_snapshot' => $sellerSnapshot,
            'buyer_snapshot' => $buyerSnapshot,
        ]);

        $this->assertIsArray($invoice->seller_snapshot);
        $this->assertIsArray($invoice->buyer_snapshot);
        $this->assertEquals('Test Co', $invoice->seller_snapshot['company_name']);
        $this->assertEquals('Client Name', $invoice->buyer_snapshot['name']);
    }

    public function test_invoice_soft_deletes(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_DRAFT]);

        $invoice->delete();

        $this->assertSoftDeleted($invoice);
        $this->assertNotNull(Invoice::withTrashed()->find($invoice->id));
    }

    public function test_credit_note_belongs_to_original_invoice(): void
    {
        $originalInvoice = Invoice::factory()->finalized()->create();
        $creditNote = Invoice::factory()->creditNote()->create([
            'credit_note_for' => $originalInvoice->id,
        ]);

        $this->assertInstanceOf(Invoice::class, $creditNote->originalInvoice);
        $this->assertEquals($originalInvoice->id, $creditNote->originalInvoice->id);
    }

    public function test_invoice_has_credit_notes_relation(): void
    {
        $invoice = Invoice::factory()->finalized()->create();

        // Create credit notes with unique numbers
        Invoice::factory()->create([
            'credit_note_for' => $invoice->id,
            'client_id' => $invoice->client_id,
            'type' => Invoice::TYPE_CREDIT_NOTE,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        Invoice::factory()->create([
            'credit_note_for' => $invoice->id,
            'client_id' => $invoice->client_id,
            'type' => Invoice::TYPE_CREDIT_NOTE,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->assertCount(2, $invoice->creditNotes);
    }
}
