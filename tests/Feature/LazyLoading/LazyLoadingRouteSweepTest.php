<?php

use App\Support\LazyLoading\LazyLoadingRouteSweeper;
use App\Support\LazyLoading\LazyLoadingSweepFixture;
use App\Support\LazyLoading\LazyLoadingViolationCollector;
use App\Support\LazyLoading\NonHttpLazyLoadingCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Listeners\NotifyOrderPaymentCompleted;
use Modules\Orders\Listeners\NotifyOrderPaymentFailed;
use Modules\Orders\Models\Order;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;

/**
 * Permanent regression harness: GET + POST/PUT/PATCH route sweep under
 * Model::preventLazyLoading(true), plus non-HTTP queued-listener probes.
 *
 * CLI: php artisan app:lazy-loading-route-sweep --fail-on-violation --writes
 * Composer: composer test:lazy-loading
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

test('exhaustive GET+write route sweep finds no new lazy-loading violations beyond baseline', function (): void {
    $fixture = LazyLoadingSweepFixture::seed();

    $collector = new LazyLoadingViolationCollector;
    $sweeper = new LazyLoadingRouteSweeper($collector);

    $collector->install(true);
    $collector->reset();

    Notification::fake();

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
    $writeCoverage = [
        'exercised' => 0,
        'skipped' => 0,
        'reached_response' => 0,
        'skip_reasons' => [],
    ];
    $forbiddenCoverage = [
        'total' => 0,
        'by_guard' => [],
        'sample_uris' => [],
    ];

    $httpCall = function (string $method, string $uri, array $payload = []) {
        $isApi = str_starts_with($uri, '/api/') || str_contains($uri, '/api/');
        $hasFiles = collect($payload)->flatten()->contains(
            static fn (mixed $value): bool => $value instanceof UploadedFile
        );

        return match (strtoupper($method)) {
            'GET' => $isApi ? test()->getJson($uri) : test()->get($uri),
            'POST' => ($isApi && ! $hasFiles) ? test()->postJson($uri, $payload) : test()->post($uri, $payload),
            'PUT' => ($isApi && ! $hasFiles) ? test()->putJson($uri, $payload) : test()->put($uri, $payload),
            'PATCH' => ($isApi && ! $hasFiles) ? test()->patchJson($uri, $payload) : test()->patch($uri, $payload),
            default => test()->get($uri),
        };
    };

    try {
        foreach ($guards as $guard => $authenticate) {
            $result = $sweeper->sweep(
                $httpCall,
                $fixture['parameters'],
                $guard,
                $authenticate,
                $queryExtras,
                true,
            );

            $byGuard[$guard] = [
                'visited' => $result['visited'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'forbidden' => $result['forbidden'],
                'write_exercised' => $result['write_exercised'],
                'write_reached_response' => $result['write_reached_response'],
                'unique_violations' => count($result['violations']),
                'error_uris' => $result['error_uris'],
            ];

            $writeCoverage['exercised'] += $result['write_exercised'];
            $writeCoverage['skipped'] += $result['write_skipped'];
            $writeCoverage['reached_response'] += $result['write_reached_response'];
            $writeCoverage['skip_reasons'] = array_merge(
                $writeCoverage['skip_reasons'],
                $result['write_skip_reasons'],
            );

            $forbiddenCoverage['total'] += $result['forbidden'];
            $forbiddenCoverage['by_guard'][$guard] = $result['forbidden'];
            if ($guard === 'admin' && $result['forbidden_uris'] !== []) {
                $forbiddenCoverage['sample_uris'] = array_slice($result['forbidden_uris'], 0, 20);
            }
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
        'write_coverage' => $writeCoverage,
        'forbidden_coverage' => $forbiddenCoverage,
        'write_coverage_notes' => [
            'guard_multiplied' => 'exercised/skipped sums across admin+provider+user+guest',
            'out_of_scope' => LazyLoadingRouteSweeper::OUT_OF_SCOPE_REASON,
            'out_of_scope_prefixes' => LazyLoadingRouteSweeper::SKIP_URI_PREFIXES,
            'distinct_app_write_routes' => 'see artisan route:list write methods minus SKIP_URI_PREFIXES/closures',
            'admin_root_fixture' => 'Admin.root is not mass-assignable; fixture must forceFill(root: true) or permission-gated dashboard GETs return 403 without exercising Resources',
        ],
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

    if ($resolved !== []) {
        expect($resolved)->toBeEmpty(
            "Baseline entries no longer reproduce — remove them from lazy_loading_baseline.php:\n".
            implode("\n", $resolved)
        );
    }
});

test('queued order payment listeners do not lazy-load product/order under strict mode', function (): void {
    expect(NonHttpLazyLoadingCatalog::queuedOrderPaymentListeners())->toContain(
        NotifyOrderPaymentCompleted::class,
        NotifyOrderPaymentFailed::class,
    );

    // Strict mode is configured app-wide in AppServiceProvider for non-production,
    // including queue workers (same bootstrap; no Queue::before override).
    expect(Model::preventsLazyLoading())->toBeTrue();

    $fixture = LazyLoadingSweepFixture::seed();
    $order = Order::query()->findOrFail($fixture['parameters']['order']);
    $offer = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($fixture['provider'])
        ->create();

    $payment = Payment::factory()->forProduct($offer, $fixture['user'])->create([
        'status' => PaymentStatusEnum::Accepted,
    ]);

    // Fresh payment without relations — mirrors queued listener deserialization.
    $fresh = Payment::query()->findOrFail($payment->id);

    $collector = new LazyLoadingViolationCollector;
    $collector->install(true);
    $collector->reset();
    Notification::fake();

    try {
        $collector->setContext('listener:NotifyOrderPaymentCompleted', 'queue');
        (new NotifyOrderPaymentCompleted)->handle(new PaymentCompleted($fresh));

        $freshFailed = Payment::query()->findOrFail($payment->id);
        $collector->setContext('listener:NotifyOrderPaymentFailed', 'queue');
        (new NotifyOrderPaymentFailed)->handle(new PaymentFailed($freshFailed));
    } finally {
        $collector->restore();
    }

    expect($collector->uniqueByModelRelation())->toBeEmpty(
        'Queued payment listeners still lazy-load: '.json_encode($collector->uniqueByModelRelation())
    );
});
