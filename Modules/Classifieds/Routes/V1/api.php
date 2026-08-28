<?php

use Illuminate\Support\Facades\Route;
use Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController;
use Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController;
use Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController;

Route::group(['prefix' => 'classifieds'], function () {

    // Public Routes
    Route::get('properties/all', [PropertyAdvisementController::class, 'all'])->name('properties.all');
    Route::get('cars/all', [CarAdvisementController::class, 'all'])->name('cars.all');
    Route::get('electronics/all', [ElectronicAdvisementController::class, 'all'])->name('electronics.all');
    Route::get('institutes/all', [InstituteAdvisementController::class, 'all'])->name('institutes.all');

    Route::get('properties/by-user/{user}', [PropertyAdvisementController::class, 'byUser'])->name('properties.byUser');
    Route::get('cars/by-user/{user}', [CarAdvisementController::class, 'byUser'])->name('cars.byUser');
    Route::get('electronics/by-user/{user}', [ElectronicAdvisementController::class, 'byUser'])->name('electronics.byUser');
    Route::get('institutes/by-user/{user}', [InstituteAdvisementController::class, 'byUser'])->name('institutes.byUser');

    // Protected Routes
    Route::middleware(['auth:sanctum', 'user.active'])->group(function () {

        // Property Advisements Routes
        Route::apiResource('properties', PropertyAdvisementController::class)->parameters(['properties' => 'propertyAdvisement']);
        Route::delete('properties/{propertyAdvisement}/media/{media:uuid}', [PropertyAdvisementController::class, 'deleteMedia'])->name('properties.deleteMedia');

        // Car Advisements Routes
        Route::apiResource('cars', CarAdvisementController::class)->parameters(['cars' => 'carAdvisement']);
        Route::delete('cars/{carAdvisement}/media/{media:uuid}', [CarAdvisementController::class, 'deleteMedia'])->name('cars.deleteMedia');

        // Electronic Advisements Routes
        Route::apiResource('electronics', ElectronicAdvisementController::class)->parameters(['electronics' => 'electronicAdvisement']);
        Route::delete('electronics/{electronicAdvisement}/media/{media:uuid}', [ElectronicAdvisementController::class, 'deleteMedia'])->name('electronics.deleteMedia');

        // Institute Advisements Routes
        Route::apiResource('institutes', InstituteAdvisementController::class)->parameters(['institutes' => 'instituteAdvisement']);
        Route::delete('institutes/{instituteAdvisement}/media/{media:uuid}', [InstituteAdvisementController::class, 'deleteMedia'])->name('institutes.deleteMedia');
    });
});
