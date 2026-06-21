<?php

use Illuminate\Support\Facades\DB;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

test('credit increases wallet balance', function () {
    $user = createWalletUser();
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->credit($user, 100, $operation));

    expect((float) $user->wallet->fresh()->balance)->toBe(100.0);
});

test('credit creates ledger transaction', function () {
    $user = createWalletUser();
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->credit($user, 50, $operation, 'Test credit'));

    $transaction = WalletTransaction::query()->where('wallet_id', $user->wallet->id)->first();

    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->credit)->toBe(50.0)
        ->and((float) $transaction->balance_after)->toBe(50.0);
});

test('debit decreases wallet balance', function () {
    $user = createWalletUser();
    fundWallet($user, 200);
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->debit($user, 75, $operation));

    expect((float) $user->wallet->fresh()->balance)->toBe(125.0);
});

test('debit throws InsufficientBalanceException when balance too low', function () {
    $user = createWalletUser();
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    expect(fn () => DB::transaction(fn () => app(WalletService::class)->debit($user, 10, $operation)))
        ->toThrow(InsufficientBalanceException::class);
});

test('addPendingCredit increases pending_credit', function () {
    $user = createWalletUser();
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->addPendingCredit($user, 40, $operation));

    expect((float) $user->wallet->fresh()->pending_credit)->toBe(40.0);
});

test('addPendingDebit increases pending_debit', function () {
    $user = createWalletUser();
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 30, $operation));

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(30.0);
});

test('releasePendingCreditToBalance decreases pending_credit and increases balance by net amount', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 100]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->releasePendingCreditToBalance($user, 100, 90, $operation));

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->pending_credit)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(90.0);
});

test('reversePendingDebit decreases pending_debit', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_debit' => 25]);
    $operation = WithdrawRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->reversePendingDebit($user, 25, $operation));

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(0.0);
});

test('reversePendingCredit decreases pending_credit', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 60]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->reversePendingCredit($user, 60, $operation));

    expect((float) $user->wallet->fresh()->pending_credit)->toBe(0.0);
});

test('adjustPending increases pending_credit and decreases pending_debit in one operation', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_credit' => 5, 'pending_debit' => 20]);
    $operation = TopUpRequest::factory()->for($user, 'user')->create();

    DB::transaction(fn () => app(WalletService::class)->adjustPending($user, 10, 5, $operation));

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->pending_credit)->toBe(15.0)
        ->and((float) $wallet->pending_debit)->toBe(15.0);
});

test('canWithdraw returns true when available balance is sufficient', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    $user->wallet->update(['pending_debit' => 30]);

    expect(app(WalletService::class)->canWithdraw($user, 70))->toBeTrue();
});

test('canWithdraw returns false when available balance is insufficient', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    $user->wallet->update(['pending_debit' => 30]);

    expect(app(WalletService::class)->canWithdraw($user, 71))->toBeFalse();
});

test('getBalance returns WalletBalanceData with correct available amount', function () {
    $user = createWalletUser();
    fundWallet($user, 200);
    $user->wallet->update(['pending_credit' => 10, 'pending_debit' => 50]);

    $balance = app(WalletService::class)->getBalance($user);

    expect($balance->balance)->toBe(200.0)
        ->and($balance->pending_credit)->toBe(10.0)
        ->and($balance->pending_debit)->toBe(50.0)
        ->and($balance->available)->toBe(150.0);
});

test('finalizeWithdraw when approved debits balance and reverses pending', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create(['amount' => 40]);

    DB::transaction(function () use ($user, $withdrawRequest) {
        app(WalletService::class)->addPendingDebit($user, 40, $withdrawRequest);
        app(WalletService::class)->finalizeWithdraw($user, $withdrawRequest, approved: true);
    });

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(60.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0);
});

test('finalizeWithdraw when rejected only reverses pending debit', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create(['amount' => 40]);

    DB::transaction(function () use ($user, $withdrawRequest) {
        app(WalletService::class)->addPendingDebit($user, 40, $withdrawRequest);
        app(WalletService::class)->finalizeWithdraw($user, $withdrawRequest, approved: false);
    });

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(100.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0);
});
