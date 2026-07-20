<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Widen invoices.number and quotes.reference from VARCHAR(20) to VARCHAR(191).
     *
     * Root cause of a production SQLSTATE[22001] "Data too long for column" (1406):
     * DocumentNumberFormatter accepts custom templates up to 100 chars and a
     * {client_name} slug up to 20 chars (e.g. "{prefix}-{year}-{month}-{client_name}-{number}"
     * => "F-2026-07-gams-asbl-001" = 23 chars), but the identifier columns were only
     * VARCHAR(20). Finalizing an invoice/quote with such a format truncated the value
     * and aborted the transaction.
     *
     * 191 is the largest length that keeps the composite unique index
     * (user_id, number) / (user_id, reference) under the 767-byte InnoDB index
     * prefix limit even on legacy row formats (191 * 4 bytes utf8mb4 = 764).
     *
     * Widening a VARCHAR is non-destructive and preserves existing indexes.
     * MySQL only: SQLite does not enforce VARCHAR length, so nothing to do there
     * (which is exactly why this bug was invisible in the SQLite test suite).
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Preserve nullability: invoices.number is nullable (null = draft),
        // quotes.reference is NOT NULL.
        DB::statement('ALTER TABLE `invoices` MODIFY `number` VARCHAR(191) NULL');
        DB::statement('ALTER TABLE `quotes` MODIFY `reference` VARCHAR(191) NOT NULL');
    }

    /**
     * No-op: narrowing back to VARCHAR(20) would truncate existing numbers/references
     * and is intentionally not reversed to avoid data loss.
     */
    public function down(): void
    {
        // Intentionally left blank (forward-only widening).
    }
};
