<?php

use App\Contracts\PanAnalytics\PanAnalyticsRepositoryInterface;
use App\Services\PanAnalytics\PanAnalyticsService;
use App\Support\LookupCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    LookupCache::forget('stats:pan-analytics:all');
});

/**
 * @return array{count: int, queries: list<array<string, mixed>>}
 */
function measurePanAnalyticsQueries(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    DB::flushQueryLog();

    return [
        'count' => count($queries),
        'queries' => $queries,
    ];
}

function seedPanAnalyticsLookupRows(): void
{
    DB::table('pan_analytics')->insert([
        [
            'name' => 'home-page',
            'impressions' => 100,
            'hovers' => 20,
            'clicks' => 10,
        ],
        [
            'name' => 'submit-btn',
            'impressions' => 50,
            'hovers' => 15,
            'clicks' => 40,
        ],
        [
            'name' => 'checkout-form-step',
            'impressions' => 30,
            'hovers' => 5,
            'clicks' => 5,
        ],
        [
            'name' => 'logo-icon',
            'impressions' => 20,
            'hovers' => 0,
            'clicks' => 1,
        ],
    ]);
}

test('PanAnalyticsRepository::all is identical cold vs warm and skips SELECT on hit', function (): void {
    seedPanAnalyticsLookupRows();

    $repo = app(PanAnalyticsRepositoryInterface::class);

    $coldMeasure = measurePanAnalyticsQueries(function () use ($repo, &$cold): void {
        $cold = $repo->all();
    });

    expect($cold)->toBeInstanceOf(Collection::class)
        ->and($cold)->toHaveCount(4)
        ->and($coldMeasure['count'])->toBe(1)
        ->and($coldMeasure['queries'][0]['query'])->toContain('pan_analytics');

    $warmMeasure = measurePanAnalyticsQueries(function () use ($repo, &$warm): void {
        $warm = $repo->all();
    });

    expect($warm)->toBeInstanceOf(Collection::class)
        ->and($warm->count())->toBe($cold->count())
        ->and($warm->pluck('name')->all())->toBe($cold->pluck('name')->all())
        ->and($warm->pluck('impressions')->all())->toBe($cold->pluck('impressions')->all())
        ->and($warm->pluck('hovers')->all())->toBe($cold->pluck('hovers')->all())
        ->and($warm->pluck('clicks')->all())->toBe($cold->pluck('clicks')->all())
        ->and($warmMeasure['count'])->toBe(0);
});

test('PanAnalyticsService::indexPayload summary is identical cold vs warm and skips full-table SELECT on hit', function (): void {
    seedPanAnalyticsLookupRows();

    $service = app(PanAnalyticsService::class);

    $coldMeasure = measurePanAnalyticsQueries(function () use ($service, &$cold): void {
        $cold = $service->indexPayload(null, 10);
    });

    expect($cold['summary'])->toBe([
        'total_impressions' => 200,
        'total_hovers' => 40,
        'total_clicks' => 56,
        'overall_engagement_rate' => 48.0,
    ])
        ->and($cold['categories'])->toBe([
            'page' => 1,
            'button' => 1,
            'form' => 1,
            'other' => 1,
        ])
        ->and($cold['funnelData'])->toBe([
            'impressions' => 200,
            'hovers' => 40,
            'clicks' => 56,
        ])
        ->and($coldMeasure['count'])->toBe(3);

    $warmMeasure = measurePanAnalyticsQueries(function () use ($service, &$warm): void {
        $warm = $service->indexPayload(null, 10);
    });

    $warmSql = collect($warmMeasure['queries'])->pluck('query')->all();

    expect($warm['summary'])->toBe($cold['summary'])
        ->and($warm['categories'])->toBe($cold['categories'])
        ->and($warm['funnelData'])->toBe($cold['funnelData'])
        // Full-table SELECT is cached; only pagination COUNT + page SELECT remain.
        ->and($warmMeasure['count'])->toBe(2)
        ->and($warmSql)->not->toContain('select * from `pan_analytics`')
        ->and($warmSql[0])->toContain('count(*)')
        ->and($warmSql[1])->toContain('order by');
});

test('PanAnalytics Tier 2 key relies on TTL only — no write-path invalidation hooks', function (): void {
    $files = [
        ...glob(base_path('app/Actions/PanAnalytics/**/*.php')) ?: [],
        ...glob(base_path('app/Services/PanAnalytics/**/*.php')) ?: [],
        ...glob(base_path('app/Repositories/PanAnalytics/**/*.php')) ?: [],
        ...glob(base_path('app/Http/Controllers/Dashboard/PanAnalyticsController.php')) ?: [],
    ];

    $needle = "LookupCache::forget('stats:pan-analytics:all')";

    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);

        expect($contents)->not->toContain($needle, "Unexpected invalidation in {$file}");
    }

    expect($files)->not->toBeEmpty();
});
