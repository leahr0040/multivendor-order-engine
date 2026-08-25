<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class UnavailableProductsData extends Data
{
    /**
     * @param  Collection<int, int>  $product_ids
     */
    public function __construct(
        public Collection $product_ids,
    ) {}
}
