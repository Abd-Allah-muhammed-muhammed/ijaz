<?php

namespace App\Http\Middleware;

use App\Actions\Auth\Provider\RedirectProviderToAccountStatusGateAction;
use App\Models\Provider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-applies the LoginRequest status gate on every authenticated provider
 * request. Providers use the session guard (no Sanctum tokens), so blocking a
 * provider mid-session must terminate the live session — the session-guard
 * equivalent of UpdateUserStatusAction revoking User tokens on block.
 */
class EnsureProviderIsApprovedMiddleware
{
    public function __construct(
        private readonly RedirectProviderToAccountStatusGateAction $redirectToGateAction,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ?Provider $provider */
        $provider = auth('provider')->user();

        if ($provider) {
            $message = $provider->status->authRejectionMessage((bool) $provider->blocked_until);

            if ($message !== null) {
                auth('provider')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $this->redirectToGateAction->handle($provider);
            }
        }

        return $next($request);
    }
}
