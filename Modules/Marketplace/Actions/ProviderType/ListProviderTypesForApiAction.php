<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\Models\ProviderType;

class ListProviderTypesForApiAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
    ) {}

    /** @return Collection<int, ProviderType> */
    public function handle(): Collection
    {
        return $this->repository->allWithTranslationsForApi();
    }
}
