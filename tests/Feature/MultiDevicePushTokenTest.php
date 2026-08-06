<?php

use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Actions\User\UpdateUserStatusAction;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Enums\Users\UserStatusEnum;
use App\Models\DeviceToken;
use App\Models\User;
use App\NotificationChannels\FirebaseChannel;
use App\Notifications\DomainNotification;
use App\Services\Firebase\FirebaseService;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

test('registering a device token upserts and reassigns ownership if another account previously owned that token', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();
    $register = app(RegisterDeviceTokenAction::class);

    $register->handle($ownerA, 'shared-fcm-token', 'android', 'Family Phone');

    expect(DeviceToken::query()->where('token', 'shared-fcm-token')->count())->toBe(1)
        ->and($ownerA->deviceTokens()->where('token', 'shared-fcm-token')->exists())->toBeTrue();

    $register->handle($ownerB, 'shared-fcm-token', 'android', 'Family Phone');

    expect(DeviceToken::query()->where('token', 'shared-fcm-token')->count())->toBe(1)
        ->and($ownerA->fresh()->deviceTokens()->where('token', 'shared-fcm-token')->exists())->toBeFalse()
        ->and($ownerB->fresh()->deviceTokens()->where('token', 'shared-fcm-token')->exists())->toBeTrue();
});

test('a user can be logged in on two devices simultaneously without either session being revoked', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);

    $tokenA = $user->createToken('user-app', ['*'])->plainTextToken;
    $tokenB = $user->createToken('user-app', ['*'])->plainTextToken;

    expect($user->tokens()->count())->toBe(2);

    $this->withToken($tokenA)
        ->getJson('/api/v1/user/auth/me')
        ->assertSuccessful();

    $this->app['auth']->forgetGuards();

    $this->withToken($tokenB)
        ->getJson('/api/v1/user/auth/me')
        ->assertSuccessful();

    expect($user->fresh()->tokens()->count())->toBe(2);
});

test('firebase channel sends to every registered device token for a notifiable', function () {
    $user = User::factory()->create(['language' => 'en']);
    $register = app(RegisterDeviceTokenAction::class);
    $register->handle($user, 'device-one');
    $register->handle($user, 'device-two');

    $sentTokens = [];

    $firebase = $this->mock(FirebaseService::class, function (MockInterface $mock) use (&$sentTokens) {
        $mock->shouldReceive('send')
            ->twice()
            ->andReturnUsing(function ($outgoing) use (&$sentTokens) {
                $sentTokens[] = $outgoing->targetValue;

                return ['name' => 'ok'];
            });
    });

    $notification = new class extends DomainNotification
    {
        protected function titleKey(): string
        {
            return 'order_offer_created';
        }

        protected function bodyKey(): string
        {
            return 'order_offer_has_been_created';
        }

        protected function payload(): array
        {
            return [];
        }

        protected function firebaseData(object $notifiable): array
        {
            return [];
        }

        protected function sendsFirebase(object $notifiable): bool
        {
            return true;
        }

        public function broadcastType(): string
        {
            return 'test';
        }
    };

    expect((new FirebaseChannel($firebase))->send($user, $notification))->toBeTrue()
        ->and($sentTokens)->toEqualCanonicalizing(['device-one', 'device-two']);
});

test('single-device logout revokes only the current session, other devices remain logged in', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $register = app(RegisterDeviceTokenAction::class);
    $register->handle($user, 'device-a');
    $register->handle($user, 'device-b');

    $accessA = $user->createToken('user-app', ['*']);
    $accessB = $user->createToken('user-app', ['*']);
    $tokenA = $accessA->plainTextToken;
    $tokenB = $accessB->plainTextToken;
    $tokenAId = $accessA->accessToken->id;
    $tokenBId = $accessB->accessToken->id;

    $this->withToken($tokenA)
        ->postJson('/api/v1/user/auth/logout', ['player_id' => 'device-a'])
        ->assertSuccessful();

    expect($user->fresh()->tokens()->whereKey($tokenAId)->exists())->toBeFalse()
        ->and($user->fresh()->tokens()->whereKey($tokenBId)->exists())->toBeTrue()
        ->and($user->deviceTokens()->where('token', 'device-a')->exists())->toBeFalse()
        ->and($user->deviceTokens()->where('token', 'device-b')->exists())->toBeTrue();

    // Full plain-text token (id|secret). Reset auth guards between requests so a
    // revoked token cannot ride the previous request's authenticated user.
    $this->flushHeaders();
    $this->app['auth']->forgetGuards();
    $this->withToken($tokenB)
        ->getJson('/api/v1/user/auth/me')
        ->assertSuccessful();

    $this->flushHeaders();
    $this->app['auth']->forgetGuards();
    $this->withToken($tokenA)
        ->getJson('/api/v1/user/auth/me')
        ->assertUnauthorized();
});

test('logout-all-devices revokes every sanctum token and clears every device token', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $register = app(RegisterDeviceTokenAction::class);
    $register->handle($user, 'device-a');
    $register->handle($user, 'device-b');
    $user->createToken('user-app', ['*']);
    $user->createToken('user-app', ['*']);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $this->postJson('/api/v1/user/auth/logout-all')
        ->assertSuccessful()
        ->assertJsonPath('message', __('auth.logged_out_all_devices'));

    expect($user->fresh()->tokens()->count())->toBe(0)
        ->and($user->deviceTokens()->count())->toBe(0);
});

test('banning or deleting a user clears all their device tokens', function () {
    $banned = User::factory()->create(['status' => UserStatusEnum::Active]);
    app(RegisterDeviceTokenAction::class)->handle($banned, 'ban-token');
    $banned->createToken('user-app', ['*']);

    app(UpdateUserStatusAction::class)->handle(
        $banned,
        new UpdateUserStatusDTO(UserStatusEnum::Blocked->value, 7, 'abuse'),
    );

    expect($banned->fresh()->tokens()->count())->toBe(0)
        ->and($banned->deviceTokens()->count())->toBe(0);

    $deleted = User::factory()->create(['status' => UserStatusEnum::Active]);
    app(RegisterDeviceTokenAction::class)->handle($deleted, 'delete-token');
    $deleted->createToken('user-app', ['*']);

    app(UpdateUserStatusAction::class)->handle(
        $deleted,
        new UpdateUserStatusDTO(UserStatusEnum::Deleted->value, null, null),
    );

    expect($deleted->fresh()->tokens()->count())->toBe(0)
        ->and($deleted->deviceTokens()->count())->toBe(0);
});
