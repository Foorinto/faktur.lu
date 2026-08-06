<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remise permanente négociée avec un client (FEAT-108).
 *
 * Un accord commercial se négocie une fois et vaut pour toutes les factures
 * suivantes. Ces colonnes ne portent qu'une **valeur par défaut** : elle est
 * recopiée sur le document à sa création, puis vit sa vie. Modifier la remise
 * d'un client ne doit jamais déplacer un centime sur une facture déjà établie.
 *
 * Le format reprend celui des remises de document déjà en place
 * (`invoice_discounts`) : `percent` ou `amount`, valeur en décimal 12,4. Les
 * mêmes bornes s'appliqueront à la validation.
 *
 * `default_discount_value` à 0 signifie « aucune remise » : c'est la valeur par
 * défaut, et tous les clients existants la reçoivent sans changement de
 * comportement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Nullables à dessein : `ClientController` écrit `$request->validated()`
            // tel quel, et un champ laissé vide au formulaire arrive à `null`.
            // `defaultDiscountPayload()` traite `null` comme « aucune remise ».
            $table->string('default_discount_type', 10)->nullable()->default('percent')->after('default_vat_rate');
            $table->decimal('default_discount_value', 12, 4)->nullable()->default(0)->after('default_discount_type');

            // Libellé repris tel quel sur la facture. La remise figure sur un
            // document légal : le commerçant doit pouvoir en choisir les mots
            // (« Remise fidélité », « Accord-cadre 2026 »).
            $table->string('default_discount_label')->nullable()->after('default_discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'default_discount_type',
                'default_discount_value',
                'default_discount_label',
            ]);
        });
    }
};
