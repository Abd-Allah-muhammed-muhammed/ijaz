<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Api\V1\BankController;
use Modules\Catalog\Http\Controllers\Api\V1\CarBrandController;
use Modules\Catalog\Http\Controllers\Api\V1\CarCategoryController;
use Modules\Catalog\Http\Controllers\Api\V1\CarTypeController;
use Modules\Catalog\Http\Controllers\Api\V1\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\Api\V1\ElectronicBrandController;
use Modules\Catalog\Http\Controllers\Api\V1\PropertyCategoryController;
use Modules\Catalog\Http\Controllers\Api\V1\PropertyTypeController;
use Modules\Catalog\Http\Controllers\Api\V1\SpecializationController;

Route::prefix('catalog')->group(static function () {
    Route::get('banks', [BankController::class, 'index'])->name('banks.index');
    Route::get('property-types', [PropertyTypeController::class, 'index'])->name('property-types.index');
    Route::get('property-categories', [PropertyCategoryController::class, 'index'])->name('property-categories.index');
    Route::get('car-brands', [CarBrandController::class, 'index'])->name('car-brands.index');
    Route::get('car-brands/{carBrand}', [CarBrandController::class, 'show'])->name('car-brands.show');
    Route::get('car-types', [CarTypeController::class, 'index'])->name('car-types.index');
    Route::get('car-types/{carType}', [CarTypeController::class, 'show'])->name('car-types.show');
    Route::get('car-categories', [CarCategoryController::class, 'index'])->name('car-categories.index');
    Route::get('car-categories/{carCategory}', [CarCategoryController::class, 'show'])->name('car-categories.show');
    Route::get('device-categories', [DeviceCategoryController::class, 'index'])->name('device-categories.index');
    Route::get('device-categories/{deviceCategory}', [DeviceCategoryController::class, 'show'])->name('device-categories.show');
    Route::get('electronic-brands', [ElectronicBrandController::class, 'index'])->name('electronic-brands.index');
    Route::get('electronic-brands/{electronicBrand}', [ElectronicBrandController::class, 'show'])->name('electronic-brands.show');
    Route::get('specializations', [SpecializationController::class, 'index'])->name('specializations.index');
    Route::get('specializations/{specialization}', [SpecializationController::class, 'show'])->name('specializations.show');
});
