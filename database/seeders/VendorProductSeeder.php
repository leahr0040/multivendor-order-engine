<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class VendorProductSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::all();

        Product::all()->each(fn (Product $product) => $this->offerToEveryVendor($product, $vendors));
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     */
    private function offerToEveryVendor(Product $product, Collection $vendors): void
    {
        $cheapest = fake()->numberBetween(2_000, 40_000);

        $vendors->shuffle()->values()->each(fn (Vendor $vendor, int $rank) => VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => $cheapest + $rank * fake()->numberBetween(200, 3_000),
            'is_active' => true,
        ]));
    }
}
