<?php

namespace App\Actions\Auth\Provider;

use App\Models\RegisterVerificationCode;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Services\SmsService;
use Random\RandomException;

class SendProviderRegistrationOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    /**
     * Reproduces Frontend\AuthController::otp()'s exact current behavior:
     * System B (RegisterVerificationCode, phone-string keyed), 5-minute TTL,
     * SMS dispatch, and the OTP-in-logs line. The logs and lack of rate
     * limiting are known issues deferred to a later security-focused step.
     *
     * @throws RandomException
     */
    public function handle(string $rawPhone): void
    {
        $phone = Phone::make($rawPhone)->toString();

        $code = RegisterVerificationCode::updateOrCreate([
            'queryable' => $phone,
        ], [
            'token' => $this->generateOtpForPhone($phone),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->smsService->sendOtp($code->token, $phone);

        Log::channel('sms')
            ->info(
                'Login OTP for number '.$phone.' is '.$code->token,
                $result->toArray()
            );
    }
}
