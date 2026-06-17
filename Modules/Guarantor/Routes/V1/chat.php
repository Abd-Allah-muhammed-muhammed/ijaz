<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Http\Controllers\V1\GuarantorChatController;

Route::prefix('guarantor')->name('chats.guarantor.')->controller(GuarantorChatController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('{conversation}', 'show')->name('show');
    Route::post('{conversation}/send', 'send')->name('send');
});
