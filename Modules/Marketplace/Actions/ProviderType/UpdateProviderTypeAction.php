<?php

namespace Modules\Marketplace\Actions\ProviderType;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\DTOs\UpdateProviderTypeDTO;
use Modules\Marketplace\Models\ProviderType;
use Throwable;

class UpdateProviderTypeAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
        private readonly SyncProviderTypeCategoriesAction $syncCategoriesAction,
    ) {}

    /** @throws Throwable */
    public function handle(ProviderType $providerType, UpdateProviderTypeDTO $dto): ProviderType
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'provider-types',
            disk: 'public',
            previousPath: $dto->image !== null ? $providerType->image : null,
            dbWork: function (?string $imagePath) use ($providerType, $dto): ProviderType {
                $data = [
                    'files' => $dto->files,
                    'translations' => $dto->translations,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $providerType = $this->repository->update($providerType, $data);
                $this->syncCategoriesAction->handle($providerType, $dto->categories);

                return $providerType;
            },
        );
    }
}
