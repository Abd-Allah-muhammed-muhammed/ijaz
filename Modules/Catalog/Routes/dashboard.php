<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Dashboard\CarBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\CarCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\CarTypeController;
use Modules\Catalog\Http\Controllers\Dashboard\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyTypeController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::resource('property-categories', PropertyCategoryController::class)->except(['show']);

        Route::controller(PropertyTypeController::class)
            ->prefix('property-types')
            ->as('property-types.')
            ->group(function () {
                Route::put('/{propertyType}/update-status', 'updateStatus')->name('update-status');
            });
        Route::resource('property-types', PropertyTypeController::class)->except(['show']);

        Route::controller(CarBrandController::class)
            ->prefix('car-brands')
            ->as('car-brands.')
            ->group(function () {
                Route::put('/{carBrand}/update-status', 'updateStatus')->name('update-status');
            });
        Route::resource('car-brands', CarBrandController::class)->except(['show']);

        Route::controller(CarTypeController::class)
            ->prefix('car-types')
            ->as('car-types.')
            ->group(function () {
                Route::put('/{carType}/update-status', 'updateStatus')->name('update-status');
            });
        Route::resource('car-types', CarTypeController::class)->except(['show']);

        Route::resource('car-categories', CarCategoryController::class)->except(['show']);
        Route::resource('device-categories', DeviceCategoryController::class)->except(['show']);
    });
