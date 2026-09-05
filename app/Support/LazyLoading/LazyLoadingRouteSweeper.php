<?php

namespace App\Support\LazyLoading;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exhaustive GET-route sweeper for Eloquent lazy-loading violations.
 *
 * Used by the permanent Pest regression test and the artisan command.
 * Substitutes seeded model keys into route parameters where possible;
 * skips routes whose required params cannot be resolved.
 */
final class LazyLoadingRouteSweeper
{
    /**
     * URI prefixes / exact names always skipped (devtools, health, webhooks, assets).
     *
     * @var list<string>
     */
    public const SKIP_URI_PREFIXES = [
        '_boost',
        '_debugbar',
        'horizon',
        'telescope',
        'log-viewer',
        'docs',
        'up',
        'sanctum',
        'broadcasting',
        'api/payments',
        'pulse',
        'livewire',
        'storage',
        'vendor',
    ];

    public function __construct(
        private readonly LazyLoadingViolationCollector $collector,
    ) {}

    /**
     * @param  array<string, int|string>  $parameterBag  parameter name => substitute value
     * @param  array<string, array<string, int|string>>  $queryByUriSuffix  uri path suffix => query string map
     *                                                                      e.g. ['api/v1/catalog/providers' => ['phone' => '...']]
     * @return array{
     *     visited: int,
     *     skipped: int,
     *     errors: int,
     *     violations: list<array{model: class-string, relation: string, uris: list<string>, guards: list<string>}>,
     *     skipped_uris: list<string>,
     *     error_uris: list<array{uri: string, status: int|null, message: string}>
     * }
     */
    public function sweep(
        callable $httpGet,
        array $parameterBag,
        string $guard,
        ?callable $authenticate = null,
        array $queryByUriSuffix = [],
    ): array {
        $visited = 0;
        $skipped = 0;
        $errors = 0;
        $skippedUris = [];
        $errorUris = [];

        foreach ($this->candidateGetRoutes() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');

            if ($this->shouldSkip($route)) {
                $skipped++;
                $skippedUris[] = $uri;

                continue;
            }

            $resolved = $this->resolveUri($route, $parameterBag);

            if ($resolved === null) {
                $skipped++;
                $skippedUris[] = $uri.' (unresolved params)';

                continue;
            }

            $resolved = $this->appendQueryExtras($resolved, $queryByUriSuffix);

            if ($authenticate !== null) {
                $authenticate();
            }

            $this->collector->setContext($resolved, $guard);
            $violationsBefore = $this->collector->count();

            try {
                /** @var TestResponse|Response $response */
                $response = $httpGet($resolved);
                $visited++;

                $status = method_exists($response, 'status')
                    ? (int) $response->status()
                    : (int) $response->getStatusCode();

                // Auth/permission misses are expected for some combinations; they are
                // not lazy-load findings. 5xx without a recorded violation still counts
                // as an infrastructure error for the sweep report.
                if ($status >= 500 && $this->collector->count() === $violationsBefore) {
                    $errors++;
                    $errorUris[] = [
                        'uri' => $resolved,
                        'status' => $status,
                        'message' => 'HTTP '.$status,
                    ];
                }
            } catch (\Throwable $throwable) {
                $visited++;
                // Violations are collected via the handler; other throwables are errors.
                if (! str_contains($throwable::class, 'LazyLoading')) {
                    $errors++;
                    $errorUris[] = [
                        'uri' => $resolved,
                        'status' => null,
                        'message' => $throwable::class.': '.$throwable->getMessage(),
                    ];
                }
            }
        }

        return [
            'visited' => $visited,
            'skipped' => $skipped,
            'errors' => $errors,
            'violations' => $this->collector->uniqueByModelRelation(),
            'skipped_uris' => $skippedUris,
            'error_uris' => $errorUris,
        ];
    }

    /**
     * @param  array<string, array<string, int|string>>  $queryByUriSuffix
     */
    public function appendQueryExtras(string $resolvedUri, array $queryByUriSuffix): string
    {
        $path = parse_url($resolvedUri, PHP_URL_PATH) ?: $resolvedUri;
        $normalized = ltrim($path, '/');

        foreach ($queryByUriSuffix as $suffix => $query) {
            $suffix = ltrim($suffix, '/');
            if ($normalized === $suffix || str_ends_with($normalized, '/'.$suffix)) {
                $qs = http_build_query($query);

                return $resolvedUri.(str_contains($resolvedUri, '?') ? '&' : '?').$qs;
            }
        }

        return $resolvedUri;
    }

    /**
     * @return list<Route>
     */
    public function candidateGetRoutes(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    public function shouldSkip(Route $route): bool
    {
        $uri = ltrim($route->uri(), '/');

        foreach (self::SKIP_URI_PREFIXES as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                return true;
            }
        }

        // Skip closure fallbacks and ignitions.
        $action = $route->getActionName();
        if ($action === 'Closure') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, int|string>  $parameterBag
     */
    public function resolveUri(Route $route, array $parameterBag): ?string
    {
        $uri = $route->uri();
        $parameters = $route->parameterNames();

        foreach ($parameters as $name) {
            if (! array_key_exists($name, $parameterBag)) {
                return null;
            }

            $uri = preg_replace('/\{'.$name.'\??\}/', (string) $parameterBag[$name], $uri) ?? $uri;
        }

        // Drop any remaining optional empty segments.
        if (str_contains($uri, '{')) {
            return null;
        }

        return '/'.ltrim($uri, '/');
    }
}
