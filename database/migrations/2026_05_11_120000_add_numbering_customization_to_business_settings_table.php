<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('number_format', 100)->default('{prefix}-{year}-{number}');
            $table->string('invoice_prefix', 20)->default('F');
            $table->string('credit_note_prefix', 20)->default('AV');
            $table->string('quote_prefix', 20)->default('DEV');
            $table->unsignedInteger('invoice_starting_number')->nullable();
            $table->unsignedInteger('credit_note_starting_number')->nullable();
            $table->unsignedInteger('quote_starting_number')->nullable();
            $table->unsignedTinyInteger('number_padding')->default(3);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->nullable()->after('number');
            $table->index(['user_id', 'type', 'sequence_number'], 'idx_invoices_user_type_sequence');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->nullable()->after('reference');
            $table->index(['user_id', 'sequence_number'], 'idx_quotes_user_sequence');
        });

        $this->backfillInvoiceSequenceNumbers();
        $this->backfillQuoteSequenceNumbers();

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarding_numbering_acknowledged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_numbering_acknowledged_at');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('idx_quotes_user_sequence');
            $table->dropColumn('sequence_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_user_type_sequence');
            $table->dropColumn('sequence_number');
        });

        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'number_format',
                'invoice_prefix',
                'credit_note_prefix',
                'quote_prefix',
                'invoice_starting_number',
                'credit_note_starting_number',
                'quote_starting_number',
                'number_padding',
            ]);
        });
    }

    /**
     * Extract the trailing digit run from existing invoice numbers and
     * write it back to `sequence_number` so the new generator can pick up
     * where the legacy hardcoded format (F-YYYY-NNN, AV-YYYY-NNN) left off.
     */
    private function backfillInvoiceSequenceNumbers(): void
    {
        $unparseable = 0;

        DB::table('invoices')
            ->whereNotNull('number')
            ->orderBy('id')
            ->chunkById(500, function ($invoices) use (&$unparseable) {
                foreach ($invoices as $invoice) {
                    if (preg_match('/(\d+)\s*$/', $invoice->number, $matches)) {
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['sequence_number' => (int) $matches[1]]);
                    } else {
                        $unparseable++;
                        Log::warning('numbering-backfill: could not extract sequence from invoice', [
                            'invoice_id' => $invoice->id,
                            'number' => $invoice->number,
                        ]);
                    }
                }
            });

        if ($unparseable > 0) {
            Log::info('numbering-backfill: invoices backfill complete with ' . $unparseable . ' unparseable entries');
        }
    }

    private function backfillQuoteSequenceNumbers(): void
    {
        $unparseable = 0;

        DB::table('quotes')
            ->whereNotNull('reference')
            ->orderBy('id')
            ->chunkById(500, function ($quotes) use (&$unparseable) {
                foreach ($quotes as $quote) {
                    if (preg_match('/(\d+)\s*$/', $quote->reference, $matches)) {
                        DB::table('quotes')
                            ->where('id', $quote->id)
                            ->update(['sequence_number' => (int) $matches[1]]);
                    } else {
                        $unparseable++;
                        Log::warning('numbering-backfill: could not extract sequence from quote', [
                            'quote_id' => $quote->id,
                            'reference' => $quote->reference,
                        ]);
                    }
                }
            });

        if ($unparseable > 0) {
            Log::info('numbering-backfill: quotes backfill complete with ' . $unparseable . ' unparseable entries');
        }
    }
};
