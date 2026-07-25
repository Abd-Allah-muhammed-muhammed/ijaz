<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Models\Provider;

class FindProviderForApiAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(int|string $providerId): ?Provider
    {
        $provider = $this->repository->findById($providerId);

        if (! $provider) {
            return null;
        }

        return $this->repository->loadForApiGet($provider);
    }
}
