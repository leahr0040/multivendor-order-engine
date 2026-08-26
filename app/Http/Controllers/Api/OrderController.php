<?php

namespace App\Http\Controllers\Api;

use App\Actions\PlaceOrder;
use App\Data\OrderData;
use App\Data\UnavailableProductsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Services\Pricing\CartPriceService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private CartPriceService $cart_price_service,
        private PlaceOrder $place_order,
    ) {}

    public function store(OrderRequest $request): JsonResponse
    {
        $priced = $this->cart_price_service->price($request->cartLines(), $request->customer());

        if ($priced instanceof UnavailableProductsData) {
            throw $request->unavailable($priced);
        }

        $order = $this->place_order->handle($priced, $request->customer(), $request->idempotencyKey());

        return response()->json(
            OrderData::from($order->loadDetail()),
            $order->wasRecentlyCreated ? 201 : 200,
        );
    }
}
