<?php

namespace App\Support\Api\Strategies;

use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use Illuminate\Http\Request;

/**
 * Detects an API version key from the URL path shape `api/{version}/...`.
 *
 * INFORMATIONAL ONLY — parsing the path does not change which route Laravel
 * matched. The router already selected a controller via the registered URL
 * prefix; this strategy only reports which version segment was present so
 * callers can attach metadata (logging, response headers, etc.).
 */
final class UrlPrefixResolver implements ApiVersionResolverStrategy
{
    public function resolve(Request $request): ?string
    {
        $segments = explode('/', trim($request->path(), '/'));

        if (($segments[0] ?? null) !== 'api') {
            return null;
        }

        $version = $segments[1] ?? null;

        if (! is_string($version) || $version === '') {
            return null;
        }

        return $version;
    }
}
