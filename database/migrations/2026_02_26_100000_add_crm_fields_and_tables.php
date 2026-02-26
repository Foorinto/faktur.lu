<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add CRM fields to clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->string('status')->default('active')->after('type');
            $table->string('source')->nullable()->after('status');
            $table->decimal('estimated_value', 12, 2)->nullable()->after('source');
            $table->timestamp('converted_at')->nullable()->after('estimated_value');
        });

        // Interactions table
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // note, call, email, meeting, other
            $table->text('content');
            $table->timestamp('contacted_at');
            $table->timestamps();

            $table->index(['client_id', 'contacted_at']);
        });

        // Reminders table
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('remind_at');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'remind_at', 'is_completed']);
        });

        // Tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Pivot table client_tag
        Schema::create('client_tag', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['client_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('interactions');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['status', 'source', 'estimated_value', 'converted_at']);
        });
    }
};
