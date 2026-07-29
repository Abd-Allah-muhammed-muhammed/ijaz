<?php

namespace App\Actions\Auth\User;

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\DTOs\Auth\OtpChallengeResult;
use App\Models\OtpSession;
use App\Models\User;
use App\Support\Phone;

class BuildOtpChallengeAction
{
    public function __construct(
        private readonly EnsureOtpCooldownAction $ensureOtpCooldownAction,
    ) {}

    public function handle(OtpSession $session, User $user): OtpChallengeResult
    {
        $phone = Phone::make($user->phone)->toString();
        $availableIn = $this->ensureOtpCooldownAction->availableIn($phone);
        $expiresIn = max(0, $session->expires_at->getTimestamp() - now()->getTimestamp());

        return OtpChallengeResult::success(
            verificationId: (string) $session->id,
            expiresIn: $expiresIn,
            resendAvailableAt: now()->addSeconds($availableIn)->toIso8601String(),
        );
    }
}
