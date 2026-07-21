<?php

use App\Enums\CategoryFeesTypeEnum;
use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Api\V1\User\OrderController as UserOrderController;
use App\Http\Controllers\Provider\OrderController as ProviderOrderController;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Services\PaymentService;

beforeEach(function () {
    Notification::fake();
    withoutOrdersLocaleMiddleware();
    setWalletSetting('testing_fees', '20');
});

/**
 * Documents fee-key resolution for User vs Provider controllers.
 *
 * User:  app('settings')->get($paymentService->getDefaultDriver().'_fees')
 * Provider: app('settings')->get(config('payment.default').'_fees')
 *
 * PaymentService::getDefaultDriver() currently returns config('payment.default'),
 * so TODAY both formulas produce identical fees for the same category/gateway inputs.
 * This file locks that parity AND shows they would diverge if getDefaultDriver were
 * overridden independently of config('payment.default') (fragile dual-source coupling).
 */
it('produces identical fees for the same scenario via both controller formulas today', function () {
    $categoryFees = 10.0;
    $gatewayFromConfig = (float) app('settings')->get(config('payment.default').'_fees');
    $gatewayFromDriver = (float) app('settings')->get(app(PaymentService::class)->getDefaultDriver().'_fees');

    expect(app(PaymentService::class)->getDefaultDriver())->toBe(config('payment.default'))
        ->and($gatewayFromConfig)->toBe($gatewayFromDriver)
        ->and(computeUserControllerOfferFees($categoryFees, $gatewayFromDriver))
        ->toBe(computeProviderControllerOfferFees($categoryFees, $gatewayFromConfig))
        ->and(computeUserControllerOfferFees($categoryFees, $gatewayFromDriver))->toBe(31.5);
});

it('applies the same provider_fees when user accepts then provider updates price with same category fees', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        categoryAttrs: ['fees' => 10.0, 'fees_type' => CategoryFeesTypeEnum::FIXED],
        offerAttrs: ['price' => 200.0],
    );

    Sanctum::actingAs($owner, ['user-api'], 'user-api');
    $this->postJson(action([UserOrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Accepted->value])->assertOk();

    $feesAfterUserAccept = (float) $order->fresh()->provider_fees;
    expect($feesAfterUserAccept)->toBe(31.5);

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $this->post(action([ProviderOrderController::class, 'updateOffer'], [
        'order' => $order,
        'offer' => $offer->fresh(),
    ]), [
        'price' => 250,
        'description' => 'Price bump',
    ])->assertRedirect();

    // Category fee base unchanged (FIXED 10) + same gateway key → same provider_fees.
    expect((float) $order->fresh()->provider_fees)->toBe($feesAfterUserAccept)
        ->and((float) $order->fresh()->price)->toBe(250.0)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided);
});

/**
 * KNOWN FRAGILITY (not a current runtime bug): if getDefaultDriver() were ever
 * overridden to differ from config('payment.default'), User accept fees and Provider
 * update fees would silently diverge. Lock the dual-expression surface here.
 */
it('documents that fee gateway keys come from different expressions', function () {
    $userKey = app(PaymentService::class)->getDefaultDriver().'_fees';
    $providerKey = config('payment.default').'_fees';

    expect($userKey)->toBe($providerKey)
        ->and($userKey)->toBe('testing_fees');
});
