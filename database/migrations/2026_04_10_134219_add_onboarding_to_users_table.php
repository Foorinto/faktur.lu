<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('onboarding_step')->nullable()->after('account_status');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->boolean('onboarding_skipped')->default(false)->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at', 'onboarding_skipped']);
        });
    }
};
