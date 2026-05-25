<?php

use Illuminate\Support\Facades\Route;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\PropertyAdvisementController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::resource('property-advisements', PropertyAdvisementController::class)
            ->only(['index', 'show', 'update']);

        Route::resource('car-advisements', CarAdvisementController::class)
            ->only(['index', 'show', 'update']);
    });
