<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;

Route::resource('top-up-requests', TopUpController::class)->only(['index', 'show', 'store', 'destroy']);
Route::resource('withdraw-requests', WithdrawController::class)->only(['index', 'show', 'store', 'destroy']);
