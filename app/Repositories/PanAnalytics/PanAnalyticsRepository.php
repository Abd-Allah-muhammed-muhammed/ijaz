<?php

namespace App\Repositories\PanAnalytics;

use App\Actions\PanAnalytics\CategorizePanElementAction;
use App\Contracts\PanAnalytics\PanAnalyticsRepositoryInterface;
use App\Support\LookupCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PanAnalyticsRepository implements PanAnalyticsRepositoryInterface
{
    /**
     * Short TTL for the full-table snapshot used by summary / categories / topElements.
     * Pure expiry only — clear() intentionally does not invalidate (brief staleness OK).
     */
    private const ALL_ROWS_TTL_SECONDS = 60;

    public function __construct(
        private readonly CategorizePanElementAction $categorizePanElementAction,
    ) {}

    public function all(): Collection
    {
        /** @var Collection<int, object> $rows */
        $rows = LookupCache::rememberFor(
            'stats:pan-analytics:all',
            self::ALL_ROWS_TTL_SECONDS,
            fn (): Collection => DB::table('pan_analytics')->get(),
        );

        return $rows;
    }

    public function paginateFiltered(?string $category, int $perPage): LengthAwarePaginator
    {
        $query = DB::table('pan_analytics')->select('*');

        if ($category && $category !== 'all') {
            $this->applyCategoryFilter($query, $category);
        }

        return $query->orderByDesc('clicks')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function truncate(): void
    {
        DB::table('pan_analytics')->truncate();
    }

    private function applyCategoryFilter(Builder $query, string $category): void
    {
        $patterns = $this->categorizePanElementAction->patternsFor($category);

        if ($patterns === []) {
            return;
        }

        $query->where(function (Builder $q) use ($patterns): void {
            foreach ($patterns as $index => $pattern) {
                if ($index === 0) {
                    $q->where('name', 'like', $pattern);

                    continue;
                }

                $q->orWhere('name', 'like', $pattern);
            }
        });
    }
}
