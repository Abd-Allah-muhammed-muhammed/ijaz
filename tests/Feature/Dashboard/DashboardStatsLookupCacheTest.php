<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use App\Repositories\Provider\ProviderManagementRepository;
use App\Repositories\User\UserManagementRepository;
use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Repositories\OrderRepository;

beforeEach(function (): void {
    LookupCache::forget('stats:orders:dashboard');
    LookupCache::forget('stats:guarantor:dashboard');
    LookupCache::forget('stats:users:status-counts');
    LookupCache::forget('stats:providers:status-counts');
});

/**
 * @return array{count: int, queries: list<array<string, mixed>>}
 */
function measureStatsQueries(Closure $callback): array
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

test('OrderRepository::dashboardStats is byte-identical cold vs warm and skips COUNT queries on hit', function (): void {
    Order::factory()->create(['status' => OrderStatusEnum::New]);
    Order::factory()->create(['status' => OrderStatusEnum::InProgress]);
    Order::factory()->create(['status' => OrderStatusEnum::EndedByClient]);
    Order::factory()->create(['status' => OrderStatusEnum::CancelledByClient]);

    $repo = app(OrderRepository::class);

    $coldMeasure = measureStatsQueries(function () use ($repo, &$cold): void {
        $cold = $repo->dashboardStats();
    });

    expect($cold)->toBeArray()
        ->and(array_keys($cold))->toBe(['total', 'active', 'pending', 'completed', 'cancelled'])
        ->and($cold['total'])->toBe(4)
        ->and($cold['active'])->toBe(1)
        ->and($cold['pending'])->toBe(1)
        ->and($cold['completed'])->toBe(1)
        ->and($cold['cancelled'])->toBe(1)
        ->and($coldMeasure['count'])->toBe(5);

    $warmMeasure = measureStatsQueries(function () use ($repo, &$warm): void {
        $warm = $repo->dashboardStats();
    });

    expect($warm)->toBe($cold)
        ->and(gettype($warm))->toBe('array')
        ->and($warmMeasure['count'])->toBe(0);
});

test('GuarantorRepository::getDashboardStats is byte-identical cold vs warm and skips COUNT queries on hit', function (): void {
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::PendingAdmin]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::InProgress]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Overdue]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Ended]);

    $repo = app(GuarantorRepository::class);

    $coldMeasure = measureStatsQueries(function () use ($repo, &$cold): void {
        $cold = $repo->getDashboardStats();
    });

    expect($cold)->toBe([
        'total' => 4,
        'pending_admin' => 1,
        'in_progress' => 1,
        'overdue' => 1,
        'ended' => 1,
    ])->and($coldMeasure['count'])->toBe(5);

    $warmMeasure = measureStatsQueries(function () use ($repo, &$warm): void {
        $warm = $repo->getDashboardStats();
    });

    expect($warm)->toBe($cold)
        ->and(gettype($warm))->toBe('array')
        ->and($warmMeasure['count'])->toBe(0);
});

test('UserManagementRepository::statusCounts is byte-identical cold vs warm and skips GROUP BY on hit', function (): void {
    User::factory()->create(['status' => UserStatusEnum::Active]);
    User::factory()->create(['status' => UserStatusEnum::Active]);
    User::factory()->create(['status' => UserStatusEnum::Blocked]);

    $repo = app(UserManagementRepository::class);

    $coldMeasure = measureStatsQueries(function () use ($repo, &$cold): void {
        $cold = $repo->statusCounts();
    });

    expect($cold)->toBe([
        'total' => 3,
        'active' => 2,
        'blocked' => 1,
    ])->and($coldMeasure['count'])->toBe(1);

    $warmMeasure = measureStatsQueries(function () use ($repo, &$warm): void {
        $warm = $repo->statusCounts();
    });

    expect($warm)->toBe($cold)
        ->and(gettype($warm))->toBe('array')
        ->and($warmMeasure['count'])->toBe(0);
});

test('ProviderManagementRepository::statusCounts is byte-identical cold vs warm and skips GROUP BY on hit', function (): void {
    createWalletProvider(['status' => ProviderStatusEnum::Approved]);
    createWalletProvider(['status' => ProviderStatusEnum::Pending]);
    createWalletProvider(['status' => ProviderStatusEnum::Blocked]);

    $repo = app(ProviderManagementRepository::class);

    $coldMeasure = measureStatsQueries(function () use ($repo, &$cold): void {
        $cold = $repo->statusCounts();
    });

    expect($cold)->toBe([
        'total' => 3,
        'approved' => 1,
        'pending' => 1,
        'blocked' => 1,
    ])->and($coldMeasure['count'])->toBe(1);

    $warmMeasure = measureStatsQueries(function () use ($repo, &$warm): void {
        $warm = $repo->statusCounts();
    });

    expect($warm)->toBe($cold)
        ->and(gettype($warm))->toBe('array')
        ->and($warmMeasure['count'])->toBe(0);
});

test('Tier 2 dashboard stats keys rely on TTL only — no write-path invalidation hooks', function (): void {
    // 30s staleness is acceptable for summary badges; Actions must not forget these keys.
    $files = [
        ...glob(base_path('Modules/Orders/Actions/**/*.php')) ?: [],
        ...glob(base_path('Modules/Guarantor/Actions/**/*.php')) ?: [],
        ...glob(base_path('app/Actions/**/*.php')) ?: [],
        ...glob(base_path('app/Services/**/*.php')) ?: [],
    ];

    $forbidden = [
        "LookupCache::forget('stats:orders:dashboard')",
        "LookupCache::forget('stats:guarantor:dashboard')",
        "LookupCache::forget('stats:users:status-counts')",
        "LookupCache::forget('stats:providers:status-counts')",
    ];

    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);

        foreach ($forbidden as $needle) {
            expect($contents)->not->toContain($needle, "Unexpected invalidation in {$file}");
        }
    }

    expect($files)->not->toBeEmpty();
});
