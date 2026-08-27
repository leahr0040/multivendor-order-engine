<?php

namespace App\Services\Discounts\Rules;

use App\Data\PricedLineData;
use App\Models\User;
use App\Services\Discounts\DiscountRuleInterface;

class LoyaltyCustomerDiscountRule implements DiscountRuleInterface
{
    public function isEligible(PricedLineData $line, ?User $user): bool
    {
        $tier = $user?->loyalty_tier;

        return $tier !== null && collect(config('discounts.loyalty', []))->has($tier->value);
    }

    public function getDiscountPercent(PricedLineData $line, ?User $user): int
    {
        return (int) config('discounts.loyalty.'.$user?->loyalty_tier->value, 0);
    }
}
