<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;

class ListPropertyAdvisementsForDashboardAction
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
