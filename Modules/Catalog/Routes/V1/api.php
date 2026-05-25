<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\V1\CarBrandController;
use Modules\Catalog\Http\Controllers\V1\CarCategoryController;
use Modules\Catalog\Http\Controllers\V1\CarTypeController;
use Modules\Catalog\Http\Controllers\V1\PropertyCategoryController;
use Modules\Catalog\Http\Controllers\V1\PropertyTypeController;

Route::prefix('catalog')->group(static function () {
    Route::get('property-types', [PropertyTypeController::class, 'index']);
    Route::get('property-categories', [PropertyCategoryController::class, 'index']);
    Route::get('car-brands', [CarBrandController::class, 'index']);
    Route::get('car-brands/{carBrand}', [CarBrandController::class, 'show']);
    Route::get('car-types', [CarTypeController::class, 'index']);
    Route::get('car-types/{carType}', [CarTypeController::class, 'show']);
    Route::get('car-categories', [CarCategoryController::class, 'index']);
    Route::get('car-categories/{carCategory}', [CarCategoryController::class, 'show']);
});
