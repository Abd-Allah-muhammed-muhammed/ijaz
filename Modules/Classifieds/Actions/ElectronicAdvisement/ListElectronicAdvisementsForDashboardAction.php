<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;

class ListElectronicAdvisementsForDashboardAction
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
