<?php

namespace App\Actions;

use App\Models\BusinessSettings;
use App\Models\Quote;
use App\Services\DocumentNumberFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateQuoteNumberAction
{
    public const DEFAULT_PREFIX = 'DEV';

    /**
     * Generate the next quote reference for the given quote (or for a fresh one).
     * Quotes are numbered on creation (not finalisation), so the date used in the
     * formatter is the quote's intended creation date - defaulting to today.
     *
     * @return array{reference: string, sequence_number: int}
     */
    public function execute(?Quote $quote = null, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $settings = BusinessSettings::getInstance();

        return DB::transaction(function () use ($quote, $year, $settings) {
            $sequence = $this->nextSequence($year, $settings);

            $reference = DocumentNumberFormatter::format(
                $settings?->number_format ?? DocumentNumberFormatter::DEFAULT_TEMPLATE,
                [
                    'prefix' => $settings?->quote_prefix ?? self::DEFAULT_PREFIX,
                    'sequence' => $sequence,
                    'padding' => $settings?->number_padding ?? 3,
                    'date' => $this->resolveDate($quote, $year),
                    'client_name' => $quote?->client?->name,
                ],
            );

            return [
                'reference' => $reference,
                'sequence_number' => $sequence,
            ];
        });
    }

    /**
     * Preview the next reference without locking. Used by the create-page banner.
     */
    public function preview(?Quote $quote = null, ?int $year = null): string
    {
        $year = $year ?? now()->year;
        $settings = BusinessSettings::getInstance();

        $sequence = $this->nextSequence($year, $settings, lock: false);

        return DocumentNumberFormatter::format(
            $settings?->number_format ?? DocumentNumberFormatter::DEFAULT_TEMPLATE,
            [
                'prefix' => $settings?->quote_prefix ?? self::DEFAULT_PREFIX,
                'sequence' => $sequence,
                'padding' => $settings?->number_padding ?? 3,
                'date' => $this->resolveDate($quote, $year),
                'client_name' => $quote?->client?->name,
            ],
        );
    }

    private function nextSequence(int $year, ?BusinessSettings $settings, bool $lock = true): int
    {
        // BelongsToUser global scope filters by auth()->id() automatically.
        // We include trashed quotes because deleted references must not be reused.
        $query = Quote::query()
            ->withTrashed()
            ->whereYear('created_at', $year);

        if ($lock) {
            $query->lockForUpdate();
        }

        $maxSequence = (int) $query->max('sequence_number');

        if ($maxSequence > 0) {
            return $maxSequence + 1;
        }

        return $settings?->quote_starting_number ?? 1;
    }

    private function resolveDate(?Quote $quote, int $year): Carbon
    {
        if ($quote?->created_at) {
            return Carbon::parse($quote->created_at);
        }

        $now = Carbon::now();
        if ($now->year === $year) {
            return $now;
        }

        return Carbon::create($year, 1, 1);
    }
}
