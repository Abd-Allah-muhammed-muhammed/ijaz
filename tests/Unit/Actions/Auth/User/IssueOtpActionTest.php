<?php

use App\Actions\Auth\User\IssueOtpAction;
use App\Enums\Auth\OtpPurposeEnum;
use App\Exceptions\Auth\OtpCooldownException;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

test('IssueOtpAction generates otp, stores it, dispatches sms, and logs result for login type', function () {
    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->withArgs(fn (string $code, string $number) => $number === $phone && $code !== '')
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(IssueOtpAction::class)->handle($user, OtpPurposeEnum::Login);

    expect($user->otps()->where('purpose', OtpPurposeEnum::Login)->exists())->toBeTrue();
});

test('IssueOtpAction does not log the raw otp code', function () {
    config([
        'sms.verification_code_all_numbers' => true,
        'sms.verification_code' => '4829',
    ]);

    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);
    $otp = '4829';

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->with($otp, $phone)
        ->andReturn(new SmsResult(
            status: 'success',
            driver: 'authentica',
            message: 'ok',
            data: [
                'phone' => $phone,
                'message' => ['body' => $otp, 'type' => 'otp'],
            ],
        ));
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($otp, $user) {
            expect($message)->toBe('OTP sent for user '.$user->id)
                ->and($message)->not->toContain($otp)
                ->and($context)->toBe([
                    'type' => 'login',
                    'status' => 'success',
                    'driver' => 'authentica',
                    'message' => 'ok',
                ])
                ->and(json_encode($context))->not->toContain($otp);

            return true;
        });

    app(IssueOtpAction::class)->handle($user, OtpPurposeEnum::Login);
});

test('IssueOtpAction throws cooldown exception on rapid repeat calls', function () {
    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    $action = app(IssueOtpAction::class);
    $action->handle($user, OtpPurposeEnum::Email);

    expect(fn () => $action->handle($user, OtpPurposeEnum::Phone))
        ->toThrow(OtpCooldownException::class);

    expect($user->otps()->where('purpose', OtpPurposeEnum::Email)->exists())->toBeTrue()
        ->and($user->otps()->where('purpose', OtpPurposeEnum::Phone)->exists())->toBeFalse();

    RateLimiter::clear('otp-send:'.$phone);
});

test('IssueOtpAction does not record cooldown when gateway rejects the message', function () {
    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->twice()
        ->andReturn(new SmsResult(status: 'failed', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->twice()->andReturnSelf();
    Log::shouldReceive('info')->twice();

    $action = app(IssueOtpAction::class);
    $action->handle($user, OtpPurposeEnum::Login);
    $action->handle($user, OtpPurposeEnum::Login);

    expect(RateLimiter::tooManyAttempts('otp-send:'.$phone, 1))->toBeFalse();
});

test('IssueOtpAction dispatches SMS for non-login types', function () {
    config([
        'sms.verification_code_all_numbers' => true,
        'sms.verification_code' => '4829',
    ]);

    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->with('4829', $phone)
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->with('OTP sent for user '.$user->id, [
            'type' => 'email',
            'status' => 'success',
            'driver' => 'testing',
            'message' => '',
        ]);

    app(IssueOtpAction::class)->handle($user, OtpPurposeEnum::Email);

    expect($user->otps()->where('purpose', OtpPurposeEnum::Email)->where('token', '4829')->exists())->toBeTrue();

    RateLimiter::clear('otp-send:'.$phone);
});
