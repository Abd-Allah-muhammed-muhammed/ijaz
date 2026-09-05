<?php

namespace App\Support\LazyLoading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Testing\TestResponse;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Orders\Models\OrderOffer;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exhaustive route sweeper for Eloquent lazy-loading violations (GET + write methods).
 *
 * Write routes run inside a DB transaction that is always rolled back after the
 * response is inspected so the audit does not persist mutations.
 */
final class LazyLoadingRouteSweeper
{
    /**
     * URI prefixes that are framework/tooling surfaces, not application domain
     * routes. Excluded from lazy-loading coverage on purpose (Horizon, Livewire
     * internals, Telescope, Log Viewer, Sanctum CSRF, payment gateway callbacks, …).
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

    /**
     * Human-readable reason recorded when a write route matches {@see SKIP_URI_PREFIXES}
     * or is a Closure action — these are out of scope for app lazy-loading audits.
     */
    public const OUT_OF_SCOPE_REASON = 'out of scope (tooling/framework: horizon/livewire/telescope/log-viewer/sanctum/payment-callbacks/closures)';

    /**
     * @var list<string>
     */
    public const WRITE_METHODS = ['POST', 'PUT', 'PATCH'];

    public function __construct(
        private readonly LazyLoadingViolationCollector $collector,
        private readonly FormRequestPayloadBuilder $payloadBuilder = new FormRequestPayloadBuilder,
    ) {}

    /**
     * @param  callable(string, string, array<string, mixed>): (TestResponse|Response)  $httpCall
     * @param  array<string, int|string>  $parameterMap
     * @param  array<string, array<string, int|string>>  $queryByUriSuffix
     * @return array{
     *     visited: int,
     *     skipped: int,
     *     errors: int,
     *     write_exercised: int,
     *     write_skipped: int,
     *     write_reached_response: int,
     *     violations: list<array{model: class-string, relation: string, uris: list<string>, guards: list<string>}>,
     *     skipped_uris: list<string>,
     *     write_skip_reasons: list<array{uri: string, method: string, reason: string}>,
     *     error_uris: list<array{uri: string, status: int|null, message: string}>
     * }
     */
    public function sweep(
        callable $httpCall,
        array $parameterMap,
        string $guard,
        ?callable $authenticate = null,
        array $queryByUriSuffix = [],
        bool $includeWrites = true,
    ): array {
        $visited = 0;
        $skipped = 0;
        $errors = 0;
        $writeExercised = 0;
        $writeSkipped = 0;
        $writeReachedResponse = 0;
        $skippedUris = [];
        $writeSkipReasons = [];
        $errorUris = [];

        $methods = $includeWrites ? ['GET', ...self::WRITE_METHODS] : ['GET'];

        foreach ($this->candidateMethodRoutes($methods) as [$method, $route]) {
            $uri = '/'.ltrim($route->uri(), '/');
            $isWrite = in_array($method, self::WRITE_METHODS, true);

            if ($this->shouldSkip($route)) {
                $skipped++;
                $skippedUris[] = "{$method} {$uri}";
                if ($isWrite) {
                    $writeSkipped++;
                    $writeSkipReasons[] = [
                        'uri' => $uri,
                        'method' => $method,
                        'reason' => self::OUT_OF_SCOPE_REASON,
                    ];
                }

                continue;
            }

            $resolved = $this->resolveUri($route, $parameterMap);

            if ($resolved === null) {
                $skipped++;
                $skippedUris[] = "{$method} {$uri} (unresolved params)";
                if ($isWrite) {
                    $writeSkipped++;
                    $writeSkipReasons[] = ['uri' => $uri, 'method' => $method, 'reason' => 'unresolved route params'];
                }

                continue;
            }

            $resolved = $this->appendQueryExtras($resolved, $queryByUriSuffix);

            if ($authenticate !== null) {
                $authenticate();
            }

            $payload = [];
            if ($isWrite) {
                $built = $this->payloadBuilder->build($route, $parameterMap, $method, $resolved);
                if (isset($built['skip'])) {
                    $skipped++;
                    $writeSkipped++;
                    $writeSkipReasons[] = [
                        'uri' => $resolved,
                        'method' => $method,
                        'reason' => $built['skip'],
                    ];

                    continue;
                }
                $payload = $built['payload'];
                $writeExercised++;
            }

            $contextLabel = "{$method} {$resolved}";
            $this->collector->setContext($contextLabel, $guard);
            $violationsBefore = $this->collector->count();

            try {
                if ($isWrite) {
                    DB::beginTransaction();
                }

                try {
                    /** @var TestResponse|Response $response */
                    $response = $httpCall($method, $resolved, $payload);
                    $visited++;

                    $status = method_exists($response, 'status')
                        ? (int) $response->status()
                        : (int) $response->getStatusCode();

                    if ($isWrite) {
                        // Any HTTP response (including domain 422s like invalid OTP) means the
                        // write stack ran under preventLazyLoading — count as reached.
                        $writeReachedResponse++;
                        if ($status === 422) {
                            $writeSkipReasons[] = [
                                'uri' => $resolved,
                                'method' => $method,
                                'reason' => 'HTTP 422 domain/validation response (route still exercised for lazy-loading)',
                            ];
                        }
                    }

                    if ($status >= 500 && $this->collector->count() === $violationsBefore) {
                        $errors++;
                        $errorUris[] = [
                            'uri' => $contextLabel,
                            'status' => $status,
                            'message' => 'HTTP '.$status,
                        ];
                    }
                } finally {
                    if ($isWrite && DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }
                }
            } catch (\Throwable $throwable) {
                $visited++;
                if ($isWrite && DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                if (! str_contains($throwable::class, 'LazyLoading')) {
                    $errors++;
                    $errorUris[] = [
                        'uri' => $contextLabel,
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
            'write_exercised' => $writeExercised,
            'write_skipped' => $writeSkipped,
            'write_reached_response' => $writeReachedResponse,
            'violations' => $this->collector->uniqueByModelRelation(),
            'skipped_uris' => $skippedUris,
            'write_skip_reasons' => $writeSkipReasons,
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
     * Expand each route into one entry per HTTP method we care about.
     *
     * @param  list<string>  $methods
     * @return list<array{0: string, 1: Route}>
     */
    public function candidateMethodRoutes(array $methods): array
    {
        $out = [];

        foreach (RouteFacade::getRoutes() as $route) {
            foreach ($methods as $method) {
                if (in_array($method, $route->methods(), true)) {
                    $out[] = [$method, $route];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<Route>
     */
    public function candidateGetRoutes(): array
    {
        return array_values(array_map(
            static fn (array $pair): Route => $pair[1],
            $this->candidateMethodRoutes(['GET']),
        ));
    }

    public function shouldSkip(Route $route): bool
    {
        $uri = ltrim($route->uri(), '/');

        foreach (self::SKIP_URI_PREFIXES as $prefix) {
            // Match "livewire" against both "livewire/..." and hashed "livewire-xxxx/..."
            if ($uri === $prefix
                || str_starts_with($uri, $prefix.'/')
                || str_starts_with($uri, $prefix.'-')) {
                return true;
            }
        }

        if ($route->getActionName() === 'Closure') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, int|string>  $parameterMap
     */
    public function resolveUri(Route $route, array $parameterMap): ?string
    {
        $uri = $route->uri();
        $parameters = $route->parameterNames();
        $fromSignature = $this->modelsFromControllerSignature($route);

        foreach ($parameters as $name) {
            $value = $this->parameterValueFor($name, $parameterMap, $fromSignature);

            if ($value === null) {
                return null;
            }

            $uri = preg_replace('/\{'.$name.'\??\}/', (string) $value, $uri) ?? $uri;
        }

        if (str_contains($uri, '{')) {
            return null;
        }

        return '/'.ltrim($uri, '/');
    }

    /**
     * @param  array<string, int|string>  $parameterMap
     * @param  array<string, class-string>  $fromSignature
     */
    private function parameterValueFor(string $name, array $parameterMap, array $fromSignature): int|string|null
    {
        if ($name === 'offer') {
            $class = $fromSignature['offer'] ?? null;
            if ($class === OpportunityOffer::class && array_key_exists('opportunityOffer', $parameterMap)) {
                return $parameterMap['opportunityOffer'];
            }
            if ($class === OrderOffer::class && array_key_exists('orderOffer', $parameterMap)) {
                return $parameterMap['orderOffer'];
            }
        }

        if (array_key_exists($name, $parameterMap)) {
            return $parameterMap[$name];
        }

        return null;
    }

    /**
     * @return array<string, class-string>
     */
    private function modelsFromControllerSignature(Route $route): array
    {
        $action = $route->getAction('controller');
        if (! is_string($action)) {
            return [];
        }

        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
        } else {
            $class = $action;
            $method = '__invoke';
        }

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return [];
        }

        $map = [];
        $reflection = new ReflectionMethod($class, $method);
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (is_subclass_of($typeName, Model::class)) {
                $map[$parameter->getName()] = $typeName;
            }
        }

        return $map;
    }
}
