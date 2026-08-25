<?php

namespace Database\Seeders;

use App\Enums\CategorySlug;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        collect(CategorySlug::cases())->each(fn (CategorySlug $slug) => Category::create([
            'slug' => $slug,
            'name' => Str::headline($slug->value),
        ]));
    }
}
