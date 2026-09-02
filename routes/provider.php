<?php

use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\DeviceTokenController;
use App\Http\Controllers\Provider\HomeController;
use App\Http\Controllers\Provider\NotificationController;
use App\Http\Middleware\EnsureProviderIsApprovedMiddleware;
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
                Route::group(['middleware' => ['auth:provider', EnsureProviderIsApprovedMiddleware::class]], static function () {
                    Route::get('/profile', 'profile')->name('profile');
                    Route::post('/profile', 'updateProfile')->name('profile.update');
                    Route::post('/deactivate', 'deactivate')->name('deactivate');
                    Route::get('/statements', 'statements')->name('statements');
                    Route::get('/lang/{locale}', 'switchLang')->name('switchLang');
                });

            });
            Route::middleware(['auth:provider', EnsureProviderIsApprovedMiddleware::class])->group(static function () {
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::prefix('dashboard')->group(static function () {
                    Route::get('/', HomeController::class)->name('home');
                });
                Route::prefix('notifications')->as('notifications.')->controller(NotificationController::class)->group(static function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/unread-count', 'unreadCount')->name('unread-count');
                    Route::post('/mark-all-as-read', 'markAllAsRead')->name('mark-all-as-read');
                    Route::post('/{notification}/mark-as-read', 'markAsRead')->name('mark-as-read');
                });
                Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
            });
        });
    });
