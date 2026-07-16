<?php

namespace App\Actions\Auth\User;

use App\Contracts\Auth\UserRepositoryInterface;
use App\DTOs\Auth\UserRegisterResult;
use App\Services\Sms\Phone;
use Illuminate\Http\UploadedFile;
use Random\RandomException;

class RegisterUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SendLoginOtpAction $sendLoginOtpAction,
    ) {}

    /**
     * Reproduces AuthController::register()'s body exactly: phone normalization,
     * image storage, password-defaults-to-phone-when-blank, user creation, OTP
     * send, 15-minute login token, and nationality.translation eager load.
     *
     * Transaction handling stays at the Service level (UserAuthService::register
     * wraps this in DB::transaction), matching the current controller's
     * DB::beginTransaction/commit/rollBack wrapping.
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
            $validatedData['password'] = $validatedData['phone'];
        }

        $user = $this->userRepository->create($validatedData);
        $this->sendLoginOtpAction->handle($user);
        $token = explode('|', $user->createToken('login', [], now()->addMinutes(15))->plainTextToken)[1];
        $user->load(['nationality.translation']);

        return new UserRegisterResult(user: $user, token: $token);
    }
}
