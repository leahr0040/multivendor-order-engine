<?php

use App\Http\Controllers\Api\CartQuoteController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok'])->name('api.health');

Route::post('/cart/quote', CartQuoteController::class)->name('api.cart.quote');
Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
