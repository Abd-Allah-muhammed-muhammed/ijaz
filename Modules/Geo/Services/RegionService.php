<?php

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Actions\Region\DeleteRegionAction;
use Modules\Geo\Actions\Region\GetRegionsForDropdownAction;
use Modules\Geo\Actions\Region\ListRegionsAction;
use Modules\Geo\Actions\Region\ListRegionsForApiAction;
use Modules\Geo\Actions\Region\ListRegionsForSelectAction;
use Modules\Geo\Actions\Region\ShowRegionAction;
use Modules\Geo\Actions\Region\StoreRegionAction;
use Modules\Geo\Actions\Region\UpdateRegionAction;
use Modules\Geo\DTOs\StoreRegionDTO;
use Modules\Geo\DTOs\UpdateRegionDTO;
use Modules\Geo\Models\Region;

class RegionService
{
    public function __construct(
        private readonly ListRegionsAction $listAction,
        private readonly ListRegionsForApiAction $listForApiAction,
        private readonly ListRegionsForSelectAction $listForSelectAction,
        private readonly StoreRegionAction $storeAction,
        private readonly UpdateRegionAction $updateAction,
        private readonly DeleteRegionAction $deleteAction,
        private readonly ShowRegionAction $showAction,
        private readonly GetRegionsForDropdownAction $dropdownAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreRegionDTO $dto): Region
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Region $region, UpdateRegionDTO $dto): Region
    {
        return $this->updateAction->handle($region, $dto);
    }

    public function destroy(Region $region): void
    {
        $this->deleteAction->handle($region);
    }

    public function show(Region $region): Region
    {
        return $this->showAction->handle($region);
    }

    /**
     * @return Collection<int, Region>
     */
    public function getAllForDropdown(): Collection
    {
        return $this->dropdownAction->handle();
    }

    /**
     * @return Collection<int, Region>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }

    public function listForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($search, $perPage);
    }
}
