<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Http\Controllers\V1\GuarantorController;
use Modules\Guarantor\Http\Controllers\V1\InstallmentController;

Route::prefix('guarantor')->name('guarantor.')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/', [GuarantorController::class, 'index'])
            ->name('index');

        Route::post('/individual', [GuarantorController::class, 'storeIndividual'])
            ->name('store.individual');

        Route::post('/company', [GuarantorController::class, 'storeCompany'])
            ->name('store.company');

        Route::get('/{guarantorRequest}', [GuarantorController::class, 'show'])
            ->name('show');

        Route::post('/{guarantorRequest}', [GuarantorController::class, 'update'])
            ->name('update');

        Route::delete('/{guarantorRequest}', [GuarantorController::class, 'destroy'])
            ->name('destroy');

        Route::post('/{guarantorRequest}/status', [GuarantorController::class, 'updateStatus'])
            ->name('updateStatus');

        Route::post('/{guarantorRequest}/pay', [GuarantorController::class, 'pay'])
            ->name('pay');

        Route::delete('/{guarantorRequest}/media/{media:uuid}', [GuarantorController::class, 'deleteMedia'])
            ->name('deleteMedia');

        Route::prefix('{guarantorRequest}/installments')
            ->name('installments.')
            ->group(function () {

                Route::get('/', [InstallmentController::class, 'index'])
                    ->name('index');

                Route::post('/{installment}/pay', [InstallmentController::class, 'pay'])
                    ->name('pay');
            });
    });
});
