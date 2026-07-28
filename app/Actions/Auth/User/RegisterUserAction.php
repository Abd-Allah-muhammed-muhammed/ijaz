<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\UserRegisterResult;
use App\Enums\Auth\OtpPurposeEnum;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Random\RandomException;

class RegisterUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly IssueOtpAction $issueOtpAction,
        private readonly OtpSessionRepositoryInterface $otpSessionRepository,
        private readonly BuildOtpChallengeAction $buildOtpChallengeAction,
    ) {}

    /**
     * Create the user, open an OtpSession (purpose Register), send OTP, and
     * return the same challenge shape as login — no Sanctum token, no user payload.
     *
     * @throws RandomException
     */
    public function handle(array $validatedData): UserRegisterResult
    {
        $phone = Phone::make($validatedData['phone']);
        $validatedData['phone'] = $phone->toString();

        if (isset($validatedData['image']) && $validatedData['image'] instanceof UploadedFile) {
            $validatedData['image'] = $validatedData['image']->store('users');
        }

        if (! filled($validatedData['password'] ?? null)) {
            // Mobile users authenticate with phone and OTP. This unknown value only
            // satisfies the non-nullable column and dormant web auth scaffolding,
            // so it must never be derived from guessable user data.
            $validatedData['password'] = Str::random(32);
        }

        $user = $this->userRepository->create($validatedData);

        $session = $this->otpSessionRepository->createForUser(
            $user,
            OtpPurposeEnum::Register,
            (int) config('otp.session_ttl_minutes', 15),
        );

        $this->issueOtpAction->handle($user, OtpPurposeEnum::Register);

        return UserRegisterResult::fromChallenge(
            $this->buildOtpChallengeAction->handle($session, $user),
        );
    }
}
