<?php

use App\Models\Provider;
use App\Services\Media\MediaAccessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Http\Resources\ConversationAttachmentResource;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
        ->toHaveKeys(['id', 'file_name', 'mime_type', 'type', 'url', 'size', 'available'])
        ->and($attachment['file_name'])->toBe('chat-photo.jpg')
        ->and($attachment['type'])->toBe('image')
        ->and($attachment['mime_type'])->toStartWith('image/')
        ->and($attachment['url'])->not->toBeEmpty()
        ->and($attachment['size'])->not->toBeEmpty()
        ->and($attachment['available'])->toBeTrue();

    $message = ConversationMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('has_attachments', true)
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->getMedia('attachments'))->toHaveCount(1)
        ->and(file_exists(base_path('Modules/Chat/Models/ConversationAttachment.php')))->toBeFalse();
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
        ->and($broadcastPayload['attachments'][0])->toHaveKeys(['id', 'file_name', 'mime_type', 'type', 'url', 'size', 'available'])
        ->and($broadcastPayload['attachments'][0]['file_name'])->toBe('broadcast.jpg')
        ->and($broadcastPayload['attachments'][0]['available'])->toBeTrue()
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

test('ConversationAttachmentResource no longer references the legacy ConversationAttachment model', function () {
    $source = file_get_contents(
        base_path('Modules/Chat/Http/Resources/ConversationAttachmentResource.php')
    );

    expect($source)
        ->not->toContain('Modules\\Chat\\Models\\ConversationAttachment')
        ->not->toContain('fromLegacy')
        ->and(class_exists('Modules\\Chat\\Models\\ConversationAttachment', false))->toBeFalse()
        ->and(file_exists(base_path('Modules/Chat/Models/ConversationAttachment.php')))->toBeFalse();

    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => 'media only',
        'has_attachments' => true,
    ]);

    $media = $message
        ->addMedia(UploadedFile::fake()->image('ok.jpg', 10, 10))
        ->toMediaCollection('attachments', 'public');

    $payload = ConversationAttachmentResource::make($media)->resolve();

    expect($payload['available'])->toBeTrue()
        ->and($payload['file_name'])->toBe('ok.jpg')
        ->and($media)->toBeInstanceOf(Media::class);
});

test('a message with a missing MediaLibrary file shows a clear "attachment unavailable" placeholder with correct translated text, not a generic filename', function () {
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => null,
        'has_attachments' => true,
    ]);

    // Misleading MediaLibrary "name" (what used to leak as "Test File"-style text).
    $media = $message
        ->addMedia(UploadedFile::fake()->create('gone.pdf', 20, 'application/pdf'))
        ->usingName('Test File')
        ->usingFileName('gone.pdf')
        ->toMediaCollection('attachments', 'public');

    Storage::disk('public')->delete($media->getPathRelativeToRoot());

    $payload = ConversationMessageResource::make(
        $message->fresh()->load(['media', 'sender'])
    )->resolve();

    expect($payload['attachments'])->toHaveCount(1)
        ->and($payload['attachments'][0]['available'])->toBeFalse()
        ->and($payload['attachments'][0]['url'])->toBe('')
        ->and($payload['attachments'][0]['file_name'])->toBe('')
        ->and($payload['attachments'][0]['name'])->toBeNull()
        ->and($payload['attachments'][0]['label'])->toBe(__('This attachment is no longer available'))
        ->and($payload['attachments'][0]['label'])->not->toContain('Test File')
        ->and($payload['attachments'][0]['file_name'])->not->toContain('Test File');

    // Flag-only message (no media rows) also gets the honest placeholder — not a fake filename.
    $flagOnly = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => null,
        'has_attachments' => true,
    ]);

    $flagPayload = ConversationMessageResource::make(
        $flagOnly->load(['media', 'sender'])
    )->resolve();

    expect($flagPayload['attachments'])->toHaveCount(1)
        ->and($flagPayload['attachments'][0]['available'])->toBeFalse()
        ->and($flagPayload['attachments'][0]['file_name'])->toBe('')
        ->and($flagPayload['attachments'][0]['label'])->toBe(__('This attachment is no longer available'));
});

test('a message with a missing/deleted attachment file renders a graceful unavailable fallback instead of blank content', function () {
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    // MediaLibrary path: media row exists but the backing file was deleted.
    $mediaMessage = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => null,
        'has_attachments' => true,
    ]);

    $media = $mediaMessage
        ->addMedia(UploadedFile::fake()->create('gone.pdf', 20, 'application/pdf'))
        ->toMediaCollection('attachments', 'public');

    Storage::disk('public')->delete($media->getPathRelativeToRoot());

    $mediaPayload = ConversationMessageResource::make(
        $mediaMessage->fresh()->load(['media', 'sender'])
    )->resolve();

    expect($mediaPayload['attachments'])->toHaveCount(1)
        ->and($mediaPayload['attachments'][0]['available'])->toBeFalse()
        ->and($mediaPayload['attachments'][0]['file_name'])->toBe('')
        ->and($mediaPayload['attachments'][0]['label'])->toBe(__('This attachment is no longer available'))
        ->and($mediaPayload['attachments'][0]['url'])->toBe('');

    // Show endpoint must surface the unavailable card (not an empty attachments array).
    $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'available' => false,
            'label' => __('This attachment is no longer available'),
        ]);
});

test('a message with a missing attachment file still appears in the conversation with its text content and correct position, only the attachment shows the unavailable placeholder', function () {
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $before = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => 'message before missing attachment',
        'has_attachments' => false,
        'created_at' => now()->subMinutes(3),
        'updated_at' => now()->subMinutes(3),
    ]);

    $withMissing = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => 'caption still visible with missing file',
        'has_attachments' => true,
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $media = $withMissing
        ->addMedia(UploadedFile::fake()->create('vanished.pdf', 20, 'application/pdf'))
        ->toMediaCollection('attachments', 'public');

    Storage::disk('public')->delete($media->getPathRelativeToRoot());

    $after = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => Provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => 'message after missing attachment',
        'has_attachments' => false,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful();

    // API returns newest-first; message must still be present (never filtered out).
    $items = collect($response->json('data.items'));
    $ids = $items->pluck('id')->all();

    expect($ids)
        ->toContain($before->id)
        ->toContain($withMissing->id)
        ->toContain($after->id);

    $byContent = $items->keyBy('content');

    expect($byContent->has('caption still visible with missing file'))->toBeTrue()
        ->and($byContent['caption still visible with missing file']['id'])->toBe($withMissing->id)
        ->and($byContent['caption still visible with missing file']['content'])->toBe('caption still visible with missing file')
        ->and($byContent['caption still visible with missing file']['attachments'])->toHaveCount(1)
        ->and($byContent['caption still visible with missing file']['attachments'][0]['available'])->toBeFalse()
        ->and($byContent['caption still visible with missing file']['attachments'][0]['label'])
        ->toBe(__('This attachment is no longer available'));

    // Neighbors remain in the same page — message kept its place in the thread.
    expect($ids)->toContain($before->id)
        ->and($ids)->toContain($after->id);

    // Resource-level: text + unavailable attachment coexist — nothing strips the message.
    $resolved = ConversationMessageResource::make(
        $withMissing->fresh()->load(['media', 'sender'])
    )->resolve();

    expect($resolved['content'])->toBe('caption still visible with missing file')
        ->and($resolved['attachments'])->toHaveCount(1)
        ->and($resolved['attachments'][0]['available'])->toBeFalse();
});

test('provider chat send returns a proper validation error for an oversized file attachment', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    // max:5120 (kilobytes) — 6000 KB must fail validation.
    $oversized = UploadedFile::fake()->create('too-big.pdf', 6000, 'application/pdf');

    $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            ['files' => [$oversized]],
            ['Accept' => 'application/json'],
        )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['files.0']);
});

test('oversized file validation error shows a clean user-friendly message, not the raw files.N field path', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $oversized = UploadedFile::fake()->create('too-big.pdf', 6000, 'application/pdf');

    $response = $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            ['files' => [$oversized]],
            ['Accept' => 'application/json'],
        )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['files.0']);

    // Envelope uses literal key "files.0" (not nested files → 0).
    $messages = collect($response->json('errors')['files.0'] ?? [])
        ->flatten()
        ->map(fn ($m) => (string) $m)
        ->all();

    expect($messages)->not->toBeEmpty();

    foreach ($messages as $errorMessage) {
        expect($errorMessage)
            ->toBe(__('One of your files exceeds the 5MB limit.'))
            ->and($errorMessage)->not->toContain('files.0')
            ->and($errorMessage)->not->toMatch('/files\.\d+/');
    }
});

test('the clean validation message works correctly regardless of which file index is oversized', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $okSmall = UploadedFile::fake()->create('ok.pdf', 100, 'application/pdf');
    $oversized = UploadedFile::fake()->create('too-big.pdf', 6000, 'application/pdf');
    $okAlso = UploadedFile::fake()->create('also-ok.pdf', 80, 'application/pdf');

    // Index 1 is the oversized file (not index 0).
    $response = $this->actingAs($provider, 'provider')
        ->post(
            action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]),
            ['files' => [$okSmall, $oversized, $okAlso]],
            ['Accept' => 'application/json'],
        )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['files.1']);

    $messages = collect($response->json('errors')['files.1'] ?? [])
        ->flatten()
        ->map(fn ($m) => (string) $m)
        ->all();

    expect($messages)->not->toBeEmpty();

    foreach ($messages as $errorMessage) {
        expect($errorMessage)
            ->toBe(__('One of your files exceeds the 5MB limit.'))
            ->and($errorMessage)->not->toContain('files.1')
            ->and($errorMessage)->not->toMatch('/files\.\d+/');
    }
});
