<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListLatestUsersForDashboardAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function handle(int $limit = 4): Collection
    {
        return $this->repository->latestForDashboard($limit);
    }
}
