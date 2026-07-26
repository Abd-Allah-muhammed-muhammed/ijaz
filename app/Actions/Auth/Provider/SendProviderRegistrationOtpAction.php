<?php

namespace App\Actions\Auth\Provider;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Models\RegisterVerificationCode;
use App\Support\Phone;
use App\Traits\OTPGeneration;
use Random\RandomException;

class SendProviderRegistrationOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly SendOtpSmsAction $sendOtpSmsAction,
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

        // No User yet — phone-only SMS path (preserves historical log shape).
        $this->sendOtpSmsAction->handle($code->token, $phone, 'login');
    }
}
