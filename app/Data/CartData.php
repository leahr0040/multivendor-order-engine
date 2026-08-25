<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CartData extends Data
{
    /**
     * @param  Collection<int, CartLineData>  $items
     */
    public function __construct(
        public Collection $items,
        public ?string $user_ulid,
    ) {}
}
