<?php

namespace App\Services\Discounts\Rules;

use App\Data\PricedLineData;
use App\Models\User;
use App\Services\Discounts\DiscountRuleInterface;

class CategoryDiscountRule implements DiscountRuleInterface
{
    public function isEligible(PricedLineData $line, ?User $user): bool
    {
        return collect(config('discounts.category', []))->has($line->category_slug->value);
    }

    public function getDiscountPercent(PricedLineData $line, ?User $user): int
    {
        return (int) config('discounts.category.'.$line->category_slug->value, 0);
    }
}
