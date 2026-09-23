<?php

use App\Security\AuditChain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Le journal d'audit devient démontrable (FEAT-125, étape 5 du plan).
 *
 * Deux colonnes sur chaque entrée : son empreinte et celle de l'entrée
 * précédente. Une table d'ancres pour que la chaîne reste vérifiable après
 * une purge de rétention, une table des exports hors site. Puis toutes les
 * entrées existantes sont scellées, dans l'ordre : à partir d'ici, tout
 * changement rétroactif se voit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Chaque étape est gardée : si le déploiement s'interrompt au milieu du
        // scellement, rejouer la migration reprend là où elle s'est arrêtée
        // au lieu d'échouer sur une colonne déjà présente.
        if (! Schema::hasColumn('audit_logs', 'hash')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->char('hash', 64)->nullable()->index();
                $table->char('previous_hash', 64)->nullable();
            });
        }

        // ⚠️ La clé étrangère vers users mettait user_id à NULL à la suppression
        // définitive d'un compte : une entrée scellée changeait de contenu et
        // la chaîne cassait, à chaque compte supprimé. Une entrée du journal
        // doit garder qui a agi, même si le compte n'existe plus : la colonne
        // et son index restent, la contrainte part.
        if ($this->contrainteUserId()) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        if (! Schema::hasTable('audit_chain_anchors')) {
            Schema::create('audit_chain_anchors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('last_pruned_id');
                $table->char('last_pruned_hash', 64);
                $table->unsignedBigInteger('pruned_count');
                $table->dateTime('pruned_at');
            });
        }

        if (! Schema::hasTable('audit_chain_exports')) {
            Schema::create('audit_chain_exports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('from_id');
                $table->unsignedBigInteger('to_id');
                $table->unsignedBigInteger('entries');
                $table->char('head_hash', 64);
                $table->string('file_name');
                $table->char('file_sha256', 64);
                $table->string('remote_path')->nullable();
                $table->boolean('encrypted')->default(false);
                $table->char('signature', 64);
                $table->dateTime('exported_at');
            });
        }

        $scellees = app(AuditChain::class)->sealPending();

        Log::info('Journal d\'audit scellé', ['entrees' => $scellees]);
    }

    /** La contrainte est-elle encore là ? (absente après une reprise, ou déjà retirée) */
    private function contrainteUserId(): bool
    {
        foreach (Schema::getForeignKeys('audit_logs') as $cle) {
            if (($cle['columns'] ?? []) === ['user_id']) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        // Remettre la contrainte suppose qu'aucune entrée ne pointe vers un
        // compte disparu entre-temps : on détache ces entrées d'abord.
        DB::table('audit_logs')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->select('id'))
            ->update(['user_id' => null]);

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::dropIfExists('audit_chain_exports');
        Schema::dropIfExists('audit_chain_anchors');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['hash']);
            $table->dropColumn(['hash', 'previous_hash']);
        });
    }
};
