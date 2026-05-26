<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Dashboard\CarBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\CarCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\CarTypeController;
use Modules\Catalog\Http\Controllers\Dashboard\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\ElectronicBrandController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyTypeController;
use Modules\Catalog\Http\Controllers\Dashboard\SpecializationController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {

        // Property
        Route::resource('property-categories', PropertyCategoryController::class)->except(['show']);
        Route::resource('property-types', PropertyTypeController::class)->except(['show']);
        Route::put('property-types/{propertyType}/update-status', [PropertyTypeController::class, 'updateStatus'])
            ->name('property-types.update-status');

        // Cars
        Route::resource('car-brands', CarBrandController::class)->except(['show']);
        Route::put('car-brands/{carBrand}/update-status', [CarBrandController::class, 'updateStatus'])
            ->name('car-brands.update-status');

        Route::resource('car-types', CarTypeController::class)->except(['show']);
        Route::put('car-types/{carType}/update-status', [CarTypeController::class, 'updateStatus'])
            ->name('car-types.update-status');

        Route::resource('car-categories', CarCategoryController::class)->except(['show']);

        // Devices
        Route::resource('device-categories', DeviceCategoryController::class)->except(['show']);

        // Electronics
        Route::resource('electronic-brands', ElectronicBrandController::class)->except(['show']);
        Route::put('electronic-brands/{electronic_brand}/update-status', [ElectronicBrandController::class, 'updateStatus'])
            ->name('electronic-brands.update-status');

        // Specializations
        Route::resource('specializations', SpecializationController::class)->except(['show']);
    });
