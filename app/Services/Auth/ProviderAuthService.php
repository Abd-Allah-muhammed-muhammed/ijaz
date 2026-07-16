<?php

namespace App\Services\Auth;

use App\Actions\Auth\Provider\LoginProviderAction;
use App\Actions\Auth\Provider\RegisterProviderAction;
use App\Actions\Auth\Provider\SendProviderRegistrationOtpAction;
use App\DTOs\Auth\ProviderLoginResult;
use App\DTOs\Auth\ProviderRegisterResult;
use App\Http\Requests\Provider\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class ProviderAuthService
{
    public function __construct(
        private readonly LoginProviderAction $loginProviderAction,
        private readonly RegisterProviderAction $registerProviderAction,
        private readonly SendProviderRegistrationOtpAction $sendProviderRegistrationOtpAction,
    ) {}

    public function login(LoginRequest $request): ProviderLoginResult
    {
        $request->authenticate();

        return $this->loginProviderAction->handle($request);
    }

    public function logout(Request $request): void
    {
        auth('provider')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @throws RandomException
     */
    public function sendRegistrationOtp(string $phone): void
    {
        $this->sendProviderRegistrationOtpAction->handle($phone);
    }

    /**
     * Wraps registration in a transaction, mirroring the controller's original
     * DB::beginTransaction/commit/rollBack. The invalid-logo path returns a
     * failed() result (transaction commits with nothing written). Any other
     * failure re-throws so the transaction rolls back and the controller
     * report()s it and maps to the generic failure response.
     *
     * @throws Throwable
     */
    public function register(array $validatedData, Request $request): ProviderRegisterResult
    {
        return DB::transaction(fn () => $this->registerProviderAction->handle($validatedData, $request));
    }
}
