<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Http\Controllers\Api\V1\TicketSupportChatController;
use Modules\Chat\Http\Resources\ConversationAttachmentResource;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Models\System;
use Modules\Orders\Actions\Dashboard\SendAdminOrderConversationMessageAction;

test('ticket conversation list response includes lastMessage sender and media, and unread count, matching Order/Member list shape', function () {
    Storage::fake('public');

    ['user' => $user, 'conversation' => $conversation] = createTicketSupportConversation();
    $system = System::query()->findOrFail(1);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $system->getKey(),
        'sender_type' => System::class,
        'receiver_id' => $user->getKey(),
        'receiver_type' => User::class,
        'content' => 'Support reply with attachment',
        'has_attachments' => true,
        'read_at' => null,
    ]);

    $message
        ->addMedia(UploadedFile::fake()->image('ticket-photo.jpg'))
        ->toMediaCollection('attachments', 'public');

    $conversation->forceFill([
        'last_message_id' => $message->id,
        'last_message_at' => now(),
    ])->save();

    Sanctum::actingAs($user);

    $listItem = $this->getJson(action([TicketSupportChatController::class, 'index']))
        ->assertSuccessful()
        ->json('data.items.0');

    expect($listItem)->toHaveKeys(['id', 'last_message', 'unread_count'])
        ->and($listItem['unread_count'])->toBe(1)
        ->and($listItem['last_message'])->toBeArray()
        ->and($listItem['last_message'])->toHaveKeys(['content', 'sender', 'attachments'])
        ->and($listItem['last_message']['sender'])->toBeArray()
        ->and($listItem['last_message']['sender']['id'])->toBe($system->getKey())
        ->and($listItem['last_message']['attachments'])->toBeArray()
        ->and($listItem['last_message']['attachments'])->not->toBeEmpty();
});

test('sending a message returns the actual created message without a redundant re-query', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->load(['user1', 'user2']);

    $message = app(SendMessageAction::class)->handle(
        $conversation,
        $user1,
        new ChatMessageData(content: 'Created without re-query'),
        new MemberChatHandler,
    );

    // wasRecentlyCreated is only true on the Eloquent instance returned from create(),
    // not on a row re-fetched via messages()->latest()->first().
    expect($message)->toBeInstanceOf(ConversationMessage::class)
        ->and($message->content)->toBe('Created without re-query')
        ->and($message->wasRecentlyCreated)->toBeTrue()
        ->and($message->id)->toBe($conversation->fresh()->last_message_id);

    // Admin order send path must return the created instance the same way.
    ['user' => $orderUser, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    createOrderConversation($orderUser, $provider, $order);
    $admin = createOrdersAdmin();

    $adminMessage = app(SendAdminOrderConversationMessageAction::class)->handle(
        $order,
        $admin,
        new ChatMessageData(content: 'Admin intervention message'),
    );

    expect($adminMessage)->toBeInstanceOf(ConversationMessage::class)
        ->and($adminMessage->content)->toBe('Admin intervention message')
        ->and($adminMessage->wasRecentlyCreated)->toBeTrue();
});

test('attachment availability check is cached within a single request, not re-checked per media item', function () {
    Storage::fake('public');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user1->getKey(),
        'sender_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'receiver_type' => User::class,
        'content' => null,
        'has_attachments' => true,
        'read_at' => null,
    ]);

    $media = $message
        ->addMedia(UploadedFile::fake()->image('cached.jpg'))
        ->toMediaCollection('attachments', 'public');

    $path = $media->getPathRelativeToRoot();

    $first = ConversationAttachmentResource::make($media)->resolve();
    expect($first['available'])->toBeTrue();

    // Delete the file so a second Storage::exists() would return false.
    Storage::disk('public')->delete($path);
    expect(Storage::disk('public')->exists($path))->toBeFalse();

    // Same request: cached true must win (proves we did not re-hit storage for this path).
    $second = ConversationAttachmentResource::make($media)->resolve();
    expect($second['available'])->toBeTrue();

    // Clear request-scoped cache — next check must observe the missing file.
    request()->attributes->remove(ConversationAttachmentResource::EXISTS_CACHE_ATTRIBUTE);
    $third = ConversationAttachmentResource::make($media)->resolve();
    expect($third['available'])->toBeFalse();
});
