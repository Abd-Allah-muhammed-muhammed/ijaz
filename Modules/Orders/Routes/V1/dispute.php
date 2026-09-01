<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Api\V1\OrderDisputeController;

Route::middleware(['auth:sanctum', 'user.active'])->group(static function () {
    Route::post('/orders/{order}/dispute', [OrderDisputeController::class, 'store']);
});
