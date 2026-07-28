<?php

namespace App\Support\Api\Contracts;

use Illuminate\Http\Request;

/**
 * Strategy that attempts to detect which API version a client is referring to.
 *
 * INFORMATIONAL ONLY — this does NOT control Laravel route matching or which
 * controller handles a request. URL prefixes (e.g. api/v1/...) remain the sole
 * source of truth for routing. Resolvers exist for awareness (headers, logging,
 * deprecation metadata), not for dispatching to alternate controllers.
 */
interface ApiVersionResolverStrategy
{
    /**
     * Attempt to determine the API version the client is asking for from this
     * request. Returns null if this strategy finds no signal (allowing the
     * next strategy in the chain to try). This is INFORMATIONAL — it does not
     * affect Laravel's actual route matching, which is URL-prefix-based.
     */
    public function resolve(Request $request): ?string;
}
