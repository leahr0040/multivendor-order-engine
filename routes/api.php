<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok'])->name('api.health');
