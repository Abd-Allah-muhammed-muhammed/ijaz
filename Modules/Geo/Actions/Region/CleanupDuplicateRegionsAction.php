<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Geo\Actions\Region\DeleteRegionAction as DeleteSingleRegionAction;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\DTOs\DuplicateRegionsCleanupResult;
use Modules\Geo\Models\Region;
use Throwable;

class CleanupDuplicateRegionsAction
{
    /**
     * Production-confirmed orphaned duplicate seeder regions (IDs 14–26).
     * Do not derive this list from a title-duplicate query.
     *
     * @var list<int>
     */
    public const DUPLICATE_REGION_IDS = [14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26];

    public function __construct(
        private readonly RegionRepositoryInterface $repository,
        private readonly DeleteSingleRegionAction $deleteRegionAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(bool $dryRun = false): DuplicateRegionsCleanupResult
    {
        $regions = $this->repository->listByIds(self::DUPLICATE_REGION_IDS);

        /** @var list<array{id: int, title_ar: string|null}> $deleted */
        $deleted = $regions
            ->map(fn (Region $region): array => [
                'id' => $region->id,
                'title_ar' => $region->translate('ar')?->title,
            ])
            ->values()
            ->all();

        $cityCount = (int) $regions->sum('cities_count');

        if ($dryRun || $regions->isEmpty()) {
            return new DuplicateRegionsCleanupResult(
                regionCount: $regions->count(),
                cityCount: $cityCount,
                deleted: $deleted,
                dryRun: $dryRun,
            );
        }

        DB::transaction(function () use ($regions): void {
            foreach ($regions as $region) {
                $this->deleteRegionAction->handle($region);
            }
        });

        foreach ($deleted as $row) {
            Log::info('geo:cleanup-duplicate-regions deleted region', [
                'id' => $row['id'],
                'title_ar' => $row['title_ar'],
            ]);
        }

        return new DuplicateRegionsCleanupResult(
            regionCount: count($deleted),
            cityCount: $cityCount,
            deleted: $deleted,
            dryRun: false,
        );
    }
}
