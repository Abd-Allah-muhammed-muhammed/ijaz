<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\UserLoginResult;
use App\Enums\Auth\OtpPurposeEnum;
use App\Support\Phone;
use Random\RandomException;

class LoginUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly IssueOtpAction $issueOtpAction,
        private readonly OtpSessionRepositoryInterface $otpSessionRepository,
        private readonly BuildOtpChallengeAction $buildOtpChallengeAction,
    ) {}

    /**
     * Resolve the user, gate on status, create an OtpSession, send OTP, and
     * return a challenge payload (verification_id) — no Sanctum token.
     *
     * @throws RandomException
     */
    public function handle(string $rawPhone): UserLoginResult
    {
        $phone = Phone::make($rawPhone);
        $user = $this->userRepository->findByPhone($phone->toString());

        if (! $user) {
            return UserLoginResult::failure(__('auth.user_not_found'), 400);
        }

        $rejectionMessage = $user->status->authRejectionMessage((bool) $user->blocked_until);

        if ($rejectionMessage !== null) {
            return UserLoginResult::failure($rejectionMessage, 400);
        }

        $this->otpSessionRepository->deleteForUser($user, OtpPurposeEnum::Login);

        $session = $this->otpSessionRepository->createForUser(
            $user,
            OtpPurposeEnum::Login,
            (int) config('otp.session_ttl_minutes', 15),
        );

        $this->issueOtpAction->handle($user, OtpPurposeEnum::Login);

        return UserLoginResult::fromChallenge(
            $this->buildOtpChallengeAction->handle($session, $user),
        );
    }
}
