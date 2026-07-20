<?php

use App\Models\User;
use Modules\Chat\Support\ParticipantConversationMessenger;

test('getReceiver returns user2 when sender is user1', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->load(['user1', 'user2']);

    $messenger = new ParticipantConversationMessenger($conversation);
    $method = new ReflectionMethod($messenger, 'getReceiver');
    $method->setAccessible(true);

    expect($method->invoke($messenger, $user1)->is($user2))->toBeTrue();
});

test('getReceiver returns user1 when sender is user2', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->load(['user1', 'user2']);

    $messenger = new ParticipantConversationMessenger($conversation);
    $method = new ReflectionMethod($messenger, 'getReceiver');
    $method->setAccessible(true);

    expect($method->invoke($messenger, $user2)->is($user1))->toBeTrue();
});

test('getReceiver works with UUID comparison', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->update([
        'user1_id' => (string) $user1->getKey(),
        'user2_id' => (string) $user2->getKey(),
    ]);
    $conversation->load(['user1', 'user2']);

    $messenger = new ParticipantConversationMessenger($conversation->fresh());
    $method = new ReflectionMethod($messenger, 'getReceiver');
    $method->setAccessible(true);

    expect($method->invoke($messenger, $user1)->is($user2))->toBeTrue();
});
