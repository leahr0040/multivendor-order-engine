<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\SubOrderStatus;
use App\Models\SubOrder;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyVendorJob implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public SubOrder $sub_order) {}

    public function handle(): void
    {
        $this->sub_order->loadMissing('vendor', 'order_items');

        Log::info('Vendor notified', [
            'vendor' => $this->sub_order->vendor->name,
            'sub_order' => $this->sub_order->ulid,
            'items' => $this->sub_order->order_items->count(),
            'final_price' => $this->sub_order->final_price,
        ]);

        $this->sub_order->update(['status' => SubOrderStatus::Completed]);

        $this->completeOrderOnceEveryVendorIsNotified();
    }

    private function completeOrderOnceEveryVendorIsNotified(): void
    {
        $order = $this->sub_order->order;

        if ($order->sub_orders()->where('status', SubOrderStatus::Pending)->doesntExist()) {
            $order->update(['status' => OrderStatus::Completed]);
        }
    }
}
