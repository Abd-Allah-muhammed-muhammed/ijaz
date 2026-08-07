<?php

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Support\Facades\Event;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Infrastructure\Events\UserTypingEvent;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('typing event broadcasts on the presence channel with the correct sender info', function () {
    Event::fake([UserTypingEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $this->actingAs($provider, 'provider')
        ->postJson(action([OrderChatController::class, 'typing'], ['conversation' => $conversation->id]))
        ->assertSuccessful();

    Event::assertDispatched(UserTypingEvent::class, function (UserTypingEvent $event) use ($conversation, $provider) {
        expect($event->broadcastAs())->toBe('typing')
            ->and($event->broadcastOn())->toHaveCount(1)
            ->and($event->broadcastOn()[0])->toBeInstanceOf(PresenceChannel::class)
            ->and($event->broadcastOn()[0]->name)->toBe('presence-chats.'.$conversation->id);

        $payload = $event->broadcastWith();

        expect($payload['id'])->toEqual($provider->getKey())
            ->and($payload['socket_id'])->toBe($provider->getAuthIdentifierForBroadcasting())
            ->and($payload['name'])->toBe($provider->name);

        return true;
    });
});

test('typing indicator API endpoint requires a valid conversation participant', function () {
    Event::fake([UserTypingEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $stranger = createTestProvider();

    $this->actingAs($stranger, 'provider')
        ->postJson(action([OrderChatController::class, 'typing'], ['conversation' => $conversation->id]))
        ->assertForbidden();

    Event::assertNotDispatched(UserTypingEvent::class);
});

test('admin typing on an order conversation broadcasts on the conversation channel, not the order id channel', function () {
    Event::fake([UserTypingEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $admin = createOrdersAdmin();

    expect($conversation->id)->not->toBe($order->id);

    $this->actingAs($admin, 'admin')
        ->postJson(action([OrderController::class, 'conversationTyping'], ['order' => $order->id]))
        ->assertSuccessful();

    Event::assertDispatched(UserTypingEvent::class, function (UserTypingEvent $event) use ($conversation, $order, $admin) {
        $channel = $event->broadcastOn()[0];

        expect($channel)->toBeInstanceOf(PresenceChannel::class)
            ->and($channel->name)->toBe('presence-chats.'.$conversation->id)
            ->and($channel->name)->not->toBe('presence-chats.'.$order->id)
            ->and($event->broadcastAs())->toBe('typing');

        $payload = $event->broadcastWith();

        expect($payload['socket_id'])->toBe($admin->getAuthIdentifierForBroadcasting());

        return true;
    });
});
