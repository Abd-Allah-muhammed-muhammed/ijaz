<?php

use App\Actions\Auth\User\SendLoginOtpAction;
use App\Exceptions\Auth\OtpCooldownException;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

test('generates otp, stores it, dispatches sms, and logs result', function () {
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

    app(SendLoginOtpAction::class)->handle($user);

    expect($user->verificationCodes()->where('type', 'login')->exists())->toBeTrue();
});

test('SendLoginOtpAction does not log the raw otp code', function () {
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
            expect($message)->toBe('Login OTP sent for user '.$user->id)
                ->and($message)->not->toContain($otp)
                ->and($context)->toBe([
                    'status' => 'success',
                    'driver' => 'authentica',
                    'message' => 'ok',
                ])
                ->and(json_encode($context))->not->toContain($otp);

            return true;
        });

    app(SendLoginOtpAction::class)->handle($user);
});

test('SendLoginOtpAction throws cooldown exception on rapid repeat calls', function () {
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

    $action = app(SendLoginOtpAction::class);
    $action->handle($user);

    expect(fn () => $action->handle($user))
        ->toThrow(OtpCooldownException::class);

    RateLimiter::clear('otp-send:'.$phone);
});

test('SendLoginOtpAction does not record cooldown when gateway rejects the message', function () {
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

    $action = app(SendLoginOtpAction::class);
    $action->handle($user);
    $action->handle($user);

    expect(RateLimiter::tooManyAttempts('otp-send:'.$phone, 1))->toBeFalse();
});
