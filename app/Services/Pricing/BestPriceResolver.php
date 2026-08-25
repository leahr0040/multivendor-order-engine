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
        $cheapest_first = VendorProduct::whereIn('product_id', $product_ids)
            ->where('is_active', true)
            ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY price, id) AS price_rank');

        return VendorProduct::query()
            ->fromSub($cheapest_first, 'vendor_products')
            ->where('price_rank', 1)
            ->with('product.category', 'vendor')
            ->get()
            ->keyBy('product_id');
    }
}
