<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\Models\ProviderType;

class ShowProviderTypeAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
    ) {}

    public function handle(ProviderType $providerType): ProviderType
    {
        return $this->repository->loadForEdit($providerType);
    }
}
