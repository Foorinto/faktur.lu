<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add the accountant portal feature (accounting_portal) to the free plan,
 * so free users can invite a fiduciary in read-only mode.
 *
 * Only the accountant PORTAL is granted; the Sage/CSV accounting_exports
 * remain Essentiel/Pro. The fiduciary on a free client can view data and
 * use the FAIA export (already free).
 *
 * Idempotent: skips if the feature is already present.
 */
return new class extends Migration
{
    public function up(): void
    {
        $plan = DB::table('plans')->where('name', 'free')->first();
        if (!$plan) {
            return;
        }

        $features = json_decode($plan->features, true) ?: [];
        if (!in_array('accounting_portal', $features, true)) {
            $features[] = 'accounting_portal';
            DB::table('plans')->where('id', $plan->id)->update([
                'features' => json_encode($features),
            ]);
        }
    }

    public function down(): void
    {
        $plan = DB::table('plans')->where('name', 'free')->first();
        if (!$plan) {
            return;
        }

        $features = json_decode($plan->features, true) ?: [];
        $features = array_values(array_filter($features, fn ($f) => $f !== 'accounting_portal'));
        DB::table('plans')->where('id', $plan->id)->update([
            'features' => json_encode($features),
        ]);
    }
};
