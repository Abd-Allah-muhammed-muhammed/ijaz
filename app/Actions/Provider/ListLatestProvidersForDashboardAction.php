<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;

class ListLatestProvidersForDashboardAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Provider>
     */
    public function handle(int $limit = 4): Collection
    {
        return $this->repository->latestForDashboard($limit);
    }
}
