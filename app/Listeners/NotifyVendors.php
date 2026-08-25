<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\NotifyVendorJob;
use App\Models\SubOrder;

class NotifyVendors
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->sub_orders->each(fn (SubOrder $sub_order) => NotifyVendorJob::dispatch($sub_order));
    }
}
