<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\DTOs\Auth\OtpChallengeResult;
use App\DTOs\Auth\OtpVerifyResult;
use App\Models\User;
use Random\RandomException;

class ResendOtpSessionAction
{
    public function __construct(
        private readonly OtpSessionRepositoryInterface $otpSessionRepository,
        private readonly IssueOtpAction $issueOtpAction,
        private readonly BuildOtpChallengeAction $buildOtpChallengeAction,
    ) {}

    /**
     * Reuses the same OtpSession verification_id, extends expiry, and re-sends OTP.
     * Cooldown is enforced inside IssueOtpAction.
     *
     * @return OtpChallengeResult|OtpVerifyResult Challenge on success; verify-style failure on missing/expired session.
     *
     * @throws RandomException
     */
    public function handle(string $verificationId): OtpChallengeResult|OtpVerifyResult
    {
        $session = $this->otpSessionRepository->findById($verificationId);

        if (! $session || $session->isExpired()) {
            return OtpVerifyResult::failure('verification_expired', trans('verification expired'));
        }

        if ($session->hasExceededAttempts()) {
            return OtpVerifyResult::failure('max_attempts_exceeded', trans('max attempts exceeded'));
        }

        /** @var User $user */
        $user = $session->user;

        $session = $this->otpSessionRepository->extendExpiry(
            $session,
            (int) config('otp.session_ttl_minutes', 15),
        );

        $this->issueOtpAction->handle($user, $session->purpose);

        return $this->buildOtpChallengeAction->handle($session, $user);
    }
}
