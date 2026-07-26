<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\ProviderController;
use Illuminate\Support\Facades\Route;

// Shared: routes requiring an authenticated Sanctum session (OTP + Account)
Route::middleware('auth:sanctum')->group(static function () {

    // --- OTP ---
    Route::controller(OtpController::class)->prefix('otp')->group(static function () {
        Route::post('send', 'send');
        Route::post('verify', 'verify');
    });

    // --- Account (historical /api/v1/auth/* — NOT user login auth) ---
    /*
    | Distinct from /api/v1/user/auth/* (login/register/me/logout).
    | Do not unify without mobile coordination.
    */
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
});

// --- User Auth (own middleware shape: public login/register + nested user-api group) ---
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
