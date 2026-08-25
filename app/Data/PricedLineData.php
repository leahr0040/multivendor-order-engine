<?php

namespace App\Data;

use App\Enums\CategorySlug;
use App\Models\VendorProduct;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class PricedLineData extends Data
{
    /**
     * @param  array<int, string>  $applied_rules
     */
    public function __construct(
        #[Hidden]
        public int $product_id,
        #[Hidden]
        public int $vendor_id,
        #[Hidden]
        public int $vendor_product_id,
        public string $product_ulid,
        public string $product_name,
        #[Hidden]
        public CategorySlug $category_slug,
        public string $vendor_ulid,
        public string $vendor_name,
        public int $quantity,
        public int $original_unit_price,
        public int $original_price,
        public int $discount,
        public int $final_price,
        public array $applied_rules,
    ) {}

    public static function fromVendorProduct(VendorProduct $vendor_product, int $quantity): self
    {
        $original_price = $quantity * $vendor_product->price;

        return new self(
            product_id: $vendor_product->product_id,
            vendor_id: $vendor_product->vendor_id,
            vendor_product_id: $vendor_product->id,
            product_ulid: $vendor_product->product->ulid,
            product_name: $vendor_product->product->name,
            category_slug: $vendor_product->product->category->slug,
            vendor_ulid: $vendor_product->vendor->ulid,
            vendor_name: $vendor_product->vendor->name,
            quantity: $quantity,
            original_unit_price: $vendor_product->price,
            original_price: $original_price,
            discount: 0,
            final_price: $original_price,
            applied_rules: [],
        );
    }
}
