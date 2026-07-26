<?php

use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

function createAccountApiUser(array $attributes = []): User
{
    return User::factory()->create([
        'status' => UserStatusEnum::Active,
        ...$attributes,
    ]);
}

function createAccountApiNotification(User $user, ?string $readAt = null): DatabaseNotification
{
    return DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'data' => ['message' => 'test'],
        'read_at' => $readAt,
    ]);
}

test('GET /api/v1/auth/counts returns unread notification and message counts', function () {
    $user = createAccountApiUser();
    $other = createAccountApiUser();
    createAccountApiNotification($user);
    createAccountApiNotification($user, now()->toDateTimeString());

    $conversation = Conversation::query()->create([
        'user1_id' => $user->getKey(),
        'user1_type' => User::class,
        'user2_id' => $other->getKey(),
        'user2_type' => User::class,
    ]);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $other->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Unread',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/counts')
        ->assertSuccessful()
        ->assertJsonPath('data.unread_notifications_count', 1)
        ->assertJsonPath('data.unread_messages_count', 1);
});

test('GET /api/v1/auth/notifications returns paginated notifications for the authenticated user', function () {
    $user = createAccountApiUser();
    $other = createAccountApiUser();
    createAccountApiNotification($user);
    createAccountApiNotification($user);
    createAccountApiNotification($other);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/notifications')
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2);
});

test('GET /api/v1/auth/delete-account marks the account deleted and revokes tokens', function () {
    $user = createAccountApiUser();
    $user->createToken('login');
    $user->createToken('user-app');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/delete-account')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Account deleted successfully.');

    expect($user->fresh()->status)->toBe(UserStatusEnum::Deleted)
        ->and($user->tokens()->count())->toBe(0);
});

test('GET /api/v1/auth/notifications/{id}/mark-as-read returns 404 for another users notification', function () {
    $user = createAccountApiUser();
    $other = createAccountApiUser();
    $notification = createAccountApiNotification($other);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/auth/notifications/{$notification->id}/mark-as-read")
        ->assertNotFound()
        ->assertJsonPath('message', 'Notification not found or already read.');
});
