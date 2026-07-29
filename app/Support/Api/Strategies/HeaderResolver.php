<?php

namespace App\Support\Api\Strategies;

use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use Illuminate\Http\Request;

/**
 * Detects an API version key from a request header (default X-API-Version).
 *
 * INFORMATIONAL ONLY — a version header never redirects or re-dispatches to a
 * different controller. Laravel continues to match routes by URL prefix only.
 * Enable this strategy in config/api.php negotiation.strategies when clients
 * should be able to declare a version for awareness/logging purposes.
 */
final class HeaderResolver implements ApiVersionResolverStrategy
{
    private string $headerName;

    public function __construct(?string $headerName = null)
    {
        $this->headerName = $headerName
            ?? (string) config('api.negotiation.header_name', 'X-API-Version');
    }

    public function resolve(Request $request): ?string
    {
        $value = $request->headers->get($this->headerName);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
