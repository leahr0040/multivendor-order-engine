<?php

use App\Enums\CategorySlug;
use App\Enums\LoyaltyTier;

return [

    'max_percent' => 50,

    'category' => [
        CategorySlug::Electronics->value => 5,
        CategorySlug::Books->value => 10,
    ],

    'quantity' => [
        ['min' => 5, 'percent' => 5],
        ['min' => 10, 'percent' => 12],
    ],

    'loyalty' => [
        LoyaltyTier::Silver->value => 3,
        LoyaltyTier::Gold->value => 7,
    ],

];
