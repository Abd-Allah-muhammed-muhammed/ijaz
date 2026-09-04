<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\Auth\ProviderAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class AccountStatusController extends Controller
{
    public function __construct(
        private readonly ProviderAuthService $providerAuthService,
    ) {}

    public function show(Request $request, Provider $provider): InertiaResponse|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('provider.login');
        }

        $dto = $this->providerAuthService->resolveAccountStatusGate($provider);

        if ($dto === null) {
            return redirect()->route('provider.login');
        }

        return inertia('Provider/Auth/AccountStatusPage', $dto->toInertiaProps());
    }
}
