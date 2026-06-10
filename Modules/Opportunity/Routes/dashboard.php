<?php

use Illuminate\Support\Facades\Route;
use Modules\Opportunity\Http\Controllers\Dashboard\CommentController;
use Modules\Opportunity\Http\Controllers\Dashboard\OfferController;
use Modules\Opportunity\Http\Controllers\Dashboard\OpportunityController;

Route::middleware([
    'localeSessionRedirect',
    'localizationRedirect',
    'localeViewPath',
    'auth:admin',
])->group(function () {
    Route::delete('opportunities/offers/{offer}', [OfferController::class, 'destroy'])
        ->name('opportunities.offers.destroy');

    Route::delete('opportunities/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('opportunities.comments.destroy');

    Route::resource('opportunities', OpportunityController::class)
        ->only(['index', 'show', 'destroy']);
});
