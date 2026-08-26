<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Jobs\NotifyVendorJob;
use App\Models\Order;
use App\Models\SubOrder;
use Illuminate\Support\Facades\Bus;

class NotifyVendors
{
    public function handle(OrderPlaced $event): void
    {
        $order_id = $event->order->id;

        Bus::batch($event->order->sub_orders->map(fn (SubOrder $sub_order) => new NotifyVendorJob($sub_order)))
            ->then(fn () => Order::whereKey($order_id)->update(['status' => OrderStatus::Completed]))
            ->dispatch();
    }
}
