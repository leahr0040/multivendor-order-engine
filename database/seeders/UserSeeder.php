<?php

namespace Database\Seeders;

use App\Enums\LoyaltyTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        collect(LoyaltyTier::cases())->each(fn (LoyaltyTier $tier) => User::factory()->create([
            'name' => Str::headline($tier->value).' Customer',
            'email' => $tier->value.'@example.com',
            'loyalty_tier' => $tier,
        ]));
    }
}
