<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Secteur d'activité déclaré par l'utilisateur.
 *
 * Sert d'abord à MESURER : avant de construire un pack métier — celui de la
 * santé demanderait treize jours — on veut savoir qui s'inscrit réellement.
 * Une colonne et un écran coûtent deux jours, et répondent à la question que
 * personne ne peut trancher autrement.
 *
 * À ne pas confondre avec `business_settings.activity_type`, qui vaut
 * services / goods / mixed et ne sert qu'aux seuils de franchise français.
 *
 * Nullable à dessein : il faut pouvoir distinguer « n'a pas été interrogé » —
 * tous les comptes antérieurs — de « a répondu Autre ». Confondre les deux
 * fausserait la mesure dès le premier jour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_sector', 30)->nullable()->after('onboarding_step');
            $table->timestamp('business_sector_set_at')->nullable()->after('business_sector');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['business_sector', 'business_sector_set_at']);
        });
    }
};
