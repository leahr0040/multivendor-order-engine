<?php

namespace Database\Factories;

use App\Enums\CategorySlug;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Arr::random(CategorySlug::cases());

        return [
            'slug' => $slug,
            'name' => Str::headline($slug->value),
        ];
    }
}
