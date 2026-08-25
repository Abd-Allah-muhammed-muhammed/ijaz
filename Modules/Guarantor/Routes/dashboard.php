<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController;

Route::middleware([
    'localeSessionRedirect',
    'localizationRedirect',
    'localeViewPath',
    'auth:admin',
])->group(function () {

    Route::prefix('guarantor')->name('guarantor.')->group(function () {
        Route::get('/', [GuarantorController::class, 'index'])->name('index');
        Route::get('/{guarantorRequest}', [GuarantorController::class, 'show'])->name('show');
        Route::get('/{guarantorRequest}/conversation-messages', [GuarantorController::class, 'conversationMessages'])
            ->name('conversation-messages');
        Route::post('/{guarantorRequest}/conversation-messages', [GuarantorController::class, 'sendConversationMessage'])
            ->name('conversation-messages.store');
        Route::post('/{guarantorRequest}/conversation-typing', [GuarantorController::class, 'conversationTyping'])
            ->name('conversation-typing');
        Route::post('/{guarantorRequest}/approve', [GuarantorController::class, 'approveByAdmin'])->name('approveByAdmin');
        Route::post('/{guarantorRequest}/reject', [GuarantorController::class, 'rejectByAdmin'])->name('rejectByAdmin');
        Route::post('/{guarantorRequest}/cancel', [GuarantorController::class, 'cancel'])->name('cancel');
        Route::put('/{guarantorRequest}/resolve-dispute', [GuarantorController::class, 'resolveDispute'])->name('resolveDispute');
        Route::post('/{guarantorRequest}/installments/{installment}/release', [GuarantorController::class, 'releaseInstallment'])->name('releaseInstallment');
        Route::delete('/{guarantorRequest}', [GuarantorController::class, 'destroy'])->name('destroy');
    });
});
