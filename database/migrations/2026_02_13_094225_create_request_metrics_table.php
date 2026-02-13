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
        if (Schema::hasTable('request_metrics')) {
            return;
        }

        Schema::create('request_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('method', 10);
            $table->unsignedInteger('response_time_ms');
            $table->unsignedSmallInteger('memory_usage_mb');
            $table->unsignedSmallInteger('query_count');
            $table->unsignedInteger('query_time_ms');
            $table->unsignedSmallInteger('status_code');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');

            $table->index('created_at');
            $table->index(['url', 'created_at']);
            $table->index('status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_metrics');
    }
};
