<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Provider\ProviderChatIndexController;

/*
| Provider order-chat inbox (Orders domain filter on conversations).
| Mounted under the same provider/dashboard prefix as Chat's provider routes.
*/
Route::prefix('chat')->as('chat.')->group(function () {
    Route::get('/', ProviderChatIndexController::class)->name('index');
});
