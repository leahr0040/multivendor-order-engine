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

    /**
     * A discounted copy rather than a mutation, so a rule reading the cart-wide
     * lines never sees half of them already adjusted.
     *
     * @param  array<int, string>  $applied_rules
     */
    public function withDiscount(int $discount, array $applied_rules): self
    {
        return new self(
            product_id: $this->product_id,
            vendor_id: $this->vendor_id,
            vendor_product_id: $this->vendor_product_id,
            product_ulid: $this->product_ulid,
            product_name: $this->product_name,
            category_slug: $this->category_slug,
            vendor_ulid: $this->vendor_ulid,
            vendor_name: $this->vendor_name,
            quantity: $this->quantity,
            original_unit_price: $this->original_unit_price,
            original_price: $this->original_price,
            discount: $discount,
            final_price: $this->original_price - $discount,
            applied_rules: $applied_rules,
        );
    }
}
