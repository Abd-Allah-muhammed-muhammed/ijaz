<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Dashboard\CategoryController;
use Modules\Marketplace\Http\Controllers\Dashboard\ProviderTypeController;
use Modules\Marketplace\Http\Controllers\Dashboard\SkillController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('skills', SkillController::class)->except(['show']);
        Route::resource('provider-types', ProviderTypeController::class)->except(['show']);
    });
