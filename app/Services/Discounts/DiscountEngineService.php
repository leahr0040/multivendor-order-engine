<?php

namespace App\Services\Discounts;

use App\Data\PricedLineData;
use App\Models\User;
use Illuminate\Support\Collection;

class DiscountEngineService
{
    /**
     * @param  Collection<int, DiscountRuleInterface>  $rules
     */
    public function __construct(private Collection $rules) {}

    /**
     * @param  Collection<int, PricedLineData>  $lines
     * @return Collection<int, PricedLineData>
     */
    public function apply(Collection $lines, ?User $user): Collection
    {
        return $lines->map(fn (PricedLineData $line) => $this->discountLine($line, $user));
    }

    private function discountLine(PricedLineData $line, ?User $user): PricedLineData
    {
        $percents = $this->rules
            ->filter(fn (DiscountRuleInterface $rule) => $rule->isEligible($line, $user))
            ->mapWithKeys(fn (DiscountRuleInterface $rule) => [
                (string) __('discounts.'.class_basename($rule)) => $rule->discountPercentFor($line, $user),
            ]);

        $total_percent = min((int) $percents->sum(), (int) config('discounts.max_percent'));

        $discount = (int) floor($line->original_price * $total_percent / 100);

        return $line->withDiscount($discount, $percents->keys()->all());
    }
}
