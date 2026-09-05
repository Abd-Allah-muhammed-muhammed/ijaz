<?php

namespace App\Services\Auth;

use App\Actions\Auth\Provider\LoginProviderAction;
use App\Actions\Auth\Provider\RegisterProviderAction;
use App\Actions\Auth\Provider\ResolveProviderAccountStatusGateAction;
use App\Actions\Auth\Provider\SendProviderRegistrationOtpAction;
use App\Actions\DeviceToken\ClearDeviceTokenByTokenAction;
use App\Actions\Provider\NotifyAdminsOfProviderPendingApprovalAction;
use App\Actions\Provider\SelfDeactivateProviderAction;
use App\Actions\Provider\UpdateProviderAction;
use App\DTOs\Auth\ProviderAccountStatusGateDTO;
use App\DTOs\Auth\ProviderLoginResult;
use App\DTOs\Auth\ProviderRegisterResult;
use App\DTOs\Provider\UpdateProviderDTO;
use App\Http\Requests\Provider\Auth\LoginRequest;
use App\Models\Provider;
use App\Services\Provider\ProviderDeviceTokenService;
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
        private readonly UpdateProviderAction $updateProviderAction,
        private readonly SelfDeactivateProviderAction $selfDeactivateProviderAction,
        private readonly ClearDeviceTokenByTokenAction $clearDeviceTokenByTokenAction,
        private readonly NotifyAdminsOfProviderPendingApprovalAction $notifyAdminsOfProviderPendingApprovalAction,
        private readonly ResolveProviderAccountStatusGateAction $resolveAccountStatusGateAction,
    ) {}

    public function login(LoginRequest $request): ProviderLoginResult
    {
        $request->authenticate();

        return $this->loginProviderAction->handle($request);
    }

    public function logout(Request $request): void
    {
        $provider = auth('provider')->user();
        $webToken = $request->session()->get(ProviderDeviceTokenService::SESSION_WEB_FCM_TOKEN_KEY);

        if ($provider instanceof Provider && is_string($webToken) && trim($webToken) !== '') {
            $this->clearDeviceTokenByTokenAction->handle($provider, $webToken);
        }

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
    public function register(array $validatedData): ProviderRegisterResult
    {
        $result = DB::transaction(fn () => $this->registerProviderAction->handle($validatedData));

        if ($result->success && $result->provider instanceof Provider) {
            $this->notifyAdminsOfProviderPendingApprovalAction->handle($result->provider);
        }

        return $result;
    }

    /**
     * Self-service profile update — reuses Pass E2 UpdateProviderAction
     * (logo + all ProviderTypeFilesEnum media + category/skill sync).
     *
     * @throws Throwable
     */
    public function updateProfile(Provider $provider, UpdateProviderDTO $dto): void
    {
        DB::transaction(function () use ($provider, $dto): void {
            $this->updateProviderAction->handle($provider, $dto);
        });
    }

    /**
     * Provider-initiated account deactivation. Transitions to SelfDeactivated
     * (distinct from admin Suspended/Blocked), then ends the session.
     * Does not cascade to orders, offers, or guarantor rows.
     */
    public function selfDeactivate(Provider $provider, Request $request): void
    {
        $this->selfDeactivateProviderAction->handle($provider);
        $this->logout($request);
    }

    /**
     * Fresh lookup for the signed account-status gate page.
     * Null when the provider is Approved (gate not applicable).
     */
    public function resolveAccountStatusGate(Provider $provider): ?ProviderAccountStatusGateDTO
    {
        return $this->resolveAccountStatusGateAction->handle($provider);
    }
}
