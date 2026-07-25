<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;

class ListInstituteAdvisementsForDashboardAction
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
