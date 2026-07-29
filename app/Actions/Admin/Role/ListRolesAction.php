<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListRolesAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
