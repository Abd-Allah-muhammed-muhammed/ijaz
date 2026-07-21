<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\TicketSupportController;
use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\V1\CmsController;
use Modules\Cms\Http\Controllers\V1\MessageController;
use Modules\Jobs\Http\Controllers\V1\JobController;

Route::prefix('catalog')->group(static function () {
    Route::controller(CatalogController::class)->group(static function () {
        Route::prefix('categories')->group(static function () {
            Route::get('/', 'categories');
            Route::get('/{category}/children', 'categoryChildren');
            Route::get('/{category}/skills', 'categorySkills');
            Route::get('/with-no-children', 'categoriesWithNoChildren');
        });
        Route::prefix('regions')->group(static function () {
            Route::get('/', 'regions');
            Route::get('/{region}/cities', 'cities');
        });
        Route::get('/provider-types', 'providerTypes');
        Route::get('/nationalities', 'nationalities');

        Route::get('/providers', 'providers');
        Route::get('/settings', 'settings');
    });

    Route::controller(CmsController::class)->group(static function () {
        Route::get('/banners', 'banners');
        Route::get('/pages', 'pages');
        Route::get('/pages/{page}', 'page');
        Route::get('/questions', 'questions');
    });
});

Route::middleware('auth:sanctum')->group(static function () {

    Route::controller(OtpController::class)->prefix('otp')->group(static function () {
        Route::post('send', 'send');
        Route::post('verify', 'verify');
    });

    Route::get('jobs/all', [JobController::class, 'all']);
    Route::get('jobs/{job}', [JobController::class, 'show']);
    Route::delete('jobs/{job}/media/{media}', [JobController::class, 'deleteMedia']);
    Route::apiResource('jobs', JobController::class)->except('show');

    Route::controller(MessageController::class)->prefix('messages')->group(static function () {
        Route::post('/', 'store');
    });

    Route::controller(UserController::class)->prefix('auth')->group(static function () {
        Route::get('/counts', 'counts');
        Route::get('/notifications', 'notifications');
        Route::get('/notifications/mark-all-as-read', 'markAllNotificationsAsRead');
        Route::get('/notifications/{notification}/mark-as-read', 'markAsRead');
        Route::delete('/notifications/all', 'deleteAllNotification');
        Route::delete('/notifications/{notification}', 'deleteNotification');
        Route::post('/update-settings', 'updateSettings');

        Route::get('/delete-account', 'deleteAccount');
    });

    Route::controller(TicketSupportController::class)->prefix('tickets')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{ticketSupport}', 'show');
        Route::delete('/{ticketSupport}', 'destroy');
        Route::get('/{ticketSupport}/conversation', 'conversation');
        Route::post('/{ticketSupport}/conversation', 'conversationStore');
    });
});
