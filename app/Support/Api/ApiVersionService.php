<?php

namespace App\Support\Api;

use Illuminate\Http\Request;

/**
 * Convenience wrapper around {@see ApiVersionResolverChain} + {@see ApiVersionRegistry}.
 *
 * INFORMATIONAL ONLY — helpers here never influence Laravel route matching or
 * controller selection. Use for reading the resolved version key / deprecation
 * flags for logging and future response headers.
 */
final class ApiVersionService
{
    public function __construct(
        private readonly ApiVersionResolverChain $chain,
        private readonly ApiVersionRegistry $registry,
    ) {}

    public function current(?Request $request = null): string
    {
        return $this->chain->resolve($request ?? request());
    }

    public function isDeprecated(?string $version = null): bool
    {
        $key = $version ?? $this->current();

        return $this->registry->get($key)?->deprecated ?? false;
    }

    public function registry(): ApiVersionRegistry
    {
        return $this->registry;
    }

    public function chain(): ApiVersionResolverChain
    {
        return $this->chain;
    }
}
