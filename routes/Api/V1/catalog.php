<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\GuaranteeRequestController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\OrderChatController;
use App\Http\Controllers\Api\V1\OtpController;
use App\Http\Controllers\Api\V1\TicketSupportChatController;
use App\Http\Controllers\Api\V1\TicketSupportController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/banners', 'banners');
        Route::get('/pages', 'pages');
        Route::get('/pages/{page}', 'page');
        Route::get('/settings', 'settings');
        Route::get('/questions', 'questions');
    });
});

Route::middleware('auth:sanctum')->group(static function () {
    Route::controller(ChatController::class)->prefix('chats')->group(static function () {
        Route::controller(OrderChatController::class)->prefix('orders')->group(static function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('/send/{conversation}', 'send');
            Route::get('/{conversation}', 'show');
        });
        Route::controller(TicketSupportChatController::class)->prefix('tickets')->group(static function () {
            Route::get('/', 'index');
            Route::post('/send/{conversation}', 'send');
            Route::get('/{conversation}', 'show');
        });
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/guarantee', 'guaranteeChatStore');
        Route::get('/guarantee', 'guaranteeChatIndex');

        require base_path('Modules/Opportunity/Routes/V1/chat.php');

        Route::post('/send/{conversation}', 'send');
        Route::get('/{conversation}/show', 'chat');
        Route::get('/{conversation}', 'show');
    });

    Route::controller(WalletController::class)->prefix('wallet')->group(static function () {
        Route::get('balance', 'walletBalance');
        Route::post('add-balance', 'walletAddBalance');
        Route::post('withdraw', 'walletWithdraw');
        Route::get('transaction', 'walletTransactions');
    });

    Route::controller(OtpController::class)->prefix('otp')->group(static function () {
        Route::post('send', 'send');
        Route::post('verify', 'verify');
    });

    Route::get('jobs/all', [JobController::class, 'all']);
    Route::get('jobs/{job}', [JobController::class, 'show']);
    Route::delete('jobs/{job}/media/{media}', [JobController::class, 'deleteMedia']);
    Route::apiResource('jobs', JobController::class)->except('show');

    Route::controller(GuaranteeRequestController::class)->prefix('guarantee-requests')->group(static function () {
        Route::get('/', 'index');
        Route::get('/assigned', 'assigned');
        Route::post('/', 'store');
        Route::get('/{guaranteeRequest}', 'show');
        Route::post('/{guaranteeRequest}/edit', 'edit');
        Route::delete('/{guaranteeRequest}/media/{media:uuid}', 'deleteMedia');
        Route::post('/{guaranteeRequest}/update-status', 'updateStatus');
        Route::delete('/{guaranteeRequest}', 'destroy');
        Route::post('/{guaranteeRequest}/pay', 'pay');
    });
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
