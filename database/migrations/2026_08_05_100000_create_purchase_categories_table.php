<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégories de dépenses définies par l'utilisateur.
 *
 * Les neuf catégories du module Dépenses étaient figées dans le code et
 * orientées activité de services : ni loyer, ni charges locatives, ni assurance.
 * Une entreprise qui paie un loyer n'avait que « Autre ».
 *
 * ⚠️ Le nom `expense_categories` est déjà pris par les notes de frais RH
 * (2026_02_27_130000_create_expense_tables.php). D'où `purchase_categories`.
 *
 * ⚠️ Cette migration ne touche PAS la table `expenses`. La colonne
 * `expenses.category` continue de stocker une clé texte ; les neuf clés
 * existantes deviendront des lignes de cette table, provisionnées à la demande
 * (cf. PurchaseCategory::ensureDefaultsFor). Les dépenses déjà enregistrées
 * restent donc rattachées sans qu'un seul UPDATE ne soit exécuté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // La clé est ce que stocke expenses.category : elle ne change JAMAIS
            // une fois créée, sous peine de détacher l'historique. Le libellé,
            // lui, est librement modifiable.
            $table->string('key', 60);
            $table->string('label', 100);

            // Compte de charge du plan comptable normalisé (classe 6).
            // Facultatif : un indépendant au livre des recettes n'utilise pas le PCN.
            $table->string('pcn_account', 10)->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'key']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_categories');
    }
};
