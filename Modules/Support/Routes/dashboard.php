<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\Dashboard\SupportChatController;

Route::middleware([
    'localeSessionRedirect',
    'localizationRedirect',
    'localeViewPath',
    'auth:admin',
])->group(function () {
    Route::controller(SupportChatController::class)->prefix('support')->as('support.')->group(function () {
        Route::post('/tickets/{ticket}/send', 'send')->name('tickets.send');
    });
});
