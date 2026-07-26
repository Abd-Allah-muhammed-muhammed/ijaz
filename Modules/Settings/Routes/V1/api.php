<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\Api\V1\SettingController;

Route::prefix('catalog')->group(static function () {
    Route::get('/settings', [SettingController::class, 'settings']);
});
