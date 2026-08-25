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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaceOrder
{
    public function handle(PricedCartData $cart, ?User $user, string $idempotency_key): Order
    {
        $order = DB::transaction(fn () => $this->createOrder($cart, $user, $idempotency_key));

        $order->load('sub_orders');

        OrderPlaced::dispatch($order);

        return $order;
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

        $cart->lines
            ->groupBy('vendor_id')
            ->each(fn (Collection $lines, int|string $vendor_id) => $this->createSubOrder($order, (int) $vendor_id, $lines));

        return $order;
    }

    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    private function createSubOrder(Order $order, int $vendor_id, Collection $lines): SubOrder
    {
        $original_price = (int) $lines->sum(fn (PricedLineData $line) => $line->original_price);
        $discount = (int) $lines->sum(fn (PricedLineData $line) => $line->discount);

        $sub_order = $order->sub_orders()->create([
            'vendor_id' => $vendor_id,
            'original_price' => $original_price,
            'discount' => $discount,
            'final_price' => $original_price - $discount,
            'status' => SubOrderStatus::Pending,
        ]);

        $sub_order->order_items()->createMany(
            $lines->map(fn (PricedLineData $line) => [
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
