<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sub_order_id
 * @property int $product_id
 * @property int|null $vendor_product_id
 * @property int $quantity
 * @property int $original_unit_price
 * @property int $original_price
 * @property int $discount
 * @property int $final_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubOrder $sub_order
 * @property-read Product $product
 */
#[Fillable([
    'sub_order_id',
    'product_id',
    'vendor_product_id',
    'quantity',
    'original_unit_price',
    'original_price',
    'discount',
    'final_price',
])]
class OrderItem extends Model
{
    /** @return BelongsTo<SubOrder, $this> */
    public function sub_order(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
