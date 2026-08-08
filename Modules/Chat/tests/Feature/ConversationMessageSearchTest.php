<?php

use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Models\ConversationMessage;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('searching within a conversation returns only messages matching the search term from that conversation', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    ['user' => $otherUser, 'provider' => $otherProvider, 'order' => $otherOrder] = createOrderWithParticipants();
    $otherConversation = createOrderConversation($otherUser, $otherProvider, $otherOrder);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $provider->getKey(),
        'sender_type' => $provider::class,
        'receiver_id' => $user->getKey(),
        'receiver_type' => $user::class,
        'content' => 'Please review the invoice draft',
        'has_attachments' => false,
    ]);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user->getKey(),
        'sender_type' => $user::class,
        'receiver_id' => $provider->getKey(),
        'receiver_type' => $provider::class,
        'content' => 'Sounds good, see you tomorrow',
        'has_attachments' => false,
    ]);

    // Same search term exists in another conversation — must never leak across.
    ConversationMessage::query()->create([
        'conversation_id' => $otherConversation->id,
        'sender_id' => $otherProvider->getKey(),
        'sender_type' => $otherProvider::class,
        'receiver_id' => $otherUser->getKey(),
        'receiver_type' => $otherUser::class,
        'content' => 'Please review the invoice draft from other chat',
        'has_attachments' => false,
    ]);

    $response = $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
            'search' => 'invoice',
        ]))
        ->assertSuccessful();

    $items = collect($response->json('data.items'));

    expect($items)->toHaveCount(1)
        ->and($items->first()['content'])->toBe('Please review the invoice draft')
        ->and($items->pluck('content')->all())->not->toContain('Sounds good, see you tomorrow')
        ->and($items->pluck('content')->all())->not->toContain('Please review the invoice draft from other chat');
});

test('conversation search respects participant authorization (cannot search a conversation you are not part of)', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $provider->getKey(),
        'sender_type' => $provider::class,
        'receiver_id' => $user->getKey(),
        'receiver_type' => $user::class,
        'content' => 'Secret invoice details',
        'has_attachments' => false,
    ]);

    $stranger = createTestProvider();

    $this->actingAs($stranger, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
            'search' => 'invoice',
        ]))
        ->assertForbidden();
});

test('searching within a conversation does not mark messages as read', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $unread = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user->getKey(),
        'sender_type' => $user::class,
        'receiver_id' => $provider->getKey(),
        'receiver_type' => $provider::class,
        'content' => 'Please review the invoice draft',
        'has_attachments' => false,
        'read_at' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
            'search' => 'invoice',
        ]))
        ->assertSuccessful();

    expect($unread->fresh()->read_at)->toBeNull();

    // Control: listing without search still marks as read for the actor.
    $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], [
            'conversation' => $conversation->id,
        ]))
        ->assertSuccessful();

    expect($unread->fresh()->read_at)->not->toBeNull();
});
