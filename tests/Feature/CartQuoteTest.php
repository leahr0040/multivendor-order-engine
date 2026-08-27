<?php

namespace Tests\Feature;

use App\Enums\CategorySlug;
use App\Enums\LoyaltyTier;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prices_a_line_at_the_cheapest_active_vendor(): void
    {
        $cheapest_price = fake()->numberBetween(100, 5_000);
        $quantity = fake()->numberBetween(2, 10);

        $product = Product::factory()->create();
        VendorProduct::factory()->for($product)->create(['price' => $cheapest_price + fake()->numberBetween(1, 500)]);
        VendorProduct::factory()->for($product)->create(['price' => $cheapest_price]);

        $response = $this->postJson(route('api.cart.quote'), $this->payload($product, quantity: $quantity));

        $response->assertOk();
        $this->assertSame($cheapest_price, $response->json('lines.0.original_unit_price'));
        $this->assertSame($cheapest_price * $quantity, $response->json('original_price'));
    }

    public function test_a_cheaper_inactive_vendor_never_wins(): void
    {
        $active_price = fake()->numberBetween(1_000, 5_000);

        $product = Product::factory()->create();
        VendorProduct::factory()->for($product)->create(['price' => $active_price]);
        VendorProduct::factory()->for($product)->inactive()->create(['price' => fake()->numberBetween(1, 999)]);

        $response = $this->postJson(route('api.cart.quote'), $this->payload($product));

        $response->assertOk();
        $this->assertSame($active_price, $response->json('lines.0.original_unit_price'));
    }

    public function test_a_product_no_active_vendor_stocks_is_rejected_rather_than_priced(): void
    {
        $product = Product::factory()->create();
        VendorProduct::factory()->for($product)->inactive()->create();

        $this->postJson(route('api.cart.quote'), $this->payload($product))
            ->assertJsonValidationErrorFor('items');
    }

    public function test_the_same_product_twice_is_rejected_rather_than_merged(): void
    {
        $product = Product::factory()->create();
        VendorProduct::factory()->for($product)->create();

        $this->postJson(route('api.cart.quote'), [
            'items' => [
                ['product' => $product->ulid, 'quantity' => fake()->numberBetween(1, 10)],
                ['product' => $product->ulid, 'quantity' => fake()->numberBetween(1, 10)],
            ],
        ])->assertJsonValidationErrorFor('items.0.product');
    }

    public function test_the_quote_names_the_rules_that_produced_the_discount(): void
    {
        $threshold = (int) collect(config('discounts.quantity'))->min('min');

        $product = $this->productIn(CategorySlug::Books);
        VendorProduct::factory()->for($product)->create(['price' => fake()->numberBetween(100, 5_000)]);

        $response = $this->postJson(route('api.cart.quote'), $this->payload($product, quantity: $threshold));

        $response->assertOk();
        $this->assertSame([
            __('discounts.CategoryDiscountRule'),
            __('discounts.QuantityThresholdDiscountRule'),
        ], $response->json('lines.0.applied_rules'));
        $this->assertGreaterThan(0, $response->json('discount'));
        $this->assertSame(
            $response->json('original_price') - $response->json('discount'),
            $response->json('final_price'),
        );
    }

    public function test_the_customer_loyalty_tier_discounts_the_quote(): void
    {
        $user = User::factory()->create(['loyalty_tier' => LoyaltyTier::Gold]);

        $product = $this->productIn(CategorySlug::Home);
        VendorProduct::factory()->for($product)->create(['price' => fake()->numberBetween(100, 5_000)]);

        $loyal = $this->postJson(route('api.cart.quote'), $this->payload($product, user: $user));
        $anonymous = $this->postJson(route('api.cart.quote'), $this->payload($product));

        $loyal->assertOk();
        $this->assertContains(__('discounts.LoyaltyCustomerDiscountRule'), $loyal->json('lines.0.applied_rules'));
        $this->assertLessThan($anonymous->json('final_price'), $loyal->json('final_price'));
    }

    private function productIn(CategorySlug $slug): Product
    {
        return Product::factory()
            ->for(Category::factory()->create(['slug' => $slug]))
            ->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Product $product, int $quantity = 1, ?User $user = null): array
    {
        return [
            'items' => [['product' => $product->ulid, 'quantity' => $quantity]],
            'user' => $user?->ulid,
        ];
    }
}
