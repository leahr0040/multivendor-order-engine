<?php

namespace App\Services\Discounts;

use App\Data\PricedLineData;
use App\Models\User;
use Illuminate\Support\Collection;

class DiscountEngineService
{
    /**
     * @param  Collection<int, PricedLineData>  $lines
     * @return Collection<int, PricedLineData>
     */
    public function apply(Collection $lines, ?User $user): Collection
    {
        // Empty rule set: rates are summed and floored once per line here once rules are bound.
        return $lines;
    }
}
