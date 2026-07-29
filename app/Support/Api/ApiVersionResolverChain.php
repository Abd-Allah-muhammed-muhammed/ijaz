<?php

namespace App\Support\Api;

use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use Illuminate\Http\Request;

/**
 * Runs configured {@see ApiVersionResolverStrategy} implementations in order
 * and caches the first registry-valid result on the request attributes.
 *
 * INFORMATIONAL ONLY — the resolved string is for awareness (logging, optional
 * response headers, deprecation metadata). It does NOT select controllers or
 * alter Laravel's URL-prefix route matching. A client header/query claiming
 * "v2" while hitting /api/v1/... still executes the v1 route; this chain may
 * merely report a different informational key when those strategies are enabled.
 */
final class ApiVersionResolverChain
{
    /**
     * @param  list<ApiVersionResolverStrategy>  $strategies
     */
    public function __construct(
        private readonly array $strategies,
        private readonly ApiVersionRegistry $registry,
    ) {}

    public function resolve(Request $request): string
    {
        $cached = $request->attributes->get('_resolved_api_version');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $version = $this->resolveFresh($request);
        $request->attributes->set('_resolved_api_version', $version);

        return $version;
    }

    private function resolveFresh(Request $request): string
    {
        foreach ($this->strategies as $strategy) {
            $version = $strategy->resolve($request);

            if ($version !== null && $this->registry->get($version) !== null) {
                return $version;
            }
        }

        return $this->registry->default()->key;
    }
}
