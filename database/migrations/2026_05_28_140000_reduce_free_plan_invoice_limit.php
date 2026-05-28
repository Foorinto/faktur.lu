<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reduce the free plan monthly invoice limit from 10 to 5.
 * 10 was deemed too generous for the free tier.
 *
 * Only max_invoices_per_month changes; clients (10), quotes (5),
 * expenses (10) and other limits are left untouched.
 *
 * Idempotent: re-applying sets the same value.
 */
return new class extends Migration
{
    public function up(): void
    {
        $plan = DB::table('plans')->where('name', 'free')->first();
        if (!$plan) {
            return;
        }

        $limits = json_decode($plan->limits, true) ?: [];
        $limits['max_invoices_per_month'] = 5;

        DB::table('plans')->where('id', $plan->id)->update([
            'limits' => json_encode($limits),
        ]);
    }

    public function down(): void
    {
        $plan = DB::table('plans')->where('name', 'free')->first();
        if (!$plan) {
            return;
        }

        $limits = json_decode($plan->limits, true) ?: [];
        $limits['max_invoices_per_month'] = 10;

        DB::table('plans')->where('id', $plan->id)->update([
            'limits' => json_encode($limits),
        ]);
    }
};
