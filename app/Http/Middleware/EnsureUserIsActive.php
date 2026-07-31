<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-applies the LoginUserAction status gate on every authenticated User API
 * request. Sanctum only validates the token itself — without this, a user
 * banned/deleted after token issuance would keep working until the token was
 * revoked or naturally expired (tokens never expire in this app).
 *
 * Provider equivalent: EnsureProviderIsApprovedMiddleware (session guard).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $message = $user->status->authRejectionMessage((bool) $user->blocked_until);

        if ($message === null) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }

        return response()->json([
            'success' => false,
            'data' => [],
            'errors' => (object) [],
            'message' => $message,
            'token' => '',
        ], Response::HTTP_FORBIDDEN);
    }
}
