<?php

namespace App\Services\PanAnalytics;

use App\Actions\PanAnalytics\CategorizePanElementAction;
use App\Contracts\PanAnalytics\PanAnalyticsRepositoryInterface;
use App\Http\Resources\Dashboard\PanAnalyticsResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PanAnalyticsService
{
    public function __construct(
        private readonly PanAnalyticsRepositoryInterface $repository,
        private readonly CategorizePanElementAction $categorizePanElementAction,
    ) {}

    /**
     * @return array{
     *     analytics: LengthAwarePaginator,
     *     summary: array{total_impressions: int|float, total_hovers: int|float, total_clicks: int|float, overall_engagement_rate: float|int},
     *     categories: array<string, int>,
     *     topElements: mixed,
     *     funnelData: array{impressions: int|float, hovers: int|float, clicks: int|float}
     * }
     */
    public function indexPayload(?string $category, int $perPage): array
    {
        $allAnalytics = $this->repository->all();

        $summary = [
            'total_impressions' => $allAnalytics->sum('impressions'),
            'total_hovers' => $allAnalytics->sum('hovers'),
            'total_clicks' => $allAnalytics->sum('clicks'),
        ];

        $summary['overall_engagement_rate'] = $summary['total_impressions'] > 0
            ? round((($summary['total_hovers'] + $summary['total_clicks']) / $summary['total_impressions']) * 100, 2)
            : 0;

        $categorizedData = $allAnalytics->map(function ($item) {
            return (object) array_merge((array) $item, [
                'category' => $this->categorizePanElementAction->handle($item->name),
            ]);
        });

        $categories = $categorizedData->groupBy('category')->map->count()->toArray();

        $topElements = PanAnalyticsResource::collection(
            $allAnalytics->sortByDesc('clicks')->take(10)->values()
        );

        $analytics = $this->repository->paginateFiltered($category, $perPage);
        $analytics->getCollection()->transform(function ($item) {
            return (new PanAnalyticsResource($item))->resolve();
        });

        return [
            'analytics' => $analytics,
            'summary' => $summary,
            'categories' => $categories,
            'topElements' => $topElements,
            'funnelData' => [
                'impressions' => $summary['total_impressions'],
                'hovers' => $summary['total_hovers'],
                'clicks' => $summary['total_clicks'],
            ],
        ];
    }

    /**
     * Rows shaped for CSV export (category + rates precomputed).
     *
     * @return Collection<int, array{
     *     id: mixed,
     *     name: mixed,
     *     category: string,
     *     impressions: mixed,
     *     hovers: mixed,
     *     clicks: mixed,
     *     engagement_rate: float,
     *     click_rate: float
     * }>
     */
    public function exportRows(): Collection
    {
        return $this->repository->all()->map(function ($item): array {
            $impressions = $item->impressions ?: 1;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $this->categorizePanElementAction->handle($item->name),
                'impressions' => $item->impressions,
                'hovers' => $item->hovers,
                'clicks' => $item->clicks,
                'engagement_rate' => round(($item->hovers / $impressions) * 100, 2),
                'click_rate' => round(($item->clicks / $impressions) * 100, 2),
            ];
        });
    }

    public function clear(): void
    {
        DB::transaction(fn () => $this->repository->truncate());
    }
}
