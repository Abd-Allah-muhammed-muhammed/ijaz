<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\V1\MemberChatController;
use Modules\Chat\Http\Controllers\V1\OrderChatController;
use Modules\Chat\Http\Controllers\V1\TicketSupportChatController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('chats')->group(function () {
        Route::controller(OrderChatController::class)->prefix('orders')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('/send/{conversation}', 'send');
            Route::get('/{conversation}', 'show');
        });

        Route::controller(TicketSupportChatController::class)->prefix('tickets')->group(function () {
            Route::get('/', 'index');
            Route::post('/send/{conversation}', 'send');
            Route::get('/{conversation}', 'show');
        });

        Route::controller(MemberChatController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('/send/{conversation}', 'send');
            Route::get('/{conversation}/show', 'chat');
            Route::get('/{conversation}', 'show');
        });
    });
});
