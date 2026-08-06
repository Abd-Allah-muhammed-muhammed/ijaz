<?php

use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('logout all devices endpoint requires authentication', function () {
    $this->postJson('/api/v1/user/auth/logout-all')
        ->assertUnauthorized();
});

test('logout all devices clears tokens and device tokens for the authenticated user', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $user->registerDeviceToken('endpoint-device');
    $user->createToken('user-app', ['*']);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $this->postJson('/api/v1/user/auth/logout-all')
        ->assertSuccessful()
        ->assertJsonPath('message', __('auth.logged_out_all_devices'));

    expect($user->fresh()->tokens()->count())->toBe(0)
        ->and($user->deviceTokens()->count())->toBe(0);
});
