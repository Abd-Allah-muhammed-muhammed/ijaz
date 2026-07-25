<?php

use App\Http\Controllers\Dashboard\HomeController;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

it('renders dashboard home with order stats for an admin', function () {
    Order::factory()->create(['status' => OrderStatusEnum::New]);
    Order::factory()->create(['status' => OrderStatusEnum::InProgress]);

    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Home')
            ->where('stats.totalOrders', 2)
            ->has('pendingOrders')
            ->has('inProgressOrders')
            ->has('orderStatusDistribution')
        );
});
