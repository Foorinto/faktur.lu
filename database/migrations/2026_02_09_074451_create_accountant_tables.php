<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table des comptables (utilisateurs comptables)
        Schema::create('accountants', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        // Table des invitations
        Schema::create('accountant_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending'); // pending, accepted, revoked, expired
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Table pivot accountant_user (relation many-to-many)
        Schema::create('accountant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accountant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('active'); // active, revoked
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['accountant_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        // Table des téléchargements (audit)
        Schema::create('accountant_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accountant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('export_type'); // faia, excel, pdf_archive
            $table->string('period'); // 2026, 2026-Q1, 2026-01
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['accountant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountant_downloads');
        Schema::dropIfExists('accountant_user');
        Schema::dropIfExists('accountant_invitations');
        Schema::dropIfExists('accountants');
    }
};
