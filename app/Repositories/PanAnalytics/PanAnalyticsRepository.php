<?php

namespace App\Repositories\PanAnalytics;

use App\Actions\PanAnalytics\CategorizePanElementAction;
use App\Contracts\PanAnalytics\PanAnalyticsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PanAnalyticsRepository implements PanAnalyticsRepositoryInterface
{
    public function __construct(
        private readonly CategorizePanElementAction $categorizePanElementAction,
    ) {}

    public function all(): Collection
    {
        return DB::table('pan_analytics')->get();
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
