<?php

use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\MessagesReadEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\ConversationMessage;

test('NewMessageEvent broadcasts immediately on presence chats.{id} as new-message', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user1->getKey(),
        'sender_type' => $user1::class,
        'receiver_id' => $user2->getKey(),
        'receiver_type' => $user2::class,
        'content' => 'hello',
        'has_attachments' => false,
    ]);
    $message->setRelation('sender', $user1);
    $message->setRelation('media', collect());

    $event = new NewMessageEvent($message);

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class)
        ->and($event->broadcastAs())->toBe(ChatEventEnum::New_Message->value)
        ->and($event->broadcastAs())->toBe('new-message');

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PresenceChannel::class)
        ->and($channels[0]->name)->toBe('presence-chats.'.$conversation->id);
});

test('ChatUpdatedEvent broadcasts on participant private socket channels as chat-updated', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $event = new ChatUpdatedEvent($conversation, $user1, $user2);

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class)
        ->and($event->broadcastAs())->toBe(ChatEventEnum::Chat_Updated->value)
        ->and($event->broadcastAs())->toBe('chat-updated');

    $channelNames = collect($event->broadcastOn())->map->name->all();

    expect($channelNames)->toContain('private-'.$user1->getAuthIdentifierForBroadcasting())
        ->and($channelNames)->toContain('private-'.$user2->getAuthIdentifierForBroadcasting())
        ->and($event->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class);
});

test('ConversationResource last_message_at is humanized relative text not ISO8601', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->forceFill(['last_message_at' => now()->subHours(2)])->save();

    $payload = ConversationResource::make($conversation)->resolve();

    expect($payload['last_message_at'])
        ->toBe($conversation->last_message_at->shortAbsoluteDiffForHumans())
        ->and($payload['last_massage_at'])
        ->toBe($conversation->last_message_at->shortAbsoluteDiffForHumans())
        // Guard against accidentally shipping ISO timestamps that Provider UI Date-parses.
        ->and($payload['last_message_at'])->not->toMatch('/^\d{4}-\d{2}-\d{2}T/')
        ->and($payload['last_message_at_iso'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

test('ConversationMessageResource created_at is humanized relative text not ISO8601', function () {
    $this->freezeSecond(function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = createMemberConversation($user1, $user2);

        $message = ConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->getKey(),
            'sender_type' => $user1::class,
            'receiver_id' => $user2->getKey(),
            'receiver_type' => $user2::class,
            'content' => 'hello',
            'has_attachments' => false,
        ]);

        $payload = ConversationMessageResource::make($message)->resolve();

        expect($payload['created_at'])
            ->toBe($message->created_at->shortAbsoluteDiffForHumans())
            ->and($payload['created_at'])->not->toMatch('/^\d{4}-\d{2}-\d{2}T/')
            ->and($payload['created_at_iso'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
    });
});

test('MessagesReadEvent broadcasts on presence chats.{id} as messages-read', function () {
    $event = new MessagesReadEvent('conv-1', ['msg-1', 'msg-2'], now()->toIso8601String());

    expect($event)->toBeInstanceOf(ShouldBroadcastNow::class)
        ->and($event->broadcastAs())->toBe(ChatEventEnum::Messages_Read->value)
        ->and($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class)
        ->and($event->broadcastOn()[0]->name)->toBe('presence-chats.conv-1')
        ->and($event->broadcastWith()['message_ids'])->toBe(['msg-1', 'msg-2']);
});
