<?php

use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Models\Order;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('provider can search own orders by title', function (): void {
    $provider = createWalletProvider();
    $otherProvider = createWalletProvider();

    $matching = Order::factory()->create([
        'provider_id' => $provider->id,
        'title' => 'UniquePlumbingRepairRequest',
        'status' => OrderStatusEnum::InProgress,
    ]);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'title' => 'UnrelatedElectricalJob',
        'status' => OrderStatusEnum::InProgress,
    ]);

    Order::factory()->create([
        'provider_id' => $otherProvider->id,
        'title' => 'UniquePlumbingRepairRequest',
        'status' => OrderStatusEnum::InProgress,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'index'], ['search' => 'UniquePlumbingRepairRequest']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});

test('provider can filter own orders by status', function (): void {
    $provider = createWalletProvider();

    $inProgress = Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByProvider,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'index'], ['status' => OrderStatusEnum::InProgress->value]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $inProgress->id)
        );
});

test('provider sees all statuses when no status filter is applied', function (): void {
    $provider = createWalletProvider();

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByProvider,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Index')
            ->has('rows.data', 2)
        );
});

test('provider can filter own orders by date range', function (): void {
    $provider = createWalletProvider();

    $inRange = Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
        'created_at' => now()->subDays(3),
    ]);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
        'created_at' => now()->subDays(20),
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'index'], [
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $inRange->id)
        );
});
