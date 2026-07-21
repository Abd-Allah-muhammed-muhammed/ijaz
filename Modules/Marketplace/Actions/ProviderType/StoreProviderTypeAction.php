<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\DTOs\StoreProviderTypeDTO;
use Modules\Marketplace\Models\ProviderType;
use Throwable;

class StoreProviderTypeAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
        private readonly SyncProviderTypeCategoriesAction $syncCategoriesAction,
    ) {}

    /** @throws Throwable */
    public function handle(StoreProviderTypeDTO $dto): ProviderType
    {
        return DB::transaction(function () use ($dto): ProviderType {
            $providerType = $this->repository->create([
                'files' => $dto->files,
                'image' => $dto->image->store('provider-types', 'public'),
                'translations' => $dto->translations,
            ]);

            $this->syncCategoriesAction->handle($providerType, $dto->categories);

            return $providerType->fresh(['translations', 'translation', 'categories.translations']) ?? $providerType;
        });
    }
}
