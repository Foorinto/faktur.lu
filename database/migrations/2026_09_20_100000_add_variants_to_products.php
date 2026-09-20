<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variantes d'article : nuances, tailles, coloris, formats (FEAT-120).
 *
 * Une variante EST un article, avec un parent. Quatre tables pointent déjà vers
 * `products.id` — lignes de facture, lignes de devis, lignes de dépense et
 * mouvements de stock : aucune ne change, puisqu'une variante est un article
 * comme un autre. Une table séparée aurait imposé une clé étrangère de plus
 * dans chacune, et un identifiant de variante à faire remonter dans les exports.
 *
 * ⚠️ `restrictOnDelete` : on refuse de supprimer une famille tant qu'elle porte
 * des variantes. Une cascade effacerait leurs mouvements de stock, c'est-à-dire
 * des écritures qui justifient un inventaire. Le contrôleur propose de détacher
 * les variantes plutôt que de les emporter.
 *
 * Purement additive : un article existant porte `parent_id` à null et se
 * comporte exactement comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('products')->restrictOnDelete();

            // Le libellé de la variante : « nuance 12 », « L », « magnum ».
            $table->string('variant_label')->nullable()->after('designation');

            // L'axe porté par la famille : « Nuance », « Taille », « Format ».
            // Sans lui, l'interface dirait « Variante » partout, ce qui ne veut
            // rien dire pour personne.
            $table->string('variant_axis_label')->nullable()->after('variant_label');

            // Des tailles se lisent S, M, L, XL. Par ordre alphabétique on
            // obtiendrait L, M, S, XL. L'ordre est donc voulu, pas déduit.
            $table->unsignedInteger('sort_order')->default(0)->after('variant_axis_label');

            // La recherche des documents filtre sur `parent_id` à chaque frappe.
            $table->index(['user_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'parent_id']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['variant_label', 'variant_axis_label', 'sort_order']);
        });
    }
};
