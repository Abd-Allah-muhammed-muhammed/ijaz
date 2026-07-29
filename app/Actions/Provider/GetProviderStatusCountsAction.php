<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;

class GetProviderStatusCountsAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return array{total: int, approved: int, pending: int, blocked: int}
     */
    public function handle(): array
    {
        return $this->repository->statusCounts();
    }
}
