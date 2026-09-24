<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forme d'exercice et mentions légales obligatoires (FEAT-133).
 *
 * RCS et autorisation d'établissement étaient facultatifs pour tout le
 * monde. Or une société ou un commerçant doit les imprimer sur ses factures,
 * alors qu'une profession libérale n'a ni l'un ni parfois l'autre. La forme
 * d'exercice (société, indépendant commerçant ou artisan, profession
 * libérale ou activité non commerciale) pilote désormais ce qui est exigé ;
 * la case « mon activité ne relève pas de l'autorisation » couvre les
 * professions réglementées par ailleurs et les activités non commerciales.
 *
 * Colonnes nulles par défaut : les comptes existants ne sont pas bloqués,
 * ils voient un rappel jusqu'à ce qu'ils répondent à la question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('business_settings', 'exercise_form')) {
                $table->string('exercise_form', 20)->nullable()->after('country_code');
            }
            if (! Schema::hasColumn('business_settings', 'no_establishment_authorization')) {
                $table->boolean('no_establishment_authorization')->default(false)->after('establishment_authorization');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            foreach (['exercise_form', 'no_establishment_authorization'] as $column) {
                if (Schema::hasColumn('business_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
