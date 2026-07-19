<?php

namespace App\Actions\Auth\User;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Models\User;
use App\Services\Sms\Phone;
use App\Traits\OTPGeneration;
use Random\RandomException;

class IssueOtpAction
{
    use OTPGeneration;

    public function __construct(
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
    ) {}

    /**
     * Matches OtpController::send()'s current behavior EXACTLY: generates and
     * stores a code but does NOT dispatch an SMS and does NOT log anything.
     *
     * This is intentionally different from SendLoginOtpAction (see its docblock).
     * The missing SMS dispatch is a KNOWN gap deferred to a later security step —
     * do not "fix" it here.
     *
     * @throws RandomException
     */
    public function handle(User $user, string $type): void
    {
        $phone = Phone::make($user->phone);
        $normalizedPhone = $phone->toString();

        $this->ensureOtpCooldownAction->ensure($normalizedPhone);

        $user->updateOrCreateVerificationCode($this->generateOtpForPhone($phone), $type);

        $this->ensureOtpCooldownAction->recordSent($normalizedPhone);
    }
}
