<?php

namespace App\Console\Commands;

use App\Support\LazyLoading\LazyLoadingRouteSweeper;
use App\Support\LazyLoading\LazyLoadingSweepFixture;
use App\Support\LazyLoading\LazyLoadingViolationCollector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

#[Signature('app:lazy-loading-route-sweep {--guard=* : Guards to authenticate as (admin, provider, user, guest). Default: all} {--json= : Write full report JSON to this path} {--fail-on-violation : Exit 1 when any unique violation is found} {--writes : Also exercise POST/PUT/PATCH with synthetic FormRequest payloads (rolled back)} {--get-only : Skip write routes}')]
#[Description('Exhaustive route sweep that collects Eloquent lazy-loading violations under preventLazyLoading')]
class LazyLoadingRouteSweepCommand extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production (preventLazyLoading is disabled there).');

            return self::FAILURE;
        }

        $guards = $this->option('guard');
        if ($guards === [] || $guards === null) {
            $guards = ['admin', 'provider', 'user', 'guest'];
        }

        $includeWrites = ! $this->option('get-only');
        if ($this->option('writes')) {
            $includeWrites = true;
        }

        $fixture = LazyLoadingSweepFixture::seed();
        $collector = new LazyLoadingViolationCollector;
        $sweeper = new LazyLoadingRouteSweeper($collector);
        $collector->install(collectOnly: true);
        $collector->reset();

        Notification::fake();

        $summary = [
            'visited' => 0,
            'skipped' => 0,
            'errors' => 0,
            'write_exercised' => 0,
            'write_skipped' => 0,
            'write_reached_response' => 0,
            'by_guard' => [],
            'write_skip_reasons' => [],
        ];

        try {
            foreach ($guards as $guard) {
                Auth::guard('web')->logout();
                Auth::guard('admin')->logout();
                Auth::guard('provider')->logout();

                $authenticate = match ($guard) {
                    'admin' => function () use ($fixture): void {
                        Auth::guard('admin')->login($fixture['admin']);
                    },
                    'provider' => function () use ($fixture): void {
                        Auth::guard('provider')->login($fixture['provider']);
                    },
                    'user' => function () use ($fixture): void {
                        Auth::guard('web')->login($fixture['user']);
                        Auth::guard('user-api')->setUser($fixture['user']);
                    },
                    default => null,
                };

                $result = $sweeper->sweep(
                    httpCall: function (string $method, string $uri, array $payload = []) use ($guard, $fixture): Response {
                        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
                        $isApi = str_starts_with(ltrim($path, '/'), 'api/');
                        $request = Request::create($uri, $method, $payload);
                        $request->headers->set('Accept', $isApi ? 'application/json' : 'text/html');

                        if ($guard === 'user' && $isApi) {
                            $request->headers->set(
                                'Authorization',
                                'Bearer '.$fixture['user']->createToken('lazy-sweep')->plainTextToken
                            );
                        }

                        return app()->handle($request);
                    },
                    parameterBag: $fixture['parameters'],
                    guard: $guard,
                    authenticate: $authenticate,
                    queryByUriSuffix: [
                        'api/v1/catalog/providers' => ['phone' => $fixture['provider']->phone],
                        'api/v1/user/providers/get' => ['provider_id' => $fixture['provider']->id],
                    ],
                    includeWrites: $includeWrites,
                );

                $summary['visited'] += $result['visited'];
                $summary['skipped'] += $result['skipped'];
                $summary['errors'] += $result['errors'];
                $summary['write_exercised'] += $result['write_exercised'];
                $summary['write_skipped'] += $result['write_skipped'];
                $summary['write_reached_response'] += $result['write_reached_response'];
                $summary['write_skip_reasons'] = array_merge(
                    $summary['write_skip_reasons'],
                    $result['write_skip_reasons'],
                );
                $summary['by_guard'][$guard] = [
                    'visited' => $result['visited'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'write_exercised' => $result['write_exercised'],
                    'write_reached_response' => $result['write_reached_response'],
                    'unique_violations' => count($result['violations']),
                    'error_uris' => $result['error_uris'],
                ];
            }
        } finally {
            $collector->restore();
        }

        $unique = $collector->uniqueByModelRelation();

        $this->info(sprintf(
            'Visited %d routes across %d guard(s); skipped %d; HTTP/infra errors %d; unique violations %d.',
            $summary['visited'],
            count($guards),
            $summary['skipped'],
            $summary['errors'],
            count($unique),
        ));

        if ($includeWrites) {
            $writeTotal = $summary['write_exercised'] + $summary['write_skipped'];
            $this->info(sprintf(
                'Write coverage (guard-multiplied): exercised=%d/%d (%.1f%%) reached_response=%d skipped=%d.',
                $summary['write_exercised'],
                $writeTotal,
                $writeTotal > 0 ? (100 * $summary['write_exercised'] / $writeTotal) : 0,
                $summary['write_reached_response'],
                $summary['write_skipped'],
            ));
            $this->line('Out-of-scope write skips (not app domain routes): '.LazyLoadingRouteSweeper::OUT_OF_SCOPE_REASON);
            $this->line('Prefixes: '.implode(', ', LazyLoadingRouteSweeper::SKIP_URI_PREFIXES));
        }

        foreach ($unique as $row) {
            $this->line(sprintf(
                '  - %s::%s  guards=[%s]  sample=%s',
                $row['model'],
                $row['relation'],
                implode(',', $row['guards']),
                $row['uris'][0] ?? '(none)',
            ));
        }

        $payload = [
            'summary' => $summary,
            'violations' => $unique,
            'raw_count' => $collector->count(),
        ];

        $jsonPath = $this->option('json');
        if (is_string($jsonPath) && $jsonPath !== '') {
            file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('Wrote report to '.$jsonPath);
        }

        if ($this->option('fail-on-violation') && $unique !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
