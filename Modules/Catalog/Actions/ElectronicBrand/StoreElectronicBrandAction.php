<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;
use Throwable;

class StoreElectronicBrandAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreElectronicBrandDTO $dto): ElectronicBrand
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'electronic_brands',
            disk: 'public',
            dbWork: function (?string $imagePath) use ($dto): ElectronicBrand {
                $electronicBrand = $this->repository->create([
                    'image' => $imagePath,
                    'is_active' => $dto->isActive,
                ]);
                $electronicBrand->translations()->createMany($dto->translations);

                return $electronicBrand->load(['translation']);
            },
        );
    }
}
