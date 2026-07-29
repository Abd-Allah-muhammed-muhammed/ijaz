<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;

class ListCitiesAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
