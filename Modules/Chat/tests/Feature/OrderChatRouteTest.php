<?php

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Http\Controllers\V1\OrderChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;

test('unauthenticated cannot list order chats', function () {
    $this->getJson(action([OrderChatController::class, 'index']))
        ->assertUnauthorized();
});

test('user can list own order chats', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    createOrderConversation($user, $provider, $order);

    Sanctum::actingAs($user);

    $this->getJson(action([OrderChatController::class, 'index']))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('user can open order chat', function () {
    ['user' => $user, 'order' => $order] = createOrderWithParticipants();

    Sanctum::actingAs($user);

    $this->postJson(action([OrderChatController::class, 'store']), [
        'order_id' => $order->getKey(),
    ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['id']]);
});

test('missing order_id returns 422', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([OrderChatController::class, 'store']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order_id']);
});

test('non-existent order_id returns 422', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([OrderChatController::class, 'store']), [
        'order_id' => '00000000-0000-0000-0000-000000000000',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['order_id']);
});

test('participant can view order messages', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    Sanctum::actingAs($user);

    $this->getJson(action([OrderChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('non-participant cannot view order messages', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(action([OrderChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertForbidden();
});

test('participant can send message in order chat', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);

    Sanctum::actingAs($user);

    $this->postJson(action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Order update',
    ])->assertSuccessful()
        ->assertJsonPath('data.content', 'Order update');
});

test('non-participant cannot send in order chat', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Blocked',
    ])->assertForbidden();
});

test('empty body returns 422 for order chat send', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    Sanctum::actingAs($user);

    $this->postJson(action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content', 'files']);
});
