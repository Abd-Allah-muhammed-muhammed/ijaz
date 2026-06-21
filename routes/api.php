<?php

use App\Http\Controllers\Api\CatalogController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// for dashboard filters
Route::controller(CatalogController::class)->group(function () {
    Route::get('/categories', 'categories')->name('api.categories');
    Route::get('/skills', 'skills')->name('api.skills');
    Route::get('/regions', 'regions')->name('api.regions');
    Route::get('/cities', 'cities')->name('api.cities');
    Route::get('/provider-types', 'providerTypes')->name('api.provider-types');
});
