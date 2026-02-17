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
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->char('locale', 2)->default('fr')->after('author_id');
            $table->index('locale');
            $table->index(['locale', 'status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['locale', 'status', 'published_at']);
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
    }
};
