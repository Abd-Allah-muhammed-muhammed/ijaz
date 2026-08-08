<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Http\Controllers\Api\V1\MemberChatController;
use Modules\Chat\Http\Controllers\Api\V1\OrderChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Models\ConversationMessage;

test('conversation list response includes lastMessage sender and media, matching the open-conversation response shape', function () {
    Storage::fake('public');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user2->getKey(),
        'sender_type' => $user2::class,
        'receiver_id' => $user1->getKey(),
        'receiver_type' => $user1::class,
        'content' => 'Hello with attachment',
        'has_attachments' => true,
        'read_at' => null,
    ]);

    $message
        ->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->toMediaCollection('attachments', 'public');

    $conversation->forceFill([
        'last_message_id' => $message->id,
        'last_message_at' => now(),
    ])->save();

    Sanctum::actingAs($user1);

    $listItem = $this->getJson(action([MemberChatController::class, 'index']))
        ->assertSuccessful()
        ->json('data.items.0');

    expect($listItem)->toHaveKeys(['id', 'last_message', 'unread_count'])
        ->and($listItem['unread_count'])->toBe(1)
        ->and($listItem['last_message'])->toBeArray()
        ->and($listItem['last_message'])->toHaveKeys(['content', 'sender', 'attachments'])
        ->and($listItem['last_message']['sender'])->toBeArray()
        ->and($listItem['last_message']['sender']['id'])->toBe($user2->getKey())
        ->and($listItem['last_message']['attachments'])->toBeArray()
        ->and($listItem['last_message']['attachments'])->not->toBeEmpty();

    // Order list path must match the same last_message + unread shape.
    ['user' => $orderUser, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants($user1);
    $orderConversation = createOrderConversation($orderUser, $provider, $order);

    $orderMessage = ConversationMessage::query()->create([
        'conversation_id' => $orderConversation->id,
        'sender_id' => $provider->getKey(),
        'sender_type' => $provider::class,
        'receiver_id' => $orderUser->getKey(),
        'receiver_type' => $orderUser::class,
        'content' => 'Order hello',
        'has_attachments' => false,
        'read_at' => null,
    ]);
    $orderConversation->forceFill([
        'last_message_id' => $orderMessage->id,
        'last_message_at' => now(),
    ])->save();

    $orderListItem = $this->getJson(action([OrderChatController::class, 'index']))
        ->assertSuccessful()
        ->json('data.items.0');

    expect($orderListItem)->toHaveKeys(['id', 'last_message', 'unread_count'])
        ->and($orderListItem['last_message'])->toHaveKeys(['content', 'sender'])
        ->and($orderListItem['last_message']['sender']['id'])->toBe($provider->getKey())
        ->and($orderListItem['unread_count'])->toBe(1);
});

test('dispatching ChatUpdatedEvent with a precomputed unread_count does not trigger an additional COUNT query', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user2->getKey(),
        'sender_type' => $user2::class,
        'receiver_id' => $user1->getKey(),
        'receiver_type' => $user1::class,
        'content' => 'Unread for sender inbox semantics',
        'has_attachments' => false,
        'read_at' => null,
    ]);

    $conversation->load(['lastMessage.sender', 'lastMessage.media', 'user1', 'user2']);

    $capturing = false;
    $capturedSql = [];
    DB::listen(static function ($query) use (&$capturing, &$capturedSql): void {
        if ($capturing) {
            $capturedSql[] = $query->sql;
        }
    });

    $isUnreadCountSql = static function (string $sql): bool {
        $normalized = strtolower($sql);

        return str_contains($normalized, 'read_at')
            && str_contains($normalized, 'conversation_messages')
            && str_contains($normalized, 'count(');
    };

    $withPrecompute = new ChatUpdatedEvent($conversation, $user1, $user2, 7);
    $capturing = true;
    $capturedSql = [];
    $payload = $withPrecompute->broadcastWith();
    $capturing = false;
    $precomputeSql = $capturedSql;

    expect($payload['unread_count'])->toBe(7)
        ->and(collect($precomputeSql)->filter($isUnreadCountSql))->toBeEmpty();

    // Control: omitting the precomputed count still hits the fallback COUNT path.
    $withoutPrecompute = new ChatUpdatedEvent($conversation->fresh([
        'lastMessage.sender',
        'lastMessage.media',
        'user1',
        'user2',
    ]), $user1, $user2);

    $capturing = true;
    $capturedSql = [];
    $fallbackPayload = $withoutPrecompute->broadcastWith();
    $capturing = false;

    expect(collect($capturedSql)->filter($isUnreadCountSql))->not->toBeEmpty()
        ->and($fallbackPayload['unread_count'])->toBeInt();
});
