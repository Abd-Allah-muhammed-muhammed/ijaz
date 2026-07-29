<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GetUserRegistrationCountsSinceAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<string, int>
     */
    public function handle(CarbonInterface $since): Collection
    {
        return $this->repository->registrationCountsSince($since);
    }
}
