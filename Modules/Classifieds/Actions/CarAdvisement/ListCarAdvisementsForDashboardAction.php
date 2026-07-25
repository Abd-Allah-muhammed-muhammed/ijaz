<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;

class ListCarAdvisementsForDashboardAction
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
