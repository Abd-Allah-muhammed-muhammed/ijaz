<?php

use Illuminate\Support\Facades\Route;
use Modules\Classifieds\Http\Controllers\V1\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\V1\ElectronicAdvisementController;
use Modules\Classifieds\Http\Controllers\V1\PropertyAdvisementController;

Route::group(['prefix' => 'classifieds'], function () {

    // Public Routes
    Route::get('properties/all', [PropertyAdvisementController::class, 'all'])->name('properties.all');
    Route::get('cars/all', [CarAdvisementController::class, 'all'])->name('cars.all');
    Route::get('electronics/all', [ElectronicAdvisementController::class, 'all'])->name('electronics.all');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Property Advisements Routes
        Route::apiResource('properties', PropertyAdvisementController::class)->parameters(['properties' => 'propertyAdvisement']);
        Route::delete('properties/{propertyAdvisement}/media/{media:uuid}', [PropertyAdvisementController::class, 'deleteMedia'])->name('properties.deleteMedia');

        // Car Advisements Routes
        Route::apiResource('cars', CarAdvisementController::class)->parameters(['cars' => 'carAdvisement']);
        Route::delete('cars/{carAdvisement}/media/{media:uuid}', [CarAdvisementController::class, 'deleteMedia'])->name('cars.deleteMedia');

        // Electronic Advisements Routes
        Route::apiResource('electronics', ElectronicAdvisementController::class)->parameters(['electronics' => 'electronicAdvisement']);
        Route::delete('electronics/{electronicAdvisement}/media/{media:uuid}', [ElectronicAdvisementController::class, 'deleteMedia'])->name('electronics.deleteMedia');
    });
});
