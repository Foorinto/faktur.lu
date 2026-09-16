<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des mouvements de stock (FEAT-116, lot 3).
 *
 * Le stock est un JOURNAL, pas un compteur. Le stock courant d'un produit est
 * la somme des quantités signées de ses mouvements. Une colonne `quantity`
 * mutable sur `products` serait plus simple et indéfendable : on ne saurait
 * jamais pourquoi le stock vaut ce qu'il vaut, ni qui l'a changé. Le reste de
 * l'application raisonne déjà ainsi (les encaissements sont un journal).
 *
 * `source_type`/`source_id` relient un mouvement à son origine (facture, note
 * de crédit, dépense) sans couplage dur : un mouvement manuel ou d'inventaire
 * n'a pas de source. `unit_cost` sert la valorisation au coût moyen pondéré.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Quantité SIGNÉE : positive pour une entrée, négative pour une
            // sortie. Le stock courant est leur somme.
            $table->decimal('quantity', 12, 4);

            // entree | sortie | ajustement | inventaire
            $table->string('type');

            // Origine facultative du mouvement (polymorphe léger, sans contrainte
            // FK : la source peut être supprimée, le mouvement historique reste).
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->date('date');

            // Coût unitaire HT à l'entrée, pour le coût moyen pondéré. Nul sur
            // une sortie (le coût sort au CMP courant, calculé, pas stocké).
            $table->decimal('unit_cost', 12, 4)->nullable();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'date']);
            $table->index(['user_id', 'product_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
