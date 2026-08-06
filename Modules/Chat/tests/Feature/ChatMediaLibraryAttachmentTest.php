<?php

use App\Models\Provider;
use App\Services\Media\MediaAccessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('sending a chat message with an image attachment stores it via MediaLibrary and exposes size/mime/url', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);

    $response = $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            ['files' => [UploadedFile::fake()->image('chat-photo.jpg', 40, 40)]],
            ['Accept' => 'application/json'],
        )
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.attachments');

    $attachment = $response->json('data.attachments.0');

    expect($attachment)
        ->toHaveKeys(['id', 'file_name', 'mime_type', 'type', 'url', 'size'])
        ->and($attachment['file_name'])->toBe('chat-photo.jpg')
        ->and($attachment['type'])->toBe('image')
        ->and($attachment['mime_type'])->toStartWith('image/')
        ->and($attachment['url'])->not->toBeEmpty()
        ->and($attachment['size'])->not->toBeEmpty();

    $message = ConversationMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('has_attachments', true)
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->getMedia('attachments'))->toHaveCount(1)
        ->and($message->attachments()->count())->toBe(0);
});

test('sending a chat message with a PDF attachment stores it via MediaLibrary and exposes filename/size correctly', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);

    $response = $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            ['files' => [UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf')]],
            ['Accept' => 'application/json'],
        )
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.attachments');

    $attachment = $response->json('data.attachments.0');

    expect($attachment['file_name'])->toBe('contract.pdf')
        ->and($attachment['extension'])->toBe('pdf')
        ->and($attachment['type'])->toBe('pdf')
        ->and($attachment['size'])->not->toBeEmpty();

    $message = ConversationMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('has_attachments', true)
        ->first();

    expect($message->getMedia('attachments'))->toHaveCount(1)
        ->and($message->getFirstMedia('attachments')->file_name)->toBe('contract.pdf');
});

test('a broadcast NewMessageEvent payload includes MediaLibrary-backed attachment data in the same shape as the HTTP response', function () {
    Bus::fake();
    Event::fake([ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);

    $response = $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            [
                'content' => 'See attached',
                'files' => [UploadedFile::fake()->image('broadcast.jpg', 20, 20)],
            ],
            ['Accept' => 'application/json'],
        )
        ->assertSuccessful();

    $httpPayload = $response->json('data');

    $message = ConversationMessage::query()->findOrFail($httpPayload['id']);
    $message->loadMissing(['sender', 'media']);

    $broadcastPayload = (new NewMessageEvent($message))->broadcastWith();
    $resourcePayload = json_decode(
        json_encode(ConversationMessageResource::make($message)->resolve()),
        true,
    );

    expect($broadcastPayload)->toBe($resourcePayload)
        ->and($broadcastPayload['attachments'])->toHaveCount(1)
        ->and($broadcastPayload['attachments'][0])->toHaveKeys(['id', 'file_name', 'mime_type', 'type', 'url', 'size'])
        ->and($broadcastPayload['attachments'][0]['file_name'])->toBe('broadcast.jpg')
        ->and($broadcastPayload['attachments'][0]['id'])->toBe($httpPayload['attachments'][0]['id']);
});

test('existing chat media access control (MediaAccessService::resolveChat) still correctly authorizes sender/receiver for MediaLibrary-stored chat files', function () {
    Storage::fake('local');

    $sender = createWalletProvider();
    $receiver = createWalletProvider();
    $outsider = createWalletProvider();

    $conversation = Conversation::query()->create([
        'user1_id' => $sender->getKey(),
        'user1_type' => Provider::class,
        'user2_id' => $receiver->getKey(),
        'user2_type' => Provider::class,
    ]);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $sender->getKey(),
        'receiver_type' => Provider::class,
        'receiver_id' => $receiver->getKey(),
        'content' => 'attachment',
        'has_attachments' => true,
    ]);

    $media = $message
        ->addMedia(UploadedFile::fake()->image('acl.jpg'))
        ->toMediaCollection('attachments', 'local');

    $service = app(MediaAccessService::class);

    $this->actingAs($sender, 'provider');
    expect($service->authorizeAndResolvePath($media, 'chat'))->toBe($media->getPath());

    $this->actingAs($receiver, 'provider');
    expect($service->authorizeAndResolvePath($media, 'chat'))->toBe($media->getPath());

    $this->actingAs($outsider, 'provider');
    expect(fn () => $service->authorizeAndResolvePath($media, 'chat'))
        ->toThrow(HttpException::class);
});
