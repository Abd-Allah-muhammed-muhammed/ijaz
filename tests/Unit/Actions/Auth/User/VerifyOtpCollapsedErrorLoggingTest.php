<?php

use App\Actions\Auth\User\VerifyOtpAction;
use App\Contracts\Auth\OtpRepositoryInterface;
use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('verify logs distinct reason when otp row is missing', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context) use ($session, $user) {
            expect($message)->toBe('OTP session verify failed')
                ->and($context['reason'])->toBe('missing_otp')
                ->and($context['verification_id'])->toBe((string) $session->id)
                ->and($context['user_id'])->toBe($user->id)
                ->and($context['purpose'])->toBe(OtpPurposeEnum::Login->value);

            return true;
        });

    $result = app(VerifyOtpAction::class)->handleSession((string) $session->id, '1111');

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('invalid_code')
        ->and($result->message)->toBe(trans('wrong OTP'));
});

test('verify logs distinct reason when otp row is expired', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    app(OtpRepositoryInterface::class)->issueForSubject(
        $user,
        OtpPurposeEnum::Login,
        '1111',
    );

    // Force expiry after issue (issue sets a future expires_at).
    $otp = app(OtpRepositoryInterface::class)->findForSubject($user, OtpPurposeEnum::Login);
    $otp->forceFill(['expires_at' => now()->subMinute()])->save();

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => $message === 'OTP session verify failed'
            && $context['reason'] === 'expired_otp');

    $result = app(VerifyOtpAction::class)->handleSession((string) $session->id, '1111');

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('invalid_code');
});

test('verify logs distinct reason when code is wrong', function () {
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        15,
    );

    app(OtpRepositoryInterface::class)->issueForSubject(
        $user,
        OtpPurposeEnum::Login,
        '1111',
    );

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => $message === 'OTP session verify failed'
            && $context['reason'] === 'invalid_code');

    $result = app(VerifyOtpAction::class)->handleSession((string) $session->id, '9999');

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('invalid_code');
});
