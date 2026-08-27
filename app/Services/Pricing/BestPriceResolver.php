<?php

namespace App\Services\Pricing;

use App\Models\VendorProduct;
use Illuminate\Support\Collection;

class BestPriceResolver
{
    /**
     * ULIDs are translated to internal ids once, at the HTTP boundary — nothing
     * below it filters or keys on a public identifier.
     *
     * @param  Collection<int, int>  $product_ids
     * @return Collection<int, VendorProduct> keyed by product id
     */
    public function resolve(Collection $product_ids): Collection
    {
        // re-ranked per quote; cacheable at scale, but placement must read live prices
        return VendorProduct::cheapestFor($product_ids)
            ->with('product.category', 'vendor')
            ->get()
            ->keyBy('product_id');
    }
}
