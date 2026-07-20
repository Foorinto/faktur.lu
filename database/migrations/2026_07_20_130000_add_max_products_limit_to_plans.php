<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the `max_products` limit to existing plans (FEAT-095).
     * Free = 10 catalogue items, Essentiel & Pro = unlimited (null).
     * Deploy only runs `migrate` (not the seeder), so existing plan rows are
     * patched here; PlansSeeder carries the same values for fresh installs.
     */
    public function up(): void
    {
        $this->setLimit('free', 10);
        $this->setLimit('essentiel', null);
        $this->setLimit('pro', null);
    }

    public function down(): void
    {
        foreach (['free', 'essentiel', 'pro'] as $name) {
            $plan = DB::table('plans')->where('name', $name)->first();
            if (! $plan) {
                continue;
            }
            $limits = json_decode($plan->limits ?? '[]', true) ?: [];
            unset($limits['max_products']);
            DB::table('plans')->where('name', $name)->update(['limits' => json_encode($limits)]);
        }
    }

    private function setLimit(string $name, ?int $value): void
    {
        $plan = DB::table('plans')->where('name', $name)->first();
        if (! $plan) {
            return; // fresh install: PlansSeeder will carry the value
        }

        $limits = json_decode($plan->limits ?? '[]', true) ?: [];
        $limits['max_products'] = $value;

        DB::table('plans')->where('name', $name)->update(['limits' => json_encode($limits)]);
    }
};
