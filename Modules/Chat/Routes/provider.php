<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Provider\MemberChatController;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;

Route::prefix('chat')->as('chat.')->group(function () {
    Route::controller(OrderChatController::class)->prefix('orders')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/send/{conversation}', 'send');
        Route::get('/{conversation}', 'show');
    });

    Route::controller(MemberChatController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/{conversation}', 'show')->name('show');
        Route::post('/{conversation}/send', 'send')->name('send');
    });
});
