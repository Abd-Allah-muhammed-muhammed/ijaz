<?php

use Illuminate\Support\Facades\Route;
use Modules\Opportunity\Http\Controllers\V1\OpportunityChatController;

Route::middleware('auth:sanctum')->controller(OpportunityChatController::class)->group(static function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('{conversation}', 'show')->name('show');
    Route::post('{conversation}/send', 'send')->name('send');
});
