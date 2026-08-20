<?php

use Illuminate\Support\Facades\Route;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::controller(PayoutRequestController::class)
            ->prefix('payout-requests')
            ->as('payout-requests.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/{payoutRequest}/submit', 'submit')->name('submit');
                Route::put('/{payoutRequest}/confirm', 'confirm')->name('confirm');
                Route::put('/{payoutRequest}/fail', 'fail')->name('fail');
                Route::put('/{payoutRequest}/reject', 'reject')->name('reject');
            });
    });
