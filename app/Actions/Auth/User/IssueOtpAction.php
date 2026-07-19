<?php

namespace App\Actions\Auth\User;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Actions\Auth\SendOtpSmsAction;
use App\Models\User;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Random\RandomException;

class IssueOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
        private readonly SendOtpSmsAction $sendOtpSmsAction,
    ) {}

    /**
     * Generates, stores, and sends an OTP via SMS for the requested type.
     *
     * @throws RandomException
     */
    public function handle(User $user, string $type): void
    {
        $phone = Phone::make($user->phone);
        $normalizedPhone = $phone->toString();

        $this->ensureOtpCooldownAction->ensure($normalizedPhone);

        $code = $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), $type);

        $this->sendOtpSmsAction->handle($user, $code->token, $normalizedPhone, $type);
    }
}
