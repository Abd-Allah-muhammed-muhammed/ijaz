<?php

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Actions\Auth\User\IssueOtpAction;
use App\Actions\Auth\User\VerifyOtpAction;
use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Otp;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

beforeEach(function () {
    config([
        'sms.test_number' => '966500000000',
        'sms.verification_code' => '1111',
        'sms.verification_code_all_numbers' => false,
    ]);
});

test('SMS_TEST_NUMBER receives fixed code without a real gateway call being made', function () {
    $phone = '966500000000';
    $user = User::factory()->create([
        'phone' => $phone,
        'status' => UserStatusEnum::Active,
    ]);
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldNotReceive('sendOtp');
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(IssueOtpAction::class)->handle($user, OtpPurposeEnum::Login);

    expect(
        Otp::query()
            ->where('subject_id', $user->id)
            ->where('purpose', OtpPurposeEnum::Login)
            ->where('token', '1111')
            ->exists()
    )->toBeTrue();
});

test('SMS_TEST_NUMBER still verifies successfully with the fixed code', function () {
    $phone = '966500000000';
    $user = User::factory()->create([
        'phone' => $phone,
        'status' => UserStatusEnum::Active,
    ]);
    RateLimiter::clear('otp-send:'.$phone);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldNotReceive('sendOtp');
    app()->instance(SmsService::class, $sms);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    $session = app(OtpSessionRepositoryInterface::class)->createForUser(
        $user,
        OtpPurposeEnum::Login,
        (int) config('otp.session_ttl_minutes', 15),
    );

    app(IssueOtpAction::class)->handle($user, OtpPurposeEnum::Login);

    $result = app(VerifyOtpAction::class)->handleSession((string) $session->id, '1111');

    expect($result->success)->toBeTrue()
        ->and($result->accessToken)->not->toBeEmpty();
});

test('a real (non-test) phone number still gets a real SMS send attempt', function () {
    $phone = Phone::make('512345678')->toString();
    expect($phone)->not->toBe(config('sms.test_number'));

    $user = User::factory()->create(['phone' => $phone]);
    $result = new SmsResult(status: 'success', driver: 'orbit');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->withArgs(fn (string $code, string $number) => $number === $phone && $code !== '')
        ->andReturn($result);
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldReceive('recordSent')->once()->with($phone);
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    $actual = app(SendOtpSmsAction::class)->handle('4829', $phone, 'login', $user);

    expect($actual)->toBe($result);
});
