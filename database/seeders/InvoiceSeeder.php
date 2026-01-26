<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        if ($clients->isEmpty()) {
            $this->command->warn('No clients found. Please run ClientSeeder first.');
            return;
        }

        // Create draft invoices
        foreach ($clients->random(min(2, $clients->count())) as $client) {
            $invoice = Invoice::factory()->create([
                'client_id' => $client->id,
            ]);

            InvoiceItem::factory()->count(rand(1, 4))->create([
                'invoice_id' => $invoice->id,
            ]);

            $this->recalculateTotals($invoice);
        }

        // Create finalized invoices
        foreach ($clients->random(min(3, $clients->count())) as $client) {
            $invoice = Invoice::factory()->finalized()->create([
                'client_id' => $client->id,
            ]);

            InvoiceItem::factory()->count(rand(2, 5))->create([
                'invoice_id' => $invoice->id,
            ]);

            $this->recalculateTotals($invoice);
        }

        // Create sent invoices
        foreach ($clients->random(min(2, $clients->count())) as $client) {
            $invoice = Invoice::factory()->sent()->create([
                'client_id' => $client->id,
            ]);

            InvoiceItem::factory()->count(rand(1, 3))->create([
                'invoice_id' => $invoice->id,
            ]);

            $this->recalculateTotals($invoice);
        }

        // Create paid invoices
        foreach ($clients->random(min(4, $clients->count())) as $client) {
            $invoice = Invoice::factory()->paid()->create([
                'client_id' => $client->id,
            ]);

            InvoiceItem::factory()->count(rand(2, 6))->create([
                'invoice_id' => $invoice->id,
            ]);

            $this->recalculateTotals($invoice);
        }

        $this->command->info('Invoices seeded successfully.');
    }

    /**
     * Recalculate invoice totals from items.
     */
    private function recalculateTotals(Invoice $invoice): void
    {
        $items = $invoice->items()->get();

        $totalHt = '0';
        $totalVat = '0';
        $totalTtc = '0';

        foreach ($items as $item) {
            $totalHt = bcadd($totalHt, (string) $item->total_ht, 4);
            $totalVat = bcadd($totalVat, (string) $item->total_vat, 4);
            $totalTtc = bcadd($totalTtc, (string) $item->total_ttc, 4);
        }

        Invoice::withoutEvents(function () use ($invoice, $totalHt, $totalVat, $totalTtc) {
            $invoice->update([
                'total_ht' => $totalHt,
                'total_vat' => $totalVat,
                'total_ttc' => $totalTtc,
            ]);
        });
    }
}
