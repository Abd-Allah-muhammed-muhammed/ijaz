<?php

use Illuminate\Support\Facades\Route;

Route::get('/payments/{payment}/success', function () {
    return view('payment::success');
})->name('payment.success');

Route::get('/payments/{payment}/failed', function () {
    return view('payment::failed');
})->name('payment.failed');
