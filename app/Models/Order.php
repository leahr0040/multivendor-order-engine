<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property int|null $user_id
 * @property int $original_price
 * @property int $discount
 * @property int $final_price
 * @property OrderStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, SubOrder> $sub_orders
 * @property-read Collection<int, OrderItem> $order_items
 */
#[Fillable(['user_id', 'original_price', 'discount', 'final_price', 'status'])]
class Order extends Model
{
    use HasPublicId;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SubOrder, $this> */
    public function sub_orders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    /** @return HasManyThrough<OrderItem, SubOrder, $this> */
    public function order_items(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, SubOrder::class);
    }
}
