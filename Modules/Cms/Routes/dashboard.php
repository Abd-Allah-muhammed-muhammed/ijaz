<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\Dashboard\BannerController;
use Modules\Cms\Http\Controllers\Dashboard\MessageController;
use Modules\Cms\Http\Controllers\Dashboard\PageController;
use Modules\Cms\Http\Controllers\Dashboard\QuestionController;

Route::middleware(['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth:admin'])
    ->group(function () {
        Route::resource('banners', BannerController::class)->except(['show']);
        Route::resource('pages', PageController::class)->except(['show']);
        Route::resource('questions', QuestionController::class)->except(['show']);
        Route::controller(MessageController::class)->prefix('messages')->as('messages.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::delete('/{message}', 'destroy')->name('destroy');
        });
    });
