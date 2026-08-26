<?php

namespace App\Services\Orders;

use App\Data\PricedCartData;
use App\Data\PricedLineData;
use Illuminate\Support\Collection;

class VendorSplit
{
    /**
     * A vendor's share never carries an order-wide adjustment — only what its own lines add up to.
     *
     * @return Collection<int, PricedCartData> keyed by vendor id
     */
    public function split(PricedCartData $cart): Collection
    {
        return $cart->lines
            ->groupBy('vendor_id')
            ->mapWithKeys(fn (Collection $lines, int|string $vendor_id) => [
                (int) $vendor_id => $this->vendorCart($lines),
            ]);
    }

    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    private function vendorCart(Collection $lines): PricedCartData
    {
        $original_price = (int) $lines->sum(fn (PricedLineData $line) => $line->original_price);
        $discount = (int) $lines->sum(fn (PricedLineData $line) => $line->discount);

        return new PricedCartData(
            lines: $lines,
            original_price: $original_price,
            discount: $discount,
            final_price: $original_price - $discount,
        );
    }
}
