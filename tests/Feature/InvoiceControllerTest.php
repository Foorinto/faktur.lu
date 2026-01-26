<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();

        // Create business settings for finalization
        BusinessSettings::factory()->create();
    }

    public function test_guest_cannot_access_invoices(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_invoices_index(): void
    {
        Invoice::factory()->count(3)->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/Index')
                ->has('invoices.data', 3)
            );
    }

    public function test_user_can_filter_invoices_by_status(): void
    {
        Invoice::factory()->create(['client_id' => $this->client->id, 'status' => Invoice::STATUS_DRAFT]);
        Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);
        Invoice::factory()->paid()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->get(route('invoices.index', ['status' => 'paid']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('invoices.data', 1)
            );
    }

    public function test_user_can_view_create_invoice_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/Create')
                ->has('clients')
                ->has('vatRates')
            );
    }

    public function test_user_can_create_draft_invoice(): void
    {
        $this->actingAs($this->user)
            ->post(route('invoices.store'), [
                'client_id' => $this->client->id,
                'items' => [
                    [
                        'title' => 'Service 1',
                        'quantity' => 2,
                        'unit_price' => 100,
                        'vat_rate' => 17,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'title' => 'Service 1',
        ]);
    }

    public function test_user_can_create_draft_invoice_without_items(): void
    {
        // Drafts can be created without items (items can be added later)
        $this->actingAs($this->user)
            ->post(route('invoices.store'), [
                'client_id' => $this->client->id,
                'items' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
    }

    public function test_user_can_view_draft_invoice_edit(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user)
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/Edit')
                ->has('invoice')
            );
    }

    public function test_user_cannot_edit_finalized_invoice(): void
    {
        $invoice = Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->get(route('invoices.edit', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));
    }

    public function test_user_can_update_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), [
                'client_id' => $this->client->id,
                'notes' => 'Updated notes',
                'items' => [
                    [
                        'description' => 'Updated service',
                        'quantity' => 3,
                        'unit_price' => 150,
                        'vat_rate' => 17,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_can_view_finalized_invoice(): void
    {
        $invoice = Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);
        InvoiceItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoices/Show')
                ->has('invoice')
                ->has('invoice.items', 2)
            );
    }

    public function test_user_can_finalize_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user)
            ->post(route('invoices.finalize', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->number);
        $this->assertNotNull($invoice->seller_snapshot);
        $this->assertNotNull($invoice->buyer_snapshot);
    }

    public function test_user_cannot_finalize_invoice_without_items(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user)
            ->post(route('invoices.finalize', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->status);
    }

    public function test_user_can_mark_invoice_as_sent(): void
    {
        $invoice = Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->post(route('invoices.mark-sent', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_SENT, $invoice->status);
        $this->assertNotNull($invoice->sent_at);
    }

    public function test_user_can_mark_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->sent()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->post(route('invoices.mark-paid', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_user_can_create_credit_note(): void
    {
        $invoice = Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);
        InvoiceItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user)
            ->post(route('invoices.credit-note', $invoice))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'credit_note_for' => $invoice->id,
            'type' => Invoice::TYPE_CREDIT_NOTE,
        ]);

        // Verify credit note has negative amounts
        $creditNote = Invoice::where('credit_note_for', $invoice->id)->first();
        $this->assertLessThan(0, $creditNote->total_ttc);
    }

    public function test_user_can_delete_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->user)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSoftDeleted($invoice);
    }

    public function test_user_cannot_delete_finalized_invoice(): void
    {
        $invoice = Invoice::factory()->finalized()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'deleted_at' => null]);
    }

    public function test_invoice_number_is_sequential(): void
    {
        $invoice1 = Invoice::factory()->create(['client_id' => $this->client->id]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice1->id]);

        $invoice2 = Invoice::factory()->create(['client_id' => $this->client->id]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice2->id]);

        $this->actingAs($this->user)->post(route('invoices.finalize', $invoice1));
        $this->actingAs($this->user)->post(route('invoices.finalize', $invoice2));

        $invoice1->refresh();
        $invoice2->refresh();

        $year = now()->year;
        $this->assertEquals("$year-001", $invoice1->number);
        $this->assertEquals("$year-002", $invoice2->number);
    }
}
