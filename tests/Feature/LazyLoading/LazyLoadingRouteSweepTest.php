<?php

use App\Support\LazyLoading\LazyLoadingRouteSweeper;
use App\Support\LazyLoading\LazyLoadingSweepFixture;
use App\Support\LazyLoading\LazyLoadingViolationCollector;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

/**
 * Permanent regression harness: exhaustive GET-route sweep under
 * Model::preventLazyLoading(true). Collects every LazyLoadingViolationException
 * without aborting mid-request so one hit can surface multiple offenders.
 *
 * Re-run: php artisan test --compact tests/Feature/LazyLoading/LazyLoadingRouteSweepTest.php
 * CLI:    php artisan app:lazy-loading-route-sweep --fail-on-violation --json=storage/logs/lazy-loading-sweep.json
 *
 * Baseline: known model::relation pairs still awaiting fix. NEW pairs fail CI.
 * Shrink tests/Feature/LazyLoading/lazy_loading_baseline.php as fixes land;
 * empty array = full green.
 */
beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();
});

/**
 * @return list<string>
 */
function lazyLoadingViolationKeys(array $violations): array
{
    return array_values(array_map(
        static fn (array $row): string => $row['model'].'::'.$row['relation'],
        $violations,
    ));
}

test('exhaustive GET route sweep finds no new lazy-loading violations beyond baseline', function (): void {
    $fixture = LazyLoadingSweepFixture::seed();

    $collector = new LazyLoadingViolationCollector;
    $sweeper = new LazyLoadingRouteSweeper($collector);

    $collector->install(collectOnly: true);
    $collector->reset();

    $guards = [
        'admin' => function () use ($fixture): void {
            test()->actingAs($fixture['admin'], 'admin');
        },
        'provider' => function () use ($fixture): void {
            test()->actingAs($fixture['provider'], 'provider');
        },
        'user' => function () use ($fixture): void {
            Sanctum::actingAs($fixture['user'], ['*'], 'user-api');
            test()->actingAs($fixture['user'], 'web');
        },
        'guest' => function (): void {
            Auth::guard('web')->logout();
            Auth::guard('admin')->logout();
            Auth::guard('provider')->logout();
        },
    ];

    $queryExtras = [
        'api/v1/catalog/providers' => ['phone' => $fixture['provider']->phone],
        'api/v1/user/providers/get' => ['provider_id' => $fixture['provider']->id],
    ];

    $byGuard = [];

    try {
        foreach ($guards as $guard => $authenticate) {
            $result = $sweeper->sweep(
                httpGet: function (string $uri) {
                    if (str_starts_with($uri, '/api/') || str_contains($uri, '/api/')) {
                        return test()->getJson($uri);
                    }

                    return test()->get($uri);
                },
                parameterBag: $fixture['parameters'],
                guard: $guard,
                authenticate: $authenticate,
                queryByUriSuffix: $queryExtras,
            );

            $byGuard[$guard] = [
                'visited' => $result['visited'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'unique_violations' => count($result['violations']),
            ];
        }
    } finally {
        $collector->restore();
    }

    $unique = $collector->uniqueByModelRelation();
    $foundKeys = lazyLoadingViolationKeys($unique);
    sort($foundKeys);

    $baselinePath = __DIR__.'/lazy_loading_baseline.php';
    /** @var list<string> $baseline */
    $baseline = file_exists($baselinePath) ? require $baselinePath : [];
    sort($baseline);

    $reportPath = storage_path('logs/lazy-loading-sweep-last.json');
    if (! is_dir(dirname($reportPath))) {
        mkdir(dirname($reportPath), 0755, true);
    }
    file_put_contents($reportPath, json_encode([
        'by_guard' => $byGuard,
        'found' => $foundKeys,
        'baseline' => $baseline,
        'violations' => $unique,
        'raw_count' => $collector->count(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $new = array_values(array_diff($foundKeys, $baseline));
    $resolved = array_values(array_diff($baseline, $foundKeys));

    expect($new)->toBeEmpty(
        "New lazy-loading violations (update baseline only after confirming):\n".
        implode("\n", $new)."\n\nFull report: {$reportPath}"
    );

    // When a baseline entry is fixed, shrink the baseline file so CI stays honest.
    if ($resolved !== []) {
        expect($resolved)->toBeEmpty(
            "Baseline entries no longer reproduce — remove them from lazy_loading_baseline.php:\n".
            implode("\n", $resolved)
        );
    }
});
