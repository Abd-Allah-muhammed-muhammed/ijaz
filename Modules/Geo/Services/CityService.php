<?php

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Actions\City\DeleteCityAction;
use Modules\Geo\Actions\City\ListCitiesAction;
use Modules\Geo\Actions\City\ShowCityAction;
use Modules\Geo\Actions\City\StoreCityAction;
use Modules\Geo\Actions\City\UpdateCityAction;
use Modules\Geo\Actions\Region\GetRegionsForDropdownAction;
use Modules\Geo\DTOs\StoreCityDTO;
use Modules\Geo\DTOs\UpdateCityDTO;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class CityService
{
    public function __construct(
        private readonly ListCitiesAction $listAction,
        private readonly StoreCityAction $storeAction,
        private readonly UpdateCityAction $updateAction,
        private readonly DeleteCityAction $deleteAction,
        private readonly ShowCityAction $showAction,
        private readonly GetRegionsForDropdownAction $regionsDropdownAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreCityDTO $dto): City
    {
        return $this->storeAction->handle($dto);
    }

    public function update(City $city, UpdateCityDTO $dto): City
    {
        return $this->updateAction->handle($city, $dto);
    }

    public function destroy(City $city): void
    {
        $this->deleteAction->handle($city);
    }

    public function show(City $city): City
    {
        return $this->showAction->handle($city);
    }

    /**
     * @return Collection<int, Region>
     */
    public function getRegionsForDropdown(): Collection
    {
        return $this->regionsDropdownAction->handle();
    }
}
