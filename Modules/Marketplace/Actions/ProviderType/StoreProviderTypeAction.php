<?php

namespace Modules\Marketplace\Actions\ProviderType;

use App\Support\HandlesTransactionalFileUpload;
use App\Support\LookupCache;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\DTOs\StoreProviderTypeDTO;
use Modules\Marketplace\Models\ProviderType;
use Throwable;

class StoreProviderTypeAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
        private readonly SyncProviderTypeCategoriesAction $syncCategoriesAction,
    ) {}

    /** @throws Throwable */
    public function handle(StoreProviderTypeDTO $dto): ProviderType
    {
        $providerType = $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'provider-types',
            disk: 'public',
            dbWork: function (?string $imagePath) use ($dto): ProviderType {
                $providerType = $this->repository->create([
                    'files' => $dto->files,
                    'image' => $imagePath,
                    'translations' => $dto->translations,
                ]);

                $this->syncCategoriesAction->handle($providerType, $dto->categories);

                return $providerType->fresh(['translations', 'translation', 'categories.translations']) ?? $providerType;
            },
        );

        LookupCache::forgetAllLocales('provider-types:all');

        return $providerType;
    }
}
