<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetProviderRegistrationCountsSinceAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<string, int>
     */
    public function handle(CarbonInterface $since): Collection
    {
        return $this->repository->registrationCountsSince($since);
    }
}
