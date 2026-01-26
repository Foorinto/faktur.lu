<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertTimeToInvoiceAction
{
    /**
     * Convert time entries to a draft invoice.
     *
     * @param Collection|array $timeEntryIds
     * @param float $hourlyRate
     * @param int|null $vatRate
     * @param bool $groupByProject
     * @throws ValidationException
     */
    public function execute(
        Collection|array $timeEntryIds,
        float $hourlyRate,
        int $vatRate = 17,
        bool $groupByProject = true
    ): Invoice {
        $timeEntryIds = collect($timeEntryIds);

        if ($timeEntryIds->isEmpty()) {
            throw ValidationException::withMessages([
                'time_entries' => 'Veuillez sélectionner au moins une entrée de temps.',
            ]);
        }

        // Get time entries
        $entries = TimeEntry::whereIn('id', $timeEntryIds)
            ->unbilled()
            ->stopped()
            ->get();

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'time_entries' => 'Aucune entrée de temps valide trouvée.',
            ]);
        }

        // Verify all entries belong to the same client
        $clientIds = $entries->pluck('client_id')->unique();
        if ($clientIds->count() > 1) {
            throw ValidationException::withMessages([
                'time_entries' => 'Toutes les entrées doivent appartenir au même client.',
            ]);
        }

        $clientId = $clientIds->first();
        $client = Client::findOrFail($clientId);

        return DB::transaction(function () use ($entries, $client, $hourlyRate, $vatRate, $groupByProject) {
            // Create draft invoice
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'currency' => $client->currency ?? 'EUR',
                'status' => Invoice::STATUS_DRAFT,
                'notes' => 'Facture générée à partir du suivi du temps.',
            ]);

            if ($groupByProject) {
                // Group entries by project and create one line per project
                $byProject = $entries->groupBy(fn ($entry) => $entry->project_name ?? 'Sans projet');

                $sortOrder = 0;
                foreach ($byProject as $projectName => $projectEntries) {
                    $totalSeconds = $projectEntries->sum('duration_seconds');
                    $totalHours = round($totalSeconds / 3600, 2);

                    // Build description with date range
                    $dates = $projectEntries->pluck('started_at')->sort();
                    $dateRange = $dates->first()->format('d/m') . ' - ' . $dates->last()->format('d/m/Y');

                    $title = $projectName;
                    $description = $dateRange;
                    if ($projectEntries->count() > 1) {
                        $description .= " - {$projectEntries->count()} sessions";
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'title' => $title,
                        'description' => $description,
                        'quantity' => $totalHours,
                        'unit_price' => $hourlyRate,
                        'vat_rate' => $vatRate,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            } else {
                // Create one line per time entry
                $sortOrder = 0;
                foreach ($entries as $entry) {
                    $hours = round($entry->duration_seconds / 3600, 2);
                    $title = $entry->project_name ?: 'Prestation';
                    $description = $entry->started_at->format('d/m/Y')
                        . ($entry->description ? " - {$entry->description}" : '');

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'title' => $title,
                        'description' => $description,
                        'quantity' => $hours,
                        'unit_price' => $hourlyRate,
                        'vat_rate' => $vatRate,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            // Mark time entries as billed
            foreach ($entries as $entry) {
                $entry->markAsBilled($invoice);
            }

            return $invoice->load('items');
        });
    }
}
