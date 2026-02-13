<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'account_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('account_status')->default('trial')->after('is_active');
            });
        }

        // Update existing users based on their subscription/trial status
        // Use Laravel's query builder for database-agnostic queries
        $now = now()->toDateTimeString();

        // Users with active subscriptions → 'active'
        DB::table('users')
            ->whereNotNull('stripe_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.user_id', 'users.id')
                    ->where('subscriptions.stripe_status', 'active');
            })
            ->update(['account_status' => 'active']);

        // Users with trial_ends_at in the past and no subscription → 'expired'
        DB::table('users')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->where('account_status', 'trial')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.user_id', 'users.id')
                    ->where('subscriptions.stripe_status', 'active');
            })
            ->update(['account_status' => 'expired']);

        // Users with trial_ends_at in the future stay as 'trial' (default)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });
    }
};
