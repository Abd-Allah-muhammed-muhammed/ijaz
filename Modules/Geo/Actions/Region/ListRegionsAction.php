<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;

class ListRegionsAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
