<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Resources\WalletTransactionResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

test('WalletTransactionResource resolves transfer_status correctly when operation.payoutRequest is eager-loaded', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $admin = createWalletAdmin();
    test()->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdraw->id)
        ->update(['status' => PayoutStatusEnum::Submitted]);

    $transaction = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('debit', '>', 0)
        ->with(['operation.payoutRequest'])
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($payload['transfer_status'])->toMatchArray([
        'value' => 'in_progress',
        'label' => __('payout.transfer_status.in_progress'),
        'color' => 'warning',
    ]);
});

test('accessing operation without eager-loading throws LazyLoadingViolationException when preventLazyLoading is enabled', function () {
    expect(Model::preventsLazyLoading())->toBeTrue();

    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $user->wallet->id)
        ->limit(2)
        ->get()
        ->first(fn (WalletTransaction $row): bool => $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawRequested);

    expect($transaction)->not->toBeNull()
        ->and($transaction->relationLoaded('operation'))->toBeFalse()
        ->and($transaction->preventsLazyLoading)->toBeTrue();

    expect(fn () => $transaction->operation)
        ->toThrow(LazyLoadingViolationException::class);
});

test('non-withdraw wallet transactions return null transfer_status without attempting any relation traversal', function () {
    $user = createWalletUser();
    fundWallet($user, 250);

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $user->wallet->id)
        ->where('credit', '>', 0)
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($transaction->relationLoaded('operation'))->toBeFalse()
        ->and($payload['transfer_status'])->toBeNull();
});
