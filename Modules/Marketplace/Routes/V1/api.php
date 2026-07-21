<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\V1\MarketplaceCatalogController;

Route::prefix('catalog')->group(static function () {
    Route::controller(MarketplaceCatalogController::class)->group(static function () {
        Route::prefix('categories')->group(static function () {
            Route::get('/', 'categories');
            Route::get('/{category}/children', 'categoryChildren');
            Route::get('/{category}/skills', 'categorySkills');
            Route::get('/with-no-children', 'categoriesWithNoChildren');
        });
        Route::get('/provider-types', 'providerTypes');
    });
});
