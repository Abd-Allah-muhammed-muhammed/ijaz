<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;

class GetUserStatusCountsAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return array{total: int, active: int, blocked: int}
     */
    public function handle(): array
    {
        return $this->repository->statusCounts();
    }
}
