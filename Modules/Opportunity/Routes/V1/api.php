<?php

use Illuminate\Support\Facades\Route;
use Modules\Opportunity\Http\Controllers\V1\CommentController;
use Modules\Opportunity\Http\Controllers\V1\OfferController;
use Modules\Opportunity\Http\Controllers\V1\OpportunityController;

Route::prefix('opportunities')->name('opportunities.')->group(function () {

    Route::get('all', [OpportunityController::class, 'all'])->name('all');
    Route::get('{opportunity}', [OpportunityController::class, 'show'])->name('show');
    Route::get('{opportunity}/comments', [CommentController::class, 'index'])->name('comments.index');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::post('/', [OpportunityController::class, 'store'])->name('store');
        Route::post('{opportunity}', [OpportunityController::class, 'update'])->name('update');
        Route::delete('{opportunity}', [OpportunityController::class, 'destroy'])->name('destroy');
        Route::delete('{opportunity}/media/{media:uuid}', [OpportunityController::class, 'deleteMedia'])->name('deleteMedia');

        Route::get('{opportunity}/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::post('{opportunity}/offers', [OfferController::class, 'store'])->name('offers.store');
        Route::post('{opportunity}/offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
        Route::post('{opportunity}/offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');

        Route::post('{opportunity}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('{opportunity}/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
});
