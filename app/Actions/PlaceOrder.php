<?php

namespace App\Actions;

use App\Data\PricedCartData;
use App\Data\PricedLineData;
use App\Enums\OrderStatus;
use App\Enums\SubOrderStatus;
use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Orders\VendorSplit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PlaceOrder
{
    public function __construct(
        private VendorSplit $vendor_split,
    ) {}

    public function handle(PricedCartData $cart, ?User $user, string $idempotency_key): Order
    {
        try {
            $order = DB::transaction(fn () => $this->createOrder($cart, $user, $idempotency_key));
            // no authenticated caller yet, so a replay cannot be scoped to an owner
        } catch (UniqueConstraintViolationException $e) {
            return $this->replay($idempotency_key) ?? throw $e;
        }

        $order->load('sub_orders');

        OrderPlaced::dispatch($order);

        return $order;
    }

    public function replay(string $idempotency_key): ?Order
    {
        return Order::firstWhere('idempotency_key', $idempotency_key);
    }

    private function createOrder(PricedCartData $cart, ?User $user, string $idempotency_key): Order
    {
        $order = Order::create([
            'user_id' => $user?->id,
            'idempotency_key' => $idempotency_key,
            'original_price' => $cart->original_price,
            'discount' => $cart->discount,
            'final_price' => $cart->final_price,
            'status' => OrderStatus::Pending,
        ]);

        $this->vendor_split->split($cart)
            ->each(fn (PricedCartData $vendor_cart, int $vendor_id) => $this->createSubOrder($order, $vendor_id, $vendor_cart));

        return $order;
    }

    private function createSubOrder(Order $order, int $vendor_id, PricedCartData $vendor_cart): SubOrder
    {
        $sub_order = $order->sub_orders()->create([
            'vendor_id' => $vendor_id,
            'original_price' => $vendor_cart->original_price,
            'discount' => $vendor_cart->discount,
            'final_price' => $vendor_cart->final_price,
            'status' => SubOrderStatus::Pending,
        ]);

        $sub_order->order_items()->createMany(
            $vendor_cart->lines->map(fn (PricedLineData $line) => [
                'product_id' => $line->product_id,
                'vendor_product_id' => $line->vendor_product_id,
                'quantity' => $line->quantity,
                'original_unit_price' => $line->original_unit_price,
                'original_price' => $line->original_price,
                'discount' => $line->discount,
                'final_price' => $line->final_price,
            ])->all()
        );

        return $sub_order;
    }
}
