<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Http\Controllers\V1\MemberChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;

test('unauthenticated cannot list chats', function () {
    $this->getJson(action([MemberChatController::class, 'index']))
        ->assertUnauthorized();
});

test('authenticated user can list member chats', function () {
    $user = User::factory()->create();
    createMemberConversation($user, User::factory()->create());

    Sanctum::actingAs($user);

    $this->getJson(action([MemberChatController::class, 'index']))
        ->assertSuccessful();
});

test('response has items array with pagination', function () {
    $user = User::factory()->create();
    createMemberConversation($user, User::factory()->create());

    Sanctum::actingAs($user);

    $this->getJson(action([MemberChatController::class, 'index']))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('can open P2P conversation', function () {
    $user = User::factory()->create();
    $receiver = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson(action([MemberChatController::class, 'store']), [
        'socket_id' => $receiver->getAuthIdentifierForBroadcasting(),
    ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['id']]);
});

test('opening same conversation twice returns existing (idempotent)', function () {
    $user = User::factory()->create();
    $receiver = User::factory()->create();

    Sanctum::actingAs($user);

    $first = $this->postJson(action([MemberChatController::class, 'store']), [
        'socket_id' => $receiver->getAuthIdentifierForBroadcasting(),
    ])->assertSuccessful()->json('data.id');

    $second = $this->postJson(action([MemberChatController::class, 'store']), [
        'socket_id' => $receiver->getAuthIdentifierForBroadcasting(),
    ])->assertSuccessful()->json('data.id');

    expect($first)->toBe($second);
});

test('participant can view messages', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    Sanctum::actingAs($user1);

    $this->getJson(action([MemberChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('non-participant cannot view messages', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(action([MemberChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertForbidden();
});

test('participant can send text message', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    Sanctum::actingAs($user1);

    $this->postJson(action([MemberChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Hello there',
    ])->assertSuccessful()
        ->assertJsonPath('data.content', 'Hello there');
});

test('participant can send file', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    Sanctum::actingAs($user1);

    $this->post(
        action([MemberChatController::class, 'send'], ['conversation' => $conversation->id]),
        ['files' => [UploadedFile::fake()->image('photo.jpg')]],
        ['Accept' => 'application/json'],
    )->assertSuccessful();
});

test('non-participant cannot send', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());

    Sanctum::actingAs(User::factory()->create());

    $this->postJson(action([MemberChatController::class, 'send'], ['conversation' => $conversation->id]), [
        'content' => 'Nope',
    ])->assertForbidden();
});

test('empty content and no file returns 422', function () {
    $user1 = User::factory()->create();
    $conversation = createMemberConversation($user1, User::factory()->create());

    Sanctum::actingAs($user1);

    $this->postJson(action([MemberChatController::class, 'send'], ['conversation' => $conversation->id]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content', 'files']);
});

test('participant can view conversation detail', function () {
    $user1 = User::factory()->create();
    $conversation = createMemberConversation($user1, User::factory()->create());

    Sanctum::actingAs($user1);

    $this->getJson(action([MemberChatController::class, 'chat'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['id']]);
});

test('non-participant cannot view conversation detail', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(action([MemberChatController::class, 'chat'], ['conversation' => $conversation->id]))
        ->assertForbidden();
});
