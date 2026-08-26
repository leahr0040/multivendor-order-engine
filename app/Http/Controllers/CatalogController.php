<?php

namespace App\Http\Controllers;

use App\Data\ProductData;
use App\Data\UserData;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::with('category')
            ->whereRelation('vendor_products', 'is_active', true)
            ->get();

        return Inertia::render('catalog', [
            'products' => ProductData::collect($products),
            // demo picker for the loyalty-tier discount, not a login substitute
            'users' => UserData::collect(User::all()),
        ]);
    }
}
