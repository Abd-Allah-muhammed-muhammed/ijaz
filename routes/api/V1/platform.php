<?php

use App\Http\Controllers\Api\V1\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->group(static function () {
    Route::controller(PlatformController::class)->group(static function () {
        Route::get('/providers', 'providers');
    });
});
