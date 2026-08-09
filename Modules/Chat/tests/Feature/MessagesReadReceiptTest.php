<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\MarkMessagesAsReadAction;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Infrastructure\Events\MessagesReadEvent;
use Modules\Chat\Models\ConversationMessage;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;

test('marking a message as read broadcasts a live event to the sender', function () {
    Event::fake([MessagesReadEvent::class]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Please read me',
        'has_attachments' => false,
    ]);

    $ids = app(MarkMessagesAsReadAction::class)->handle($conversation, $user2);

    expect($ids)->toContain((string) $message->id)
        ->and($message->fresh()->read_at)->not->toBeNull();

    Event::assertDispatched(MessagesReadEvent::class, function (MessagesReadEvent $event) use ($conversation, $message) {
        expect($event->broadcastAs())->toBe(ChatEventEnum::Messages_Read->value)
            ->and($event->broadcastAs())->toBe('messages-read')
            ->and($event->conversationId)->toBe((string) $conversation->id)
            ->and($event->messageIds)->toContain((string) $message->id);

        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1)
            ->and($channels[0]->name)->toBe('presence-chats.'.$conversation->id);

        $payload = $event->broadcastWith();
        expect($payload)->toHaveKeys(['conversation_id', 'message_ids', 'read_at'])
            ->and($payload['message_ids'])->toContain((string) $message->id);

        return true;
    });
});

test('admin viewing/sending on an order conversation never marks messages as read for User/Provider', function () {
    withoutOrdersLocaleMiddleware();

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $admin = createOrdersAdmin();

    // Unread message from User → Provider (Provider is designated receiver).
    $toProvider = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => $user::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => $provider::class,
        'receiver_id' => $provider->getKey(),
        'content' => 'Hello provider',
        'has_attachments' => false,
    ]);

    // Unread message from Provider → User.
    $toUser = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => $provider::class,
        'sender_id' => $provider->getKey(),
        'receiver_type' => $user::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Hello user',
        'has_attachments' => false,
    ]);

    Event::fake([MessagesReadEvent::class]);

    // Explicit Option C guard: MarkMessagesAsReadAction with Admin is a no-op.
    expect(app(MarkMessagesAsReadAction::class)->handle($conversation, $admin))->toBe([])
        ->and($toProvider->fresh()->read_at)->toBeNull()
        ->and($toUser->fresh()->read_at)->toBeNull();

    Event::assertNotDispatched(MessagesReadEvent::class);

    // Admin Orders message list must not mark either side as read.
    $this->actingAs($admin, 'admin')
        ->getJson(action([OrderController::class, 'conversationMessages'], ['order' => $order]))
        ->assertSuccessful();

    expect($toProvider->fresh()->read_at)->toBeNull()
        ->and($toUser->fresh()->read_at)->toBeNull();

    // Admin sending support replies must not flip participant unread receipts.
    $this->actingAs($admin, 'admin')
        ->postJson(action([OrderController::class, 'sendConversationMessage'], ['order' => $order]), [
            'content' => 'Admin oversight note',
        ])
        ->assertSuccessful();

    expect($toProvider->fresh()->read_at)->toBeNull()
        ->and($toUser->fresh()->read_at)->toBeNull();

    // Control: the real receiver still can mark their own inbox as read.
    app(ListMessagesAction::class)->handle($conversation, $provider);

    expect($toProvider->fresh()->read_at)->not->toBeNull()
        ->and($toUser->fresh()->read_at)->toBeNull();
});

test('read receipt checkmarks only render on messages sent by the current user, never on incoming messages', function () {
    $messageIn = file_get_contents(resource_path('js/shared/chat/components/bubbles/message-in.tsx'));
    $messageOut = file_get_contents(resource_path('js/shared/chat/components/bubbles/message-out.tsx'));

    expect($messageIn)->not->toContain('double-check')
        ->and($messageIn)->not->toContain('iconName="check"')
        ->and($messageIn)->not->toContain('read_at')
        ->and($messageOut)->toContain('double-check')
        ->and($messageOut)->toContain('read_at');
});
