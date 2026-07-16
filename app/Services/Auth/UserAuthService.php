<?php

namespace App\Services\Auth;

use App\Actions\Auth\User\IssueOtpAction;
use App\Actions\Auth\User\LoginUserAction;
use App\Actions\Auth\User\RegisterUserAction;
use App\Actions\Auth\User\VerifyOtpAction;
use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\OtpVerifyResult;
use App\DTOs\Auth\UserLoginResult;
use App\DTOs\Auth\UserRegisterResult;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class UserAuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoginUserAction $loginUserAction,
        private readonly RegisterUserAction $registerUserAction,
        private readonly IssueOtpAction $issueOtpAction,
        private readonly VerifyOtpAction $verifyOtpAction,
    ) {}

    public function login(string $phone): UserLoginResult
    {
        return $this->loginUserAction->handle($phone);
    }

    /**
     * Wraps registration in a transaction, mirroring the controller's original
     * DB::beginTransaction/commit/rollBack. On failure the transaction rolls
     * back and the throwable is re-thrown for the controller to report() and
     * map to the generic failure response.
     *
     * @throws Throwable
     */
    public function register(array $validatedData): UserRegisterResult
    {
        return DB::transaction(fn () => $this->registerUserAction->handle($validatedData));
    }

    /**
     * @throws RandomException
     */
    public function sendOtp(string $type): void
    {
        $user = $this->userRepository->findAuthenticated();
        $this->issueOtpAction->handle($user, $type);
    }

    /**
     * Returns null for the "wrong OTP" case (invalid/expired/missing code),
     * otherwise the processCode()-shaped result.
     *
     * @throws \Exception
     */
    public function verifyOtp(string $type, string $otp): ?OtpVerifyResult
    {
        $user = $this->userRepository->findAuthenticated();

        return $this->verifyOtpAction->handle($user, $type, $otp);
    }

    public function logout(): void
    {
        $user = $this->userRepository->findAuthenticated();
        $user->tokens()->delete();
    }
}
