<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController;

Route::controller(TopUpRequestController::class)->prefix('top-up-requests')->as('top-up-requests.')->group(static function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{topUpRequest}', 'show')->name('show');
    Route::put('/{topUpRequest}/update-status', 'updateStatus')->name('updateStatus');
});

Route::controller(WithdrawRequestController::class)->prefix('withdraw-requests')->as('withdraw-requests.')->group(static function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{withdrawRequest}', 'show')->name('show');
    Route::put('/{withdrawRequest}/update-status', 'updateStatus')->name('updateStatus');
});
