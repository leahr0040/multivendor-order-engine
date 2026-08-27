<?php

namespace Tests\Feature;

use App\Enums\CategorySlug;
use App\Enums\SubOrderStatus;
use App\Jobs\NotifyVendorJob;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_vendor_gets_its_own_sub_order_holding_only_its_own_lines(): void
    {
        $vendor_a = Vendor::factory()->create();
        $vendor_b = Vendor::factory()->create();

        $product_a = $this->stocked($vendor_a);
        $product_b = $this->stocked($vendor_a);
        $product_c = $this->stocked($vendor_b);

        $response = $this->postJson(route('api.orders.store'), $this->payload([[$product_a, 1], [$product_b, 1], [$product_c, 1]]));

        $response->assertCreated();
        $sub_orders = collect($response->json('sub_orders'))->keyBy('vendor.ulid');
        $this->assertEqualsCanonicalizing([$vendor_a->ulid, $vendor_b->ulid], $sub_orders->keys()->all());
        $this->assertCount(2, $sub_orders[$vendor_a->ulid]['order_items']);
        $this->assertCount(1, $sub_orders[$vendor_b->ulid]['order_items']);
    }

    public function test_a_sub_order_totals_only_its_own_vendor_lines(): void
    {
        $price_a = fake()->numberBetween(100, 5_000);
        $price_b = fake()->numberBetween(100, 5_000);
        $quantity_a = fake()->numberBetween(2, 10);

        $vendor_a = Vendor::factory()->create();
        $vendor_b = Vendor::factory()->create();

        $product_a = $this->stocked($vendor_a, $price_a);
        $product_b = $this->stocked($vendor_b, $price_b);

        $response = $this->postJson(route('api.orders.store'), $this->payload([[$product_a, $quantity_a], [$product_b, 1]]));

        $response->assertCreated();
        $sub_orders = collect($response->json('sub_orders'))->keyBy('vendor.ulid');
        $this->assertSame($price_a * $quantity_a, $sub_orders[$vendor_a->ulid]['original_price']);
        $this->assertSame($price_b, $sub_orders[$vendor_b->ulid]['original_price']);
        $this->assertSame($response->json('original_price'), $sub_orders->sum('original_price'));
        $this->assertSame($response->json('discount'), $sub_orders->sum('discount'));
        $this->assertSame($response->json('final_price'), $sub_orders->sum('final_price'));
    }

    public function test_the_line_discount_is_persisted_on_the_order_item(): void
    {
        $threshold = (int) collect(config('discounts.quantity'))->min('min');
        $price = fake()->numberBetween(100, 5_000);

        $product = Product::factory()
            ->for(Category::factory()->create(['slug' => CategorySlug::Books]))
            ->create();
        VendorProduct::factory()->for($product)->create(['price' => $price]);

        $this->postJson(route('api.orders.store'), $this->payload([[$product, $threshold]]))
            ->assertCreated();

        $item = OrderItem::query()->sole();
        $this->assertGreaterThan(0, $item->discount);
        $this->assertSame($threshold * $price - $item->discount, $item->final_price);
        $this->assertSame([
            __('discounts.CategoryDiscountRule'),
            __('discounts.QuantityThresholdDiscountRule'),
        ], $item->applied_discount_rules);
    }

    public function test_a_replay_returns_the_existing_order_without_creating_a_second_one(): void
    {
        $product = $this->stocked(Vendor::factory()->create());
        $payload = $this->payload([[$product, fake()->numberBetween(1, 10)]], idempotency_key: (string) Str::ulid());

        $placed = $this->postJson(route('api.orders.store'), $payload);
        $replay = $this->postJson(route('api.orders.store'), $payload);

        $placed->assertCreated();
        $replay->assertOk();
        $this->assertSame($placed->json('ulid'), $replay->json('ulid'));
        $this->assertSame(1, Order::query()->count());
    }

    public function test_placing_an_order_dispatches_one_notification_job_per_vendor(): void
    {
        Bus::fake();

        $product_a = $this->stocked(Vendor::factory()->create());
        $product_b = $this->stocked(Vendor::factory()->create());

        $this->postJson(route('api.orders.store'), $this->payload([[$product_a, 1], [$product_b, 1]]))
            ->assertCreated();

        Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 2
            && $batch->jobs->every(fn (NotifyVendorJob $job) => $job->sub_order->exists));
    }

    public function test_the_notification_job_completes_its_sub_order(): void
    {
        Bus::fake();

        $product = $this->stocked(Vendor::factory()->create());

        $this->postJson(route('api.orders.store'), $this->payload([[$product, 1]]))
            ->assertCreated();

        $sub_order = SubOrder::query()->sole();
        $this->assertSame(SubOrderStatus::Pending, $sub_order->status);

        (new NotifyVendorJob($sub_order))->handle();

        $this->assertSame(SubOrderStatus::Completed, $sub_order->refresh()->status);
    }

    private function stocked(Vendor $vendor, ?int $price = null): Product
    {
        $product = Product::factory()->create();

        VendorProduct::factory()->for($product)->for($vendor)->create([
            'price' => $price ?? fake()->numberBetween(100, 5_000),
        ]);

        return $product;
    }

    /**
     * @param  array<int, array{0: Product, 1: int}>  $lines
     * @return array<string, mixed>
     */
    private function payload(array $lines, ?string $idempotency_key = null): array
    {
        return [
            'idempotency_key' => $idempotency_key ?? (string) Str::ulid(),
            'items' => collect($lines)
                ->map(fn (array $line) => ['product' => $line[0]->ulid, 'quantity' => $line[1]])
                ->all(),
        ];
    }
}
