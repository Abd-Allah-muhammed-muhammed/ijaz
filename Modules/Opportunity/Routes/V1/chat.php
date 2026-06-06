<?php

use Illuminate\Support\Facades\Route;
use Modules\Opportunity\Http\Controllers\V1\OpportunityChatController;

Route::prefix('opportunities')->name('chats.opportunities.')->controller(OpportunityChatController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('{conversation}', 'show')->name('show');
    Route::post('{conversation}/send', 'send')->name('send');
});
