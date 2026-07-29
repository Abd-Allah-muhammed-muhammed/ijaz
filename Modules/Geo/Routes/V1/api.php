<?php

use Illuminate\Support\Facades\Route;
use Modules\Geo\Http\Controllers\Api\V1\GeoController;

Route::prefix('catalog')->group(static function () {
    Route::controller(GeoController::class)->group(static function () {
        Route::prefix('regions')->group(static function () {
            Route::get('/', 'regions');
            Route::get('/{region}/cities', 'cities');
        });
        Route::get('/nationalities', 'nationalities');
    });
});
