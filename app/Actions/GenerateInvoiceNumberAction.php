<?php

namespace App\Actions;

use App\Models\BusinessSettings;
use App\Models\Invoice;
use App\Services\DocumentNumberFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceNumberAction
{
    /** Legacy default prefix kept for backwards compatibility on accounts with no settings. */
    public const DEFAULT_PREFIX_INVOICE = 'F';

    /** Legacy default prefix for credit notes. */
    public const DEFAULT_PREFIX_CREDIT_NOTE = 'AV';

    /**
     * Generate the next invoice/credit note number for the given invoice.
     * The number is computed from the user's BusinessSettings (custom format + prefix +
     * starting_number + padding). The returned array contains both the rendered string
     * and the raw integer sequence so the caller can persist sequence_number too.
     *
     * @return array{number: string, sequence_number: int}
     */
    public function execute(?Invoice $invoice = null, ?int $year = null, ?string $type = null): array
    {
        $year = $year ?? now()->year;
        $type = $type ?? ($invoice?->type ?? Invoice::TYPE_INVOICE);
        $settings = BusinessSettings::getInstance();

        return DB::transaction(function () use ($invoice, $year, $type, $settings) {
            $sequence = $this->nextSequence($year, $type, $settings);

            // Filet de sécurité : des données historiques peuvent déjà porter le
            // numéro calculé (comptes créés avant l'alignement des années, imports
            // depuis un autre outil, numéros saisis à la main). On avance jusqu'à
            // un numéro réellement libre plutôt que d'échouer sur l'index unique
            // au moment de l'écriture — l'utilisateur voyait sinon une erreur
            // technique en fin de parcours, sans pouvoir rien y faire.
            $attempts = 0;
            do {
                $number = DocumentNumberFormatter::format(
                    $settings?->number_format ?? DocumentNumberFormatter::DEFAULT_TEMPLATE,
                    [
                        'prefix' => $this->prefixForType($type, $settings),
                        'sequence' => $sequence,
                        'padding' => $settings?->number_padding ?? 3,
                        'date' => $this->resolveDate($invoice, $year),
                        'client_name' => $invoice?->client?->name,
                    ],
                );

                $taken = Invoice::query()
                    ->withTrashed()
                    ->where('number', $number)
                    ->when($invoice?->exists, fn ($q) => $q->whereKeyNot($invoice->getKey()))
                    ->exists();

                if ($taken) {
                    $sequence++;
                }
            } while ($taken && ++$attempts < 1000);

            return [
                'number' => $number,
                'sequence_number' => $sequence,
            ];
        });
    }

    /**
     * Preview the next number without locking or committing anything.
     * Used by the create-page banner and by the live-preview in Settings.
     */
    public function preview(?Invoice $invoice = null, ?int $year = null, ?string $type = null): string
    {
        $year = $year ?? now()->year;
        $type = $type ?? ($invoice?->type ?? Invoice::TYPE_INVOICE);
        $settings = BusinessSettings::getInstance();

        $sequence = $this->nextSequence($year, $type, $settings, lock: false);

        return DocumentNumberFormatter::format(
            $settings?->number_format ?? DocumentNumberFormatter::DEFAULT_TEMPLATE,
            [
                'prefix' => $this->prefixForType($type, $settings),
                'sequence' => $sequence,
                'padding' => $settings?->number_padding ?? 3,
                'date' => $this->resolveDate($invoice, $year),
                'client_name' => $invoice?->client?->name,
            ],
        );
    }

    /**
     * Compute the next sequence number for (current user, type, year).
     * BelongsToUser global scope filters by auth()->id() automatically.
     */
    private function nextSequence(int $year, string $type, ?BusinessSettings $settings, bool $lock = true): int
    {
        // L'année du compteur doit être CELLE QUI S'AFFICHE dans le numéro, donc
        // celle de la date de facture (issued_at) — et non celle de finalisation.
        // Une facture datée 2026 mais finalisée en 2025 portait sinon un numéro
        // « 2026 » tout en étant comptée dans le compteur 2025 : l'année suivante,
        // le générateur ne la voyait pas et réattribuait son numéro.
        //
        // withTrashed() : l'index unique (user_id, number) ignore deleted_at, donc
        // une ligne supprimée conserve son numéro. Un numéro consommé ne doit
        // jamais être réémis. (GenerateQuoteNumberAction applique déjà cette règle.)
        $query = Invoice::query()
            ->withTrashed()
            ->where('type', $type)
            ->whereYear('issued_at', $year)
            ->whereNotNull('finalized_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        $maxSequence = (int) $query->max('sequence_number');

        if ($maxSequence > 0) {
            return $maxSequence + 1;
        }

        // No documents yet for this (user, type, year) - honour the configured
        // starting_number when migrating from another tool.
        return $this->startingNumberForType($type, $settings) ?? 1;
    }

    private function prefixForType(string $type, ?BusinessSettings $settings): string
    {
        return match ($type) {
            Invoice::TYPE_CREDIT_NOTE => $settings?->credit_note_prefix ?? self::DEFAULT_PREFIX_CREDIT_NOTE,
            default => $settings?->invoice_prefix ?? self::DEFAULT_PREFIX_INVOICE,
        };
    }

    private function startingNumberForType(string $type, ?BusinessSettings $settings): ?int
    {
        if (! $settings) {
            return null;
        }

        return match ($type) {
            Invoice::TYPE_CREDIT_NOTE => $settings->credit_note_starting_number,
            default => $settings->invoice_starting_number,
        };
    }

    /**
     * The date used by the format placeholders. We honour an explicit issued_at when
     * present on the invoice (so back-dating a finalisation still renders with the
     * correct {month}/{day}); otherwise we use today's date in the requested year.
     */
    private function resolveDate(?Invoice $invoice, int $year): Carbon
    {
        if ($invoice?->issued_at) {
            return Carbon::parse($invoice->issued_at);
        }

        $now = Carbon::now();
        if ($now->year === $year) {
            return $now;
        }

        return Carbon::create($year, 1, 1);
    }
}
