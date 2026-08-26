<?php

namespace App\Providers;

use App\Services\Discounts\DiscountEngineService;
use App\Services\Discounts\Rules\CategoryDiscountRule;
use App\Services\Discounts\Rules\LoyaltyCustomerDiscountRule;
use App\Services\Discounts\Rules\QuantityThresholdDiscountRule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class DiscountServiceProvider extends ServiceProvider
{
    /**
     * The one place the rule set is declared. Adding a rule is a class plus a
     * line in this tag — rates are additive, so the order never matters.
     */
    public function register(): void
    {
        $this->app->tag([
            CategoryDiscountRule::class,
            QuantityThresholdDiscountRule::class,
            LoyaltyCustomerDiscountRule::class,
        ], 'discount.rules');

        $this->app->bind(
            DiscountEngineService::class,
            fn (Application $app) => new DiscountEngineService(collect($app->tagged('discount.rules'))),
        );
    }
}
