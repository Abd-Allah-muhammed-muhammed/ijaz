<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Payments\PayTabsController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::group(['prefix' => 'payments', 'as' => 'payment.'], static function () {
    Route::controller(PayTabsController::class)->as('paytabs.')->group(function () {
        Route::prefix('paytabs')->group(function () {
            Route::match(['get', 'post'], '/guarantor/{payment}/redirect', 'guarantorPayment')->name('guarantor.redirect');
            Route::match(['get', 'post'], '/guarantor/{payment}/callback', 'guarantorPayment')->name('guarantor.callback');
            Route::match(['get', 'post'], '/{payment}/redirect', 'redirect')->name('redirect');
            Route::match(['get', 'post'], '/{payment}/callback', 'callback')->name('callback');
        });
    });
});

// for dashboard filters
Route::controller(CatalogController::class)->group(function () {
    Route::get('/categories', 'categories')->name('api.categories');
    Route::get('/skills', 'skills')->name('api.skills');
    Route::get('/regions', 'regions')->name('api.regions');
    Route::get('/cities', 'cities')->name('api.cities');
    Route::get('/provider-types', 'providerTypes')->name('api.provider-types');
});
