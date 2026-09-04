<?php

use Illuminate\Support\Facades\Route;
// Paused (not removed) — chore/provider-topup-pause, 2026-09-04.
// use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;

// Paused (not removed) — Provider dashboard top-up is temporarily disabled.
// Re-enable by uncommenting the import above and this resource line, then
// run `php artisan wayfinder:generate` so Provider TopUpController.ts returns.
// Task: chore/provider-topup-pause (2026-09-04).
// Route::resource('top-up-requests', TopUpController::class)->only(['index', 'show', 'store', 'destroy']);
Route::resource('withdraw-requests', WithdrawController::class)->only(['index', 'show', 'store', 'destroy']);
