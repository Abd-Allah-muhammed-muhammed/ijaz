<?php

use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\HomeController;
use App\Http\Controllers\Provider\OrderController;
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
                    Route::post('/register', 'store')->name('register.submit');
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
                    Route::prefix('/orders')->controller(OrderController::class)->as('orders.')->group(static function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/offers', 'offers')->name('offers');
                        Route::get('/new', 'new')->name('index');
                        Route::group(['prefix' => '/{order}/offers', 'as' => 'offers.'], static function () {
                            Route::post('submit', 'submitOffer')->name('offers.store');
                            Route::post('{offer}/update', 'updateOffer')->name('offers.update');
                            Route::delete('{offer}', 'deleteOffer')->name('offers.delete');
                        });
                        Route::post('/{order}/end', 'end');
                        Route::post('/{order}/review', 'updateReview')->name('review.update');
                        Route::get('/{order}', 'show')->name('show');
                    });
                });
            });
        });
    });
