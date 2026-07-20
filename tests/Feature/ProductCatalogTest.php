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

    public function test_user_can_create_product_via_route(): void
    {
        $this->seed(\Database\Seeders\PlansSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store', ['locale' => 'fr']), [
            'designation' => 'Prestation conseil',
            'reference' => 'REF-1',
            'unit_price_ht' => 120,
            'vat_rate' => 17,
            'unit' => 'hour',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', [
            'designation' => 'Prestation conseil',
            'user_id' => $user->id,
            'unit' => 'hour',
        ]);
    }

    public function test_quota_middleware_blocks_store_when_limit_reached(): void
    {
        $this->seed(\Database\Seeders\PlansSeeder::class);
        $user = User::factory()->create();
        Product::factory()->count(10)->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('products.store', ['locale' => 'fr']), [
            'designation' => 'Onzième',
            'unit_price_ht' => 10,
            'vat_rate' => 17,
            'unit' => 'piece',
        ]);

        // Blocked by plan.limit:products — still 10, the 11th was not created.
        $this->assertSame(10, Product::withoutUserScope()->where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('products', ['designation' => 'Onzième']);
    }

    public function test_search_returns_only_own_active_matching_products(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Micro Shure', 'is_active' => true]);
        Product::factory()->create(['user_id' => $user->id, 'designation' => 'Micro inactif', 'is_active' => false]);
        Product::factory()->create(['user_id' => $other->id, 'designation' => 'Micro Autre', 'is_active' => true]);

        $response = $this->actingAs($user)->getJson(route('products.search', ['locale' => 'fr', 'q' => 'Micro']));

        $response->assertOk();
        $data = $response->json('products');
        $names = array_column($data, 'designation');

        $this->assertContains('Micro Shure', $names);
        $this->assertNotContains('Micro inactif', $names); // inactive excluded
        $this->assertNotContains('Micro Autre', $names);   // other user's excluded
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
