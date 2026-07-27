<?php

namespace App\Actions\Auth\User;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Contracts\Auth\OtpRepositoryInterface;
use App\Enums\Auth\OtpPurposeEnum;
use App\Models\User;
use App\Support\Phone;
use App\Traits\OTPGeneration;
use Random\RandomException;

class IssueOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
        private readonly SendOtpSmsAction $sendOtpSmsAction,
        private readonly OtpRepositoryInterface $otpRepository,
    ) {}

    /**
     * Generates, stores, and sends an OTP via SMS for the requested purpose.
     *
     * @throws RandomException
     */
    public function handle(User $user, OtpPurposeEnum|string $purpose): void
    {
        $purpose = $purpose instanceof OtpPurposeEnum
            ? $purpose
            : OtpPurposeEnum::from($purpose);

        $phone = Phone::make($user->phone);
        $normalizedPhone = $phone->toString();

        $this->ensureOtpCooldownAction->ensure($normalizedPhone);

        $code = $this->otpRepository->issueForSubject(
            $user,
            $purpose,
            $this->generateOtpForPhone($phone),
        );

        $this->sendOtpSmsAction->handle($code->token, $normalizedPhone, $purpose->value, $user);
    }
}
