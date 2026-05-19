<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->enum('member_type', ['collaborator', 'employee_account'])
                ->default('collaborator')
                ->after('user_id');
            $table->string('invited_email', 255)->nullable()->after('member_type');
            $table->timestamp('invited_at')->nullable()->after('invited_email');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');

            $table->index(['project_id', 'member_type']);
        });
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'member_type']);
            $table->dropColumn(['member_type', 'invited_email', 'invited_at', 'accepted_at']);
        });
    }
};
