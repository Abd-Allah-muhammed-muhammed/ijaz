<?php

use App\Enums\CategoryFeesTypeEnum;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Api\V1\OrderController as UserOrderController;
use Modules\Orders\Http\Controllers\Provider\OrderController as ProviderOrderController;
use Modules\Payment\Services\PaymentService;

beforeEach(function () {
    Notification::fake();
    withoutOrdersLocaleMiddleware();
    setWalletSetting('testing_fees', '20');
});

/**
 * Step 0 lock-in numbers must still hold after fee unification:
 * category FIXED fees=10, gateway testing_fees=20, offer 200
 * → provider_fees = 20 + 10 + (0.15 * 10) = 31.5
 */
it('produces provider_fees of 31.5 for the Step 0 concrete scenario via CalculateOrderFeesAction', function () {
    ['order' => $order, 'offer' => $offer] = createOrderWithOffer(
        categoryAttrs: ['fees' => 10.0, 'fees_type' => CategoryFeesTypeEnum::FIXED],
        offerAttrs: ['price' => 200.0],
    );

    $result = app(CalculateOrderFeesAction::class)->handle($order, (float) $offer->price);

    expect($result->providerFees)->toBe(31.5)
        ->and($result->userFees)->toBe(0.0)
        ->and($result->price)->toBe(200.0);
});

it('applies identical provider_fees when user accepts then provider updates price with same category fees', function () {
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

    // FIXED category fees + shared CalculateOrderFeesAction → same provider_fees.
    expect((float) $order->fresh()->provider_fees)->toBe($feesAfterUserAccept)
        ->and((float) $order->fresh()->price)->toBe(250.0)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided);
});

it('uses a single gateway fee key via PaymentService getDefaultDriver for both User and Provider paths', function () {
    $driverKey = app(PaymentService::class)->getDefaultDriver().'_fees';

    expect($driverKey)->toBe('testing_fees')
        ->and(app(PaymentService::class)->getDefaultDriver())->toBe(config('payment.default'));
});
