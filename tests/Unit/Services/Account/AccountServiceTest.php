<?php

use App\Contracts\Account\AccountRepositoryInterface;
use App\DTOs\Account\DeleteNotificationResult;
use App\DTOs\Account\MarkNotificationResult;
use App\DTOs\Account\UpdateAccountSettingsDTO;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use App\Repositories\Account\AccountRepository;
use App\Services\Account\AccountService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Services\ConversationService;

function createAccountUser(array $attributes = []): User
{
    return User::factory()->create([
        'status' => UserStatusEnum::Active,
        ...$attributes,
    ]);
}

function createAccountNotification(User $user, ?string $readAt = null): DatabaseNotification
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

test('counts returns unread notifications and unread messages via Chat service', function () {
    $user = createAccountUser();
    $other = createAccountUser();
    createAccountNotification($user);
    createAccountNotification($user);
    createAccountNotification($user, now()->toDateTimeString());

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

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $other->getKey(),
        'content' => 'Sent by user — must not count',
    ]);

    $counts = app(AccountService::class)->counts($user);

    expect($counts->unreadNotificationsCount)->toBe(2)
        ->and($counts->unreadMessagesCount)->toBe(1);
});

test('counts uses ConversationService for unread messages not ConversationMessage directly', function () {
    $user = createAccountUser();

    $chat = Mockery::mock(ConversationService::class);
    $chat->shouldReceive('countUnreadFor')
        ->once()
        ->withArgs(fn ($actor) => $actor->is($user))
        ->andReturn(7);
    app()->instance(ConversationService::class, $chat);

    $counts = app(AccountService::class)->counts($user);

    expect($counts->unreadMessagesCount)->toBe(7)
        ->and($counts->unreadNotificationsCount)->toBe(0);
});

test('listNotifications paginates latest notifications for the user', function () {
    $user = createAccountUser();
    $other = createAccountUser();
    createAccountNotification($user);
    createAccountNotification($user);
    createAccountNotification($other);

    $paginator = app(AccountService::class)->listNotifications($user, 15);

    expect($paginator->total())->toBe(2);
});

test('markAllNotificationsRead marks only unread notifications', function () {
    $user = createAccountUser();
    $unread = createAccountNotification($user);
    $alreadyRead = createAccountNotification($user, now()->toDateTimeString());

    app(AccountService::class)->markAllNotificationsRead($user);

    expect($unread->fresh()->read_at)->not->toBeNull()
        ->and($alreadyRead->fresh()->read_at)->not->toBeNull();
});

test('markNotificationAsRead marks owned unread notification', function () {
    $user = createAccountUser();
    $notification = createAccountNotification($user);

    $result = app(AccountService::class)->markNotificationAsRead($user, $notification);

    expect($result->status)->toBe(MarkNotificationResult::STATUS_MARKED)
        ->and($result->message)->toBe('Notification marked as read.')
        ->and($notification->fresh()->read_at)->not->toBeNull();
});

test('markNotificationAsRead returns already read for owned read notification', function () {
    $user = createAccountUser();
    $notification = createAccountNotification($user, now()->toDateTimeString());

    $result = app(AccountService::class)->markNotificationAsRead($user, $notification);

    expect($result->status)->toBe(MarkNotificationResult::STATUS_ALREADY_READ)
        ->and($result->message)->toBe('Notification already marked as read.');
});

test('markNotificationAsRead returns not found for another users notification', function () {
    $user = createAccountUser();
    $other = createAccountUser();
    $notification = createAccountNotification($other);

    $result = app(AccountService::class)->markNotificationAsRead($user, $notification);

    expect($result->isNotFound())->toBeTrue()
        ->and($result->statusCode)->toBe(404)
        ->and($result->message)->toBe('Notification not found or already read.')
        ->and($notification->fresh()->read_at)->toBeNull();
});

test('deleteNotification deletes owned notification', function () {
    $user = createAccountUser();
    $notification = createAccountNotification($user);

    $result = app(AccountService::class)->deleteNotification($user, $notification);

    expect($result->status)->toBe(DeleteNotificationResult::STATUS_DELETED)
        ->and($result->message)->toBe('Notification deleted successfully.')
        ->and(DatabaseNotification::query()->whereKey($notification->getKey())->exists())->toBeFalse();
});

test('deleteNotification returns not found for another users notification', function () {
    $user = createAccountUser();
    $other = createAccountUser();
    $notification = createAccountNotification($other);

    $result = app(AccountService::class)->deleteNotification($user, $notification);

    expect($result->isNotFound())->toBeTrue()
        ->and($result->statusCode)->toBe(404)
        ->and($result->message)->toBe('Notification not found')
        ->and(DatabaseNotification::query()->whereKey($notification->getKey())->exists())->toBeTrue();
});

test('deleteAllNotifications removes every notification for the user', function () {
    $user = createAccountUser();
    $other = createAccountUser();
    createAccountNotification($user);
    createAccountNotification($user);
    createAccountNotification($other);

    app(AccountService::class)->deleteAllNotifications($user);

    expect($user->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(1);
});

test('updateSettings updates language', function () {
    $user = createAccountUser(['language' => 'en']);

    $updated = app(AccountService::class)->updateSettings(
        $user,
        new UpdateAccountSettingsDTO(language: 'ar'),
    );

    expect($updated->language)->toBe('ar')
        ->and($user->fresh()->language)->toBe('ar');
});

test('deleteAccount marks status deleted and revokes tokens', function () {
    $user = createAccountUser();
    $user->createToken('login');
    $user->createToken('user-app');

    expect($user->tokens()->count())->toBe(2);

    app(AccountService::class)->deleteAccount($user);

    expect($user->fresh()->status)->toBe(UserStatusEnum::Deleted)
        ->and($user->tokens()->count())->toBe(0);
});

test('deleteAccount rolls back status when token revocation fails', function () {
    $user = createAccountUser();
    $user->createToken('login');

    $repository = Mockery::mock(AccountRepository::class)->makePartial();
    $repository->shouldReceive('revokeTokens')
        ->once()
        ->andThrow(new RuntimeException('token revoke failed'));
    app()->instance(AccountRepositoryInterface::class, $repository);

    expect(fn () => app(AccountService::class)->deleteAccount($user))
        ->toThrow(RuntimeException::class, 'token revoke failed');

    expect($user->fresh()->status)->toBe(UserStatusEnum::Active)
        ->and($user->tokens()->count())->toBe(1);
});
