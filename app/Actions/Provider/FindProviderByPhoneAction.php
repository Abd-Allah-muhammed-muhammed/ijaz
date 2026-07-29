<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Models\Provider;
use App\Support\Phone;

class FindProviderByPhoneAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(Phone $phone, ?int $categoryId = null): ?Provider
    {
        return $this->repository->findByPhone($phone, $categoryId);
    }
}
