<?php

namespace App\Services\Pricing;

use App\Data\CartLineData;
use App\Data\PricedCartData;
use App\Data\PricedLineData;
use App\Data\UnavailableProductsData;
use App\Models\User;
use App\Models\VendorProduct;
use App\Services\Discounts\DiscountEngineService;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Pipeline;

class CartPriceService
{
    public function __construct(
        private DiscountEngineService $discount_engine,
    ) {}

    /**
     * @param  Collection<int, CartLineData>  $items
     */
    public function price(Collection $items, ?User $user): PricedCartData|UnavailableProductsData
    {
        /** @var PricedCartData|UnavailableProductsData $result */
        $result = Pipeline::send($items)
            ->through([
                $this->resolveCheapestVendors(...),
                $this->buildPricedLines($items),
                $this->applyDiscounts($user),
                $this->sumCartTotals(...),
            ])
            ->thenReturn();

        return $result;
    }

    /**
     * @param  Collection<int, CartLineData>  $items
     */
    private function resolveCheapestVendors(Collection $items, Closure $next): PricedCartData|UnavailableProductsData
    {
        $product_ids = $items->pluck('product_id');

        // re-ranked per quote; cacheable at scale, but placement must read live prices
        $cheapest_vendor_products = VendorProduct::cheapestFor($product_ids)
            ->with('product.category', 'vendor')
            ->get()
            ->keyBy('product_id');

        $unavailable = $product_ids
            ->reject(fn (int $product_id) => $cheapest_vendor_products->has($product_id))
            ->values();

        return $unavailable->isEmpty()
            ? $next($cheapest_vendor_products)
            : new UnavailableProductsData($unavailable);
    }

    /**
     * @param  Collection<int, CartLineData>  $items
     * @return Closure(Collection<int, VendorProduct>, Closure): PricedCartData
     */
    private function buildPricedLines(Collection $items): Closure
    {
        return fn (Collection $cheapest_vendor_products, Closure $next) => $next(
            $items->map(fn (CartLineData $item) => PricedLineData::fromVendorProduct(
                $cheapest_vendor_products[$item->product_id], $item->quantity
            ))
        );
    }

    /**
     * @return Closure(Collection<int, PricedLineData>, Closure): PricedCartData
     */
    private function applyDiscounts(?User $user): Closure
    {
        return fn (Collection $lines, Closure $next) => $next(
            $this->discount_engine->apply($lines, $user)
        );
    }

    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    private function sumCartTotals(Collection $lines, Closure $next): PricedCartData
    {
        $original_price = (int) $lines->sum(fn (PricedLineData $line) => $line->original_price);
        $discount = (int) $lines->sum(fn (PricedLineData $line) => $line->discount);

        return $next(new PricedCartData(
            lines: $lines,
            original_price: $original_price,
            discount: $discount,
            final_price: $original_price - $discount,
        ));
    }
}
