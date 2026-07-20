<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make clients.email nullable.
     *
     * Root cause of a production SQLSTATE[23000] 1048 "Column 'email' cannot be null":
     * ClientImportService already treats email as optional ('required' => false), and
     * many legitimate clients have no email (public administrations, postal-only
     * contacts, Peppol delivery). But the column was created NOT NULL, so importing or
     * creating such a client aborted with an integrity violation.
     *
     * Validation is relaxed to 'nullable' in Store/UpdateClientRequest and the
     * frontend email field is no longer 'required', so email is now optional end-to-end.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Not reversed: existing rows may now have NULL emails, and re-adding NOT NULL
        // would fail. Forward-only.
    }
};
