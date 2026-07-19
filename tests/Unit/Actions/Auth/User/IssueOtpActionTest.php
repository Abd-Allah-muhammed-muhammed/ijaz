<?php

use App\Actions\Auth\User\IssueOtpAction;
use App\Exceptions\Auth\OtpCooldownException;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

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
    $action->handle($user, 'email');

    expect(fn () => $action->handle($user, 'phone'))
        ->toThrow(OtpCooldownException::class);

    expect($user->verificationCodes()->where('type', 'email')->exists())->toBeTrue()
        ->and($user->verificationCodes()->where('type', 'phone')->exists())->toBeFalse();

    RateLimiter::clear('otp-send:'.$phone);
});

test('IssueOtpAction now dispatches SMS, matching SendLoginOtpAction behavior', function () {
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

    app(IssueOtpAction::class)->handle($user, 'email');

    expect($user->verificationCodes()->where('type', 'email')->where('token', '4829')->exists())->toBeTrue();

    RateLimiter::clear('otp-send:'.$phone);
});
