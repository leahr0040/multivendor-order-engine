<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class VendorData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
    ) {}
}
