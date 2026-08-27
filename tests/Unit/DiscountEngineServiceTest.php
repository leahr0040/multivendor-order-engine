<?php

namespace Tests\Unit;

use App\Data\PricedLineData;
use App\Enums\CategorySlug;
use App\Enums\LoyaltyTier;
use App\Models\User;
use App\Services\Discounts\DiscountEngineService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiscountEngineServiceTest extends TestCase
{
    public function test_it_floors_the_discount_rather_than_rounding_it_up(): void
    {
        $rate = fake()->numberBetween(1, min(9, $this->maxPercent()));
        // an odd price outside the fives never divides evenly by a single-digit rate
        $unit_price = fake()->numberBetween(100, 999) * 10 + fake()->randomElement([1, 3, 7, 9]);

        config()->set('discounts.category', [CategorySlug::Books->value => $rate]);
        config()->set('discounts.quantity', []);

        $line = $this->discount(quantity: 1, unit_price: $unit_price, category: CategorySlug::Books);

        $this->assertSame(intdiv($unit_price * $rate, 100), $line->discount);
        $this->assertSame($unit_price - $line->discount, $line->final_price);
    }

    public function test_it_stacks_rates_additively_and_names_every_rule_that_applied(): void
    {
        $headroom = intdiv($this->maxPercent(), 3);
        $category_rate = fake()->numberBetween(1, $headroom);
        $quantity_rate = fake()->numberBetween(1, $headroom);
        $loyalty_rate = fake()->numberBetween(1, $headroom);
        $threshold = fake()->numberBetween(2, 9);

        config()->set('discounts.category', [CategorySlug::Books->value => $category_rate]);
        config()->set('discounts.quantity', [['min' => $threshold, 'percent' => $quantity_rate]]);
        config()->set('discounts.loyalty', [LoyaltyTier::Gold->value => $loyalty_rate]);

        $line = $this->discount(quantity: $threshold, unit_price: 1_000, category: CategorySlug::Books, tier: LoyaltyTier::Gold);

        $this->assertSame(
            intdiv($threshold * 1_000 * ($category_rate + $quantity_rate + $loyalty_rate), 100),
            $line->discount,
        );
        $this->assertSame([
            __('discounts.CategoryDiscountRule'),
            __('discounts.QuantityThresholdDiscountRule'),
            __('discounts.LoyaltyCustomerDiscountRule'),
        ], $line->applied_rules);
    }

    public function test_it_caps_the_stacked_rates_at_the_configured_maximum(): void
    {
        $max_percent = fake()->numberBetween(10, 50);

        config()->set('discounts.max_percent', $max_percent);
        config()->set('discounts.category', [CategorySlug::Books->value => $max_percent]);
        config()->set('discounts.loyalty', [LoyaltyTier::Gold->value => fake()->numberBetween(1, 20)]);
        config()->set('discounts.quantity', []);

        $line = $this->discount(quantity: 1, unit_price: 1_000, category: CategorySlug::Books, tier: LoyaltyTier::Gold);

        $this->assertSame(intdiv(1_000 * $max_percent, 100), $line->discount);
    }

    public function test_the_highest_matching_quantity_threshold_wins(): void
    {
        $half = intdiv($this->maxPercent(), 2);
        $lower = ['min' => fake()->numberBetween(2, 5), 'percent' => fake()->numberBetween(1, $half)];
        $higher = ['min' => fake()->numberBetween(6, 10), 'percent' => fake()->numberBetween($half + 1, $this->maxPercent())];

        config()->set('discounts.quantity', [$lower, $higher]);
        config()->set('discounts.category', []);
        config()->set('discounts.loyalty', []);

        $line = $this->discount(quantity: $higher['min'], unit_price: 1_000, category: CategorySlug::Books);

        $this->assertSame(intdiv($higher['min'] * 1_000 * $higher['percent'], 100), $line->discount);
        $this->assertSame([__('discounts.QuantityThresholdDiscountRule')], $line->applied_rules);
    }

    public function test_a_rule_that_does_not_apply_is_neither_charged_nor_named(): void
    {
        $threshold = fake()->numberBetween(3, 9);

        config()->set('discounts.category', []);
        config()->set('discounts.quantity', [['min' => $threshold, 'percent' => fake()->numberBetween(1, 10)]]);
        config()->set('discounts.loyalty', []);

        $line = $this->discount(quantity: $threshold - 1, unit_price: 1_000, category: CategorySlug::Books);

        $this->assertSame(0, $line->discount);
        $this->assertSame([], $line->applied_rules);
        $this->assertSame($line->original_price, $line->final_price);
    }

    public function test_an_anonymous_cart_gets_no_loyalty_discount(): void
    {
        $line = $this->discount(quantity: 1, unit_price: 1_000, category: CategorySlug::Books);

        $this->assertNotContains(__('discounts.LoyaltyCustomerDiscountRule'), $line->applied_rules);
        $this->assertContains(__('discounts.CategoryDiscountRule'), $line->applied_rules);
    }

    private function maxPercent(): int
    {
        return (int) config('discounts.max_percent');
    }

    private function discount(
        int $quantity,
        int $unit_price,
        CategorySlug $category,
        ?LoyaltyTier $tier = null,
    ): PricedLineData {
        $engine = $this->app->make(DiscountEngineService::class);

        $user = $tier ? tap(new User, fn (User $user) => $user->loyalty_tier = $tier) : null;

        /** @var PricedLineData $line */
        $line = $engine->apply(new Collection([$this->line($quantity, $unit_price, $category)]), $user)->first();

        return $line;
    }

    private function line(int $quantity, int $unit_price, CategorySlug $category): PricedLineData
    {
        $original_price = $quantity * $unit_price;

        return new PricedLineData(
            product_id: fake()->numberBetween(1, 1_000),
            vendor_id: fake()->numberBetween(1, 1_000),
            vendor_product_id: fake()->numberBetween(1, 1_000),
            product_ulid: (string) Str::ulid(),
            product_name: fake()->words(3, true),
            category_slug: $category,
            vendor_ulid: (string) Str::ulid(),
            vendor_name: fake()->company(),
            quantity: $quantity,
            original_unit_price: $unit_price,
            original_price: $original_price,
            discount: 0,
            final_price: $original_price,
            applied_rules: [],
        );
    }
}
