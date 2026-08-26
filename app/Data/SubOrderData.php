<?php

namespace App\Data;

use App\Enums\SubOrderStatus;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class SubOrderData extends Data
{
    /**
     * @param  Collection<int, OrderItemData>  $order_items
     */
    public function __construct(
        public string $ulid,
        public SubOrderStatus $status,
        public VendorData $vendor,
        public int $original_price,
        public int $discount,
        public int $final_price,
        #[DataCollectionOf(OrderItemData::class)]
        public Collection $order_items,
    ) {}
}
