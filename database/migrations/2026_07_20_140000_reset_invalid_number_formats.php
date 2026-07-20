<?php

use App\Services\DocumentNumberFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reset any invalid custom numbering format to the default template.
     *
     * Legacy accounts could have saved a format with unsupported placeholders
     * (e.g. "INV-{YYYY}-{####}") before validation was enforced. Such a format
     * renders to a constant string → every document gets the same number →
     * duplicate-key crash (SQLSTATE 23000) on the second document. This cleans
     * those up. The formatter also has a runtime safety net, but fixing the stored
     * data restores clean, sequential numbers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_settings') || ! Schema::hasColumn('business_settings', 'number_format')) {
            return;
        }

        DB::table('business_settings')
            ->whereNotNull('number_format')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $errors = DocumentNumberFormatter::validateTemplate((string) $row->number_format);
                    if (! empty($errors)) {
                        DB::table('business_settings')
                            ->where('id', $row->id)
                            ->update(['number_format' => DocumentNumberFormatter::DEFAULT_TEMPLATE]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Not reversible: previous invalid values are intentionally not restored.
    }
};
