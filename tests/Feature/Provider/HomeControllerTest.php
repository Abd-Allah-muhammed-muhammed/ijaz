<?php

use App\Http\Controllers\Provider\HomeController;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\Models\WithdrawRequest;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

it('renders provider home with order stats and recommendations', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->create([
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home')
            ->where('totalOrders', 1)
            ->has('recommendOrders')
            ->has('pendingOrders')
            ->has('inProgressOrders')
        );
});

test('provider home page exposes real wallet balance and amount_in_transfer, not hardcoded values', function () {
    $provider = createWalletProvider();
    $provider->wallet->update(['balance' => 4321.75]);

    $withdraw = createWithdrawFor($provider, ['amount' => 150]);

    PayoutRequest::factory()->create([
        'amount' => 150,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home')
            ->where('wallet.balance', number_format(4321.75, 2))
            ->where('wallet.amount_in_transfer', 150)
        );
});

test('provider home page exposes real totalOrders and totalFinishedOrders counts', function () {
    $provider = createWalletProvider();

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home')
            ->where('totalOrders', 3)
            ->where('totalFinishedOrders', 2)
        );
});

test('provider home page no longer includes any conversations-card-specific props tied to fake data', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home')
            ->missing('conversations')
            ->missing('conversationOrders')
            ->has('endedByProviderOrders')
        );
});
