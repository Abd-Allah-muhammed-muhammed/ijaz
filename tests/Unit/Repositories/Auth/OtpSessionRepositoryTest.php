<?php

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Models\OtpSession;
use App\Models\User;

test('createForUser persists a uuid session with configured max attempts and ttl', function () {
    $user = User::factory()->create();

    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    expect($session->exists)->toBeTrue()
        ->and($session->id)->toBeString()
        ->and($session->user_id)->toBe($user->id)
        ->and($session->purpose)->toBe(OtpPurposeEnum::Login)
        ->and($session->attempts_count)->toBe(0)
        ->and($session->max_attempts)->toBe((int) config('otp.max_verification_attempts'))
        ->and($session->expires_at->greaterThan(now()->addMinutes(14)))->toBeTrue()
        ->and($session->expires_at->lessThanOrEqualTo(now()->addMinutes(15)->addSecond()))->toBeTrue()
        ->and($session->isExpired())->toBeFalse()
        ->and($session->hasExceededAttempts())->toBeFalse();
});

test('findById returns the matching session or null', function () {
    $user = User::factory()->create();
    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    $repo = app(OtpSessionRepositoryInterface::class);

    expect($repo->findById($session->id)?->is($session))->toBeTrue()
        ->and($repo->findById((string) str()->uuid()))->toBeNull();
});

test('incrementAttempts bumps attempts_count and hasExceededAttempts when max is reached', function () {
    config(['otp.max_verification_attempts' => 2]);

    $user = User::factory()->create();
    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    $repo = app(OtpSessionRepositoryInterface::class);

    $session = $repo->incrementAttempts($session);
    expect($session->attempts_count)->toBe(1)
        ->and($session->hasExceededAttempts())->toBeFalse();

    $session = $repo->incrementAttempts($session);
    expect($session->attempts_count)->toBe(2)
        ->and($session->hasExceededAttempts())->toBeTrue();
});

test('isExpired is true when expires_at is in the past', function () {
    $user = User::factory()->create();
    $session = OtpSession::query()->create([
        'user_id' => $user->id,
        'purpose' => OtpPurposeEnum::Login,
        'attempts_count' => 0,
        'max_attempts' => 5,
        'expires_at' => now()->subMinute(),
    ]);

    expect($session->isExpired())->toBeTrue();
});

test('deleteForUser removes sessions for the given user and purpose only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $repo = app(OtpSessionRepositoryInterface::class);

    $login = $repo->createForUser($user, OtpPurposeEnum::Login, 15);
    $phone = $repo->createForUser($user, OtpPurposeEnum::Phone, 15);
    $otherLogin = $repo->createForUser($other, OtpPurposeEnum::Login, 15);

    $repo->deleteForUser($user, OtpPurposeEnum::Login);

    expect($repo->findById($login->id))->toBeNull()
        ->and($repo->findById($phone->id))->not->toBeNull()
        ->and($repo->findById($otherLogin->id))->not->toBeNull();
});

test('deleteExpired removes only past-due sessions and returns the count', function () {
    $user = User::factory()->create();
    $repo = app(OtpSessionRepositoryInterface::class);

    $expired = OtpSession::query()->create([
        'user_id' => $user->id,
        'purpose' => OtpPurposeEnum::Login,
        'attempts_count' => 0,
        'max_attempts' => 5,
        'expires_at' => now()->subMinute(),
    ]);
    $active = $repo->createForUser($user, OtpPurposeEnum::Phone, 15);

    $deleted = $repo->deleteExpired();

    expect($deleted)->toBe(1)
        ->and($repo->findById($expired->id))->toBeNull()
        ->and($repo->findById($active->id))->not->toBeNull();
});

test('auth:prune-expired-otp-sessions command deletes expired rows via the repository', function () {
    $user = User::factory()->create();

    OtpSession::query()->create([
        'user_id' => $user->id,
        'purpose' => OtpPurposeEnum::Login,
        'attempts_count' => 0,
        'max_attempts' => 5,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('auth:prune-expired-otp-sessions')
        ->expectsOutput('Pruned 1 expired OTP session(s).')
        ->assertSuccessful();

    expect(OtpSession::query()->count())->toBe(0);
});
