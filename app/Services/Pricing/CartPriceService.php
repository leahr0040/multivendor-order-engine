<?php

namespace App\Services\Pricing;

use App\Data\CartLineData;
use App\Data\PricedCartData;
use App\Data\PricedLineData;
use App\Data\UnavailableProductsData;
use App\Models\User;
use App\Services\Discounts\DiscountEngineService;
use Illuminate\Support\Collection;

class CartPriceService
{
    public function __construct(
        private BestPriceResolver $best_price_resolver,
        private DiscountEngineService $discount_engine,
    ) {}

    /**
     * @param  Collection<int, CartLineData>  $items
     */
    public function price(Collection $items, ?User $user): PricedCartData|UnavailableProductsData
    {
        $product_ids = $items->pluck('product_id');
        $cheapest_vendor_products = $this->best_price_resolver->resolve($product_ids);

        $unavailable = $product_ids->reject(fn (int $product_id) => $cheapest_vendor_products->has($product_id));

        if ($unavailable->isNotEmpty()) {
            return new UnavailableProductsData($unavailable->values());
        }

        $lines = $items->map(fn (CartLineData $item) => PricedLineData::fromVendorProduct(
            $cheapest_vendor_products[$item->product_id], $item->quantity
        ));

        return $this->pricedCart($this->discount_engine->apply($lines, $user));
    }

    /**
     * @param  Collection<int, PricedLineData>  $lines
     */
    private function pricedCart(Collection $lines): PricedCartData
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
