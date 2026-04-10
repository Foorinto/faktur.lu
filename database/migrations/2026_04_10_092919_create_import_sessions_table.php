<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('clients'); // clients, products, expenses, etc.
            $table->string('filename');
            $table->string('storage_path');
            $table->json('headers')->nullable(); // colonnes du fichier
            $table->json('mapping')->nullable(); // mapping colonnes -> champs
            $table->json('preview_data')->nullable(); // 5 premières lignes pour aperçu
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->integer('imported_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->string('duplicate_strategy')->default('skip'); // skip, update, create
            $table->string('status')->default('uploaded'); // uploaded, mapping, preview, processing, completed, failed
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
