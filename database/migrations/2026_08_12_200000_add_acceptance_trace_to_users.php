<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trace des acceptations contractuelles.
     *
     * La case des conditions générales était validée à l'inscription
     * (`required|accepted`) puis oubliée : aucune trace ne subsistait de ce qui
     * avait été accepté, ni quand, ni dans quelle version. C'est pourtant la
     * première pièce qu'on réclame en cas de litige, et celle qu'un DPO demande.
     *
     * L'adresse IP et l'horodatage font la valeur probante de l'acceptation :
     * sans eux, il ne reste qu'une affirmation.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->timestamp('dpa_accepted_at')->nullable()->after('terms_accepted_at');
            $table->string('dpa_version', 10)->nullable()->after('dpa_accepted_at');
            $table->string('acceptance_ip', 45)->nullable()->after('dpa_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'dpa_accepted_at', 'dpa_version', 'acceptance_ip']);
        });
    }
};
