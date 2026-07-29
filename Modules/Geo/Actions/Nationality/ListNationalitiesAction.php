<?php

namespace Modules\Geo\Actions\Nationality;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;

class ListNationalitiesAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
