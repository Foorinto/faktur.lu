<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $pro = Plan::where('name', 'pro')->first();

        if ($pro && !in_array('organizations', $pro->features ?? [])) {
            $features = $pro->features ?? [];
            $features[] = 'organizations';
            $pro->features = $features;
            $pro->save();
        }
    }

    public function down(): void
    {
        $pro = Plan::where('name', 'pro')->first();

        if ($pro) {
            $features = array_filter($pro->features ?? [], fn ($f) => $f !== 'organizations');
            $pro->features = array_values($features);
            $pro->save();
        }
    }
};
