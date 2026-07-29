<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListUsersAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
