<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('paid_at');
            $table->string('archive_format', 20)->nullable()->after('archived_at'); // pdfa-1b, pdfa-3b, pdf
            $table->string('archive_checksum', 64)->nullable()->after('archive_format'); // SHA256
            $table->string('archive_path')->nullable()->after('archive_checksum');
            $table->timestamp('archive_expires_at')->nullable()->after('archive_path'); // 10 years retention

            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn([
                'archived_at',
                'archive_format',
                'archive_checksum',
                'archive_path',
                'archive_expires_at',
            ]);
        });
    }
};
