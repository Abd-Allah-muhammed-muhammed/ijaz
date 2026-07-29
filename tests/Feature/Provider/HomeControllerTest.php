<?php

use App\Http\Controllers\Provider\HomeController;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

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
