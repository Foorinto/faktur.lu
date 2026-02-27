<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('onboarding_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_template_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->integer('due_offset_days')->nullable();
            $table->unsignedInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_template_items');
        Schema::dropIfExists('onboarding_templates');
    }
};
