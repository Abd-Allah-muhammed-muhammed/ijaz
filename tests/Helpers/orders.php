<?php

use App\Enums\CategoryFeesTypeEnum;
use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\User;
use Database\Factories\OrderOfferFactory;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Marketplace\Models\Category;

/**
 * Shared helpers for Orders Step 0 regression-lock tests.
 */
function withoutOrdersLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

function createOrdersAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Orders Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
}

/**
 * @return array{owner: User, provider: Provider, category: Category, order: Order, offer: OrderOffer}
 */
function createOrderWithOffer(
    array $orderAttrs = [],
    array $offerAttrs = [],
    array $categoryAttrs = [],
): array {
    $owner = User::factory()->create();
    $provider = createWalletProvider();
    $category = Category::factory()->create(array_merge([
        'fees' => 10.0,
        'fees_type' => CategoryFeesTypeEnum::FIXED,
    ], $categoryAttrs));

    $order = Order::factory()->create(array_merge([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
        'price' => 0,
        'user_fees' => 0,
        'provider_fees' => 0,
    ], $orderAttrs));

    $offer = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(array_merge([
            'price' => 200.0,
            'description' => 'Regression offer',
            'status' => OfferStatusEnum::Pending,
        ], $offerAttrs));

    return compact('owner', 'provider', 'category', 'order', 'offer');
}

/**
 * Current User-controller fee formula (lock-in):
 * gatewayFees + categoryFees + (15% of categoryFees)
 * gateway key = PaymentService::getDefaultDriver().'_fees'
 */
function computeUserControllerOfferFees(float $categoryFees, float $gatewayFees): float
{
    return (float) $gatewayFees + $categoryFees + (15 / 100 * $categoryFees);
}

/**
 * Current Provider-controller fee formula (lock-in):
 * gatewayFees + categoryFees + (15% of categoryFees)
 * gateway key = config('payment.default').'_fees'
 *
 * KNOWN: expressions differ from User controller (getDefaultDriver vs config),
 * but PaymentService::getDefaultDriver() currently returns config('payment.default'),
 * so results match unless getDefaultDriver is overridden independently.
 */
function computeProviderControllerOfferFees(float $categoryFees, float $gatewayFees): float
{
    return floatval($gatewayFees) + $categoryFees + (15 / 100 * $categoryFees);
}
