<?php

namespace Modules\Geo\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Models\City;

interface CityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): City;

    public function create(int $regionId, array $translations): City;

    public function update(City $city, int $regionId, array $translations): City;

    public function delete(City $city): void;

    public function loadForEdit(City $city): City;
}
