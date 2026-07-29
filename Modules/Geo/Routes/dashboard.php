<?php

use Illuminate\Support\Facades\Route;
use Modules\Geo\Http\Controllers\Dashboard\CityController;
use Modules\Geo\Http\Controllers\Dashboard\NationalityController;
use Modules\Geo\Http\Controllers\Dashboard\RegionController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::resource('regions', RegionController::class)->except(['show']);
        Route::resource('cities', CityController::class)->except(['show']);
        Route::resource('nationalities', NationalityController::class)->except(['show']);
    });
