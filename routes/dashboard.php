<?php

use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\PanAnalyticsController;
use App\Http\Controllers\Dashboard\ProviderController;
use App\Http\Controllers\Dashboard\ProviderTypeController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\SkillController;
use App\Http\Controllers\Dashboard\SupportController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\Dashboard\BannerController;
use Modules\Cms\Http\Controllers\Dashboard\MessageController;
use Modules\Cms\Http\Controllers\Dashboard\PageController;
use Modules\Cms\Http\Controllers\Dashboard\QuestionController;
use Modules\Geo\Http\Controllers\Dashboard\CityController;
use Modules\Geo\Http\Controllers\Dashboard\NationalityController;
use Modules\Geo\Http\Controllers\Dashboard\RegionController;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    static function () {
        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], static function () {
            Route::group(['middleware' => ['guest:admin']], static function () {
                Route::get('/login', [AuthController::class, 'loginForm'])->name('login.form');
                Route::post('/login', [AuthController::class, 'login'])->name('login');
            });
            Route::middleware('auth:admin')->group(function () {
                Route::get('/', HomeController::class)->name('home');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::resource('roles', RoleController::class)->except(['show']);
                Route::resource('admins', AdminController::class)->except(['show']);
                Route::resource('categories', CategoryController::class)->except(['show']);
                Route::resource('banners', BannerController::class)->except(['show']);
                Route::resource('skills', SkillController::class)->except(['show']);
                Route::resource('regions', RegionController::class)->except(['show']);
                Route::resource('cities', CityController::class)->except(['show']);
                Route::resource('nationalities', NationalityController::class)->except(['show']);
                Route::resource('provider-types', ProviderTypeController::class)->except(['show']);
                Route::controller(ProviderController::class)->prefix('providers')->as('providers.')->group(function () {
                    Route::put('/{provider}/status', 'updateStatus')->name('update-status');
                });
                Route::resource('providers', ProviderController::class);
                Route::controller(UserController::class)->prefix('users')->as('users.')->group(function () {
                    Route::put('/{user}/status', 'updateStatus')->name('update-status');
                });
                Route::resource('users', UserController::class);
                Route::resource('pages', PageController::class)->except(['show']);
                Route::resource('questions', QuestionController::class)->except(['show']);
                Route::controller(MessageController::class)->prefix('messages')->as('messages.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::delete('/{message}', 'destroy')->name('destroy');
                });
                Route::prefix('/orders')->controller(OrderController::class)->as('orders.')->group(static function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{order}', 'show')->name('show');
                    Route::get('/{order}/conversation-messages', 'conversationMessages')->name('conversation-messages');
                });
                Route::controller(SupportController::class)->prefix('support')->as('support.')->group(function () {
                    Route::get('/tickets', 'index')->name('tickets.index');
                    Route::get('/tickets/{ticket}', 'show')->name('tickets.show');
                    Route::post('/tickets/{ticket}', 'openChat')->name('tickets.open-chat');
                    Route::put('/tickets/{ticket}/status', 'updateStatus')->name('tickets.update-status');
                });
                Route::controller(PanAnalyticsController::class)->prefix('pan-analytics')->as('pan-analytics.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/export', 'export')->name('export');
                    Route::delete('/clear', 'clear')->name('clear');
                });
            });
        });
    }
);
