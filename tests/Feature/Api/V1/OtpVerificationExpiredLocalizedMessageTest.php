<?php

use App\Enums\Users\UserStatusEnum;
use App\Models\OtpSession;
use App\Models\User;
use App\Support\Phone;

beforeEach(function () {
    config(['sms.default' => 'testing']);
});

/**
 * @return array{locale: string, message: string}
 */
dataset('otp_verification_expired_locales', [
    'en' => ['en', 'verification expired'],
    'ar' => ['ar', 'انتهت صلاحية التحقق'],
    'hi' => ['hi', 'सत्यापन की समय सीमा समाप्त हो गई'],
    'ur' => ['ur', 'تصدیق کی مدت ختم ہو گئی'],
]);

test('expired otp verify returns localized verification expired message per Accept-Language', function (string $locale, string $message): void {
    User::factory()->create([
        'phone' => Phone::make('512345678')->toString(),
        'status' => UserStatusEnum::Active,
    ]);

    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])
        ->json('data');

    OtpSession::query()->whereKey($login['verification_id'])->update([
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '1234',
    ], [
        'Accept-Language' => $locale,
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.code', 'verification_expired')
        ->assertJsonPath('message', $message);

    expect($response->json('message'))->toBe(__('verification expired', [], $locale));

    if ($locale !== 'en') {
        expect($response->json('message'))
            ->not->toBe(__('verification expired', [], 'en'));
    }
})->with('otp_verification_expired_locales');

test('wrong otp verify returns localized wrong OTP message per Accept-Language', function (): void {
    User::factory()->create([
        'phone' => Phone::make('512345679')->toString(),
        'status' => UserStatusEnum::Active,
    ]);

    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345679'])
        ->json('data');

    $response = $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '0000',
    ], [
        'Accept-Language' => 'ar',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('data.code', 'invalid_code')
        ->assertJsonPath('message', __('wrong OTP', [], 'ar'));

    expect($response->json('message'))
        ->toBe('رمز التحقق غير صحيح')
        ->not->toBe('wrong OTP');
});
