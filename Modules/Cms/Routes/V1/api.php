<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\V1\CmsController;
use Modules\Cms\Http\Controllers\V1\MessageController;

Route::prefix('catalog')->group(static function () {
    Route::controller(CmsController::class)->group(static function () {
        Route::get('/banners', 'banners');
        Route::get('/pages', 'pages');
        Route::get('/pages/{page}', 'page');
        Route::get('/questions', 'questions');
    });
});

Route::middleware('auth:sanctum')->group(static function () {
    Route::controller(MessageController::class)->prefix('messages')->group(static function () {
        Route::post('/', 'store');
    });
});
