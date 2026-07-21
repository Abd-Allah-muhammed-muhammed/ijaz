<?php

use Illuminate\Support\Facades\Route;
use Modules\Jobs\Http\Controllers\V1\JobController;

Route::middleware('auth:sanctum')->group(static function () {
    Route::get('jobs/all', [JobController::class, 'all']);
    Route::get('jobs/{job}', [JobController::class, 'show']);
    Route::delete('jobs/{job}/media/{media}', [JobController::class, 'deleteMedia']);
    Route::apiResource('jobs', JobController::class)->except('show');
});
