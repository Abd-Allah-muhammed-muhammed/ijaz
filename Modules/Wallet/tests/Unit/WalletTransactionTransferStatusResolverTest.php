<?php

use App\Enums\OperationStatusEnum;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\MissingTransferStatusEagerLoadException;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;
use Modules\Wallet\Support\WalletTransactionTransferStatusResolver;

test('a service method resolves transfer_status correctly for a withdraw wallet transaction with eager-loaded operation.payoutRequest', function () {
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

    $status = app(WalletTransactionTransferStatusResolver::class)->resolve($transaction);

    expect($status)->toMatchArray([
        'value' => 'in_progress',
        'label' => __('payout.transfer_status.in_progress'),
        'color' => 'warning',
    ]);
});

test('calling the transfer_status resolution without the required eager-load throws or logs a clear, loud signal in local/testing environments, rather than silently returning null', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );

    $transaction = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', 'withdraw_requested')
        ->firstOrFail();

    $resolver = app(WalletTransactionTransferStatusResolver::class);

    expect(fn () => $resolver->resolve($transaction))
        ->toThrow(
            MissingTransferStatusEagerLoadException::class,
            'transfer_status requires operation to be eager-loaded on withdraw wallet transactions',
        );
});

test('non-withdraw wallet transactions return null transfer_status without attempting any relation traversal', function () {
    $user = createWalletUser();
    fundWallet($user, 250);

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $user->wallet->id)
        ->where('credit', '>', 0)
        ->firstOrFail();

    expect($transaction->relationLoaded('operation'))->toBeFalse();

    expect(app(WalletTransactionTransferStatusResolver::class)->resolve($transaction))->toBeNull();
});
