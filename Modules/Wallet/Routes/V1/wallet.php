<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\V1\WalletController;

Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/balance', [WalletController::class, 'balance']);
    Route::post('/add-balance', [WalletController::class, 'addBalance']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/transaction', [WalletController::class, 'transactions']);
});
