<?php

namespace Modules\Geo\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Models\Region;

interface RegionRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): Region;

    public function create(array $translations): Region;

    public function update(Region $region, array $translations): Region;

    public function delete(Region $region): void;

    public function loadForEdit(Region $region): Region;

    /**
     * @return Collection<int, Region>
     */
    public function getAllForDropdown(): Collection;
}
