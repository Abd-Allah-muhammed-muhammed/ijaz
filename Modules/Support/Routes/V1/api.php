<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\V1\TicketSupportController;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(TicketSupportController::class)->prefix('tickets')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{ticketSupport}', 'show');
        Route::delete('/{ticketSupport}', 'destroy');
        Route::get('/{ticketSupport}/conversation', 'conversation');
        Route::post('/{ticketSupport}/conversation', 'conversationStore');
    });
});
