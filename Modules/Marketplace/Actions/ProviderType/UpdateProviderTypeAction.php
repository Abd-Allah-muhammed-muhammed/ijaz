<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\DTOs\UpdateProviderTypeDTO;
use Modules\Marketplace\Models\ProviderType;
use Throwable;

class UpdateProviderTypeAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
        private readonly SyncProviderTypeCategoriesAction $syncCategoriesAction,
    ) {}

    /** @throws Throwable */
    public function handle(ProviderType $providerType, UpdateProviderTypeDTO $dto): ProviderType
    {
        return DB::transaction(function () use ($providerType, $dto): ProviderType {
            $data = [
                'files' => $dto->files,
                'translations' => $dto->translations,
            ];

            if ($dto->image !== null) {
                $data['image'] = $dto->image->store('provider-types', 'public');
                $providerType->deleteImage();
            }

            $providerType = $this->repository->update($providerType, $data);
            $this->syncCategoriesAction->handle($providerType, $dto->categories);

            return $providerType;
        });
    }
}
