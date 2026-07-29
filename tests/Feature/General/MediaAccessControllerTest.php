<?php

use App\Models\Provider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

beforeEach(function () {
    Storage::fake('local');
});

test('media file download respects ownership', function () {
    $owner = createWalletProvider();
    $attacker = createWalletProvider();

    $media = $owner
        ->addMedia(UploadedFile::fake()->image('owned.jpg'))
        ->toMediaCollection('default', 'local');

    $this->actingAs($owner, 'provider')
        ->get(route('media.file-path', $media))
        ->assertSuccessful();

    $this->actingAs($attacker, 'provider')
        ->get(route('media.file-path', $media))
        ->assertNotFound();
});

test('chat media download respects sender/receiver check with correct guard', function () {
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
    ]);

    $media = $message
        ->addMedia(UploadedFile::fake()->image('chat.jpg'))
        ->toMediaCollection('default', 'local');

    $this->actingAs($sender, 'provider')
        ->get(route('media.chat.media', $media))
        ->assertSuccessful();

    $this->actingAs($receiver, 'provider')
        ->get(route('media.chat.media', $media))
        ->assertSuccessful();

    $this->actingAs($outsider, 'provider')
        ->get(route('media.chat.media', $media))
        ->assertNotFound();
});
