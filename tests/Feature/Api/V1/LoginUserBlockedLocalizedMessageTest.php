<?php

use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use App\Support\Phone;

beforeEach(function () {
    config(['sms.default' => 'testing']);
});

test('blocked user login returns localized auth.blocked message per Accept-Language header', function () {
    User::factory()->create([
        'phone' => Phone::make('512345678')->toString(),
        'status' => UserStatusEnum::Blocked,
        'blocked_at' => now(),
        'blocked_until' => now()->addDays(7),
    ]);

    $response = $this->postJson('/api/v1/user/auth/login', [
        'phone' => '512345678',
    ], [
        'Accept-Language' => 'ar',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('message', __('auth.blocked', [], 'ar'))
        ->assertJsonPath('success', false);

    expect($response->json('message'))
        ->toBe('حسابك محظور')
        ->not->toBe('this account is blocked')
        ->not->toBe(__('auth.blocked', [], 'en'));
});

test('banned (permanently blocked) user login returns localized auth.banned message', function () {
    User::factory()->create([
        'phone' => Phone::make('512345679')->toString(),
        'status' => UserStatusEnum::Blocked,
        'blocked_at' => now(),
        'blocked_until' => null,
    ]);

    $response = $this->postJson('/api/v1/user/auth/login', [
        'phone' => '512345679',
    ], [
        'Accept-Language' => 'ar',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('message', __('auth.banned', [], 'ar'))
        ->assertJsonPath('success', false);

    expect($response->json('message'))
        ->toBe('حسابك محظور بشكل دائم')
        ->not->toBe('this account is banned')
        ->not->toBe(__('auth.banned', [], 'en'));
});
