<?php

namespace App\Models;

use Database\Factories\VendorProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $product_id
 * @property int $price
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vendor $vendor
 * @property-read Product $product
 */
#[Fillable(['vendor_id', 'product_id', 'price', 'is_active'])]
class VendorProduct extends Model
{
    /** @use HasFactory<VendorProductFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<$this>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @param  Collection<int, int>  $product_ids
     */
    #[Scope]
    protected function cheapestFor(Builder $query, Collection $product_ids): void
    {
        $ranked_by_price = static::query()
            ->active()
            ->whereIn('product_id', $product_ids)
            ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY price, id) AS price_rank');

        $query->fromSub($ranked_by_price, $this->getTable())->where('price_rank', 1);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
