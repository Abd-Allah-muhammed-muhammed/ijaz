<?php

namespace App\Actions\Auth\Provider;

use App\Actions\Auth\EnsureOtpCooldownAction;
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
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
    ) {}

    /**
     * Reproduces Frontend\AuthController::otp()'s behavior:
     * System B (RegisterVerificationCode, phone-string keyed), 5-minute TTL,
     * and SMS dispatch. OTP codes are intentionally omitted from log output.
     *
     * @throws RandomException
     */
    public function handle(string $rawPhone): void
    {
        $phone = Phone::make($rawPhone)->toString();

        $this->ensureOtpCooldownAction->ensure($phone);

        $code = RegisterVerificationCode::updateOrCreate([
            'queryable' => $phone,
        ], [
            'token' => $this->generateOtpForPhone($phone),
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->smsService->sendOtp($code->token, $phone);

        if ($result->isSuccessful()) {
            $this->ensureOtpCooldownAction->recordSent($phone);
        }

        // Do not log the OTP or $result->data: AuthenticaGateway nests the code
        // in data.message.body (SmsMessage::toArray()), which would leak it.
        Log::channel('sms')->info('Login OTP sent for number '.$phone, [
            'status' => $result->status,
            'driver' => $result->driver,
            'message' => $result->message,
        ]);
    }
}
