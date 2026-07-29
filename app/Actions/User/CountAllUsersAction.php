<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;

class CountAllUsersAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(): int
    {
        return $this->repository->countAll();
    }
}
