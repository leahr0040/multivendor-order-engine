<?php

namespace App\Data;

use App\Enums\CategorySlug;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public CategorySlug $slug,
        public string $name,
    ) {}
}
