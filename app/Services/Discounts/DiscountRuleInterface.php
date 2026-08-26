<?php

namespace App\Services\Discounts;

use App\Data\PricedLineData;
use App\Models\User;

interface DiscountRuleInterface
{
    
    public function isEligible(PricedLineData $line, ?User $user): bool;


    public function discountPercentFor(PricedLineData $line, ?User $user): int;
}
