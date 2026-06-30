<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Models\Payment;

Route::get('/payments/{payment}/success', function (Payment $payment) {
    return view('payment::success', compact('payment'));
})->name('payment.success');

Route::get('/payments/{payment}/failed', function (Payment $payment) {
    return view('payment::failed', compact('payment'));
})->name('payment.failed');
