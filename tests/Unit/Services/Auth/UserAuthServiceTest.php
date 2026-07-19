<?php

use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\Auth\UserAuthService;
use App\Services\Sms\Phone;
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

test('login returns token for existing active user', function () {
    $user = createUserAuthUser();

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeTrue()
        ->and($result->token)->not->toBe('')
        ->and($user->verificationCodes()->where('type', 'login')->exists())->toBeTrue();
});

test('login fails with user not found message when phone does not match', function () {
    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(trans('user not found'))
        ->and($result->statusCode)->toBe(400);
});

test('login fails with appropriate message for deleted user', function () {
    createUserAuthUser(['status' => UserStatusEnum::Deleted]);

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(trans('this account is deleted'))
        ->and($result->statusCode)->toBe(400);
});

test('login fails with appropriate message for blocked user', function () {
    createUserAuthUser([
        'status' => UserStatusEnum::Blocked,
        'blocked_until' => now()->addDay(),
    ]);

    $result = app(UserAuthService::class)->login('512345678');

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(trans('this account is blocked'))
        ->and($result->statusCode)->toBe(400);
});

test('register creates user, sends otp, returns token and user', function () {
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

    expect($result->token)->not->toBe('')
        ->and(User::where('email', 'jane.register@example.com')->exists())->toBeTrue()
        ->and($result->user->verificationCodes()->where('type', 'login')->exists())->toBeTrue();
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
        ->and(VerificationCode::count())->toBe(0);
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

    expect($user->verificationCodes()->where('type', 'login')->exists())->toBeTrue();
});

test('verifyOtp for login type elevates to full-abilities token', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', 'login');

    $result = app(UserAuthService::class)->verifyOtp('login', '1234');

    expect($result)->not->toBeNull()
        ->and($result->success)->toBeTrue()
        ->and($result->token)->not->toBe('')
        ->and($user->tokens()->where('name', 'user-app')->exists())->toBeTrue()
        ->and($user->verificationCodes()->where('type', 'login')->exists())->toBeFalse();
});

test('verifyOtp for email type preserves current behavior (bool passed to getUserResource throws)', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', 'email');

    expect(fn () => app(UserAuthService::class)->verifyOtp('email', '1234'))
        ->toThrow(TypeError::class);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('verifyOtp for phone type is a no-op matching current stub behavior', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', 'phone');

    $result = app(UserAuthService::class)->verifyOtp('phone', '1234');

    expect($result)->not->toBeNull()
        ->and($result->success)->toBeFalse()
        ->and($result->token)->toBe('');
});

test('verifyOtp with wrong code returns failure result', function () {
    $user = createUserAuthUser();
    $this->actingAs($user, 'user-api');
    $user->updateOrCreateVerificationCode('1234', 'login');

    $result = app(UserAuthService::class)->verifyOtp('login', '9999');

    expect($result)->toBeNull();
});

test('logout deletes all user tokens', function () {
    $user = createUserAuthUser();
    $user->createToken('user-app', ['*']);
    $this->actingAs($user, 'user-api');

    app(UserAuthService::class)->logout();

    expect($user->tokens()->count())->toBe(0);
});
