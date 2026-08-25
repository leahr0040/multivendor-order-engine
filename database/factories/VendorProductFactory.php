<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorProduct>
 */
class VendorProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'product_id' => Product::factory(),
            'price' => fake()->numberBetween(1_000, 50_000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
