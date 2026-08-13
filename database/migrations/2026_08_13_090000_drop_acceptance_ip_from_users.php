<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retrait de l'adresse IP relevée à l'acceptation.
     *
     * L'article 28 exige un contrat « par écrit, y compris sous forme
     * électronique ». Il ne demande pas d'adresse IP, et celle-ci n'apportait
     * pas ce qu'on lui prêtait : en cas de contestation, ce qui pèse est
     * l'horodatage, l'identité du compte et son activité ultérieure. Une IP est
     * partagée, dynamique, ou derrière le NAT d'un opérateur — elle ne dit pas
     * qui était au clavier.
     *
     * Elle coûtait en revanche deux choses. C'est une donnée personnelle
     * (considérant 30 ; CJUE, Breyer), collectée pour documenter un accord de
     * protection des données — la minimisation de l'article 5.1.c s'en accommode
     * mal. Et elle vivait aussi longtemps que le compte, quand l'annexe 1 du DPA
     * annonce des journaux conservés trente jours : le document promettait une
     * durée que cette colonne ne tenait pas.
     *
     * La preuve repose désormais sur la date, la version acceptée et la voie
     * d'acceptation — ce qu'un auditeur demande réellement.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'acceptance_ip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('acceptance_ip');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'acceptance_ip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('acceptance_ip', 45)->nullable()->after('dpa_acceptance_method');
            });
        }
    }
};
