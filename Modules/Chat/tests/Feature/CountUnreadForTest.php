<?php

use App\Models\User;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Services\ConversationService;

test('countUnreadFor returns correct unread count for a user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createMemberConversation($user, $other);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Unread 1',
    ]);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Unread 2',
    ]);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Already read',
        'read_at' => now(),
    ]);

    expect(app(ConversationService::class)->countUnreadFor($user))->toBe(2);
});

test('countUnreadFor returns zero when no unread messages', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createMemberConversation($user, $other);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Already read',
        'read_at' => now(),
    ]);

    expect(app(ConversationService::class)->countUnreadFor($user))->toBe(0);
});

test('countUnreadFor only counts messages where actor is receiver, not sender', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createMemberConversation($user, $other);

    // Actor is sender — must not count
    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $other->getKey(),
        'content' => 'Sent by actor',
    ]);

    // Actor is receiver — must count
    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Received by actor',
    ]);

    expect(app(ConversationService::class)->countUnreadFor($user))->toBe(1);
});
