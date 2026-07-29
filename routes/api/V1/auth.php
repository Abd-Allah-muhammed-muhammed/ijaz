<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\ProviderController;
use Illuminate\Support\Facades\Route;

// Public OTP session endpoints (login/register — no pre-auth Sanctum token)
Route::group(['prefix' => 'otp', 'controller' => OtpController::class], static function () {
    Route::post('verify', 'verify');
    Route::post('resend', 'resend');
});

// Shared: routes requiring an authenticated Sanctum session
Route::middleware('auth:sanctum')->group(static function () {

    // --- OTP (authenticated purpose send + phone/email verify) ---
    Route::group(['prefix' => 'otp', 'controller' => OtpController::class], static function () {
        Route::post('send', 'send');
        Route::post('verify-purpose', 'verifyPurpose');
    });

    // --- Account (historical /api/v1/auth/* — NOT user login auth) ---
    /*
    | Distinct from /api/v1/user/auth/* (login/register/me/logout).
    | Do not unify without mobile coordination.
    */
    Route::group(['prefix' => 'auth', 'controller' => AccountController::class], static function () {
        Route::get('/counts', 'counts');
        Route::post('/update-settings', 'updateSettings');
        Route::get('/delete-account', 'deleteAccount');

        Route::group(['prefix' => 'notifications'], static function () {
            Route::get('/', 'notifications');
            Route::get('/mark-all-as-read', 'markAllNotificationsAsRead');
            Route::get('/{notification}/mark-as-read', 'markAsRead');
            Route::delete('/all', 'deleteAllNotification');
            Route::delete('/{notification}', 'deleteNotification');
        });
    });
});

// --- User Auth (own middleware shape: public login/register + nested user-api group) ---
Route::group(['prefix' => 'user'], static function () {

    // --- User Auth ---
    Route::group(['prefix' => 'auth', 'controller' => AuthController::class], static function () {

        // --- Login/Register ---
        Route::post('login', 'login');
        Route::post('register', 'register');

        // --- User API ---
        Route::middleware(['auth:user-api'])->group(static function () {
            Route::post('profile/update', 'profileUpdate');
            Route::get('me', 'auth');
            Route::post('logout', 'logout');
        });
    });

    // --- Providers ---
    Route::group(['middleware' => ['auth:user-api']], static function () {
        Route::group(['prefix' => 'providers', 'controller' => ProviderController::class], static function () {
            Route::get('/get', 'get');
        });
    });
});
