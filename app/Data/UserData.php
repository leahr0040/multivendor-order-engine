<?php

namespace App\Data;

use App\Enums\LoyaltyTier;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public LoyaltyTier $loyalty_tier,
    ) {}
}
