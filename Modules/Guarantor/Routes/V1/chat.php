<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Http\Controllers\V1\GuarantorChatController;

Route::prefix('chats/guarantor')->name('chats.guarantor.')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/', [GuarantorChatController::class, 'index'])
            ->name('index');

        Route::post('/', [GuarantorChatController::class, 'store'])
            ->name('store');

        Route::get('/{conversation}', [GuarantorChatController::class, 'show'])
            ->name('show');

        Route::post('/{conversation}/send', [GuarantorChatController::class, 'send'])
            ->name('send');
    });
});
