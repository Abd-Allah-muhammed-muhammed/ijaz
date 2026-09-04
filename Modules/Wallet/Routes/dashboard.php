<?php

use Illuminate\Support\Facades\Route;
// Paused (not removed) — Admin dashboard top-up UI temporarily disabled.
// Task: chore/provider-topup-pause (2026-09-04). UI-only pause — API + notifications untouched.
// use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController;

// Paused (not removed) — Admin top-up Index/Show/updateStatus.
// Re-enable by uncommenting the import above and this route group, then run
// `php artisan wayfinder:generate` so Dashboard TopUpRequestController.ts returns.
// Route::controller(TopUpRequestController::class)->prefix('top-up-requests')->as('top-up-requests.')->group(static function () {
//     Route::get('/', 'index')->name('index');
//     Route::get('/{topUpRequest}', 'show')->name('show');
//     Route::put('/{topUpRequest}/update-status', 'updateStatus')->name('updateStatus');
// });

Route::controller(WithdrawRequestController::class)->prefix('withdraw-requests')->as('withdraw-requests.')->group(static function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{withdrawRequest}', 'show')->name('show');
    Route::put('/{withdrawRequest}/update-status', 'updateStatus')->name('updateStatus');
});
