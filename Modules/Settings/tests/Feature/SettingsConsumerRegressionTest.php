<?php

use App\Support\LookupCache;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Payment\Services\PaymentService;
use Modules\Settings\Http\Controllers\Api\V1\SettingController;
use Modules\Settings\Models\Setting;
use Modules\Wallet\Actions\CreditProviderRegistrationBonusAction;
use Modules\Wallet\Http\Requests\Provider\WithdrawRequestRequest;
use Modules\Wallet\Http\Requests\StoreWithdrawRequest;

/**
 * Task 0 — Snapshot: every existing app('settings') consumer must keep resolving
 * the same key→value behavior after Modules/Settings extraction.
 */
beforeEach(function () {
    cache()->forget('settings');
    app()->forgetInstance('settings');
    LookupCache::forget('settings:public');

    Setting::query()->updateOrCreate(
        ['key' => 'min_withdraw_amount'],
        ['content' => '200', 'group' => 'wallet', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'provider_registration_bonus_enabled'],
        ['content' => '1', 'group' => 'wallet', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'provider_registration_bonus_amount'],
        ['content' => '50', 'group' => 'wallet', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'guarantee_fee_percent'],
        ['content' => '2.5', 'group' => 'guarantor', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'email'],
        ['content' => 'info@ijaz.sa', 'group' => 'general', 'is_public' => true],
    );

    $driverFeesKey = app(PaymentService::class)->getDefaultDriver().'_fees';
    Setting::query()->updateOrCreate(
        ['key' => $driverFeesKey],
        ['content' => '15', 'group' => 'payment', 'is_public' => false],
    );

    cache()->forget('settings');
    app()->forgetInstance('settings');
});

it('resolves the same keys each of the five consumers read via app(settings)', function () {
    // StoreWithdrawRequest / WithdrawRequestRequest
    expect((float) app('settings')->get('min_withdraw_amount', 200))->toBe(200.0);

    // CreditProviderRegistrationBonusAction
    expect(filter_var(app('settings')->get('provider_registration_bonus_enabled', true), FILTER_VALIDATE_BOOLEAN))->toBeTrue()
        ->and((float) app('settings')->get('provider_registration_bonus_amount', 50))->toBe(50.0);

    // CalculateOrderFeesAction
    $driverFeesKey = app(PaymentService::class)->getDefaultDriver().'_fees';
    expect((float) app('settings')->get($driverFeesKey, 0))->toBe(15.0);

    // Public settings API historically exposed the bag (allowlisted subset still includes these)
    expect(app('settings')->get('phone'))->toBe('966500000000')
        ->and(app('settings')->get('email'))->toBe('info@ijaz.sa')
        ->and(app('settings')->get('guarantee_fee_percent'))->toBe('2.5');
});

it('keeps catalog settings endpoint serving values sourced from app(settings)', function () {
    $response = $this->getJson(action([SettingController::class, 'settings']));

    $response->assertSuccessful()
        ->assertJsonPath('data.phone', '966500000000')
        ->assertJsonPath('data.email', 'info@ijaz.sa')
        ->assertJsonPath('data.min_withdraw_amount', '200')
        ->assertJsonPath('data.provider_registration_bonus_amount', '50')
        ->assertJsonPath('data.guarantee_fee_percent', '2.5');

    expect($response->json('data'))->not->toHaveKey('guarantee_fee');

    // Sensitive/dynamic payment fee keys default to private (is_public = false)
    $driverFeesKey = app(PaymentService::class)->getDefaultDriver().'_fees';
    $response->assertJsonMissing([$driverFeesKey => '15']);
});

it('keeps CalculateOrderFeesAction gateway key resolution identical', function () {
    $gatewayFeesKey = app(PaymentService::class)->getDefaultDriver().'_fees';
    $gatewayFees = (float) app('settings')->get($gatewayFeesKey, 0);

    expect($gatewayFees)->toBe(15.0)
        ->and(app(CalculateOrderFeesAction::class))->toBeInstanceOf(CalculateOrderFeesAction::class);
});

it('keeps CreditProviderRegistrationBonusAction settings resolution identical', function () {
    $enabled = filter_var(
        app('settings')->get('provider_registration_bonus_enabled', true),
        FILTER_VALIDATE_BOOLEAN,
    );
    $bonusAmount = (float) app('settings')->get('provider_registration_bonus_amount', 50);

    expect($enabled)->toBeTrue()
        ->and($bonusAmount)->toBe(50.0)
        ->and(app(CreditProviderRegistrationBonusAction::class))
        ->toBeInstanceOf(CreditProviderRegistrationBonusAction::class);
});

it('keeps StoreWithdrawRequest and WithdrawRequestRequest min rule sourced from app(settings)', function () {
    $apiRequest = StoreWithdrawRequest::create('/fake', 'POST');
    $apiRequest->setContainer(app());

    $providerRequest = WithdrawRequestRequest::create('/fake', 'POST');
    $providerRequest->setContainer(app());

    expect($apiRequest->rules()['amount'])->toContain('min:200')
        ->and($providerRequest->rules()['amount'])->toContain('min:200')
        ->and($apiRequest->messages()['amount.min'])->toBeString()
        ->and($providerRequest->messages()['amount.min'])->toBeString();
});
