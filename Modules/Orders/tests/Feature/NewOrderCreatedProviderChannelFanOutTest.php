<?php

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Events\NewOrderCreated;
use Modules\Orders\Models\Order;

test('creating an unassigned order notifies every provider in that category via their own provider-{id} channel', function () {
    $category = Category::factory()->create();
    $inCategoryA = createWalletProvider();
    $inCategoryB = createWalletProvider();
    $inCategoryA->categories()->attach($category->id);
    $inCategoryB->categories()->attach($category->id);

    $order = Order::factory()->create([
        'category_id' => $category->id,
        'provider_id' => null,
    ]);

    $channels = collect((new NewOrderCreated($order))->broadcastOn());
    $names = $channels->map(fn (PrivateChannel $channel) => $channel->name)->all();

    expect($channels)->toHaveCount(2)
        ->and($channels->every(fn ($channel) => $channel instanceof PrivateChannel))->toBeTrue()
        ->and($names)->toContain('private-provider-'.$inCategoryA->id)
        ->and($names)->toContain('private-provider-'.$inCategoryB->id);
});

test('a provider not in the order category does not receive the new-order notification', function () {
    $orderCategory = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    $eligible = createWalletProvider();
    $ineligible = createWalletProvider();
    $eligible->categories()->attach($orderCategory->id);
    $ineligible->categories()->attach($otherCategory->id);

    $order = Order::factory()->create([
        'category_id' => $orderCategory->id,
        'provider_id' => null,
    ]);

    $names = collect((new NewOrderCreated($order))->broadcastOn())
        ->map(fn (PrivateChannel $channel) => $channel->name)
        ->all();

    expect($names)->toContain('private-provider-'.$eligible->id)
        ->and($names)->not->toContain('private-provider-'.$ineligible->id)
        ->and($names)->not->toContain('private-category.'.$orderCategory->id);
});

test('NewOrderCreated broadcast payload shape is preserved for providers who do receive it', function () {
    $category = Category::factory()->create();
    $provider = createWalletProvider();
    $provider->categories()->attach($category->id);

    $order = Order::factory()->create([
        'category_id' => $category->id,
        'provider_id' => null,
    ]);

    $event = new NewOrderCreated($order);

    expect($event->broadcastAs())->toBe('new-order')
        ->and(array_keys($event->broadcastWith()))->toBe([
            'id',
            'title',
            'description',
            'expected_time',
            'budget_start',
            'budget_end',
            'category',
            'price',
            'status',
            'offers_count',
            'created_at',
            'media_count',
        ]);
});
