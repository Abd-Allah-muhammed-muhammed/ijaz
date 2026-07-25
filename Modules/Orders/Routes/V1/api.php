<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Api\V1\OrderController;

/*
| User API order routes — previously in routes/Api/V1/user.php.
| Loaded under api/v1 with NO name prefix (routes stay unnamed).
*/
Route::prefix('user')->group(static function () {
    Route::middleware(['auth:user-api', 'abilities:user-api'])->group(static function () {
        Route::controller(OrderController::class)->prefix('orders')->group(static function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{order}', 'show');
            Route::post('/{order}/edit', 'edit');
            Route::post('/{order}/{offer}/update-status', 'updateOfferStatus');
            Route::post('/{order}/{offer}/pay', 'pay');
            Route::post('/{order}/end-and-review', 'endAndReview');
            Route::delete('/{order}/{media:uuid}/delete', 'deleteMedia');
            Route::delete('/{order}', 'destroy');
        });
    });
});
