<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\Dashboard\SupportChatController;
use Modules\Support\Http\Controllers\Dashboard\SupportController;

Route::middleware([
    'localeSessionRedirect',
    'localizationRedirect',
    'localeViewPath',
    'auth:admin',
])->group(function () {
    Route::prefix('support')->as('support.')->group(function () {
        Route::controller(SupportController::class)->group(function () {
            Route::get('/tickets', 'index')->name('tickets.index');
            Route::get('/tickets/{ticket}', 'show')->name('tickets.show');
            Route::post('/tickets/{ticket}', 'openChat')->name('tickets.open-chat');
            Route::put('/tickets/{ticket}/status', 'updateStatus')->name('tickets.update-status');
        });

        Route::controller(SupportChatController::class)->group(function () {
            Route::post('/tickets/{ticket}/send', 'send')->name('tickets.send');
        });
    });
});
