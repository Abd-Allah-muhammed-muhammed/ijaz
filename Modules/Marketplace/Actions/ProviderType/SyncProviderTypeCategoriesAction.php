<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\Models\ProviderType;

class SyncProviderTypeCategoriesAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
    ) {}

    public function handle(ProviderType $providerType, ?array $categoryIds): void
    {
        $this->repository->syncCategories($providerType, $categoryIds);
    }
}
