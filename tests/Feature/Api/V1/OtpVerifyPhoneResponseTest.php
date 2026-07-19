<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('otp verify phone type still returns success false in response', function () {
    $user = User::factory()->create(['phone_verified_at' => null]);
    $user->updateOrCreateVerificationCode('1234', 'phone');
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/otp/verify', [
        'type' => 'phone',
        'otp' => '1234',
    ])->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('token', '')
        ->assertJsonPath('message', '')
        ->assertJsonPath('data', []);

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});
