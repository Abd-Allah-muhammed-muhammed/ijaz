<?php

use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Otp;
use App\Models\OtpSession;
use App\Models\User;
use App\Services\Auth\UserAuthService;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

beforeEach(function () {
    config(['sms.default' => 'testing']);
});

function createUserAuthUser(array $attributes = []): User
{
    return User::factory()->create([
        'phone' => Phone::make('512345678')->toString(),
        'status' => UserStatusEnum::Active,
        ...$attributes,
    ]);
}

test('login returns verification challenge for existing active user', function () {
    $user = createUserAuthUser();

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeTrue()
        ->and($result->verificationId)->not->toBe('')
        ->and($result->expiresIn)->toBeGreaterThan(0)
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->otps()->where('purpose', OtpPurposeEnum::Login)->exists())->toBeTrue()
        ->and(OtpSession::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('login fails with user not found message when phone does not match', function () {
    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(__('auth.user_not_found'))
        ->and($result->statusCode)->toBe(400);
});

test('login fails with appropriate message for deleted user', function () {
    createUserAuthUser(['status' => UserStatusEnum::Deleted]);

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(__('auth.deleted'))
        ->and($result->statusCode)->toBe(400);
});

test('login fails with appropriate message for blocked user', function () {
    createUserAuthUser([
        'status' => UserStatusEnum::Blocked,
        'blocked_until' => now()->addDay(),
    ]);

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(__('auth.blocked'))
        ->and($result->statusCode)->toBe(400);
});

test('register creates user, sends otp, returns verification challenge without token', function () {
    Storage::fake('local');

    $result = app(UserAuthService::class)->register([
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'jane.register@example.com',
        'phone' => '512345678',
        'nationality_id' => null,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '10',
        'longitude' => '20',
        'password' => null,
    ]);

    $user = User::where('email', 'jane.register@example.com')->first();

    expect($result->verificationId)->not->toBe('')
        ->and($user)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->otps()->where('purpose', OtpPurposeEnum::Register)->exists())->toBeTrue();
});

test('register rolls back transaction on failure', function () {
    Storage::fake('local');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->andThrow(new RuntimeException('boom'));
    app()->instance(SmsService::class, $sms);

    $register = fn () => app(UserAuthService::class)->register([
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'rollback@example.com',
        'phone' => '512345678',
        'nationality_id' => null,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '10',
        'longitude' => '20',
        'password' => null,
    ]);

    expect($register)->toThrow(RuntimeException::class);

    expect(User::where('email', 'rollback@example.com')->exists())->toBeFalse()
        ->and(Otp::count())->toBe(0)
        ->and(OtpSession::count())->toBe(0);
});

test('sendOtp stores code and dispatches sms', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->withArgs(fn (string $code, string $number) => $code !== '' && $number === $user->phone)
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    app(UserAuthService::class)->sendOtp('login');

    expect($user->otps()->where('purpose', OtpPurposeEnum::Login)->exists())->toBeTrue();
});

test('verifyOtpSession issues full-access token and deletes the session', function () {
    $user = createUserAuthUser();
    $challenge = app(UserAuthService::class)->login('512345678');
    $otp = $user->otps()->where('purpose', OtpPurposeEnum::Login)->value('token');

    $result = app(UserAuthService::class)->verifyOtpSession($challenge->verificationId, $otp);

    expect($result->success)->toBeTrue()
        ->and($result->accessToken)->not->toBe('')
        ->and($user->tokens()->where('name', 'user-app')->exists())->toBeTrue()
        ->and($user->tokens()->first()->abilities)->toBe(['*'])
        ->and($user->otps()->where('purpose', OtpPurposeEnum::Login)->exists())->toBeFalse()
        ->and(OtpSession::query()->whereKey($challenge->verificationId)->exists())->toBeFalse();
});

test('verifyOtp for email type preserves current behavior (bool passed to getUserResource throws)', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', OtpPurposeEnum::Email);

    expect(fn () => app(UserAuthService::class)->verifyOtp('email', '1234'))
        ->toThrow(TypeError::class);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('verifyOtp for phone type still returns success false while persisting phone_verified_at', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', OtpPurposeEnum::Phone);

    $result = app(UserAuthService::class)->verifyOtp('phone', '1234');

    expect($result)->not->toBeNull()
        ->and($result->success)->toBeFalse()
        ->and($result->token)->toBe('')
        ->and($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('markPhoneAsVerified persists phone_verified_at timestamp', function () {
    $user = createUserAuthUser(['phone_verified_at' => null]);

    expect($user->markPhoneAsVerified())->toBeTrue()
        ->and($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('verifyOtpSession with wrong code returns invalid_code', function () {
    $user = createUserAuthUser();
    $challenge = app(UserAuthService::class)->login('512345678');

    $result = app(UserAuthService::class)->verifyOtpSession($challenge->verificationId, '9999');

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('invalid_code')
        ->and($result->attemptsRemaining)->toBe((int) config('otp.max_verification_attempts') - 1);
});

test('logout deletes all user tokens', function () {
    $user = createUserAuthUser();
    $user->createToken('user-app', ['*']);
    $this->actingAs($user, 'user-api');

    app(UserAuthService::class)->logout();

    expect($user->tokens()->count())->toBe(0);
});
