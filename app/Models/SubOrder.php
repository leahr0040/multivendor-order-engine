<?php

namespace App\Models;

use App\Enums\SubOrderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property int $order_id
 * @property int $vendor_id
 * @property int $original_price
 * @property int $discount
 * @property int $final_price
 * @property SubOrderStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Vendor $vendor
 * @property-read Collection<int, OrderItem> $order_items
 */
#[Fillable(['order_id', 'vendor_id', 'original_price', 'discount', 'final_price', 'status'])]
class SubOrder extends Model
{
    use HasPublicId;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubOrderStatus::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
