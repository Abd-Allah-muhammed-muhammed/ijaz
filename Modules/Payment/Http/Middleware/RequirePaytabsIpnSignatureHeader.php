<?php

namespace Modules\Payment\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the unused vendor Paytabs paymentIPN route: missing signature → TypeError 500.
 */
final class RequirePaytabsIpnSignatureHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('signature');

        if (! is_string($signature) || $signature === '') {
            return response('invalid callback\IPN request', 400)
                ->header('Content-Type', 'text/plain');
        }

        return $next($request);
    }
}
