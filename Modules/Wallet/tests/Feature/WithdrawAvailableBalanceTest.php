<?php

use Laravel\Sanctum\Sanctum;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

test('withdrawal request exceeding total balance is rejected with 422', function () {
    setWalletSetting('min_withdraw_amount', '50');

    $user = createWalletUser();
    fundWallet($user, 600);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 601,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(600.0);
});

test('withdrawal request exceeding available balance after existing pending requests is rejected', function () {
    setWalletSetting('min_withdraw_amount', '50');

    $user = createWalletUser();
    fundWallet($user, 600);
    Sanctum::actingAs($user);

    // Hold 500 via an existing pending withdrawal → available = 100
    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 500,
    ])->assertSuccessful();

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(600.0);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 101,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(500.0);
});

test('withdrawal request within available balance (accounting for pending) succeeds', function () {
    setWalletSetting('min_withdraw_amount', '50');

    $user = createWalletUser();
    fundWallet($user, 600);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 400,
    ])->assertSuccessful();

    // available = 600 - 400 = 200 → 200 is exactly within available
    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful();

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(600.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(600.0);
});

test('multiple sequential withdrawal requests are correctly blocked once cumulative pending exceeds balance', function () {
    setWalletSetting('min_withdraw_amount', '50');

    $user = createWalletUser();
    fundWallet($user, 600);
    Sanctum::actingAs($user);

    // Exact scenario: 120 + 50 + 60 + 80 = 310 (within 600), then 1000 must fail
    foreach ([120, 50, 60, 80] as $amount) {
        $this->postJson(action([WalletController::class, 'withdraw']), [
            'amount' => $amount,
        ])->assertSuccessful();
    }

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(4)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(310.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(600.0);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 1000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(4)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(310.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(600.0);
});

test('service-layer create rejects overdraft after pending holds (action lock safety net)', function () {
    setWalletSetting('min_withdraw_amount', '50');

    $user = createWalletUser();
    fundWallet($user, 600);

    app(WithdrawRequestService::class)->create($user, new CreateWithdrawData(amount: 500, userNotes: null));

    expect(fn () => app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    ))->toThrow(InsufficientBalanceException::class);

    expect(WithdrawRequest::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(500.0);
});

test('insufficient available balance translation key exists in all locales', function () {
    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $translations = json_decode(
            file_get_contents(lang_path("{$locale}.json")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($translations)
            ->toHaveKey('insufficient_available_balance')
            ->and($translations['insufficient_available_balance'])->not->toBeEmpty()
            ->and($translations['insufficient_available_balance'])->toContain(':available')
            ->and($translations['insufficient_available_balance'])->toContain(':requested');
    }
});
