<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Wallet\Actions\CreditProviderRegistrationBonusAction;
use Modules\Wallet\Http\Controllers\V1\WalletController;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

test('provider registration credits bonus when enabled', function () {
    setWalletSetting('provider_registration_bonus_enabled', '1');
    setWalletSetting('provider_registration_bonus_amount', '50');

    $provider = createWalletProvider();

    DB::transaction(fn () => app(CreditProviderRegistrationBonusAction::class)->handle($provider));

    expect((float) $provider->wallet->fresh()->balance)->toBe(50.0)
        ->and(WalletTransaction::query()->where('wallet_id', $provider->wallet->id)->where('description', __('Registration bonus'))->exists())->toBeTrue();
});

test('provider registration does not credit bonus when disabled', function () {
    setWalletSetting('provider_registration_bonus_enabled', '0');
    setWalletSetting('provider_registration_bonus_amount', '50');

    $provider = createWalletProvider();

    DB::transaction(fn () => app(CreditProviderRegistrationBonusAction::class)->handle($provider));

    expect((float) $provider->wallet->fresh()->balance)->toBe(0.0)
        ->and(WalletTransaction::query()->where('wallet_id', $provider->wallet->id)->count())->toBe(0);
});

test('provider registration uses configured bonus amount from settings', function () {
    setWalletSetting('provider_registration_bonus_enabled', '1');
    setWalletSetting('provider_registration_bonus_amount', '75');

    $provider = createWalletProvider();

    DB::transaction(fn () => app(CreditProviderRegistrationBonusAction::class)->handle($provider));

    expect((float) $provider->wallet->fresh()->balance)->toBe(75.0);
});

test('withdraw request rejects amount below configured minimum', function () {
    setWalletSetting('min_withdraw_amount', '200');

    $user = createWalletUser();
    fundWallet($user, 500);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 199,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->count())->toBe(0);
});

test('withdraw request accepts amount equal to configured minimum', function () {
    setWalletSetting('min_withdraw_amount', '200');

    $user = createWalletUser();
    fundWallet($user, 500);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful();

    expect(WithdrawRequest::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('withdraw request rejects amount below default minimum when setting missing', function () {
    Setting::query()->where('key', 'min_withdraw_amount')->delete();
    cache()->forget('settings');
    app()->forgetInstance('settings');

    $user = createWalletUser();
    fundWallet($user, 500);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 199,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->count())->toBe(0);
});
