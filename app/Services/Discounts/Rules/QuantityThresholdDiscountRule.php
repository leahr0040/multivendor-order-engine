<?php

namespace App\Services\Discounts\Rules;

use App\Data\PricedLineData;
use App\Models\User;
use App\Services\Discounts\DiscountRuleInterface;

class QuantityThresholdDiscountRule implements DiscountRuleInterface
{
    public function isEligible(PricedLineData $line, ?User $user): bool
    {
        return collect(config()->array('discounts.quantity', []))
            ->contains(fn (array $threshold) => $line->quantity >= $threshold['min']);
    }

    public function discountPercentFor(PricedLineData $line, ?User $user): int
    {
        $threshold = collect(config()->array('discounts.quantity', []))
            ->filter(fn (array $threshold) => $line->quantity >= $threshold['min'])
            ->sortByDesc('min')
            ->first();

        return (int) ($threshold['percent'] ?? 0);
    }
}
