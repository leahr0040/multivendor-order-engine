<?php

namespace App\Jobs;

use App\Enums\SubOrderStatus;
use App\Models\SubOrder;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyVendorJob implements ShouldQueueAfterCommit
{
    use Batchable, Queueable;

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
    }
}
