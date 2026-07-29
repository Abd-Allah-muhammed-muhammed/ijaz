<?php

use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Otp;
use App\Models\OtpSession;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Geo\Models\Nationality;

beforeEach(function () {
    config(['sms.default' => 'testing']);
});

function otpSessionNationality(): Nationality
{
    return Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
        'translations' => geoNameTranslations('Saudi'),
    ]);
}

function otpSessionActiveUser(array $attributes = []): User
{
    return User::factory()->create([
        'phone' => Phone::make('512345678')->toString(),
        'status' => UserStatusEnum::Active,
        ...$attributes,
    ]);
}

function challengeKeys(): array
{
    return ['verification_id', 'expires_in', 'resend_available_at'];
}

test('register creates otp session and returns verification_id, not a sanctum token', function () {
    Storage::fake('local');
    $nationality = otpSessionNationality();

    $response = $this->postJson('/api/v1/user/auth/register', [
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'jane.otpsession@example.com',
        'phone' => '512345678',
        'nationality_id' => $nationality->id,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '24.7',
        'longitude' => '46.6',
        'password' => null,
    ])->assertSuccessful();

    $json = $response->json();

    expect(array_keys($json['data']))->toBe(challengeKeys())
        ->and($json['data']['verification_id'])->toBeString()->not->toBeEmpty()
        ->and($json['data']['expires_in'])->toBeInt()->toBeGreaterThan(0)
        ->and($json['data']['resend_available_at'])->toBeString()->not->toBeEmpty()
        ->and($json['token'])->toBe('');

    $user = User::where('email', 'jane.otpsession@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0)
        ->and(OtpSession::query()->where('user_id', $user->id)->where('purpose', OtpPurposeEnum::Register)->exists())->toBeTrue()
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});

test('login creates otp session and returns verification_id, not a sanctum token', function () {
    $user = otpSessionActiveUser();

    $response = $this->postJson('/api/v1/user/auth/login', [
        'phone' => '512345678',
    ])->assertSuccessful();

    $json = $response->json();

    expect(array_keys($json['data']))->toBe(challengeKeys())
        ->and($json['data']['verification_id'])->toBeString()->not->toBeEmpty()
        ->and($json['token'])->toBe('')
        ->and($user->fresh()->tokens()->count())->toBe(0)
        ->and(OtpSession::query()->where('user_id', $user->id)->where('purpose', OtpPurposeEnum::Login)->exists())->toBeTrue();
});

test('register and login response shapes are identical', function () {
    Storage::fake('local');
    $nationality = otpSessionNationality();

    $register = $this->postJson('/api/v1/user/auth/register', [
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'shape@example.com',
        'phone' => '512345679',
        'nationality_id' => $nationality->id,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '24.7',
        'longitude' => '46.6',
        'password' => null,
    ])->assertSuccessful()->json();

    otpSessionActiveUser(['phone' => Phone::make('512345680')->toString()]);

    $login = $this->postJson('/api/v1/user/auth/login', [
        'phone' => '512345680',
    ])->assertSuccessful()->json();

    expect(array_keys($register))->toBe(array_keys($login))
        ->and(array_keys($register['data']))->toBe(array_keys($login['data']))
        ->and(array_keys($register['data']))->toBe(challengeKeys())
        ->and($register['token'])->toBe('')
        ->and($login['token'])->toBe('');
});

test('verify with correct code deletes otp session and returns access_token + user', function () {
    $user = otpSessionActiveUser(['nationality_id' => otpSessionNationality()->id]);

    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])
        ->assertSuccessful()
        ->json('data');

    $otp = Otp::query()
        ->where('subject_id', $user->id)
        ->where('purpose', OtpPurposeEnum::Login)
        ->value('token');

    $response = $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => $otp,
    ])->assertSuccessful();

    $json = $response->json();

    expect($json['success'])->toBeTrue()
        ->and($json['data']['access_token'])->toBeString()->not->toBeEmpty()
        ->and($json['data']['token_type'])->toBe('Bearer')
        ->and($json['data']['user']['id'])->toBe($user->id)
        ->and(OtpSession::query()->whereKey($login['verification_id'])->exists())->toBeFalse()
        ->and($user->fresh()->tokens()->count())->toBe(1);
});

test('the issued access_token has full-access * ability', function () {
    $user = otpSessionActiveUser();

    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])
        ->json('data');

    $otp = Otp::query()
        ->where('subject_id', $user->id)
        ->where('purpose', OtpPurposeEnum::Login)
        ->value('token');

    $accessToken = $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => $otp,
    ])->json('data.access_token');

    $token = $user->fresh()->tokens()->first();

    expect($token->name)->toBe('user-app')
        ->and($token->abilities)->toBe(['*']);

    $this->withToken($accessToken)
        ->getJson('/api/v1/user/auth/me')
        ->assertSuccessful();
});

test('verify with wrong code increments attempts and returns invalid_code with attempts_remaining', function () {
    $user = otpSessionActiveUser();

    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])
        ->json('data');

    $response = $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '000000',
    ])->assertUnprocessable();

    $json = $response->json();

    expect($json['success'])->toBeFalse()
        ->and($json['data']['code'])->toBe('invalid_code')
        ->and($json['data']['attempts_remaining'])->toBe((int) config('otp.max_verification_attempts') - 1);

    expect(OtpSession::query()->whereKey($login['verification_id'])->value('attempts_count'))->toBe(1);
});

test('verify after max attempts returns max_attempts_exceeded', function () {
    config(['otp.max_verification_attempts' => 2]);

    $user = otpSessionActiveUser();
    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])->json('data');

    $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '000000',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '000000',
    ])->assertUnprocessable()
        ->assertJsonPath('data.code', 'max_attempts_exceeded');

    $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '000000',
    ])->assertUnprocessable()
        ->assertJsonPath('data.code', 'max_attempts_exceeded');
});

test('verify after session expiry returns verification_expired', function () {
    $user = otpSessionActiveUser();
    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])->json('data');

    OtpSession::query()->whereKey($login['verification_id'])->update([
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/otp/verify', [
        'verification_id' => $login['verification_id'],
        'code' => '1234',
    ])->assertUnprocessable()
        ->assertJsonPath('data.code', 'verification_expired');
});

test('resend reuses the same verification_id', function () {
    $user = otpSessionActiveUser();
    $login = $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])->json('data');

    // Clear cooldown so resend is allowed immediately in tests.
    RateLimiter::clear('otp-send:'.$user->phone);

    $resend = $this->postJson('/api/v1/otp/resend', [
        'verification_id' => $login['verification_id'],
    ])->assertSuccessful()->json('data');

    expect($resend['verification_id'])->toBe($login['verification_id'])
        ->and(array_keys($resend))->toBe(challengeKeys())
        ->and(OtpSession::query()->where('user_id', $user->id)->count())->toBe(1);
});
