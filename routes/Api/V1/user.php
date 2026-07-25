<?php

use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\ProviderController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], static function () {
    Route::controller(AuthController::class)->prefix('auth')->group(static function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
        Route::middleware(['auth:user-api', 'abilities:user-api'])->group(static function () {
            Route::post('profile/update', 'profileUpdate');
            Route::get('me', 'auth');
            Route::post('logout', 'logout');
        });
    });
    Route::group(['middleware' => ['auth:user-api', 'abilities:user-api']], static function () {
        Route::controller(ProviderController::class)->prefix('providers')->group(static function () {
            Route::get('/get', 'get');
        });
    });

});
