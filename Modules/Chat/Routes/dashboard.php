<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Dashboard\SupportChatController;

Route::controller(SupportChatController::class)->prefix('support')->as('support.')->group(function () {
    Route::post('/tickets/{ticket}/send', 'send')->name('tickets.send');
});
