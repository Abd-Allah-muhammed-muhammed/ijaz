<?php

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Resources\Api\V1\TicketSupportResource;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Models\User;

it('transforms ticket support to array', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $resource = new TicketSupportResource($ticket);
    $array = $resource->toArray(request());

    expect($array)->toBeArray()
        ->and($array['id'])->toBe($ticket->id)
        ->and($array['title'])->toBe('Test Ticket')
        ->and($array['message'])->toBe('Test message')
        ->and($array['status'])->toBeArray()
        ->and($array['status']['value'])->toBe('pending')
        ->and($array['status']['color'])->toBe('primary')
        ->and($array['user_id'])->toBe($user->id)
        ->and($array['user_type'])->toBe(User::class)
        ->and($array['operation_id'])->toBe($order->id)
        ->and($array['operation_type'])->toBe(Order::class)
        ->and($array['created_at'])->not->toBeNull()
        ->and($array['updated_at'])->not->toBeNull();
});

it('hides user fields when relation loaded', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $ticket->load('user');

    $resource = new TicketSupportResource($ticket);
    $array = $resource->resolve(request());

    expect($array)->not->toHaveKey('user_id')
        ->and($array)->not->toHaveKey('user_type');
});

it('hides operation fields when relation loaded', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $ticket = TicketSupport::create([
        'user_type' => User::class,
        'user_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'title' => 'Test Ticket',
        'message' => 'Test message',
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $ticket->load('operation');

    $resource = new TicketSupportResource($ticket);
    $array = $resource->resolve(request());

    expect($array)->not->toHaveKey('operation_id')
        ->and($array)->not->toHaveKey('operation_type');
});

it('formats different status types', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $statuses = [
        TicketSupportStatusEnum::Pending->value => ['value' => 'pending', 'color' => 'primary'],
        TicketSupportStatusEnum::Open->value => ['value' => 'open', 'color' => 'success'],
        TicketSupportStatusEnum::Closed->value => ['value' => 'closed', 'color' => 'danger'],
    ];

    foreach ($statuses as $status => $expected) {
        $ticket = TicketSupport::create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
            'title' => 'Test Ticket',
            'message' => 'Test message',
            'status' => $status,
        ]);

        $resource = new TicketSupportResource($ticket);
        $array = $resource->toArray(request());

        expect($array['status']['value'])->toBe($expected['value'])
            ->and($array['status']['color'])->toBe($expected['color']);
    }
});
