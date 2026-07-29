<?php

namespace Modules\Geo\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

interface CityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): City;

    public function create(int $regionId, array $translations): City;

    public function update(City $city, int $regionId, array $translations): City;

    public function delete(City $city): void;

    public function loadForEdit(City $city): City;

    /**
     * @return Collection<int, City>
     */
    public function listForSelect(?string $search = null, int $regionId = 0): Collection;

    public function paginateForApiByRegion(Region $region, ?string $search = null, int $perPage = 10): LengthAwarePaginator;
}
