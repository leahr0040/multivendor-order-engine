<?php

namespace App\Http\Controllers\Api;

use App\Data\UnavailableProductsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
use App\Services\Pricing\CartPriceService;
use Illuminate\Http\JsonResponse;

class CartQuoteController extends Controller
{
    public function __construct(
        private CartPriceService $cart_price_service,
    ) {}

    public function __invoke(CartRequest $request): JsonResponse
    {
        $priced = $this->cart_price_service->calculate($request->cartLines(), $request->customer());

        if ($priced instanceof UnavailableProductsData) {
            throw $request->unavailable($priced);
        }

        return response()->json($priced);
    }
}
