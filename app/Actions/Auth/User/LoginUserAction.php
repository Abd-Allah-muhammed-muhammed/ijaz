<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\UserLoginResult;
use App\Enums\Users\UserStatusEnum;
use App\Services\Sms\Phone;
use Random\RandomException;

class LoginUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SendLoginOtpAction $sendLoginOtpAction,
    ) {}

    /**
     * Reproduces AuthController::login() exactly: resolve user by phone, apply
     * the status gate, then generate+store+send the OTP, purge existing tokens,
     * and issue a 15-minute empty-abilities "login" token.
     *
     * Failure cases ("user not found" / status blocked) are returned as a
     * discriminated UserLoginResult with the verbatim messages and 400 status
     * the controller currently produces — not thrown — so the response shape is
     * preserved without side effects.
     *
     * @throws RandomException
     */
    public function handle(string $rawPhone): UserLoginResult
    {
        $phone = Phone::make($rawPhone);
        $user = $this->userRepository->findByPhone($phone->toString());

        if (! $user) {
            return UserLoginResult::failure(trans('user not found'), 400);
        }

        if ($user->status->isNot(UserStatusEnum::Active)) {
            $message = match ($user->status) {
                UserStatusEnum::Deleted => trans('this account is deleted'),
                UserStatusEnum::Blocked => $user->blocked_until ? trans('this account is blocked') : trans('this account is banned'),
                default => trans('this account is not active '),
            };

            return UserLoginResult::failure($message, 400);
        }

        $this->sendLoginOtpAction->handle($user);
        $user->tokens()->delete();

        return UserLoginResult::success(
            explode('|', $user->createToken('login', [], now()->addMinutes(15))->plainTextToken)[1],
        );
    }
}
