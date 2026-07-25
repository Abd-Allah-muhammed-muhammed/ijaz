<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListAdminsAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
