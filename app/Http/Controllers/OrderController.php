<?php

namespace App\Http\Controllers;

use App\Data\OrderData;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    // the ulid is the capability: no auth layer yet to check ownership against
    public function show(Order $order): Response
    {
        return Inertia::render('orders/show', [
            'order' => OrderData::from($order->loadDetail()),
        ]);
    }
}
