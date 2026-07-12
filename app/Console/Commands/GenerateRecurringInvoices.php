<?php

namespace App\Console\Commands;

use App\Actions\FinalizeInvoiceAction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Services\InvoiceEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'recurring:generate';
    protected $description = 'Génère les factures récurrentes dues';

    public function handle(FinalizeInvoiceAction $finalizeAction): int
    {
        $recurringInvoices = RecurringInvoice::with(['items', 'client', 'user'])
            ->due()
            ->get();

        $generated = 0;
        $errors = 0;

        foreach ($recurringInvoices as $recurring) {
            try {
                $invoice = $this->createInvoice($recurring);

                if ($recurring->auto_finalize) {
                    $finalizeAction->execute($invoice);

                    if ($recurring->auto_send && $recurring->client->email) {
                        $this->sendInvoice($invoice, $recurring);
                    }
                }

                $recurring->update(['last_invoice_id' => $invoice->id]);
                $recurring->advanceToNextDate();

                $generated++;
                $this->info("Facture créée pour {$recurring->client->name} (récurrence #{$recurring->id})");
                Log::info("Recurring invoice #{$recurring->id}: invoice #{$invoice->id} created for {$recurring->client->name}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("Erreur récurrence #{$recurring->id}: {$e->getMessage()}");
                Log::error("Recurring invoice #{$recurring->id} failed: {$e->getMessage()}");
            }
        }

        $this->info("Terminé : {$generated} factures générées, {$errors} erreurs.");

        return 0;
    }

    protected function createInvoice(RecurringInvoice $recurring): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $recurring->client_id,
            'title' => $recurring->title,
            'currency' => $recurring->currency,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays($recurring->payment_delay_days)->toDateString(),
            'notes' => $recurring->notes,
            'vat_mention' => $recurring->vat_mention,
            'custom_vat_mention' => $recurring->custom_vat_mention,
            'footer_message' => $recurring->footer_message,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Ensure the invoice belongs to the correct user
        $invoice->forceFill(['user_id' => $recurring->user_id])->save();

        foreach ($recurring->items as $item) {
            $totalHt = round($item->quantity * $item->unit_price, 4);
            $totalVat = round($totalHt * $item->vat_rate / 100, 4);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type,
                'discount_value' => $item->discount_value,
                'vat_rate' => $item->vat_rate,
                'total_ht' => $totalHt,
                'total_vat' => $totalVat,
                'total_ttc' => $totalHt + $totalVat,
                'sort_order' => $item->sort_order,
            ]);
        }

        // Copy global discounts from the recurring template
        foreach ($recurring->discounts as $discount) {
            $invoice->discounts()->create([
                'label' => $discount->label,
                'type' => $discount->type,
                'value' => $discount->value,
                'sort_order' => $discount->sort_order,
            ]);
        }

        // Recalculate totals (global discounts ventilated per VAT rate)
        $invoice->load('items', 'discounts');
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);
        $invoice->update([
            'total_ht' => $totals['total_ht'],
            'total_vat' => $totals['total_vat'],
            'total_ttc' => $totals['total_ttc'],
        ]);

        return $invoice->fresh();
    }

    protected function sendInvoice(Invoice $invoice, RecurringInvoice $recurring): void
    {
        try {
            $emailService = app(InvoiceEmailService::class);
            $emailService->sendInvoice($invoice, $recurring->client->email);
        } catch (\Exception $e) {
            Log::warning("Recurring #{$recurring->id}: auto-send failed: {$e->getMessage()}");
        }
    }
}
