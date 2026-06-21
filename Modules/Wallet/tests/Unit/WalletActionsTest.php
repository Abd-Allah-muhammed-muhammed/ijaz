<?php

use Illuminate\Support\Facades\DB;
use Modules\Wallet\Actions\AddPendingCreditAction;
use Modules\Wallet\Actions\AddPendingDebitAction;
use Modules\Wallet\Actions\AdjustPendingAction;
use Modules\Wallet\Actions\CreditWalletAction;
use Modules\Wallet\Actions\DebitWalletAction;
use Modules\Wallet\Actions\FinalizeWithdrawAction;
use Modules\Wallet\Actions\ReleasePendingCreditToBalanceAction;
use Modules\Wallet\Actions\ReversePendingCreditAction;
use Modules\Wallet\Actions\ReversePendingDebitAction;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

test('CreditWalletAction creates wallet transaction with correct fields', function () {
    $user = createWalletUser();
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(CreditWalletAction::class)->handle($user, 120, $operation, 'Credit test'));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->sole();

    expect((float) $transaction->credit)->toBe(120.0)
        ->and($transaction->operation_type)->toBe(TopUpRequest::class)
        ->and($transaction->operation_id)->toBe((string) $operation->getKey());
});

test('DebitWalletAction throws InsufficientBalanceException', function () {
    $user = createWalletUser();
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    expect(fn () => DB::transaction(fn () => app(DebitWalletAction::class)->handle($user, 5, $operation)))
        ->toThrow(InsufficientBalanceException::class);
});

test('AddPendingCreditAction creates correct ledger row', function () {
    $user = createWalletUser();
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(AddPendingCreditAction::class)->handle($user, 35, $operation));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->sole();

    expect((float) $transaction->pending_credit)->toBe(35.0)
        ->and((float) $user->wallet->fresh()->pending_credit)->toBe(35.0);
});

test('AddPendingDebitAction creates correct ledger row', function () {
    $user = createWalletUser();
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(AddPendingDebitAction::class)->handle($user, 45, $operation));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->sole();

    expect((float) $transaction->pending_debit)->toBe(45.0)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(45.0);
});

test('ReleasePendingCreditToBalanceAction moves gross from pending to net in balance', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 80]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(ReleasePendingCreditToBalanceAction::class)->handle($user, 80, 72, $operation));

    $wallet = $user->wallet->fresh();
    $transaction = WalletTransaction::query()->where('wallet_id', $wallet->id)->sole();

    expect((float) $wallet->pending_credit)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(72.0)
        ->and((float) $transaction->pending_credit)->toBe(-80.0)
        ->and((float) $transaction->credit)->toBe(72.0);
});

test('ReversePendingDebitAction creates negative pending_debit ledger row', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_debit' => 55]);
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(ReversePendingDebitAction::class)->handle($user, 55, $operation));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->sole();

    expect((float) $transaction->pending_debit)->toBe(-55.0)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0);
});

test('ReversePendingCreditAction creates negative pending_credit ledger row', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 65]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(ReversePendingCreditAction::class)->handle($user, 65, $operation));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->sole();

    expect((float) $transaction->pending_credit)->toBe(-65.0)
        ->and((float) $user->wallet->fresh()->pending_credit)->toBe(0.0);
});

test('AdjustPendingAction updates both pending_credit and pending_debit', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 10, 'pending_debit' => 30]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(AdjustPendingAction::class)->handle($user, 15, 10, $operation));

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->pending_credit)->toBe(25.0)
        ->and((float) $wallet->pending_debit)->toBe(20.0);
});

test('FinalizeWithdrawAction approved path debits balance', function () {
    $user = createWalletUser();
    fundWallet($user, 150);
    $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create(['amount' => 50]);

    DB::transaction(function () use ($user, $withdrawRequest) {
        app(AddPendingDebitAction::class)->handle($user, 50, $withdrawRequest);
        app(FinalizeWithdrawAction::class)->handle($user, $withdrawRequest, approved: true);
    });

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(100.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0);
});

test('FinalizeWithdrawAction rejected path only reverses pending', function () {
    $user = createWalletUser();
    fundWallet($user, 150);
    $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create(['amount' => 50]);

    DB::transaction(function () use ($user, $withdrawRequest) {
        app(AddPendingDebitAction::class)->handle($user, 50, $withdrawRequest);
        app(FinalizeWithdrawAction::class)->handle($user, $withdrawRequest, approved: false);
    });

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(150.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0);
});
