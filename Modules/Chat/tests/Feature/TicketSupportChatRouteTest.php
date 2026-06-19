<?php

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Http\Controllers\V1\TicketSupportChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;

test('unauthenticated cannot list ticket chats', function () {
    $this->getJson(action([TicketSupportChatController::class, 'index']))
        ->assertUnauthorized();
});

test('user can list ticket chats', function () {
    ['user' => $user] = createTicketSupportConversation();

    Sanctum::actingAs($user);

    $this->getJson(action([TicketSupportChatController::class, 'index']))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('ticket owner can view messages', function () {
    ['user' => $user, 'conversation' => $conversation] = createTicketSupportConversation();

    Sanctum::actingAs($user);

    $this->getJson(action([TicketSupportChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('non-participant cannot view ticket messages', function () {
    ['conversation' => $conversation] = createTicketSupportConversation();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(action([TicketSupportChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertForbidden();
});

test('ticket owner can send message', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['user' => $user, 'conversation' => $conversation] = createTicketSupportConversation();
    $conversation->load(['user1', 'user2']);

    Sanctum::actingAs($user);

    $this->postJson(action([TicketSupportChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Need help please',
    ])->assertSuccessful()
        ->assertJsonPath('data.content', 'Need help please');
});

test('non-participant cannot send ticket message', function () {
    ['conversation' => $conversation] = createTicketSupportConversation();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([TicketSupportChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Blocked',
    ])->assertForbidden();
});

test('empty body returns 422 for ticket chat send', function () {
    ['user' => $user, 'conversation' => $conversation] = createTicketSupportConversation();

    Sanctum::actingAs($user);

    $this->postJson(action([TicketSupportChatController::class, 'send'], ['conversation' => $conversation->id]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content', 'files']);
});
