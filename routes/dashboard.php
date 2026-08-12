<?php

use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\DeviceTokenController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\PanAnalyticsController;
use App\Http\Controllers\Dashboard\ProviderController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    static function () {
        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], static function () {
            Route::group(['middleware' => ['guest:admin', SetLocaleFromRequest::class]], static function () {
                Route::get('/login', [AuthController::class, 'loginForm'])->name('login.form');
                Route::post('/login', [AuthController::class, 'login'])->name('login');
            });
            Route::middleware('auth:admin')->group(function () {
                Route::get('/', HomeController::class)->name('home');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
                Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
                Route::prefix('notifications')->as('notifications.')->controller(NotificationController::class)->group(static function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/unread-count', 'unreadCount')->name('unread-count');
                    Route::post('/mark-all-as-read', 'markAllAsRead')->name('mark-all-as-read');
                    Route::post('/{notification}/mark-as-read', 'markAsRead')->name('mark-as-read');
                });
                Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
                Route::resource('roles', RoleController::class)->except(['show']);
                Route::resource('admins', AdminController::class)->except(['show']);
                Route::controller(ProviderController::class)->prefix('providers')->as('providers.')->group(function () {
                    Route::put('/{provider}/status', 'updateStatus')->name('update-status');
                });
                Route::resource('providers', ProviderController::class);
                Route::controller(UserController::class)->prefix('users')->as('users.')->group(function () {
                    Route::put('/{user}/status', 'updateStatus')->name('update-status');
                });
                Route::resource('users', UserController::class);
                Route::controller(PanAnalyticsController::class)->prefix('pan-analytics')->as('pan-analytics.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/export', 'export')->name('export');
                    Route::delete('/clear', 'clear')->name('clear');
                });
            });
        });
    }
);
