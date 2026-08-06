<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the correct multi-guard actor for /broadcasting/auth.
 *
 * Dashboard Echo shares one auth endpoint with middleware that previously used
 * auth:admin,provider — Admin always won when both sessions existed in the same
 * browser. ProviderLayout then subscribed to provider-{id} + category.{id} for
 * the Inertia provider user, and every /broadcasting/auth call 403'd as Admin.
 *
 * Selection rules:
 * 1. Channel name forces an exclusive guard (provider-*, category.*, admin-*, …)
 * 2. Shared channels (online, chats.*) prefer the dashboard Referer
 * 3. Otherwise try admin then provider (legacy default)
 */
class AuthenticateBroadcasting
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->preferredGuards($request) as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);

                return $next($request);
            }
        }

        abort(403);
    }

    /**
     * @return list<string>
     */
    private function preferredGuards(Request $request): array
    {
        $channel = (string) $request->input('channel_name', '');
        $bare = (string) preg_replace('/^(private|presence)-/', '', $channel);
        $referer = (string) $request->headers->get('referer', '');

        if (str_starts_with($bare, 'provider-') || str_starts_with($bare, 'category.')) {
            return ['provider'];
        }

        if (str_starts_with($bare, 'admin-') || str_starts_with($bare, 'systems.')) {
            return ['admin'];
        }

        if (str_starts_with($bare, 'user-')) {
            return ['user'];
        }

        // Shared: online, chats.*, public — Provider URLs contain `/provider/`.
        if (str_contains($referer, '/provider/')) {
            return ['provider', 'admin'];
        }

        return ['admin', 'provider'];
    }
}
