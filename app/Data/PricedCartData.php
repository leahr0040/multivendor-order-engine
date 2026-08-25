<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class PricedCartData extends Data
{
    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    public function __construct(
        public Collection $lines,
        public int $original_price,
        public int $discount,
        public int $final_price,
    ) {}

    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    public static function fromLines(Collection $lines): self
    {
        $original_price = (int) $lines->sum(fn (PricedLineData $line) => $line->original_price);
        $discount = (int) $lines->sum(fn (PricedLineData $line) => $line->discount);

        return new self(
            lines: $lines,
            original_price: $original_price,
            discount: $discount,
            final_price: $original_price - $discount,
        );
    }
}
