<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de stock activable par produit (FEAT-116, lot 2).
 *
 * Le suivi est explicite et désactivé par défaut : la grande majorité des
 * articles (prestations de service surtout) n'a pas de stock. Seuls les
 * articles de type `product` ont vocation à être suivis — la règle est
 * appliquée dans le formulaire et le modèle, pas au niveau du schéma.
 *
 * `stock_alert_threshold` est nullable : sans seuil, aucune alerte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_stock')->default(false)->after('unit');
            $table->decimal('stock_alert_threshold', 12, 4)->nullable()->after('track_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'stock_alert_threshold']);
        });
    }
};
