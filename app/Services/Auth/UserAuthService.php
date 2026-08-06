<?php

namespace App\Services\Auth;

use App\Actions\Auth\User\IssueOtpAction;
use App\Actions\Auth\User\LoginUserAction;
use App\Actions\Auth\User\LogoutAllDevicesAction;
use App\Actions\Auth\User\LogoutUserAction;
use App\Actions\Auth\User\RegisterUserAction;
use App\Actions\Auth\User\ResendOtpSessionAction;
use App\Actions\Auth\User\VerifyOtpAction;
use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\OtpChallengeResult;
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
        private readonly ResendOtpSessionAction $resendOtpSessionAction,
        private readonly LogoutUserAction $logoutUserAction,
        private readonly LogoutAllDevicesAction $logoutAllDevicesAction,
    ) {}

    public function login(string $phone): UserLoginResult
    {
        return $this->loginUserAction->handle($phone);
    }

    /**
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
     * Public session-based verify (login / register).
     *
     * @throws \Exception
     */
    public function verifyOtpSession(string $verificationId, string $code, ?string $playerId = null): OtpVerifyResult
    {
        return $this->verifyOtpAction->handleSession($verificationId, $code, $playerId);
    }

    /**
     * Authenticated purpose-based verify (phone / email / …).
     * Returns null for the "wrong OTP" case.
     *
     * @throws \Exception
     */
    public function verifyOtp(string $type, string $otp): ?OtpVerifyResult
    {
        $user = $this->userRepository->findAuthenticated();

        return $this->verifyOtpAction->handle($user, $type, $otp);
    }

    /**
     * @throws RandomException
     */
    public function resendOtpSession(string $verificationId): OtpChallengeResult|OtpVerifyResult
    {
        return $this->resendOtpSessionAction->handle($verificationId);
    }

    public function logout(): void
    {
        $user = $this->userRepository->findAuthenticated();
        $this->logoutUserAction->handle($user);
    }

    public function logoutAllDevices(): void
    {
        $user = $this->userRepository->findAuthenticated();
        $this->logoutAllDevicesAction->handle($user);
    }
}
