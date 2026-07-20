<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_is_scoped_to_its_owner(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice);
        Product::create(['designation' => 'Micro Alice', 'unit_price_ht' => 100, 'vat_rate' => 17, 'unit' => 'piece']);

        $this->actingAs($bob);
        Product::create(['designation' => 'Micro Bob', 'unit_price_ht' => 50, 'vat_rate' => 17, 'unit' => 'piece']);

        // Each user only sees their own catalogue (BelongsToUser global scope).
        $this->assertSame(1, Product::count());
        $this->assertSame('Micro Bob', Product::first()->designation);
    }

    public function test_free_plan_quota_blocks_the_eleventh_product(): void
    {
        $this->seed(\Database\Seeders\PlansSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $plans = app(PlanService::class);

        // Free plan = 10 products.
        Product::factory()->count(10)->create(['user_id' => $user->id]);
        $this->assertFalse($plans->canCreateProduct($user));

        Product::query()->take(1)->delete();
        $this->assertTrue($plans->canCreateProduct($user));
    }
}
