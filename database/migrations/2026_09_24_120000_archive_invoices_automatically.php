<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archivage automatique (FEAT-126) : où et quand la copie hors site d'une
 * archive est partie. Les archives elles-mêmes se rattrapent la nuit
 * (archive:catch-up), pas ici : Ghostscript facture par facture n'a rien à
 * faire dans une migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'archive_uploaded_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dateTime('archive_uploaded_at')->nullable();
                $table->string('archive_remote_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['archive_uploaded_at', 'archive_remote_path']);
        });
    }
};
