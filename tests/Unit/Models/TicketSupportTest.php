<?php

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Models\User;

it('has fillable attributes', function () {
    $fillable = [
        'user_type',
        'user_id',
        'operation_type',
        'operation_id',
        'title',
        'message',
        'status',
    ];

    $ticket = new TicketSupport;

    expect($ticket->getFillable())->toBe($fillable);
});

it('casts status to enum', function () {
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

    expect($ticket->status)->toBeInstanceOf(TicketSupportStatusEnum::class)
        ->and($ticket->status)->toBe(TicketSupportStatusEnum::Pending);
});

it('belongs to a user polymorphically', function () {
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

    expect($ticket->user)->toBeInstanceOf(User::class)
        ->and($ticket->user->is($user))->toBeTrue();
});

it('belongs to an operation polymorphically', function () {
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

    expect($ticket->operation)->toBeInstanceOf(Order::class)
        ->and($ticket->operation->is($order))->toBeTrue();
});

it('has timestamps', function () {
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

    expect($ticket->created_at)->not->toBeNull()
        ->and($ticket->updated_at)->not->toBeNull();
});
