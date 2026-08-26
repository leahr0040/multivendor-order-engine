<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class OrderItemData extends Data
{
    public function __construct(
        public ProductData $product,
        public int $quantity,
        public int $original_unit_price,
        public int $original_price,
        public int $discount,
        public int $final_price,
    ) {}
}
