<?php

use App\Actions\Auth\User\SendLoginOtpAction;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Support\Facades\Log;
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
