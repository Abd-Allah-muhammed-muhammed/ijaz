<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;

class CountAllProvidersAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(): int
    {
        return $this->repository->countAll();
    }
}
