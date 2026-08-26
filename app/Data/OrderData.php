<?php

namespace App\Data;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class OrderData extends Data
{
    /**
     * @param  Collection<int, SubOrderData>  $sub_orders
     */
    public function __construct(
        public string $ulid,
        public OrderStatus $status,
        public int $original_price,
        public int $discount,
        public int $final_price,
        public ?CarbonImmutable $created_at,
        #[DataCollectionOf(SubOrderData::class)]
        public Collection $sub_orders,
    ) {}
}
