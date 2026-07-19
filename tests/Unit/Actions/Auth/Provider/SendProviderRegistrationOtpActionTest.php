<?php

use App\Actions\Auth\Provider\SendProviderRegistrationOtpAction;
use App\Models\RegisterVerificationCode;
use App\Services\Sms\Phone;
use Illuminate\Support\Facades\Log;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

test('SendProviderRegistrationOtpAction does not log the raw otp code', function () {
    config([
        'sms.verification_code_all_numbers' => true,
        'sms.verification_code' => '4829',
    ]);

    $phone = Phone::make('512345678')->toString();
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
        ->withArgs(function (string $message, array $context) use ($otp, $phone) {
            expect($message)->toBe('Login OTP sent for number '.$phone)
                ->and($message)->not->toContain($otp)
                ->and($context)->toBe([
                    'status' => 'success',
                    'driver' => 'authentica',
                    'message' => 'ok',
                ])
                ->and(json_encode($context))->not->toContain($otp);

            return true;
        });

    app(SendProviderRegistrationOtpAction::class)->handle('512345678');

    expect(RegisterVerificationCode::query()->where('queryable', $phone)->where('token', $otp)->exists())->toBeTrue();
});
