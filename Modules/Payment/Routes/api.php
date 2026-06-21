<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentCallbackController;

Route::prefix('payments')->group(function () {

    Route::match(['get', 'post'], '{driver}/{payment}/redirect', [
        PaymentCallbackController::class, 'redirect',
    ])->name('payment.redirect');

    Route::match(['get', 'post'], '{driver}/{payment}/callback', [
        PaymentCallbackController::class, 'callback',
    ])->name('payment.callback');

});
