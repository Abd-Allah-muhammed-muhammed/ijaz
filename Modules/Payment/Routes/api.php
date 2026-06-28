<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentCallbackController;
use Modules\Payment\Http\Controllers\RajhiWebhookController;

Route::prefix('payments')->group(function () {

    Route::match(['get', 'post'], '{driver}/{payment}/redirect', [
        PaymentCallbackController::class, 'redirect',
    ])->name('payment.redirect');

    Route::match(['get', 'post'], '{driver}/{payment}/callback', [
        PaymentCallbackController::class, 'callback',
    ])->name('payment.callback');

    // Rajhi webhook — source of truth
    Route::post('rajhi/webhook', [RajhiWebhookController::class, 'handle'])
        ->name('payment.rajhi.webhook');

});
