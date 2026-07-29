<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;

class UpdateProviderStatusAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(Provider $provider, UpdateProviderStatusDTO $dto): Provider
    {
        $provider = $this->repository->saveStatus($provider, $dto->status);

        if ($dto->status === ProviderStatusEnum::Blocked->value) {
            $this->repository->block($provider, $dto->blockDays ?: 0, $dto->blockReason);
        }

        return $provider;
    }
}
