<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Models\Provider;

class EditProviderAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(Provider $provider): Provider
    {
        return $this->repository->loadForEdit($provider);
    }
}
