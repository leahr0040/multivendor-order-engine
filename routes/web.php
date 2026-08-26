<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogController::class)->name('catalog');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
