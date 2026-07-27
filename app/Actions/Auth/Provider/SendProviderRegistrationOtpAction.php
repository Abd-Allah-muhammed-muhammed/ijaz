<?php

namespace App\Actions\Auth\Provider;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Contracts\Auth\OtpRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Support\Phone;
use App\Traits\OTPGeneration;
use Random\RandomException;

class SendProviderRegistrationOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly SendOtpSmsAction $sendOtpSmsAction,
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    /**
     * Issues a phone-keyed provider-registration OTP (5-minute TTL via config)
     * and dispatches SMS. OTP codes are intentionally omitted from log output.
     *
     * @throws RandomException
     */
    public function handle(string $rawPhone): void
    {
        $phone = Phone::make($rawPhone)->toString();
        $purpose = OtpPurposeEnum::ProviderRegistration;

        $this->ensureOtpCooldownAction->ensure($phone);

        $code = $this->otpRepository->issueForPhone(
            $phone,
            $purpose,
            $this->generateOtpForPhone($phone),
        );

        // No User yet — phone-only SMS path (preserves historical log shape).
        $this->sendOtpSmsAction->handle($code->token, $phone, $purpose->value);
    }
}
