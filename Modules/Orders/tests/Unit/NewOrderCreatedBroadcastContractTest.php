<?php

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Orders\Events\NewOrderCreated;
use Modules\Orders\Models\Order;

/**
 * Contract freeze for the provider Echo wire format.
 * Namespace may change; broadcastAs / broadcastOn / broadcastWith must not.
 */
it('locks NewOrderCreated broadcast wire contract', function () {
    $order = Order::factory()->create();
    $event = new NewOrderCreated($order);

    expect($event->broadcastAs())->toBe('new-order');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-category.'.$order->category_id);

    expect(array_keys($event->broadcastWith()))->toBe([
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
