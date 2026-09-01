<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;

/*
| Admin dashboard order routes.
| Loaded under locale/dashboard with dashboard. name prefix (BaseModule mapDashboardRoutes).
*/
Route::middleware([
    'localeSessionRedirect',
    'localizationRedirect',
    'localeViewPath',
    'auth:admin',
])->group(static function () {
    Route::prefix('/orders')->controller(OrderController::class)->as('orders.')->group(static function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{order}', 'show')->name('show');
        Route::get('/{order}/conversation-messages', 'conversationMessages')->name('conversation-messages');
        Route::post('/{order}/conversation-messages', 'sendConversationMessage')->name('conversation-messages.store');
        Route::post('/{order}/conversation-typing', 'conversationTyping')->name('conversation-typing');
        Route::put('/{order}/resolve-dispute', 'resolveDispute')->name('resolveDispute');
    });
});
