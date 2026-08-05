<?php

use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;

test('tryIncrementPendingDebitIfAvailable succeeds when balance is sufficient', function () {
    $user = createWalletUser();
    fundWallet($user, 600);

    $wallet = $user->wallet()->firstOrFail();
    $repo = app(WalletRepositoryInterface::class);

    $ok = $repo->tryIncrementPendingDebitIfAvailable($wallet, 500.0);

    expect($ok)->toBeTrue()
        ->and((float) $wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $wallet->fresh()->balance)->toBe(600.0);
});

test('tryIncrementPendingDebitIfAvailable fails when balance is insufficient', function () {
    $user = createWalletUser();
    fundWallet($user, 100);

    $wallet = $user->wallet()->firstOrFail();
    $repo = app(WalletRepositoryInterface::class);

    $ok = $repo->tryIncrementPendingDebitIfAvailable($wallet, 200.0);

    expect($ok)->toBeFalse()
        ->and((float) $wallet->fresh()->pending_debit)->toBe(0.0);
});

test('tryIncrementPendingDebitIfAvailable accounts for existing pending debit', function () {
    $user = createWalletUser();
    fundWallet($user, 600);

    $wallet = $user->wallet()->firstOrFail();
    $wallet->update(['pending_debit' => 500]);
    $repo = app(WalletRepositoryInterface::class);

    expect($repo->tryIncrementPendingDebitIfAvailable($wallet->fresh(), 101.0))->toBeFalse()
        ->and($repo->tryIncrementPendingDebitIfAvailable($wallet->fresh(), 100.0))->toBeTrue()
        ->and((float) $wallet->fresh()->pending_debit)->toBe(600.0);
});

test('tryIncrementPendingDebitIfAvailable is safe under sequential oversubscription', function () {
    $user = createWalletUser();
    fundWallet($user, 600);

    $wallet = $user->wallet()->firstOrFail();
    $repo = app(WalletRepositoryInterface::class);

    $successes = 0;
    foreach (range(1, 5) as $_) {
        if ($repo->tryIncrementPendingDebitIfAvailable($wallet->fresh(), 200.0)) {
            $successes++;
        }
    }

    expect($successes)->toBe(3)
        ->and((float) $wallet->fresh()->pending_debit)->toBe(600.0);
});
