<?php

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

test('handle sends otp via SmsService with correct token and phone', function () {
    $user = User::factory()->create();
    $result = new SmsResult(status: 'success', driver: 'testing');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->with('4829', '966512345678')
        ->andReturn($result);
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldReceive('recordSent')->once()->with('966512345678');
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'login');
});

test('handle records cooldown only when send is successful', function () {
    $user = User::factory()->create();

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldReceive('recordSent')->once()->with('966512345678');
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'login');
});

test('handle does not record cooldown when send fails', function () {
    $user = User::factory()->create();

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->andReturn(new SmsResult(status: 'failed', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldNotReceive('recordSent');
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'login');
});

test('handle logs safe fields only, excludes result data', function () {
    $user = User::factory()->create();
    $result = new SmsResult(
        status: 'success',
        driver: 'authentica',
        message: 'OTP accepted',
        data: [
            'message' => ['body' => '4829'],
        ],
    );

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->once()->andReturn($result);
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldReceive('recordSent')->once();
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) use ($user) {
            expect($message)->toBe('OTP sent for user '.$user->id)
                ->and($context)->toBe([
                    'type' => 'email',
                    'status' => 'success',
                    'driver' => 'authentica',
                    'message' => 'OTP accepted',
                ])
                ->and($context)->not->toHaveKey('data')
                ->and(json_encode($context))->not->toContain('4829');

            return true;
        });

    app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'email');
});

test('handle logs the otp type', function () {
    $user = User::factory()->create();

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')
        ->once()
        ->andReturn(new SmsResult(status: 'failed', driver: 'testing', message: 'Rejected'));
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldNotReceive('recordSent');
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->with('OTP sent for user '.$user->id, [
            'type' => 'password_reset',
            'status' => 'failed',
            'driver' => 'testing',
            'message' => 'Rejected',
        ]);

    app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'password_reset');
});

test('handle returns the SmsResult from the gateway', function () {
    $user = User::factory()->create();
    $result = new SmsResult(status: 'failed', driver: 'testing', message: 'Rejected');

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->once()->andReturn($result);
    app()->instance(SmsService::class, $sms);

    $cooldown = Mockery::mock(EnsureOtpCooldownAction::class);
    $cooldown->shouldNotReceive('recordSent');
    app()->instance(EnsureOtpCooldownAction::class, $cooldown);

    Log::shouldReceive('channel')->with('sms')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    $actual = app(SendOtpSmsAction::class)->handle($user, '4829', '966512345678', 'phone');

    expect($actual)->toBe($result);
});
