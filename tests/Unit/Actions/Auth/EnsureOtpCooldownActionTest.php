<?php

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Exceptions\Auth\OtpCooldownException;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['otp.send_cooldown_seconds' => 60]);
    RateLimiter::clear('otp-send:966512345678');
    RateLimiter::clear('otp-send:966512345679');
});

afterEach(function () {
    Carbon::setTestNow();
    RateLimiter::clear('otp-send:966512345678');
    RateLimiter::clear('otp-send:966512345679');
});

test('ensure allows first request for a phone number', function () {
    app(EnsureOtpCooldownAction::class)->ensure('966512345678');

    expect(RateLimiter::attempts('otp-send:966512345678'))->toBe(0);
});

test('ensure throws OtpCooldownException on second request within cooldown window', function () {
    $action = app(EnsureOtpCooldownAction::class);
    $action->recordSent('966512345678');

    expect(fn () => $action->ensure('966512345678'))
        ->toThrow(OtpCooldownException::class);
});

test('ensure allows request again after cooldown window expires', function () {
    Carbon::setTestNow('2026-07-19 12:00:00');

    $action = app(EnsureOtpCooldownAction::class);
    $action->recordSent('966512345678');

    Carbon::setTestNow(now()->addSeconds(61));

    $action->ensure('966512345678');

    expect(RateLimiter::tooManyAttempts('otp-send:966512345678', 1))->toBeFalse();
});

test('cooldown is keyed by phone only, not by type or user', function () {
    $action = app(EnsureOtpCooldownAction::class);
    $action->recordSent('966512345678');

    expect(fn () => $action->ensure('966512345678'))
        ->toThrow(OtpCooldownException::class);

    $action->ensure('966512345679');
});

test('recordSent starts the cooldown window', function () {
    app(EnsureOtpCooldownAction::class)->recordSent('966512345678');

    expect(RateLimiter::tooManyAttempts('otp-send:966512345678', 1))->toBeTrue()
        ->and(RateLimiter::availableIn('otp-send:966512345678'))->toBeGreaterThan(0);
});
