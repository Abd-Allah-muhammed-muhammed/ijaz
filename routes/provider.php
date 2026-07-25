<?php

use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\HomeController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ], static function () {
        Route::group(['prefix' => 'provider', 'as' => 'provider.'], static function () {
            Route::controller(AuthController::class)->group(static function () {
                Route::group(['middleware' => ['guest:provider']], static function () {
                    Route::get('/login', 'loginForm')->name('login');
                    Route::post('/login', 'login')->name('login.submit');
                    Route::get('/register', 'register')->name('register');
                });
                Route::group(['middleware' => ['auth:provider']], static function () {
                    Route::get('/profile', 'profile')->name('profile');
                    Route::post('/profile', 'updateProfile')->name('profile.update');
                    Route::get('/statements', 'statements')->name('statements');
                    Route::get('/lang/{locale}', 'switchLang')->name('switchLang');
                });

            });
            Route::middleware('auth:provider')->group(static function () {
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::prefix('dashboard')->group(static function () {
                    Route::get('/', HomeController::class)->name('home');
                });
            });
        });
    });
