<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $contact_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'contact_email'])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory, HasPublicId;

    /** @return HasMany<VendorProduct, $this> */
    public function vendor_products(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }
}
