<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\Dashboard\SettingController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('settings/{key}/history', [SettingController::class, 'history'])->name('settings.history');
    });
