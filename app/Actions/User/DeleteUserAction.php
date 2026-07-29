<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\Models\User;

class DeleteUserAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(User $user): void
    {
        $this->repository->delete($user);
    }
}
