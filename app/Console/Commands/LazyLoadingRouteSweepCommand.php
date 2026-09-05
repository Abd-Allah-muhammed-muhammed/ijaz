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
use Symfony\Component\HttpFoundation\Response;

#[Signature('app:lazy-loading-route-sweep {--guard=* : Guards to authenticate as (admin, provider, user, guest). Default: all} {--json= : Write full report JSON to this path} {--fail-on-violation : Exit 1 when any unique violation is found}')]
#[Description('Exhaustive GET-route sweep that collects Eloquent lazy-loading violations under preventLazyLoading')]
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

        $fixture = LazyLoadingSweepFixture::seed();
        $collector = new LazyLoadingViolationCollector;
        $sweeper = new LazyLoadingRouteSweeper($collector);
        $collector->install(collectOnly: true);
        $collector->reset();

        $combined = [];
        $summary = [
            'visited' => 0,
            'skipped' => 0,
            'errors' => 0,
            'by_guard' => [],
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
                    httpGet: function (string $uri) use ($guard, $fixture): Response {
                        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
                        $request = Request::create($uri, 'GET');
                        $request->headers->set('Accept', str_starts_with(ltrim($path, '/'), 'api/') ? 'application/json' : 'text/html');

                        if ($guard === 'user' && str_starts_with(ltrim($path, '/'), 'api/')) {
                            $request->headers->set('Authorization', 'Bearer '.$fixture['user']->createToken('lazy-sweep')->plainTextToken);
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
                );

                $summary['visited'] += $result['visited'];
                $summary['skipped'] += $result['skipped'];
                $summary['errors'] += $result['errors'];
                $summary['by_guard'][$guard] = [
                    'visited' => $result['visited'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'unique_violations' => count($result['violations']),
                    'error_uris' => $result['error_uris'],
                ];
                $combined = array_merge($combined, $result['violations']);
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
