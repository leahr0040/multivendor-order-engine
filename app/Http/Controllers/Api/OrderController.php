<?php

namespace App\Http\Controllers\Api;

use App\Data\OrderData;
use App\Data\UnavailableProductsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Services\Orders\OrderPlacementService;
use App\Services\Pricing\CartPriceService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private CartPriceService $cart_price_service,
        private OrderPlacementService $order_placement_service,
    ) {}

    public function store(OrderRequest $request): JsonResponse
    {
        if ($replay = $this->order_placement_service->getPlacedOrder($request->idempotencyKey())) {
            return response()->json(OrderData::from($replay->loadDetail()), 200);
        }

        $priced = $this->cart_price_service->calculate($request->cartLines(), $request->customer());

        if ($priced instanceof UnavailableProductsData) {
            throw $request->unavailable($priced);
        }

        $order = $this->order_placement_service->place($priced, $request->customer(), $request->idempotencyKey());

        return response()->json(
            OrderData::from($order->loadDetail()),
            $order->wasRecentlyCreated ? 201 : 200,
        );
    }
}
