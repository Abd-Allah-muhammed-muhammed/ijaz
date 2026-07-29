<?php

namespace App\Support\Api\Strategies;

use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use Illuminate\Http\Request;

/**
 * Detects an API version key from a query-string parameter (default `version`).
 *
 * INFORMATIONAL ONLY — a ?version= query value never changes which controller
 * Laravel invokes. Routing remains URL-prefix-based. Enable this strategy in
 * config/api.php negotiation.strategies only when query-based awareness is needed.
 */
final class QueryStringResolver implements ApiVersionResolverStrategy
{
    private string $queryParamName;

    public function __construct(?string $queryParamName = null)
    {
        $this->queryParamName = $queryParamName
            ?? (string) config('api.negotiation.query_param_name', 'version');
    }

    public function resolve(Request $request): ?string
    {
        $value = $request->query($this->queryParamName);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
