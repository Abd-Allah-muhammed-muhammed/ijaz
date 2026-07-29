<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Http\Controllers\Provider\ProviderChatIndexController;

/*
| Provider dashboard order routes + order-chat inbox.
| Mounted under locale/provider/dashboard with provider. name prefix + auth:provider
| (see Orders RouteServiceProvider::mapProviderRoutes).
*/

Route::prefix('chat')->as('chat.')->group(function () {
    Route::get('/', ProviderChatIndexController::class)->name('index');
});

Route::prefix('/orders')->controller(OrderController::class)->as('orders.')->group(static function () {
    Route::get('/', 'index')->name('index');
    Route::get('/offers', 'offers')->name('offers');
    Route::get('/new', 'new')->name('index');
    Route::group(['prefix' => '/{order}/offers', 'as' => 'offers.'], static function () {
        Route::post('submit', 'submitOffer')->name('offers.store');
        Route::post('{offer}/update', 'updateOffer')->name('offers.update');
        Route::delete('{offer}', 'deleteOffer')->name('offers.delete');
    });
    Route::post('/{order}/end', 'end');
    Route::post('/{order}/review', 'updateReview')->name('review.update');
    Route::get('/{order}', 'show')->name('show');
});
