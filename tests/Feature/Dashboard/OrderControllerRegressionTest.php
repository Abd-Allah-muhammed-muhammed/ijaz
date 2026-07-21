<?php

use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Dashboard\OrderController;
use App\Models\Order;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

it('lists orders with stats for an admin', function () {
    Order::factory()->create(['status' => OrderStatusEnum::New]);
    Order::factory()->create(['status' => OrderStatusEnum::InProgress]);
    Order::factory()->create(['status' => OrderStatusEnum::EndedByClient]);
    Order::factory()->create(['status' => OrderStatusEnum::CancelledByClient]);

    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Orders/Index')
            ->has('rows.data', 4)
            ->has('stats')
            ->where('stats.total', 4)
            ->where('stats.active', 1)
            ->where('stats.pending', 1)
            ->where('stats.completed', 1)
            ->where('stats.cancelled', 1)
        );
});

it('filters dashboard orders by status', function () {
    Order::factory()->create(['status' => OrderStatusEnum::New]);
    Order::factory()->create(['status' => OrderStatusEnum::InProgress]);

    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index'], [
            'status' => OrderStatusEnum::New->value,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Orders/Index')
            ->has('rows.data', 1)
        );
});

it('shows a single order for an admin', function () {
    $order = Order::factory()->create();
    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Orders/Show')
            ->where('order.id', $order->id)
        );
});

it('returns conversation messages json payload for an order without a chat', function () {
    $order = Order::factory()->create();
    $admin = createOrdersAdmin();

    $this->actingAs($admin, 'admin')
        ->getJson(action([OrderController::class, 'conversationMessages'], ['order' => $order]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
});
