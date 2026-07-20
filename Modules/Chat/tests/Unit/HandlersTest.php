<?php

use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Handlers\OrderChatHandler;
use Modules\Chat\Handlers\TicketSupportChatHandler;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\System;
use Modules\Chat\Support\ParticipantConversationMessenger;

test('MemberChatHandler listQuery returns only P2P conversations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    ['order' => $order] = createOrderWithParticipants($user);

    createMemberConversation($user, $other);
    Conversation::query()->create([
        'user1_id' => $user->getKey(),
        'user1_type' => User::class,
        'user2_id' => createTestProvider()->getKey(),
        'user2_type' => Provider::class,
        'operation_type' => Order::class,
        'operation_id' => $order->getKey(),
    ]);

    $results = (new MemberChatHandler)->listQuery($user)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->operation_type)->toBeNull();
});

test('MemberChatHandler listQuery filters by actor as user1 or user2', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    createMemberConversation($user1, $user2);
    createMemberConversation($user2, $user3);

    $results = (new MemberChatHandler)->listQuery($user2)->pluck('id');

    expect($results)->toHaveCount(2);
});

test('MemberChatHandler messenger returns ParticipantConversationMessenger', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());

    expect((new MemberChatHandler)->messenger($conversation))
        ->toBeInstanceOf(ParticipantConversationMessenger::class);
});

test('OrderChatHandler canOpen returns true for order user', function () {
    ['user' => $user, 'order' => $order] = createOrderWithParticipants();

    expect((new OrderChatHandler)->canOpen($user, $order))->toBeTrue();
});

test('OrderChatHandler canOpen returns true for order provider', function () {
    ['provider' => $provider, 'order' => $order] = createOrderWithParticipants();

    expect((new OrderChatHandler)->canOpen($provider, $order))->toBeTrue();
});

test('OrderChatHandler canOpen returns false for unrelated user', function () {
    ['order' => $order] = createOrderWithParticipants();
    $stranger = User::factory()->create();

    expect((new OrderChatHandler)->canOpen($stranger, $order))->toBeFalse();
});

test('OrderChatHandler listQuery returns only order conversations for actor', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    ['provider' => $provider, 'order' => $order] = createOrderWithParticipants($user);

    createOrderConversation($user, $provider, $order);
    createMemberConversation($user, $other);

    $results = (new OrderChatHandler)->listQuery($user)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->operation_type)->toBe(Order::class);
});

test('TicketSupportChatHandler canOpen returns true for ticket owner', function () {
    $user = User::factory()->create();
    $ticket = createTestTicketSupport($user);

    expect((new TicketSupportChatHandler)->canOpen($user, $ticket))->toBeTrue();
});

test('TicketSupportChatHandler canOpen returns false for other users', function () {
    $ticket = createTestTicketSupport();
    $stranger = User::factory()->create();

    expect((new TicketSupportChatHandler)->canOpen($stranger, $ticket))->toBeFalse();
});

test('TicketSupportChatHandler participants returns System and ticket user', function () {
    ensureSystemExists();
    $user = User::factory()->create();
    $ticket = createTestTicketSupport($user);

    [$system, $ticketUser] = (new TicketSupportChatHandler)->participants($ticket);

    expect($system)->toBeInstanceOf(System::class)
        ->and($ticketUser->is($user))->toBeTrue();
});
